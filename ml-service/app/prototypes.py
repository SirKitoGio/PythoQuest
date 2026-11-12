"""Visual prototype store and multimodal similarity blending for PhytoQuest.

Enables image-to-image matching with verified reference photos alongside
zero-shot text-to-image prompts.
"""

from __future__ import annotations

import json
import logging
from pathlib import Path
from typing import TYPE_CHECKING

import numpy as np

if TYPE_CHECKING:
    from app.backends import Taxon

logger = logging.getLogger("ml-service.prototypes")

SERVICE_DIR = Path(__file__).resolve().parent.parent
DEFAULT_PROTOTYPES_PATH = SERVICE_DIR / "data" / "prototypes.json"


def blend_similarities(
    text_sim: np.ndarray,
    proto_sim: np.ndarray,
    mask: np.ndarray,
    visual_weight: float = 0.70,
) -> np.ndarray:
    """Blend text-to-image similarity with visual prototype similarity.

    For taxa with verified prototypes (mask=True):
        similarity = (1 - visual_weight) * text_sim + visual_weight * proto_sim
    For taxa without prototypes (mask=False):
        similarity = text_sim
    """
    result = text_sim.copy()
    if np.any(mask):
        result[mask] = (1.0 - visual_weight) * text_sim[mask] + visual_weight * proto_sim[mask]
    return result


def calibrate_prototype_similarities(
    text_sim: np.ndarray,
    proto_sim: np.ndarray,
    mask: np.ndarray,
) -> np.ndarray:
    """Align image-to-image prototype similarities with the text-to-image similarity scale.

    In CLIP/BioCLIP, image-to-image cosine similarity has a higher baseline (~0.42)
    than image-to-text similarity (~0.10) due to the modality gap. When only a subset
    of taxa have prototypes, z-score alignment ensures unrelated prototype species do not
    artificially outrank true non-prototype matches, while strong visual matches (>0.55)
    receive a decisive positive boost.
    """
    calibrated = proto_sim.copy()
    active_count = int(np.sum(mask))
    if active_count < 3 or np.all(mask):
        return calibrated

    p_vals = proto_sim[mask]
    p_median = float(np.median(p_vals))
    p_std = float(np.std(p_vals))

    t_median = float(np.median(text_sim))
    t_std = float(np.std(text_sim))

    if p_std > 1e-6 and t_std > 1e-6:
        z = (p_vals - p_median) / p_std
        aligned = t_median + z * t_std
        # Add direct visual confidence bonus when raw image-to-image similarity is strong
        strong_bonus = np.maximum(0.0, p_vals - 0.55) * 0.35
        calibrated[mask] = aligned + strong_bonus

    return calibrated


def parse_inaturalist_photos(payload: dict, max_photos: int = 5) -> list[str]:
    """Extract photo URLs from an iNaturalist API observations response.

    Upgrades square/small thumbnails to 'medium' resolution for high-quality embedding.
    """
    urls: list[str] = []
    for obs in payload.get("results", []):
        for photo in obs.get("photos", []):
            raw_url = photo.get("url")
            if not raw_url:
                continue
            # Upgrade thumbnail URLs to medium for quality
            clean_url = raw_url.replace("/square.", "/medium.").replace("/small.", "/medium.")
            urls.append(clean_url)
            if len(urls) >= max_photos:
                return urls
    return urls



class PrototypeStore:
    def __init__(self, storage_path: Path | None = None) -> None:
        self.storage_path = storage_path or DEFAULT_PROTOTYPES_PATH
        self._prototypes: dict[str, np.ndarray] = {}
        self._exemplars: dict[str, np.ndarray] = {}
        self._metadata: dict[str, dict] = {}

    def __len__(self) -> int:
        return len(self._prototypes)

    def __contains__(self, scientific_name: str) -> bool:
        return scientific_name in self._prototypes

    def set_prototype(
        self,
        scientific_name: str,
        embedding: np.ndarray,
        sample_count: int = 1,
        source: str = "inaturalist",
        exemplars: list[np.ndarray] | np.ndarray | None = None,
    ) -> None:
        norm = np.linalg.norm(embedding)
        if norm > 0:
            embedding = embedding / norm
        self._prototypes[scientific_name] = embedding.astype(np.float32)

        if exemplars is not None and len(exemplars) > 0:
            ex_arr = np.asarray(exemplars, dtype=np.float32)
            if ex_arr.ndim == 1:
                ex_arr = ex_arr[np.newaxis, :]
            norms = np.linalg.norm(ex_arr, axis=1, keepdims=True)
            norms = np.where(norms > 0, norms, 1.0)
            self._exemplars[scientific_name] = (ex_arr / norms).astype(np.float32)

        self._metadata[scientific_name] = {
            "sample_count": sample_count,
            "source": source,
        }

    def get_prototype(self, scientific_name: str) -> np.ndarray | None:
        return self._prototypes.get(scientific_name)

    def get_exemplars(self, scientific_name: str) -> np.ndarray | None:
        return self._exemplars.get(scientific_name)

    def matrix_for(self, taxa: list[Taxon], dim: int = 512) -> tuple[np.ndarray, np.ndarray]:
        """Return (prototype_matrix, mask) aligned with the provided taxa list."""
        n = len(taxa)
        matrix = np.zeros((n, dim), dtype=np.float32)
        mask = np.zeros(n, dtype=bool)

        for i, taxon in enumerate(taxa):
            emb = self._prototypes.get(taxon.scientific_name)
            if emb is not None and len(emb) == dim:
                matrix[i] = emb
                mask[i] = True

        return matrix, mask

    def score_taxa(
        self,
        taxa: list[Taxon],
        image_emb: np.ndarray,
        centroid_matrix: np.ndarray,
        mask: np.ndarray,
    ) -> np.ndarray:
        """Compute visual similarity combining exemplar max-pooling and centroid similarity."""
        centroid_sim = centroid_matrix @ image_emb
        if not self._exemplars:
            return centroid_sim

        scores = centroid_sim.copy()
        for i, taxon in enumerate(taxa):
            if not mask[i]:
                continue
            ex_mat = self._exemplars.get(taxon.scientific_name)
            if ex_mat is not None and ex_mat.shape[1] == len(image_emb):
                ex_dots = ex_mat @ image_emb
                max_ex = float(np.max(ex_dots))
                scores[i] = 0.60 * max_ex + 0.40 * float(centroid_sim[i])
        return scores

    def save(self, path: Path | None = None) -> None:
        target = path or self.storage_path
        target.parent.mkdir(parents=True, exist_ok=True)
        data = {}
        for name, emb in self._prototypes.items():
            entry: dict = {
                "embedding": emb.tolist(),
                "metadata": self._metadata.get(name, {}),
            }
            if name in self._exemplars:
                entry["exemplars"] = self._exemplars[name].tolist()
            data[name] = entry
        tmp = target.with_suffix(".tmp.json")
        tmp.write_text(json.dumps(data), encoding="utf-8")
        tmp.replace(target)
        logger.info("Saved %d prototypes to %s", len(data), target)

    def load(self, path: Path | None = None) -> int:
        source = path or self.storage_path
        if not source.exists():
            return 0
        try:
            data = json.loads(source.read_text(encoding="utf-8"))
            for name, payload in data.items():
                emb = np.array(payload["embedding"], dtype=np.float32)
                norm = np.linalg.norm(emb)
                if norm > 0:
                    emb = emb / norm
                self._prototypes[name] = emb
                if "exemplars" in payload and payload["exemplars"]:
                    ex_arr = np.array(payload["exemplars"], dtype=np.float32)
                    if ex_arr.ndim == 2 and len(ex_arr) > 0:
                        norms = np.linalg.norm(ex_arr, axis=1, keepdims=True)
                        norms = np.where(norms > 0, norms, 1.0)
                        self._exemplars[name] = (ex_arr / norms).astype(np.float32)
                self._metadata[name] = payload.get("metadata", {})
            logger.info("Loaded %d prototypes (%d with multi-exemplars) from %s", len(self._prototypes), len(self._exemplars), source)
            return len(self._prototypes)
        except Exception:
            logger.exception("Failed to load prototypes from %s", source)
            return 0
