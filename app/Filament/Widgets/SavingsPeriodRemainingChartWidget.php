<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\OnlyOnSavingAccountsList;
use App\Filament\Widgets\Concerns\ResolvesSavingsAnalytics;
use App\Support\MoneyFormatter;
use Filament\Widgets\ChartWidget;
use Illuminate\Contracts\Support\Htmlable;

class SavingsPeriodRemainingChartWidget extends ChartWidget
{
    use OnlyOnSavingAccountsList;
    use ResolvesSavingsAnalytics;

    protected static bool $isDiscovered = false;

    protected ?string $heading = 'Te falta este período';

    protected ?string $maxHeight = '260px';

    protected int | string | array $columnSpan = 1;

    protected static ?int $sort = 2;

    public function getDescription(): string | Htmlable | null
    {
        $period = $this->savingsAnalytics()['period_targets'] ?? [];

        if (! ($period['has_targets'] ?? false)) {
            return 'Sin meta por período definida.';
        }

        if ((float) $period['remaining'] <= 0) {
            return 'Meta del período cumplida. Depositaste '.MoneyFormatter::format($period['total_deposited']).'.';
        }

        return 'Te faltan '.MoneyFormatter::format($period['remaining'])
            .' para llegar a '.MoneyFormatter::format($period['total_target']).'.';
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

        $remaining = (float) $period['remaining'];
        $deposited = (float) $period['total_deposited'];

        if ($remaining <= 0) {
            return [
                'labels' => ['Meta cumplida'],
                'datasets' => [[
                    'data' => [1],
                    'backgroundColor' => ['#10b981'],
                    'borderWidth' => 0,
                ]],
            ];
        }

        return [
            'labels' => ['Te falta', 'Depositado'],
            'datasets' => [[
                'data' => [$remaining, $deposited],
                'backgroundColor' => ['#f59e0b', '#3b82f6'],
                'borderWidth' => 0,
            ]],
        ];
    }

    protected function getOptions(): ?array
    {
        return $this->doughnutOptions();
    }
}
