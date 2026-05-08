FROM php:8.2-fpm-alpine

LABEL description="Lsky Pro - Enhanced image hosting"

# System deps
RUN apk add --no-cache bash curl git \
    libpng-dev libjpeg-turbo-dev freetype-dev libwebp-dev \
    libzip-dev oniguruma-dev libxml2-dev \
    sqlite-dev postgresql-dev imagemagick-dev imagemagick

# PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) gd pdo_mysql pdo_pgsql pdo_sqlite \
    zip bcmath mbstring xml exif opcache ftp

# PECL extensions
RUN apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
    && pecl install imagick redis \
    && docker-php-ext-enable imagick redis \
    && apk del .build-deps

# Composer & Node
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
RUN apk add --no-cache nodejs npm

# PHP config
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini

# App
COPY . /var/www/html
WORKDIR /var/www/html

RUN composer install --no-dev --optimize-autoloader --no-interaction --ignore-platform-req=ext-ftp \
    && npm install && npm run production \
    && chmod -R 775 storage bootstrap/cache \
    && php artisan storage:link

EXPOSE 80

CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=80"]
