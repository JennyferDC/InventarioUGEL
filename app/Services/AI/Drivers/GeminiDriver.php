<?php

namespace App\Services\AI\Drivers;

use App\Services\AI\Contracts\AIService;
use Illuminate\Support\Facades\Http;
use Exception;

class GeminiDriver implements AIService
{
    protected array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function generateText(string $prompt, array $options = []): string
    {
        $apiKey = $this->config['key'] ?? null;
        $model = $this->config['model'] ?? 'gemini-2.5-flash';

        if (!$apiKey) {
            throw new Exception("La clave API de Gemini no está configurada en config/services.php.");
        }

        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";

        $response = Http::timeout(30)
            ->post($url, [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt]
                        ]
                    ]
                ],
                'generationConfig' => array_merge([
                    'temperature' => 0.7,
                ], $options['generationConfig'] ?? [])
            ]);

        if ($response->failed()) {
            $errorMsg = $response->json('error.message') ?? $response->body();
            throw new Exception("Error en la API de Gemini: {$errorMsg}");
        }

        $text = $response->json('candidates.0.content.parts.0.text');

        if (!$text) {
            throw new Exception("Formato de respuesta inválido o vacío de la API de Gemini.");
        }

        return $text;
    }
}
