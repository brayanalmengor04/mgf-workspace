<?php

namespace App\Models;

use App\Enums\BudgetPeriod;
use App\Enums\BudgetStatus;
use App\Enums\QuoteCurrency;
use App\Models\Concerns\ConfiguresActivityLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Models\Concerns\HasActivity;
use Spatie\Activitylog\Support\LogOptions;

class BudgetPlan extends Model
{
    use ConfiguresActivityLog, HasActivity;

    protected $attributes = [
        'currency' => 'PAB',
        'period' => 'biweekly',
        'pdf_layout' => 'classic',
        'primary_color' => '#0f172a',
    ];

    protected $fillable = [
        'budget_number',
        'status',
        'is_paid',
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
            'is_paid' => 'boolean',
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
        if ($user->canViewGlobalData()) {
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

    public function syncPaymentStatus(): void
    {
        $this->loadMissing('items');

        $allPaid = $this->items->isNotEmpty()
            && $this->items->every(fn (BudgetPlanItem $item): bool => $item->is_paid);

        if ($this->is_paid !== $allPaid) {
            $this->forceFill(['is_paid' => $allPaid])->save();
        }
    }

    public function getActivitylogOptions(): LogOptions
    {
        return static::activityLogOptionsFor([
            'status',
            'is_paid',
            'title',
            'subtitle',
            'period',
            'net_income',
            'income_notes',
            'currency',
            'total_allocated',
            'remaining_balance',
            'footer_notes',
        ]);
    }
}
