<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\InteractsWithSelectedSavingAccount;
use App\Filament\Widgets\Concerns\InteractsWithSavingsProjectionCadence;
use App\Filament\Widgets\Concerns\OnlyOnSavingAccountsList;
use App\Services\Savings\SavingsLedgerService;
use App\Support\MoneyFormatter;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Livewire\Attributes\On;

class SavingsAccountInsightsWidget extends StatsOverviewWidget
{
    use InteractsWithSelectedSavingAccount;
    use InteractsWithSavingsProjectionCadence;
    use OnlyOnSavingAccountsList;

    protected static bool $isDiscovered = false;

    protected ?string $heading = 'Inteligencia de la cuenta';

    protected int | string | array $columnSpan = 'full';

    protected static ?int $sort = 3;

    public function boot(): void
    {
        $this->bootInteractsWithSelectedSavingAccount();
        $this->bootInteractsWithSavingsProjectionCadence();
    }

    #[On('savings-account-selected')]
    public function refreshInsights(?int $accountId = null): void
    {
        $this->refreshSelectedAccount($accountId);
    }

    #[On('savings-projection-cadence-changed')]
    public function refreshInsightsCadence(?string $cadence = null): void
    {
        $this->refreshProjectionCadence($cadence);
    }

    protected function getStats(): array
    {
        $account = $this->selectedSavingsAccount();

        if ($account === null) {
            return [];
        }

        $metrics = app(SavingsLedgerService::class)->metricsForAccount(
            $account,
            $this->selectedProjectionCadence(),
        );
        $insights = $metrics['insights'];
        $currency = $metrics['currency'];

        $paceDescription = match ($insights['pace_status']) {
            'ahead' => 'Adelantado '.MoneyFormatter::formatSigned($insights['pace_delta'], $currency).' este '.$insights['period_label'],
            'behind' => 'Faltan '.MoneyFormatter::format(abs($insights['pace_delta']), $currency).' para la meta '.$insights['period_label'],
            'on_track' => 'Justo en la meta '.$insights['period_label'],
            default => $metrics['period_target'] !== null
                ? 'Depositaste '.MoneyFormatter::format($insights['period_deposits'], $currency).' este '.$insights['period_label']
                : 'Define meta por período para medir ritmo',
        };

        $paceColor = match ($insights['pace_status']) {
            'ahead' => 'success',
            'on_track' => 'info',
            'behind' => 'warning',
            default => 'gray',
        };

        $lastDepositDescription = match (true) {
            $insights['days_since_last_deposit'] === null => 'Sin depósitos registrados',
            $insights['days_since_last_deposit'] === 0 => 'Depositaste hoy',
            $insights['days_since_last_deposit'] === 1 => 'Depositaste ayer',
            default => 'Hace '.(int) $insights['days_since_last_deposit'].' días',
        };

        $goalProjection = $metrics['insights']['goal_projection'];

        $stats = [
            Stat::make('Proyección de meta', match ($goalProjection['confidence'] ?? 'none') {
                'complete' => 'Cumplida',
                default => $goalProjection['estimated_date'] ?? '—',
            })
                ->description(($goalProjection['cadence_label'] ?? 'Quincenal').' · '.$goalProjection['label_detail'])
                ->descriptionIcon('heroicon-m-clock')
                ->color(match ($goalProjection['confidence'] ?? 'none') {
                    'complete', 'high' => 'success',
                    'medium' => 'info',
                    'low' => 'warning',
                    default => 'gray',
                }),

            Stat::make('Salud del ahorro', $insights['health_score'].'/100')
                ->description($insights['health_label'])
                ->descriptionIcon('heroicon-m-heart')
                ->color($insights['health_score'] >= 65 ? 'success' : ($insights['health_score'] >= 40 ? 'warning' : 'danger'))
                ->chart($insights['balance_sparkline']),

            Stat::make('Ritmo del período', $insights['period_progress_percent'] !== null
                ? $insights['period_progress_percent'].'%'
                : '—')
                ->description($paceDescription)
                ->descriptionIcon(match ($insights['pace_status']) {
                    'ahead' => 'heroicon-m-arrow-trending-up',
                    'behind' => 'heroicon-m-arrow-trending-down',
                    default => 'heroicon-m-calendar-days',
                })
                ->color($paceColor)
                ->chart($insights['net_sparkline']),

            Stat::make('Flujo neto', MoneyFormatter::formatSigned($insights['net_flow'], $currency))
                ->description('Entradas '.MoneyFormatter::format($insights['total_in'], $currency)
                    .' · Retiros '.MoneyFormatter::format($insights['total_out'], $currency))
                ->descriptionIcon('heroicon-m-arrows-right-left')
                ->color($insights['net_flow'] >= 0 ? 'success' : 'danger'),

            Stat::make('Promedio depósito', $insights['deposit_count'] > 0
                ? MoneyFormatter::format($insights['avg_deposit'], $currency)
                : '—')
                ->description($insights['deposit_count'].' depósito(s) · Mejor mes '.MoneyFormatter::format($insights['best_month_amount'], $currency))
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('info'),

            Stat::make('Constancia', $insights['streak_months'].' mes(es)')
                ->description($lastDepositDescription
                    .($insights['last_movement_date'] ? ' · Último: '.$insights['last_movement_type'].' '.$insights['last_movement_date'] : ''))
                ->descriptionIcon('heroicon-m-fire')
                ->color($insights['streak_months'] >= 2 ? 'success' : 'gray'),
        ];

        if ($metrics['pending_replenishment'] > 0) {
            $stats[] = Stat::make('Por reponer', MoneyFormatter::format($metrics['pending_replenishment'], $currency))
                ->description(number_format((float) $metrics['replenishment_progress_percent'], 1).'% repuesto · Prioridad alta')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('warning');
        }

        return $stats;
    }
}
