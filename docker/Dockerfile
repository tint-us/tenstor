# Stage 1: install PHP deps via Composer
FROM composer:2 AS vendor
WORKDIR /app
COPY src/composer.json ./
RUN composer install --no-dev --prefer-dist --no-interaction --no-progress

# Stage 2: runtime
FROM php:8.3-apache

# Install build deps for mbstring (needs oniguruma), enable Apache rewrite
RUN apt-get update && apt-get install -y --no-install-recommends \
        libonig-dev \
        pkg-config \
    && docker-php-ext-install -j$(nproc) mbstring \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

# php.ini tweaks
COPY docker/php.ini /usr/local/etc/php/conf.d/uploads.ini

# App dirs: buat hanya folder milik app, bukan seluruh /var/log
RUN mkdir -p /tenable/hasil /var/log/tenstor \
    && chown -R www-data:www-data /tenable/hasil /var/log/tenstor

# Copy app
WORKDIR /var/www/html
COPY --from=vendor /app/vendor ./vendor
COPY src/index.php ./index.php

# Env defaults
ENV PHP_TZ=Asia/Jakarta \
    TENSTOR_UPLOAD_DIR=/tenable/hasil \
    TENSTOR_LOG_FILE=/var/log/tenstor/report-tenable.log

# Penting: JANGAN set USER www-data di sini. Biarkan default (root)
# agar apache entrypoint bisa bikin /var/log/apache2 dsb dengan benar.

EXPOSE 80
