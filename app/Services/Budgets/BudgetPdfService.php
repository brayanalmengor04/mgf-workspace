<?php

namespace App\Services\Budgets;

use App\Enums\BudgetCategoryType;
use App\Enums\BudgetPdfLayout;
use App\Enums\BudgetPeriod;
use App\Enums\BudgetStatus;
use App\Enums\QuoteCurrency;
use App\Models\BudgetPlan;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BudgetPdfService
{
    public function __construct(
        private readonly BudgetCalculator $calculator,
    ) {}

    public function issue(BudgetPlan $budgetPlan): BudgetPlan
    {
        $budgetPlan->load('items');

        $payload = $this->buildPayload($budgetPlan);
        $pdfPath = $this->renderPdf($budgetPlan, $payload);

        $budgetPlan->forceFill([
            'status' => BudgetStatus::Issued,
            'generated_payload' => $payload,
            'pdf_path' => $pdfPath,
            'issued_at' => now(),
        ])->save();

        activity()
            ->performedOn($budgetPlan)
            ->causedBy(auth()->user())
            ->event('issued')
            ->withProperties(['budget_number' => $budgetPlan->budget_number])
            ->log('Presupuesto emitido');

        return $budgetPlan->refresh();
    }

    public function regenerate(BudgetPlan $budgetPlan): BudgetPlan
    {
        $budgetPlan->load('items');

        $payload = $budgetPlan->generated_payload ?? $this->buildPayload($budgetPlan);
        $pdfPath = $this->renderPdf($budgetPlan, $payload);

        $budgetPlan->forceFill([
            'generated_payload' => $payload,
            'pdf_path' => $pdfPath,
        ])->save();

        activity()
            ->performedOn($budgetPlan)
            ->causedBy(auth()->user())
            ->event('regenerated')
            ->withProperties(['budget_number' => $budgetPlan->budget_number])
            ->log('PDF de presupuesto regenerado');

        return $budgetPlan->refresh();
    }

    public function downloadPath(BudgetPlan $budgetPlan): ?string
    {
        if ($budgetPlan->pdf_path === null || ! Storage::disk('local')->exists($budgetPlan->pdf_path)) {
            return null;
        }

        return Storage::disk('local')->path($budgetPlan->pdf_path);
    }

    /**
     * @return array<string, mixed>
     */
    public function buildPayload(BudgetPlan $budgetPlan): array
    {
        $netIncome = (float) $budgetPlan->net_income;

        $itemsInput = $budgetPlan->items->map(fn ($item) => [
            'category_type' => $item->category_type->value,
            'concept' => $item->concept,
            'notes' => $item->notes,
            'amount' => (float) $item->amount,
        ])->all();

        $calculated = $this->calculator->calculate($netIncome, $itemsInput);

        $sections = collect(BudgetCategoryType::cases())
            ->map(function (BudgetCategoryType $category) use ($calculated): array {
                $categoryItems = collect($calculated['items'])
                    ->filter(fn (array $item): bool => $item['category_type'] === $category->value)
                    ->values()
                    ->all();

                return [
                    'letter' => $category->sectionLetter(),
                    'label' => $category->sectionLabel(),
                    'category' => $category->value,
                    'items' => $categoryItems,
                    'subtotal' => $calculated['by_category'][$category->value]['total'],
                    'percentage' => $calculated['by_category'][$category->value]['percentage'],
                ];
            })
            ->filter(fn (array $section): bool => count($section['items']) > 0)
            ->values()
            ->all();

        $period = $budgetPlan->period instanceof BudgetPeriod
            ? $budgetPlan->period
            : BudgetPeriod::tryFrom((string) $budgetPlan->period);

        return [
            'budget_number' => $budgetPlan->budget_number,
            'issued_at' => ($budgetPlan->issued_at ?? now())->toIso8601String(),
            'title' => $budgetPlan->title,
            'subtitle' => $budgetPlan->subtitle,
            'period' => $period?->value ?? '',
            'period_label' => $period?->label() ?? '',
            'currency' => QuoteCurrency::resolve($budgetPlan->currency)->value,
            'currency_symbol' => QuoteCurrency::resolve($budgetPlan->currency)->symbol(),
            'net_income' => $netIncome,
            'income_notes' => $budgetPlan->income_notes,
            'sections' => $sections,
            'totals' => [
                'total_allocated' => $calculated['total_allocated'],
                'remaining_balance' => $calculated['remaining_balance'],
                'allocation_rate' => $calculated['allocation_rate'],
            ],
            'footer_notes' => $budgetPlan->footer_notes,
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function renderPdf(BudgetPlan $budgetPlan, array $payload): string
    {
        $filename = 'budgets/'.Str::slug($budgetPlan->budget_number).'-'.Str::uuid().'.pdf';

        $layout = BudgetPdfLayout::tryFrom((string) ($budgetPlan->pdf_layout ?? '')) ?? BudgetPdfLayout::Classic;

        $pdf = Pdf::loadView($layout->view(), [
            'budgetPlan' => $budgetPlan,
            'payload' => $payload,
            'primaryColor' => $budgetPlan->primary_color ?? '#0f172a',
        ])->setPaper('letter');

        Storage::disk('local')->put($filename, $pdf->output());

        return $filename;
    }
}
