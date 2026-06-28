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
| `APP_KEY` | Mismo valor que en `.env.PRD` |
| `APP_DEBUG` | `false` |
| `APP_URL` | URL pública del servicio Railway |
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

## Requisitos en Railway

1. Servicio `mgf-workspace` creado en el entorno de producción.
2. Builder: **Dockerfile** (`Dockerfile` en la raíz del repo).
3. Variables anteriores configuradas en el servicio.

## Verificación

Tras un push o un run manual, revisa en GitHub Actions que el job *Deploy to Railway* termine en verde y en Railway que aparezca un nuevo deployment en estado **Success**.
