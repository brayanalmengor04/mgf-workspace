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
            ->limit(30)
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
Analiza la imagen de un presupuesto personal (puede estar escrito a mano, impreso o ser una foto).
Extrae la información en JSON con este esquema exacto:

{
  "title": "string",
  "period": "weekly|biweekly|monthly|custom",
  "currency": "PAB|USD",
  "net_income": number|null,
  "income_notes": "string|null",
  "extraction_confidence": number,
  "items": [
    {
      "concept": "string",
      "amount": number|null,
      "category_type": "fixed_expense|savings|other",
      "notes": "string|null",
      "confidence": number,
      "needs_amount": boolean
    }
  ],
  "warnings": ["string"]
}

Reglas:
- Usa secciones típicas MGF: A=gastos fijos, B=ahorros, C=otros.
- Si un monto es ilegible, usa null y needs_amount=true.
- confidence entre 0 y 1 por ítem y extraction_confidence global.
- No inventes conceptos que no aparezcan en la imagen.
- Moneda PAB si ves B/. o sin indicar en Panamá.

Categorías:
{$categories}

Conceptos frecuentes del usuario (prioriza estos nombres si coinciden):
{$templates}

Contexto: {$lastPlanSummary}
PROMPT;
    }
}
