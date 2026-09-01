<?php

namespace App\Filament\Resources\BudgetPlans\Pages;

use App\Filament\Resources\BudgetPlans\BudgetPlanResource;
use App\Filament\Widgets\BudgetListStatsWidget;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBudgetPlans extends ListRecords
{
    protected static string $resource = BudgetPlanResource::class;

    public function getSubheading(): ?string
    {
        return 'Comprobantes, seguimiento de pagos y saldo disponible.';
    }

    protected function getHeaderWidgets(): array
    {
        return [
            BudgetListStatsWidget::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Nuevo presupuesto')
                ->icon('heroicon-o-plus')
                ->color('primary'),
        ];
    }

    /**
     * @return array<string>
     */
    public function getPageClasses(): array
    {
        return [
            ...parent::getPageClasses(),
            'mgf-budget-plans-list-page',
        ];
    }
}
