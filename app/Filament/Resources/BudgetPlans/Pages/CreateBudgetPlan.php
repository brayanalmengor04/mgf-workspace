<?php

namespace App\Filament\Resources\BudgetPlans\Pages;

use App\Enums\BudgetPeriod;
use App\Enums\BudgetStatus;
use App\Enums\QuoteCurrency;
use App\Filament\Resources\BudgetPlans\BudgetPlanResource;
use App\Filament\Resources\BudgetPlans\Concerns\RecalculatesBudgetTotals;
use App\Filament\Resources\BudgetPlans\Schemas\BudgetPlanForm;
use App\Models\BudgetPlan;
use App\Services\Budgets\BudgetNumberGenerator;
use Filament\Resources\Pages\CreateRecord;

class CreateBudgetPlan extends CreateRecord
{
    use RecalculatesBudgetTotals;

    protected static string $resource = BudgetPlanResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['budget_number'] = app(BudgetNumberGenerator::class)->generate();
        $data['status'] = BudgetStatus::Draft->value;
        $data['created_by'] = auth()->id();
        $data['currency'] ??= QuoteCurrency::Usd->value;
        $data['period'] ??= BudgetPeriod::Biweekly->value;
        $data['title'] ??= BudgetPeriod::Biweekly->defaultTitle();
        $data['subtitle'] ??= BudgetPeriod::Biweekly->defaultSubtitle();

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->syncItemsFromForm();
        $this->recalculateBudgetTotals($this->record);
    }

    protected function syncItemsFromForm(): void
    {
        /** @var BudgetPlan $record */
        $record = $this->record;
        $state = $this->form->getRawState();
        $items = BudgetPlanForm::collectItemsFromState($state);

        foreach ($items as $index => $item) {
            $record->items()->create([
                'category_type' => $item['category_type'],
                'concept' => $item['concept'],
                'notes' => $item['notes'],
                'amount' => $item['amount'],
                'sort_order' => $index,
            ]);
        }
    }
}
