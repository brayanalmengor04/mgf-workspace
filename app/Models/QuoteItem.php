<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuoteItem extends Model
{
    protected $attributes = [
        'quantity' => 1,
        'unit_price' => 0,
        'tax_rate' => 7,
        'tax_amount' => 0,
        'line_total' => 0,
        'sort_order' => 0,
    ];

    protected $fillable = [
        'quote_id',
        'sort_order',
        'quantity',
        'description',
        'unit_price',
        'tax_rate',
        'tax_amount',
        'line_total',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'unit_price' => 'decimal:2',
            'tax_rate' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'line_total' => 'decimal:2',
        ];
    }

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    protected static function booted(): void
    {
        static::creating(function (QuoteItem $item): void {
            $item->tax_rate ??= 7;
            $item->quantity ??= 1;
            $item->unit_price ??= 0;
        });
    }
}
