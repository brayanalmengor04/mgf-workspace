#!/bin/sh
set -e

# Local: copia plantilla a .env. Railway: usa variables del servicio (no commitear .env.PRD).
if [ ! -f .env ]; then
    if [ -f .env.PRD ]; then
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

rm -f public/hot

if [ ! -f public/build/manifest.json ]; then
    npm run build
fi

exec php artisan serve --host=0.0.0.0 --port=8000
