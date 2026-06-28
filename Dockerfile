FROM php:8.4-cli-bookworm

RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    unzip \
    curl \
    libzip-dev \
    libpng-dev \
    libicu-dev \
    libonig-dev \
    && docker-php-ext-install pdo_mysql zip intl bcmath gd opcache \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y --no-install-recommends nodejs \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

COPY . .

# Filament CSS/JS + Vite build baked into the image (not committed to git).
ENV APP_KEY=base64:buildtimeplaceholderkey000000000000000=
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader \
    && npm ci \
    && npm run build \
    && php artisan filament:upgrade --no-interaction

ENTRYPOINT ["entrypoint.sh"]
