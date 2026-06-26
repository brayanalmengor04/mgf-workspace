<?php

namespace App\Filament\Resources\QuoteTemplates\Tables;

use App\Enums\QuoteCurrency;
use App\Enums\QuotePdfLayout;
use App\Filament\Resources\Quotes\QuoteResource;
use App\Models\QuoteTemplate;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class QuoteTemplatesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('issuer_name')
                    ->label('Emisor')
                    ->searchable(),
                TextColumn::make('owner.name')
                    ->label('Proveedor')
                    ->searchable()
                    ->visible(fn (): bool => auth()->user()?->isAdmin() ?? false),
                TextColumn::make('currency')
                    ->label('Moneda')
                    ->formatStateUsing(fn ($state): string => QuoteCurrency::resolve($state)->label())
                    ->toggleable(),
                TextColumn::make('pdf_layout')
                    ->label('Estilo PDF')
                    ->formatStateUsing(fn (?string $state): string => QuotePdfLayout::tryFrom((string) $state)?->label() ?? 'Clásico')
                    ->toggleable(),
                IconColumn::make('is_default')
                    ->label('Predeterminada')
                    ->boolean(),
                IconColumn::make('is_active')
                    ->label('Activa')
                    ->boolean(),
                TextColumn::make('updated_at')
                    ->label('Actualizada')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->recordActions([
                Action::make('use_template')
                    ->label('Usar plantilla')
                    ->icon('heroicon-o-document-plus')
                    ->url(fn (QuoteTemplate $record): string => QuoteResource::getUrl('create', ['template_id' => $record->id])),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
