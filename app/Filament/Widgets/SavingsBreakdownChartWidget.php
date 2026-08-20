<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\OnlyOnSavingAccountsList;
use App\Services\Savings\SavingsLedgerService;
use App\Support\MoneyFormatter;
use Filament\Widgets\ChartWidget;
use Illuminate\Contracts\Support\Htmlable;

class SavingsBreakdownChartWidget extends ChartWidget
{
    use OnlyOnSavingAccountsList;

    protected static bool $isDiscovered = false;

    protected ?string $heading = 'Composición de movimientos';

    protected ?string $maxHeight = '280px';

    protected int | string | array $columnSpan = 1;

    public function getDescription(): string | Htmlable | null
    {
        $user = auth()->user();

        if ($user === null) {
            return null;
        }

        $analytics = app(SavingsLedgerService::class)->analyticsForUser($user);
        $gap = $analytics['gap'];

        if ($gap['pending'] <= 0) {
            return 'Todo repuesto. Tu saldo refleja lo acumulado sin pendientes.';
        }

        return 'Te faltan '.MoneyFormatter::format($gap['pending'])
            .' por reponer. Si repusieras todo, tu saldo sería '
            .MoneyFormatter::format($gap['target_if_replenished']).'.';
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

        $breakdown = app(SavingsLedgerService::class)->analyticsForUser($user)['breakdown'];
        $values = $breakdown['values'];

        if (array_sum($values) <= 0) {
            return [
                'labels' => ['Sin movimientos'],
                'datasets' => [[
                    'data' => [1],
                    'backgroundColor' => ['#e5e7eb'],
                    'borderWidth' => 0,
                ]],
            ];
        }

        return [
            'labels' => $breakdown['labels'],
            'datasets' => [[
                'data' => $values,
                'backgroundColor' => ['#059669', '#dc2626', '#f59e0b'],
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
