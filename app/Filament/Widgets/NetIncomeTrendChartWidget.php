<?php

namespace App\Filament\Widgets;

class NetIncomeTrendChartWidget extends FinancialTrendChartWidget
{
    protected static bool $isDiscovered = false;

    public string $metric = 'net_income';

    public static function canView(): bool
    {
        return false;
    }
}
