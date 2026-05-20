<?php

namespace App\Services\AI;

use App\Services\AI\Contracts\AIService;

class AITasksService
{
    protected AIService $ai;

    public function __construct(AIService $ai)
    {
        $this->ai = $ai;
    }

    /**
     * Corrige y profesionaliza observaciones redactadas de manera informal sobre equipos tecnológicos.
     *
     * @param string $observacion
     * @return string
     */
    public function mejorarObservacionTecnica(string $observacion): string
    {
        $systemPrompt = "Eres un asistente técnico especializado en inventario informático y mantenimiento de equipos tecnológicos. Tu función es corregir y profesionalizar observaciones redactadas de manera informal sobre equipos tecnológicos, utilizando un lenguaje técnico, claro y breve.";
        
        $prompt = "{$systemPrompt}\n\nObservación informal a mejorar:\n\"{$observacion}\"\n\nObservación mejorada:";

        return $this->ai->generateText($prompt);
    }
}
