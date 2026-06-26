<?php

namespace App\Services\Budgets;

use App\Enums\BudgetCategoryType;

class BudgetCalculator
{
    /**
     * @param  array<int, array{category_type?: string, amount?: float|string}>  $items
     * @return array{
     *     items: array<int, array{category_type: string, amount: float, percentage: float}>,
     *     total_allocated: float,
     *     remaining_balance: float,
     *     allocation_rate: float,
     *     by_category: array<string, array{total: float, percentage: float, count: int}>
     * }
     */
    public function calculate(float $netIncome, array $items): array
    {
        $totalAllocated = 0.0;
        $processedItems = [];
        $byCategory = [];

        foreach (BudgetCategoryType::cases() as $category) {
            $byCategory[$category->value] = [
                'total' => 0.0,
                'percentage' => 0.0,
                'count' => 0,
            ];
        }

        foreach ($items as $item) {
            $amount = (float) ($item['amount'] ?? 0);
            $categoryType = BudgetCategoryType::resolve($item['category_type'] ?? null);
            $percentage = $netIncome > 0 ? round(($amount / $netIncome) * 100, 1) : 0.0;

            $totalAllocated += $amount;
            $byCategory[$categoryType->value]['total'] += $amount;
            $byCategory[$categoryType->value]['count']++;

            $processedItems[] = [
                'category_type' => $categoryType->value,
                'concept' => $item['concept'] ?? '',
                'notes' => $item['notes'] ?? null,
                'amount' => $amount,
                'percentage' => $percentage,
            ];
        }

        foreach ($byCategory as $key => $data) {
            $byCategory[$key]['percentage'] = $netIncome > 0
                ? round(($data['total'] / $netIncome) * 100, 1)
                : 0.0;
        }

        return [
            'items' => $processedItems,
            'total_allocated' => round($totalAllocated, 2),
            'remaining_balance' => round($netIncome - $totalAllocated, 2),
            'allocation_rate' => $netIncome > 0
                ? round(($totalAllocated / $netIncome) * 100, 1)
                : 0.0,
            'by_category' => $byCategory,
        ];
    }
}
