<?php

namespace App\Filament\Resources\BudgetPlans\Schemas;

use App\Enums\BudgetCategoryType;
use App\Enums\BudgetPdfLayout;
use App\Enums\BudgetPeriod;
use App\Enums\QuoteCurrency;
use App\Filament\Resources\BudgetItemTemplates\BudgetItemTemplateResource;
use App\Filament\Resources\BudgetPlans\BudgetPlanResource;
use App\Models\BudgetItemTemplate;
use App\Models\BudgetPlan;
use App\Models\SavingsAccount;
use App\Services\Budgets\BudgetCalculator;
use App\Services\Budgets\BudgetItemTemplateSync;
use App\Support\MoneyFormatter;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class BudgetPlanForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Wizard::make([
                    Step::make('Identidad')
                        ->description('Título y periodo del presupuesto')
                        ->icon(Heroicon::OutlinedIdentification)
                        ->schema([
                            Select::make('period')
                                ->label('Periodo')
                                ->options(BudgetPeriod::options())
                                ->default(BudgetPeriod::Biweekly->value)
                                ->required()
                                ->native(false)
                                ->live()
                                ->afterStateUpdated(function (?string $state, callable $set): void {
                                    $period = BudgetPeriod::tryFrom((string) $state);

                                    if ($period === null) {
                                        return;
                                    }

                                    $set('title', $period->defaultTitle());
                                    $set('subtitle', $period->defaultSubtitle());
                                }),
                            TextInput::make('title')
                                ->label('Título')
                                ->required()
                                ->default(BudgetPeriod::Biweekly->defaultTitle())
                                ->maxLength(120)
                                ->columnSpanFull(),
                            TextInput::make('subtitle')
                                ->label('Subtítulo')
                                ->default(BudgetPeriod::Biweekly->defaultSubtitle())
                                ->maxLength(160)
                                ->columnSpanFull(),
                            Toggle::make('is_paid')
                                ->label('¿Presupuesto Pagado?')
                                ->disabled()
                                ->dehydrated(false)
                                ->helperText('Se marca automáticamente cuando todos los ítems individuales están pagados.')
                                ->columnSpanFull(),
                            Select::make('currency')
                                ->label('Moneda')
                                ->options(QuoteCurrency::options())
                                ->default(QuoteCurrency::Usd->value)
                                ->required()
                                ->native(false)
                                ->live(),
                            Select::make('pdf_layout')
                                ->label('Estilo de PDF')
                                ->options(BudgetPdfLayout::options())
                                ->default(BudgetPdfLayout::Classic->value)
                                ->required()
                                ->native(false)
                                ->live()
                                ->helperText(fn (Get $get): string => BudgetPdfLayout::tryFrom((string) $get('pdf_layout'))?->description() ?? '')
                                ->columnSpanFull(),
                            ColorPicker::make('primary_color')
                                ->label('Color principal')
                                ->default('#0f172a')
                                ->live(),
                            Placeholder::make('preview_hint')
                                ->label('Vista previa')
                                ->content('Guarda el presupuesto y usa el botón «Vista previa del estilo» para ver cómo se verá el PDF.')
                                ->visible(fn ($livewire): bool => ! ($livewire instanceof EditRecord))
                                ->columnSpanFull(),
                            Actions::make([
                                Action::make('preview_layout')
                                    ->label('Abrir vista previa del estilo')
                                    ->icon(Heroicon::OutlinedEye)
                                    ->color('gray')
                                    ->url(fn (EditRecord $livewire): string => BudgetPlanResource::getUrl('preview', ['record' => $livewire->getRecord()]))
                                    ->openUrlInNewTab()
                                    ->visible(fn ($livewire): bool => $livewire instanceof EditRecord),
                            ])->columnSpanFull(),
                            TextInput::make('budget_number')
                                ->label('Número')
                                ->disabled()
                                ->dehydrated(false)
                                ->visibleOn('edit'),
                        ])
                        ->columns(2),
                    Step::make('Ingresos')
                        ->description('Salario neto recibido en el periodo')
                        ->icon(Heroicon::OutlinedBanknotes)
                        ->schema([
                            TextInput::make('net_income')
                                ->label('Salario neto (recibido)')
                                ->numeric()
                                ->prefix(fn (Get $get): string => QuoteCurrency::resolve($get('currency'))->symbol())
                                ->default(0)
                                ->required()
                                ->minValue(0)
                                ->dehydrateStateUsing(fn (?string $state): float => filled($state) ? (float) $state : 0.0)
                                ->live(onBlur: true)
                                ->helperText('Monto que realmente recibes después de descuentos.'),
                            TextInput::make('income_notes')
                                ->label('Nota sobre ingresos')
                                ->default('Tras descuentos de ley (SS, SE, ISR)')
                                ->maxLength(255)
                                ->columnSpanFull(),
                            Placeholder::make('income_preview')
                                ->label('Vista previa')
                                ->content(function (Get $get): HtmlString {
                                    $amount = MoneyFormatter::format(
                                        (float) ($get('net_income') ?? 0),
                                        $get('currency')
                                    );
                                    $notes = $get('income_notes') ?: 'Sin notas adicionales';

                                    return new HtmlString(
                                        '<div style="border:1px solid #e2e8f0;border-radius:12px;padding:20px;background:#f8fafc;">'
                                        .'<div style="font-size:11px;text-transform:uppercase;letter-spacing:0.08em;color:#64748b;margin-bottom:8px;">Salario neto (recibido)</div>'
                                        .'<div style="font-size:28px;font-weight:700;color:#0f172a;">'.$amount.'</div>'
                                        .'<div style="font-size:12px;color:#64748b;margin-top:8px;">'.e($notes).'</div>'
                                        .'</div>'
                                    );
                                })
                                ->columnSpanFull(),
                        ])
                        ->columns(2),
                    Step::make('Distribución')
                        ->description('Gastos fijos, ahorros y otros conceptos')
                        ->icon(Heroicon::OutlinedChartPie)
                        ->schema([
                            Placeholder::make('reuse_hint')
                                ->hiddenLabel()
                                ->content(new HtmlString(
                                    '<p style="margin:0;font-size:13px;color:#64748b;">'
                                    .'Evita reescribir lo mismo cada quincena: copia el presupuesto anterior o agrega conceptos de tu catálogo. '
                                    .'<a href="'.e(BudgetItemTemplateResource::getUrl('index')).'" style="color:#0f766e;text-decoration:underline;">Administrar conceptos frecuentes</a>'
                                    .'</p>'
                                ))
                                ->columnSpanFull(),
                            Actions::make([
                                static::copyPreviousItemsAction(),
                                static::importCatalogItemsAction(),
                                static::saveItemsToCatalogAction(),
                            ])->columnSpanFull(),
                            Section::make('Gastos fijos')
                                ->description('Pagos recurrentes del periodo: comida, transporte, servicios…')
                                ->schema(static::categoryRepeater(BudgetCategoryType::FixedExpense))
                                ->collapsible()
                                ->collapsed(),
                            Section::make('Ahorros')
                                ->description('Metas fijas o temporales: fondos, equipos, navidad…')
                                ->schema(static::categoryRepeater(BudgetCategoryType::Savings))
                                ->collapsible()
                                ->collapsed(),
                            Section::make('Otros')
                                ->description('Conceptos que no encajan en las categorías anteriores')
                                ->schema(static::categoryRepeater(BudgetCategoryType::Other))
                                ->collapsible()
                                ->collapsed(),
                        ]),
                    Step::make('Resumen')
                        ->description('Balance y notas finales')
                        ->icon(Heroicon::OutlinedClipboardDocumentCheck)
                        ->schema([
                            Placeholder::make('allocation_dashboard')
                                ->label('Panel de distribución')
                                ->content(fn (Get $get): HtmlString => static::renderAllocationDashboard($get))
                                ->columnSpanFull(),
                            Textarea::make('footer_notes')
                                ->label('Notas al pie')
                                ->rows(3)
                                ->placeholder('Observaciones, metas del próximo periodo, recordatorios…')
                                ->columnSpanFull(),
                            Placeholder::make('totals_on_edit')
                                ->label('Totales guardados')
                                ->content(function (?BudgetPlan $record): string {
                                    if ($record === null) {
                                        return '—';
                                    }

                                    $allocated = MoneyFormatter::format((float) $record->total_allocated, $record->currency);
                                    $remaining = MoneyFormatter::format((float) $record->remaining_balance, $record->currency);

                                    return "Asignado: {$allocated} · Disponible: {$remaining}";
                                })
                                ->visibleOn('edit'),
                        ]),
                ])
                    ->label('Presupuesto')
                    ->columnSpanFull()
                    ->contained()
                    ->skippable(false),
            ]);
    }

    private static function copyPreviousItemsAction(): Action
    {
        return Action::make('copy_previous_items')
            ->label('Copiar del anterior')
            ->icon(Heroicon::OutlinedDocumentDuplicate)
            ->color('gray')
            ->requiresConfirmation()
            ->modalHeading('Copiar ítems del presupuesto anterior')
            ->modalDescription('Se reemplazarán los conceptos actuales con los del último presupuesto. Los estados de pago no se copian.')
            ->action(function (callable $set, Get $get): void {
                $user = auth()->user();

                if ($user === null) {
                    return;
                }

                $previous = BudgetPlan::query()
                    ->forUser($user)
                    ->with('items')
                    ->latest('created_at')
                    ->first();

                if ($previous === null || $previous->items->isEmpty()) {
                    Notification::make()
                        ->title('No hay un presupuesto anterior con ítems')
                        ->warning()
                        ->send();

                    return;
                }

                $groups = app(BudgetItemTemplateSync::class)
                    ->itemsToFormGroups($previous->items);

                foreach ($groups as $key => $rows) {
                    $set($key, $rows);
                }

                if (blank($get('net_income')) || (float) $get('net_income') <= 0) {
                    $set('net_income', (float) $previous->net_income);
                }

                if (blank($get('income_notes'))) {
                    $set('income_notes', $previous->income_notes);
                }

                Notification::make()
                    ->title('Ítems copiados')
                    ->body("Se importaron {$previous->items->count()} conceptos desde {$previous->budget_number}.")
                    ->success()
                    ->send();
            });
    }

    private static function importCatalogItemsAction(): Action
    {
        return Action::make('import_catalog_items')
            ->label('Agregar del catálogo')
            ->icon(Heroicon::OutlinedBookmarkSquare)
            ->color('primary')
            ->modalHeading('Agregar conceptos frecuentes')
            ->modalDescription('Se agregarán al formulario sin borrar los que ya tienes. Se omiten conceptos duplicados.')
            ->form([
                CheckboxList::make('template_ids')
                    ->label('Conceptos')
                    ->options(function (): array {
                        $user = auth()->user();

                        if ($user === null) {
                            return [];
                        }

                        return BudgetItemTemplate::query()
                            ->forUser($user)
                            ->active()
                            ->orderBy('sort_order')
                            ->orderBy('concept')
                            ->get()
                            ->mapWithKeys(function (BudgetItemTemplate $template): array {
                                $amount = number_format((float) $template->default_amount, 2);
                                $label = "{$template->category_type->label()} · {$template->concept} (\${$amount})";

                                return [$template->id => $label];
                            })
                            ->all();
                    })
                    ->searchable()
                    ->bulkToggleable()
                    ->columns(1)
                    ->required()
                    ->helperText(function (): ?string {
                        $user = auth()->user();

                        if ($user === null) {
                            return null;
                        }

                        $hasTemplates = BudgetItemTemplate::query()
                            ->forUser($user)
                            ->active()
                            ->exists();

                        return $hasTemplates
                            ? null
                            : 'Aún no tienes conceptos frecuentes. Créalos en Finanzas personales → Conceptos frecuentes, o guarda los de un presupuesto existente.';
                    }),
            ])
            ->action(function (array $data, callable $set, Get $get): void {
                $user = auth()->user();
                $ids = $data['template_ids'] ?? [];

                if ($user === null || blank($ids)) {
                    return;
                }

                $templates = BudgetItemTemplate::query()
                    ->forUser($user)
                    ->active()
                    ->whereIn('id', $ids)
                    ->orderBy('sort_order')
                    ->orderBy('concept')
                    ->get();

                if ($templates->isEmpty()) {
                    Notification::make()
                        ->title('No se encontraron conceptos')
                        ->warning()
                        ->send();

                    return;
                }

                $sync = app(BudgetItemTemplateSync::class);
                $incoming = $sync->templatesToFormGroups($templates);
                $current = [];

                foreach (BudgetCategoryType::cases() as $category) {
                    $key = "items_{$category->value}";
                    $current[$key] = $get($key) ?? [];
                }

                $merged = $sync->mergeFormGroups($current, $incoming);

                foreach ($merged as $key => $rows) {
                    $set($key, $rows);
                }

                Notification::make()
                    ->title('Conceptos agregados')
                    ->body("Se agregaron {$templates->count()} concepto(s) del catálogo.")
                    ->success()
                    ->send();
            });
    }

    private static function saveItemsToCatalogAction(): Action
    {
        return Action::make('save_items_to_catalog')
            ->label('Guardar en catálogo')
            ->icon(Heroicon::OutlinedBookmark)
            ->color('gray')
            ->requiresConfirmation()
            ->modalHeading('Guardar conceptos en el catálogo')
            ->modalDescription('Se crearán o actualizarán tus conceptos frecuentes con los montos actuales del formulario.')
            ->visible(fn ($livewire): bool => $livewire instanceof CreateRecord || $livewire instanceof EditRecord)
            ->action(function (Get $get): void {
                $user = auth()->user();

                if ($user === null) {
                    return;
                }

                $items = static::collectItemsFromState($get);

                if ($items === []) {
                    Notification::make()
                        ->title('No hay conceptos para guardar')
                        ->warning()
                        ->send();

                    return;
                }

                $synced = 0;

                foreach ($items as $index => $item) {
                    BudgetItemTemplate::query()->updateOrCreate(
                        [
                            'user_id' => $user->id,
                            'category_type' => $item['category_type'],
                            'concept' => $item['concept'],
                        ],
                        [
                            'notes' => $item['notes'],
                            'default_amount' => $item['amount'],
                            'sort_order' => $index,
                            'is_active' => true,
                        ]
                    );

                    $synced++;
                }

                Notification::make()
                    ->title('Catálogo actualizado')
                    ->body("Se guardaron {$synced} concepto(s) frecuentes.")
                    ->success()
                    ->send();
            });
    }

    /**
     * @return array<int, Repeater>
     */
    private static function categoryRepeater(BudgetCategoryType $category): array
    {
        $isSavings = $category === BudgetCategoryType::Savings;

        return [
            Repeater::make("items_{$category->value}")
                ->label($category->label())
                ->collapsible()
                ->collapsed()
                ->itemLabel(function (array $state): ?string {
                    if (blank($state['concept'] ?? null)) {
                        return 'Nuevo concepto';
                    }

                    $concept = Str::limit((string) $state['concept'], 36);
                    $amount = (float) ($state['amount'] ?? 0);

                    if ($amount <= 0) {
                        return $concept;
                    }

                    return "{$concept} · ".number_format($amount, 2);
                })
                ->schema([
                    TextInput::make('id')
                        ->hidden()
                        ->dehydrated()
                        ->numeric(),
                    TextInput::make('concept')
                        ->label('Concepto')
                        ->required()
                        ->maxLength(120)
                        ->columnSpan(3),
                    TextInput::make('notes')
                        ->label('Notas')
                        ->placeholder('Gasto quincenal, Ahorro fijo…')
                        ->maxLength(120)
                        ->columnSpan(2),
                    TextInput::make('amount')
                        ->label('Monto')
                        ->numeric()
                        ->default(0)
                        ->required()
                        ->minValue(0)
                        ->prefix(fn (Get $get): string => QuoteCurrency::resolve($get('../../currency'))->symbol())
                        ->dehydrateStateUsing(fn (?string $state): float => filled($state) ? (float) $state : 0.0)
                        ->live(onBlur: true)
                        ->columnSpan(2),
                    Placeholder::make('percentage_preview')
                        ->label('% del ingreso')
                        ->content(function (Get $get): string {
                            $netIncome = (float) ($get('../../net_income') ?? 0);
                            $amount = (float) ($get('amount') ?? 0);

                            if ($netIncome <= 0) {
                                return '—';
                            }

                            return number_format(($amount / $netIncome) * 100, 1).'%';
                        })
                        ->columnSpan(1),
                    Toggle::make('is_paid')
                        ->label('Pagado')
                        ->inline(false)
                        ->live()
                        ->columnSpan(1),
                    DatePicker::make('paid_at')
                        ->label('Fecha de pago')
                        ->visible(fn (Get $get): bool => $get('is_paid') === true)
                        ->required(fn (Get $get): bool => $get('is_paid') === true)
                        ->columnSpan(2),
                    Select::make('savings_account_id')
                        ->label('Depositar en cuenta')
                        ->options(function (): array {
                            $user = auth()->user();

                            if ($user === null) {
                                return [];
                            }

                            return SavingsAccount::query()
                                ->forUser($user)
                                ->active()
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->all();
                        })
                        ->searchable()
                        ->nullable()
                        ->native(false)
                        ->helperText('Al marcar pagado, se registrará un depósito en esta cuenta.')
                        ->visible($isSavings)
                        ->columnSpan(3),
                    Select::make('category_type')
                        ->default($category->value)
                        ->dehydrated(true)
                        ->hidden(),
                ])
                ->columns(11)
                ->defaultItems(0)
                ->reorderable(false)
                ->addActionLabel("Agregar {$category->label()}")
                ->columnSpanFull()
                ->dehydrated(false),
        ];
    }

    private static function renderAllocationDashboard(Get $get): HtmlString
    {
        $netIncome = (float) ($get('net_income') ?? 0);
        $currency = $get('currency');

        $allItems = static::collectItemsFromState($get);
        $calculator = app(BudgetCalculator::class);
        $result = $calculator->calculate($netIncome, $allItems);

        $remaining = $result['remaining_balance'];
        $remainingColor = $remaining < 0 ? '#dc2626' : ($remaining > 0 ? '#059669' : '#64748b');
        $remainingLabel = $remaining < 0 ? 'Excedido' : 'Disponible libre';

        $bars = collect(BudgetCategoryType::cases())
            ->map(function (BudgetCategoryType $category) use ($result, $currency, $netIncome): string {
                $data = $result['by_category'][$category->value];
                $width = $netIncome > 0 ? min(100, ($data['total'] / $netIncome) * 100) : 0;
                $formatted = MoneyFormatter::format($data['total'], $currency);

                if ($data['count'] === 0) {
                    return '';
                }

                return '<div style="margin-bottom:12px;">'
                    .'<div style="display:flex;justify-content:space-between;font-size:12px;margin-bottom:4px;">'
                    .'<span>'.$category->icon().' '.e($category->label()).'</span>'
                    .'<span style="font-weight:600;">'.$formatted.' ('.number_format($data['percentage'], 1).'%)</span>'
                    .'</div>'
                    .'<div style="background:#e2e8f0;border-radius:999px;height:8px;overflow:hidden;">'
                    .'<div style="background:'.$category->color().';width:'.$width.'%;height:100%;border-radius:999px;"></div>'
                    .'</div>'
                    .'</div>';
            })
            ->filter()
            ->implode('');

        return new HtmlString(
            '<div style="border:1px solid #e2e8f0;border-radius:12px;padding:20px;background:#fff;">'
            .'<div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:16px;flex-wrap:wrap;gap:12px;">'
            .'<div>'
            .'<div style="font-size:11px;text-transform:uppercase;color:#64748b;">Total asignado</div>'
            .'<div style="font-size:22px;font-weight:700;">'.MoneyFormatter::format($result['total_allocated'], $currency).'</div>'
            .'<div style="font-size:12px;color:#64748b;">'.number_format($result['allocation_rate'], 1).'% del ingreso</div>'
            .'</div>'
            .'<div style="text-align:right;">'
            .'<div style="font-size:11px;text-transform:uppercase;color:#64748b;">'.$remainingLabel.'</div>'
            .'<div style="font-size:22px;font-weight:700;color:'.$remainingColor.';">'.MoneyFormatter::format($remaining, $currency).'</div>'
            .'</div>'
            .'</div>'
            .$bars
            .($bars === '' ? '<p style="color:#64748b;font-size:13px;margin:0;">Agrega conceptos en el paso anterior para ver la distribución.</p>' : '')
            .'</div>'
        );
    }

    /**
     * @return array<int, array{category_type: string, concept: string, notes: string|null, amount: float}>
     */
    public static function collectItemsFromState(Get|array|callable $state): array
    {
        $read = match (true) {
            $state instanceof Get => fn (string $path): mixed => $state($path),
            is_callable($state) => $state,
            default => fn (string $path): mixed => data_get($state, $path),
        };

        $items = [];

        foreach (BudgetCategoryType::cases() as $category) {
            $rows = $read("items_{$category->value}") ?? [];

            foreach ($rows as $row) {
                if (blank($row['concept'] ?? null)) {
                    continue;
                }

                $items[] = [
                    'id' => filled($row['id'] ?? null) ? (int) $row['id'] : null,
                    'category_type' => $category->value,
                    'concept' => (string) $row['concept'],
                    'notes' => filled($row['notes'] ?? null) ? (string) $row['notes'] : null,
                    'amount' => (float) ($row['amount'] ?? 0),
                    'is_paid' => (bool) ($row['is_paid'] ?? false),
                    'paid_at' => filled($row['paid_at'] ?? null) ? (string) $row['paid_at'] : null,
                    'savings_account_id' => filled($row['savings_account_id'] ?? null)
                        ? (int) $row['savings_account_id']
                        : null,
                ];
            }
        }

        return $items;
    }
}
