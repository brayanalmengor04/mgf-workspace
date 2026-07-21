<?php

namespace App\Filament\Resources\BudgetItemTemplates\Pages;

use App\Filament\Resources\BudgetItemTemplates\BudgetItemTemplateResource;
use App\Models\BudgetItemTemplate;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditBudgetItemTemplate extends EditRecord
{
    protected static string $resource = BudgetItemTemplateResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        /** @var BudgetItemTemplate $record */
        $record = $this->record;

        $newCategory = (string) ($data['category_type'] ?? $record->category_type->value);
        $oldCategory = $record->category_type->value;

        if ($newCategory !== $oldCategory) {
            $data['sort_order'] = BudgetItemTemplate::nextSortOrderFor(
                (int) $record->user_id,
                $newCategory,
            );
        }

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
