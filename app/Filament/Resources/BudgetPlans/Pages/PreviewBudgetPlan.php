<?php

namespace App\Filament\Resources\BudgetPlans\Pages;

use App\Enums\BudgetPdfLayout;
use App\Filament\Resources\BudgetPlans\BudgetPlanResource;
use App\Support\BudgetPreviewData;
use Filament\Actions\Action;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;

class PreviewBudgetPlan extends Page
{
    use InteractsWithRecord;

    protected static string $resource = BudgetPlanResource::class;

    protected string $view = 'filament.budget-plans.preview';

    protected static ?string $title = 'Vista previa del estilo PDF';

    protected static bool $shouldRegisterNavigation = false;

    protected Width | string | null $maxContentWidth = Width::Full;

    public function mount(int | string $record): void
    {
        $this->record = $this->resolveRecord($record);

        $this->mountCanAuthorizeAccess();
    }

    public function getDocumentHtml(): string
    {
        $budgetPlan = $this->getRecord();

        $layout = BudgetPdfLayout::tryFrom((string) $budgetPlan->pdf_layout)
            ?? BudgetPdfLayout::Classic;

        return BudgetPreviewData::renderLayoutDocument(
            $layout,
            $budgetPlan->currency?->value,
            $budgetPlan->primary_color,
        );
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('edit')
                ->label('Volver a editar')
                ->icon(Heroicon::OutlinedPencilSquare)
                ->url(fn (): string => BudgetPlanResource::getUrl('edit', ['record' => $this->getRecord()])),
        ];
    }
}
