<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiService
{
    protected string $apiKey;

    protected string $model;

    protected string $thinkingLevel;

    protected string $visionModel;

    protected string $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models';

    public function __construct(
        private readonly GeminiUsageLogger $usageLogger,
    ) {
        $this->apiKey = (string) config('services.gemini.api_key', '');
        $this->model = (string) config('services.gemini.model', 'gemini-3.1-flash-lite');
        $this->thinkingLevel = (string) config('services.gemini.thinking_level', 'minimal');
        $this->visionModel = (string) config('services.gemini.vision_model', 'gemini-3.1-flash-lite');
    }

    public function isConfigured(): bool
    {
        return $this->apiKey !== '';
    }

    public function missingKeyMessage(): string
    {
        return '⚠️ **El asistente IA no está configurado en este entorno.** '
            .'Agrega `GEMINI_API_KEY` en tu archivo `.env` (local) o en las variables de Railway (producción). '
            .'Obtén una key gratuita en [Google AI Studio](https://aistudio.google.com/apikey). '
            .'Después reinicia el contenedor: `docker compose up -d`.';
    }

    /**
     * @param  array<int, array{role: string, parts: array<int, array{text: string}>}>  $chatHistory
     */
    public function generateContent(array $chatHistory, ?string $systemInstruction = null, string $purpose = 'chat'): string
    {
        if (! $this->isConfigured()) {
            Log::error('Gemini API key is not set.');

            return $this->missingKeyMessage();
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

    public function generateContentFromImage(
        string $imagePath,
        string $prompt,
        ?string $systemInstruction = null,
        string $purpose = 'vision',
    ): string {
        if (! $this->isConfigured()) {
            Log::error('Gemini API key is not set.');

            return json_encode(['error' => $this->missingKeyMessage()]);
        }

        if (! is_readable($imagePath)) {
            return json_encode(['error' => 'No se pudo leer la imagen subida.']);
        }

        $mime = mime_content_type($imagePath) ?: 'image/jpeg';
        $encoded = base64_encode((string) file_get_contents($imagePath));

        $payload = [
            'contents' => [
                [
                    'role' => 'user',
                    'parts' => [
                        ['text' => $prompt],
                        [
                            'inline_data' => [
                                'mime_type' => $mime,
                                'data' => $encoded,
                            ],
                        ],
                    ],
                ],
            ],
            'generationConfig' => [
                'temperature' => 0.2,
                'responseMimeType' => 'application/json',
                'maxOutputTokens' => 2048,
            ],
        ];

        if ($systemInstruction) {
            $payload['systemInstruction'] = [
                'parts' => [
                    ['text' => $systemInstruction],
                ],
            ];
        }

        $models = $this->visionModelsToTry();
        $lastError = 'No se pudo analizar la imagen. Intenta con mejor iluminación o una foto más nítida.';

        foreach ($models as $model) {
            $result = $this->requestVisionContent($model, $payload, $purpose);

            if ($result['ok']) {
                return $result['text'];
            }

            $lastError = $result['error'];

            if (! $result['retryable']) {
                break;
            }
        }

        return json_encode(['error' => $lastError]);
    }

    /**
     * @return array<int, string>
     */
    protected function visionModelsToTry(): array
    {
        $models = array_merge(
            [$this->visionModel],
            (array) config('services.gemini.vision_model_fallbacks', []),
        );

        return array_values(array_unique(array_filter($models)));
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{ok: bool, text?: string, error: string, retryable: bool}
     */
    protected function requestVisionContent(string $model, array $payload, string $purpose): array
    {
        $url = "{$this->baseUrl}/{$model}:generateContent?key={$this->apiKey}";
        $startedAt = microtime(true);

        try {
            $response = Http::timeout(120)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($url, $payload);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::warning('Gemini Vision connection error', [
                'model' => $model,
                'message' => $e->getMessage(),
            ]);

            return [
                'ok' => false,
                'error' => 'La IA tardó demasiado en responder. Intenta de nuevo en unos segundos.',
                'retryable' => true,
            ];
        }

        $durationMs = (microtime(true) - $startedAt) * 1000;

        if (! $response->successful()) {
            $this->usageLogger->recordError(
                $model,
                $purpose,
                $response->status(),
                $response->body(),
                $durationMs,
            );

            Log::error('Gemini Vision API Error', [
                'status' => $response->status(),
                'body' => $response->body(),
                'model' => $model,
            ]);

            $retryable = in_array($response->status(), [404, 429, 500, 502, 503, 504], true);

            return [
                'ok' => false,
                'error' => $this->visionErrorMessage($response->status(), $response->body()),
                'retryable' => $retryable,
            ];
        }

        $data = $response->json();
        $text = $this->extractVisibleText($data);

        $this->usageLogger->record(
            $model,
            $purpose,
            is_array($data) ? ($data['usageMetadata'] ?? null) : null,
            $response->status(),
            $durationMs,
        );

        if ($text === '') {
            return [
                'ok' => false,
                'error' => 'La IA no devolvió datos legibles de la imagen.',
                'retryable' => true,
            ];
        }

        return [
            'ok' => true,
            'text' => $text,
            'error' => '',
            'retryable' => false,
        ];
    }

    protected function visionErrorMessage(int $status, string $body): string
    {
        if (app()->hasDebugModeEnabled()) {
            $decoded = json_decode($body, true);
            $apiMessage = is_array($decoded) ? ($decoded['error']['message'] ?? null) : null;

            if (is_string($apiMessage) && $apiMessage !== '') {
                return "Error de visión ({$status}): {$apiMessage}";
            }
        }

        return match ($status) {
            429 => 'Demasiadas solicitudes a la IA. Espera un momento e intenta de nuevo.',
            503, 504 => 'La IA está ocupada en este momento. Intenta de nuevo en unos segundos.',
            default => 'No se pudo analizar la imagen. Intenta con mejor iluminación o una foto más nítida.',
        };
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
