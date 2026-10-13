import io
import os

os.environ["ML_BACKEND"] = "mock"
os.environ["TAXA_PATH"] = "data/taxa.sample.json"

import pytest
from fastapi.testclient import TestClient
from PIL import Image

from app.main import app


@pytest.fixture(scope="module")
def client():
    with TestClient(app) as test_client:
        yield test_client


def make_image(fmt: str = "PNG", color=(34, 139, 34)) -> bytes:
    buffer = io.BytesIO()
    Image.new("RGB", (64, 64), color).save(buffer, format=fmt)
    return buffer.getvalue()


def identify(client, data: bytes, filename="leaf.png", content_type="image/png", **form):
    return client.post("/v1/identify", files={"image": (filename, data, content_type)}, data=form)


def test_health_reports_mock_backend_and_taxa(client):
    response = client.get("/health")

    assert response.status_code == 200
    assert response.json() == {"status": "ok", "backend": "mock", "model_version": "mock-1", "taxa_count": 15}


def test_identify_returns_sorted_candidates_limited_to_top_k(client):
    response = identify(client, make_image(), plant_part="leaf", top_k="3")

    assert response.status_code == 200
    body = response.json()
    assert body["model"] == "mock"
    assert body["model_version"] == "mock-1"
    assert body["plant_part"] == "leaf"
    assert isinstance(body["latency_ms"], int)
    scores = [c["score"] for c in body["candidates"]]
    assert len(scores) == 3
    assert scores == sorted(scores, reverse=True)
    assert all(0.0 <= s <= 1.0 for s in scores)
    assert set(body["candidates"][0]) == {"scientific_name", "family", "score"}


def test_identify_is_deterministic_and_defaults_to_whole_plant_top_5(client):
    data = make_image("JPEG")
    first = identify(client, data, "a.jpg", "image/jpeg").json()
    second = identify(client, data, "a.jpg", "image/jpeg").json()

    assert first["plant_part"] == "whole plant"
    assert len(first["candidates"]) == 5
    assert first["candidates"] == second["candidates"]


def test_full_distribution_sums_to_one(client):
    body = identify(client, make_image("WEBP"), "a.webp", "image/webp", top_k="20").json()

    assert len(body["candidates"]) == 15
    assert sum(c["score"] for c in body["candidates"]) == pytest.approx(1.0, abs=1e-3)


@pytest.mark.parametrize(("requested", "expected"), [("0", 1), ("-4", 1), ("999", 15)])
def test_top_k_is_clamped(client, requested, expected):
    # 999 clamps to 20, then is bounded by the 15 sample taxa.
    response = identify(client, make_image(), top_k=requested)

    assert response.status_code == 200
    assert len(response.json()["candidates"]) == expected


def test_non_image_upload_is_rejected(client):
    response = identify(client, b"definitely not an image", "notes.txt", "text/plain")

    assert response.status_code == 422
    assert isinstance(response.json()["detail"], str)


def test_unsupported_image_format_is_rejected(client):
    response = identify(client, make_image("GIF"), "a.gif", "image/gif")

    assert response.status_code == 422


def test_invalid_plant_part_and_top_k_are_rejected(client):
    assert identify(client, make_image(), plant_part="root").status_code == 422
    assert identify(client, make_image(), top_k="many").status_code == 422


def test_missing_image_returns_string_detail(client):
    response = client.post("/v1/identify", data={"top_k": "3"})

    assert response.status_code == 422
    assert isinstance(response.json()["detail"], str)


def test_taxa_reload_returns_count(client):
    response = client.post("/v1/taxa/reload")

    assert response.status_code == 200
    assert response.json() == {"taxa_count": 15}


def test_mock_backend_does_not_import_torch():
    import sys

    assert "torch" not in sys.modules


def test_api_token_is_required_when_configured(monkeypatch):
    monkeypatch.setenv("ML_API_TOKEN", "s3cret")

    with TestClient(app) as client:
        assert client.get("/health").status_code == 200
        assert client.post("/v1/taxa/reload").status_code == 401
        assert client.post("/v1/taxa/reload", headers={"Authorization": "Bearer wrong"}).status_code == 401
        assert client.post("/v1/taxa/reload", headers={"Authorization": "Bearer s3cret"}).status_code == 200
