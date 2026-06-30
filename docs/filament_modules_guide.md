# Guía de Creación de Módulos (Recursos de Filament)

En este proyecto utilizamos **Filament PHP** como panel de administración. Lo que comúnmente llamamos "Módulos" en el sistema (por ejemplo, Presupuestos, Cotizaciones, Usuarios), en realidad son **Recursos de Filament (Filament Resources)**. 

Un Recurso de Filament es una clase de PHP que se encarga de definir cómo se comporta un modelo de base de datos dentro del panel de administración (sus formularios de creación/edición, su tabla para listar registros y sus relaciones).

## 1. Crear un Nuevo Módulo (Recurso)

Para crear un nuevo módulo, puedes utilizar el comando de Artisan proporcionado por Filament:

```bash
# Recuerda usar "just artisan" si estás usando Docker/Justfile
php artisan make:filament-resource NombreDelModelo
```

Ejemplo:
```bash
php artisan make:filament-resource Invoice
```

Este comando generará varios archivos en la carpeta `app/Filament/Resources/InvoiceResource`:
- `InvoiceResource.php`: El archivo principal del módulo.
- `Pages/`: Contiene las páginas de listar, crear y editar (`ListInvoices.php`, `CreateInvoice.php`, `EditInvoice.php`).

## 2. Definir el Formulario (Form Schema)

Dentro del archivo `InvoiceResource.php` (o en una clase separada si lo organizas por carpetas como en `BudgetPlanForm.php`), encontrarás el método `form(Form $form)`. 

Aquí es donde defines los campos que tendrá tu formulario:

```php
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Select;

public static function form(Form $form): Form
{
    return $form
        ->schema([
            TextInput::make('title')
                ->label('Título')
                ->required(),
            Select::make('status')
                ->options([
                    'draft' => 'Borrador',
                    'published' => 'Publicado',
                ]),
            Toggle::make('is_active')
                ->label('Activo'),
        ]);
}
```

## 3. Definir la Tabla (Table Columns)

En el mismo archivo `InvoiceResource.php`, está el método `table(Table $table)`. Aquí configuras qué columnas se muestran en la vista de lista, además de los filtros y acciones:

```php
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\SelectFilter;

public static function table(Table $table): Table
{
    return $table
        ->columns([
            TextColumn::make('title')->searchable()->sortable(),
            IconColumn::make('is_active')->boolean(),
        ])
        ->filters([
            SelectFilter::make('status')
                ->options([
                    'draft' => 'Borrador',
                    'published' => 'Publicado',
                ]),
        ]);
}
```

## 4. Opciones Avanzadas

- **Widgets**: Puedes añadir widgets (como gráficos o tarjetas de resumen) a un recurso específico, o al Dashboard global (usando `php artisan make:filament-widget`).
- **Relaciones (Relation Managers)**: Si tu modelo tiene una relación "HasMany" (por ejemplo, Facturas tiene Ítems de Factura), puedes crear un Relation Manager con:
  ```bash
  php artisan make:filament-relation-manager InvoiceResource items concept
  ```
  Esto te permitirá gestionar los ítems directamente desde la página de edición de la Factura.

Para más detalles exhaustivos sobre todos los campos y configuraciones posibles, consulta la documentación oficial de [Filament PHP (Panel Builder)](https://filamentphp.com/docs).