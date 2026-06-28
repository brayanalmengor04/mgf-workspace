#!/bin/sh
set -e

is_railway=false
if [ -n "${RAILWAY_ENVIRONMENT_ID:-}" ] || [ -n "${RAILWAY_PROJECT_ID:-}" ] || [ -n "${RAILWAY_SERVICE_ID:-}" ]; then
    is_railway=true
fi

# Local: copia plantilla. Railway: materializa .env desde variables del servicio.
if [ ! -f .env ]; then
    if [ "$is_railway" = true ] && [ -n "${APP_KEY:-}" ]; then
        printenv | grep -E '^(APP_|DB_|LOG_|SESSION_|CACHE_|QUEUE_|MAIL_|BROADCAST_|FILESYSTEM_|VITE_)' | sort -u > .env
    elif [ -f .env.PRD ]; then
        cp .env.PRD .env
    elif [ -f .env.example ]; then
        cp .env.example .env
    fi
fi

if [ ! -f vendor/autoload.php ]; then
    composer install --no-interaction --prefer-dist --optimize-autoloader
fi

if [ ! -d node_modules/.bin ]; then
    npm install
fi

needs_key=false
if [ -f .env ]; then
    if ! grep -qE '^APP_KEY=base64:' .env 2>/dev/null; then
        needs_key=true
    fi
elif [ -z "${APP_KEY:-}" ]; then
    needs_key=true
fi

if [ "$needs_key" = true ]; then
    php artisan key:generate --force
fi

if [ "$is_railway" = true ]; then
    php artisan optimize:clear
fi

rm -f public/hot

if [ ! -f public/build/manifest.json ]; then
    npm run build
fi

if [ "$is_railway" = true ]; then
    php artisan migrate --force
fi

exec php artisan serve --host=0.0.0.0 --port=8000
