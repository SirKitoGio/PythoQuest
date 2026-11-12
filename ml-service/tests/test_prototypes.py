import numpy as np
import pytest
from app.backends import Taxon
from app.prototypes import PrototypeStore, blend_similarities


def test_blend_similarities_with_full_mask():
    text_sim = np.array([0.5, 0.4, 0.3])
    proto_sim = np.array([0.9, 0.8, 0.1])
    mask = np.array([True, True, True])
    weight = 0.7

    blended = blend_similarities(text_sim, proto_sim, mask, visual_weight=weight)

    expected = 0.3 * text_sim + 0.7 * proto_sim
    np.testing.assert_allclose(blended, expected)


def test_blend_similarities_with_partial_mask():
    text_sim = np.array([0.5, 0.4, 0.3])
    proto_sim = np.array([0.9, 0.8, 0.1])
    mask = np.array([True, False, True])
    weight = 0.7

    blended = blend_similarities(text_sim, proto_sim, mask, visual_weight=weight)

    # For index 1 (mask=False), it should strictly retain text_sim[1]
    assert blended[1] == pytest.approx(0.4)
    assert blended[0] == pytest.approx(0.3 * 0.5 + 0.7 * 0.9)
    assert blended[2] == pytest.approx(0.3 * 0.3 + 0.7 * 0.1)


def test_prototype_store_save_load(tmp_path):
    store = PrototypeStore(storage_path=tmp_path / "prototypes.json")
    emb1 = np.random.randn(512).astype(np.float32)
    emb1 = emb1 / np.linalg.norm(emb1)

    store.set_prototype("Psidium guajava", emb1, sample_count=3)
    store.save()

    loaded = PrototypeStore(storage_path=tmp_path / "prototypes.json")
    loaded.load()

    assert "Psidium guajava" in loaded
    np.testing.assert_allclose(loaded.get_prototype("Psidium guajava"), emb1, atol=1e-5)


def test_prototype_store_matrix_for_taxa(tmp_path):
    store = PrototypeStore(storage_path=tmp_path / "prototypes.json")
    emb = np.ones(512, dtype=np.float32) / np.sqrt(512)
    store.set_prototype("Psidium guajava", emb, sample_count=2)

    taxa = [
        Taxon(scientific_name="Psidium guajava", family="Myrtaceae"),
        Taxon(scientific_name="Moringa oleifera", family="Moringaceae"),
    ]

    matrix, mask = store.matrix_for(taxa, dim=512)

    assert matrix.shape == (2, 512)
    assert mask.tolist() == [True, False]
    np.testing.assert_allclose(matrix[0], emb)
    np.testing.assert_allclose(matrix[1], np.zeros(512))


def test_parse_inaturalist_photo_urls():
    from app.prototypes import parse_inaturalist_photos

    sample_api_response = {
        "results": [
            {
                "photos": [
                    {"url": "https://inaturalist-open-data.s3.amazonaws.com/photos/123/square.jpg"},
                    {"url": "https://inaturalist-open-data.s3.amazonaws.com/photos/456/square.jpg"},
                ]
            },
            {
                "photos": [
                    {"url": "https://inaturalist-open-data.s3.amazonaws.com/photos/789/square.jpg"}
                ]
            }
        ]
    }

    urls = parse_inaturalist_photos(sample_api_response, max_photos=2)
    assert len(urls) == 2
    # Verify square.jpg is upgraded to medium.jpg for high-quality embedding
    assert urls[0] == "https://inaturalist-open-data.s3.amazonaws.com/photos/123/medium.jpg"
    assert urls[1] == "https://inaturalist-open-data.s3.amazonaws.com/photos/456/medium.jpg"


def test_build_prompts_multi_prompt_ensemble():
    from app.backends import build_prompt, build_prompts

    taxon = Taxon(
        scientific_name="Vitex negundo",
        family="Lamiaceae",
        common_name="Five-leaved Chaste Tree",
        aliases=("Lagundi", "Dangla"),
        is_philippine=True,
    )

    # Whole plant ensemble
    prompts = build_prompts(taxon, "whole plant")
    assert "a photo of Lamiaceae Vitex negundo" in prompts
    assert "a photo of Vitex negundo" in prompts
    assert "a photo of Five-leaved Chaste Tree (Vitex negundo)" in prompts
    assert "a photo of Lagundi (Vitex negundo)" in prompts
    assert "a photo of Dangla (Vitex negundo)" in prompts
    assert len(prompts) >= 5

    # Plant part ensemble
    leaf_prompts = build_prompts(taxon, "leaf")
    assert "a photo of the leaf of Lamiaceae Vitex negundo" in leaf_prompts
    assert "a photo of the leaf of Five-leaved Chaste Tree (Vitex negundo)" in leaf_prompts
    assert "a photo of the leaf of Lagundi (Vitex negundo)" in leaf_prompts
    assert len(leaf_prompts) >= 4

    # Backward compatible single prompt
    single = build_prompt(taxon, "leaf")
    assert single == "a photo of the leaf of Lamiaceae Vitex negundo"


def test_load_taxa_with_aliases(tmp_path):
    import json
    from app.backends import load_taxa

    sample = [
        {
            "scientific_name": "Moringa oleifera",
            "family": "Moringaceae",
            "common_name": "Horseradish Tree",
            "aliases": ["Malunggay", "Marunggay"],
            "is_philippine": True,
            "medicinal_rating": 4,
        }
    ]
    p = tmp_path / "taxa.json"
    p.write_text(json.dumps(sample), encoding="utf-8")

    taxa = load_taxa(p)
    assert len(taxa) == 1
    t = taxa[0]
    assert t.scientific_name == "Moringa oleifera"
    assert t.common_name == "Horseradish Tree"
    assert t.aliases == ("Malunggay", "Marunggay")
    assert t.is_philippine is True
    assert t.medicinal_rating == 4


