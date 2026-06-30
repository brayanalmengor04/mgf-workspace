<?php

namespace App\Filament\Widgets;

use App\Models\BudgetPlan;
use App\Models\BudgetPlanItem;
use App\Support\MoneyFormatter;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class BudgetOverviewWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 2;

    protected ?string $heading = 'Pagos de presupuestos';

    protected function getStats(): array
    {
        $user = auth()->user();

        if ($user === null) {
            return [];
        }

        $plansQuery = BudgetPlan::query()->forUser($user);

        $paidBudgets = (clone $plansQuery)->where('is_paid', true)->count();
        $pendingBudgets = (clone $plansQuery)->where('is_paid', false)->count();

        $itemsQuery = BudgetPlanItem::query()
            ->whereHas('budgetPlan', fn ($query) => $query->forUser($user));

        $paidAmount = (clone $itemsQuery)->where('is_paid', true)->sum('amount');
        $pendingAmount = (clone $itemsQuery)->where('is_paid', false)->sum('amount');

        return [
            Stat::make('Presupuestos Pagados', $paidBudgets)
                ->description('Monto pagado: '.MoneyFormatter::format((float) $paidAmount))
                ->descriptionIcon('heroicon-m-check-badge')
                ->color('success'),

            Stat::make('Presupuestos Pendientes', $pendingBudgets)
                ->description('Monto pendiente: '.MoneyFormatter::format((float) $pendingAmount))
                ->descriptionIcon('heroicon-m-clock')
                ->color('danger'),

            Stat::make('Presupuestos Totales', (clone $plansQuery)->count())
                ->description('Registrados en el sistema')
                ->descriptionIcon('heroicon-m-document-text'),
        ];
    }
}
