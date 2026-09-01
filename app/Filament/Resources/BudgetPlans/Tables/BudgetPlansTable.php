<?php

namespace App\Filament\Resources\BudgetPlans\Tables;

use App\Enums\BudgetPeriod;
use App\Enums\BudgetStatus;
use App\Enums\QuoteCurrency;
use App\Filament\Resources\BudgetPlans\BudgetPlanResource;
use App\Models\BudgetPlan;
use App\Services\Budgets\BudgetPdfService;
use App\Services\Budgets\BudgetPlanDuplicator;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Response;

class BudgetPlansTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('Presupuesto')
                    ->description(fn (BudgetPlan $record): string => $record->budget_number)
                    ->searchable(['title', 'budget_number'])
                    ->sortable()
                    ->limit(42)
                    ->url(fn (BudgetPlan $record): string => BudgetPlanResource::getUrl('view', ['record' => $record])),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn (BudgetStatus $state): string => $state->label())
                    ->color(fn (BudgetStatus $state): string => match ($state) {
                        BudgetStatus::Draft => 'gray',
                        BudgetStatus::Issued => 'success',
                        BudgetStatus::Archived => 'warning',
                    }),
                TextColumn::make('period')
                    ->label('Periodo')
                    ->formatStateUsing(fn (BudgetPeriod $state): string => $state->label())
                    ->badge()
                    ->color('gray')
                    ->toggleable(),
                TextColumn::make('net_income')
                    ->label('Ingreso')
                    ->money(fn (BudgetPlan $record): string => QuoteCurrency::resolve($record->currency)->value)
                    ->sortable(),
                TextColumn::make('remaining_balance')
                    ->label('Disponible')
                    ->money(fn (BudgetPlan $record): string => QuoteCurrency::resolve($record->currency)->value)
                    ->color(fn (BudgetPlan $record): string => (float) $record->remaining_balance < 0 ? 'danger' : 'success')
                    ->sortable(),
                TextColumn::make('payment_progress')
                    ->label('Cumplimiento')
                    ->state(function (BudgetPlan $record): float {
                        $total = (float) $record->items->sum('amount');

                        if ($total <= 0) {
                            return 0.0;
                        }

                        $paid = (float) $record->items->where('is_paid', true)->sum('amount');

                        return round(($paid / $total) * 100, 1);
                    })
                    ->formatStateUsing(fn (float $state): string => number_format($state, 0).'%')
                    ->badge()
                    ->color(fn (float $state): string => match (true) {
                        $state >= 100 => 'success',
                        $state >= 50 => 'warning',
                        default => 'gray',
                    })
                    ->sortable(false),
                IconColumn::make('is_paid')
                    ->label('Liquidado')
                    ->boolean()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('issued_at')
                    ->label('Emitido')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('creator.name')
                    ->label('Creado por')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->paginated([10, 25, 50])
            ->emptyStateHeading('Sin presupuestos')
            ->emptyStateDescription('Crea tu primer comprobante para comenzar a planificar tus finanzas.')
            ->emptyStateIcon('heroicon-o-calculator')
            ->filters([
                SelectFilter::make('status')
                    ->label('Estado')
                    ->options(collect(BudgetStatus::cases())->mapWithKeys(
                        fn (BudgetStatus $status): array => [$status->value => $status->label()]
                    )),
                SelectFilter::make('period')
                    ->label('Periodo')
                    ->options(BudgetPeriod::options()),
                TernaryFilter::make('is_paid')
                    ->label('Pagado')
                    ->placeholder('Todos')
                    ->trueLabel('Pagados')
                    ->falseLabel('Pendientes'),
            ])
            ->recordActions([
                Action::make('view')
                    ->label('Abrir')
                    ->icon('heroicon-o-eye')
                    ->color('primary')
                    ->url(fn (BudgetPlan $record): string => BudgetPlanResource::getUrl('view', ['record' => $record])),
                ActionGroup::make([
                    Action::make('charts')
                        ->label('Métricas')
                        ->icon('heroicon-o-chart-pie')
                        ->url(fn (BudgetPlan $record): string => BudgetPlanResource::getUrl('view', ['record' => $record, 'tab' => 'summary'])),
                Action::make('duplicate')
                    ->label('Duplicar')
                    ->icon('heroicon-o-document-duplicate')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->modalDescription('Se creará un borrador nuevo con los mismos conceptos e ingresos, sin estados de pago ni PDF.')
                    ->action(function (BudgetPlan $record) {
                        $duplicate = app(BudgetPlanDuplicator::class)->duplicate($record->load('items'));

                        Notification::make()
                            ->title('Presupuesto duplicado')
                            ->success()
                            ->send();

                        return redirect(BudgetPlanResource::getUrl('edit', ['record' => $duplicate]));
                    }),
                Action::make('download')
                    ->label('PDF')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->visible(fn (BudgetPlan $record): bool => $record->pdf_path !== null)
                    ->action(function (BudgetPlan $record) {
                        $path = app(BudgetPdfService::class)->downloadPath($record);

                        if ($path === null) {
                            Notification::make()
                                ->title('PDF no disponible')
                                ->danger()
                                ->send();

                            return;
                        }

                        return Response::download($path, "{$record->budget_number}.pdf");
                    }),
                Action::make('change_payment_status')
                    ->label('Cambiar estado')
                    ->icon('heroicon-o-currency-dollar')
                    ->color('info')
                    ->modalHeading('Actualizar estado de pagos')
                    ->modalWidth('4xl')
                    ->fillForm(function (BudgetPlan $record): array {
                        return [
                            'items' => $record->items->map(function ($item) {
                                return [
                                    'id' => $item->id,
                                    'concept' => $item->concept,
                                    'is_paid' => $item->is_paid,
                                    'paid_at' => $item->paid_at,
                                ];
                            })->toArray(),
                        ];
                    })
                    ->form([
                        Repeater::make('items')
                            ->label('Ítems del presupuesto')
                            ->schema([
                                Hidden::make('id'),
                                TextInput::make('concept')
                                    ->label('Concepto')
                                    ->readOnly()
                                    ->columnSpan(2),
                                DatePicker::make('paid_at')
                                    ->label('Fecha de pago')
                                    ->required(fn ($get) => $get('is_paid'))
                                    ->columnSpan(1),
                                Toggle::make('is_paid')
                                    ->label('Pagado')
                                    ->inline(false)
                                    ->live()
                                    ->columnSpan(1),
                            ])
                            ->columns(4)
                            ->addable(false)
                            ->deletable(false)
                            ->reorderable(false)
                            ->collapsible()
                            ->collapsed()
                            ->itemLabel(fn (array $state): ?string => $state['concept'] ?? 'Ítem')
                            ->extraAttributes([
                                'class' => 'max-h-96 overflow-y-auto p-2 border border-gray-200 dark:border-gray-800 rounded-lg shadow-inner',
                            ]),
                    ])
                    ->action(function (array $data, BudgetPlan $record): void {
                        foreach ($data['items'] as $itemData) {
                            $updateData = [
                                'is_paid' => $itemData['is_paid'],
                            ];

                            if ($itemData['is_paid']) {
                                $updateData['paid_at'] = $itemData['paid_at'];
                            } else {
                                $updateData['paid_at'] = null;
                            }

                            $record->items()->where('id', $itemData['id'])->update($updateData);
                        }

                        $record->syncPaymentStatus();

                        Notification::make()
                            ->title('Estado de pagos actualizado')
                            ->success()
                            ->send();
                    }),
                    EditAction::make(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
