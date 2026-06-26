# Migraciones y políticas

Referencia de cambios de esquema y reglas de autorización del módulo de cotizaciones.

## Migraciones (dominio cotizaciones)

Ejecutar con:

```bash
just migrate
# o desde cero con datos demo:
just fresh-seed
```

| Archivo | Qué hace |
|---------|----------|
| `2026_06_26_171859_create_activity_log_table.php` | Tabla Spatie `activity_log` para auditoría |
| `2026_06_26_172000_create_quote_templates_table.php` | Plantillas: emisor, PDF, banco, logo, color |
| `2026_06_26_172001_create_quotes_table.php` | Cotizaciones: emisor, destinatario, totales, PDF, `created_by` |
| `2026_06_26_172002_create_quote_items_table.php` | Líneas de cotización (cantidad, precio, ITBMS) |
| `2026_06_26_180000_add_pdf_layout_to_quote_templates_table.php` | Columna `pdf_layout` (classic/modern/minimal) |
| `2026_06_26_190000_add_currency_to_quotes_and_templates.php` | Columna `currency` (ISO 4217, default `PAB`) |
| `2026_06_26_190001_backfill_quote_currency_defaults.php` | Rellena `PAB` en registros existentes sin moneda |
| `2026_06_26_200000_add_roles_and_ownership.php` | `users.role`, `users.is_active`, `quote_templates.user_id` |
| `2026_06_26_200001_backfill_roles_and_ownership.php` | Primer usuario → admin; plantillas sin dueño → admin |

### Columnas clave

**users**

- `role`: `admin` \| `provider`
- `is_active`: bloquea acceso al panel si es `false`

**quote_templates**

- `user_id`: dueño (proveedor); admin ve todas
- `currency`, `pdf_layout`, `logo_path`, `primary_color`

**quotes**

- `created_by`: dueño de la cotización
- `currency`: moneda de la cotización
- `status`: `draft` \| `issued` \| `cancelled`

---

## Políticas (`app/Policies/`)

Laravel resuelve `{Model}Policy` automáticamente.

### `UserPolicy`

| Acción | Admin | Proveedor |
|--------|-------|-----------|
| viewAny, create, update | ✅ | ❌ |
| delete | ✅ (no a sí mismo) | ❌ |

Recursos: `UserResource` — menú **Configuración → Usuarios**.

### `QuotePolicy`

| Acción | Admin | Proveedor |
|--------|-------|-----------|
| viewAny, create | ✅ | ✅ |
| view, update | ✅ | ✅ solo `created_by = self` |
| delete | ✅ | ✅ solo borradores propios |

Scope: `Quote::scopeForUser()` en `QuoteResource::getEloquentQuery()`.

### `QuoteTemplatePolicy`

| Acción | Admin | Proveedor |
|--------|-------|-----------|
| viewAny, create | ✅ | ✅ |
| view, update, delete | ✅ | ✅ solo `user_id = self` |

Scope: `QuoteTemplate::scopeForUser()` en `QuoteTemplateResource`.

Plantilla predeterminada (`is_default`) se respeta **por proveedor** (no global).

### `ActivityPolicy`

| Acción | Admin | Proveedor |
|--------|-------|-----------|
| viewAny | ✅ activo | ✅ activo |
| view | ✅ | ✅ solo actividad donde es `causer` |
| delete | ✅ | ❌ |

Complementos:

- `App\Authorizers\ActivityLogAuthorizer` — acceso al menú de auditoría (usuarios activos).
- `App\Support\ActivityLogScope` — filtra widgets y listado por rol.
- Widgets scoped: `Filament/Widgets/Activity/Scoped*.php`
- Recurso override: `Filament/ActivityLog/ActivityLogResource.php`

**Proveedor** ve menú **Cotizaciones → Mi bitácora**.  
**Admin** ve **Configuración → Auditoría** (actividad de todos).

---

## Matriz rápida por menú

| Menú | Admin | Proveedor |
|------|-------|-----------|
| Cotizaciones | Todas | Solo las suyas |
| Plantillas | Todas | Solo las suyas |
| Usuarios | ✅ | ❌ |
| Auditoría / Mi bitácora | Todos | Solo su actividad |
| Dashboard stats | Plataforma global | Onboarding personal |
