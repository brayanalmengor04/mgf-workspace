<?php

namespace App\Services\Quotes;

use App\Models\Quote;
use Illuminate\Support\Facades\DB;

class QuoteNumberGenerator
{
    public function generate(): string
    {
        $year = now()->year;
        $prefix = "COT-{$year}-";

        return DB::transaction(function () use ($prefix, $year): string {
            $lastNumber = Quote::query()
                ->where('quote_number', 'like', $prefix.'%')
                ->lockForUpdate()
                ->orderByDesc('quote_number')
                ->value('quote_number');

            $sequence = 1;

            if ($lastNumber !== null && preg_match('/COT-'.preg_quote((string) $year, '/').'-(\d+)$/', $lastNumber, $matches)) {
                $sequence = ((int) $matches[1]) + 1;
            }

            return $prefix.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
        });
    }
}
