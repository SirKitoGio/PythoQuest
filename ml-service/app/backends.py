"""Identification backends: BioCLIP (real, zero-shot) and a deterministic mock.

torch / open_clip are imported lazily inside BioclipBackend so the mock backend
(used by the test-suite) never pays for, or needs, a torch import.
"""

from __future__ import annotations

import hashlib
import json
import logging
import os
from dataclasses import dataclass
from pathlib import Path

import numpy as np
from PIL import Image

logger = logging.getLogger("ml-service")

SERVICE_DIR = Path(__file__).resolve().parent.parent
DEFAULT_TAXA_PATH = SERVICE_DIR / "data" / "taxa.json"
SAMPLE_TAXA_PATH = SERVICE_DIR / "data" / "taxa.sample.json"
CACHE_DIR = SERVICE_DIR / "cache"

PLANT_PARTS = ("flower", "leaf", "stem", "bark", "fruit", "whole plant")
DEFAULT_PLANT_PART = "whole plant"

#: BioCLIP was trained on taxonomic strings, so the prompt leads with family + binomial.
PROMPT_TEMPLATE = "a photo of {family} {scientific_name}"
PART_PROMPT_TEMPLATE = "a photo of the {part} of {family} {scientific_name}"


@dataclass(frozen=True)
class Taxon:
    scientific_name: str
    family: str
    common_name: str | None = None


def resolve_taxa_path() -> Path:
    """Return TAXA_PATH (relative paths resolve against the service dir), falling back to the sample."""
    configured = os.environ.get("TAXA_PATH")
    path = Path(configured) if configured else DEFAULT_TAXA_PATH
    if not path.is_absolute():
        path = SERVICE_DIR / path
    if path.exists():
        return path
    logger.warning("Taxa file %s not found; falling back to %s", path, SAMPLE_TAXA_PATH)
    return SAMPLE_TAXA_PATH


def load_taxa(path: Path) -> list[Taxon]:
    raw = json.loads(path.read_text(encoding="utf-8"))
    taxa: list[Taxon] = []
    seen: set[str] = set()
    for row in raw:
        name = (row.get("scientific_name") or "").strip()
        if not name or name in seen:
            continue
        seen.add(name)
        taxa.append(
            Taxon(
                scientific_name=name,
                family=(row.get("family") or "").strip(),
                common_name=row.get("common_name"),
            )
        )
    return taxa


def build_prompt(taxon: Taxon, plant_part: str) -> str:
    template = PROMPT_TEMPLATE if plant_part == DEFAULT_PLANT_PART else PART_PROMPT_TEMPLATE
    text = template.format(part=plant_part, family=taxon.family, scientific_name=taxon.scientific_name)
    return " ".join(text.split())  # collapse double spaces when family is empty


def softmax(logits: np.ndarray) -> np.ndarray:
    shifted = logits - logits.max()
    exp = np.exp(shifted)
    return exp / exp.sum()


class Backend:
    name: str = "base"
    model_version: str = ""

    def __init__(self) -> None:
        self.taxa: list[Taxon] = []
        self.loaded = False

    def load(self) -> None:
        self.loaded = True

    def set_taxa(self, taxa: list[Taxon]) -> None:
        self.taxa = taxa

    def predict(self, image: Image.Image, image_bytes: bytes, plant_part: str) -> np.ndarray:
        """Return softmax probabilities aligned with self.taxa."""
        raise NotImplementedError


class MockBackend(Backend):
    """Deterministic, offline: ranks taxa by a stable hash of the image bytes."""

    name = "mock"
    model_version = "mock-1"

    def predict(self, image: Image.Image, image_bytes: bytes, plant_part: str) -> np.ndarray:
        seed_material = hashlib.sha256(image_bytes + plant_part.encode()).digest()
        rng = np.random.default_rng(int.from_bytes(seed_material[:8], "big"))
        logits = rng.normal(size=len(self.taxa)) * 4.0
        return softmax(logits)


class BioclipBackend(Backend):
    name = "bioclip"
    HF_MODEL_ID = "hf-hub:imageomics/bioclip"

    def __init__(self) -> None:
        super().__init__()
        self.model_version = f"{self.HF_MODEL_ID}@open_clip"
        self._text_embeddings: dict[str, np.ndarray] = {}

    def load(self) -> None:
        import open_clip
        import torch

        self._torch = torch
        requested = os.environ.get("ML_DEVICE")
        if requested:
            self.device = requested
        elif torch.backends.mps.is_available():
            self.device = "mps"
        else:
            self.device = "cpu"

        model, _, preprocess = open_clip.create_model_and_transforms(self.HF_MODEL_ID)
        model.eval().to(self.device)
        self.model = model
        self.preprocess = preprocess
        self.tokenizer = open_clip.get_tokenizer(self.HF_MODEL_ID)
        self.logit_scale = float(model.logit_scale.exp().item())
        self.model_version = f"{self.HF_MODEL_ID}@open_clip-{open_clip.__version__}"
        self.loaded = True
        logger.info("BioCLIP loaded on %s (logit_scale=%.2f)", self.device, self.logit_scale)

    def set_taxa(self, taxa: list[Taxon]) -> None:
        embeddings = {part: self._text_embeddings_for(taxa, part) for part in PLANT_PARTS}
        self.taxa = taxa
        self._text_embeddings = embeddings

    def _text_embeddings_for(self, taxa: list[Taxon], plant_part: str) -> np.ndarray:
        prompts = [build_prompt(t, plant_part) for t in taxa]
        if not prompts:
            return np.zeros((0, 0), dtype=np.float32)

        key = hashlib.sha1(
            json.dumps(
                {
                    "taxa": [[t.family, t.scientific_name] for t in taxa],
                    "template": PART_PROMPT_TEMPLATE if plant_part != DEFAULT_PLANT_PART else PROMPT_TEMPLATE,
                    "part": plant_part,
                    "model": self.model_version,
                },
                sort_keys=True,
            ).encode()
        ).hexdigest()
        cache_file = CACHE_DIR / f"text_emb_{key}.npy"
        if cache_file.exists():
            cached = np.load(cache_file)
            if cached.shape[0] == len(prompts):
                return cached

        torch = self._torch
        chunks: list[np.ndarray] = []
        with torch.no_grad():
            for start in range(0, len(prompts), 256):
                tokens = self.tokenizer(prompts[start : start + 256]).to(self.device)
                features = self.model.encode_text(tokens).float()
                features = features / features.norm(dim=-1, keepdim=True)
                chunks.append(features.cpu().numpy())
        embeddings = np.concatenate(chunks).astype(np.float32)

        CACHE_DIR.mkdir(parents=True, exist_ok=True)
        tmp = cache_file.with_suffix(".tmp.npy")
        np.save(tmp, embeddings)
        tmp.replace(cache_file)
        return embeddings

    def predict(self, image: Image.Image, image_bytes: bytes, plant_part: str) -> np.ndarray:
        torch = self._torch
        with torch.no_grad():
            pixels = self.preprocess(image).unsqueeze(0).to(self.device)
            features = self.model.encode_image(pixels).float()
            features = features / features.norm(dim=-1, keepdim=True)
        image_emb = features.cpu().numpy()[0]
        logits = self.logit_scale * (self._text_embeddings[plant_part] @ image_emb)
        return softmax(logits.astype(np.float64))


def make_backend(name: str | None = None) -> Backend:
    name = (name or os.environ.get("ML_BACKEND") or "bioclip").strip().lower()
    if name == "mock":
        return MockBackend()
    if name == "bioclip":
        return BioclipBackend()
    raise ValueError(f"Unknown ML_BACKEND '{name}' (expected bioclip or mock)")
