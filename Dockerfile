FROM oven/bun:1 AS assets

WORKDIR /src
COPY app/package.json app/bun.lock ./
RUN bun install --frozen-lockfile
COPY app/ ./
RUN bun run build

FROM dunglas/frankenphp:php8.4

ENV DEBIAN_FRONTEND=noninteractive

RUN apt-get update && apt-get install -y --no-install-recommends \
        git unzip zip libzip-dev libpng-dev libjpeg62-turbo-dev \
        libfreetype6-dev libonig-dev libxml2-dev libicu-dev \
        libcurl4-openssl-dev libssl-dev ca-certificates \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        pdo_mysql zip gd mbstring pcntl bcmath intl exif \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

RUN echo "memory_limit = 512M\nmax_execution_time = 120" > /usr/local/etc/php/conf.d/custom-limits.ini

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER=1 COMPOSER_NO_INTERACTION=1

WORKDIR /app
RUN echo "\n{\n\tfrankenphp\n\torder php_server before file_server\n}\n\n:\{\$PORT:80\} {\n\troot * public\n\tencode zstd br gzip\n\tphp_server\n}\n" > /etc/caddy/Caddyfile
COPY app/. .

RUN composer install --no-dev --optimize-autoloader

COPY --from=assets /src/public/build ./public/build

RUN mkdir -p bootstrap/cache storage/framework/cache/data \
        storage/framework/sessions storage/framework/testing storage/framework/views \
        storage/app/private storage/app/public \
    && touch storage/logs/laravel.log

RUN chown -R www-data:www-data /app \
    && chmod -R 775 /app/storage /app/bootstrap/cache

# FrankenPHP serves from /app/public
ENV SERVER_NAME="http://"

CMD php artisan storage:link --force || true; php artisan config:cache; php artisan route:cache; php artisan view:cache; frankenphp run --config /etc/caddy/Caddyfile --adapter caddyfile
