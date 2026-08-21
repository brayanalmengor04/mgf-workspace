<?php

namespace App\Services\Savings;

use App\Enums\BudgetPeriod;
use App\Enums\SavingsProjectionCadence;
use App\Enums\SavingsTransactionType;
use App\Models\BudgetPlan;
use App\Models\BudgetPlanItem;
use App\Models\SavingsAccount;
use App\Models\SavingsTransaction;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class SavingsLedgerService
{
    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    public function currentPeriodRange(BudgetPeriod $period, ?Carbon $reference = null): array
    {
        $now = ($reference ?? now())->copy()->startOfDay();

        return match ($period) {
            BudgetPeriod::Weekly => [
                $now->copy()->startOfWeek(),
                $now->copy()->endOfWeek(),
            ],
            BudgetPeriod::Biweekly => $now->day <= 15
                ? [$now->copy()->startOfMonth(), $now->copy()->day(15)->endOfDay()]
                : [$now->copy()->day(16)->startOfDay(), $now->copy()->endOfMonth()],
            BudgetPeriod::Monthly, BudgetPeriod::Custom => [
                $now->copy()->startOfMonth(),
                $now->copy()->endOfMonth(),
            ],
        };
    }

    public function depositsInCurrentPeriod(SavingsAccount $account): float
    {
        $period = $account->period instanceof BudgetPeriod
            ? $account->period
            : BudgetPeriod::tryFrom((string) ($account->period ?? ''));

        if ($period === null) {
            return 0.0;
        }

        [$start, $end] = $this->currentPeriodRange($period);

        $amount = $account->transactions()
            ->whereIn('type', [SavingsTransactionType::Opening, SavingsTransactionType::Deposit])
            ->whereNull('related_withdrawal_id')
            ->whereDate('occurred_at', '>=', $start->toDateString())
            ->whereDate('occurred_at', '<=', $end->toDateString())
            ->sum('amount');

        return round((float) $amount, 2);
    }

    public function recordOpening(
        SavingsAccount $account,
        float $amount,
        ?string $notes = null,
        ?Carbon $occurredAt = null,
    ): SavingsTransaction {
        if ($amount <= 0) {
            throw new InvalidArgumentException('El saldo inicial debe ser mayor que cero.');
        }

        if ($account->transactions()->where('type', SavingsTransactionType::Opening)->exists()) {
            throw new InvalidArgumentException('Esta cuenta ya tiene un movimiento de apertura.');
        }

        return $this->createTransaction(
            account: $account,
            type: SavingsTransactionType::Opening,
            amount: $amount,
            notes: $notes,
            occurredAt: $occurredAt,
        );
    }

    public function recordDeposit(
        SavingsAccount $account,
        float $amount,
        ?string $notes = null,
        ?Carbon $occurredAt = null,
        ?BudgetPlanItem $budgetPlanItem = null,
    ): SavingsTransaction {
        if ($amount <= 0) {
            throw new InvalidArgumentException('El depósito debe ser mayor que cero.');
        }

        return $this->createTransaction(
            account: $account,
            type: SavingsTransactionType::Deposit,
            amount: $amount,
            notes: $notes,
            occurredAt: $occurredAt,
            budgetPlanItem: $budgetPlanItem,
        );
    }

    public function recordWithdrawal(
        SavingsAccount $account,
        float $amount,
        ?string $notes = null,
        ?Carbon $occurredAt = null,
    ): SavingsTransaction {
        if ($amount <= 0) {
            throw new InvalidArgumentException('El retiro debe ser mayor que cero.');
        }

        $this->assertSufficientBalance($account, $amount);

        return $this->createTransaction(
            account: $account,
            type: SavingsTransactionType::Withdrawal,
            amount: $amount,
            notes: $notes,
            occurredAt: $occurredAt,
        );
    }

    public function recordReplenishment(
        SavingsAccount $account,
        SavingsTransaction $withdrawal,
        float $amount,
        ?string $notes = null,
        ?Carbon $occurredAt = null,
    ): SavingsTransaction {
        if ($withdrawal->type !== SavingsTransactionType::Withdrawal) {
            throw new InvalidArgumentException('Solo se puede reponer un retiro.');
        }

        if ($withdrawal->savings_account_id !== $account->id) {
            throw new InvalidArgumentException('El retiro no pertenece a esta cuenta.');
        }

        if ($amount <= 0) {
            throw new InvalidArgumentException('La reposición debe ser mayor que cero.');
        }

        $alreadyReplenished = (float) $withdrawal->replenishments()->sum('amount');
        $remaining = (float) $withdrawal->amount - $alreadyReplenished;

        if ($amount > $remaining + 0.0001) {
            throw new InvalidArgumentException('La reposición supera el monto pendiente del retiro.');
        }

        return $this->createTransaction(
            account: $account,
            type: SavingsTransactionType::Deposit,
            amount: $amount,
            notes: $notes ?? 'Reposición de retiro',
            occurredAt: $occurredAt,
            relatedWithdrawal: $withdrawal,
        );
    }

    public function syncDepositFromBudgetItem(BudgetPlanItem $item): ?SavingsTransaction
    {
        if (! $item->is_paid || $item->savings_account_id === null || (float) $item->amount <= 0) {
            return null;
        }

        if (SavingsTransaction::query()->where('budget_plan_item_id', $item->id)->exists()) {
            return null;
        }

        $account = SavingsAccount::query()->find($item->savings_account_id);

        if ($account === null) {
            return null;
        }

        return $this->recordDeposit(
            account: $account,
            amount: (float) $item->amount,
            notes: "Desde presupuesto: {$item->concept}",
            occurredAt: $item->paid_at ? Carbon::parse($item->paid_at) : now(),
            budgetPlanItem: $item,
        );
    }

    public function syncDepositsFromBudgetPlan(BudgetPlan $plan): void
    {
        $plan->loadMissing('items');

        foreach ($plan->items as $item) {
            $this->syncDepositFromBudgetItem($item);
        }
    }

    /**
     * @return array{
     *     total_balance: float,
     *     pending_replenishment: float,
     *     active_accounts: int
     * }
     */
    public function summaryForUser(User $user): array
    {
        $accounts = SavingsAccount::query()
            ->forUser($user)
            ->active()
            ->get();

        return [
            'total_balance' => round((float) $accounts->sum('current_balance'), 2),
            'pending_replenishment' => round((float) $accounts->sum('pending_replenishment'), 2),
            'active_accounts' => $accounts->count(),
        ];
    }

    /**
     * @return array{
     *     summary: array{total_balance: float, pending_replenishment: float, active_accounts: int},
     *     totals: array{deposits: float, withdrawals: float, replenishments: float, openings: float, net_flow: float},
     *     breakdown: array{labels: array<int, string>, values: array<int, float>},
     *     gap: array{balance: float, pending: float, target_if_replenished: float},
     *     trend: array{
     *         labels: array<int, string>,
     *         balance: array<int, float>,
     *         deposits: array<int, float>,
     *         withdrawals: array<int, float>
     *     },
     *     accounts: array<int, array{
     *         id: int,
     *         name: string,
     *         current_balance: float,
     *         pending_replenishment: float,
     *         target_per_period: float|null,
     *         period: string|null,
     *         goal_amount: float|null,
     *         goal_progress_percent: float|null,
     *         goal_remaining: float|null,
     *         period_deposits: float,
     *         period_progress_percent: float|null,
     *         period_remaining: float|null,
     *         pending_withdrawals: array<int, array{amount: float, pending: float, occurred_at: string, notes: string|null}>
     *     }>,
     *     goals: array{
     *         has_goals: bool,
     *         total_goal: float,
     *         total_balance: float,
     *         progress_percent: float|null,
     *         remaining: float
     *     },
     *     period_targets: array{
     *         has_targets: bool,
     *         total_target: float,
     *         total_deposited: float,
     *         progress_percent: float|null,
     *         remaining: float
     *     },
     *     replenishment: array{
     *         pending: float,
     *         replenished: float,
     *         total_withdrawn: float,
     *         progress_percent: float|null
     *     }
     * }
     */
    public function analyticsForUser(User $user): array
    {
        $accounts = SavingsAccount::query()
            ->forUser($user)
            ->active()
            ->with(['transactions' => fn ($query) => $query->orderBy('occurred_at')->orderBy('id')])
            ->orderBy('name')
            ->get();

        $summary = $this->summaryForUser($user);

        $totals = [
            'deposits' => 0.0,
            'withdrawals' => 0.0,
            'replenishments' => 0.0,
            'openings' => 0.0,
            'net_flow' => 0.0,
        ];

        $monthly = [];
        $allTransactions = collect();

        foreach ($accounts as $account) {
            foreach ($account->transactions as $transaction) {
                $allTransactions->push($transaction);
                $monthKey = $transaction->occurred_at->format('Y-m');

                if (! isset($monthly[$monthKey])) {
                    $monthly[$monthKey] = [
                        'label' => $transaction->occurred_at->translatedFormat('M Y'),
                        'deposits' => 0.0,
                        'withdrawals' => 0.0,
                    ];
                }

                $amount = (float) $transaction->amount;

                match ($transaction->type) {
                    SavingsTransactionType::Opening => $totals['openings'] += $amount,
                    SavingsTransactionType::Deposit => $transaction->related_withdrawal_id !== null
                        ? $totals['replenishments'] += $amount
                        : $totals['deposits'] += $amount,
                    SavingsTransactionType::Withdrawal => $totals['withdrawals'] += $amount,
                    SavingsTransactionType::Adjustment => null,
                };

                if (in_array($transaction->type, [SavingsTransactionType::Opening, SavingsTransactionType::Deposit], true)) {
                    $monthly[$monthKey]['deposits'] += $amount;
                }

                if ($transaction->type === SavingsTransactionType::Withdrawal) {
                    $monthly[$monthKey]['withdrawals'] += $amount;
                }
            }
        }

        $totals['net_flow'] = round(
            $totals['openings'] + $totals['deposits'] + $totals['replenishments'] - $totals['withdrawals'],
            2,
        );

        ksort($monthly);
        $monthly = array_slice($monthly, -12, 12, true);

        $balanceByMonth = [];
        $runningBalance = 0.0;

        foreach ($allTransactions->sortBy([
            ['occurred_at', 'asc'],
            ['id', 'asc'],
        ]) as $transaction) {
            $runningBalance += $transaction->signedAmount();
            $balanceByMonth[$transaction->occurred_at->format('Y-m')] = round($runningBalance, 2);
        }

        $trend = [
            'labels' => [],
            'balance' => [],
            'deposits' => [],
            'withdrawals' => [],
        ];

        $lastBalance = 0.0;

        foreach ($monthly as $monthKey => $data) {
            $trend['labels'][] = $data['label'];
            $trend['deposits'][] = round($data['deposits'], 2);
            $trend['withdrawals'][] = round($data['withdrawals'], 2);
            $lastBalance = $balanceByMonth[$monthKey] ?? $lastBalance;
            $trend['balance'][] = $lastBalance;
        }

        $accountDetails = $accounts->map(function (SavingsAccount $account): array {
            $period = $account->period instanceof BudgetPeriod
                ? $account->period->label()
                : (BudgetPeriod::tryFrom((string) ($account->period ?? ''))?->label());

            $replenishedByWithdrawal = $account->transactions
                ->where('type', SavingsTransactionType::Deposit)
                ->whereNotNull('related_withdrawal_id')
                ->groupBy('related_withdrawal_id')
                ->map(fn ($group) => (float) $group->sum('amount'));

            $goalAmount = $account->goal_amount !== null ? (float) $account->goal_amount : null;
            $goalProgress = ($goalAmount !== null && $goalAmount > 0)
                ? round(min(100, ((float) $account->current_balance / $goalAmount) * 100), 1)
                : null;
            $goalRemaining = ($goalAmount !== null && $goalAmount > 0)
                ? round(max(0, $goalAmount - (float) $account->current_balance), 2)
                : null;

            $targetPerPeriod = $account->target_per_period !== null ? (float) $account->target_per_period : null;
            $periodDeposits = $this->depositsInCurrentPeriod($account);
            $periodProgress = ($targetPerPeriod !== null && $targetPerPeriod > 0)
                ? round(min(100, ($periodDeposits / $targetPerPeriod) * 100), 1)
                : null;
            $periodRemaining = ($targetPerPeriod !== null && $targetPerPeriod > 0)
                ? round(max(0, $targetPerPeriod - $periodDeposits), 2)
                : null;

            $pendingWithdrawals = $account->transactions
                ->where('type', SavingsTransactionType::Withdrawal)
                ->map(function (SavingsTransaction $withdrawal) use ($replenishedByWithdrawal): ?array {
                    $replenished = (float) ($replenishedByWithdrawal->get($withdrawal->id) ?? 0);
                    $pending = round((float) $withdrawal->amount - $replenished, 2);

                    if ($pending <= 0) {
                        return null;
                    }

                    return [
                        'amount' => (float) $withdrawal->amount,
                        'pending' => $pending,
                        'occurred_at' => $withdrawal->occurred_at->format('Y-m-d'),
                        'notes' => $withdrawal->notes,
                    ];
                })
                ->filter()
                ->values()
                ->all();

            return [
                'id' => $account->id,
                'name' => $account->name,
                'current_balance' => (float) $account->current_balance,
                'pending_replenishment' => (float) $account->pending_replenishment,
                'target_per_period' => $account->target_per_period !== null ? (float) $account->target_per_period : null,
                'period' => $period,
                'goal_amount' => $goalAmount,
                'goal_progress_percent' => $goalProgress,
                'goal_remaining' => $goalRemaining,
                'period_deposits' => $periodDeposits,
                'period_progress_percent' => $periodProgress,
                'period_remaining' => $periodRemaining,
                'pending_withdrawals' => $pendingWithdrawals,
            ];
        })->values()->all();

        $moneyIn = round($totals['openings'] + $totals['deposits'] + $totals['replenishments'], 2);

        $goalAccounts = $accounts->filter(fn (SavingsAccount $account): bool => $account->goal_amount !== null && (float) $account->goal_amount > 0);
        $totalGoal = round((float) $goalAccounts->sum('goal_amount'), 2);
        $goalBalance = round((float) $goalAccounts->sum('current_balance'), 2);
        $goalRemaining = round(max(0, $totalGoal - $goalBalance), 2);
        $goalProgress = $totalGoal > 0
            ? round(min(100, ($goalBalance / $totalGoal) * 100), 1)
            : null;

        $targetAccounts = $accounts->filter(fn (SavingsAccount $account): bool => $account->target_per_period !== null && (float) $account->target_per_period > 0);
        $totalPeriodTarget = round((float) $targetAccounts->sum('target_per_period'), 2);
        $totalPeriodDeposited = round($targetAccounts->sum(fn (SavingsAccount $account): float => $this->depositsInCurrentPeriod($account)), 2);
        $periodRemaining = round(max(0, $totalPeriodTarget - $totalPeriodDeposited), 2);
        $periodProgress = $totalPeriodTarget > 0
            ? round(min(100, ($totalPeriodDeposited / $totalPeriodTarget) * 100), 1)
            : null;

        $replenished = round($totals['replenishments'], 2);
        $pendingReplenishment = round($summary['pending_replenishment'], 2);
        $replenishmentBase = $replenished + $pendingReplenishment;
        $replenishmentProgress = $replenishmentBase > 0
            ? round(min(100, ($replenished / $replenishmentBase) * 100), 1)
            : null;

        return [
            'summary' => $summary,
            'totals' => array_map(fn (float $value): float => round($value, 2), $totals),
            'breakdown' => [
                'labels' => ['Entradas', 'Retiros', 'Por reponer'],
                'values' => [
                    $moneyIn,
                    round($totals['withdrawals'], 2),
                    round($summary['pending_replenishment'], 2),
                ],
            ],
            'gap' => [
                'balance' => round($summary['total_balance'], 2),
                'pending' => round($summary['pending_replenishment'], 2),
                'target_if_replenished' => round($summary['total_balance'] + $summary['pending_replenishment'], 2),
            ],
            'trend' => $trend,
            'accounts' => $accountDetails,
            'goals' => [
                'has_goals' => $goalAccounts->isNotEmpty(),
                'total_goal' => $totalGoal,
                'total_balance' => $goalBalance,
                'progress_percent' => $goalProgress,
                'remaining' => $goalRemaining,
            ],
            'period_targets' => [
                'has_targets' => $targetAccounts->isNotEmpty(),
                'total_target' => $totalPeriodTarget,
                'total_deposited' => $totalPeriodDeposited,
                'progress_percent' => $periodProgress,
                'remaining' => $periodRemaining,
            ],
            'replenishment' => [
                'pending' => $pendingReplenishment,
                'replenished' => $replenished,
                'total_withdrawn' => round($totals['withdrawals'], 2),
                'progress_percent' => $replenishmentProgress,
            ],
        ];
    }

    /**
     * @return array{
     *     account_id: int,
     *     account_name: string,
     *     balance: float,
     *     goal_amount: float|null,
     *     goal_label: string,
     *     goal_remaining: float|null,
     *     goal_progress_percent: float|null,
     *     goal_missing_percent: float|null,
     *     has_goal: bool,
     *     pending_replenishment: float,
     *     replenishment_replenished: float,
     *     replenishment_progress_percent: float|null,
     *     currency: \App\Enums\QuoteCurrency
     * }
     */
    public function metricsForAccount(
        SavingsAccount $account,
        ?SavingsProjectionCadence $projectionCadence = null,
    ): array {
        $account->loadMissing('transactions');

        $balance = round((float) $account->current_balance, 2);
        $totalGoal = ($account->goal_amount !== null && (float) $account->goal_amount > 0)
            ? round((float) $account->goal_amount, 2)
            : null;
        $periodTarget = ($account->target_per_period !== null && (float) $account->target_per_period > 0)
            ? round((float) $account->target_per_period, 2)
            : null;

        $goalAmount = $totalGoal ?? $periodTarget;
        $goalLabel = $totalGoal !== null ? 'Meta total' : 'Meta del período';

        $goalRemaining = $goalAmount !== null ? round(max(0, $goalAmount - $balance), 2) : null;
        $goalProgress = ($goalAmount !== null && $goalAmount > 0)
            ? round(min(100, ($balance / $goalAmount) * 100), 1)
            : null;
        $goalMissingPercent = $goalProgress !== null
            ? round(max(0, 100 - $goalProgress), 1)
            : null;

        $replenished = round((float) $account->transactions
            ->where('type', SavingsTransactionType::Deposit)
            ->whereNotNull('related_withdrawal_id')
            ->sum('amount'), 2);

        $pending = round((float) $account->pending_replenishment, 2);
        $replenishmentBase = $replenished + $pending;
        $replenishmentProgress = $replenishmentBase > 0
            ? round(min(100, ($replenished / $replenishmentBase) * 100), 1)
            : null;

        $periodEnum = $account->period instanceof BudgetPeriod
            ? $account->period
            : BudgetPeriod::tryFrom((string) ($account->period ?? ''));

        $periodDeposits = $this->depositsInCurrentPeriod($account);
        $periodProgress = ($periodTarget !== null && $periodTarget > 0)
            ? round(min(100, ($periodDeposits / $periodTarget) * 100), 1)
            : null;
        $periodRemaining = ($periodTarget !== null && $periodTarget > 0)
            ? round(max(0, $periodTarget - $periodDeposits), 2)
            : null;
        $paceDelta = ($periodTarget !== null && $periodTarget > 0)
            ? round($periodDeposits - $periodTarget, 2)
            : null;
        $paceStatus = match (true) {
            $paceDelta === null => 'none',
            $paceDelta > 0 => 'ahead',
            $paceDelta < 0 => 'behind',
            default => 'on_track',
        };

        $regularDeposits = $account->transactions->filter(
            fn (SavingsTransaction $transaction): bool => in_array($transaction->type, [SavingsTransactionType::Opening, SavingsTransactionType::Deposit], true)
                && $transaction->related_withdrawal_id === null
        );

        $totalIn = round((float) $regularDeposits->sum('amount') + $replenished, 2);
        $totalOut = round((float) $account->transactions
            ->where('type', SavingsTransactionType::Withdrawal)
            ->sum('amount'), 2);
        $netFlow = round($totalIn - $totalOut, 2);
        $depositCount = $regularDeposits->count();
        $avgDeposit = $depositCount > 0
            ? round((float) $regularDeposits->avg('amount'), 2)
            : 0.0;

        $lastMovement = $account->transactions->sortByDesc('occurred_at')->first();
        $lastDeposit = $regularDeposits->sortByDesc('occurred_at')->first();
        $daysSinceLastDeposit = $lastDeposit !== null
            ? (int) $lastDeposit->occurred_at->startOfDay()->diffInDays(now()->startOfDay())
            : null;

        $threeMonthsAgo = now()->subMonths(3)->startOfDay();
        $recentDepositsTotal = round((float) $regularDeposits
            ->filter(fn (SavingsTransaction $transaction): bool => $transaction->occurred_at->greaterThanOrEqualTo($threeMonthsAgo))
            ->sum('amount'), 2);
        $avgMonthlyDeposit = round($recentDepositsTotal / 3, 2);

        $goalProjection = $this->depositCadenceProjection(
            regularDeposits: $regularDeposits,
            totalGoal: $totalGoal,
            balance: $balance,
            periodTarget: $periodTarget,
            configuredPeriod: $periodEnum,
            avgMonthlyDeposit: $avgMonthlyDeposit,
            currency: $account->currency instanceof \App\Enums\QuoteCurrency
                ? $account->currency
                : \App\Enums\QuoteCurrency::resolve($account->currency),
            cadence: $projectionCadence ?? SavingsProjectionCadence::Biweekly,
        );

        $projectionLabel = $goalProjection['label_short'];

        $periodLabel = $periodEnum?->label() ?? 'período';

        $healthScore = (int) round(min(100, max(0,
            ($goalProgress ?? 0) * 0.45
            + ($periodProgress ?? ($depositCount > 0 ? 50 : 0)) * 0.25
            + ($pending <= 0 ? 20 : max(0, 20 - min(20, $pending / max(1, $balance) * 20)))
            + (($daysSinceLastDeposit !== null && $daysSinceLastDeposit <= 30) ? 10 : 0)
        )));

        $healthLabel = match (true) {
            $healthScore >= 85 => 'Excelente',
            $healthScore >= 65 => 'En camino',
            $healthScore >= 40 => 'Puede mejorar',
            default => 'Necesita impulso',
        };

        $trend = $this->trendForAccount($account, 6);
        $balanceSparkline = $trend['balance'] !== [] ? $trend['balance'] : null;
        $netSparkline = [];

        foreach ($trend['labels'] as $index => $label) {
            $netSparkline[] = round(
                (float) ($trend['deposits'][$index] ?? 0) - (float) ($trend['withdrawals'][$index] ?? 0),
                2,
            );
        }

        $monthlyDeposits = [];

        foreach ($account->transactions->sortBy('occurred_at') as $transaction) {
            if (! in_array($transaction->type, [SavingsTransactionType::Opening, SavingsTransactionType::Deposit], true)
                || $transaction->related_withdrawal_id !== null) {
                continue;
            }

            $key = $transaction->occurred_at->format('Y-m');
            $monthlyDeposits[$key] = ($monthlyDeposits[$key] ?? 0) + (float) $transaction->amount;
        }

        ksort($monthlyDeposits);
        $bestMonthAmount = $monthlyDeposits !== [] ? round(max($monthlyDeposits), 2) : 0.0;
        $streakMonths = 0;

        foreach (array_reverse($monthlyDeposits, true) as $amount) {
            if ($amount <= 0) {
                break;
            }

            $streakMonths++;
        }

        return [
            'account_id' => $account->id,
            'account_name' => $account->name,
            'balance' => $balance,
            'goal_amount' => $goalAmount,
            'total_goal_amount' => $totalGoal,
            'period_target' => $periodTarget,
            'goal_label' => $goalLabel,
            'goal_remaining' => $goalRemaining,
            'goal_progress_percent' => $goalProgress,
            'goal_missing_percent' => $goalMissingPercent,
            'has_goal' => $goalAmount !== null,
            'pending_replenishment' => $pending,
            'replenishment_replenished' => $replenished,
            'replenishment_progress_percent' => $replenishmentProgress,
            'currency' => $account->currency instanceof \App\Enums\QuoteCurrency
                ? $account->currency
                : \App\Enums\QuoteCurrency::resolve($account->currency),
            'insights' => [
                'period_label' => $periodLabel,
                'period_deposits' => $periodDeposits,
                'period_progress_percent' => $periodProgress,
                'period_remaining' => $periodRemaining,
                'pace_delta' => $paceDelta,
                'pace_status' => $paceStatus,
                'net_flow' => $netFlow,
                'total_in' => $totalIn,
                'total_out' => $totalOut,
                'deposit_count' => $depositCount,
                'avg_deposit' => $avgDeposit,
                'avg_monthly_deposit' => $avgMonthlyDeposit,
                'days_since_last_deposit' => $daysSinceLastDeposit,
                'last_movement_type' => $lastMovement?->type->label(),
                'last_movement_amount' => $lastMovement !== null ? round((float) $lastMovement->amount, 2) : null,
                'last_movement_date' => $lastMovement?->occurred_at->format('d/m/Y'),
                'projection_label' => $projectionLabel,
                'projection_periods' => $goalProjection['deposits_needed'],
                'goal_projection' => $goalProjection,
                'health_score' => $healthScore,
                'health_label' => $healthLabel,
                'balance_sparkline' => $balanceSparkline,
                'net_sparkline' => $netSparkline !== [] ? $netSparkline : null,
                'best_month_amount' => $bestMonthAmount,
                'streak_months' => $streakMonths,
            ],
        ];
    }

    /**
     * @return array{
     *     labels: array<int, string>,
     *     balance: array<int, float>,
     *     deposits: array<int, float>,
     *     withdrawals: array<int, float>
     * }
     */
    public function trendForAccount(SavingsAccount $account, int $limit = 12): array
    {
        $account->loadMissing(['transactions' => fn ($query) => $query->orderBy('occurred_at')->orderBy('id')]);

        $monthly = [];
        $runningBalance = 0.0;
        $balanceByMonth = [];

        foreach ($account->transactions->sortBy([
            ['occurred_at', 'asc'],
            ['id', 'asc'],
        ]) as $transaction) {
            $monthKey = $transaction->occurred_at->format('Y-m');

            if (! isset($monthly[$monthKey])) {
                $monthly[$monthKey] = [
                    'label' => $transaction->occurred_at->translatedFormat('M Y'),
                    'deposits' => 0.0,
                    'withdrawals' => 0.0,
                ];
            }

            $amount = (float) $transaction->amount;

            if (in_array($transaction->type, [SavingsTransactionType::Opening, SavingsTransactionType::Deposit], true)) {
                $monthly[$monthKey]['deposits'] += $amount;
            }

            if ($transaction->type === SavingsTransactionType::Withdrawal) {
                $monthly[$monthKey]['withdrawals'] += $amount;
            }

            $runningBalance += $transaction->signedAmount();
            $balanceByMonth[$monthKey] = round($runningBalance, 2);
        }

        ksort($monthly);
        $monthly = array_slice($monthly, -max(1, $limit), null, true);

        $trend = [
            'labels' => [],
            'balance' => [],
            'deposits' => [],
            'withdrawals' => [],
        ];

        $lastBalance = 0.0;

        foreach ($monthly as $monthKey => $data) {
            $trend['labels'][] = $data['label'];
            $trend['deposits'][] = round($data['deposits'], 2);
            $trend['withdrawals'][] = round($data['withdrawals'], 2);
            $lastBalance = $balanceByMonth[$monthKey] ?? $lastBalance;
            $trend['balance'][] = $lastBalance;
        }

        return $trend;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, SavingsTransaction>  $regularDeposits
     * @return array{
     *     confidence: 'high'|'medium'|'low'|'none'|'complete',
     *     cadence: string,
     *     cadence_label: string,
     *     frequency_adverb: string,
     *     frequency_noun: string,
     *     avg_interval_days: int,
     *     avg_deposit_amount: float,
     *     deposits_needed: int|null,
     *     estimated_days: int|null,
     *     estimated_date: string|null,
     *     label_short: string,
     *     label_detail: string
     * }
     */
    private function depositCadenceProjection(
        \Illuminate\Support\Collection $regularDeposits,
        ?float $totalGoal,
        float $balance,
        ?float $periodTarget,
        ?BudgetPeriod $configuredPeriod,
        float $avgMonthlyDeposit,
        \App\Enums\QuoteCurrency $currency,
        SavingsProjectionCadence $cadence,
    ): array {
        $avgDepositAmount = $regularDeposits->count() > 0
            ? round((float) $regularDeposits->avg('amount'), 2)
            : 0.0;

        $remaining = ($totalGoal !== null && $totalGoal > 0)
            ? round(max(0, $totalGoal - $balance), 2)
            : null;

        if ($totalGoal !== null && $remaining <= 0) {
            return [
                'confidence' => 'complete',
                'cadence' => $cadence->value,
                'cadence_label' => $cadence->label(),
                'frequency_adverb' => $cadence->frequencyAdverb(),
                'frequency_noun' => $cadence->frequencyNoun(),
                'avg_interval_days' => $cadence->intervalDays(),
                'avg_deposit_amount' => $avgDepositAmount,
                'deposits_needed' => 0,
                'estimated_days' => 0,
                'estimated_date' => null,
                'label_short' => 'Meta total cumplida',
                'label_detail' => 'Ya alcanzaste tu meta total. Sigue depositando si quieres superarla.',
            ];
        }

        if ($remaining === null || $remaining <= 0) {
            return [
                'confidence' => 'none',
                'cadence' => $cadence->value,
                'cadence_label' => $cadence->label(),
                'frequency_adverb' => $cadence->frequencyAdverb(),
                'frequency_noun' => $cadence->frequencyNoun(),
                'avg_interval_days' => $cadence->intervalDays(),
                'avg_deposit_amount' => $avgDepositAmount,
                'deposits_needed' => null,
                'estimated_days' => null,
                'estimated_date' => null,
                'label_short' => 'Define una meta total',
                'label_detail' => 'Agrega una meta total en la cuenta para estimar cuándo la cumplirías.',
            ];
        }

        $cadenceAmount = $this->cadenceDepositAmount(
            periodTarget: $periodTarget,
            configuredPeriod: $configuredPeriod,
            cadence: $cadence,
        );

        $confidence = ($periodTarget !== null && $periodTarget > 0) ? 'high' : 'none';

        if ($cadenceAmount === null || $cadenceAmount <= 0) {
            return [
                'confidence' => 'none',
                'cadence' => $cadence->value,
                'cadence_label' => $cadence->label(),
                'frequency_adverb' => $cadence->frequencyAdverb(),
                'frequency_noun' => $cadence->frequencyNoun(),
                'avg_interval_days' => $cadence->intervalDays(),
                'avg_deposit_amount' => $periodTarget ?? 0.0,
                'deposits_needed' => null,
                'estimated_days' => null,
                'estimated_date' => null,
                'label_short' => 'Sin meta por período',
                'label_detail' => 'Configura el monto meta por período en esta cuenta para calcular la proyección.',
            ];
        }

        $depositsNeeded = (int) ceil($remaining / $cadenceAmount);
        $estimatedDate = $cadence->addPeriods(now()->startOfDay(), $depositsNeeded);
        $estimatedDateLabel = $estimatedDate->translatedFormat('M Y');
        $estimatedDays = (int) now()->startOfDay()->diffInDays($estimatedDate);

        $cadenceFormatted = \App\Support\MoneyFormatter::format($cadenceAmount, $currency);
        $remainingFormatted = \App\Support\MoneyFormatter::format($remaining, $currency);
        $timePhrase = $cadence->timePhrase($depositsNeeded);
        $cadenceLabel = $cadence->label();

        $labelShort = "Meta {$timePhrase} · ~{$estimatedDateLabel}";

        $labelDetail = "Proyección {$cadenceLabel} con meta por período de {$cadenceFormatted} ({$cadence->frequencyAdverb()}). "
            ."Faltan {$remainingFormatted}: la cumplirías {$timePhrase}, hacia {$estimatedDateLabel}.";

        return [
            'confidence' => $confidence,
            'cadence' => $cadence->value,
            'cadence_label' => $cadenceLabel,
            'frequency_adverb' => $cadence->frequencyAdverb(),
            'frequency_noun' => $cadence->frequencyNoun(),
            'avg_interval_days' => $cadence->intervalDays(),
            'avg_deposit_amount' => $cadenceAmount,
            'deposits_needed' => $depositsNeeded,
            'estimated_days' => $estimatedDays,
            'estimated_date' => $estimatedDateLabel,
            'label_short' => $labelShort,
            'label_detail' => $labelDetail,
        ];
    }

    private function cadenceDepositAmount(
        ?float $periodTarget,
        ?BudgetPeriod $configuredPeriod,
        SavingsProjectionCadence $cadence,
    ): ?float {
        if ($periodTarget === null || $periodTarget <= 0) {
            return null;
        }

        if ($configuredPeriod === null || $this->periodMatchesCadence($configuredPeriod, $cadence)) {
            return round($periodTarget, 2);
        }

        $monthlyEquivalent = match ($configuredPeriod) {
            BudgetPeriod::Weekly => $periodTarget * 4,
            BudgetPeriod::Biweekly => $periodTarget * 2,
            BudgetPeriod::Monthly, BudgetPeriod::Custom => $periodTarget,
        };

        return match ($cadence) {
            SavingsProjectionCadence::Weekly => round($monthlyEquivalent / 4, 2),
            SavingsProjectionCadence::Biweekly => round($monthlyEquivalent / 2, 2),
            SavingsProjectionCadence::Monthly => round($monthlyEquivalent, 2),
            SavingsProjectionCadence::Quarterly => round($monthlyEquivalent * 3, 2),
        };
    }

    private function periodMatchesCadence(?BudgetPeriod $period, SavingsProjectionCadence $cadence): bool
    {
        return match ($cadence) {
            SavingsProjectionCadence::Weekly => $period === BudgetPeriod::Weekly,
            SavingsProjectionCadence::Biweekly => $period === BudgetPeriod::Biweekly,
            SavingsProjectionCadence::Monthly => $period === BudgetPeriod::Monthly || $period === BudgetPeriod::Custom,
            SavingsProjectionCadence::Quarterly => false,
        };
    }

    public function recalculate(SavingsAccount $account): void
    {
        $transactions = $account->transactions()
            ->orderBy('occurred_at')
            ->orderBy('id')
            ->get();

        $balance = 0.0;

        foreach ($transactions as $transaction) {
            $balance += $transaction->signedAmount();
        }

        $pending = 0.0;

        $withdrawals = $transactions->where('type', SavingsTransactionType::Withdrawal);

        foreach ($withdrawals as $withdrawal) {
            $replenished = (float) SavingsTransaction::query()
                ->where('related_withdrawal_id', $withdrawal->id)
                ->where('type', SavingsTransactionType::Deposit)
                ->sum('amount');

            $pending += max(0, (float) $withdrawal->amount - $replenished);
        }

        $account->forceFill([
            'current_balance' => round($balance, 2),
            'pending_replenishment' => round($pending, 2),
        ])->saveQuietly();
    }

    private function createTransaction(
        SavingsAccount $account,
        SavingsTransactionType $type,
        float $amount,
        ?string $notes,
        ?Carbon $occurredAt,
        ?BudgetPlanItem $budgetPlanItem = null,
        ?SavingsTransaction $relatedWithdrawal = null,
    ): SavingsTransaction {
        return DB::transaction(function () use ($account, $type, $amount, $notes, $occurredAt, $budgetPlanItem, $relatedWithdrawal): SavingsTransaction {
            $transaction = $account->transactions()->create([
                'type' => $type,
                'amount' => round($amount, 2),
                'occurred_at' => ($occurredAt ?? now())->toDateString(),
                'notes' => $notes,
                'budget_plan_item_id' => $budgetPlanItem?->id,
                'related_withdrawal_id' => $relatedWithdrawal?->id,
            ]);

            $this->recalculate($account->refresh());

            return $transaction;
        });
    }

    private function assertSufficientBalance(SavingsAccount $account, float $amount): void
    {
        $this->recalculate($account);
        $account->refresh();

        if ((float) $account->current_balance < $amount - 0.0001) {
            throw new InvalidArgumentException('Saldo insuficiente para este retiro.');
        }
    }
}
