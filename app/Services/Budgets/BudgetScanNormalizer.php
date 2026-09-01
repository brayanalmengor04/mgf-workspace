<?php

namespace App\Services\Budgets;

use App\Enums\BudgetCategoryType;
use App\Enums\BudgetPeriod;
use App\Enums\QuoteCurrency;
use App\Models\BudgetItemTemplate;
use App\Models\BudgetPlanItem;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class BudgetScanNormalizer
{
    /**
     * @param  array<string, mixed>  $raw
     * @return array<string, mixed>
     */
    public function normalize(array $raw, User $user): array
    {
        if (isset($raw['error'])) {
            return $raw;
        }

        $templates = BudgetItemTemplate::query()
            ->forUser($user)
            ->active()
            ->get();

        $warnings = collect($raw['warnings'] ?? [])->filter()->values()->all();
        $seenConcepts = [];
        $items = [];

        foreach ($raw['items'] ?? [] as $row) {
            if (! is_array($row)) {
                continue;
            }

            $concept = trim((string) ($row['concept'] ?? ''));
            if ($concept === '') {
                continue;
            }

            $conceptKey = $this->conceptKey($concept);
            if (in_array($conceptKey, $seenConcepts, true)) {
                $warnings[] = "Concepto duplicado omitido: {$concept}";

                continue;
            }

            $template = $this->matchTemplate($templates, $concept);
            $category = BudgetCategoryType::resolve(
                $template?->category_type->value ?? ($row['category_type'] ?? null)
            );

            $amount = $this->resolveAmount($row, $template, $user, $concept);
            $needsAmount = (bool) ($row['needs_amount'] ?? false) || $amount === null;

            if ($amount === null) {
                $amount = 0.0;
                $needsAmount = true;
                $warnings[] = "Monto pendiente para: {$concept}";
            }

            $confidence = (float) ($row['confidence'] ?? 0.8);
            if ($confidence < 0.7) {
                $warnings[] = "Revisa «{$concept}» (baja confianza de lectura)";
            }

            $items[] = [
                'concept' => $template?->concept ?? $concept,
                'amount' => round((float) $amount, 2),
                'category_type' => $category->value,
                'notes' => filled($row['notes'] ?? null)
                    ? (string) $row['notes']
                    : ($template?->notes),
                'confidence' => $confidence,
                'needs_amount' => $needsAmount,
                'matched_template' => $template !== null,
            ];

            $seenConcepts[] = $conceptKey;
        }

        $netIncome = isset($raw['net_income']) && is_numeric($raw['net_income'])
            ? (float) $raw['net_income']
            : null;

        if ($netIncome === null) {
            $warnings[] = 'Ingreso neto no detectado; confírmalo antes de crear el borrador.';
        }

        $totalItems = collect($items)->sum('amount');
        if ($netIncome !== null && abs($netIncome - $totalItems) > 0.01) {
            $warnings[] = 'La suma de ítems no coincide con el ingreso neto detectado.';
        }

        return [
            'title' => filled($raw['title'] ?? null)
                ? (string) $raw['title']
                : BudgetPeriod::Biweekly->defaultTitle(),
            'period' => BudgetPeriod::tryFrom((string) ($raw['period'] ?? ''))?->value
                ?? BudgetPeriod::Biweekly->value,
            'currency' => QuoteCurrency::resolve($raw['currency'] ?? null)->value,
            'net_income' => $netIncome ?? 0.0,
            'income_notes' => filled($raw['income_notes'] ?? null)
                ? (string) $raw['income_notes']
                : 'Tras descuentos de ley (SS, SE, ISR)',
            'extraction_confidence' => (float) ($raw['extraction_confidence'] ?? 0.75),
            'items' => $items,
            'warnings' => array_values(array_unique($warnings)),
            'sync_templates' => true,
        ];
    }

    /**
     * @param  Collection<int, BudgetItemTemplate>  $templates
     */
    private function matchTemplate(Collection $templates, string $concept): ?BudgetItemTemplate
    {
        $key = $this->conceptKey($concept);

        foreach ($templates as $template) {
            if ($this->conceptKey($template->concept) === $key) {
                return $template;
            }
        }

        $best = null;
        $bestScore = 0.0;

        foreach ($templates as $template) {
            similar_text($key, $this->conceptKey($template->concept), $percent);

            if ($percent > $bestScore) {
                $bestScore = $percent;
                $best = $template;
            }
        }

        return $bestScore >= 85 ? $best : null;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function resolveAmount(
        array $row,
        ?BudgetItemTemplate $template,
        User $user,
        string $concept,
    ): ?float {
        if (isset($row['amount']) && is_numeric($row['amount']) && (float) $row['amount'] > 0) {
            return (float) $row['amount'];
        }

        if ($template !== null && (float) $template->default_amount > 0) {
            return (float) $template->default_amount;
        }

        $historical = BudgetPlanItem::query()
            ->where('concept', $concept)
            ->whereHas('budgetPlan', fn ($query) => $query->forUser($user))
            ->orderByDesc('created_at')
            ->limit(3)
            ->avg('amount');

        if ($historical !== null && (float) $historical > 0) {
            return (float) $historical;
        }

        return null;
    }

    private function conceptKey(string $concept): string
    {
        $text = Str::ascii(mb_strtolower(trim($concept)));

        return preg_replace('/\s+/u', ' ', $text) ?? $text;
    }
}
