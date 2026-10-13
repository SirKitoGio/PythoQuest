#!/usr/bin/env bash
# Pull the latest code, rebuild, restart and migrate. Run on the server from the repo root.
set -euo pipefail
cd "$(dirname "$0")/.."

compose() { docker compose -f compose.production.yaml --env-file .env.production "$@"; }

git pull --ff-only
compose build
# The web container runs migrations on start; workers start once it is healthy.
compose up -d --remove-orphans --wait
docker image prune -f > /dev/null

echo "PhytoQuest deployed."
