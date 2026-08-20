<?php

namespace App\Models;

use App\Enums\SavingsTransactionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SavingsTransaction extends Model
{
    protected $fillable = [
        'savings_account_id',
        'type',
        'amount',
        'occurred_at',
        'notes',
        'budget_plan_item_id',
        'related_withdrawal_id',
    ];

    protected function casts(): array
    {
        return [
            'type' => SavingsTransactionType::class,
            'amount' => 'decimal:2',
            'occurred_at' => 'date',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(SavingsAccount::class, 'savings_account_id');
    }

    public function budgetPlanItem(): BelongsTo
    {
        return $this->belongsTo(BudgetPlanItem::class);
    }

    public function relatedWithdrawal(): BelongsTo
    {
        return $this->belongsTo(self::class, 'related_withdrawal_id');
    }

    public function replenishments(): HasMany
    {
        return $this->hasMany(self::class, 'related_withdrawal_id');
    }

    public function signedAmount(): float
    {
        if ($this->type === SavingsTransactionType::Adjustment) {
            return (float) $this->amount;
        }

        return (float) $this->amount * $this->type->signedMultiplier();
    }
}
