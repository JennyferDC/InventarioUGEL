<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\AI\AIManager;
use App\Services\AI\Contracts\AIService;

class AIServiceProvider extends ServiceProvider
{
    /**
     * Registra servicios en el contenedor de dependencias.
     */
    public function register(): void
    {
        $this->app->singleton(AIManager::class, function ($app) {
            return new AIManager($app);
        });

        // Vincula el contrato AIService con el AIManager
        $this->app->bind(AIService::class, AIManager::class);
    }
}
