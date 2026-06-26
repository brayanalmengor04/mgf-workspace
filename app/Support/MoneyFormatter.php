<?php

namespace App\Support;

use App\Enums\QuoteCurrency;
use Illuminate\Support\Number;

class MoneyFormatter
{
    public static function format(float $amount, QuoteCurrency|string|null $currency = null): string
    {
        $currency = QuoteCurrency::resolve($currency);

        return Number::currency($amount, $currency->value, config('app.locale', 'es'));
    }
}
