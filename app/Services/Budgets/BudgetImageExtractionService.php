<?php

namespace App\Services\Budgets;

use App\Enums\BudgetCategoryType;
use App\Models\BudgetItemTemplate;
use App\Models\BudgetPlan;
use App\Models\User;
use App\Services\GeminiService;

class BudgetImageExtractionService
{
    public function __construct(
        private readonly GeminiService $gemini,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function extract(string $imagePath, User $user): array
    {
        $prompt = $this->buildPrompt($user);
        $system = 'Eres un extractor de presupuestos personales para usuarios en Panamá. '
            .'Devuelve SOLO JSON válido según el esquema indicado. No incluyas markdown ni explicaciones.';

        $response = $this->gemini->generateContentFromImage(
            imagePath: $imagePath,
            prompt: $prompt,
            systemInstruction: $system,
            purpose: 'budget_scan',
        );

        $decoded = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE || ! is_array($decoded)) {
            if (preg_match('/\{.*\}/s', $response, $matches)) {
                $decoded = json_decode($matches[0], true);
            }
        }

        if (! is_array($decoded)) {
            return [
                'error' => 'No se pudo interpretar la respuesta de la IA.',
                'raw' => $response,
            ];
        }

        if (isset($decoded['error'])) {
            return $decoded;
        }

        return $decoded;
    }

    private function buildPrompt(User $user): string
    {
        $categories = collect(BudgetCategoryType::cases())
            ->map(fn (BudgetCategoryType $type): string => "- {$type->value}: {$type->sectionLabel()}")
            ->implode("\n");

        $templates = BudgetItemTemplate::query()
            ->forUser($user)
            ->active()
            ->orderBy('sort_order')
            ->limit(12)
            ->get()
            ->map(fn (BudgetItemTemplate $template): string => sprintf(
                '- [%s] %s (monto ref: %s)',
                $template->category_type->value,
                $template->concept,
                number_format((float) $template->default_amount, 2)
            ))
            ->implode("\n");

        $lastPlan = BudgetPlan::query()
            ->forUser($user)
            ->with('items')
            ->orderByDesc('created_at')
            ->first();

        $lastPlanSummary = $lastPlan
            ? "Último presupuesto: {$lastPlan->title} ({$lastPlan->budget_number}), ingreso {$lastPlan->net_income}, {$lastPlan->items->count()} ítems."
            : 'Sin presupuestos previos.';

        return <<<PROMPT
Extrae el presupuesto de la imagen. Devuelve SOLO JSON válido con este esquema:

{"title":"string","period":"weekly|biweekly|monthly|custom","currency":"PAB|USD","net_income":number|null,"income_notes":"string|null","extraction_confidence":number,"items":[{"concept":"string","amount":number|null,"category_type":"fixed_expense|savings|other","notes":"string|null","confidence":number,"needs_amount":boolean}],"warnings":["string"]}

Reglas: A=fixed_expense, B=savings, C=other. Monto ilegible → null y needs_amount=true. No inventes conceptos. PAB por defecto en Panamá.

Categorías:
{$categories}

Conceptos del usuario (prioriza si coinciden):
{$templates}

{$lastPlanSummary}
PROMPT;
    }
}
