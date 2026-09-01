<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Cache de respuestas del asistente para preguntas repetidas (ahorro de tokens).
 */
class AssistantResponseCacheService
{
    public function ttlSeconds(): int
    {
        return (int) config('services.gemini.chat.response_cache_ttl', 3600);
    }

    public function shouldCache(string $userMessage): bool
    {
        $normalized = $this->normalize($userMessage);

        if ($normalized === '') {
            return false;
        }

        if (str_starts_with($normalized, '/')) {
            return true;
        }

        $actionVerbs = ['crea', 'crear', 'genera', 'generar', 'agrega', 'agregar', 'envia', 'enviar', 'manda', 'anota', 'registra'];

        foreach ($actionVerbs as $verb) {
            if (str_contains($normalized, $verb)) {
                return false;
            }
        }

        return true;
    }

    public function get(User $user, string $userMessage): ?string
    {
        if (! $this->shouldCache($userMessage)) {
            return null;
        }

        return Cache::get($this->cacheKey($user, $userMessage));
    }

    public function put(User $user, string $userMessage, string $response): void
    {
        if (! $this->shouldCache($userMessage)) {
            return;
        }

        if ($this->looksLikeActionJson($response)) {
            return;
        }

        Cache::put(
            $this->cacheKey($user, $userMessage),
            $response,
            $this->ttlSeconds(),
        );
    }

    public function looksLikeActionJson(string $response): bool
    {
        return (bool) preg_match('/"action"\s*:\s*"(create_|add_|save_|request_|deposit_)/', $response);
    }

    private function cacheKey(User $user, string $userMessage): string
    {
        return 'assistant:response:'.$user->id.':'.md5($this->normalize($userMessage));
    }

    private function normalize(string $message): string
    {
        $text = Str::lower(trim($message));
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

        return $text;
    }
}
