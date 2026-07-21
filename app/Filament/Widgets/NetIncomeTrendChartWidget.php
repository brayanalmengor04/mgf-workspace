<?php

namespace App\Filament\Widgets;

class NetIncomeTrendChartWidget extends FinancialTrendChartWidget
{
    protected static ?int $sort = 5;

    protected ?string $heading = 'Tendencia · Ingresos netos';

    protected int | string | array $columnSpan = 'full';

    protected function seriesKey(): string
    {
        return 'net_income';
    }

    protected function seriesLabel(): string
    {
        return 'Ingresos netos';
    }

    protected function borderColor(): string
    {
        return '#f59e0b';
    }

    protected function fillColor(): string
    {
        return 'rgba(245, 158, 11, 0.12)';
    }
}
