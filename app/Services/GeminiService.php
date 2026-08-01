<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiService
{
    protected string $apiKey;
    protected string $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models';
    public function __construct()
    {
        $this->apiKey = env('GEMINI_API_KEY', '');
        $this->model = env('GEMINI_MODEL', 'gemini-2.5-flash');
    }

    /**
     * Send a message to Gemini and get the response.
     * 
     * @param array $chatHistory Array of message objects: [['role' => 'user'|'model', 'parts' => [['text' => '...']]]]
     * @param string|null $systemInstruction Optional system instruction for the model.
     * @return string
     */
    public function generateContent(array $chatHistory, ?string $systemInstruction = null): string
    {
        if (empty($this->apiKey)) {
            Log::error('Gemini API key is not set.');
            return 'Error: Gemini API key no configurada.';
        }

        $url = "{$this->baseUrl}/{$this->model}:generateContent?key={$this->apiKey}";

        $payload = [
            'contents' => $chatHistory,
        ];

        if ($systemInstruction) {
            $payload['systemInstruction'] = [
                'parts' => [
                    ['text' => $systemInstruction]
                ]
            ];
        }

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->post($url, $payload);

        if ($response->successful()) {
            $data = $response->json();
            
            if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                return $data['candidates'][0]['content']['parts'][0]['text'];
            }
            
            Log::error('Unexpected Gemini response format', ['response' => $data]);
            return 'Lo siento, no pude procesar la respuesta adecuadamente.';
        }

        Log::error('Gemini API Error', [
            'status' => $response->status(),
            'body' => $response->body()
        ]);

        return 'Hubo un error al comunicarse con la IA. Por favor, intenta de nuevo más tarde.';
    }
}
