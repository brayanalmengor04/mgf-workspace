<?php

namespace App\Models;

use App\Enums\QuoteCurrency;
use App\Enums\QuoteStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Models\Concerns\HasActivity;
use Spatie\Activitylog\Support\LogOptions;

class Quote extends Model
{
    use HasActivity;

    protected $attributes = [
        'currency' => 'PAB',
    ];

    protected $fillable = [
        'quote_template_id',
        'quote_number',
        'status',
        'issuer_name',
        'issuer_ruc',
        'issuer_dv',
        'issuer_has_dv',
        'issuer_address',
        'issuer_phone',
        'issuer_email',
        'recipient_name',
        'recipient_ruc',
        'recipient_dv',
        'recipient_has_dv',
        'recipient_address',
        'recipient_phone',
        'recipient_email',
        'bank_name',
        'bank_account_number',
        'yappy_id',
        'footer_notes',
        'currency',
        'subtotal',
        'tax_amount',
        'total',
        'generated_payload',
        'pdf_path',
        'issued_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => QuoteStatus::class,
            'currency' => QuoteCurrency::class,
            'issuer_has_dv' => 'boolean',
            'recipient_has_dv' => 'boolean',
            'subtotal' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total' => 'decimal:2',
            'generated_payload' => 'array',
            'issued_at' => 'datetime',
        ];
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(QuoteTemplate::class, 'quote_template_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(QuoteItem::class)->orderBy('sort_order');
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
        return $this->status === QuoteStatus::Draft;
    }

    public function isIssued(): bool
    {
        return $this->status === QuoteStatus::Issued;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }
}
