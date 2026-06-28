$ErrorActionPreference = "Stop"

param(
    [Parameter(Position = 0)]
    [ValidateSet(
        "migrate",
        "migrate-seed",
        "seed",
        "fresh",
        "fresh-seed",
        "backup",
        "restore",
        "artisan",
        "shell",
        "logs",
        "clear",
        "filament-user",
        "help"
    )]
    [string]$Command = "help",

    [Parameter(ValueFromRemainingArguments = $true)]
    [string[]]$Rest
)

$configPath = Join-Path $PSScriptRoot "railway.config.json"
if (-not (Test-Path $configPath)) {
    throw "No se encontró scripts/railway.config.json"
}

$config = Get-Content $configPath -Raw | ConvertFrom-Json
$appService = $config.appService
$mysqlService = $config.mysqlService

function Assert-RailwayCli {
    if (-not (Get-Command railway -ErrorAction SilentlyContinue)) {
        throw "Railway CLI no está instalado. Instálalo con: npm i -g @railway/cli"
    }
}

function Invoke-RailwayApp {
    param([string[]]$Args)
    Assert-RailwayCli
    & railway run --service $appService @Args
    if ($LASTEXITCODE -ne 0) { exit $LASTEXITCODE }
}

function Show-Help {
    Write-Host @"
Comandos de producción (Railway)

  migrate         Ejecutar migraciones pendientes
  migrate-seed    Migrar + seeders
  seed            Solo seeders
  fresh           migrate:fresh (destructivo)
  fresh-seed      migrate:fresh --seed (destructivo)
  backup          Volcar MySQL a database/backups/
  restore <file>  Restaurar backup en producción
  artisan <cmd>   Ejecutar php artisan ...
  shell           Shell en el contenedor app
  logs            Logs del servicio app
  clear           optimize:clear
  filament-user   Crear usuario admin Filament

Requisitos: railway login y scripts/railway.config.json con los nombres de servicio.

Ejemplos:
  .\scripts\railway-prod.ps1 migrate
  .\scripts\railway-prod.ps1 artisan "migrate:status"
  just prod migrate
"@
}

switch ($Command) {
    "help" {
        Show-Help
    }

    "migrate" {
        Invoke-RailwayApp @("php", "artisan", "migrate", "--force")
    }

    "migrate-seed" {
        Invoke-RailwayApp @("php", "artisan", "migrate", "--seed", "--force")
    }

    "seed" {
        Invoke-RailwayApp @("php", "artisan", "db:seed", "--force")
    }

    "fresh" {
        $confirm = Read-Host "¿Borrar TODAS las tablas en producción? Escribe 'si' para continuar"
        if ($confirm -ne "si") { throw "Cancelado." }
        Invoke-RailwayApp @("php", "artisan", "migrate:fresh", "--force")
    }

    "fresh-seed" {
        $confirm = Read-Host "¿Borrar TODAS las tablas y volver a seedear? Escribe 'si' para continuar"
        if ($confirm -ne "si") { throw "Cancelado." }
        Invoke-RailwayApp @("php", "artisan", "migrate:fresh", "--seed", "--force")
    }

    "backup" {
        Assert-RailwayCli
        $backupDir = Join-Path (Split-Path $PSScriptRoot -Parent) "database/backups"
        New-Item -ItemType Directory -Force -Path $backupDir | Out-Null
        $timestamp = Get-Date -Format "yyyy-MM-dd_HH-mm-ss"
        $outputFile = Join-Path $backupDir "prod_$timestamp.sql"

        $dumpCmd = "mysqldump -h `$MYSQLHOST -P `$MYSQLPORT -u `$MYSQLUSER -p`$MYSQLPASSWORD `$MYSQLDATABASE --single-transaction --no-tablespaces"
        cmd /c "railway run --service $mysqlService sh -c `"$dumpCmd`" > `"$outputFile`""

        if ($LASTEXITCODE -ne 0) { exit $LASTEXITCODE }
        Write-Host "Respaldo guardado en $outputFile"
    }

    "restore" {
        if (-not $Rest -or $Rest.Count -eq 0) {
            throw "Uso: restore <archivo.sql en database/backups/>"
        }

        $fileName = $Rest[0]
        $backupDir = Join-Path (Split-Path $PSScriptRoot -Parent) "database/backups"
        $inputFile = Join-Path $backupDir $fileName

        if (-not (Test-Path $inputFile)) {
            throw "No existe $inputFile"
        }

        $confirm = Read-Host "¿Restaurar $fileName en producción? Escribe 'si' para continuar"
        if ($confirm -ne "si") { throw "Cancelado." }

        Assert-RailwayCli
        $restoreCmd = "mysql -h `$MYSQLHOST -P `$MYSQLPORT -u `$MYSQLUSER -p`$MYSQLPASSWORD `$MYSQLDATABASE"
        cmd /c "type `"$inputFile`" | railway run --service $mysqlService sh -c `"$restoreCmd`""

        if ($LASTEXITCODE -ne 0) { exit $LASTEXITCODE }
        Write-Host "Base de datos restaurada desde $inputFile"
    }

    "artisan" {
        if (-not $Rest -or $Rest.Count -eq 0) {
            throw "Uso: artisan <comando artisan>"
        }
        $artisanArgs = @("php", "artisan") + $Rest
        Invoke-RailwayApp $artisanArgs
    }

    "shell" {
        Assert-RailwayCli
        & railway run --service $appService sh
    }

    "logs" {
        Assert-RailwayCli
        & railway logs --service $appService
    }

    "clear" {
        Invoke-RailwayApp @("php", "artisan", "optimize:clear")
    }

    "filament-user" {
        Invoke-RailwayApp @("php", "artisan", "make:filament-user")
    }
}
