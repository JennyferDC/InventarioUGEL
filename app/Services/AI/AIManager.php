<?php

namespace App\Services\AI;

use Illuminate\Support\Manager;
use App\Services\AI\Contracts\AIService;
use App\Services\AI\Drivers\GeminiDriver;
use App\Services\AI\Drivers\MockDriver;

class AIManager extends Manager implements AIService
{
    protected ?array $lastResponseMetadata = null;

    /**
     * Obtiene los metadatos de la última llamada exitosa.
     *
     * @return array|null
     */
    public function getLastResponseMetadata(): ?array
    {
        return $this->lastResponseMetadata;
    }

    /**
     * Establece los metadatos de la respuesta de IA actual.
     *
     * @param array $metadata
     * @return void
     */
    public function setLastResponseMetadata(array $metadata): void
    {
        $this->lastResponseMetadata = $metadata;
    }

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
     * Instancia el driver de OpenRouter.
     *
     * @return Drivers\OpenRouterDriver
     */
    public function createOpenRouterDriver()
    {
        $config = $this->config->get('services.ai.drivers.openrouter', []);
        return new Drivers\OpenRouterDriver($config);
    }

    /**
     * Genera texto con tolerancia a fallos secuencial (Gemini -> OpenRouter).
     */
    public function generateText(string $prompt, array $options = []): string
    {
        $defaultDriver = $this->getDefaultDriver();

        // If configured as mock, execute mock directly and return metadata
        if ($defaultDriver === 'mock') {
            $this->setLastResponseMetadata([
                'provider' => 'Mock',
                'model' => 'mock-driver'
            ]);
            return $this->driver('mock')->generateText($prompt, $options);
        }

        // Try direct Gemini call if it is the default driver
        if ($defaultDriver === 'gemini') {
            try {
                $result = $this->driver('gemini')->generateText($prompt, $options);
                $this->setLastResponseMetadata([
                    'provider' => 'Gemini',
                    'model' => $this->config->get('services.ai.drivers.gemini.model', 'gemini-2.5-flash')
                ]);
                return $result;
            } catch (\Exception $e) {
                logger()->warning("La llamada directa a la API de Gemini falló: " . $e->getMessage() . ". Activando failover automático a OpenRouter...");
            }
        }

        // Gemini failed or OpenRouter is set as default. Fallback to OpenRouter.
        $openRouterConfig = $this->config->get('services.ai.drivers.openrouter', []);
        $openRouterKey = $openRouterConfig['key'] ?? null;
        $openRouterModels = $openRouterConfig['models'] ?? [
            'google/gemini-2.5-flash',
            'meta-llama/llama-3.3-70b-instruct',
            'deepseek/deepseek-chat',
            'qwen/qwen-2.5-72b-instruct'
        ];

        if (!$openRouterKey) {
            throw new \Exception("La llamada principal a la IA falló y la clave API de OpenRouter no está configurada en config/services.php.");
        }

        $lastException = null;

        // Bucle secuencial de reintentos sobre la lista de modelos de OpenRouter
        foreach ($openRouterModels as $model) {
            try {
                logger()->info("Intentando llamada a OpenRouter mediante el modelo: {$model}");
                
                $driver = new \App\Services\AI\Drivers\OpenRouterDriver([
                    'key' => $openRouterKey,
                    'model' => $model
                ]);

                $result = $driver->generateText($prompt, $options);

                $this->setLastResponseMetadata([
                    'provider' => 'OpenRouter',
                    'model' => $model
                ]);

                return $result;
            } catch (\Exception $e) {
                logger()->warning("El modelo de OpenRouter {$model} falló: " . $e->getMessage());
                $lastException = $e;
            }
        }

        throw new \Exception("Todos los proveedores y modelos de IA fallaron. Último error registrado: " . ($lastException ? $lastException->getMessage() : 'Desconocido'));
    }
}
