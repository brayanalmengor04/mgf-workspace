<?php

namespace App\Filament\Widgets;

use App\Enums\QuoteCurrency;
use App\Enums\QuoteStatus;
use App\Filament\Resources\QuoteTemplates\QuoteTemplateResource;
use App\Filament\Resources\Quotes\QuoteResource;
use App\Models\Quote;
use App\Models\QuoteTemplate;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class QuotesOverviewWidget extends TableWidget
{
    protected static ?int $sort = 1;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->check();
    }

    public function table(Table $table): Table
    {
        $user = auth()->user();

        return $table
            ->heading($user?->isProvider() ? 'Mis cotizaciones recientes' : 'Cotizaciones recientes')
            ->description(fn (): string => sprintf(
                '%d este mes · %d borradores · %d emitidas',
                Quote::query()->forUser($user)->where('created_at', '>=', now()->startOfMonth())->count(),
                Quote::query()->forUser($user)->where('status', QuoteStatus::Draft)->count(),
                Quote::query()->forUser($user)->where('status', QuoteStatus::Issued)->count(),
            ))
            ->query(
                Quote::query()->forUser($user)->latest()->limit(5)
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
                    ->money(fn (Quote $record): string => QuoteCurrency::resolve($record->currency)->value),
            ])
            ->paginated(false)
            ->headerActions([
                Action::make('new_quote')
                    ->label('Nueva cotización')
                    ->icon('heroicon-o-plus')
                    ->url(QuoteResource::getUrl('create')),
                Action::make('templates')
                    ->label('Mis plantillas')
                    ->icon('heroicon-o-document-duplicate')
                    ->url(QuoteTemplateResource::getUrl('index'))
                    ->visible(fn (): bool => auth()->user()?->isProvider() ?? false),
            ]);
    }
}
