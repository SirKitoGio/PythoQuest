---
title: PhytoQuest ML Service
emoji: 🌿
colorFrom: green
colorTo: yellow
sdk: docker
app_port: 8001
pinned: false
---

# PhytoQuest ML Service

Zero-shot plant species identification with [BioCLIP](https://huggingface.co/imageomics/bioclip)
(`open_clip`, `hf-hub:imageomics/bioclip`), ranked against the PhytoQuest species catalogue.

It returns **ranked candidates with confidence scores**. It never confirms an identification
and never infers chemistry or toxicity.

## Setup

Requires [uv](https://docs.astral.sh/uv/). It installs Python 3.12 and all dependencies into `.venv/`.

```bash
cd ml-service
uv sync
```

## Run

```bash
uv run uvicorn app.main:app --port 8001
```

The first start downloads the BioCLIP weights (about 600 MB) to `~/.cache/huggingface` and encodes the
text prompts for every taxon and plant part (about 90 s for 4,100 taxa on Apple Silicon). The embeddings
are cached in `cache/`, so later starts take about 7 s.

## Tests

```bash
uv run pytest
```

The tests use the mock backend. They run offline and never import torch.

## Environment

| Variable     | Default           | Purpose |
|--------------|-------------------|---------|
| `ML_BACKEND` | `bioclip`         | `bioclip` or `mock` (deterministic, hashes image bytes, no torch) |
| `TAXA_PATH`  | `data/taxa.json`  | Label set; relative paths resolve against `ml-service/`. Falls back to `data/taxa.sample.json` if missing |
| `ML_DEVICE`  | auto (`mps` or `cpu`) | Optional torch device override |

Taxa file shape: `[{"scientific_name": "Vitex negundo", "family": "Lamiaceae", "common_name": "Lagundi"}, ...]`

## HTTP contract

`GET /health` → `200 {"status":"ok","backend":"bioclip","model_version":"...","taxa_count":4100}`
(returns `503` with `"status":"unavailable"` if the model failed to load).

`POST /v1/identify` (multipart/form-data)

| Field        | Required | Notes |
|--------------|----------|-------|
| `image`      | yes      | JPEG, PNG or WebP, up to 20 MB |
| `plant_part` | no       | `flower`, `leaf`, `stem`, `bark`, `fruit`, `whole plant` (default) |
| `top_k`      | no       | default 5, clamped to 1..20 |

```json
{"model":"bioclip","model_version":"hf-hub:imageomics/bioclip@open_clip-3.3.0","plant_part":"leaf",
 "candidates":[{"scientific_name":"Psidium guajava","family":"Myrtaceae","score":0.9018}],"latency_ms":124}
```

Candidates are sorted by score, highest first. Scores are softmax probabilities over the full taxa
list, using BioCLIP's `logit_scale`, rounded to 4 decimal places. Bad input returns
`422 {"detail": "..."}`. An unloaded model or an empty taxa list returns `503`.

`POST /v1/taxa/reload` re-reads `TAXA_PATH`, recomputes the embeddings (cached), and returns `{"taxa_count": N}`.

Prompts follow `a photo of {family} {scientific_name}`, or `a photo of the {part} of {family} {scientific_name}`
when a plant part is given.
