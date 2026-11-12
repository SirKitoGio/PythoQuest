#!/usr/bin/env python3
"""Fetch verified reference photos from iNaturalist and build visual prototype embeddings for BioCLIP.

Usage:
    uv run python scripts/fetch_prototypes.py --limit 10
    uv run python scripts/fetch_prototypes.py --species "Psidium guajava,Moringa oleifera"
"""

import argparse
import io
import json
import logging
import sys
import time
from pathlib import Path

import httpx
import numpy as np
from PIL import Image

# Ensure ml-service root is in sys.path
SERVICE_DIR = Path(__file__).resolve().parent.parent
sys.path.insert(0, str(SERVICE_DIR))

from app.backends import BioclipBackend, Taxon, load_taxa, resolve_taxa_path
from app.prototypes import PrototypeStore, parse_inaturalist_photos

logging.basicConfig(level=logging.INFO, format="%(asctime)s %(levelname)s %(message)s")
logger = logging.getLogger("fetch-prototypes")

INATURALIST_API = "https://api.inaturalist.org/v1/observations"
PHILIPPINES_PLACE_ID = 6883  # iNaturalist place ID for the Philippines

SYNONYM_MAP: dict[str, list[str]] = {
    "Buddleia asiatica": ["Buddleja asiatica"],
    "Pueraria pseudohirsuta": ["Pueraria montana", "Pueraria lobata"],
    "Zanthoxylum alatum": ["Zanthoxylum armatum"],
    "Celastrus dependens": ["Celastrus paniculatus"],
    "Lindera strychnifolia": ["Lindera aggregata"],
    "Canarium luzonicum": ["Canarium ovatum", "Canarium album"],
    "Carmona retusa": ["Ehretia microphylla"],
    "Combretum indicum": ["Quisqualis indica"],
    "Coleus amboinicus": ["Plectranthus amboinicus"],
    "Mentha cordifolia": ["Mentha spicata", "Mentha villosa"],
}


def fetch_species_photos(
    client: httpx.Client,
    scientific_name: str,
    max_photos: int = 5,
    philippines_priority: bool = True,
) -> list[str]:
    """Fetch verified research-grade photo URLs for a species from iNaturalist."""
    urls: list[str] = []
    candidate_names = [scientific_name, *SYNONYM_MAP.get(scientific_name, [])]

    for query_name in candidate_names:
        if len(urls) >= max_photos:
            break
        # Try Philippines first if requested
        if philippines_priority:
            try:
                resp = client.get(
                    INATURALIST_API,
                    params={
                        "taxon_name": query_name,
                        "quality_grade": "research",
                        "photos": "true",
                        "place_id": PHILIPPINES_PLACE_ID,
                        "per_page": max_photos,
                    },
                    timeout=15.0,
                )
                if resp.status_code == 429:
                    logger.warning("iNaturalist rate limit hit; waiting 3s...")
                    time.sleep(3.0)
                    resp = client.get(
                        INATURALIST_API,
                        params={
                            "taxon_name": query_name,
                            "quality_grade": "research",
                            "photos": "true",
                            "place_id": PHILIPPINES_PLACE_ID,
                            "per_page": max_photos,
                        },
                        timeout=15.0,
                    )
                if resp.status_code == 200:
                    for u in parse_inaturalist_photos(resp.json(), max_photos=max_photos):
                        if u not in urls:
                            urls.append(u)
            except Exception as e:
                logger.debug("Failed regional query for %s: %s", query_name, e)

        # Fall back to global research-grade photos if insufficient regional ones found
        if len(urls) < max_photos:
            try:
                needed = max_photos - len(urls)
                resp = client.get(
                    INATURALIST_API,
                    params={
                        "taxon_name": query_name,
                        "quality_grade": "research",
                        "photos": "true",
                        "per_page": needed,
                    },
                    timeout=15.0,
                )
                if resp.status_code == 429:
                    logger.warning("iNaturalist rate limit hit; waiting 3s...")
                    time.sleep(3.0)
                    resp = client.get(
                        INATURALIST_API,
                        params={
                            "taxon_name": query_name,
                            "quality_grade": "research",
                            "photos": "true",
                            "per_page": needed,
                        },
                        timeout=15.0,
                    )
                if resp.status_code == 200:
                    global_urls = parse_inaturalist_photos(resp.json(), max_photos=needed)
                    for u in global_urls:
                        if u not in urls:
                            urls.append(u)
            except Exception as e:
                logger.warning("Failed global query for %s: %s", query_name, e)

    return urls[:max_photos]


def download_image(client: httpx.Client, url: str) -> Image.Image | None:
    """Download an image and return as PIL Image."""
    try:
        resp = client.get(url, timeout=15.0, follow_redirects=True)
        if resp.status_code == 200:
            img = Image.open(io.BytesIO(resp.content)).convert("RGB")
            return img
    except Exception as e:
        logger.debug("Image download failed from %s: %s", url, e)
    return None


def main():
    parser = argparse.ArgumentParser(description="Fetch iNaturalist verified photos and create visual prototypes")
    parser.add_argument("--limit", type=int, default=0, help="Max number of species to process (0 = all)")
    parser.add_argument("--species", type=str, default="", help="Comma-separated list of species scientific names")
    parser.add_argument("--species-file", type=str, default="", help="JSON file containing list of species (or objects with scientific_name)")
    parser.add_argument(
        "--priority",
        type=str,
        default="philippine",
        choices=["philippine", "medicinal", "targets", "all"],
        help="Priority ordering (philippine: Philippine species first; medicinal: high medicinal rating; targets: target species list; all: catalogue order)",
    )
    parser.add_argument("--photos-per-species", type=int, default=3, help="Number of photos to embed per species")
    parser.add_argument("--output", type=str, default="", help="Path to save prototypes.json (default: data/prototypes.json)")
    parser.add_argument("--force", action="store_true", help="Re-fetch photos even if prototype already exists")
    args = parser.parse_args()

    taxa_path = resolve_taxa_path()
    taxa = load_taxa(taxa_path)
    logger.info("Loaded %d taxa from %s", len(taxa), taxa_path)

    if args.species_file:
        file_path = Path(args.species_file)
        if not file_path.is_absolute():
            file_path = SERVICE_DIR / file_path
        if file_path.exists():
            content = json.loads(file_path.read_text(encoding="utf-8"))
            wanted_names = []
            seen = set()
            for item in content:
                name = ""
                if isinstance(item, str):
                    name = item.strip().lower()
                elif isinstance(item, dict) and "scientific_name" in item:
                    name = item["scientific_name"].strip().lower()
                if name and name not in seen:
                    wanted_names.append(name)
                    seen.add(name)
            wanted_set = set(wanted_names)
            order_map = {name: idx for idx, name in enumerate(wanted_names)}
            taxa = [t for t in taxa if t.scientific_name.lower() in wanted_set]
            taxa.sort(key=lambda t: order_map.get(t.scientific_name.lower(), 99999))
            logger.info("Filtered by %s to %d requested species", file_path.name, len(taxa))
        else:
            logger.error("Species file %s not found", file_path)

    elif args.species:
        wanted = {s.strip().lower() for s in args.species.split(",") if s.strip()}
        taxa = [t for t in taxa if t.scientific_name.lower() in wanted]
        logger.info("Filtered to %d requested species: %s", len(taxa), [t.scientific_name for t in taxa])

    elif args.priority == "targets":
        targets_file = SERVICE_DIR / "data" / "target_philippine_species.json"
        if targets_file.exists():
            content = json.loads(targets_file.read_text(encoding="utf-8"))
            target_set = {
                (s["scientific_name"] if isinstance(s, dict) else s).strip().lower()
                for s in content
            }
            taxa = [t for t in taxa if t.scientific_name.lower() in target_set]
            logger.info("Filtered by targets to %d species", len(taxa))

    elif args.priority == "philippine":
        # Sort placing Philippine species first, then by medicinal rating
        taxa.sort(key=lambda t: (not t.is_philippine, -(t.medicinal_rating or 0), t.scientific_name))
        logger.info("Prioritized Philippine species first (%d tagged as Philippine)", sum(1 for t in taxa if t.is_philippine))

    elif args.priority == "medicinal":
        # Sort by medicinal rating descending
        taxa.sort(key=lambda t: (-(t.medicinal_rating or 0), not t.is_philippine, t.scientific_name))
        logger.info("Prioritized medicinal species (rating >= 3)")

    if args.limit > 0:
        taxa = taxa[: args.limit]
        logger.info("Limited to first %d species", len(taxa))

    output_path = Path(args.output) if args.output else SERVICE_DIR / "data" / "prototypes.json"
    store = PrototypeStore(storage_path=output_path)
    store.load()
    logger.info("Existing prototypes in store: %d", len(store))

    # Initialize BioCLIP backend
    logger.info("Loading BioCLIP model...")
    backend = BioclipBackend()
    backend.load()

    import torch

    client = httpx.Client(headers={"User-Agent": "PhytoQuest Research (https://phytoquest.test)"})
    success_count = 0

    for i, taxon in enumerate(taxa):
        name = taxon.scientific_name
        existing_samples = store._metadata.get(name, {}).get("sample_count", 0)
        if not args.force and name in store and existing_samples >= args.photos_per_species:
            logger.info("[%d/%d] Skipping %s (already has %d prototypes)", i + 1, len(taxa), name, existing_samples)
            continue

        logger.info("[%d/%d] Searching iNaturalist for: %s...", i + 1, len(taxa), name)
        urls = fetch_species_photos(client, name, max_photos=args.photos_per_species)

        if not urls:
            logger.warning("  No verified photos found for %s", name)
            time.sleep(0.5)
            continue

        logger.info("  Found %d photos. Embedding...", len(urls))
        embeddings = []
        for url in urls:
            img = download_image(client, url)
            if img is None:
                continue

            with torch.no_grad():
                pixels = backend.preprocess(img).unsqueeze(0).to(backend.device)
                features = backend.model.encode_image(pixels).float()
                features = features / features.norm(dim=-1, keepdim=True)
                embeddings.append(features.cpu().numpy()[0])

        if embeddings:
            mean_emb = np.mean(embeddings, axis=0)
            mean_emb = mean_emb / np.linalg.norm(mean_emb)
            store.set_prototype(
                name,
                mean_emb,
                sample_count=len(embeddings),
                source="inaturalist",
                exemplars=embeddings,
            )
            success_count += 1
            logger.info("  Successfully created prototype for %s (from %d photos)", name, len(embeddings))

        # Polite delay to respect iNaturalist API rate limits
        time.sleep(0.5)

        # Save progress every 5 species
        if (i + 1) % 5 == 0:
            store.save()

    store.save()
    logger.info("Done! Created %d new prototypes. Total prototypes in store: %d", success_count, len(store))


if __name__ == "__main__":
    main()
