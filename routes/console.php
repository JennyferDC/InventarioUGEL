<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

use App\Services\AI\Contracts\AIService;

Artisan::command('ai:test {prompt}', function (AIService $ai) {
    $this->info("Enviando prompt a la IA: " . $this->argument('prompt'));
    try {
        $result = $ai->generateText($this->argument('prompt'));
        $this->comment("Resultado:\n" . $result);
    } catch (\Exception $e) {
        $this->error("Error: " . $e->getMessage());
    }
})->purpose('Probar el servicio de IA de forma sencilla');
