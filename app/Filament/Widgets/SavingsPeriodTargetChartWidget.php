<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\OnlyOnSavingAccountsList;
use App\Filament\Widgets\Concerns\ResolvesSavingsAnalytics;
use App\Support\MoneyFormatter;
use Filament\Widgets\ChartWidget;
use Illuminate\Contracts\Support\Htmlable;

class SavingsPeriodTargetChartWidget extends ChartWidget
{
    use OnlyOnSavingAccountsList;
    use ResolvesSavingsAnalytics;

    protected static bool $isDiscovered = false;

    protected ?string $heading = 'Meta del período';

    protected ?string $maxHeight = '260px';

    protected int | string | array $columnSpan = 1;

    protected static ?int $sort = 1;

    public function getDescription(): string | Htmlable | null
    {
        $period = $this->savingsAnalytics()['period_targets'] ?? [];

        if (! ($period['has_targets'] ?? false)) {
            return 'Configura “Monto meta por período” en tus cuentas.';
        }

        return 'Meta: '.MoneyFormatter::format($period['total_target'])
            .' · Depositado: '.MoneyFormatter::format($period['total_deposited'])
            .' ('.number_format((float) $period['progress_percent'], 1).'%)';
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getData(): array
    {
        $period = $this->savingsAnalytics()['period_targets'] ?? [];

        if (! ($period['has_targets'] ?? false)) {
            return $this->emptyDoughnut('Sin meta');
        }

        $deposited = (float) $period['total_deposited'];
        $remaining = (float) $period['remaining'];

        if ($deposited <= 0 && $remaining <= 0) {
            return $this->emptyDoughnut('Sin depósitos');
        }

        return [
            'labels' => ['Depositado', 'Por depositar'],
            'datasets' => [[
                'data' => [$deposited, $remaining],
                'backgroundColor' => ['#3b82f6', '#fde68a'],
                'borderWidth' => 0,
            ]],
        ];
    }

    protected function getOptions(): ?array
    {
        return $this->doughnutOptions();
    }
}
