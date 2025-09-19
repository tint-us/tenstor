# Stage 1: install PHP deps via Composer
FROM composer:2 AS vendor
WORKDIR /app
COPY src/composer.json ./
RUN composer install --no-dev --prefer-dist --no-interaction --no-progress

# Stage 2: runtime
FROM php:8.3-apache

# Extensions & tools
RUN docker-php-ext-install -j$(nproc) mbstring \
&& a2enmod rewrite

# php.ini tweaks
COPY docker/php.ini /usr/local/etc/php/conf.d/uploads.ini

# App dirs
RUN mkdir -p /tenable/hasil /var/log \
&& chown -R www-data:www-data /tenable/hasil /var/log

# Copy app
WORKDIR /var/www/html
COPY --from=vendor /app/vendor ./vendor
COPY src/index.php ./index.php

# Env defaults
ENV PHP_TZ=Asia/Jakarta \
TENSTOR_UPLOAD_DIR=/tenable/hasil \
TENSTOR_LOG_FILE=/var/log/report-tenable.log

# Run as non-root
USER www-data

EXPOSE 80
