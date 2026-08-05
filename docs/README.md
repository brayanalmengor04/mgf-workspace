# Documentación MGF Workspace

Guías del módulo de cotizaciones y del panel Filament.

| Documento | Contenido |
|-----------|-----------|
| [architecture-pattern.md](./architecture-pattern.md) | Patrón de capas, convenciones y cómo escalar nuevos módulos |
| [migrations-and-policies.md](./migrations-and-policies.md) | Esquema de BD, políticas y autorización |
| [db-squash.md](./db-squash.md) | Baseline de migraciones, CI y producción |
| [seo.md](./seo.md) | Metadatos, Open Graph, sitemap y configuración SEO |
| [justfile-commands.md](./justfile-commands.md) | Comandos `just` para desarrollo con Docker |
| [railway-deploy.md](./railway-deploy.md) | Deploy automático a Railway vía GitHub Actions |
| [correo-produccion.md](./correo-produccion.md) | **Correo en prod:** Brevo, Railway, URLs y troubleshooting |
| [railway-mail-env.txt](./railway-mail-env.txt) | Variables `MAIL_*` listas para copiar en Railway |

## Producción (Railway)

| Recurso | URL |
|---------|-----|
| App | https://mgfapp.up.railway.app |
| Panel admin | https://mgfapp.up.railway.app/admin |

Correo: **Brevo** (API HTTPS). Ver [correo-produccion.md](./correo-produccion.md).

## Usuarios demo (después de `just migrate-seed`)

| Rol | Email | Contraseña |
|-----|-------|------------|
| Administrador | `admin@miempresa.com` | `password` |
| Proveedor | `proveedor@miempresa.com` | `password` |

## Inicio rápido

```bash
just build
just migrate-seed
```

Panel: [http://localhost:8000/admin](http://localhost:8000/admin)
