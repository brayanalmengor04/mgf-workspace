<?php

namespace App\Filament\Resources\SavingAccounts\Tables;

use App\Enums\BudgetPeriod;
use App\Enums\QuoteCurrency;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class SavingAccountsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Meta')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('current_balance')
                    ->label('Saldo')
                    ->money(fn ($record): string => QuoteCurrency::resolve($record->currency)->value)
                    ->sortable()
                    ->color('success'),
                TextColumn::make('target_per_period')
                    ->label('Meta / período')
                    ->formatStateUsing(function ($state, $record): string {
                        if ($state === null || (float) $state <= 0) {
                            return '—';
                        }

                        $periodEnum = $record->period instanceof BudgetPeriod
                            ? $record->period
                            : BudgetPeriod::tryFrom((string) ($record->period ?? ''));

                        $period = $periodEnum?->label() ?? '';

                        return '$'.number_format((float) $state, 2).' · '.$period;
                    }),
                TextColumn::make('pending_replenishment')
                    ->label('Por reponer')
                    ->money(fn ($record): string => QuoteCurrency::resolve($record->currency)->value)
                    ->color(fn ($state): string => (float) $state > 0 ? 'warning' : 'gray')
                    ->sortable(),
                TextColumn::make('goal_amount')
                    ->label('Meta total')
                    ->money(fn ($record): string => QuoteCurrency::resolve($record->currency)->value)
                    ->placeholder('—')
                    ->toggleable(),
                TextColumn::make('bank_alias')
                    ->label('Alias banco')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('is_active')
                    ->label('Activa')
                    ->boolean()
                    ->sortable(),
                TextColumn::make('owner.name')
                    ->label('Usuario')
                    ->toggleable()
                    ->visible(fn (): bool => auth()->user()?->canViewGlobalData() ?? false),
            ])
            ->defaultSort('name')
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Activa')
                    ->placeholder('Todas')
                    ->trueLabel('Activas')
                    ->falseLabel('Archivadas'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
