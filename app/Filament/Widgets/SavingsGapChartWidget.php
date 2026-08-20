<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\OnlyOnSavingAccountsList;
use App\Services\Savings\SavingsLedgerService;
use App\Support\MoneyFormatter;
use Filament\Widgets\ChartWidget;
use Illuminate\Contracts\Support\Htmlable;

class SavingsGapChartWidget extends ChartWidget
{
    use OnlyOnSavingAccountsList;

    protected static bool $isDiscovered = false;

    protected ?string $heading = 'Saldo vs pendiente';

    protected ?string $maxHeight = '280px';

    protected int | string | array $columnSpan = 1;

    public function getDescription(): string | Htmlable | null
    {
        $user = auth()->user();

        if ($user === null) {
            return null;
        }

        $gap = app(SavingsLedgerService::class)->analyticsForUser($user)['gap'];

        return 'Disponible: '.MoneyFormatter::format($gap['balance'])
            .' · Pendiente: '.MoneyFormatter::format($gap['pending']);
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getData(): array
    {
        $user = auth()->user();

        if ($user === null) {
            return ['datasets' => [], 'labels' => []];
        }

        $gap = app(SavingsLedgerService::class)->analyticsForUser($user)['gap'];
        $balance = $gap['balance'];
        $pending = $gap['pending'];

        if ($balance <= 0 && $pending <= 0) {
            return [
                'labels' => ['Sin saldo'],
                'datasets' => [[
                    'data' => [1],
                    'backgroundColor' => ['#e5e7eb'],
                    'borderWidth' => 0,
                ]],
            ];
        }

        return [
            'labels' => ['Saldo actual', 'Por reponer'],
            'datasets' => [[
                'data' => [$balance, $pending],
                'backgroundColor' => ['#10b981', '#f59e0b'],
                'borderWidth' => 0,
            ]],
        ];
    }

    protected function getOptions(): ?array
    {
        return [
            'plugins' => [
                'legend' => [
                    'position' => 'bottom',
                ],
            ],
            'cutout' => '62%',
        ];
    }
}
