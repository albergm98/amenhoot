# Amenhoot — app en /app (Dockploy: no montar volúmenes sobre /app/public)
FROM composer:2 AS vendor

WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --ignore-platform-reqs --no-scripts

FROM php:8.4-cli-alpine AS frontend

RUN apk add --no-cache nodejs npm icu-dev libzip-dev \
    && docker-php-ext-install zip intl

WORKDIR /app
COPY --from=vendor /app/vendor ./vendor
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY composer.json composer.lock artisan ./
COPY app ./app
COPY bootstrap ./bootstrap
COPY config ./config
COPY database ./database
COPY routes ./routes
COPY package.json package-lock.json .npmrc ./
COPY vite.config.ts tsconfig.json ./
COPY resources ./resources
COPY public ./public

RUN mkdir -p storage/framework/{cache,sessions,views} storage/logs bootstrap/cache \
    && php artisan package:discover --ansi \
    && npm install \
    && npm run build

FROM php:8.4-fpm-alpine

RUN apk add --no-cache nginx supervisor sqlite-dev mariadb-dev libzip-dev \
        freetype-dev libjpeg-turbo-dev libpng-dev libwebp-dev icu-dev curl-dev \
        ca-certificates linux-headers $PHPIZE_DEPS \
    && update-ca-certificates \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install pdo pdo_sqlite pdo_mysql opcache zip gd intl curl pcntl sockets \
    && apk del $PHPIZE_DEPS

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY . .
COPY --from=vendor /app/vendor ./vendor
COPY --from=frontend /app/public/build ./public/build

RUN composer install --no-dev --optimize-autoloader --no-interaction \
    && mkdir -p storage/app/public storage/framework/{cache,sessions,views} storage/logs bootstrap/cache \
    && touch database/database.sqlite \
    && chown -R www-data:www-data storage bootstrap/cache database \
    && chmod -R 775 storage bootstrap/cache database

COPY docker/nginx.conf /etc/nginx/http.d/default.conf
COPY docker/php-uploads.ini /usr/local/etc/php/conf.d/uploads.ini
COPY docker/supervisord.conf /etc/supervisord.conf
COPY docker/entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN sed -i 's/\r$//' /usr/local/bin/docker-entrypoint.sh \
    && chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 80

ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]
