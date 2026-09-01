<?php

namespace App\Services\Budgets;

use App\Enums\BudgetPeriod;
use App\Enums\BudgetStatus;
use App\Enums\QuoteCurrency;
use App\Models\BudgetPlan;
use App\Models\BudgetPlanItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class BudgetPlanFactory
{
    public function __construct(
        private readonly BudgetNumberGenerator $numberGenerator,
    ) {}

    /**
     * @param  array<string, mixed>  $budgetData
     */
    public function createDraftFromArray(array $budgetData, User $user): BudgetPlan
    {
        return DB::transaction(function () use ($budgetData, $user): BudgetPlan {
            $items = collect($budgetData['items'] ?? [])->filter(
                fn (array $item): bool => filled($item['concept'] ?? null)
            )->values();

            $totalAllocated = $items->sum(fn (array $item): float => (float) ($item['amount'] ?? 0));
            $netIncome = (float) ($budgetData['net_income'] ?? 0);
            $remaining = $netIncome - $totalAllocated;

            $period = BudgetPeriod::tryFrom((string) ($budgetData['period'] ?? ''))
                ?? BudgetPeriod::Biweekly;

            $plan = BudgetPlan::query()->create([
                'budget_number' => $this->numberGenerator->generate(),
                'status' => BudgetStatus::Draft,
                'title' => $budgetData['title'] ?? $period->defaultTitle(),
                'subtitle' => $budgetData['subtitle'] ?? $period->defaultSubtitle(),
                'period' => $period->value,
                'currency' => QuoteCurrency::resolve($budgetData['currency'] ?? null)->value,
                'net_income' => $netIncome,
                'income_notes' => $budgetData['income_notes'] ?? 'Tras descuentos de ley (SS, SE, ISR)',
                'total_allocated' => $totalAllocated,
                'remaining_balance' => $remaining,
                'created_by' => $user->id,
            ]);

            $sortOrder = 1;
            foreach ($items as $item) {
                BudgetPlanItem::query()->create([
                    'budget_plan_id' => $plan->id,
                    'category_type' => $item['category_type'] ?? 'fixed_expense',
                    'sort_order' => $sortOrder++,
                    'concept' => (string) $item['concept'],
                    'amount' => (float) ($item['amount'] ?? 0),
                    'notes' => filled($item['notes'] ?? null) ? (string) $item['notes'] : null,
                ]);
            }

            return $plan->fresh(['items']);
        });
    }
}
