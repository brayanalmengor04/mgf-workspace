<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;

/**
 * Snapshot financiero cacheado para el asistente (evita recomputar en cada mensaje).
 */
class AssistantContextCacheService
{
    public function ttlSeconds(): int
    {
        return (int) config('services.gemini.chat.context_cache_ttl', 300);
    }

    public function getCompactSummary(User $user): string
    {
        $key = $this->cacheKey($user);

        return Cache::remember($key, $this->ttlSeconds(), function () use ($user): string {
            return app(FinancialContextService::class)->getCompactSummary($user);
        });
    }

    public function invalidate(User $user): void
    {
        Cache::forget($this->cacheKey($user));
    }

    private function cacheKey(User $user): string
    {
        return 'assistant:financial_context:'.$user->id;
    }
}
