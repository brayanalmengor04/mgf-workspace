<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\OnlyOnSavingAccountsList;
use App\Services\Savings\SavingsLedgerService;
use App\Support\MoneyFormatter;
use Filament\Widgets\Widget;

class SavingsTargetsCompositionWidget extends Widget
{
    use OnlyOnSavingAccountsList;

    protected static bool $isDiscovered = false;

    protected string $view = 'filament.widgets.savings-targets-composition';

    protected int | string | array $columnSpan = 'full';

    protected static ?int $sort = 1;

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $user = auth()->user();

        if ($user === null) {
            return ['cards' => []];
        }

        $analytics = app(SavingsLedgerService::class)->analyticsForUser($user);
        $period = $analytics['period_targets'];
        $replenishment = $analytics['replenishment'];

        $periodPercent = $period['has_targets'] && $period['progress_percent'] !== null
            ? (float) $period['progress_percent']
            : 0.0;

        $replenishPercent = $replenishment['progress_percent'] !== null
            ? (float) $replenishment['progress_percent']
            : 100.0;

        $replenishPending = (float) ($replenishment['pending'] ?? 0);
        $replenishBase = (float) ($replenishment['replenished'] ?? 0) + $replenishPending;

        return [
            'cards' => [
                [
                    'key' => 'period_target',
                    'label' => 'Meta del período',
                    'value' => $period['has_targets']
                        ? MoneyFormatter::format($period['total_target'])
                        : '—',
                    'hint' => $period['has_targets']
                        ? 'Objetivo combinado de tus cuentas activas'
                        : 'Configura “Monto meta por período” en una cuenta',
                    'percent' => $periodPercent,
                    'bar_color' => '#3b82f6',
                    'state' => $period['has_targets']
                        ? ($periodPercent >= 100 ? 'success' : 'active')
                        : 'empty',
                ],
                [
                    'key' => 'period_remaining',
                    'label' => 'Te falta este período',
                    'value' => $period['has_targets']
                        ? MoneyFormatter::format($period['remaining'])
                        : '—',
                    'hint' => $period['has_targets']
                        ? 'Depositaste '.MoneyFormatter::format($period['total_deposited'])
                            .' de '.MoneyFormatter::format($period['total_target'])
                            .' ('.number_format($periodPercent, 1).'%)'
                        : 'Sin meta por período definida',
                    'percent' => $period['has_targets']
                        ? max(0, min(100, 100 - $periodPercent))
                        : 0.0,
                    'bar_color' => '#f59e0b',
                    'state' => $period['has_targets']
                        ? ($period['remaining'] <= 0 ? 'success' : 'active')
                        : 'empty',
                ],
                [
                    'key' => 'replenishment',
                    'label' => 'Por reponer',
                    'value' => MoneyFormatter::format($replenishPending),
                    'hint' => $replenishPending > 0
                        ? 'Repuesto '.MoneyFormatter::format($replenishment['replenished'])
                            .' de '.MoneyFormatter::format($replenishBase)
                            .' retirado'
                        : 'Sin retiros pendientes de reposición',
                    'percent' => $replenishPending > 0 ? $replenishPercent : 100.0,
                    'bar_color' => '#10b981',
                    'state' => $replenishPending > 0 ? 'warning' : 'success',
                ],
            ],
        ];
    }
}
