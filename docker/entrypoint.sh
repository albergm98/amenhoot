#!/bin/sh
set -e
cd /app

if [ ! -f /app/public/index.php ]; then
    echo "ERROR: falta public/index.php — no montes un volumen sobre /app" >&2
    exit 1
fi

mkdir -p storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    storage/app/public \
    bootstrap/cache

touch storage/logs/laravel.log database/database.sqlite 2>/dev/null || true
chown -R www-data:www-data storage bootstrap/cache database 2>/dev/null || true
chmod -R ug+rwX storage bootstrap/cache database 2>/dev/null || true

if [ -f /app/.env ]; then
    chmod 644 /app/.env 2>/dev/null || true
fi

php artisan migrate --force 2>/dev/null || true
php artisan db:seed --force 2>/dev/null || true
php artisan config:cache 2>/dev/null || true
php artisan route:cache 2>/dev/null || true
php artisan view:cache 2>/dev/null || true

exec "$@"
