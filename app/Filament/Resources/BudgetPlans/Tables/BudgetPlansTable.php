<?php

namespace App\Filament\Resources\BudgetPlans\Tables;

use App\Enums\BudgetPeriod;
use App\Enums\BudgetStatus;
use App\Enums\QuoteCurrency;
use App\Models\BudgetPlan;
use App\Services\Budgets\BudgetPdfService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
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
            ])
            ->recordActions([
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
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
