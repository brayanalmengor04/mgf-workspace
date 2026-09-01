<?php

namespace App\Services\Budgets;

use Illuminate\Support\Facades\Session;

class BudgetScanSession
{
    private const KEY = 'budget_scan_review';

    /**
     * @param  array<string, mixed>  $payload
     */
    public function put(array $payload): void
    {
        Session::put(self::KEY, $payload);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function get(): ?array
    {
        $data = Session::get(self::KEY);

        return is_array($data) ? $data : null;
    }

    public function forget(): void
    {
        Session::forget(self::KEY);
    }
}
