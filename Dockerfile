############################################
# Base: PHP 8.4 on Debian with FrankenPHP
############################################
FROM serversideup/php:8.4-frankenphp AS base

USER root

RUN install-php-extensions intl bcmath

############################################
# Composer dependencies and application code
############################################
FROM base AS composer

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# The install layer stays cached until composer.json or composer.lock changes.
COPY composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --no-interaction \
    --no-progress \
    --no-scripts \
    --no-autoloader \
    --prefer-dist

COPY . .

RUN composer dump-autoload \
    --no-dev \
    --no-interaction \
    --no-scripts \
    --optimize

############################################
# Frontend assets and the SSR bundle
############################################
FROM base AS frontend

ENV COREPACK_ENABLE_DOWNLOAD_PROMPT=0

COPY --from=node:22-bookworm-slim /usr/local/bin/node /usr/local/bin/node
COPY --from=node:22-bookworm-slim /usr/local/lib/node_modules/corepack /usr/local/lib/node_modules/corepack

RUN ln -s ../lib/node_modules/corepack/dist/corepack.js /usr/local/bin/corepack \
    && corepack enable pnpm

WORKDIR /app

COPY package.json pnpm-lock.yaml pnpm-workspace.yaml .npmrc ./

RUN pnpm install --frozen-lockfile

# The Wayfinder Vite plugin runs `php artisan wayfinder:generate`, so the build needs vendor and the app code.
COPY --from=composer /app /app

ARG VITE_APP_NAME=Laravel

RUN pnpm run build:ssr

############################################
# Production image
############################################
FROM base AS deploy

# The Inertia SSR server runs on Node.
COPY --from=node:22-bookworm-slim /usr/local/bin/node /usr/local/bin/node

COPY --from=composer --chown=www-data:www-data /app /var/www/html
COPY --from=frontend --chown=www-data:www-data /app/public/build /var/www/html/public/build
COPY --from=frontend --chown=www-data:www-data /app/bootstrap/ssr /var/www/html/bootstrap/ssr

# A fresh named volume copies this directory's owner, so www-data can create the SQLite file in it.
RUN install -d -o www-data -g www-data /var/www/html/database/sqlite

USER www-data

CMD ["php", "artisan", "octane:start", "--server=frankenphp", "--host=0.0.0.0", "--port=8080", "--admin-port=2019", "--max-requests=500"]

HEALTHCHECK --start-period=10s CMD ["healthcheck-octane"]
