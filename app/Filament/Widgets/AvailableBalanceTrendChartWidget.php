<?php

namespace App\Filament\Widgets;

class AvailableBalanceTrendChartWidget extends FinancialTrendChartWidget
{
    protected static ?int $sort = 6;

    protected ?string $heading = 'Tendencia · Saldo disponible';

    protected int | string | array $columnSpan = 1;

    protected function seriesKey(): string
    {
        return 'available_balance';
    }

    protected function seriesLabel(): string
    {
        return 'Saldo disponible';
    }

    protected function borderColor(): string
    {
        return '#10b981';
    }

    protected function fillColor(): string
    {
        return 'rgba(16, 185, 129, 0.12)';
    }
}
