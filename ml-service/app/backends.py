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
from PIL import Image, ImageFilter, ImageOps

from app.prototypes import (
    PrototypeStore,
    blend_similarities,
    calibrate_prototype_similarities,
)

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
    category: str | None = None

    @property
    def genus(self) -> str:
        parts = self.scientific_name.split()
        return parts[0] if parts else ""


def letterbox_pad(image: Image.Image) -> Image.Image:
    """Pad a rectangular image to a square so CLIP center-crop preserves leaf apex and base."""
    w, h = image.size
    if w == h or min(w, h) < 16:
        return image
    side = max(w, h)
    # Estimate background fill color from image corners
    corners = [
        image.getpixel((0, 0)),
        image.getpixel((w - 1, 0)),
        image.getpixel((0, h - 1)),
        image.getpixel((w - 1, h - 1)),
    ]
    fill = tuple(int(np.median([c[i] for c in corners])) for i in range(3))
    canvas = Image.new("RGB", (side, side), fill)
    canvas.paste(image, ((side - w) // 2, (side - h) // 2))
    return canvas


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
                category=row.get("category"),
            )
        )
    return taxa


def build_prompts(taxon: Taxon, plant_part: str) -> list[str]:
    """Generate an ensemble of prompt templates combining taxonomic hierarchy, morphology, and vernacular names."""
    prompts: list[str] = []
    genus = taxon.genus

    if plant_part == DEFAULT_PLANT_PART:
        prompts.append(f"a photo of {taxon.family} {taxon.scientific_name}")
        prompts.append(f"a photo of {taxon.scientific_name}")
        if taxon.common_name:
            prompts.append(f"a photo of {taxon.common_name} ({taxon.scientific_name})")
        if taxon.is_philippine:
            if genus and taxon.family:
                prompts.append(f"a photo of Plantae {taxon.family} {genus} {taxon.scientific_name}")
                prompts.append(f"a photo of {taxon.scientific_name}, genus {genus}, family {taxon.family}")
            prompts.append(f"a botanical specimen photo of {taxon.scientific_name}")
            prompts.append(f"a field photograph of {taxon.scientific_name} in nature")
            prompts.append(f"a clear photo of {taxon.scientific_name} showing leaves and structure")
            if taxon.category:
                prompts.append(f"a photo of {taxon.scientific_name}, a {taxon.category.lower()} plant")
            if taxon.common_name:
                prompts.append(f"a photo of {taxon.common_name}, a plant of the {taxon.family} family")
                prompts.append(f"a photo of {taxon.common_name}")
                prompts.append(f"a photo of {taxon.common_name}, a Philippine medicinal plant")
            for alias in taxon.aliases:
                if alias:
                    prompts.append(f"a photo of {alias} ({taxon.scientific_name})")
                    prompts.append(f"a photo of {alias}")
                    prompts.append(f"a photo of {alias}, a plant found in the Philippines")
    else:
        prompts.append(f"a photo of the {plant_part} of {taxon.family} {taxon.scientific_name}")
        prompts.append(f"a photo of the {plant_part} of {taxon.scientific_name}")
        if taxon.common_name:
            prompts.append(f"a photo of the {plant_part} of {taxon.common_name} ({taxon.scientific_name})")
        if taxon.is_philippine:
            if genus and taxon.family:
                prompts.append(f"a photo of the {plant_part} of Plantae {taxon.family} {genus} {taxon.scientific_name}")
            prompts.append(f"a close-up photo of the {plant_part} of {taxon.scientific_name}")
            prompts.append(f"macro photograph of the {plant_part} of {taxon.scientific_name}")
            if taxon.common_name:
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
        self._family_groups: dict[str, np.ndarray] = {}
        self._genus_groups: dict[str, np.ndarray] = {}
        self.visual_weight = float(os.environ.get("ML_VISUAL_WEIGHT", "0.55"))
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

        fam_map: dict[str, list[int]] = {}
        gen_map: dict[str, list[int]] = {}
        for idx, t in enumerate(taxa):
            if t.family:
                fam_map.setdefault(t.family.lower(), []).append(idx)
            if t.genus:
                gen_map.setdefault(t.genus.lower(), []).append(idx)
        self._family_groups = {k: np.array(v, dtype=np.int32) for k, v in fam_map.items() if len(v) > 1}
        self._genus_groups = {k: np.array(v, dtype=np.int32) for k, v in gen_map.items() if len(v) > 1}

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
                    "taxa": [[t.family, t.scientific_name, t.common_name or "", list(t.aliases), t.category or ""] for t in taxa],
                    "template": "ensemble_v3",
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
                views: list[Image.Image] = [
                    image,
                    image.transpose(Image.Transpose.FLIP_LEFT_RIGHT),
                ]
                weights: list[float] = [1.4, 1.0]

                w, h = image.size
                if w != h and min(w, h) >= 32:
                    views.append(letterbox_pad(image))
                    weights.append(1.2)

                if w >= 48 and h >= 48:
                    views.append(image.crop((int(w * 0.05), int(h * 0.05), int(w * 0.95), int(h * 0.95))))
                    weights.append(1.0)
                    views.append(image.crop((int(w * 0.10), int(h * 0.10), int(w * 0.90), int(h * 0.90))))
                    weights.append(0.9)

                try:
                    views.append(ImageOps.autocontrast(image, cutoff=1))
                    weights.append(0.9)
                    views.append(image.filter(ImageFilter.SHARPEN))
                    weights.append(0.8)
                except Exception:
                    pass

                pixels = torch.stack([self.preprocess(v) for v in views]).to(self.device)
                features = self.model.encode_image(pixels).float()
                features = features / features.norm(dim=-1, keepdim=True)
                w_tensor = torch.tensor(weights[: len(views)], dtype=torch.float32, device=self.device).unsqueeze(1)
                avg_feat = (features * w_tensor).sum(dim=0, keepdim=True) / w_tensor.sum()
                avg_feat = avg_feat / avg_feat.norm(dim=-1, keepdim=True)
                image_emb = avg_feat.cpu().numpy()[0]
            else:
                pixels = self.preprocess(image).unsqueeze(0).to(self.device)
                features = self.model.encode_image(pixels).float()
                features = features / features.norm(dim=-1, keepdim=True)
                image_emb = features.cpu().numpy()[0]

        # Cross-part organ ensembling: blend requested part with best-matching organ
        primary_text_sim = self._text_embeddings[plant_part] @ image_emb
        if len(self._text_embeddings) > 1:
            organ_sims = np.stack([emb @ image_emb for emb in self._text_embeddings.values()], axis=0)
            best_organ_sim = np.max(organ_sims, axis=0)
            text_sim = 0.78 * primary_text_sim + 0.22 * best_organ_sim
        else:
            text_sim = primary_text_sim

        if np.any(self._proto_mask):
            raw_proto_sim = self.prototype_store.score_taxa(
                self.taxa, image_emb, self._proto_matrix, self._proto_mask
            )
            calibrated_proto_sim = calibrate_prototype_similarities(
                text_sim, raw_proto_sim, self._proto_mask
            )
            combined = blend_similarities(
                text_sim, calibrated_proto_sim, self._proto_mask, self.visual_weight
            )
        else:
            combined = text_sim.copy()

        # Hierarchical taxonomic consistency reinforcement (genus & family support)
        if getattr(self, "_family_groups", None) or getattr(self, "_genus_groups", None):
            tax_bonus = np.zeros_like(combined)
            for indices in getattr(self, "_family_groups", {}).values():
                group_vals = combined[indices]
                top_k_mean = float(np.mean(np.sort(group_vals)[-min(3, len(group_vals)) :]))
                tax_bonus[indices] += 0.04 * top_k_mean
            for indices in getattr(self, "_genus_groups", {}).values():
                group_vals = combined[indices]
                top_k_mean = float(np.mean(np.sort(group_vals)[-min(2, len(group_vals)) :]))
                tax_bonus[indices] += 0.04 * top_k_mean
            combined = combined + tax_bonus

        # Calibrated logit temperature scaling: avoids over-peaked softmax
        scale = float(os.environ.get("ML_LOGIT_SCALE", min(self.logit_scale, 45.0)))
        logits = scale * combined
        if hasattr(self, "_philippine_mask") and np.any(self._philippine_mask) and self.philippine_prior > 0:
            logits[self._philippine_mask] += self.philippine_prior

        return softmax(logits.astype(np.float64))


def make_backend(name: str | None = None) -> Backend:
    name = (name or os.environ.get("ML_BACKEND") or "bioclip").strip().lower()
    if name == "mock":
        return MockBackend()
    if name == "bioclip":
        return BioclipBackend()
    raise ValueError(f"Unknown ML_BACKEND '{name}' (expected bioclip or mock)")
