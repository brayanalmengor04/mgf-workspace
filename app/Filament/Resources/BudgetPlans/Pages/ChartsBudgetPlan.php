<?php

namespace App\Filament\Resources\BudgetPlans\Pages;

use App\Filament\Resources\BudgetPlans\BudgetPlanResource;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;

class ChartsBudgetPlan extends Page
{
    use InteractsWithRecord;

    protected static string $resource = BudgetPlanResource::class;

    protected string $view = 'filament.pages.empty-redirect';

    protected static bool $shouldRegisterNavigation = false;

    public function mount(int|string $record): void
    {
        $this->redirect(BudgetPlanResource::getUrl('view', [
            'record' => $record,
            'tab' => 'summary',
        ]));
    }
}
