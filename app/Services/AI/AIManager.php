<?php

namespace App\Services\AI;

use Illuminate\Support\Manager;
use App\Services\AI\Contracts\AIService;
use App\Services\AI\Drivers\GeminiDriver;
use App\Services\AI\Drivers\MockDriver;

class AIManager extends Manager implements AIService
{
    /**
     * Obtiene el nombre del driver por defecto.
     *
     * @return string
     */
    public function getDefaultDriver()
    {
        return $this->config->get('services.ai.default', 'mock');
    }

    /**
     * Instancia el driver de Gemini.
     *
     * @return GeminiDriver
     */
    public function createGeminiDriver()
    {
        $config = $this->config->get('services.ai.drivers.gemini', []);
        return new GeminiDriver($config);
    }

    /**
     * Instancia el driver de simulacro (Mock).
     *
     * @return MockDriver
     */
    public function createMockDriver()
    {
        return new MockDriver();
    }

    /**
     * Genera texto delegando la llamada al driver activo.
     */
    public function generateText(string $prompt, array $options = []): string
    {
        return $this->driver()->generateText($prompt, $options);
    }
}
