<?php

namespace App\Services\Budgets;

use App\Enums\BudgetStatus;
use App\Models\BudgetPlan;
use App\Models\User;
use App\Support\ActivityLogSilencer;

class BudgetPlanDuplicator
{
    public function __construct(
        private readonly BudgetNumberGenerator $numberGenerator,
        private readonly BudgetCalculator $calculator,
    ) {}

    public function duplicate(BudgetPlan $source, ?User $actor = null): BudgetPlan
    {
        $actor ??= auth()->user();

        $duplicate = $source->replicate([
            'budget_number',
            'status',
            'is_paid',
            'generated_payload',
            'pdf_path',
            'issued_at',
            'total_allocated',
            'remaining_balance',
        ]);

        $duplicate->budget_number = $this->numberGenerator->generate();
        $duplicate->status = BudgetStatus::Draft;
        $duplicate->is_paid = false;
        $duplicate->created_by = $actor?->id ?? $source->created_by;
        $duplicate->save();

        $source->loadMissing('items');

        foreach ($source->items as $item) {
            $duplicate->items()->create($item->only([
                'category_type',
                'sort_order',
                'concept',
                'notes',
                'amount',
                'percentage',
            ]));
        }

        $this->recalculateTotals($duplicate);

        activity()
            ->performedOn($duplicate)
            ->causedBy($actor)
            ->event('duplicated')
            ->withProperties(['source_budget' => $source->budget_number])
            ->log('Presupuesto duplicado');

        return $duplicate->load('items');
    }

    private function recalculateTotals(BudgetPlan $budgetPlan): void
    {
        ActivityLogSilencer::withoutModelLogs(function () use ($budgetPlan): void {
            $budgetPlan->load('items');

            $result = $this->calculator->calculate(
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

            $budgetPlan->syncPaymentStatus();
        });
    }
}
