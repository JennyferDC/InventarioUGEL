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
        $systemPrompt = "Eres un asistente técnico especializado en inventario informático y mantenimiento de equipos tecnológicos. Tu función es corregir y profesionalizar observaciones redactadas de manera informal sobre equipos tecnológicos, utilizando un lenguaje técnico, claro y breve.\n"
            . "Reglas:\n"
            . "Mantén el significado original de la observación.\n"
            . "No agregues información que no haya sido mencionada.\n"
            . "Usa una redacción profesional, precisa y directa.\n"
            . "Corrige ortografía y mejora la estructura del texto.\n"
            . "IMPORTANTE: Devuelve ÚNICAMENTE la observación mejorada como texto plano, sin explicaciones, sin preámbulo, sin opciones alternativas y sin comillas, listo para ser insertado en un campo de texto.";
        
        $prompt = "{$systemPrompt}\n\nObservación informal a mejorar:\n\"{$observacion}\"\n\nObservación mejorada:";

        return trim($this->ai->generateText($prompt));
    }

    /**
     * Realiza un diagnóstico técnico de problemas en base a la información, características e historial de un equipo.
     *
     * @param array $equipoData
     * @param array $caracteristicas
     * @param array $historial
     * @param string $problema
     * @return string
     */
    public function diagnosticarEquipo(array $equipoData, array $caracteristicas, array $historial, string $problema): string
    {
        // Formatting characteristics
        $specs = "";
        foreach ($caracteristicas as $c) {
            $specs .= "- " . ($c['clave'] ?? '') . ": " . ($c['valor'] ?? '') . "\n";
        }
        if (empty($specs)) {
            $specs = "Ninguna especificación técnica registrada.\n";
        }

        // Formatting history
        $historyStr = "";
        foreach ($historial as $h) {
            $historyStr .= "- [" . ($h['fecha_hora'] ?? '') . "] Accion: " . ($h['tipo_accion'] ?? '') . " | " . ($h['descripcion'] ?? '') . "\n";
        }
        if (empty($historyStr)) {
            $historyStr = "Ningún historial de movimientos registrado.\n";
        }

        // Formatting equipment info
        $eqDetails = "Código Informática: {$equipoData['cod_informatica']}\n"
            . "Categoría: " . ($equipoData['categoria'] ?? 'equipo') . "\n"
            . "Tipo: " . ($equipoData['tipo'] ?? 'N/A') . "\n"
            . "Nombre/Hostname: " . ($equipoData['nombre'] ?? 'N/A') . "\n"
            . "Cuenta Local: " . ($equipoData['nombre_usuario'] ?? 'N/A') . "\n"
            . "Clasificación/Calidad: " . ($equipoData['clasificacion'] ?? 'N/A') . "\n"
            . "Dirección IP: " . ($equipoData['ip'] ?? 'N/A') . "\n"
            . "Estado Actual: " . ($equipoData['estado'] ?? 'N/A') . "\n"
            . "Vida útil en años: " . ($equipoData['vida_util_anios'] ?? 'N/A') . "\n"
            . "Observación Técnica: " . ($equipoData['observacion_tecnica'] ?? 'N/A') . "\n";

        $systemPrompt = "Eres un Asistente Técnico y Experto en Soporte de TI de alta experiencia en la UGEL. Tu tarea es analizar la información, características técnicas e historial de cambios de un equipo tecnológico para emitir un diagnóstico profesional respecto al problema reportado por el usuario.\n\n"
            . "### CONTEXTO DEL EQUIPO:\n"
            . "{$eqDetails}\n"
            . "### ESPECIFICACIONES TÉCNICAS (CARACTERÍSTICAS):\n"
            . "{$specs}\n"
            . "### HISTORIAL RECIENTE DE CAMBIOS:\n"
            . "{$historyStr}\n\n"
            . "### PROBLEMA DETECTADO / REPORTADO POR EL OPERADOR:\n"
            . "\"{$problema}\"\n\n"
            . "### REGLAS PARA EL INFORME DE DIAGNÓSTICO:\n"
            . "1. Sé extremadamente técnico, conciso y profesional en tu redacción.\n"
            . "2. Ofrece 3 posibles causas ordenadas por probabilidad, vinculándolas a los datos técnicos, la antigüedad o al historial si es relevante.\n"
            . "3. Ofrece pasos de solución inmediatos ordenados y claros.\n"
            . "4. Recomienda acciones preventivas a largo plazo (por ejemplo, si su vida útil está por expirar o su clasificación es MALO).\n"
            . "5. Formatea la respuesta con Markdown elegante usando negritas, listas ordenadas/desordenadas y secciones claras. Evita saludos introductorios largos y ve al grano.";

        return trim($this->ai->generateText($systemPrompt));
    }
}

