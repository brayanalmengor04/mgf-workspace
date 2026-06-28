# Laravel + Docker — comandos de desarrollo
# Uso: just          (selector interactivo)
#      just build     (reconstruir y levantar)

set shell := ["powershell.exe", "-c"]

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

# Construir imágenes, levantar y correr migraciones + seeders
build:
    {{compose}} up -d --build
    {{app}} php artisan migrate --seed

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

# Consola SQL interactiva (para revisar consultas en la BD)
db:
    {{app}} php artisan db

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

# Ejecutar tests
test:
    {{app}} php artisan test

# Crear un respaldo de la base de datos local (no se sube a git)
backup:
    if (-not (Test-Path database/backups)) { New-Item -ItemType Directory -Path database/backups | Out-Null }
    $date = Get-Date -Format 'yyyy-MM-dd_HH-mm-ss'; cmd /c "docker compose exec -T mysql mysqldump --no-tablespaces -u laravel -psecret laravel > database/backups/$date.sql"; Write-Host "✅ Respaldo guardado en database/backups/$date.sql"

# Restaurar la base de datos desde el respaldo local (pasar el nombre del archivo como argumento, ej: just restore 2026-06-27_11-50-00.sql)
restore file:
    cmd /c "docker compose exec -T mysql mysql -u laravel -psecret laravel < database/backups/{{file}}"
    Write-Host "✅ Base de datos restaurada desde database/backups/{{file}}"

# Limpiar cachés de Laravel
clear:
    {{app}} php artisan optimize:clear

# --- Producción (Railway CLI: npm i -g @railway/cli && railway login) ---

# Comando genérico: just prod migrate | just prod backup | just prod artisan "migrate:status"
prod *cmd:
    powershell -NoProfile -File scripts/railway-prod.ps1 {{cmd}}

# Migraciones en producción
prod-migrate:
    powershell -NoProfile -File scripts/railway-prod.ps1 migrate

# Migraciones + seeders en producción
prod-migrate-seed:
    powershell -NoProfile -File scripts/railway-prod.ps1 migrate-seed

# Respaldo MySQL de producción → database/backups/
prod-backup:
    powershell -NoProfile -File scripts/railway-prod.ps1 backup

# Restaurar backup en producción (ej: just prod-restore prod_2026-06-27_12-00-00.sql)
prod-restore file:
    powershell -NoProfile -File scripts/railway-prod.ps1 restore {{file}}