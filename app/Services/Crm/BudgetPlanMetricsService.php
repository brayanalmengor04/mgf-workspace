<?php

namespace App\Services\Crm;

use App\Enums\BudgetCategoryType;
use App\Enums\QuoteCurrency;
use App\Models\BudgetPlan;
use App\Models\BudgetPlanItem;
use App\Services\Budgets\BudgetCalculator;

class BudgetPlanMetricsService
{
    /**
     * @return array{
     *     currency: QuoteCurrency,
     *     net_income: float,
     *     remaining_balance: float,
     *     total_allocated: float,
     *     paid_amount: float,
     *     pending_amount: float,
     *     payment_percent: float,
     *     items: array<int, array{concept: string, amount: float, percentage: float, is_paid: bool, color: string, category: string, share: float, paid_at: string|null}>,
     *     category_chart: array{labels: array<int, string>, data: array<int, float>, colors: array<int, string>},
     *     payment_chart: array{labels: array<int, string>, data: array<int, float>, colors: array<int, string>},
     *     max_amount: float
     * }
     */
    public function forPlan(BudgetPlan $plan): array
    {
        $plan->loadMissing('items');
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
                'paid_at' => $item['paid_at'] ?? null,
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

        $itemData = array_column($items, 'amount');
        $maxAmount = count($itemData) > 0 ? max($itemData) : 0.0;
        $paymentTotal = $paidAmount + $pendingAmount;

        return [
            'currency' => $currency,
            'net_income' => $netIncome,
            'remaining_balance' => (float) ($plan->remaining_balance ?? $result['remaining_balance']),
            'total_allocated' => (float) $result['total_allocated'],
            'paid_amount' => round($paidAmount, 2),
            'pending_amount' => round($pendingAmount, 2),
            'payment_percent' => $paymentTotal > 0 ? round(($paidAmount / $paymentTotal) * 100, 1) : 0.0,
            'items' => $items,
            'category_chart' => [
                'labels' => $categoryLabels,
                'data' => $categoryData,
                'colors' => $categoryColors,
            ],
            'payment_chart' => [
                'labels' => ['Pagado', 'Pendiente'],
                'data' => [round($paidAmount, 2), round($pendingAmount, 2)],
                'colors' => ['#0d9488', '#f59e0b'],
            ],
            'max_amount' => $maxAmount,
        ];
    }

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
}
