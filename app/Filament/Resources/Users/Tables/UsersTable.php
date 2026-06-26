<?php

namespace App\Filament\Resources\Users\Tables;

use App\Enums\UserRole;
use App\Models\User;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->label('Correo')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('role')
                    ->label('Rol')
                    ->badge()
                    ->formatStateUsing(fn (UserRole $state): string => $state->label())
                    ->color(fn (UserRole $state): string => $state->color()),
                IconColumn::make('is_active')
                    ->label('Activo')
                    ->boolean(),
                TextColumn::make('quoteTemplates_count')
                    ->label('Plantillas')
                    ->counts('quoteTemplates')
                    ->toggleable(),
                TextColumn::make('quotes_count')
                    ->label('Cotizaciones')
                    ->counts('quotes')
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label('Registrado')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('name')
            ->filters([
                SelectFilter::make('role')
                    ->label('Rol')
                    ->options(UserRole::options()),
                SelectFilter::make('is_active')
                    ->label('Estado')
                    ->options([
                        '1' => 'Activos',
                        '0' => 'Inactivos',
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
