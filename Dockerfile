FROM php:8.2-cli-alpine AS vendor
WORKDIR /app
RUN apk add --no-cache git unzip libzip-dev openssl-dev \
    && docker-php-ext-install ftp zip
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY composer.json composer.lock ./
RUN composer install --no-dev --prefer-dist --no-interaction --no-progress --no-scripts
COPY . .
RUN composer dump-autoload --optimize --no-dev

FROM node:18-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY resources resources
COPY webpack.mix.js tailwind.config.js ./
RUN npm run prod

FROM nginx:1.27-alpine AS web
WORKDIR /var/www/html
COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf
COPY public public
COPY --from=assets /app/public public
RUN grep -q '^www-data:' /etc/passwd || adduser -u 82 -D -S -G www-data www-data \
    && sed -i 's/user  nginx;/user  www-data;/' /etc/nginx/nginx.conf \
    && ln -s /var/www/html/storage/app/uploads public/i

FROM php:8.2-fpm-alpine AS app
WORKDIR /var/www/html

RUN apk add --no-cache \
        bash \
        freetype-dev \
        icu-dev \
        libjpeg-turbo-dev \
        libpng-dev \
        libwebp-dev \
        libwebp-tools \
        libzip-dev \
        mariadb-client \
        oniguruma-dev \
        supervisor \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j"$(nproc)" bcmath exif ftp gd intl mbstring opcache pdo_mysql zip

COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini
COPY docker/php/php.ini /usr/local/etc/php/conf.d/lsky.ini
COPY docker/entrypoint.sh /usr/local/bin/lsky-entrypoint
COPY --chown=www-data:www-data . .
COPY --from=vendor --chown=www-data:www-data /app/vendor vendor
COPY --from=assets --chown=www-data:www-data /app/public public

RUN chmod +x /usr/local/bin/lsky-entrypoint \
    && chown -R www-data:www-data storage bootstrap/cache public

ENV IMAGE_DRIVER=gd

ENTRYPOINT ["lsky-entrypoint"]
CMD ["php-fpm"]

FROM app AS standalone

RUN apk add --no-cache nginx

COPY docker/nginx/standalone.conf /etc/nginx/conf.d/default.conf
COPY docker/supervisor/standalone.conf /etc/supervisord.conf

EXPOSE 80

CMD ["supervisord", "-c", "/etc/supervisord.conf"]
