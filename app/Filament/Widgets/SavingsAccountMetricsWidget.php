<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\InteractsWithSelectedSavingAccount;
use App\Filament\Widgets\Concerns\InteractsWithSavingsProjectionCadence;
use App\Filament\Widgets\Concerns\OnlyOnSavingAccountsList;
use App\Services\Savings\SavingsLedgerService;
use App\Support\MoneyFormatter;
use Filament\Widgets\Widget;
use Livewire\Attributes\On;

class SavingsAccountMetricsWidget extends Widget
{
    use InteractsWithSelectedSavingAccount;
    use InteractsWithSavingsProjectionCadence;
    use OnlyOnSavingAccountsList;

    protected static bool $isDiscovered = false;

    protected string $view = 'filament.widgets.savings-account-metrics';

    protected int | string | array $columnSpan = 'full';

    protected static ?int $sort = 1;

    public function boot(): void
    {
        $this->bootInteractsWithSelectedSavingAccount();
        $this->bootInteractsWithSavingsProjectionCadence();
    }

    #[On('savings-account-selected')]
    public function refreshMetrics(?int $accountId = null): void
    {
        $this->refreshSelectedAccount($accountId);
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $accounts = $this->savingsAccountOptions();
        $account = $this->selectedSavingsAccount();

        if ($account === null) {
            return [
                'accounts' => $accounts,
                'metrics' => null,
            ];
        }

        $metrics = app(SavingsLedgerService::class)->metricsForAccount(
            $account,
            $this->selectedProjectionCadence(),
        );
        $insights = $metrics['insights'];
        $currency = $metrics['currency'];

        return [
            'accounts' => $accounts,
            'projection_cadence_options' => $this->projectionCadenceOptions(),
            'projection_cadence' => $this->projectionCadence,
            'metrics' => [
                ...$metrics,
                'balance_formatted' => MoneyFormatter::format($metrics['balance'], $currency),
                'goal_formatted' => $metrics['goal_amount'] !== null
                    ? MoneyFormatter::format($metrics['goal_amount'], $currency)
                    : null,
                'remaining_formatted' => $metrics['goal_remaining'] !== null
                    ? MoneyFormatter::format($metrics['goal_remaining'], $currency)
                    : null,
                'pending_formatted' => MoneyFormatter::format($metrics['pending_replenishment'], $currency),
                'period_target_formatted' => $metrics['period_target'] !== null
                    ? MoneyFormatter::format($metrics['period_target'], $currency)
                    : null,
                'period_deposits_formatted' => MoneyFormatter::format($insights['period_deposits'], $currency),
                'period_remaining_formatted' => $insights['period_remaining'] !== null
                    ? MoneyFormatter::format($insights['period_remaining'], $currency)
                    : null,
                'projection_label' => $insights['projection_label'],
                'projection_detail' => $insights['goal_projection']['label_detail'] ?? $insights['projection_label'],
                'projection_date' => $insights['goal_projection']['estimated_date'] ?? null,
                'projection_frequency' => $insights['goal_projection']['frequency_adverb'] ?? null,
                'projection_cadence_label' => $insights['goal_projection']['cadence_label'] ?? null,
                'health_score' => $insights['health_score'],
                'health_label' => $insights['health_label'],
                'period_label' => $insights['period_label'],
                'period_progress_percent' => $insights['period_progress_percent'],
                'pace_status' => $insights['pace_status'],
                'net_flow_formatted' => MoneyFormatter::formatSigned($insights['net_flow'], $currency),
                'streak_months' => $insights['streak_months'],
            ],
        ];
    }
}
