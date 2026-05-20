<?php

namespace App\Services\AI\Drivers;

use App\Services\AI\Contracts\AIService;

class MockDriver implements AIService
{
    public function generateText(string $prompt, array $options = []): string
    {
        return "[MOCK AI DRIVER] Recibido prompt: \"{$prompt}\"\nEste es un resultado de texto simulado. Configura un driver real (como Gemini) en services.php y .env para producción.";
    }
}
