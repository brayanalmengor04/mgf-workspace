<?php

namespace App\Filament\Widgets;

use App\Enums\QuoteStatus;
use App\Filament\Resources\Quotes\QuoteResource;
use App\Models\Quote;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class QuotesOverviewWidget extends TableWidget
{
    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Cotizaciones recientes')
            ->description(fn (): string => sprintf(
                '%d este mes · %d borradores · %d emitidas',
                Quote::query()->where('created_at', '>=', now()->startOfMonth())->count(),
                Quote::query()->where('status', QuoteStatus::Draft)->count(),
                Quote::query()->where('status', QuoteStatus::Issued)->count(),
            ))
            ->query(
                Quote::query()->latest()->limit(5)
            )
            ->columns([
                TextColumn::make('quote_number')
                    ->label('Número'),
                TextColumn::make('recipient_name')
                    ->label('Destinatario')
                    ->limit(30),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn (QuoteStatus $state): string => $state->label()),
                TextColumn::make('total')
                    ->label('Total')
                    ->money('USD'),
            ])
            ->paginated(false)
            ->headerActions([
                Action::make('new_quote')
                    ->label('Nueva cotización')
                    ->icon('heroicon-o-plus')
                    ->url(QuoteResource::getUrl('create')),
            ]);
    }
}
