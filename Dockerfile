# PhytoQuest web image: FrankenPHP (Caddy + PHP 8.4) serving Laravel with automatic HTTPS.
# The same image runs the web server, queue worker and Reverb (see compose.production.yaml).

FROM composer:2.8 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --ignore-platform-reqs --no-interaction
COPY . .
RUN composer dump-autoload --optimize --classmap-authoritative --no-dev

FROM node:22-bookworm-slim AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund
COPY vite.config.js tailwind.config.js postcss.config.js ./
COPY resources ./resources
COPY --from=vendor /app/vendor/laravel/framework/src/Illuminate/Pagination/resources/views ./vendor/laravel/framework/src/Illuminate/Pagination/resources/views
RUN npm run build

FROM dunglas/frankenphp:1-php8.4-bookworm

RUN install-php-extensions pdo_pgsql pgsql gd intl zip pcntl opcache bcmath

COPY deploy/php.ini $PHP_INI_DIR/conf.d/zz-phytoquest.ini
COPY deploy/Caddyfile /etc/frankenphp/Caddyfile
COPY --chmod=755 deploy/entrypoint.sh /usr/local/bin/phytoquest-entrypoint

WORKDIR /app
COPY --from=vendor /app /app
COPY --from=assets /app/public/build /app/public/build

RUN php artisan package:discover --ansi \
    && mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs storage/app/public storage/app/private/imports

ENTRYPOINT ["phytoquest-entrypoint"]
CMD ["--config", "/etc/frankenphp/Caddyfile", "--adapter", "caddyfile"]
