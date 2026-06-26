<?php

namespace App\Filament\Resources\Quotes\Tables;

use App\Enums\QuoteCurrency;
use App\Enums\QuoteStatus;
use App\Models\Quote;
use App\Services\Quotes\QuotePdfService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Response;

class QuotesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('quote_number')
                    ->label('Número')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('recipient_name')
                    ->label('Destinatario')
                    ->searchable()
                    ->limit(40),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn (QuoteStatus $state): string => $state->label())
                    ->color(fn (QuoteStatus $state): string => match ($state) {
                        QuoteStatus::Draft => 'gray',
                        QuoteStatus::Issued => 'success',
                        QuoteStatus::Cancelled => 'danger',
                    }),
                TextColumn::make('total')
                    ->label('Total')
                    ->money(fn (Quote $record): string => QuoteCurrency::resolve($record->currency)->value)
                    ->sortable(),
                TextColumn::make('currency')
                    ->label('Moneda')
                    ->formatStateUsing(fn ($state): string => QuoteCurrency::resolve($state)->label())
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('issued_at')
                    ->label('Emitida')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('creator.name')
                    ->label('Creada por')
                    ->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('Estado')
                    ->options(collect(QuoteStatus::cases())->mapWithKeys(
                        fn (QuoteStatus $status): array => [$status->value => $status->label()]
                    )),
            ])
            ->recordActions([
                Action::make('download')
                    ->label('PDF')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->visible(fn (Quote $record): bool => $record->pdf_path !== null)
                    ->action(function (Quote $record) {
                        $path = app(QuotePdfService::class)->downloadPath($record);

                        if ($path === null) {
                            Notification::make()
                                ->title('PDF no disponible')
                                ->danger()
                                ->send();

                            return;
                        }

                        return Response::download($path, "{$record->quote_number}.pdf");
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
