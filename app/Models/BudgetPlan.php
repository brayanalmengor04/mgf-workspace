<?php

namespace App\Models;

use App\Enums\BudgetPeriod;
use App\Enums\BudgetStatus;
use App\Enums\QuoteCurrency;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Models\Concerns\HasActivity;
use Spatie\Activitylog\Support\LogOptions;

class BudgetPlan extends Model
{
    use HasActivity;

    protected $attributes = [
        'currency' => 'PAB',
        'period' => 'biweekly',
        'pdf_layout' => 'classic',
        'primary_color' => '#0f172a',
    ];

    protected $fillable = [
        'budget_number',
        'status',
        'title',
        'subtitle',
        'period',
        'net_income',
        'income_notes',
        'currency',
        'pdf_layout',
        'primary_color',
        'total_allocated',
        'remaining_balance',
        'footer_notes',
        'generated_payload',
        'pdf_path',
        'issued_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => BudgetStatus::class,
            'period' => BudgetPeriod::class,
            'currency' => QuoteCurrency::class,
            'net_income' => 'decimal:2',
            'total_allocated' => 'decimal:2',
            'remaining_balance' => 'decimal:2',
            'generated_payload' => 'array',
            'issued_at' => 'datetime',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(BudgetPlanItem::class)->orderBy('sort_order');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeForUser(Builder $query, User $user): Builder
    {
        if ($user->isAdmin()) {
            return $query;
        }

        return $query->where('created_by', $user->id);
    }

    public function isDraft(): bool
    {
        return $this->status === BudgetStatus::Draft;
    }

    public function isIssued(): bool
    {
        return $this->status === BudgetStatus::Issued;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }
}
