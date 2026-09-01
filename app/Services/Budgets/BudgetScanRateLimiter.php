<?php

namespace App\Services\Budgets;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use RuntimeException;

class BudgetScanRateLimiter
{
    public function maxPerHour(): int
    {
        return (int) config('services.budget_scan.rate_limit_per_hour', 3);
    }

    public function ensure(User $user): void
    {
        $count = (int) Cache::get($this->cacheKey($user), 0);

        if ($count >= $this->maxPerHour()) {
            throw new RuntimeException(
                'Has alcanzado el límite de escaneos por hora ('.$this->maxPerHour().'). Intenta más tarde.'
            );
        }
    }

    public function hit(User $user): void
    {
        $key = $this->cacheKey($user);
        $count = (int) Cache::get($key, 0);
        Cache::put($key, $count + 1, now()->addHour());
    }

    private function cacheKey(User $user): string
    {
        return 'budget_scan:rate:'.$user->id.':'.now()->format('Y-m-d-H');
    }
}
