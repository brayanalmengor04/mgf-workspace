<?php

namespace App\Filament\Resources\BudgetPlans\Pages;

use App\Enums\BudgetCategoryType;
use App\Enums\QuoteCurrency;
use App\Filament\Resources\BudgetPlans\BudgetPlanResource;
use App\Models\BudgetPlan;
use App\Models\BudgetPlanItem;
use App\Services\Budgets\BudgetCalculator;
use Filament\Actions\Action;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;

class ChartsBudgetPlan extends Page
{
    use InteractsWithRecord;

    protected static string $resource = BudgetPlanResource::class;

    protected string $view = 'filament.budget-plans.charts';

    protected static ?string $title = 'Métricas del presupuesto';

    protected static bool $shouldRegisterNavigation = false;

    protected Width | string | null $maxContentWidth = Width::Full;

    public function mount(int | string $record): void
    {
        $this->record = $this->resolveRecord($record);
        $this->record->loadMissing('items');

        $this->mountCanAuthorizeAccess();
    }

    public function getTitle(): string
    {
        /** @var BudgetPlan $plan */
        $plan = $this->getRecord();

        return $plan->title ?: $plan->budget_number;
    }

    /**
     * Datos orientados a gráficas por ítem (poco texto).
     *
     * @return array{
     *     currency: QuoteCurrency,
     *     net_income: float,
     *     remaining_balance: float,
     *     total_allocated: float,
     *     items: array<int, array{concept: string, amount: float, percentage: float, is_paid: bool, color: string, category: string, share: float}>,
     *     category_chart: array{labels: array<int, string>, data: array<int, float>, colors: array<int, string>},
     *     items_chart: array{labels: array<int, string>, data: array<int, float>, colors: array<int, string>},
     *     payment_chart: array{labels: array<int, string>, data: array<int, float>, colors: array<int, string>},
     *     ranking_chart: array{labels: array<int, string>, data: array<int, float>, colors: array<int, string>},
     *     max_amount: float
     * }
     */
    public function getMetrics(): array
    {
        /** @var BudgetPlan $plan */
        $plan = $this->getRecord();
        $currency = QuoteCurrency::resolve($plan->currency);
        $netIncome = (float) $plan->net_income;

        $itemsPayload = $plan->items->map(fn (BudgetPlanItem $item): array => [
            'category_type' => $item->category_type instanceof BudgetCategoryType
                ? $item->category_type->value
                : (string) $item->category_type,
            'concept' => (string) $item->concept,
            'notes' => $item->notes,
            'amount' => (float) $item->amount,
            'is_paid' => (bool) $item->is_paid,
            'paid_at' => $item->paid_at?->format('Y-m-d'),
        ])->all();

        $result = app(BudgetCalculator::class)->calculate($netIncome, $itemsPayload);
        $totalAllocated = max((float) $result['total_allocated'], 0.01);

        $items = [];
        $paidAmount = 0.0;
        $pendingAmount = 0.0;
        $categoryIndexes = [
            BudgetCategoryType::FixedExpense->value => 0,
            BudgetCategoryType::Savings->value => 0,
            BudgetCategoryType::Other->value => 0,
        ];

        foreach ($result['items'] as $item) {
            $category = BudgetCategoryType::resolve($item['category_type']);
            $amount = (float) $item['amount'];
            $isPaid = (bool) ($item['is_paid'] ?? false);
            $concept = trim((string) ($item['concept'] ?? '')) ?: 'Sin concepto';

            if ($isPaid) {
                $paidAmount += $amount;
            } else {
                $pendingAmount += $amount;
            }

            $shadeIndex = $categoryIndexes[$category->value];
            $categoryIndexes[$category->value]++;

            $items[] = [
                'concept' => $concept,
                'amount' => $amount,
                'percentage' => (float) ($item['percentage'] ?? 0),
                'is_paid' => $isPaid,
                'color' => $this->itemChartColor($category, $shadeIndex),
                'category' => $category->label(),
                'share' => round(($amount / $totalAllocated) * 100, 1),
            ];
        }

        usort($items, fn (array $a, array $b): int => $b['amount'] <=> $a['amount']);

        $categoryLabels = [];
        $categoryData = [];
        $categoryColors = [];

        foreach (BudgetCategoryType::cases() as $category) {
            $data = $result['by_category'][$category->value];
            if ((int) $data['count'] === 0 && (float) $data['total'] <= 0) {
                continue;
            }
            $categoryLabels[] = $category->label();
            $categoryData[] = (float) $data['total'];
            $categoryColors[] = $this->categoryChartColor($category);
        }

        $itemLabels = [];
        $itemData = [];
        $itemColors = [];

        foreach ($items as $item) {
            $label = $item['concept'];
            $itemLabels[] = mb_strlen($label) > 22 ? mb_substr($label, 0, 22).'…' : $label;
            $itemData[] = $item['amount'];
            $itemColors[] = $item['color'];
        }

        $maxAmount = count($itemData) > 0 ? max($itemData) : 0.0;

        return [
            'currency' => $currency,
            'net_income' => $netIncome,
            'remaining_balance' => (float) ($plan->remaining_balance ?? $result['remaining_balance']),
            'total_allocated' => (float) $result['total_allocated'],
            'items' => $items,
            'category_chart' => [
                'labels' => $categoryLabels,
                'data' => $categoryData,
                'colors' => $categoryColors,
            ],
            'items_chart' => [
                'labels' => $itemLabels,
                'data' => $itemData,
                'colors' => $itemColors,
            ],
            'ranking_chart' => [
                'labels' => array_slice($itemLabels, 0, 8),
                'data' => array_slice($itemData, 0, 8),
                'colors' => array_slice($itemColors, 0, 8),
            ],
            'payment_chart' => [
                'labels' => ['Pagado', 'Pendiente'],
                'data' => [round($paidAmount, 2), round($pendingAmount, 2)],
                'colors' => ['#0d9488', '#f59e0b'],
            ],
            'max_amount' => $maxAmount,
        ];
    }

    /**
     * Paleta visual del panel de métricas (ámbar / teal / slate).
     */
    private function categoryChartColor(BudgetCategoryType $category): string
    {
        return match ($category) {
            BudgetCategoryType::FixedExpense => '#64748b',
            BudgetCategoryType::Savings => '#0d9488',
            BudgetCategoryType::Other => '#f59e0b',
        };
    }

    private function itemChartColor(BudgetCategoryType $category, int $index): string
    {
        $shades = match ($category) {
            BudgetCategoryType::FixedExpense => ['#94a3b8', '#64748b', '#475569', '#334155', '#1e293b'],
            BudgetCategoryType::Savings => ['#5eead4', '#2dd4bf', '#14b8a6', '#0d9488', '#0f766e'],
            BudgetCategoryType::Other => ['#fcd34d', '#fbbf24', '#f59e0b', '#d97706', '#b45309'],
        };

        return $shades[$index % count($shades)];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('Volver')
                ->icon(Heroicon::OutlinedArrowLeft)
                ->url(BudgetPlanResource::getUrl('index'))
                ->color('gray'),
            Action::make('edit')
                ->label('Editar')
                ->icon(Heroicon::OutlinedPencilSquare)
                ->url(fn (): string => BudgetPlanResource::getUrl('edit', ['record' => $this->getRecord()])),
        ];
    }
}
