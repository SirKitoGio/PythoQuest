"""Download the model and precompute taxa embeddings so a container starts warm.

Run at image build time: ``python -m app.warm``.
"""

from app.backends import load_taxa, make_backend, resolve_taxa_path


def main() -> None:
    backend = make_backend()
    backend.load()
    backend.set_taxa(load_taxa(resolve_taxa_path()))
    print(f"Warmed {backend.name} with {len(backend.taxa)} taxa.")


if __name__ == "__main__":
    main()
