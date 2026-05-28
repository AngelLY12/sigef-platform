# =========================
# STAGE 1 — BUILDER
# =========================
FROM php:8.4-fpm-alpine AS builder

RUN apk add --no-cache \
    git unzip curl \
    icu-dev oniguruma-dev libzip-dev \
    freetype-dev libjpeg-turbo-dev libpng-dev \
    postgresql-dev \
    libxml2-dev \
    openssl-dev \
    $PHPIZE_DEPS

RUN docker-php-ext-configure gd \
    --with-freetype \
    --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
    pdo_mysql \
    pdo_pgsql \
    pgsql \
    bcmath \
    intl \
    zip \
    soap \
    gd \
    opcache

RUN pecl install redis && docker-php-ext-enable redis

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# Igual que tu Debian: copiamos lo necesario antes
COPY composer.json composer.lock artisan ./
COPY bootstrap ./bootstrap
COPY config ./config

RUN composer install \
    --optimize-autoloader \
    --no-interaction \
    --no-progress \
    --prefer-dist \
    --no-scripts

COPY . .

RUN rm -rf bootstrap/cache/*.php

# =========================
# STAGE 2 — RUNTIME
# =========================
FROM php:8.4-fpm-alpine

RUN apk add --no-cache \
    icu \
    libzip \
    freetype \
    libjpeg-turbo \
    libpng \
    postgresql-libs \
    mariadb-client \
    libxml2 \
    bash curl \
    fcgi \
    procps

# Healthcheck (igual que Debian)
RUN curl -o /usr/local/bin/php-fpm-healthcheck \
    https://raw.githubusercontent.com/renatomefi/php-fpm-healthcheck/master/php-fpm-healthcheck \
    && chmod +x /usr/local/bin/php-fpm-healthcheck

# Copiar extensiones compiladas
COPY --from=builder /usr/local/lib/php/extensions/ /usr/local/lib/php/extensions/
COPY --from=builder /usr/local/etc/php/conf.d/ /usr/local/etc/php/conf.d/

# Producción php.ini
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

WORKDIR /var/www

COPY --from=builder /var/www /var/www

# entrypoints
COPY docker/php-fpm/dev/entrypoint.sh /usr/local/bin/entrypoint.sh
COPY docker/php-fpm/dev/entrypoint.worker.sh /usr/local/bin/entrypoint.worker.sh
RUN chmod +x /usr/local/bin/entrypoint.sh /usr/local/bin/entrypoint.worker.sh

RUN rm -f /var/www/storage/logs/laravel.log

RUN mkdir -p \
    /var/www/storage/framework/cache \
    /var/www/storage/framework/sessions \
    /var/www/storage/framework/views \
    /var/www/storage/logs \
    /var/www/bootstrap/cache \
    && chown -R www-data:www-data /var/www \
    && chmod -R 775 /var/www/storage \
    && chmod -R 775 /var/www/bootstrap/cache

USER www-data

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]

EXPOSE 9000
CMD ["php-fpm"]
