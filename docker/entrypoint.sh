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

if [ "$is_railway" = true ] && [ -n "${RAILWAY_PUBLIC_DOMAIN:-}" ]; then
    app_url="https://${RAILWAY_PUBLIC_DOMAIN}"
    if [ -f .env ]; then
        if grep -q '^APP_URL=' .env; then
            sed -i "s|^APP_URL=.*|APP_URL=${app_url}|" .env
        else
            printf '\nAPP_URL=%s\n' "$app_url" >> .env
        fi
    fi
    export APP_URL="$app_url"
fi

# Solo desarrollo local: vendor/assets no vienen en la imagen de Railway.
if [ "$is_railway" != true ]; then
    if [ ! -f vendor/autoload.php ]; then
        composer install --no-interaction --prefer-dist --optimize-autoloader
    fi

    if [ ! -d public/css/filament ] || [ ! -f public/build/manifest.json ]; then
        if [ ! -d node_modules/.bin ]; then
            npm install
        fi
        php artisan filament:upgrade --no-interaction
        rm -f public/hot
        if [ ! -f public/build/manifest.json ]; then
            npm run build
        fi
    fi
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

# Migraciones en segundo plano: Railway hace health check antes de que migrate termine.
if [ "$is_railway" = true ]; then
    (
        i=0
        while [ "$i" -lt 30 ]; do
            if php artisan migrate --force --no-interaction; then
                exit 0
            fi
            i=$((i + 1))
            sleep 2
        done
        echo "migrate: no se pudo conectar a la BD tras 60s" >&2
        exit 1
    ) &
fi

port="${PORT:-8080}"
echo "Listening on 0.0.0.0:${port}" >&2
exec php artisan serve --host=0.0.0.0 --port="${port}" --no-reload
