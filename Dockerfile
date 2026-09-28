FROM oven/bun:1 AS assets

WORKDIR /src
COPY app/package.json app/bun.lock ./
RUN bun install --frozen-lockfile
COPY app/ ./
RUN bun run build

FROM php:8.4-apache

ENV DEBIAN_FRONTEND=noninteractive

RUN apt-get update && apt-get install -y --no-install-recommends \
        git unzip zip libzip-dev libpng-dev libjpeg62-turbo-dev \
        libfreetype6-dev libonig-dev libxml2-dev libicu-dev \
        libcurl4-openssl-dev libssl-dev ca-certificates \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        pdo_mysql zip gd mbstring exif pcntl bcmath intl \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

RUN echo "memory_limit = 512M\nmax_execution_time = 120" > /usr/local/etc/php/conf.d/custom-limits.ini


RUN rm -f /etc/apache2/mods-enabled/mpm_event.* /etc/apache2/mods-enabled/mpm_worker.* \
    && a2enmod mpm_prefork rewrite

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER=1 COMPOSER_NO_INTERACTION=1

WORKDIR /var/www/html
COPY app/. .

RUN composer install --no-dev --optimize-autoloader

COPY --from=assets /src/public/build ./public/build

RUN mkdir -p bootstrap/cache storage/framework/cache/data \
        storage/framework/sessions storage/framework/testing storage/framework/views \
        storage/app/private storage/app/public \
    && touch storage/logs/laravel.log

RUN printf '<Directory /var/www/html/public>\n    AllowOverride All\n    Require all granted\n</Directory>\n' > /etc/apache2/conf-available/laravel-public.conf \
    && a2enconf laravel-public \
    && sed -i 's|DocumentRoot /var/www/html|DocumentRoot /var/www/html/public|' /etc/apache2/sites-available/000-default.conf

RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 80

CMD php artisan storage:link --force || true; php artisan config:cache; php artisan route:cache; php artisan view:cache; sed -i "s|^Listen .*|Listen ${PORT:-80}|" /etc/apache2/ports.conf; sed -i "s|^<VirtualHost \*:[0-9]*>|<VirtualHost *:${PORT:-80}>|" /etc/apache2/sites-available/000-default.conf; apache2-foreground
