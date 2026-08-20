<?php

namespace App\Filament\Widgets;

class AvailableBalanceTrendChartWidget extends FinancialTrendChartWidget
{
    protected static ?int $sort = 5;

    protected ?string $heading = 'Tendencia financiera';

    protected int | string | array $columnSpan = 'full';
}
