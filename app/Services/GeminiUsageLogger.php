<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class GeminiUsageLogger
{
    /**
     * @param  array<string, mixed>|null  $usageMetadata
     */
    public function record(
        string $model,
        string $purpose,
        ?array $usageMetadata,
        int $httpStatus,
        float $durationMs,
    ): void {
        $promptTokens = (int) ($usageMetadata['promptTokenCount'] ?? 0);
        $outputTokens = (int) ($usageMetadata['candidatesTokenCount'] ?? 0);
        $totalTokens = (int) ($usageMetadata['totalTokenCount'] ?? ($promptTokens + $outputTokens));
        $thoughtTokens = (int) ($usageMetadata['thoughtsTokenCount'] ?? 0);

        $minuteKey = now()->format('Y-m-d-H-i');
        $dayKey = now()->format('Y-m-d');

        $requestsThisMinute = $this->incrementCounter("gemini:rpm:{$minuteKey}", 1, 120);
        $tokensThisMinute = $this->incrementCounter("gemini:tpm:{$minuteKey}", $totalTokens, 120);
        $requestsToday = $this->incrementCounter("gemini:rpd:{$dayKey}", 1, 86_400);
        $tokensToday = $this->incrementCounter("gemini:tpd:{$dayKey}", $totalTokens, 86_400);

        $limits = config('services.gemini.rate_limits', []);
        $rpmLimit = (int) ($limits['rpm'] ?? 0);
        $tpmLimit = (int) ($limits['tpm'] ?? 0);
        $rpdLimit = (int) ($limits['rpd'] ?? 0);

        $summary = sprintf(
            '[%s] %s | status=%d | tokens=%d (in=%d out=%d thoughts=%d) | %.0fms | RPM %d%s | TPM %d%s | RPD %d%s | TPD %d',
            $model,
            $purpose,
            $httpStatus,
            $totalTokens,
            $promptTokens,
            $outputTokens,
            $thoughtTokens,
            $durationMs,
            $requestsThisMinute,
            $rpmLimit > 0 ? "/{$rpmLimit}" : '',
            $tokensThisMinute,
            $tpmLimit > 0 ? "/{$tpmLimit}" : '',
            $requestsToday,
            $rpdLimit > 0 ? "/{$rpdLimit}" : '',
            $tokensToday,
        );

        Log::channel('gemini')->info($summary, [
            'model' => $model,
            'purpose' => $purpose,
            'http_status' => $httpStatus,
            'duration_ms' => round($durationMs, 2),
            'usage' => [
                'prompt_tokens' => $promptTokens,
                'output_tokens' => $outputTokens,
                'thought_tokens' => $thoughtTokens,
                'total_tokens' => $totalTokens,
            ],
            'rolling' => [
                'requests_this_minute' => $requestsThisMinute,
                'tokens_this_minute' => $tokensThisMinute,
                'requests_today' => $requestsToday,
                'tokens_today' => $tokensToday,
            ],
            'limits' => [
                'rpm' => $rpmLimit ?: null,
                'tpm' => $tpmLimit ?: null,
                'rpd' => $rpdLimit ?: null,
            ],
            'usage_percent' => [
                'rpm' => $rpmLimit > 0 ? round(($requestsThisMinute / $rpmLimit) * 100, 1) : null,
                'tpm' => $tpmLimit > 0 ? round(($tokensThisMinute / $tpmLimit) * 100, 1) : null,
                'rpd' => $rpdLimit > 0 ? round(($requestsToday / $rpdLimit) * 100, 1) : null,
            ],
        ]);
    }

    public function recordError(string $model, string $purpose, int $httpStatus, string $body, float $durationMs): void
    {
        $summary = sprintf(
            '[%s] %s | ERROR status=%d | %.0fms',
            $model,
            $purpose,
            $httpStatus,
            $durationMs,
        );

        Log::channel('gemini')->error($summary, [
            'model' => $model,
            'purpose' => $purpose,
            'http_status' => $httpStatus,
            'duration_ms' => round($durationMs, 2),
            'rate_limited' => $httpStatus === 429,
            'body' => mb_substr($body, 0, 2000),
        ]);

        if ($httpStatus === 429) {
            Log::channel('gemini')->warning('Gemini rate limit alcanzado (HTTP 429). Revisa AI Studio o baja GEMINI_TRANSLATE_FALLBACK.');
        }
    }

    private function incrementCounter(string $key, int $amount, int $ttlSeconds): int
    {
        if (! Cache::has($key)) {
            Cache::put($key, 0, $ttlSeconds);
        }

        return (int) Cache::increment($key, $amount);
    }
}
