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

## Requisitos en Railway

1. Servicio `mgf-workspace` creado en el entorno de producción.
2. Builder: **Dockerfile** (`Dockerfile` en la raíz del repo).
3. Variables de entorno de la app configuradas en el servicio (ver `.env.example`).

## Verificación

Tras un push o un run manual, revisa en GitHub Actions que el job *Deploy to Railway* termine en verde y en Railway que aparezca un nuevo deployment en estado **Success**.
