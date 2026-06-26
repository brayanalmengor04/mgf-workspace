# Documentación MGF Workspace

Guías del módulo de cotizaciones y del panel Filament.

| Documento | Contenido |
|-----------|-----------|
| [architecture-pattern.md](./architecture-pattern.md) | Patrón de capas, convenciones y cómo escalar nuevos módulos |
| [migrations-and-policies.md](./migrations-and-policies.md) | Migraciones del dominio, políticas y autorización |
| [justfile-commands.md](./justfile-commands.md) | Comandos `just` para desarrollo con Docker |

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
