<?php

namespace App\Filament\Resources\BudgetPlans\Pages;

use App\Enums\BudgetPeriod;
use App\Enums\BudgetPdfLayout;
use App\Enums\BudgetStatus;
use App\Enums\QuoteCurrency;
use App\Filament\Concerns\InteractsWithEmbeddedWizard;
use App\Filament\Resources\BudgetPlans\BudgetPlanResource;
use App\Filament\Resources\BudgetPlans\Concerns\RecalculatesBudgetTotals;
use App\Filament\Resources\BudgetPlans\Schemas\BudgetPlanForm;
use App\Models\BudgetPlan;
use App\Services\Budgets\BudgetNumberGenerator;
use Filament\Resources\Pages\CreateRecord;

class CreateBudgetPlan extends CreateRecord
{
    use InteractsWithEmbeddedWizard;
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
        $data['pdf_layout'] ??= BudgetPdfLayout::Classic->value;
        $data['primary_color'] ??= '#0f172a';
        $data['income_notes'] ??= 'Tras descuentos de ley (SS, SE, ISR)';

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

        $allPaid = true;

        foreach ($items as $index => $item) {
            if (!$item['is_paid']) {
                $allPaid = false;
            }

            $record->items()->create([
                'category_type' => $item['category_type'],
                'concept' => $item['concept'],
                'notes' => $item['notes'],
                'amount' => $item['amount'],
                'is_paid' => $item['is_paid'],
                'paid_at' => $item['paid_at'] ?? null,
                'sort_order' => $index,
            ]);
        }

        if (count($items) > 0) {
            $record->update(['is_paid' => $allPaid]);
        }
    }
}
