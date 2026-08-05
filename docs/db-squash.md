# Squash de migraciones (baseline)

El esquema canónico vive en [`database/schema/mysql-schema.sql`](../database/schema/mysql-schema.sql). Las 22 migraciones incrementales originales están archivadas en [`database/migrations_archive/`](../database/migrations_archive/).

## Estado actual

| Entorno | Comportamiento |
|---------|----------------|
| **Instalación nueva** (Docker, CI) | `migrate` carga `mysql-schema.sql` y listo |
| **Producción (Railway)** | Tabla `migrations` conserva registros históricos; sin archivos PHP pendientes → `migrate` = no-op |
| **Local con datos de prod** (`just prod-sync-local`) | Mismas entradas históricas en `migrations`; sin cambios al esquema |

## Comandos

| Comando | Uso |
|---------|-----|
| `just migrate` | Aplica migraciones incrementales nuevas (si existen) |
| `just fresh-seed` | Reset local + schema dump + seeders demo |
| `just db-doctor` | Verifica tablas esperadas y estado post-squash |
| `just db-squash` | Regenera el baseline completo (solo al cambiar el esquema base) |
| `just prod-doctor` | Verifica esquema en Railway |
| `just prod-backup` | Respaldo de MySQL de producción (antes de cambios grandes) |

## Agregar cambios de esquema (después del squash)

1. Crear migración incremental:
   ```bash
   just artisan make:migration add_column_to_table
   ```
2. Implementar `up()` / `down()`.
3. Probar localmente:
   ```bash
   just migrate
   just test
   just db-doctor
   ```
4. Push a `main` → CI verifica → Railway despliega y ejecuta `migrate --force`.

No vuelvas a editar `mysql-schema.sql` a mano. Para regenerar el baseline desde cero (raro):

```bash
just db-squash
```

## Backfills y datos

Lógica que antes vivía en migraciones:

| Antes | Ahora |
|-------|-------|
| `backfill_roles_and_ownership` | [`EnsureOwnershipSeeder`](../database/seeders/EnsureOwnershipSeeder.php) |
| `sync_budget_plan_payment_status` | `php artisan budget:sync-payment-status` |
| `backfill_quote_currency_defaults` | Defaults en columnas (`PAB`) |

## Producción: qué esperar al desplegar

1. GitHub Actions corre `verify` (migrate + tests + doctor).
2. Si pasa, despliega a Railway.
3. El entrypoint ejecuta `migrate --force` en background.
4. **No se borran datos.** No se ejecuta `migrate:fresh`.

Verificación manual opcional:

```bash
just prod-doctor
```

## CI (GitHub Actions)

El workflow [`.github/workflows/railway-deploy.yml`](../.github/workflows/railway-deploy.yml) tiene un job `verify` que debe pasar antes del deploy:

1. `docker compose up`
2. `php artisan migrate --force`
3. `./vendor/bin/phpunit`
4. `php artisan mgf:migrate-doctor`
