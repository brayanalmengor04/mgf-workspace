<?php

namespace App\Filament\Widgets\Concerns;

use App\Services\Savings\SavingsLedgerService;

trait ResolvesSavingsAnalytics
{
    /**
     * @return array<string, mixed>
     */
    protected function savingsAnalytics(): array
    {
        $user = auth()->user();

        if ($user === null) {
            return [];
        }

        return app(SavingsLedgerService::class)->analyticsForUser($user);
    }

    /**
     * @return array<string, mixed>
     */
    protected function doughnutOptions(): array
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

    /**
     * @return array<string, mixed>
     */
    protected function emptyDoughnut(string $label = 'Sin datos'): array
    {
        return [
            'labels' => [$label],
            'datasets' => [[
                'data' => [1],
                'backgroundColor' => ['#e5e7eb'],
                'borderWidth' => 0,
            ]],
        ];
    }
}
