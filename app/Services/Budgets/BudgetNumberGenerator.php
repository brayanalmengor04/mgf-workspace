<?php

namespace App\Services\Budgets;

use App\Models\BudgetPlan;
use Illuminate\Support\Facades\DB;

class BudgetNumberGenerator
{
    public function generate(): string
    {
        $year = now()->year;
        $prefix = "PRES-{$year}-";

        return DB::transaction(function () use ($prefix, $year): string {
            $lastNumber = BudgetPlan::query()
                ->where('budget_number', 'like', $prefix.'%')
                ->lockForUpdate()
                ->orderByDesc('budget_number')
                ->value('budget_number');

            $sequence = 1;

            if ($lastNumber !== null && preg_match('/PRES-'.preg_quote((string) $year, '/').'-(\d+)$/', $lastNumber, $matches)) {
                $sequence = ((int) $matches[1]) + 1;
            }

            return $prefix.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
        });
    }
}
