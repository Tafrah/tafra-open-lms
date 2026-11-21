# syntax=docker/dockerfile:1
#
# صورة واحدة للإنتاج على VPS / Coolify: nginx + php-fpm + الجدولة + عامل
# الطابور، كلهم تحت supervisord. القاعدة MySQL في حاوية منفصلة
# (docker-compose.coolify.yml). مفيش Redis: الكاش والجلسات والطوابير على القاعدة.
#
# بتعيد استخدام docker/php/php.ini و opcache.ini بتوع صورة التطوير.

FROM php:8.4-fpm-alpine AS base

RUN apk add --no-cache \
        libzip-dev libpng-dev libjpeg-turbo-dev libwebp-dev freetype-dev \
        icu-dev oniguruma-dev postgresql-dev \
        nginx supervisor su-exec mariadb-client git unzip \
    && apk add --no-cache --virtual .build-deps $PHPIZE_DEPS linux-headers \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j"$(nproc)" \
        pdo_mysql pdo_pgsql zip gd exif intl bcmath opcache pcntl \
    && apk del .build-deps

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

ENV PHP_OPCACHE_VALIDATE_TIMESTAMPS=0
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/99-opcache.ini
COPY docker/php/php.ini /usr/local/etc/php/conf.d/99-app.ini
COPY docker/coolify/php-fpm.conf /usr/local/etc/php-fpm.d/zz-lms.conf

# ─── الاعتماديات (من غير dev) ──────────────────────────────────────────────
FROM base AS vendor

COPY composer.json composer.lock ./
COPY packages ./packages
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction --no-progress

# ─── الأصول (Vite) — محتاجة vendor: ثيم Filament بيستورد من vendor/filament ─
FROM node:22-alpine AS assets

WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund
COPY . .
COPY --from=vendor /var/www/html/vendor ./vendor
RUN npm run build

# ─── الصورة النهائية ────────────────────────────────────────────────────────
FROM base AS app

COPY . .
COPY --from=vendor /var/www/html/vendor ./vendor
COPY --from=assets /app/public/build ./public/build

# post-autoload-dump بيشغّل package:discover و filament:upgrade — مش محتاجين قاعدة
RUN composer dump-autoload --optimize --no-dev --no-interaction \
    && mkdir -p storage/app/public storage/app/private storage/framework/cache/data \
        storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

COPY docker/coolify/nginx.conf /etc/nginx/http.d/default.conf
COPY docker/coolify/supervisord.conf /etc/supervisord.conf
COPY docker/coolify/entrypoint.sh /usr/local/bin/lms-entrypoint
RUN chmod +x /usr/local/bin/lms-entrypoint

EXPOSE 80

HEALTHCHECK --interval=30s --timeout=5s --start-period=120s --retries=3 \
    CMD wget -qO- http://127.0.0.1/up >/dev/null || exit 1

ENTRYPOINT ["lms-entrypoint"]
CMD ["supervisord", "-c", "/etc/supervisord.conf"]
