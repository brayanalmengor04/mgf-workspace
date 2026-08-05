# Laravel + Docker — comandos de desarrollo
# Uso: just          (selector interactivo)
#      just build     (reconstruir y levantar)

set shell := ["powershell.exe", "-ExecutionPolicy", "Bypass", "-c"]

compose := "docker compose"
app := compose + " exec app"

default:
    @just --choose --chooser "fzf --preview 'just --show {}'"

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

build-migrate:
    {{compose}} up -d --build
    {{app}} php artisan migrate

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
    {{app}} ./vendor/bin/phpunit

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

# Verificar esquema de BD (local)
db-doctor:
    {{app}} php artisan mgf:migrate-doctor

# Squash de migraciones: backup + fresh seed + schema dump + tests + doctor
db-squash:
    just backup
    {{app}} php artisan migrate:fresh --seed --force
    {{app}} php artisan schema:dump --prune --database=mysql
    {{app}} ./vendor/bin/phpunit
    {{app}} php artisan mgf:migrate-doctor

# --- Comandos de Producción (Railway) ---
# Artisan remoto: Docker local + MySQL TCP proxy (no requiere PHP ni SSH en Windows)

_prod-run-artisan command:
    $v = (railway variables --service MySQL --json | ConvertFrom-Json); $pass = $v.MYSQLPASSWORD.Trim(); docker compose exec -T -e DB_CONNECTION=mysql -e DB_HOST=$($v.RAILWAY_TCP_PROXY_DOMAIN) -e DB_PORT=$($v.RAILWAY_TCP_PROXY_PORT) -e DB_DATABASE=$($v.MYSQLDATABASE) -e DB_USERNAME=$($v.MYSQLUSER) -e DB_PASSWORD=$pass -e MYSQL_SSL_NO_VERIFY=true app php artisan {{command}}

# Ejecutar cualquier comando artisan contra la BD de producción (ej: just prod-artisan migrate)
prod-artisan *args:
    just _prod-run-artisan "{{args}}"

# Ejecutar migraciones en producción
prod-migrate:
    just _prod-run-artisan "migrate --force"

# Limpiar cachés en producción (contra BD prod; para caché del servidor usa prod-ssh)
prod-clear:
    just _prod-run-artisan "optimize:clear"

# Abrir shell SSH en el contenedor de Railway (requiere: railway ssh keys add)
prod-ssh:
    railway ssh -s mgf-workspace

# Alias legacy
prod-shell:
    just prod-ssh

# Ver logs de producción en vivo
prod-logs:
    railway logs

# Diagnóstico SMTP con variables MAIL_* de producción (no requiere SSH)
# Usa Docker local; prueba Gmail con la misma config que Railway.
prod-mail-diagnose *args:
    $v = (railway variables --service mgf-workspace --json | ConvertFrom-Json); $keys = @('APP_ENV','APP_URL','APP_BRAND','APP_NAME','APP_KEY','QUEUE_CONNECTION','MAIL_MAILER','MAIL_SCHEME','MAIL_HOST','MAIL_PORT','MAIL_USERNAME','MAIL_PASSWORD','MAIL_ENCRYPTION','MAIL_FROM_ADDRESS','MAIL_FROM_NAME'); $envArgs = @(); foreach ($key in $keys) { $val = $v.$key; if ($null -ne $val -and "$val".Trim() -ne '') { $envArgs += '-e'; $envArgs += ($key + '=' + "$val".Trim().Trim('"')) } }; docker compose exec -T @envArgs app php artisan mgf:mail-diagnose {{args}}

# Diagnóstico dentro del contenedor Railway (requiere: ssh-keygen + railway ssh keys add)
prod-mail-diagnose-ssh *args:
    railway ssh -s mgf-workspace -- php artisan mgf:mail-diagnose {{args}}

# Verificar esquema de BD en producción
prod-doctor:
    just _prod-run-artisan "mgf:migrate-doctor"

# Crear un respaldo (backup) de la base de datos de producción y guardarlo localmente
prod-backup:
    if (-not (Test-Path database/backups)) { New-Item -ItemType Directory -Path database/backups | Out-Null }; $date = Get-Date -Format 'yyyy-MM-dd_HH-mm-ss'; $out = "database/backups/prod_$date.sql"; $err = "database/backups/prod_$date.err"; $v = (railway variables --service MySQL --json | ConvertFrom-Json); $pass = $v.MYSQLPASSWORD.Trim(); cmd /c "docker run --rm mysql:8.0 mysqldump --no-tablespaces --ssl-mode=REQUIRED -h $($v.RAILWAY_TCP_PROXY_DOMAIN) -P $($v.RAILWAY_TCP_PROXY_PORT) -u $($v.MYSQLUSER) -p$pass $($v.MYSQLDATABASE) > $out 2> $err"; if (-not (Test-Path $out) -or (Get-Item $out).Length -lt 100) { if (Test-Path $err) { Get-Content $err; Remove-Item $err -ErrorAction SilentlyContinue }; if (Test-Path $out) { Remove-Item $out }; throw 'Backup falló: archivo vacío o inválido' }; Remove-Item $err -ErrorAction SilentlyContinue; Write-Host "✅ Respaldo de producción guardado en $out"

# Descargar backup de producción y restaurarlo en la BD local (¡sobrescribe tu BD local!)
prod-sync-local:
    $reply = Read-Host 'Esto reemplazará tu BD local con datos de producción. ¿Continuar? [s/N]'; if ($reply -notmatch '^[sS]$') { Write-Host 'Cancelado.'; exit 0 }; Write-Host '⬇️  Descargando backup de producción...'; just prod-backup; $latest = Get-ChildItem database/backups/prod_*.sql | Sort-Object LastWriteTime -Descending | Select-Object -First 1; if (-not $latest) { throw 'No se encontró ningún backup prod_*.sql' }; Write-Host "⬆️  Restaurando en BD local: $($latest.Name)..."; cmd /c "docker compose exec -T mysql mysql -u laravel -psecret laravel < $($latest.FullName)"; {{app}} php artisan optimize:clear; Write-Host "✅ BD local sincronizada con producción ($($latest.Name))"

# Restaurar un respaldo (backup) en la base de datos de producción (ej: just prod-restore prod_2026-06-27_12-00-00.sql)
prod-restore file:
    $v = (railway variables --service MySQL --json | ConvertFrom-Json); $pass = $v.MYSQLPASSWORD.Trim(); $file = "database/backups/{{file}}"; if (-not (Test-Path $file)) { throw "No existe el archivo: $file" }; cmd /c "docker run --rm -i mysql:8.0 mysql --ssl-mode=REQUIRED -h $($v.RAILWAY_TCP_PROXY_DOMAIN) -P $($v.RAILWAY_TCP_PROXY_PORT) -u $($v.MYSQLUSER) -p$pass $($v.MYSQLDATABASE) < $file"; Write-Host "✅ Base de datos de producción restaurada desde $file"