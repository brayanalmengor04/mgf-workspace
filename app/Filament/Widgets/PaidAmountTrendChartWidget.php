<?php

namespace App\Filament\Widgets;

class PaidAmountTrendChartWidget extends FinancialTrendChartWidget
{
    protected static ?int $sort = 7;

    protected ?string $heading = 'Tendencia · Monto pagado';

    protected int | string | array $columnSpan = 1;

    protected function seriesKey(): string
    {
        return 'paid_amount';
    }

    protected function seriesLabel(): string
    {
        return 'Monto pagado';
    }

    protected function borderColor(): string
    {
        return '#3b82f6';
    }

    protected function fillColor(): string
    {
        return 'rgba(59, 130, 246, 0.12)';
    }
}
