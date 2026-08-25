# Stateless BFF runtime for the optional root Compose profile.

FROM composer:2 AS composer-build

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-scripts \
    --no-autoloader \
    --prefer-dist \
    --ignore-platform-reqs

COPY . .
RUN composer dump-autoload --optimize --no-dev

FROM php:8.2-apache

LABEL maintainer="CI4 Website Builder"
LABEL description="Optional stateless CodeIgniter 4 BFF"

RUN apt-get update \
    && apt-get upgrade -y \
    && apt-get install -y --no-install-recommends \
        curl \
        libcurl4-openssl-dev \
        libicu-dev \
        libzip-dev \
    && docker-php-ext-install -j$(nproc) curl intl opcache zip \
    && a2enmod rewrite headers expires deflate \
    && mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && rm -rf /var/lib/apt/lists/*

COPY docker/apache/000-default.conf /etc/apache2/sites-available/000-default.conf

WORKDIR /var/www/html
COPY --from=composer-build /app /var/www/html

RUN mkdir -p writable/cache writable/logs writable/session writable/uploads writable/debugbar \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 775 /var/www/html/writable

HEALTHCHECK --interval=30s --timeout=3s --start-period=20s --retries=3 \
    CMD curl -f http://localhost/ready || exit 1

EXPOSE 80

CMD ["apache2-foreground"]
