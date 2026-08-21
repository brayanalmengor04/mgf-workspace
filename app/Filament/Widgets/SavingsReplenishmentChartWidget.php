<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\OnlyOnSavingAccountsList;
use App\Filament\Widgets\Concerns\ResolvesSavingsAnalytics;
use App\Support\MoneyFormatter;
use Filament\Widgets\ChartWidget;
use Illuminate\Contracts\Support\Htmlable;

class SavingsReplenishmentChartWidget extends ChartWidget
{
    use OnlyOnSavingAccountsList;
    use ResolvesSavingsAnalytics;

    protected static bool $isDiscovered = false;

    protected ?string $heading = 'Por reponer';

    protected ?string $maxHeight = '260px';

    protected int | string | array $columnSpan = 1;

    protected static ?int $sort = 3;

    public function getDescription(): string | Htmlable | null
    {
        $replenishment = $this->savingsAnalytics()['replenishment'] ?? [];
        $pending = (float) ($replenishment['pending'] ?? 0);

        if ($pending <= 0) {
            return 'Todo repuesto. Tu saldo refleja lo acumulado sin pendientes.';
        }

        return 'Pendiente: '.MoneyFormatter::format($pending)
            .' · Repuesto: '.MoneyFormatter::format($replenishment['replenished'] ?? 0)
            .' ('.number_format((float) ($replenishment['progress_percent'] ?? 0), 1).'%)';
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getData(): array
    {
        $replenishment = $this->savingsAnalytics()['replenishment'] ?? [];
        $pending = (float) ($replenishment['pending'] ?? 0);
        $replenished = (float) ($replenishment['replenished'] ?? 0);

        if ($pending <= 0 && $replenished <= 0) {
            return $this->emptyDoughnut('Sin retiros');
        }

        if ($pending <= 0) {
            return [
                'labels' => ['Todo repuesto'],
                'datasets' => [[
                    'data' => [1],
                    'backgroundColor' => ['#10b981'],
                    'borderWidth' => 0,
                ]],
            ];
        }

        return [
            'labels' => ['Repuesto', 'Por reponer'],
            'datasets' => [[
                'data' => [$replenished, $pending],
                'backgroundColor' => ['#10b981', '#f59e0b'],
                'borderWidth' => 0,
            ]],
        ];
    }

    protected function getOptions(): ?array
    {
        return $this->doughnutOptions();
    }
}
