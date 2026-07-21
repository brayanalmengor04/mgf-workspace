<?php

namespace App\Filament\Widgets;

use App\Services\Budgets\FinancialMetricsService;
use App\Support\MoneyFormatter;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class FinancialHealthWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 2;

    protected ?string $heading = 'Salud del presupuesto';

    public static function canView(): bool
    {
        return auth()->check();
    }

    protected function getStats(): array
    {
        $user = auth()->user();

        if ($user === null) {
            return [];
        }

        $metrics = app(FinancialMetricsService::class)->overviewFor($user);
        $currency = $metrics['currency'];

        return [
            Stat::make('Ahorro del período', MoneyFormatter::format($metrics['savings_amount'], $currency))
                ->description($metrics['has_issued']
                    ? $metrics['savings_percent'].'% del ingreso neto'
                    : 'Sin presupuestos emitidos')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),

            Stat::make('Gastos fijos', MoneyFormatter::format($metrics['fixed_expenses_amount'], $currency))
                ->description($metrics['has_issued']
                    ? $metrics['fixed_expenses_percent'].'% del ingreso neto'
                    : 'Sin presupuestos emitidos')
                ->descriptionIcon('heroicon-m-home')
                ->color('warning'),

            Stat::make('Presupuestos excedidos', (string) $metrics['exceeded_plans_count'])
                ->description('Emitidos con saldo negativo')
                ->descriptionIcon($metrics['exceeded_plans_count'] > 0
                    ? 'heroicon-m-exclamation-triangle'
                    : 'heroicon-m-check-circle')
                ->color($metrics['exceeded_plans_count'] > 0 ? 'danger' : 'success'),
        ];
    }
}
