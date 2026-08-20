<?php

namespace App\Services\Budgets;

use App\Enums\BudgetCategoryType;
use App\Enums\BudgetStatus;
use App\Enums\QuoteCurrency;
use App\Models\BudgetPlan;
use App\Models\BudgetPlanItem;
use App\Models\User;
use App\Services\Savings\SavingsLedgerService;
use Illuminate\Support\Collection;

class FinancialMetricsService
{
    /**
     * @return array{
     *     has_issued: bool,
     *     currency: QuoteCurrency,
     *     available_balance: float,
     *     available_delta: float|null,
     *     available_sparkline: array<int, float>,
     *     net_income: float,
     *     net_income_delta: float|null,
     *     net_income_delta_percent: float|null,
     *     net_income_sparkline: array<int, float>,
     *     paid_plans_count: int,
     *     paid_amount: float,
     *     payment_compliance_percent: float,
     *     paid_items_count: int,
     *     total_items_count: int,
     *     payment_sparkline: array<int, float>,
     *     savings_amount: float,
     *     savings_percent: float,
     *     fixed_expenses_amount: float,
     *     fixed_expenses_percent: float,
     *     exceeded_plans_count: int,
     *     issued_plans_count: int,
     *     total_income: float,
     *     total_expenses: float
     * }
     */
    public function overviewFor(User $user): array
    {
        $issued = $this->issuedPlansChronological($user, 12);
        $latest = $issued->last();
        $previous = $issued->count() >= 2 ? $issued->values()->get($issued->count() - 2) : null;

        $currency = QuoteCurrency::resolve($latest?->currency);

        $available = $latest !== null ? (float) $latest->remaining_balance : 0.0;
        $previousAvailable = $previous !== null ? (float) $previous->remaining_balance : null;
        $netIncome = $latest !== null ? (float) $latest->net_income : 0.0;
        $previousNetIncome = $previous !== null ? (float) $previous->net_income : null;

        $availableDelta = $previousAvailable !== null ? round($available - $previousAvailable, 2) : null;
        $netIncomeDelta = $previousNetIncome !== null ? round($netIncome - $previousNetIncome, 2) : null;
        $netIncomeDeltaPercent = ($previousNetIncome !== null && $previousNetIncome != 0.0)
            ? round(($netIncomeDelta / $previousNetIncome) * 100, 1)
            : null;

        $plansQuery = BudgetPlan::query()->forUser($user);
        $issuedPlansQuery = (clone $plansQuery)->where('status', BudgetStatus::Issued);
        $issuedPlansCount = (clone $issuedPlansQuery)->count();
        $paidPlansCount = (clone $plansQuery)->where('is_paid', true)->count();

        $totalIncome = (float) (clone $issuedPlansQuery)->sum('net_income');

        $issuedItemsQuery = BudgetPlanItem::query()
            ->whereHas(
                'budgetPlan',
                fn ($query) => $query->forUser($user)->where('status', BudgetStatus::Issued)
            );

        $totalExpenses = (float) (clone $issuedItemsQuery)
            ->whereIn('category_type', [
                BudgetCategoryType::FixedExpense->value,
                BudgetCategoryType::Other->value,
            ])
            ->sum('amount');

        $itemsQuery = BudgetPlanItem::query()
            ->whereHas('budgetPlan', fn ($query) => $query->forUser($user));

        $paidAmount = (float) (clone $itemsQuery)->where('is_paid', true)->sum('amount');
        $paidItemsCount = (clone $itemsQuery)->where('is_paid', true)->count();
        $totalItemsCount = (clone $itemsQuery)->count();
        $paymentCompliance = $totalItemsCount > 0
            ? round(($paidItemsCount / $totalItemsCount) * 100, 1)
            : 0.0;

        $savingsAmount = 0.0;
        $fixedExpensesAmount = 0.0;

        if ($latest !== null) {
            $latest->loadMissing('items');
            $savingsAmount = (float) $latest->items
                ->where('category_type', BudgetCategoryType::Savings)
                ->sum('amount');
            $fixedExpensesAmount = (float) $latest->items
                ->where('category_type', BudgetCategoryType::FixedExpense)
                ->sum('amount');
        }

        $savingsPercent = $netIncome > 0 ? round(($savingsAmount / $netIncome) * 100, 1) : 0.0;
        $fixedExpensesPercent = $netIncome > 0 ? round(($fixedExpensesAmount / $netIncome) * 100, 1) : 0.0;

        $exceededPlansCount = (clone $plansQuery)
            ->where('status', BudgetStatus::Issued)
            ->where('remaining_balance', '<', 0)
            ->count();

        return [
            'has_issued' => $latest !== null,
            'currency' => $currency,
            'available_balance' => $available,
            'available_delta' => $availableDelta,
            'available_sparkline' => $issued->map(fn (BudgetPlan $plan): float => (float) $plan->remaining_balance)->values()->all(),
            'net_income' => $netIncome,
            'net_income_delta' => $netIncomeDelta,
            'net_income_delta_percent' => $netIncomeDeltaPercent,
            'net_income_sparkline' => $issued->map(fn (BudgetPlan $plan): float => (float) $plan->net_income)->values()->all(),
            'paid_plans_count' => $paidPlansCount,
            'paid_amount' => round($paidAmount, 2),
            'payment_compliance_percent' => $paymentCompliance,
            'paid_items_count' => $paidItemsCount,
            'total_items_count' => $totalItemsCount,
            'payment_sparkline' => $issued->map(fn (BudgetPlan $plan): float => $this->paidAmountForPlan($plan))->values()->all(),
            'savings_amount' => round($savingsAmount, 2),
            'savings_percent' => $savingsPercent,
            'fixed_expenses_amount' => round($fixedExpensesAmount, 2),
            'fixed_expenses_percent' => $fixedExpensesPercent,
            'exceeded_plans_count' => $exceededPlansCount,
            'issued_plans_count' => $issuedPlansCount,
            'total_income' => round($totalIncome, 2),
            'total_expenses' => round($totalExpenses, 2),
        ];
    }

    /**
     * @return array{
     *     labels: array<int, string>,
     *     available_balance: array<int, float>,
     *     net_income: array<int, float>,
     *     paid_amount: array<int, float>,
     *     currency: \App\Enums\QuoteCurrency
     * }
     */
    public function trendSeriesFor(User $user, int $limit = 50): array
    {
        $issued = $this->issuedPlansChronological($user, $limit);
        $latest = $issued->last();

        return [
            'labels' => $issued->map(fn (BudgetPlan $plan): string => $this->planLabel($plan))->values()->all(),
            'available_balance' => $issued->map(fn (BudgetPlan $plan): float => (float) $plan->remaining_balance)->values()->all(),
            'net_income' => $issued->map(fn (BudgetPlan $plan): float => (float) $plan->net_income)->values()->all(),
            'paid_amount' => $issued->map(fn (BudgetPlan $plan): float => $this->paidAmountForPlan($plan))->values()->all(),
            'currency' => QuoteCurrency::resolve($latest?->currency),
        ];
    }

    /**
     * @return array{
     *     total_balance: float,
     *     pending_replenishment: float,
     *     active_accounts: int
     * }
     */
    public function savingsLedgerSummaryFor(User $user): array
    {
        return app(SavingsLedgerService::class)->summaryForUser($user);
    }

    /**
     * @return Collection<int, BudgetPlan>
     */
    private function issuedPlansChronological(User $user, int $limit): Collection
    {
        return BudgetPlan::query()
            ->forUser($user)
            ->where('status', BudgetStatus::Issued)
            ->with('items')
            ->orderByDesc('issued_at')
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->reverse()
            ->values();
    }

    private function paidAmountForPlan(BudgetPlan $plan): float
    {
        $plan->loadMissing('items');

        return round((float) $plan->items->where('is_paid', true)->sum('amount'), 2);
    }

    private function planLabel(BudgetPlan $plan): string
    {
        if ($plan->issued_at !== null) {
            return $plan->issued_at->translatedFormat('d M');
        }

        $title = trim((string) ($plan->title ?? ''));

        if ($title !== '') {
            return mb_strlen($title) > 16 ? mb_substr($title, 0, 16).'…' : $title;
        }

        return (string) ($plan->budget_number ?? '#'.$plan->id);
    }
}
