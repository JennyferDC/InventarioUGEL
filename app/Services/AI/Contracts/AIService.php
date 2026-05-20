<?php

namespace App\Services\AI\Contracts;

interface AIService
{
    /**
     * Genera texto a partir de un prompt dado.
     *
     * @param string $prompt
     * @param array $options Opciones adicionales específicas del driver
     * @return string
     * @throws \Exception
     */
    public function generateText(string $prompt, array $options = []): string;
}
