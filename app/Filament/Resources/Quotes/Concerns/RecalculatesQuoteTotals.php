<?php

namespace App\Filament\Resources\Quotes\Concerns;

use App\Models\Quote;
use App\Services\Quotes\QuoteCalculator;

trait RecalculatesQuoteTotals
{
    protected function recalculateQuoteTotals(Quote $quote): void
    {
        $quote->load('items');

        $calculator = app(QuoteCalculator::class);
        $result = $calculator->calculate(
            $quote->items->map(fn ($item) => [
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'tax_rate' => $item->tax_rate,
            ])->all()
        );

        foreach ($quote->items->values() as $index => $item) {
            $calculated = $result['items'][$index] ?? null;

            if ($calculated === null) {
                continue;
            }

            $item->update([
                'tax_amount' => $calculated['tax_amount'],
                'line_total' => $calculated['line_total'],
                'sort_order' => $index,
            ]);
        }

        $quote->update([
            'subtotal' => $result['subtotal'],
            'tax_amount' => $result['tax_amount'],
            'total' => $result['total'],
        ]);
    }
}
