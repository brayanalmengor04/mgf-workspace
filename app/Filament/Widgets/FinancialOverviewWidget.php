<?php

namespace App\Filament\Widgets;

use App\Services\Budgets\FinancialMetricsService;
use App\Support\MoneyFormatter;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class FinancialOverviewWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected ?string $heading = 'Métricas financieras';

    protected ?string $description = 'Saldo disponible = ingreso neto no asignado del presupuesto (no es saldo bancario).';

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

        $availableDescription = $this->deltaDescription(
            $metrics['available_delta'],
            $currency,
            suffix: 'vs período previo',
            empty: $metrics['has_issued'] ? 'Último presupuesto emitido' : 'Sin presupuestos emitidos',
        );

        $incomeDescription = $this->incomeDeltaDescription($metrics, $currency);

        return [
            Stat::make('Ingresos totales', MoneyFormatter::format($metrics['total_income'], $currency))
                ->description($metrics['issued_plans_count'] > 0
                    ? 'Suma de ingresos netos · '.$metrics['issued_plans_count'].' presupuestos emitidos'
                    : 'Sin presupuestos emitidos')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),

            Stat::make('Gastos totales', MoneyFormatter::format($metrics['total_expenses'], $currency))
                ->description($metrics['issued_plans_count'] > 0
                    ? 'Gastos fijos + otros · todos los emitidos'
                    : 'Sin presupuestos emitidos')
                ->descriptionIcon('heroicon-m-shopping-cart')
                ->color('danger'),

            Stat::make('Saldo disponible', MoneyFormatter::format($metrics['available_balance'], $currency))
                ->description($availableDescription['text'])
                ->descriptionIcon($availableDescription['icon'])
                ->descriptionColor($availableDescription['color'])
                ->color($metrics['available_balance'] >= 0 ? 'success' : 'danger')
                ->chart($metrics['available_sparkline'] ?: null)
                ->chartColor($metrics['available_balance'] >= 0 ? 'success' : 'danger'),

            Stat::make('Ingresos netos', MoneyFormatter::format($metrics['net_income'], $currency))
                ->description($incomeDescription['text'])
                ->descriptionIcon($incomeDescription['icon'])
                ->descriptionColor($incomeDescription['color'])
                ->color($incomeDescription['color'])
                ->chart($metrics['net_income_sparkline'] ?: null)
                ->chartColor($incomeDescription['color']),

            Stat::make('Presupuestos pagados', (string) $metrics['paid_plans_count'])
                ->description('Monto pagado: '.MoneyFormatter::format($metrics['paid_amount'], $currency))
                ->descriptionIcon('heroicon-m-check-badge')
                ->color('success')
                ->chart($metrics['payment_sparkline'] ?: null)
                ->chartColor('success'),

            Stat::make('Cumplimiento de pagos', $metrics['payment_compliance_percent'].'%')
                ->description($metrics['paid_items_count'].' de '.$metrics['total_items_count'].' ítems pagados')
                ->descriptionIcon('heroicon-m-clipboard-document-check')
                ->color($metrics['payment_compliance_percent'] >= 70 ? 'success' : ($metrics['payment_compliance_percent'] >= 40 ? 'warning' : 'danger')),
        ];
    }

    /**
     * @return array{text: string, icon: string, color: string}
     */
    private function deltaDescription(?float $delta, mixed $currency, string $suffix, string $empty): array
    {
        if ($delta === null) {
            return [
                'text' => $empty,
                'icon' => 'heroicon-m-minus',
                'color' => 'gray',
            ];
        }

        $sign = $delta > 0 ? '+' : '';
        $formatted = $sign.MoneyFormatter::format($delta, $currency);

        if ($delta > 0) {
            return [
                'text' => "{$formatted} {$suffix}",
                'icon' => 'heroicon-m-arrow-trending-up',
                'color' => 'success',
            ];
        }

        if ($delta < 0) {
            return [
                'text' => "{$formatted} {$suffix}",
                'icon' => 'heroicon-m-arrow-trending-down',
                'color' => 'danger',
            ];
        }

        return [
            'text' => "Sin cambio {$suffix}",
            'icon' => 'heroicon-m-minus',
            'color' => 'gray',
        ];
    }

    /**
     * @param  array<string, mixed>  $metrics
     * @return array{text: string, icon: string, color: string}
     */
    private function incomeDeltaDescription(array $metrics, mixed $currency): array
    {
        $delta = $metrics['net_income_delta'];
        $percent = $metrics['net_income_delta_percent'];

        if ($delta === null) {
            return [
                'text' => $metrics['has_issued'] ? 'Último presupuesto emitido' : 'Sin presupuestos emitidos',
                'icon' => 'heroicon-m-minus',
                'color' => 'gray',
            ];
        }

        $sign = $delta > 0 ? '+' : '';
        $money = $sign.MoneyFormatter::format($delta, $currency);
        $percentText = $percent !== null
            ? ' ('.($percent > 0 ? '+' : '').$percent.'%)'
            : '';

        if ($delta > 0) {
            return [
                'text' => "Subiste {$money}{$percentText}",
                'icon' => 'heroicon-m-arrow-trending-up',
                'color' => 'success',
            ];
        }

        if ($delta < 0) {
            return [
                'text' => "Bajaste {$money}{$percentText}",
                'icon' => 'heroicon-m-arrow-trending-down',
                'color' => 'danger',
            ];
        }

        return [
            'text' => 'Sin cambio vs período previo',
            'icon' => 'heroicon-m-minus',
            'color' => 'gray',
        ];
    }
}
