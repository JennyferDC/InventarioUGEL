<?php

namespace App\Services\AI\Drivers;

use App\Services\AI\Contracts\AIService;
use Illuminate\Support\Facades\Http;
use Exception;

class OpenRouterDriver implements AIService
{
    protected array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function generateText(string $prompt, array $options = []): string
    {
        $apiKey = $this->config['key'] ?? null;
        $model = $this->config['model'] ?? 'google/gemini-2.5-flash';

        if (!$apiKey) {
            throw new Exception("La clave API de OpenRouter no está configurada en config/services.php.");
        }

        $referer = url('/') ?? 'http://localhost:8000';

        $response = Http::timeout(30)
            ->withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
                'HTTP-Referer' => $referer,
                'X-Title' => 'Inventario UGEL',
            ])
            ->post('https://openrouter.ai/api/v1/chat/completions', [
                'model' => $model,
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'temperature' => $options['temperature'] ?? 0.7,
            ]);

        if ($response->failed()) {
            $errorMsg = $response->json('error.message') ?? $response->body();
            throw new Exception("Error en la API de OpenRouter ({$model}): {$errorMsg}");
        }

        $text = $response->json('choices.0.message.content');

        if ($text === null || $text === '') {
            throw new Exception("Formato de respuesta inválido o vacío de la API de OpenRouter ({$model}).");
        }

        return $text;
    }
}
