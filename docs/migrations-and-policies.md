# Migraciones y políticas

Referencia de esquema y reglas de autorización.

## Esquema de base de datos

Tras el squash (agosto 2026), el esquema canónico está en:

- [`database/schema/mysql-schema.sql`](../database/schema/mysql-schema.sql) — baseline para instalaciones nuevas

Runbook completo: [`docs/db-squash.md`](db-squash.md)

### Comandos

```bash
just migrate          # migraciones incrementales nuevas
just fresh-seed       # reset local + seeders demo
just db-doctor        # verificar esquema
just prod-doctor      # verificar esquema en Railway
```

### Tablas del dominio

| Tabla | Descripción |
|-------|-------------|
| `users` | Usuarios con `role` (`admin` \| `provider`) e `is_active` |
| `quote_templates` | Plantillas por proveedor (`user_id`), moneda, PDF |
| `quotes` | Cotizaciones con `quote_date`, `currency`, `created_by` |
| `quote_items` | Líneas de cotización |
| `budget_plans` | Presupuestos con `is_paid`, `pdf_layout`, `primary_color` |
| `budget_plan_items` | Líneas con `is_paid`, `paid_at` |
| `budget_item_templates` | Plantillas de conceptos por usuario |
| `calendar_events` | Eventos del calendario financiero |
| `activity_log` | Auditoría Spatie |

### Columnas clave

**users**

- `role`: `admin` \| `provider`
- `is_active`: bloquea acceso al panel si es `false`

**quote_templates**

- `user_id`: dueño (proveedor); admin ve todas
- `currency`, `pdf_layout`, `logo_path`, `primary_color`

**quotes**

- `created_by`: dueño de la cotización
- `quote_date`: fecha de la cotización
- `currency`: moneda de la cotización
- `status`: `draft` \| `issued` \| `cancelled`

**budget_plans**

- `is_paid`: sincronizado desde líneas vía `budget:sync-payment-status`
- `pdf_layout`, `primary_color`

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
