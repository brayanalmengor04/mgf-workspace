<?php

namespace App\Filament\Resources\BudgetPlans\Pages;

use App\Enums\BudgetCategoryType;
use App\Enums\BudgetPeriod;
use App\Enums\QuoteCurrency;
use App\Filament\Resources\BudgetPlans\BudgetPlanResource;
use App\Services\Budgets\BudgetItemTemplateSync;
use App\Services\Budgets\BudgetPlanFactory;
use App\Services\Budgets\BudgetScanSession;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Livewire\Attributes\Validate;

class ReviewBudgetScan extends Page
{
    protected static string $resource = BudgetPlanResource::class;

    protected static ?string $title = 'Revisar escaneo';

    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'filament.budget-plans.review-scan';

    protected Width|string|null $maxContentWidth = Width::Full;

    #[Validate('required|string|max:120')]
    public string $budgetTitle = '';

    #[Validate('required|string')]
    public string $period = 'biweekly';

    #[Validate('required|string')]
    public string $currency = 'PAB';

    #[Validate('required|numeric|min:0')]
    public float $net_income = 0;

    public string $income_notes = 'Tras descuentos de ley (SS, SE, ISR)';

    public bool $sync_templates = true;

    /** @var array<int, string> */
    public array $warnings = [];

    public float $extraction_confidence = 0;

    /** @var array<int, array<string, mixed>> */
    public array $items = [];

    public function mount(BudgetScanSession $session): void
    {
        $data = $session->get();

        if ($data === null) {
            Notification::make()
                ->title('No hay escaneo pendiente')
                ->body('Sube una imagen desde el escritorio o al crear un presupuesto.')
                ->warning()
                ->send();

            $this->redirect(BudgetPlanResource::getUrl('index'), navigate: true);

            return;
        }

        $this->budgetTitle = (string) ($data['title'] ?? '');
        $this->period = (string) ($data['period'] ?? BudgetPeriod::Biweekly->value);
        $this->currency = (string) ($data['currency'] ?? QuoteCurrency::Usd->value);
        $this->net_income = (float) ($data['net_income'] ?? 0);
        $this->income_notes = (string) ($data['income_notes'] ?? 'Tras descuentos de ley (SS, SE, ISR)');
        $this->sync_templates = (bool) ($data['sync_templates'] ?? true);
        $this->warnings = array_values($data['warnings'] ?? []);
        $this->extraction_confidence = (float) ($data['extraction_confidence'] ?? 0);
        $this->items = array_values($data['items'] ?? []);
    }

    public function getItemsTotalProperty(): float
    {
        return round(collect($this->items)->sum(fn (array $item): float => (float) ($item['amount'] ?? 0)), 2);
    }

    public function getHasBlockingIssuesProperty(): bool
    {
        foreach ($this->items as $item) {
            if (! empty($item['needs_amount']) && (float) ($item['amount'] ?? 0) <= 0) {
                return true;
            }
        }

        return false;
    }

    public function removeItem(int $index): void
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    public function createDraft(
        BudgetPlanFactory $factory,
        BudgetItemTemplateSync $templateSync,
        BudgetScanSession $session,
    ): void {
        $this->validate();

        if ($this->hasBlockingIssues) {
            Notification::make()
                ->title('Completa los montos pendientes')
                ->body('Hay ítems sin monto confirmado. Corrígelos o elimínalos antes de continuar.')
                ->danger()
                ->send();

            return;
        }

        $user = auth()->user();
        if ($user === null) {
            return;
        }

        $payload = [
            'title' => $this->budgetTitle,
            'period' => $this->period,
            'currency' => $this->currency,
            'net_income' => $this->net_income,
            'income_notes' => $this->income_notes,
            'items' => collect($this->items)->map(fn (array $item): array => [
                'concept' => (string) ($item['concept'] ?? ''),
                'amount' => (float) ($item['amount'] ?? 0),
                'category_type' => (string) ($item['category_type'] ?? BudgetCategoryType::FixedExpense->value),
                'notes' => filled($item['notes'] ?? null) ? (string) $item['notes'] : null,
            ])->all(),
        ];

        $plan = $factory->createDraftFromArray($payload, $user);

        if ($this->sync_templates) {
            $templateSync->syncFromPlan($plan, $user);
        }

        $session->forget();

        Notification::make()
            ->title('Borrador creado')
            ->body('Revisa y ajusta el presupuesto antes de emitir el PDF.')
            ->success()
            ->send();

        $this->redirect(BudgetPlanResource::getUrl('edit', ['record' => $plan]), navigate: true);
    }

    public function discard(BudgetScanSession $session): void
    {
        $session->forget();

        $this->redirect(BudgetPlanResource::getUrl('index'), navigate: true);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('discard')
                ->label('Descartar')
                ->icon(Heroicon::OutlinedTrash)
                ->color('gray')
                ->requiresConfirmation()
                ->action(fn () => $this->discard(app(BudgetScanSession::class))),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function getPeriodOptionsProperty(): array
    {
        return BudgetPeriod::options();
    }

    /**
     * @return array<string, string>
     */
    public function getCurrencyOptionsProperty(): array
    {
        return QuoteCurrency::options();
    }

    /**
     * @return array<string, string>
     */
    public function getCategoryOptionsProperty(): array
    {
        return BudgetCategoryType::options();
    }
}
