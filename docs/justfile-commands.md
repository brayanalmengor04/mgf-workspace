# Comandos Just (Docker)

El proyecto usa [Just](https://github.com/casey/just) como atajo sobre `docker compose`. Todos los comandos PHP corren **dentro del contenedor `app`**.

## Requisitos

- Docker Desktop en ejecución
- [Just](https://github.com/casey/just#installation) instalado

## Contenedores

| Comando | Descripción |
|---------|-------------|
| `just` | Selector interactivo de recetas |
| `just up` | Levantar contenedores en segundo plano |
| `just dev` | Levantar y ver logs en vivo |
| `just build` | Construir imágenes y levantar |
| `just rebuild` | Rebuild sin caché de Docker |
| `just rebuild-clean` | Rebuild borrando volúmenes (**pierde la BD**) |
| `just down` | Parar contenedores |
| `just restart` | Reiniciar contenedores |
| `just ps` | Estado de servicios |
| `just logs` | Logs de la app |
| `just logs-mysql` | Logs de MySQL |
| `just shell` | Shell dentro del contenedor app |

## Laravel / PHP

| Comando | Descripción |
|---------|-------------|
| `just artisan migrate` | Migraciones pendientes |
| `just migrate` | Alias de migrate |
| `just migrate-seed` | Migrar + seeders |
| `just fresh` | `migrate:fresh` (reset BD) |
| `just fresh-seed` | Reset BD + seeders |
| `just artisan config:clear` | Limpiar caché de config |
| `just clear` | `optimize:clear` (config, route, view, cache) |
| `just test` | PHPUnit / Pest |
| `just filament-user` | Crear usuario Filament interactivo |

### Ejemplos artisan

```bash
just artisan tinker
just artisan route:list
just artisan db:seed --class=UserSeeder
just artisan storage:link
```

## Dependencias

| Comando | Descripción |
|---------|-------------|
| `just composer install` | Dependencias PHP |
| `just npm install` | Dependencias Node |
| `just npm run build` | Compilar assets Vite |
| `just setup` | composer + npm + build + migrate --force |

## Flujo típico de desarrollo

```bash
# Primera vez
just build
just setup
just migrate-seed

# Día a día
just up
just logs

# Tras pull con nuevas migraciones
just migrate

# Tras cambios en config o policies
just clear
```

## URLs

- App: `http://localhost:8000`
- Login admin: `http://localhost:8000/admin/login`
- Raíz `/` redirige al login
