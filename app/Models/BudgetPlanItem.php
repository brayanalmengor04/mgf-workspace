<?php

namespace App\Models;

use App\Enums\BudgetCategoryType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BudgetPlanItem extends Model
{
    protected $attributes = [
        'amount' => 0,
        'percentage' => 0,
        'sort_order' => 0,
        'category_type' => 'fixed_expense',
    ];

    protected $fillable = [
        'budget_plan_id',
        'category_type',
        'sort_order',
        'concept',
        'notes',
        'amount',
        'percentage',
    ];

    protected function casts(): array
    {
        return [
            'category_type' => BudgetCategoryType::class,
            'amount' => 'decimal:2',
            'percentage' => 'decimal:1',
        ];
    }

    public function budgetPlan(): BelongsTo
    {
        return $this->belongsTo(BudgetPlan::class);
    }
}
