# MGF Workspace

Sistema de cotizaciones con panel administrativo Filament. Stack: Laravel, PHP 8.4, MySQL 8.4, Docker.

## Inicio rápido

Requisitos: Docker Desktop y [Just](https://github.com/casey/just#installation).

```bash
just build
just migrate-seed
```

| Recurso | URL |
|---------|-----|
| App | http://localhost:8000 |
| Panel Filament | http://localhost:8000/admin |
| MySQL (cliente externo) | `localhost:3309` — base `laravel`, usuario `laravel`, contraseña `secret` |

La raíz `/` redirige al login del panel.

### Usuarios demo (después de `just migrate-seed`)

| Rol | Email | Contraseña |
|-----|-------|------------|
| Administrador | `admin@miempresa.com` | `password` |
| Proveedor | `proveedor@miempresa.com` | `password` |

---

## Comandos Just (Docker)

El proyecto usa [Just](https://github.com/casey/just) como atajo sobre `docker compose`. Todos los comandos PHP corren **dentro del contenedor `app`**.

`vendor` y `node_modules` viven en volúmenes de Docker (más rápido en Windows).

### Contenedores

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

### Laravel / PHP

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

```bash
just artisan tinker
just artisan route:list
just artisan db:seed --class=UserSeeder
just artisan storage:link
```

### Dependencias

| Comando | Descripción |
|---------|-------------|
| `just composer install` | Dependencias PHP |
| `just npm install` | Dependencias Node |
| `just npm run build` | Compilar assets Vite |
| `just setup` | composer + npm + build + migrate --force |

### Flujo típico de desarrollo

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

### Docker Compose directo (alternativa)

```bash
docker compose up -d --build
docker compose exec app php artisan migrate
docker compose exec app npm run build
docker compose logs -f app
```

---

## Patrón de arquitectura

Estructura **orientada a recursos Filament** con lógica de dominio fuera de la UI. Sirve para escalar nuevos módulos (facturas, clientes, reportes) sin mezclar responsabilidades.

### Vista general

```
app/
├── Enums/              # Estados y catálogos tipados (QuoteStatus, UserRole, QuoteCurrency)
├── Models/             # Eloquent + relaciones + scopes de negocio (forUser)
├── Policies/           # Autorización Laravel (admin vs proveedor vs ownership)
├── Authorizers/        # Autorización de paquetes externos (activity log)
├── Services/{Modulo}/  # Lógica de aplicación (PDF, cálculos, numeración)
├── Support/            # Helpers reutilizables (MoneyFormatter, ActivityLogScope)
├── Rules/              # Validación de formularios (PanamaRuc, PanamaDv)
└── Filament/
    ├── Resources/{Recurso}/
    │   ├── {Recurso}Resource.php   # Registro del recurso, query global, relaciones
    │   ├── Schemas/{Recurso}Form.php  # Formulario (Wizard / Sections)
    │   ├── Tables/{Recurso}Table.php  # Columnas, filtros, acciones
    │   └── Pages/                  # Create, Edit, List
    ├── Resources/Concerns/       # Traits compartidos (HasPartyFields)
    ├── Widgets/                  # Dashboard por rol
    └── ActivityLog/              # Overrides de plugins (no auto-discover)
```

### Convenciones por capa

#### 1. Enum (`app/Enums/`)

- Un archivo por concepto (`QuoteStatus`, `UserRole`, `QuoteCurrency`).
- Métodos `label()`, `options()` para Filament y PDF.
- Valores persistidos como `string` en BD.

#### 2. Modelo (`app/Models/`)

- `$fillable` explícito, `casts()` con enums.
- Relaciones nombradas en singular/plural estándar Laravel.
- **Scope `forUser(User $user)`**: admin ve todo; proveedor solo lo suyo.
- Defaults en `$attributes` cuando aplica (`currency`, `role`).

#### 3. Policy (`app/Policies/`)

- Una policy por modelo (`QuotePolicy`, `UserPolicy`).
- Reglas típicas:
  - **Admin**: acceso total.
  - **Proveedor**: solo registros propios (`created_by` / `user_id`).
- Filament consulta policies automáticamente si el nombre sigue `{Model}Policy`.

#### 4. Service (`app/Services/{Modulo}/`)

- Clases final readonly cuando sea posible.
- Sin dependencia de Filament ni HTTP.
- Ejemplo: `QuotePdfService` genera PDF; `QuoteCalculator` calcula totales.

#### 5. Recurso Filament

| Pieza | Responsabilidad |
|-------|-----------------|
| `*Resource.php` | Modelo, navegación, `getEloquentQuery()` con scope, relaciones |
| `Schemas/*Form.php` | Solo UI del formulario (Wizard por pasos) |
| `Tables/*Table.php` | Listado, badges, acciones de fila |
| `Pages/*` | Hooks: `mutateFormDataBeforeCreate`, acciones de cabecera |

**No** poner lógica de negocio pesada en Pages; delegar a Services.

#### 6. Formularios con Wizard (stepper)

Orden recomendado para documentos comerciales:

1. **Inicio** — plantilla, moneda, metadatos
2. **Emisor** — quien emite
3. **Cliente** — destinatario
4. **Detalle** — líneas / items (Repeater colapsable)
5. **Cierre** — pago, banco, notas

```php
Wizard::make([...])
    ->label('Nombre del flujo')
    ->contained()
    ->skippable(false)
    ->columnSpanFull();
```

CSS de soporte: `public/css/filament-wizard.css` (scroll del repeater, pasos ocultos).

#### 7. Seeders

- `UserSeeder` → roles base
- `QuoteTemplateSeeder` → plantilla demo ligada al admin
- `DatabaseSeeder` → orquesta en orden de dependencias

### Escalar un módulo nuevo (checklist)

1. **Migración** — tablas + FKs + índices
2. **Enum** — estados si aplica
3. **Model** — fillable, casts, `forUser()` si hay multi-tenant por usuario
4. **Policy** — registrar reglas admin/proveedor
5. **Service** — cálculos, export, integraciones
6. **Filament Resource** — Form (Wizard), Table, Pages
7. **Seeder** — datos demo opcionales
8. **Docs** — actualizar la sección de migraciones en este README

### Multi-rol (Administrador / Proveedor)

```
┌─────────────┐     ┌──────────────────┐     ┌─────────────┐
│   Request   │────▶│  Policy + Scope  │────▶│  Query/UI   │
│  (Filament) │     │  forUser()       │     │  filtrada   │
└─────────────┘     └──────────────────┘     └─────────────┘
```

- **Administrador**: `UserRole::Admin`, sin filtro en scopes.
- **Proveedor**: `UserRole::Provider`, filtro por `user_id` o `created_by`.
- Gestión de usuarios: solo admin (`UserResource::canAccess()`).

### Plugins externos

- Override fuera de `Filament/Resources/` (ej. `Filament/ActivityLog/ActivityLogResource.php`).
- Config en `config/{plugin}.php` apuntando a clases propias.
- Widgets scoped en `Filament/Widgets/Activity/` extendiendo los del plugin.

### PDF y vistas

- Plantillas Blade en `resources/views/quotes/pdf/`.
- Estilos por layout (`classic`, `modern`, `minimal`).
- Parciales reutilizables en `partials/`.
- Payload construido en `QuotePdfService::buildPayload()` para congelar datos al emitir.

---

## Migraciones y políticas

Referencia de cambios de esquema y reglas de autorización del módulo de cotizaciones.

```bash
just migrate
# o desde cero con datos demo:
just fresh-seed
```

### Migraciones (dominio cotizaciones)

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

#### Columnas clave

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

### Políticas (`app/Policies/`)

Laravel resuelve `{Model}Policy` automáticamente.

#### `UserPolicy`

| Acción | Admin | Proveedor |
|--------|-------|-----------|
| viewAny, create, update | ✅ | ❌ |
| delete | ✅ (no a sí mismo) | ❌ |

Recursos: `UserResource` — menú **Configuración → Usuarios**.

#### `QuotePolicy`

| Acción | Admin | Proveedor |
|--------|-------|-----------|
| viewAny, create | ✅ | ✅ |
| view, update | ✅ | ✅ solo `created_by = self` |
| delete | ✅ | ✅ solo borradores propios |

Scope: `Quote::scopeForUser()` en `QuoteResource::getEloquentQuery()`.

#### `QuoteTemplatePolicy`

| Acción | Admin | Proveedor |
|--------|-------|-----------|
| viewAny, create | ✅ | ✅ |
| view, update, delete | ✅ | ✅ solo `user_id = self` |

Scope: `QuoteTemplate::scopeForUser()` en `QuoteTemplateResource`.

Plantilla predeterminada (`is_default`) se respeta **por proveedor** (no global).

#### `ActivityPolicy`

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

### Matriz rápida por menú

| Menú | Admin | Proveedor |
|------|-------|-----------|
| Cotizaciones | Todas | Solo las suyas |
| Plantillas | Todas | Solo las suyas |
| Usuarios | ✅ | ❌ |
| Auditoría / Mi bitácora | Todos | Solo su actividad |
| Dashboard stats | Plataforma global | Onboarding personal |

---

## Documentación adicional

Copias detalladas en `docs/`:

| Documento | Contenido |
|-----------|-----------|
| [docs/architecture-pattern.md](./docs/architecture-pattern.md) | Patrón de capas y convenciones |
| [docs/migrations-and-policies.md](./docs/migrations-and-policies.md) | Migraciones y autorización |
| [docs/justfile-commands.md](./docs/justfile-commands.md) | Comandos Just |
