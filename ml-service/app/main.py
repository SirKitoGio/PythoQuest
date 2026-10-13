"""PhytoQuest image-identification microservice.

Zero-shot ranking of an uploaded plant photo against the PhytoQuest species
catalogue. Returns ranked candidates with confidence scores only; it never
confirms an identification and never infers chemistry or toxicity.
"""

from __future__ import annotations

import hmac
import io
import logging
import os
import threading
import time
from contextlib import asynccontextmanager

from fastapi import FastAPI, File, Form, Request, UploadFile
from fastapi.exceptions import RequestValidationError
from fastapi.responses import JSONResponse
from PIL import Image, UnidentifiedImageError

from app.backends import (
    DEFAULT_PLANT_PART,
    PLANT_PARTS,
    Backend,
    load_taxa,
    make_backend,
    resolve_taxa_path,
)

logging.basicConfig(level=logging.INFO, format="%(asctime)s %(levelname)s %(name)s: %(message)s")
logger = logging.getLogger("ml-service")

ALLOWED_FORMATS = {"JPEG", "PNG", "WEBP"}
MAX_IMAGE_BYTES = 20 * 1024 * 1024
DEFAULT_TOP_K = 5
MAX_TOP_K = 20


class ServiceState:
    def __init__(self) -> None:
        self.backend: Backend | None = None
        self.load_error: str | None = None
        self.lock = threading.Lock()


state = ServiceState()


def reload_taxa(backend: Backend) -> int:
    path = resolve_taxa_path()
    started = time.perf_counter()
    taxa = load_taxa(path)
    backend.set_taxa(taxa)
    logger.info("Loaded %d taxa from %s in %.2fs", len(taxa), path, time.perf_counter() - started)
    return len(taxa)


@asynccontextmanager
async def lifespan(app: FastAPI):
    started = time.perf_counter()
    try:
        backend = make_backend()
        backend.load()
        reload_taxa(backend)
        state.backend = backend
        logger.info("Startup complete in %.2fs (backend=%s)", time.perf_counter() - started, backend.name)
    except Exception as exc:  # keep serving so /health can report the failure
        logger.exception("Backend failed to load")
        state.load_error = str(exc)
    yield
    state.backend = None


app = FastAPI(title="PhytoQuest ML Service", version="1.0.0", lifespan=lifespan)


@app.middleware("http")
async def require_api_token(request: Request, call_next):
    """When ML_API_TOKEN is set, /v1/* requires "Authorization: Bearer <token>"; /health stays public."""
    token = os.environ.get("ML_API_TOKEN", "")
    if token and request.url.path.startswith("/v1/"):
        supplied = request.headers.get("authorization", "")
        if not hmac.compare_digest(supplied.encode(), f"Bearer {token}".encode()):
            return JSONResponse(status_code=401, content={"detail": "Missing or invalid API token."})
    return await call_next(request)


def error(status: int, detail: str) -> JSONResponse:
    return JSONResponse(status_code=status, content={"detail": detail})


@app.exception_handler(RequestValidationError)
async def flatten_validation_error(request: Request, exc: RequestValidationError) -> JSONResponse:
    messages = []
    for err in exc.errors():
        field = ".".join(str(part) for part in err.get("loc", []) if part not in ("body", "query"))
        messages.append(f"{field}: {err.get('msg')}" if field else str(err.get("msg")))
    return error(422, "; ".join(messages) or "Invalid request.")


@app.get("/health")
def health() -> JSONResponse:
    backend = state.backend
    if backend is None or not backend.loaded:
        return JSONResponse(
            status_code=503,
            content={
                "status": "unavailable",
                "backend": None,
                "model_version": None,
                "taxa_count": 0,
                "detail": state.load_error or "Model not loaded.",
            },
        )
    return JSONResponse(
        content={
            "status": "ok",
            "backend": backend.name,
            "model_version": backend.model_version,
            "taxa_count": len(backend.taxa),
        }
    )


def parse_plant_part(value: str | None) -> str | None:
    normalised = " ".join((value or "").strip().lower().replace("_", " ").replace("-", " ").split())
    if not normalised:
        return DEFAULT_PLANT_PART
    return normalised if normalised in PLANT_PARTS else None


def parse_top_k(value: str | None) -> int | None:
    if value is None or str(value).strip() == "":
        return DEFAULT_TOP_K
    try:
        parsed = int(str(value).strip())
    except ValueError:
        return None
    return max(1, min(MAX_TOP_K, parsed))


@app.post("/v1/identify")
def identify(
    image: UploadFile = File(...),
    plant_part: str | None = Form(None),
    top_k: str | None = Form(None),
) -> JSONResponse:
    started = time.perf_counter()

    part = parse_plant_part(plant_part)
    if part is None:
        return error(422, f"plant_part must be one of: {', '.join(PLANT_PARTS)}.")
    k = parse_top_k(top_k)
    if k is None:
        return error(422, "top_k must be an integer.")

    data = image.file.read(MAX_IMAGE_BYTES + 1)
    if not data:
        return error(422, "image is empty.")
    if len(data) > MAX_IMAGE_BYTES:
        return error(422, "image exceeds the 20 MB limit.")
    try:
        with Image.open(io.BytesIO(data)) as probe:
            fmt = probe.format
            probe.load()
            pil_image = probe.convert("RGB")
    except (UnidentifiedImageError, OSError, ValueError, Image.DecompressionBombError):
        return error(422, "image must be a valid JPEG, PNG or WebP file.")
    if fmt not in ALLOWED_FORMATS:
        return error(422, f"image format {fmt} is not supported; use JPEG, PNG or WebP.")

    backend = state.backend
    if backend is None or not backend.loaded:
        return error(503, state.load_error or "Model not loaded.")

    with state.lock:
        taxa = backend.taxa
        if not taxa:
            return error(503, "Taxa list is empty.")
        probabilities = backend.predict(pil_image, data, part)

    order = probabilities.argsort()[::-1][:k]
    candidates = [
        {
            "scientific_name": taxa[i].scientific_name,
            "family": taxa[i].family,
            "score": round(float(probabilities[i]), 4),
        }
        for i in order
    ]
    return JSONResponse(
        content={
            "model": backend.name,
            "model_version": backend.model_version,
            "plant_part": part,
            "candidates": candidates,
            "latency_ms": int(round((time.perf_counter() - started) * 1000)),
        }
    )


@app.post("/v1/taxa/reload")
def taxa_reload() -> JSONResponse:
    backend = state.backend
    if backend is None or not backend.loaded:
        return error(503, state.load_error or "Model not loaded.")
    try:
        with state.lock:
            count = reload_taxa(backend)
    except (OSError, ValueError) as exc:
        return error(422, f"Could not load taxa: {exc}")
    return JSONResponse(content={"taxa_count": count})
