<?php

namespace App\Models;

use App\Enums\BudgetCategoryType;
use App\Models\Concerns\ConfiguresActivityLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Concerns\HasActivity;
use Spatie\Activitylog\Support\LogOptions;

class BudgetItemTemplate extends Model
{
    use ConfiguresActivityLog, HasActivity;

    protected $attributes = [
        'category_type' => 'fixed_expense',
        'default_amount' => 0,
        'sort_order' => 0,
        'is_active' => true,
    ];

    protected $fillable = [
        'user_id',
        'category_type',
        'concept',
        'notes',
        'default_amount',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'category_type' => BudgetCategoryType::class,
            'default_amount' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeForUser(Builder $query, User $user): Builder
    {
        if ($user->canViewGlobalData()) {
            return $query;
        }

        return $query->where('user_id', $user->id);
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public static function nextSortOrderFor(int $userId, BudgetCategoryType|string $category): int
    {
        $categoryValue = $category instanceof BudgetCategoryType
            ? $category->value
            : (string) $category;

        $max = static::query()
            ->where('user_id', $userId)
            ->where('category_type', $categoryValue)
            ->max('sort_order');

        return ((int) $max) + 1;
    }

    /**
     * @return array{category_type: string, concept: string, notes: string|null, amount: float, is_paid: bool, paid_at: null}
     */
    public function toBudgetItemPayload(): array
    {
        return [
            'category_type' => $this->category_type->value,
            'concept' => $this->concept,
            'notes' => $this->notes,
            'amount' => (float) $this->default_amount,
            'is_paid' => false,
            'paid_at' => null,
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return static::activityLogOptionsFor([
            'category_type',
            'concept',
            'notes',
            'default_amount',
            'sort_order',
            'is_active',
        ]);
    }
}
