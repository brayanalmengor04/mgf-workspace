<?php

namespace App\Filament\Resources\BudgetPlans\Pages;

use App\Filament\Resources\BudgetPlans\BudgetPlanResource;
use App\Models\BudgetPlan;
use App\Services\Budgets\BudgetPdfService;
use App\Services\Crm\BudgetPlanMetricsService;
use App\Support\MoneyFormatter;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Response;

class ViewBudgetPlan extends Page
{
    use InteractsWithRecord;

    protected static string $resource = BudgetPlanResource::class;

    protected string $view = 'filament.budget-plans.view';

    protected static ?string $title = 'Presupuesto';

    protected static bool $shouldRegisterNavigation = false;

    protected Width|string|null $maxContentWidth = Width::Full;

    public string $activeTab = 'summary';

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);
        $this->record->loadMissing('items');
        $this->mountCanAuthorizeAccess();

        if (request()->has('tab')) {
            $tab = (string) request()->query('tab', 'summary');
            if (in_array($tab, ['summary', 'items', 'documents'], true)) {
                $this->activeTab = $tab;
            }
        }
    }

    public function getTitle(): string
    {
        /** @var BudgetPlan $plan */
        $plan = $this->getRecord();

        return $plan->title ?: $plan->budget_number;
    }

    /**
     * @return array<string, mixed>
     */
    public function getHubData(): array
    {
        /** @var BudgetPlan $plan */
        $plan = $this->getRecord();
        $metrics = app(BudgetPlanMetricsService::class)->forPlan($plan);
        $currency = $metrics['currency'];

        return [
            'plan' => $plan,
            'metrics' => $metrics,
            'currency' => $currency,
            'formatted' => [
                'net_income' => MoneyFormatter::format($metrics['net_income'], $currency),
                'remaining' => MoneyFormatter::format($metrics['remaining_balance'], $currency),
                'allocated' => MoneyFormatter::format($metrics['total_allocated'], $currency),
                'paid' => MoneyFormatter::format($metrics['paid_amount'], $currency),
                'pending' => MoneyFormatter::format($metrics['pending_amount'], $currency),
            ],
        ];
    }

    public function updatedActiveTab(): void
    {
        if ($this->activeTab === 'summary') {
            $this->dispatch('budget-hub-charts');
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('Volver')
                ->icon(Heroicon::OutlinedArrowLeft)
                ->url(BudgetPlanResource::getUrl('index'))
                ->color('gray'),
            Action::make('download_pdf')
                ->label('PDF')
                ->icon(Heroicon::OutlinedArrowDownTray)
                ->visible(fn (): bool => $this->getRecord()->pdf_path !== null)
                ->action(function (): mixed {
                    /** @var BudgetPlan $plan */
                    $plan = $this->getRecord();
                    $path = app(BudgetPdfService::class)->downloadPath($plan);

                    if ($path === null) {
                        Notification::make()->title('PDF no disponible')->danger()->send();

                        return null;
                    }

                    return Response::download($path, "{$plan->budget_number}.pdf");
                }),
            EditAction::make()
                ->url(fn (): string => BudgetPlanResource::getUrl('edit', ['record' => $this->getRecord()])),
        ];
    }
}
