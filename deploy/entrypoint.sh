#!/bin/sh
# Migrates (web only), builds Laravel's caches from the runtime environment, then starts the given process
# (FrankenPHP by default; the queue and Reverb containers pass their own command).
set -e

php artisan storage:link --force > /dev/null 2>&1 || true

# Only the web container migrates; the queue and Reverb wait for it to become healthy.
if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
    php artisan migrate --force
fi

php artisan optimize > /dev/null

exec docker-php-entrypoint "$@"
