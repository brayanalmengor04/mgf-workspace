<?php

namespace App\Services\Budgets;

use App\Enums\BudgetCategoryType;
use App\Models\BudgetItemTemplate;
use App\Models\BudgetPlan;
use App\Models\User;
use Illuminate\Support\Collection;

class BudgetItemTemplateSync
{
    /**
     * Persist budget plan items into the user's reusable catalog.
     *
     * @return int Number of templates created or updated
     */
    public function syncFromPlan(BudgetPlan $plan, User $user): int
    {
        $plan->loadMissing('items');
        $synced = 0;

        foreach ($plan->items as $index => $item) {
            if (blank($item->concept)) {
                continue;
            }

            BudgetItemTemplate::query()->updateOrCreate(
                [
                    'user_id' => $user->id,
                    'category_type' => $item->category_type instanceof BudgetCategoryType
                        ? $item->category_type->value
                        : (string) $item->category_type,
                    'concept' => $item->concept,
                ],
                [
                    'notes' => $item->notes,
                    'default_amount' => $item->amount,
                    'sort_order' => $index,
                    'is_active' => true,
                ]
            );

            $synced++;
        }

        return $synced;
    }

    /**
     * @param  Collection<int, BudgetItemTemplate>|array<int, BudgetItemTemplate>  $templates
     * @return array<string, list<array{category_type: string, concept: string, notes: string|null, amount: float, is_paid: bool, paid_at: null}>>
     */
    public function templatesToFormGroups(Collection|array $templates): array
    {
        $grouped = $this->emptyFormGroups();

        foreach ($templates as $template) {
            $payload = $template->toBudgetItemPayload();
            $grouped["items_{$payload['category_type']}"][] = $payload;
        }

        return $grouped;
    }

    /**
     * @param  iterable<int, object|array<string, mixed>>  $items
     * @return array<string, list<array{category_type: string, concept: string, notes: string|null, amount: float, is_paid: bool, paid_at: null}>>
     */
    public function itemsToFormGroups(iterable $items, bool $resetPayment = true): array
    {
        $grouped = $this->emptyFormGroups();

        foreach ($items as $item) {
            $category = BudgetCategoryType::resolve(
                is_array($item)
                    ? ($item['category_type'] ?? null)
                    : ($item->category_type ?? null)
            );

            $concept = is_array($item) ? ($item['concept'] ?? '') : ($item->concept ?? '');
            $notes = is_array($item) ? ($item['notes'] ?? null) : ($item->notes ?? null);
            $amount = (float) (is_array($item) ? ($item['amount'] ?? 0) : ($item->amount ?? 0));

            if (blank($concept)) {
                continue;
            }

            $grouped["items_{$category->value}"][] = [
                'category_type' => $category->value,
                'concept' => (string) $concept,
                'notes' => filled($notes) ? (string) $notes : null,
                'amount' => $amount,
                'is_paid' => $resetPayment ? false : (bool) (is_array($item) ? ($item['is_paid'] ?? false) : ($item->is_paid ?? false)),
                'paid_at' => $resetPayment
                    ? null
                    : (is_array($item)
                        ? ($item['paid_at'] ?? null)
                        : ($item->paid_at?->format('Y-m-d') ?? null)),
            ];
        }

        return $grouped;
    }

    /**
     * @return array<string, list<array{category_type: string, concept: string, notes: string|null, amount: float, is_paid: bool, paid_at: null}>>
     */
    public function emptyFormGroups(): array
    {
        $grouped = [];

        foreach (BudgetCategoryType::cases() as $category) {
            $grouped["items_{$category->value}"] = [];
        }

        return $grouped;
    }

    /**
     * Merge imported rows into existing repeater state (append, skip exact concept duplicates in same category).
     *
     * @param  array<string, mixed>  $currentState
     * @param  array<string, list<array{category_type: string, concept: string, notes: string|null, amount: float, is_paid: bool, paid_at: null}>>  $incoming
     * @return array<string, list<array{category_type: string, concept: string, notes: string|null, amount: float, is_paid: bool, paid_at: null}>>
     */
    public function mergeFormGroups(array $currentState, array $incoming): array
    {
        $merged = $this->emptyFormGroups();

        foreach (BudgetCategoryType::cases() as $category) {
            $key = "items_{$category->value}";
            $existing = collect($currentState[$key] ?? [])
                ->filter(fn (array $row): bool => filled($row['concept'] ?? null))
                ->values()
                ->all();

            $existingConcepts = collect($existing)
                ->map(fn (array $row): string => mb_strtolower(trim((string) $row['concept'])))
                ->all();

            foreach ($incoming[$key] ?? [] as $row) {
                $conceptKey = mb_strtolower(trim((string) ($row['concept'] ?? '')));

                if ($conceptKey === '' || in_array($conceptKey, $existingConcepts, true)) {
                    continue;
                }

                $existing[] = $row;
                $existingConcepts[] = $conceptKey;
            }

            $merged[$key] = $existing;
        }

        return $merged;
    }
}
