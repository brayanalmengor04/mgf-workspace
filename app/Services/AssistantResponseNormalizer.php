<?php

namespace App\Services;

class AssistantResponseNormalizer
{
    public function __construct(
        private readonly GeminiService $gemini,
    ) {}

    public function normalize(string $raw): string
    {
        $text = $this->stripInternalMonologue($raw);

        if ($text === '') {
            return '';
        }

        if ($this->shouldTranslateToSpanish($text)) {
            $text = $this->gemini->translateToSpanish($text);
            $text = $this->stripInternalMonologue($text);
        }

        return trim($text);
    }

    protected function stripInternalMonologue(string $text): string
    {
        $text = trim($text);

        if ($text === '') {
            return '';
        }

        $patterns = [
            '/^(?:user asks?|user says?|context|contexto|capabilities|date|fecha|role|rol|prompt|system|instruction|thought|thinking)\s*:.*/im',
            '/^managing finances\.?$/im',
            '/^generating budgets.*$/im',
            '/^scheduling calendar events\.?$/im',
            '/^(?:model|asistente|ai)\s*:\s*/im',
        ];

        foreach ($patterns as $pattern) {
            $text = preg_replace($pattern, '', $text) ?? $text;
        }

        $lines = preg_split("/\r\n|\n|\r/", $text) ?: [];
        $kept = [];

        foreach ($lines as $line) {
            $trimmed = trim($line);

            if ($trimmed === '') {
                continue;
            }

            if (preg_match('/^(user asks?|capabilities|date|managing|generating|scheduling)\b/i', $trimmed)) {
                continue;
            }

            if (preg_match('/\b(user asks?|how can i help you today)\b/i', $trimmed) && ! preg_match('/[áéíóúñ¿¡]/ui', $trimmed)) {
                continue;
            }

            $kept[] = $trimmed;
        }

        return trim(implode("\n", $kept));
    }

    protected function shouldTranslateToSpanish(string $text): bool
    {
        if (! config('services.gemini.translate_fallback', true)) {
            return false;
        }

        if (preg_match('/```json[\s\S]*?```/', $text)) {
            return false;
        }

        if (preg_match('/[áéíóúñ¿¡]/ui', $text)) {
            return false;
        }

        $words = preg_split('/\s+/u', mb_strtolower(strip_tags($text)), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        if ($words === []) {
            return false;
        }

        $englishMarkers = [
            'the', 'and', 'you', 'your', 'with', 'help', 'today', 'user', 'asks', 'capabilities',
            'managing', 'generating', 'scheduling', 'financial', 'budget', 'assistant', 'can',
        ];

        $hits = 0;

        foreach ($words as $word) {
            if (in_array($word, $englishMarkers, true)) {
                $hits++;
            }
        }

        return $hits >= 2;
    }
}
