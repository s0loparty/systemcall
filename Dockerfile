# syntax=docker/dockerfile:1.7

# -----------------------------------------------------------------------------
# PHP base
# -----------------------------------------------------------------------------

FROM dunglas/frankenphp:1-php8.4-trixie AS php-base

WORKDIR /app

COPY --from=composer:2.8 /usr/bin/composer /usr/bin/composer

COPY --from=ghcr.io/mlocati/php-extension-installer:2 \
    /usr/bin/install-php-extensions \
    /usr/local/bin/

RUN install-php-extensions \
    pcntl \
    pdo_pgsql \
    pgsql \
    zip \
    opcache \
    intl \
    bcmath \
    redis-6.2.0

ENV APP_ENV=production \
    APP_DEBUG=false \
    LOG_CHANNEL=stderr \
    OCTANE_SERVER=frankenphp


# -----------------------------------------------------------------------------
# Composer dependencies
# -----------------------------------------------------------------------------

FROM php-base AS vendor

COPY composer.json composer.lock ./

RUN --mount=type=cache,id=systemcall-composer,target=/tmp/composer-cache \
    COMPOSER_CACHE_DIR=/tmp/composer-cache \
    composer install \
        --no-dev \
        --no-interaction \
        --no-progress \
        --prefer-dist \
        --no-scripts


# -----------------------------------------------------------------------------
# Frontend build
# -----------------------------------------------------------------------------

FROM node:22-trixie-slim AS assets

WORKDIR /app

# Wayfinder Vite plugin invokes:
# php artisan wayfinder:generate --with-form
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        php-cli \
        php-mbstring \
        php-xml \
        php-curl \
        php-zip \
    && rm -rf /var/lib/apt/lists/*

# Keep dependency installation independent from source/VITE changes.
COPY package.json package-lock.json ./

RUN --mount=type=cache,id=systemcall-npm,target=/root/.npm \
    npm ci --no-audit --no-fund

# Laravel files required by Wayfinder.
COPY composer.json composer.lock ./
COPY artisan ./
COPY app ./app
COPY bootstrap ./bootstrap
COPY config ./config
COPY database ./database
COPY routes ./routes
COPY resources ./resources
COPY public ./public

COPY --from=vendor /app/vendor ./vendor

# Vite configuration.
COPY components.json eslint.config.js tsconfig.json vite.config.ts ./

RUN mkdir -p \
    bootstrap/cache \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs

# Put VITE args AFTER npm ci so changing them does not invalidate npm ci.
ARG VITE_APP_NAME=Systemcall
ARG VITE_REVERB_APP_KEY=
ARG VITE_REVERB_HOST=ws.call.example.com
ARG VITE_REVERB_PORT=443
ARG VITE_REVERB_SCHEME=https

ENV VITE_APP_NAME=$VITE_APP_NAME \
    VITE_REVERB_APP_KEY=$VITE_REVERB_APP_KEY \
    VITE_REVERB_HOST=$VITE_REVERB_HOST \
    VITE_REVERB_PORT=$VITE_REVERB_PORT \
    VITE_REVERB_SCHEME=$VITE_REVERB_SCHEME

RUN npm run build


# -----------------------------------------------------------------------------
# Production
# -----------------------------------------------------------------------------

FROM php-base AS app

WORKDIR /app

COPY . .

COPY --from=vendor /app/vendor ./vendor
COPY --from=assets /app/public/build ./public/build

RUN composer dump-autoload \
        --no-dev \
        --optimize \
        --classmap-authoritative \
    && mkdir -p \
        /config/caddy \
        /data/caddy \
        bootstrap/cache \
        storage/app/public \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
    && chown -R www-data:www-data \
        /config \
        /data \
        bootstrap/cache \
        storage

USER www-data

EXPOSE 8000

CMD ["php", "artisan", "octane:start", "--server=frankenphp", "--host=0.0.0.0", "--port=8000", "--max-requests=500"]
