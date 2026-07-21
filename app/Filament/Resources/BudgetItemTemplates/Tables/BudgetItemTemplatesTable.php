<?php

namespace App\Filament\Resources\BudgetItemTemplates\Tables;

use App\Enums\BudgetCategoryType;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BudgetItemTemplatesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('concept')
                    ->label('Concepto')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('category_type')
                    ->label('Categoría')
                    ->badge()
                    ->formatStateUsing(fn (BudgetCategoryType $state): string => $state->label())
                    ->color(fn (BudgetCategoryType $state): string => match ($state) {
                        BudgetCategoryType::FixedExpense => 'gray',
                        BudgetCategoryType::Savings => 'success',
                        BudgetCategoryType::Other => 'warning',
                    })
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('default_amount')
                    ->label('Monto')
                    ->money('USD')
                    ->sortable(),
                TextColumn::make('notes')
                    ->label('Notas')
                    ->limit(40)
                    ->toggleable(),
                IconColumn::make('is_active')
                    ->label('Activo')
                    ->boolean()
                    ->sortable(),
                TextColumn::make('owner.name')
                    ->label('Usuario')
                    ->toggleable()
                    ->visible(fn (): bool => auth()->user()?->isAdmin() ?? false),
                TextColumn::make('updated_at')
                    ->label('Actualizado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->defaultGroup('category_type')
            ->groups([
                Group::make('category_type')
                    ->label('Categoría')
                    ->titlePrefixedWithLabel(false)
                    ->getTitleFromRecordUsing(fn ($record): string => $record->category_type->label())
                    ->getDescriptionFromRecordUsing(fn ($record): string => match ($record->category_type) {
                        BudgetCategoryType::FixedExpense => 'Pagos recurrentes del periodo',
                        BudgetCategoryType::Savings => 'Metas fijas o temporales',
                        BudgetCategoryType::Other => 'Conceptos varios',
                    })
                    ->collapsible()
                    ->orderQueryUsing(function (Builder $query, string $direction): Builder {
                        $order = $direction === 'desc'
                            ? "CASE category_type WHEN 'other' THEN 1 WHEN 'savings' THEN 2 WHEN 'fixed_expense' THEN 3 ELSE 4 END"
                            : "CASE category_type WHEN 'fixed_expense' THEN 1 WHEN 'savings' THEN 2 WHEN 'other' THEN 3 ELSE 4 END";

                        return $query
                            ->orderByRaw($order)
                            ->orderBy('sort_order');
                    }),
            ])
            ->groupingSettingsHidden()
            ->filters([
                SelectFilter::make('category_type')
                    ->label('Categoría')
                    ->options(BudgetCategoryType::options())
                    ->native(false)
                    ->multiple()
                    ->preload()
                    ->indicateUsing(function (array $data): ?string {
                        $values = $data['values'] ?? [];

                        if ($values === [] || $values === null) {
                            return null;
                        }

                        $labels = collect($values)
                            ->map(fn (string $value): string => BudgetCategoryType::tryFrom($value)?->label() ?? $value)
                            ->implode(', ');

                        return "Categoría: {$labels}";
                    }),
                TernaryFilter::make('is_active')
                    ->label('Activo')
                    ->placeholder('Todos')
                    ->trueLabel('Activos')
                    ->falseLabel('Inactivos'),
            ], layout: FiltersLayout::AboveContentCollapsible)
            ->filtersFormColumns(2)
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('Eliminar seleccionados'),
                ]),
            ]);
    }
}
