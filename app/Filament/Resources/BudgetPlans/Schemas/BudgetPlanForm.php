<?php

namespace App\Filament\Resources\BudgetPlans\Schemas;

use App\Enums\BudgetCategoryType;
use App\Enums\BudgetPdfLayout;
use App\Enums\BudgetPeriod;
use App\Enums\QuoteCurrency;
use App\Filament\Resources\BudgetPlans\BudgetPlanResource;
use App\Models\BudgetPlan;
use App\Support\MoneyFormatter;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Actions\Action;
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
                                ->placeholder('Tras descuentos de ley (SS, SE, ISR)')
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

    /**
     * @return array<int, Repeater>
     */
    private static function categoryRepeater(BudgetCategoryType $category): array
    {
        return [
            Repeater::make("items_{$category->value}")
                ->label($category->label())
                ->collapsible()
                ->collapsed()
                ->itemLabel(function (array $state) use ($category): ?string {
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
                    Select::make('category_type')
                        ->default($category->value)
                        ->dehydrated(true)
                        ->hidden(),
                ])
                ->columns(8)
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
        $calculator = app(\App\Services\Budgets\BudgetCalculator::class);
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
                    'category_type' => $category->value,
                    'concept' => (string) $row['concept'],
                    'notes' => filled($row['notes'] ?? null) ? (string) $row['notes'] : null,
                    'amount' => (float) ($row['amount'] ?? 0),
                ];
            }
        }

        return $items;
    }
}
