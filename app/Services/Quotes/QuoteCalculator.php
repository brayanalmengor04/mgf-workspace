<?php

namespace App\Services\Quotes;

class QuoteCalculator
{
    /**
     * @param  array<int, array{quantity: float|string, unit_price: float|string, tax_rate: float|string}>  $items
     * @return array{
     *     items: list<array{quantity: float, unit_price: float, tax_rate: float, tax_amount: float, line_total: float}>,
     *     subtotal: float,
     *     tax_amount: float,
     *     total: float
     * }
     */
    public function calculate(array $items): array
    {
        $subtotal = 0.0;
        $taxAmount = 0.0;
        $calculatedItems = [];

        foreach ($items as $item) {
            $quantity = (float) ($item['quantity'] ?? 0);
            $unitPrice = (float) ($item['unit_price'] ?? 0);
            $taxRate = (float) ($item['tax_rate'] ?? 0);

            $lineSubtotal = round($quantity * $unitPrice, 2);
            $lineTax = round($lineSubtotal * ($taxRate / 100), 2);
            $lineTotal = round($lineSubtotal + $lineTax, 2);

            $calculatedItems[] = [
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'tax_rate' => $taxRate,
                'tax_amount' => $lineTax,
                'line_total' => $lineTotal,
            ];

            $subtotal += $lineSubtotal;
            $taxAmount += $lineTax;
        }

        $subtotal = round($subtotal, 2);
        $taxAmount = round($taxAmount, 2);

        return [
            'items' => $calculatedItems,
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'total' => round($subtotal + $taxAmount, 2),
        ];
    }
}
