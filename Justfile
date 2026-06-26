# Laravel + Docker — comandos de desarrollo
# Uso: just          (selector interactivo)
#      just build     (reconstruir y levantar)

compose := "docker compose"
app := compose + " exec app"

default:
    @just --choose

# Levantar contenedores
up:
    {{compose}} up -d

# Ver logs del servidor en vivo
dev:
    {{compose}} up

# Construir imágenes y levantar
build:
    {{compose}} up -d --build

# Reconstruir desde cero (sin caché de Docker)
rebuild:
    {{compose}} down
    {{compose}} build --no-cache
    {{compose}} up -d

# Reconstruir y borrar volúmenes (¡pierde la base de datos!)
rebuild-clean:
    {{compose}} down -v
    {{compose}} build --no-cache
    {{compose}} up -d

# Parar contenedores
down:
    {{compose}} down

# Reiniciar contenedores
restart:
    {{compose}} restart

# Estado de los servicios
ps:
    {{compose}} ps

# Logs de la app (Ctrl+C para salir)
logs:
    {{compose}} logs -f app

# Logs de MySQL
logs-mysql:
    {{compose}} logs -f mysql

# Shell dentro del contenedor app
shell:
    {{compose}} exec app sh

# Ejecutar artisan (ej: just artisan migrate)
artisan *args:
    {{app}} php artisan {{args}}

# Ejecutar composer (ej: just composer install)
composer *args:
    {{app}} composer {{args}}

# Ejecutar npm (ej: just npm run build)
npm *args:
    {{app}} npm {{args}}

# Migraciones
migrate:
    {{app}} php artisan migrate

# Migraciones + seeders
migrate-seed:
    {{app}} php artisan migrate --seed

# Resetear BD y migrar de nuevo
fresh:
    {{app}} php artisan migrate:fresh

# Resetear BD, migrar y seed
fresh-seed:
    {{app}} php artisan migrate:fresh --seed

# Crear usuario admin de Filament (interactivo)
filament-user:
    {{app}} php artisan make:filament-user

# Instalar dependencias PHP y compilar assets
setup:
    {{app}} composer install
    {{app}} npm install
    {{app}} npm run build
    {{app}} php artisan migrate --force

# Correr tests
test:
    {{app}} php artisan test

# Limpiar cachés de Laravel
clear:
    {{app}} php artisan optimize:clear
