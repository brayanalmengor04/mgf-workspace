<?php

namespace App\Filament\Resources\BudgetPlans\Concerns;

use App\Filament\Resources\BudgetPlans\Schemas\BudgetPlanForm;
use App\Models\BudgetPlan;
use App\Services\Savings\SavingsLedgerService;

trait SyncsBudgetPlanItems
{
    protected function syncItemsFromForm(): void
    {
        /** @var BudgetPlan $record */
        $record = $this->record;
        $state = $this->form->getRawState();
        $items = BudgetPlanForm::collectItemsFromState($state);
        $keptIds = [];

        foreach ($items as $index => $item) {
            $payload = [
                'category_type' => $item['category_type'],
                'concept' => $item['concept'],
                'notes' => $item['notes'],
                'amount' => $item['amount'],
                'is_paid' => $item['is_paid'],
                'paid_at' => $item['paid_at'] ?? null,
                'savings_account_id' => $item['savings_account_id'] ?? null,
                'sort_order' => $index,
            ];

            if (! empty($item['id'])) {
                $existing = $record->items()->find($item['id']);

                if ($existing !== null) {
                    $existing->update($payload);
                    $keptIds[] = $existing->id;

                    continue;
                }
            }

            $created = $record->items()->create($payload);
            $keptIds[] = $created->id;
        }

        $record->items()->whereNotIn('id', $keptIds)->delete();
        $record->syncPaymentStatus();
        $record->load('items');

        app(SavingsLedgerService::class)->syncDepositsFromBudgetPlan($record);
    }
}
