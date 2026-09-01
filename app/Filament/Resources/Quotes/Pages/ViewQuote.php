<?php

namespace App\Filament\Resources\Quotes\Pages;

use App\Filament\Resources\Quotes\QuoteResource;
use App\Models\Quote;
use App\Support\MoneyFormatter;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;

class ViewQuote extends Page
{
    use InteractsWithRecord;

    protected static string $resource = QuoteResource::class;

    protected string $view = 'filament.quotes.view';

    protected static ?string $title = 'Cotización';

    protected static bool $shouldRegisterNavigation = false;

    protected Width|string|null $maxContentWidth = Width::Full;

    public string $activeTab = 'summary';

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);
        $this->mountCanAuthorizeAccess();
    }

    public function getTitle(): string
    {
        /** @var Quote $quote */
        $quote = $this->getRecord();

        return (string) $quote->quote_number;
    }

    /**
     * @return array<string, mixed>
     */
    public function getHubData(): array
    {
        /** @var Quote $quote */
        $quote = $this->getRecord();
        $currency = $quote->currency instanceof \App\Enums\QuoteCurrency
            ? $quote->currency
            : \App\Enums\QuoteCurrency::resolve($quote->currency);

        return [
            'quote' => $quote,
            'currency' => $currency,
            'formatted' => [
                'subtotal' => MoneyFormatter::format((float) $quote->subtotal, $currency),
                'tax' => MoneyFormatter::format((float) $quote->tax_amount, $currency),
                'total' => MoneyFormatter::format((float) $quote->total, $currency),
            ],
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('Volver')
                ->icon(Heroicon::OutlinedArrowLeft)
                ->url(QuoteResource::getUrl('index'))
                ->color('gray'),
            EditAction::make()
                ->url(fn (): string => QuoteResource::getUrl('edit', ['record' => $this->getRecord()])),
        ];
    }
}
