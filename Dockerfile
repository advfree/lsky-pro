FROM php:8.2-fpm-alpine AS base

LABEL maintainer="advfree <advfree@github>"
LABEL description="Lsky Pro - Optimized image hosting with compression, dark theme & security fixes"

# Install system dependencies
RUN apk add --no-cache \
    bash curl git nginx \
    libpng-dev libjpeg-turbo-dev freetype-dev libwebp-dev \
    libzip-dev oniguruma-dev libxml2-dev \
    imagemagick-dev imagemagick \
    jpegoptim optipng supervisor \
    sqlite-dev

# Install GD extension
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) gd

# Install database extensions
RUN docker-php-ext-install -j$(nproc) pdo_mysql pdo_pgsql pdo_sqlite

# Install utility extensions
RUN docker-php-ext-install -j$(nproc) zip bcmath mbstring xml exif opcache

# Install imagick & redis via PECL
RUN apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
    && pecl install imagick redis \
    && docker-php-ext-enable imagick redis \
    && apk del .build-deps

# Install Composer & Node.js
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
RUN apk add --no-cache nodejs npm

# Copy config files
COPY docker/nginx/default.conf /etc/nginx/http.d/default.conf
COPY docker/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini

# Copy application code
COPY . /var/www/html
WORKDIR /var/www/html

# Install dependencies and build assets
RUN composer install --no-dev --optimize-autoloader --no-interaction \
    && npm install \
    && npm run production \
    && chmod -R 775 storage bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && php artisan storage:link

EXPOSE 80

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
