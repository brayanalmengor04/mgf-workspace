<?php

namespace App\Filament\Resources\BudgetItemTemplates\Pages;

use App\Filament\Resources\BudgetItemTemplates\BudgetItemTemplateResource;
use App\Models\BudgetItemTemplate;
use Filament\Resources\Pages\CreateRecord;

class CreateBudgetItemTemplate extends CreateRecord
{
    protected static string $resource = BudgetItemTemplateResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();
        $data['sort_order'] = BudgetItemTemplate::nextSortOrderFor(
            (int) $data['user_id'],
            $data['category_type'] ?? 'fixed_expense',
        );

        return $data;
    }
}
