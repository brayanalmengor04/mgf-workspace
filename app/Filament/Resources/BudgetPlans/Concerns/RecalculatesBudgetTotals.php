<?php

namespace App\Filament\Resources\BudgetPlans\Concerns;

use App\Models\BudgetPlan;
use App\Services\Budgets\BudgetCalculator;

trait RecalculatesBudgetTotals
{
    protected function recalculateBudgetTotals(BudgetPlan $budgetPlan): void
    {
        $budgetPlan->load('items');

        $calculator = app(BudgetCalculator::class);
        $result = $calculator->calculate(
            (float) $budgetPlan->net_income,
            $budgetPlan->items->map(fn ($item) => [
                'category_type' => $item->category_type->value,
                'concept' => $item->concept,
                'notes' => $item->notes,
                'amount' => $item->amount,
            ])->all()
        );

        foreach ($budgetPlan->items->values() as $index => $item) {
            $calculated = $result['items'][$index] ?? null;

            if ($calculated === null) {
                continue;
            }

            $item->update([
                'percentage' => $calculated['percentage'],
                'sort_order' => $index,
            ]);
        }

        $budgetPlan->update([
            'total_allocated' => $result['total_allocated'],
            'remaining_balance' => $result['remaining_balance'],
        ]);
    }
}
