<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiService
{
    protected string $apiKey;

    protected string $model;

    protected string $thinkingLevel;

    protected string $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models';

    public function __construct(
        private readonly GeminiUsageLogger $usageLogger,
    ) {
        $this->apiKey = (string) config('services.gemini.api_key', '');
        $this->model = (string) config('services.gemini.model', 'gemini-3.1-flash-lite');
        $this->thinkingLevel = (string) config('services.gemini.thinking_level', 'minimal');
    }

    /**
     * @param  array<int, array{role: string, parts: array<int, array{text: string}>}>  $chatHistory
     */
    public function generateContent(array $chatHistory, ?string $systemInstruction = null, string $purpose = 'chat'): string
    {
        if ($this->apiKey === '') {
            Log::error('Gemini API key is not set.');

            return 'Error: Gemini API key no configurada.';
        }

        $url = "{$this->baseUrl}/{$this->model}:generateContent?key={$this->apiKey}";

        $payload = [
            'contents' => $chatHistory,
            'generationConfig' => [
                'temperature' => 0.7,
                'thinkingConfig' => [
                    'thinkingLevel' => $this->thinkingLevel,
                ],
            ],
        ];

        if ($systemInstruction) {
            $payload['systemInstruction'] = [
                'parts' => [
                    ['text' => $systemInstruction],
                ],
            ];
        }

        $startedAt = microtime(true);

        $response = Http::timeout(60)
            ->withHeaders(['Content-Type' => 'application/json'])
            ->post($url, $payload);

        $durationMs = (microtime(true) - $startedAt) * 1000;

        if (! $response->successful()) {
            $this->usageLogger->recordError(
                $this->model,
                $purpose,
                $response->status(),
                $response->body(),
                $durationMs,
            );

            Log::error('Gemini API Error', [
                'status' => $response->status(),
                'body' => $response->body(),
                'model' => $this->model,
                'purpose' => $purpose,
            ]);

            return 'Hubo un error al comunicarse con la IA. Por favor, intenta de nuevo más tarde.';
        }

        $data = $response->json();
        $text = $this->extractVisibleText($data);

        $this->usageLogger->record(
            $this->model,
            $purpose,
            is_array($data) ? ($data['usageMetadata'] ?? null) : null,
            $response->status(),
            $durationMs,
        );

        if ($text === '') {
            Log::error('Unexpected Gemini response format', ['response' => $data]);

            return 'Lo siento, no pude procesar la respuesta adecuadamente.';
        }

        return $text;
    }

    public function translateToSpanish(string $text): string
    {
        if (trim($text) === '' || ! config('services.gemini.translate_fallback', true)) {
            return $text;
        }

        $translation = $this->generateContent(
            [
                [
                    'role' => 'user',
                    'parts' => [
                        ['text' => "Traduce al español latinoamericano el siguiente mensaje para un usuario de Panamá. Devuelve SOLO el texto traducido, sin comillas, sin explicaciones y sin notas:\n\n{$text}"],
                    ],
                ],
            ],
            'Eres un traductor. Solo devuelves el texto en español. Nada más.',
            'translate',
        );

        if (str_starts_with($translation, 'Error:') || str_starts_with($translation, 'Hubo un error')) {
            return $text;
        }

        return $translation;
    }

    /**
     * @param  array<string, mixed>|null  $data
     */
    protected function extractVisibleText(?array $data): string
    {
        if (! isset($data['candidates'][0]['content']['parts']) || ! is_array($data['candidates'][0]['content']['parts'])) {
            return '';
        }

        $chunks = [];

        foreach ($data['candidates'][0]['content']['parts'] as $part) {
            if (! is_array($part) || ! isset($part['text'])) {
                continue;
            }

            if (($part['thought'] ?? false) === true) {
                continue;
            }

            $chunks[] = $part['text'];
        }

        return trim(implode("\n", $chunks));
    }
}
