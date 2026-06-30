<?php

namespace App\Filament\Widgets;

use App\Models\BudgetPlan;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class BudgetOverviewWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $totalBudgets = BudgetPlan::count();
        
        $paidBudgets = BudgetPlan::where('is_paid', true)->count();
        $pendingBudgets = BudgetPlan::where('is_paid', false)->count();

        $paidAmount = BudgetPlan::where('is_paid', true)->sum('net_income');
        $pendingAmount = BudgetPlan::where('is_paid', false)->sum('net_income');

        return [
            Stat::make('Presupuestos Totales', $totalBudgets)
                ->description('Total de presupuestos registrados')
                ->descriptionIcon('heroicon-m-document-text'),

            Stat::make('Presupuestos Pagados', $paidBudgets)
                ->description('Monto pagado: $'.number_format($paidAmount, 2))
                ->descriptionIcon('heroicon-m-check-badge')
                ->color('success'),

            Stat::make('Presupuestos Pendientes', $pendingBudgets)
                ->description('Monto pendiente: $'.number_format($pendingAmount, 2))
                ->descriptionIcon('heroicon-m-clock')
                ->color('danger'),
        ];
    }
}
