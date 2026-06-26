<?php

namespace App\Models;

use App\Enums\QuoteCurrency;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Models\Concerns\HasActivity;
use Spatie\Activitylog\Support\LogOptions;

class QuoteTemplate extends Model
{
    use HasActivity;

    protected $attributes = [
        'currency' => 'PAB',
    ];

    protected $fillable = [
        'user_id',
        'name',
        'is_default',
        'is_active',
        'issuer_name',
        'issuer_ruc',
        'issuer_dv',
        'issuer_has_dv',
        'issuer_address',
        'issuer_phone',
        'issuer_email',
        'bank_name',
        'bank_account_number',
        'yappy_id',
        'footer_notes',
        'currency',
        'pdf_layout',
        'logo_path',
        'primary_color',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'is_active' => 'boolean',
            'issuer_has_dv' => 'boolean',
            'currency' => QuoteCurrency::class,
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
        if ($user->isAdmin()) {
            return $query;
        }

        return $query->where('user_id', $user->id);
    }

    public function quotes(): HasMany
    {
        return $this->hasMany(Quote::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    protected static function booted(): void
    {
        static::saving(function (QuoteTemplate $template): void {
            if (! $template->is_default) {
                return;
            }

            static::query()
                ->where('user_id', $template->user_id)
                ->where('id', '!=', $template->id ?? 0)
                ->update(['is_default' => false]);
        });
    }
}
