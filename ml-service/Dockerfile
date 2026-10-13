# PhytoQuest ML service: BioCLIP zero-shot identification (CPU).
# Works on Hugging Face Spaces (Docker SDK) and in the production Compose stack.
FROM python:3.12-slim

COPY --from=ghcr.io/astral-sh/uv:0.12.5 /uv /bin/uv

RUN useradd --create-home --uid 1000 app
USER app
WORKDIR /home/app/service

ENV PATH="/home/app/service/.venv/bin:$PATH" \
    UV_COMPILE_BYTECODE=1 \
    UV_LINK_MODE=copy \
    UV_PYTHON_DOWNLOADS=never \
    HF_HOME=/home/app/hf-cache \
    ML_BACKEND=bioclip \
    ML_DEVICE=cpu \
    PORT=8001

COPY --chown=app pyproject.toml uv.lock .python-version ./
RUN uv sync --frozen --no-dev --no-install-project

COPY --chown=app app ./app
COPY --chown=app data ./data

# Bake the model weights and the catalogue's text embeddings into the image so a
# sleeping Space or a restarted container is ready in seconds, not minutes.
RUN python -m app.warm

EXPOSE 8001
CMD ["sh", "-c", "uvicorn app.main:app --host 0.0.0.0 --port ${PORT}"]
