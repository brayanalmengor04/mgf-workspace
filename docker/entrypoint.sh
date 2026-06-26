#!/bin/sh
set -e

echo "Waiting for MySQL..."
until php -r "
    try {
        new PDO(
            'mysql:host=' . getenv('DB_HOST') . ';port=' . (getenv('DB_PORT') ?: 3306),
            getenv('DB_USERNAME'),
            getenv('DB_PASSWORD')
        );
        exit(0);
    } catch (Throwable \$e) {
        exit(1);
    }
" 2>/dev/null; do
    sleep 2
done

echo "MySQL is ready."

if [ ! -f .env ]; then
    cp .env.example .env
fi

if [ ! -d vendor ]; then
    composer install --no-interaction --prefer-dist
fi

if [ ! -d node_modules ]; then
    npm install
fi

if ! grep -q '^APP_KEY=base64:' .env 2>/dev/null; then
    php artisan key:generate --force
fi

php artisan migrate --force

echo "Starting dev server (PHP + Vite HMR)..."
exec npx concurrently -k \
    "php artisan serve --host=0.0.0.0 --port=8000" \
    "npm run dev" \
    --names=server,vite
