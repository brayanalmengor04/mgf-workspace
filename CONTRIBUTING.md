# Guía de contribución

Gracias por interesarte en **MGF Workspace**. Este documento resume cómo colaborar de forma ordenada.

## Antes de empezar

1. Revisa el [README](README.md) para levantar el entorno con Docker y Just.
2. Lee la [política de seguridad](SECURITY.md) si encuentras algo sensible.
3. Consulta los [issues abiertos](https://github.com/brayanalmengor04/mgf-workspace/issues) por si ya se reportó lo mismo.

## Entorno local

```bash
just build
just migrate-seed
```

- App: http://localhost:8000  
- Panel: http://localhost:8000/admin  
- Tests: `just test` o `docker compose exec app php artisan test`

## Flujo de trabajo

1. Haz fork del repositorio (si no tienes acceso directo).
2. Crea una rama desde `main`:
   - `feat/descripcion-corta` — nueva funcionalidad
   - `fix/descripcion-corta` — corrección de bug
   - `docs/descripcion-corta` — solo documentación
3. Haz commits claros en español o inglés, por ejemplo:
   - `feat(budgets): permitir guardar en cualquier paso del wizard`
   - `fix(mail): registrar transporte Brevo`
4. Abre un **Pull Request** hacia `main` y completa la plantilla.
5. Espera revisión antes del merge.

## Estilo de código

- Sigue las convenciones existentes del proyecto (Laravel, Filament, PSR-12).
- Cambios pequeños y enfocados: un PR por tema cuando sea posible.
- No incluyas en el commit:
  - `.env` o secretos
  - `storage/`, `vendor/`, cachés ni backups de base de datos
  - Cambios masivos de formato no relacionados con tu tarea

## Issues

Usa las plantillas de GitHub:

- **Reporte de error** — bugs reproducibles
- **Solicitud de nueva función** — mejoras o ideas

Incluye pasos para reproducir, entorno (local / Railway) y capturas si aplica.

## Pull requests

- Describe **qué** cambia y **por qué**.
- Indica cómo probarlo (checklist en la plantilla del PR).
- Si toca UI o PDFs, menciona capturas o PDF de ejemplo.
- Asegúrate de que los tests pasen en local si modificaste lógica de negocio.

## Despliegue

La rama `main` se despliega en Railway. No hagas push directo a producción sin revisión si trabajas en equipo.

## Dudas

Abre un issue con la etiqueta que corresponda o contacta al mantenedor vía el repositorio.
