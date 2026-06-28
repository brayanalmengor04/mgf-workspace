# Deploy en Railway (GitHub Actions)

El workflow [`.github/workflows/railway-deploy.yml`](../.github/workflows/railway-deploy.yml) despliega la rama `main` en el servicio `mgf-workspace` de Railway.

## Disparadores

- **Push** a `main`
- **Manual**: GitHub → Actions → *Deploy to Railway* → *Run workflow*

## Requisitos en GitHub

| Secret | Descripción |
|--------|-------------|
| `RAILWAY_TOKEN` | Project token del proyecto en Railway (Settings → Tokens) |

El token debe pertenecer al mismo proyecto donde está el servicio `mgf-workspace`.

## Variables de entorno en Railway

`.env.PRD` es solo referencia local (está en `.gitignore` y **no** se sube al repo). En producción, Laravel lee las variables que configures en Railway.

1. Railway → proyecto → servicio **mgf-workspace** → pestaña **Variables**.
2. Copia cada clave de tu `.env.PRD` local (valores reales de producción).
3. Mínimo necesario:

| Variable | Notas |
|----------|--------|
| `APP_ENV` | `production` |
| `APP_KEY` | **Obligatorio.** Formato `base64:...` (copiar de `.env.PRD` sin comillas extra) |
| `APP_DEBUG` | `false` |
| `APP_URL` | URL pública real del servicio (ej. `https://mgf-workspace-production.up.railway.app`). Si usas el placeholder `tu-dominio.railway.app`, los CSS/JS de Filament no cargarán. El entrypoint también la ajusta desde `RAILWAY_PUBLIC_DOMAIN` al arrancar. |
| `DB_CONNECTION` | `mysql` |
| `DB_HOST` | `mysql.railway.internal` (o el host de tu plugin MySQL) |
| `DB_PORT` | `3306` |
| `DB_DATABASE` | Nombre de la base |
| `DB_USERNAME` | Usuario MySQL |
| `DB_PASSWORD` | Contraseña MySQL |
| `SESSION_DRIVER` | `database` |
| `CACHE_STORE` | `database` |
| `QUEUE_CONNECTION` | `database` |

Railway también puede inyectar `MYSQL_URL`, `MYSQLHOST`, etc. si tienes un servicio MySQL en el mismo proyecto; alinea `DB_*` con esos valores.

### APP_KEY (MissingAppKeyException)

Si ves `No application encryption key has been specified`:

1. Railway → servicio **mgf-workspace** → **Variables** → confirma que `APP_KEY` existe y empieza con `base64:`.
2. Sin comillas en el valor (Railway las guarda literal si las pones).
3. Redeploy después de guardar.

En cada arranque, el contenedor materializa un `.env` desde esas variables y ejecuta `migrate --force`.

## Operaciones en producción

Requisitos locales: [Railway CLI](https://docs.railway.com/develop/cli) instalado y `railway login`.

Configura nombres de servicio en [`scripts/railway.config.json`](../scripts/railway.config.json) (`appService`, `mysqlService`).

| Comando | Acción |
|---------|--------|
| `just prod migrate` | Migraciones pendientes |
| `just prod migrate-seed` | Migrar + seeders |
| `just prod seed` | Solo seeders |
| `just prod backup` | Volcar MySQL a `database/backups/` |
| `just prod restore <archivo.sql>` | Restaurar backup (pide confirmación) |
| `just prod artisan "migrate:status"` | Cualquier comando artisan |
| `just prod shell` | Shell en el contenedor |
| `just prod logs` | Logs del servicio |
| `just prod clear` | Limpiar cachés |
| `just prod filament-user` | Crear usuario admin |

Comandos destructivos (`fresh`, `fresh-seed`, `restore`) piden escribir `si` para confirmar.

### Error 502 (Application failed to respond)

Railway espera que la app escuche en la variable `PORT` de inmediato. El entrypoint arranca el servidor primero y corre migraciones en segundo plano. Si persiste el 502:

1. Revisa **Deploy Logs** (no Build Logs) y busca `Listening on 0.0.0.0:...`
2. En Railway → servicio → **Settings → Networking**, confirma que el puerto expuesto coincide (Railway inyecta `PORT` automáticamente).
3. Health check opcional: path `/up`

## Requisitos en Railway

1. Servicio `mgf-workspace` creado en el entorno de producción.
2. Builder: **Dockerfile** (`Dockerfile` en la raíz del repo).
3. Variables anteriores configuradas en el servicio.

## Verificación

Tras un push o un run manual, revisa en GitHub Actions que el job *Deploy to Railway* termine en verde y en Railway que aparezca un nuevo deployment en estado **Success**.
