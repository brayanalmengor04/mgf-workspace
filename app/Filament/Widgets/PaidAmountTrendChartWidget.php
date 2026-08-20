<?php

namespace App\Filament\Widgets;

class PaidAmountTrendChartWidget extends FinancialTrendChartWidget
{
    protected static bool $isDiscovered = false;

    public string $metric = 'paid_amount';

    public static function canView(): bool
    {
        return false;
    }
}
