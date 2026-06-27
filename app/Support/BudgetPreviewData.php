<?php

namespace App\Support;

use App\Enums\BudgetCategoryType;
use App\Enums\BudgetPdfLayout;
use App\Enums\BudgetPeriod;
use App\Enums\QuoteCurrency;

class BudgetPreviewData
{
    /**
     * @return array<string, mixed>
     */
    public static function samplePayload(?string $currency = null): array
    {
        $currencyEnum = QuoteCurrency::resolve($currency);
        $netIncome = 1250.0;

        $sections = [
            [
                'letter' => BudgetCategoryType::FixedExpense->sectionLetter(),
                'label' => BudgetCategoryType::FixedExpense->sectionLabel(),
                'category' => BudgetCategoryType::FixedExpense->value,
                'items' => [
                    [
                        'concept' => 'Alquiler / hipoteca',
                        'notes' => 'Pago mensual',
                        'amount' => 450.0,
                        'percentage' => 36.0,
                    ],
                    [
                        'concept' => 'Comida y supermercado',
                        'notes' => 'Quincenal',
                        'amount' => 180.0,
                        'percentage' => 14.4,
                    ],
                    [
                        'concept' => 'Transporte',
                        'notes' => null,
                        'amount' => 75.0,
                        'percentage' => 6.0,
                    ],
                ],
                'subtotal' => 705.0,
                'percentage' => 56.4,
            ],
            [
                'letter' => BudgetCategoryType::Savings->sectionLetter(),
                'label' => BudgetCategoryType::Savings->sectionLabel(),
                'category' => BudgetCategoryType::Savings->value,
                'items' => [
                    [
                        'concept' => 'Fondo de emergencia',
                        'notes' => 'Meta 6 meses',
                        'amount' => 200.0,
                        'percentage' => 16.0,
                    ],
                    [
                        'concept' => 'Vacaciones',
                        'notes' => 'Ahorro temporal',
                        'amount' => 100.0,
                        'percentage' => 8.0,
                    ],
                ],
                'subtotal' => 300.0,
                'percentage' => 24.0,
            ],
            [
                'letter' => BudgetCategoryType::Other->sectionLetter(),
                'label' => BudgetCategoryType::Other->sectionLabel(),
                'category' => BudgetCategoryType::Other->value,
                'items' => [
                    [
                        'concept' => 'Entretenimiento',
                        'notes' => null,
                        'amount' => 80.0,
                        'percentage' => 6.4,
                    ],
                ],
                'subtotal' => 80.0,
                'percentage' => 6.4,
            ],
        ];

        $totalAllocated = 1085.0;

        return [
            'budget_number' => 'PRE-2026-0001',
            'issued_at' => now()->toIso8601String(),
            'title' => BudgetPeriod::Biweekly->defaultTitle(),
            'subtitle' => BudgetPeriod::Biweekly->defaultSubtitle(),
            'period' => BudgetPeriod::Biweekly->value,
            'period_label' => BudgetPeriod::Biweekly->label(),
            'currency' => $currencyEnum->value,
            'currency_symbol' => $currencyEnum->symbol(),
            'net_income' => $netIncome,
            'income_notes' => 'Tras descuentos de ley (SS, SE, ISR)',
            'sections' => $sections,
            'totals' => [
                'total_allocated' => $totalAllocated,
                'remaining_balance' => $netIncome - $totalAllocated,
                'allocation_rate' => ($totalAllocated / $netIncome) * 100,
            ],
            'footer_notes' => 'Revisar gastos variables la próxima quincena.',
        ];
    }

    public static function renderLayoutDocument(
        BudgetPdfLayout $layout,
        ?string $currency = null,
        ?string $primaryColor = null,
    ): string {
        return view($layout->view(), [
            'budgetPlan' => null,
            'payload' => self::samplePayload($currency),
            'primaryColor' => filled($primaryColor) ? $primaryColor : '#0f172a',
        ])->render();
    }
}
