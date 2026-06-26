#!/bin/sh
set -e

if [ ! -f .env ]; then
    cp .env.example .env
fi

if [ ! -f vendor/autoload.php ]; then
    composer install --no-interaction --prefer-dist --optimize-autoloader
fi

if [ ! -d node_modules/.bin ]; then
    npm install
fi

if ! grep -q '^APP_KEY=base64:' .env 2>/dev/null; then
    php artisan key:generate --force
fi

rm -f public/hot

if [ ! -f public/build/manifest.json ]; then
    npm run build
fi

exec php artisan serve --host=0.0.0.0 --port=8000
