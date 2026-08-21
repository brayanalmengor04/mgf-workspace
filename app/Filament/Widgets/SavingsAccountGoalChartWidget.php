<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\InteractsWithSelectedSavingAccount;
use App\Filament\Widgets\Concerns\InteractsWithSavingsProjectionCadence;
use App\Filament\Widgets\Concerns\OnlyOnSavingAccountsList;
use App\Services\Savings\SavingsLedgerService;
use App\Support\MoneyFormatter;
use Filament\Widgets\ChartWidget;
use Illuminate\Contracts\Support\Htmlable;
use Livewire\Attributes\On;

class SavingsAccountGoalChartWidget extends ChartWidget
{
    use InteractsWithSelectedSavingAccount;
    use InteractsWithSavingsProjectionCadence;
    use OnlyOnSavingAccountsList;

    protected static bool $isDiscovered = false;

    protected string $view = 'filament.widgets.savings-account-goal-chart';

    protected ?string $heading = 'Avance hacia tu meta';

    protected ?string $maxHeight = '380px';

    protected int | string | array $columnSpan = 'full';

    protected static ?int $sort = 2;

    public ?float $centerPercent = null;

    public function boot(): void
    {
        $this->bootInteractsWithSelectedSavingAccount();
        $this->bootInteractsWithSavingsProjectionCadence();
    }

    #[On('savings-projection-cadence-changed')]
    public function refreshGoalChartCadence(?string $cadence = null): void
    {
        $this->refreshProjectionCadence($cadence);
        $this->cachedData = null;
    }

    #[On('savings-account-selected')]
    public function refreshGoalChart(?int $accountId = null): void
    {
        if ($accountId !== null) {
            $this->selectedAccountId = $accountId;
        }

        $this->cachedData = null;
    }

    public function getDescription(): string | Htmlable | null
    {
        $account = $this->selectedSavingsAccount();

        if ($account === null) {
            return 'Selecciona una cuenta para ver el avance.';
        }

        $metrics = app(SavingsLedgerService::class)->metricsForAccount(
            $account,
            $this->selectedProjectionCadence(),
        );

        if (! $metrics['has_goal']) {
            return 'Configura una meta en esta cuenta para ver el gráfico.';
        }

        return MoneyFormatter::format($metrics['balance'], $metrics['currency'])
            .' de '.MoneyFormatter::format($metrics['goal_amount'], $metrics['currency'])
            .' · Te falta '.MoneyFormatter::format($metrics['goal_remaining'], $metrics['currency']);
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getData(): array
    {
        $account = $this->selectedSavingsAccount();

        if ($account === null) {
            return $this->emptyChart('Sin cuenta');
        }

        $metrics = app(SavingsLedgerService::class)->metricsForAccount(
            $account,
            $this->selectedProjectionCadence(),
        );

        if (! $metrics['has_goal']) {
            return $this->emptyChart('Sin meta');
        }

        $progress = (float) $metrics['goal_progress_percent'];
        $missing = (float) $metrics['goal_missing_percent'];
        $balance = (float) $metrics['balance'];
        $remaining = (float) $metrics['goal_remaining'];

        if ($remaining <= 0) {
            $this->centerPercent = 100.0;

            return [
                'labels' => ['Meta cumplida (100%)'],
                'datasets' => [[
                    'data' => [1],
                    'backgroundColor' => ['#10b981'],
                    'borderWidth' => 0,
                ]],
            ];
        }

        $this->centerPercent = $progress;

        return [
            'labels' => [
                'Lo que tienes ('.number_format($progress, 1).'%)',
                'Te falta ('.number_format($missing, 1).'%)',
            ],
            'datasets' => [[
                'data' => [$balance, $remaining],
                'backgroundColor' => ['#10b981', '#e5e7eb'],
                'borderWidth' => 0,
            ]],
        ];
    }

    protected function getOptions(): ?array
    {
        return [
            'plugins' => [
                'legend' => [
                    'position' => 'bottom',
                ],
            ],
            'cutout' => '72%',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $account = $this->selectedSavingsAccount();

        if ($account === null) {
            return [
                'accountName' => null,
                'goalLabel' => null,
                'balanceFormatted' => null,
                'goalFormatted' => null,
                'remainingFormatted' => null,
                'missingPercent' => null,
            ];
        }

        $metrics = app(SavingsLedgerService::class)->metricsForAccount(
            $account,
            $this->selectedProjectionCadence(),
        );
        $currency = $metrics['currency'];

        return [
            'accountName' => $metrics['account_name'],
            'goalLabel' => $metrics['goal_label'],
            'balanceFormatted' => MoneyFormatter::format($metrics['balance'], $currency),
            'goalFormatted' => $metrics['goal_amount'] !== null
                ? MoneyFormatter::format($metrics['goal_amount'], $currency)
                : null,
            'remainingFormatted' => $metrics['goal_remaining'] !== null
                ? MoneyFormatter::format($metrics['goal_remaining'], $currency)
                : null,
            'missingPercent' => $metrics['goal_missing_percent'],
            'goalProjection' => $metrics['insights']['goal_projection'],
        ];
    }

    private function emptyChart(string $label): array
    {
        $this->centerPercent = null;

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
