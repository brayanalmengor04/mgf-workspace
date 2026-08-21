<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\OnlyOnSavingAccountsList;
use App\Services\Savings\SavingsLedgerService;
use App\Support\MoneyFormatter;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SavingsPanelStatsWidget extends StatsOverviewWidget
{
    use OnlyOnSavingAccountsList;

    protected static bool $isDiscovered = false;

    protected ?string $heading = 'Resumen de ahorros';

    protected int | string | array $columnSpan = 'full';

    protected static ?int $sort = 2;

    protected function getStats(): array
    {
        $user = auth()->user();

        if ($user === null) {
            return [];
        }

        $analytics = app(SavingsLedgerService::class)->analyticsForUser($user);
        $summary = $analytics['summary'];
        $totals = $analytics['totals'];
        $balanceTrend = $analytics['trend']['balance'];

        return [
            Stat::make('Saldo actual', MoneyFormatter::format($summary['total_balance']))
                ->description($summary['active_accounts'].' cuenta(s) activa(s)')
                ->descriptionIcon('heroicon-m-circle-stack')
                ->chart($balanceTrend !== [] ? $balanceTrend : null)
                ->color('success'),

            Stat::make('Entradas', MoneyFormatter::format($totals['openings'] + $totals['deposits'] + $totals['replenishments']))
                ->description('Aperturas, depósitos y reposiciones')
                ->descriptionIcon('heroicon-m-arrow-down-circle')
                ->chart($analytics['trend']['deposits'] !== [] ? $analytics['trend']['deposits'] : null)
                ->color('info'),

            Stat::make('Retiros', MoneyFormatter::format($totals['withdrawals']))
                ->description('Total retirado de tus cuentas')
                ->descriptionIcon('heroicon-m-arrow-up-circle')
                ->chart($analytics['trend']['withdrawals'] !== [] ? $analytics['trend']['withdrawals'] : null)
                ->color('danger'),
        ];
    }
}
