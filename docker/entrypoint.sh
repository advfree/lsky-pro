#!/usr/bin/env sh
set -e

export DB_CONNECTION="${DB_CONNECTION:-mysql}"
export DB_HOST="${DB_HOST:-${MYSQL_HOST:-mysql}}"
export DB_PORT="${DB_PORT:-${MYSQL_PORT:-3306}}"
export DB_DATABASE="${DB_DATABASE:-${MYSQL_DATABASE:-lsky-pro}}"
export DB_USERNAME="${DB_USERNAME:-${MYSQL_USER:-lsky-pro}}"
export DB_PASSWORD="${DB_PASSWORD:-${MYSQL_PASSWORD:-lsky-pro}}"
export CACHE_DRIVER="${CACHE_DRIVER:-file}"
export SESSION_DRIVER="${SESSION_DRIVER:-file}"
export QUEUE_CONNECTION="${QUEUE_CONNECTION:-sync}"
export IMAGE_DRIVER="${IMAGE_DRIVER:-gd}"

mkdir -p storage/app/uploads storage/app/thumbnails storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache public/uploads

if [ ! -f storage/app/.env ]; then
    cp .env.example storage/app/.env 2>/dev/null || touch storage/app/.env
fi

ln -sfn storage/app/.env .env
ln -sfn storage/installed.lock installed.lock
ln -sfn /var/www/html/storage/app/uploads public/i
if [ ! -e public/thumbnails ]; then
    ln -sfn /var/www/html/storage/app/thumbnails public/thumbnails
fi
touch .env

set_env_default() {
    key="$1"
    value="$2"
    escaped_value="$(printf '%s' "$value" | sed 's/\\/\\\\/g; s/"/\\"/g')"
    line="${key}=\"${escaped_value}\""

    if grep -q "^${key}=" .env; then
        current="$(grep "^${key}=" .env | tail -n 1 | cut -d= -f2-)"
        if [ -n "$current" ]; then
            return
        fi
    fi

    tmp="$(mktemp)"
    if grep -q "^${key}=" .env; then
        awk -v key="$key" -v line="$line" '
            BEGIN { replaced = 0 }
            $0 ~ "^" key "=" && !replaced {
                print line
                replaced = 1
                next
            }
            { print }
        ' .env > "$tmp"
    else
        cat .env > "$tmp"
        printf '%s\n' "$line" >> "$tmp"
    fi
    cat "$tmp" > .env
    rm -f "$tmp"
}

set_env_default DB_CONNECTION "$DB_CONNECTION"
set_env_default DB_HOST "$DB_HOST"
set_env_default DB_PORT "$DB_PORT"
set_env_default DB_DATABASE "$DB_DATABASE"
set_env_default DB_USERNAME "$DB_USERNAME"
set_env_default DB_PASSWORD "$DB_PASSWORD"
set_env_default CACHE_DRIVER "$CACHE_DRIVER"
set_env_default SESSION_DRIVER "$SESSION_DRIVER"
set_env_default QUEUE_CONNECTION "$QUEUE_CONNECTION"
set_env_default IMAGE_DRIVER "$IMAGE_DRIVER"

chown -h www-data:www-data .env installed.lock public/i public/thumbnails
chown -R www-data:www-data storage bootstrap/cache public/uploads

if ! grep -q '^APP_KEY=base64:' .env; then
    php artisan key:generate --force --no-interaction
fi

if [ "$DB_CONNECTION" = "mysql" ]; then
    echo "Waiting for MySQL at ${DB_HOST}:${DB_PORT}..."
    i=0
    until php -r 'try { new PDO("mysql:host=".getenv("DB_HOST").";port=".getenv("DB_PORT").";dbname=".getenv("DB_DATABASE"), getenv("DB_USERNAME"), getenv("DB_PASSWORD")); exit(0); } catch (Throwable $e) { exit(1); }'; do
        i=$((i + 1))
        if [ "$i" -ge 60 ]; then
            echo "MySQL is not ready after 120 seconds."
            exit 1
        fi
        sleep 2
    done
fi

php artisan storage:link >/dev/null 2>&1 || true
php artisan migrate --force
php artisan optimize:clear >/dev/null 2>&1 || true
php artisan config:cache
php artisan route:cache
php artisan view:cache

exec "$@"
