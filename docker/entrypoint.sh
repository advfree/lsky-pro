#!/usr/bin/env sh
set -e

mkdir -p storage/app/uploads storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache public/uploads
touch .env
chown www-data:www-data .env
chown -R www-data:www-data storage bootstrap/cache public/uploads

php artisan storage:link >/dev/null 2>&1 || true
php artisan migrate --force
php artisan optimize:clear >/dev/null 2>&1 || true
php artisan config:cache
php artisan route:cache
php artisan view:cache

exec "$@"
