<?php

namespace App\Filament\Widgets;

use App\Services\Budgets\FinancialMetricsService;
use App\Services\Savings\SavingsLedgerService;
use App\Support\MoneyFormatter;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class FinancialHealthWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 2;

    protected ?string $heading = 'Salud del presupuesto';

    public static function canView(): bool
    {
        return false;
    }

    protected function getStats(): array
    {
        $user = auth()->user();

        if ($user === null) {
            return [];
        }

        $metrics = app(FinancialMetricsService::class)->overviewFor($user);
        $savingsSummary = app(SavingsLedgerService::class)->summaryForUser($user);
        $currency = $metrics['currency'];

        $accountsDescription = $savingsSummary['active_accounts'] > 0
            ? $savingsSummary['active_accounts'].' cuenta(s) activa(s)'
            : 'Sin cuentas de ahorro';

        if ($savingsSummary['pending_replenishment'] > 0) {
            $accountsDescription .= ' · Por reponer: '.MoneyFormatter::format($savingsSummary['pending_replenishment'], $currency);
        }

        return [
            Stat::make('Ahorro planificado', MoneyFormatter::format($metrics['savings_amount'], $currency))
                ->description($metrics['has_issued']
                    ? $metrics['savings_percent'].'% del ingreso neto · Gastos fijos '.$metrics['fixed_expenses_percent'].'%'
                    : 'Sin presupuestos emitidos')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),

            Stat::make('Saldo en cuentas', MoneyFormatter::format($savingsSummary['total_balance'], $currency))
                ->description($accountsDescription)
                ->descriptionIcon('heroicon-m-circle-stack')
                ->color($savingsSummary['pending_replenishment'] > 0 ? 'warning' : 'info'),

            Stat::make('Presupuestos excedidos', (string) $metrics['exceeded_plans_count'])
                ->description($metrics['exceeded_plans_count'] > 0
                    ? 'Emitidos con saldo negativo'
                    : 'Ningún presupuesto en rojo')
                ->descriptionIcon($metrics['exceeded_plans_count'] > 0
                    ? 'heroicon-m-exclamation-triangle'
                    : 'heroicon-m-check-circle')
                ->color($metrics['exceeded_plans_count'] > 0 ? 'danger' : 'success'),
        ];
    }
}
