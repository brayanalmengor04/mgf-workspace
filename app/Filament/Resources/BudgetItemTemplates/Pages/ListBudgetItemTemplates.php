<?php

namespace App\Filament\Resources\BudgetItemTemplates\Pages;

use App\Filament\Resources\BudgetItemTemplates\BudgetItemTemplateResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBudgetItemTemplates extends ListRecords
{
    protected static string $resource = BudgetItemTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
