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

from app.prototypes import PrototypeStore, blend_similarities

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
    aliases: tuple[str, ...] = ()
    is_philippine: bool = False
    medicinal_rating: int | None = None


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
        raw_aliases = row.get("aliases") or []
        if isinstance(raw_aliases, str):
            raw_aliases = [a.strip() for a in raw_aliases.split(",") if a.strip()]
        aliases = tuple(str(a).strip() for a in raw_aliases if str(a).strip())
        taxa.append(
            Taxon(
                scientific_name=name,
                family=(row.get("family") or "").strip(),
                common_name=row.get("common_name"),
                aliases=aliases,
                is_philippine=bool(row.get("is_philippine", False)),
                medicinal_rating=row.get("medicinal_rating"),
            )
        )
    return taxa


def build_prompts(taxon: Taxon, plant_part: str) -> list[str]:
    """Generate an ensemble of prompt templates combining scientific, common, and vernacular names."""
    prompts: list[str] = []

    if plant_part == DEFAULT_PLANT_PART:
        prompts.append(f"a photo of {taxon.family} {taxon.scientific_name}")
        prompts.append(f"a photo of {taxon.scientific_name}")
        if taxon.common_name:
            prompts.append(f"a photo of {taxon.common_name}, a plant of the {taxon.family} family")
            prompts.append(f"a photo of {taxon.common_name} ({taxon.scientific_name})")
            prompts.append(f"a photo of {taxon.common_name}")
        for alias in taxon.aliases:
            if alias:
                prompts.append(f"a photo of {alias} ({taxon.scientific_name})")
                prompts.append(f"a photo of {alias}")
    else:
        prompts.append(f"a photo of the {plant_part} of {taxon.family} {taxon.scientific_name}")
        prompts.append(f"a photo of the {plant_part} of {taxon.scientific_name}")
        if taxon.common_name:
            prompts.append(f"a photo of the {plant_part} of {taxon.common_name} ({taxon.scientific_name})")
            prompts.append(f"a photo of the {plant_part} of {taxon.common_name}")
        for alias in taxon.aliases:
            if alias:
                prompts.append(f"a photo of the {plant_part} of {alias} ({taxon.scientific_name})")
                prompts.append(f"a photo of the {plant_part} of {alias}")

    # Deduplicate while preserving order and collapsing whitespace
    unique_prompts: list[str] = []
    seen = set()
    for p in prompts:
        cleaned = " ".join(p.split())
        if cleaned and cleaned not in seen:
            seen.add(cleaned)
            unique_prompts.append(cleaned)
    return unique_prompts


def build_prompt(taxon: Taxon, plant_part: str) -> str:
    """Return the primary prompt for single-prompt contexts or backward compatibility."""
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
        self.prototype_store = PrototypeStore()
        self._proto_matrix = np.zeros((0, 0), dtype=np.float32)
        self._proto_mask = np.zeros(0, dtype=bool)
        self._philippine_mask = np.zeros(0, dtype=bool)
        self.visual_weight = float(os.environ.get("ML_VISUAL_WEIGHT", "0.70"))
        self.philippine_prior = float(os.environ.get("ML_PHILIPPINE_PRIOR", "0.25"))
        self.use_tta = os.environ.get("ML_USE_TTA", "true").lower() in ("true", "1", "yes")

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
        self._philippine_mask = np.array([t.is_philippine for t in taxa], dtype=bool)
        self.prototype_store.load()
        if len(self.prototype_store) > 0 and len(taxa) > 0:
            dim = next(iter(embeddings.values())).shape[1] if embeddings else 512
            self._proto_matrix, self._proto_mask = self.prototype_store.matrix_for(taxa, dim=dim)
            logger.info("Loaded visual prototypes for %d / %d taxa", int(self._proto_mask.sum()), len(taxa))
        else:
            self._proto_matrix = np.zeros((len(taxa), 512), dtype=np.float32)
            self._proto_mask = np.zeros(len(taxa), dtype=bool)

    def _text_embeddings_for(self, taxa: list[Taxon], plant_part: str) -> np.ndarray:
        if not taxa:
            return np.zeros((0, 0), dtype=np.float32)

        taxa_prompts = [build_prompts(t, plant_part) for t in taxa]

        key = hashlib.sha1(
            json.dumps(
                {
                    "taxa": [[t.family, t.scientific_name, t.common_name or "", list(t.aliases)] for t in taxa],
                    "template": "ensemble_v2",
                    "part": plant_part,
                    "model": self.model_version,
                },
                sort_keys=True,
            ).encode()
        ).hexdigest()
        cache_file = CACHE_DIR / f"text_emb_{key}.npy"
        if cache_file.exists():
            cached = np.load(cache_file)
            if cached.shape[0] == len(taxa):
                return cached

        all_prompts: list[str] = []
        bounds: list[tuple[int, int]] = []
        for p_list in taxa_prompts:
            start = len(all_prompts)
            all_prompts.extend(p_list)
            bounds.append((start, len(all_prompts)))

        torch = self._torch
        chunks: list[np.ndarray] = []
        with torch.no_grad():
            for start in range(0, len(all_prompts), 256):
                tokens = self.tokenizer(all_prompts[start : start + 256]).to(self.device)
                features = self.model.encode_text(tokens).float()
                features = features / features.norm(dim=-1, keepdim=True)
                chunks.append(features.cpu().numpy())

        all_embs = np.concatenate(chunks).astype(np.float32)
        dim = all_embs.shape[1] if len(all_embs) > 0 else 512
        taxon_embs = np.zeros((len(taxa), dim), dtype=np.float32)

        for i, (start, end) in enumerate(bounds):
            if end > start:
                mean_vec = all_embs[start:end].mean(axis=0)
                norm = np.linalg.norm(mean_vec)
                taxon_embs[i] = mean_vec / norm if norm > 0 else mean_vec

        CACHE_DIR.mkdir(parents=True, exist_ok=True)
        tmp = cache_file.with_suffix(".tmp.npy")
        np.save(tmp, taxon_embs)
        tmp.replace(cache_file)
        return taxon_embs

    def predict(self, image: Image.Image, image_bytes: bytes, plant_part: str) -> np.ndarray:
        torch = self._torch
        with torch.no_grad():
            if self.use_tta:
                views = [image, image.transpose(Image.Transpose.FLIP_LEFT_RIGHT)]
                w, h = image.size
                if w >= 32 and h >= 32:
                    views.append(image.crop((int(w * 0.05), int(h * 0.05), int(w * 0.95), int(h * 0.95))))
                pixels = torch.stack([self.preprocess(v) for v in views]).to(self.device)
                features = self.model.encode_image(pixels).float()
                features = features / features.norm(dim=-1, keepdim=True)
                avg_feat = features.mean(dim=0, keepdim=True)
                avg_feat = avg_feat / avg_feat.norm(dim=-1, keepdim=True)
                image_emb = avg_feat.cpu().numpy()[0]
            else:
                pixels = self.preprocess(image).unsqueeze(0).to(self.device)
                features = self.model.encode_image(pixels).float()
                features = features / features.norm(dim=-1, keepdim=True)
                image_emb = features.cpu().numpy()[0]

        text_sim = self._text_embeddings[plant_part] @ image_emb
        if np.any(self._proto_mask):
            proto_sim = self._proto_matrix @ image_emb
            combined = blend_similarities(text_sim, proto_sim, self._proto_mask, self.visual_weight)
        else:
            combined = text_sim

        logits = self.logit_scale * combined
        if hasattr(self, "_philippine_mask") and np.any(self._philippine_mask) and self.philippine_prior > 0:
            logits[self._philippine_mask] += self.philippine_prior

        return softmax(logits.astype(np.float64))

        text_sim = self._text_embeddings[plant_part] @ image_emb
        if np.any(self._proto_mask):
            proto_sim = self._proto_matrix @ image_emb
            combined = blend_similarities(text_sim, proto_sim, self._proto_mask, self.visual_weight)
        else:
            combined = text_sim

        logits = self.logit_scale * combined
        return softmax(logits.astype(np.float64))



def make_backend(name: str | None = None) -> Backend:
    name = (name or os.environ.get("ML_BACKEND") or "bioclip").strip().lower()
    if name == "mock":
        return MockBackend()
    if name == "bioclip":
        return BioclipBackend()
    raise ValueError(f"Unknown ML_BACKEND '{name}' (expected bioclip or mock)")
