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
                TextColumn::make('budget_number')
                    ->label('Número')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('title')
                    ->label('Título')
                    ->searchable()
                    ->limit(40),
                TextColumn::make('period')
                    ->label('Periodo')
                    ->formatStateUsing(fn (BudgetPeriod $state): string => $state->label())
                    ->badge()
                    ->color('gray'),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn (BudgetStatus $state): string => $state->label())
                    ->color(fn (BudgetStatus $state): string => match ($state) {
                        BudgetStatus::Draft => 'gray',
                        BudgetStatus::Issued => 'success',
                        BudgetStatus::Archived => 'warning',
                    }),
                IconColumn::make('is_paid')
                    ->label('Pagado')
                    ->boolean()
                    ->sortable(),
                TextColumn::make('net_income')
                    ->label('Ingreso neto')
                    ->money(fn (BudgetPlan $record): string => QuoteCurrency::resolve($record->currency)->value)
                    ->sortable(),
                TextColumn::make('remaining_balance')
                    ->label('Disponible')
                    ->money(fn (BudgetPlan $record): string => QuoteCurrency::resolve($record->currency)->value)
                    ->color(fn (BudgetPlan $record): string => (float) $record->remaining_balance < 0 ? 'danger' : 'success')
                    ->sortable(),
                TextColumn::make('issued_at')
                    ->label('Emitido')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('creator.name')
                    ->label('Creado por')
                    ->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
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
                Action::make('charts')
                    ->label('Ver métricas')
                    ->icon('heroicon-o-chart-pie')
                    ->color('warning')
                    ->url(fn (BudgetPlan $record): string => BudgetPlanResource::getUrl('charts', ['record' => $record])),
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
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
