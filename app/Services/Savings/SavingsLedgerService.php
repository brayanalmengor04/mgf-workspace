<?php

namespace App\Services\Savings;

use App\Enums\BudgetPeriod;
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
     *         pending_withdrawals: array<int, array{amount: float, pending: float, occurred_at: string, notes: string|null}>
     *     }>
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
                ? round(((float) $account->current_balance / $goalAmount) * 100, 1)
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
                'pending_withdrawals' => $pendingWithdrawals,
            ];
        })->values()->all();

        $moneyIn = round($totals['openings'] + $totals['deposits'] + $totals['replenishments'], 2);

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
        ];
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
