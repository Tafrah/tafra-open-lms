# syntax=docker/dockerfile:1

FROM php:8.4-fpm-alpine AS base

# الامتدادات المطلوبة: pgsql للقاعدة، redis للكاش والطوابير،
# gd و exif للوسائط، intl للترجمة، bcmath للحسابات.
RUN apk add --no-cache \
        postgresql-dev libzip-dev libpng-dev libjpeg-turbo-dev freetype-dev \
        icu-dev oniguruma-dev git unzip supervisor nginx \
    && apk add --no-cache --virtual .build-deps $PHPIZE_DEPS linux-headers \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        pdo_pgsql pgsql zip gd exif intl bcmath opcache pcntl \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del .build-deps

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# OPcache — القيم دي للإنتاج (docs/14 بند ٥)
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/99-opcache.ini
COPY docker/php/php.ini /usr/local/etc/php/conf.d/99-app.ini

FROM base AS development

ENV PHP_OPCACHE_VALIDATE_TIMESTAMPS=1

RUN apk add --no-cache --virtual .xdebug-deps $PHPIZE_DEPS linux-headers \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug \
    && apk del .xdebug-deps

FROM base AS production

COPY . /var/www/html

RUN composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist \
    && chown -R www-data:www-data storage bootstrap/cache

USER www-data
