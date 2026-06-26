# Patrón de arquitectura

Este proyecto sigue una estructura **orientada a recursos Filament** con lógica de dominio fuera de la UI. Sirve para escalar nuevos módulos (facturas, clientes, reportes) sin mezclar responsabilidades.

## Vista general

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

## Convenciones por capa

### 1. Enum (`app/Enums/`)

- Un archivo por concepto (`QuoteStatus`, `UserRole`, `QuoteCurrency`).
- Métodos `label()`, `options()` para Filament y PDF.
- Valores persistidos como `string` en BD.

### 2. Modelo (`app/Models/`)

- `$fillable` explícito, `casts()` con enums.
- Relaciones nombradas en singular/plural estándar Laravel.
- **Scope `forUser(User $user)`**: admin ve todo; proveedor solo lo suyo.
- Defaults en `$attributes` cuando aplica (`currency`, `role`).

### 3. Policy (`app/Policies/`)

- Una policy por modelo (`QuotePolicy`, `UserPolicy`).
- Reglas típicas:
  - **Admin**: acceso total.
  - **Proveedor**: solo registros propios (`created_by` / `user_id`).
- Filament consulta policies automáticamente si el nombre sigue `{Model}Policy`.

### 4. Service (`app/Services/{Modulo}/`)

- Clases final readonly cuando sea posible.
- Sin dependencia de Filament ni HTTP.
- Ejemplo: `QuotePdfService` genera PDF; `QuoteCalculator` calcula totales.

### 5. Recurso Filament

| Pieza | Responsabilidad |
|-------|-----------------|
| `*Resource.php` | Modelo, navegación, `getEloquentQuery()` con scope, relaciones |
| `Schemas/*Form.php` | Solo UI del formulario (Wizard por pasos) |
| `Tables/*Table.php` | Listado, badges, acciones de fila |
| `Pages/*` | Hooks: `mutateFormDataBeforeCreate`, acciones de cabecera |

**No** poner lógica de negocio pesada en Pages; delegar a Services.

### 6. Formularios con Wizard (stepper)

Orden recomendado para documentos comerciales:

1. **Inicio** — plantilla, moneda, metadatos
2. **Emisor** — quien emite
3. **Cliente** — destinatario
4. **Detalle** — líneas / items (Repeater colapsable)
5. **Cierre** — pago, banco, notas

Configuración habitual:

```php
Wizard::make([...])
    ->label('Nombre del flujo')
    ->contained()
    ->skippable(false)
    ->columnSpanFull();
```

CSS de soporte: `public/css/filament-wizard.css` (scroll del repeater, pasos ocultos).

### 7. Seeders

- `UserSeeder` → roles base
- `QuoteTemplateSeeder` → plantilla demo ligada al admin
- `DatabaseSeeder` → orquesta en orden de dependencias

## Escalar un módulo nuevo (checklist)

1. **Migración** — tablas + FKs + índices
2. **Enum** — estados si aplica
3. **Model** — fillable, casts, `forUser()` si hay multi-tenant por usuario
4. **Policy** — registrar reglas admin/proveedor
5. **Service** — cálculos, export, integraciones
6. **Filament Resource** — Form (Wizard), Table, Pages
7. **Seeder** — datos demo opcionales
8. **Docs** — fila en `migrations-and-policies.md`

## Multi-rol (Administrador / Proveedor)

```
┌─────────────┐     ┌──────────────────┐     ┌─────────────┐
│   Request   │────▶│  Policy + Scope  │────▶│  Query/UI   │
│  (Filament) │     │  forUser()       │     │  filtrada   │
└─────────────┘     └──────────────────┘     └─────────────┘
```

- **Administrador**: `UserRole::Admin`, sin filtro en scopes.
- **Proveedor**: `UserRole::Provider`, filtro por `user_id` o `created_by`.
- Gestión de usuarios: solo admin (`UserResource::canAccess()`).

## Plugins externos

- Override fuera de `Filament/Resources/` (ej. `Filament/ActivityLog/ActivityLogResource.php`).
- Config en `config/{plugin}.php` apuntando a clases propias.
- Widgets scoped en `Filament/Widgets/Activity/` extendiendo los del plugin.

## PDF y vistas

- Plantillas Blade en `resources/views/quotes/pdf/`.
- Estilos por layout (`classic`, `modern`, `minimal`).
- Parciales reutilizables en `partials/`.
- Payload construido en `QuotePdfService::buildPayload()` para congelar datos al emitir.
