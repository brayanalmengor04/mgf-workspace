<?php

namespace App\Models;

use App\Enums\BudgetPeriod;
use App\Enums\QuoteCurrency;
use App\Models\Concerns\ConfiguresActivityLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Models\Concerns\HasActivity;
use Spatie\Activitylog\Support\LogOptions;

class SavingsAccount extends Model
{
    use ConfiguresActivityLog, HasActivity;

    protected $attributes = [
        'currency' => 'USD',
        'period' => 'biweekly',
        'current_balance' => 0,
        'pending_replenishment' => 0,
        'is_active' => true,
    ];

    protected $fillable = [
        'user_id',
        'name',
        'bank_alias',
        'bank_last_four',
        'currency',
        'target_per_period',
        'period',
        'goal_amount',
        'current_balance',
        'pending_replenishment',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'currency' => QuoteCurrency::class,
            'period' => BudgetPeriod::class,
            'target_per_period' => 'decimal:2',
            'goal_amount' => 'decimal:2',
            'current_balance' => 'decimal:2',
            'pending_replenishment' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(SavingsTransaction::class)->orderByDesc('occurred_at')->orderByDesc('id');
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

    public function getActivitylogOptions(): LogOptions
    {
        return static::activityLogOptionsFor([
            'name',
            'bank_alias',
            'bank_last_four',
            'currency',
            'target_per_period',
            'period',
            'goal_amount',
            'current_balance',
            'pending_replenishment',
            'is_active',
        ]);
    }
}
