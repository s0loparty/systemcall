FROM composer:2.8 AS vendor

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-interaction \
    --no-progress \
    --prefer-dist \
    --no-scripts \
    --optimize-autoloader

COPY . .
RUN composer dump-autoload --no-dev --optimize --classmap-authoritative

FROM node:22-trixie-slim AS assets

WORKDIR /app

RUN apt-get update \
    && apt-get install -y --no-install-recommends php-cli php-mbstring php-xml php-curl php-zip \
    && rm -rf /var/lib/apt/lists/*

COPY package.json package-lock.json ./
RUN npm ci

COPY components.json eslint.config.js tsconfig.json vite.config.ts ./
COPY composer.json composer.lock ./
COPY artisan ./
COPY app ./app
COPY bootstrap ./bootstrap
COPY config ./config
COPY database ./database
COPY resources ./resources
COPY routes ./routes
COPY public ./public
COPY --from=vendor /app/vendor ./vendor
RUN mkdir -p bootstrap/cache storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs
RUN php artisan wayfinder:generate --with-form
RUN npm run build

FROM dunglas/frankenphp:1-php8.4-trixie AS app

WORKDIR /app

RUN apt-get update \
    && apt-get install -y --no-install-recommends curl git unzip libpq-dev $PHPIZE_DEPS \
    && install-php-extensions pcntl pdo_pgsql pgsql zip opcache intl bcmath \
    && git clone --depth 1 --branch 6.2.0 --recurse-submodules https://github.com/phpredis/phpredis.git /tmp/phpredis \
    && cd /tmp/phpredis \
    && phpize \
    && ./configure --enable-redis \
    && make -j"$(nproc)" \
    && make install \
    && docker-php-ext-enable redis \
    && cd /app \
    && rm -rf /tmp/phpredis \
    && apt-get purge -y --auto-remove libpq-dev $PHPIZE_DEPS \
    && rm -rf /var/lib/apt/lists/*

ENV APP_ENV=production \
    APP_DEBUG=false \
    LOG_CHANNEL=stderr \
    OCTANE_SERVER=frankenphp

COPY . .
COPY --from=vendor /app/vendor ./vendor
COPY --from=assets /app/public/build ./public/build

RUN mkdir -p \
        /config/caddy \
        /data/caddy \
        bootstrap/cache \
        storage/app/public \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
    && chown -R www-data:www-data /config /data bootstrap/cache storage

USER www-data

EXPOSE 8000

CMD ["php", "artisan", "octane:start", "--server=frankenphp", "--host=0.0.0.0", "--port=8000", "--max-requests=500"]
