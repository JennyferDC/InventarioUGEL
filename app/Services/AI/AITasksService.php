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
     * Realiza un diagnóstico técnico de problemas en base a la información, características, historial, programas y responsable de un equipo.
     *
     * @param array $equipoData
     * @param array $caracteristicas
     * @param array $historial
     * @param array $programas
     * @param array|null $responsable
     * @param string $problema
     * @return string
     */
    public function diagnosticarEquipo(
        array $equipoData,
        array $caracteristicas,
        array $historial,
        array $programas,
        ?array $responsable,
        string $problema
    ): string {
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

        // Formatting programs
        $programsStr = "";
        foreach ($programas as $p) {
            $programsStr .= "- Código: " . ($p['cod_informatica'] ?? '') . " | Nombre: " . ($p['nombre'] ?? '') . " | Tipo: " . ($p['tipo'] ?? '') . " | Estado: " . ($p['estado'] ?? '') . "\n";
        }
        if (empty($programsStr)) {
            $programsStr = "Ningún programa de software asignado a este equipo.\n";
        }

        // Formatting responsible
        $respStr = "Sin asignar / Libre\n";
        if (!empty($responsable)) {
            $respStr = "- Nombre Completo: " . ($responsable['nombre_completo'] ?? 'N/A') . "\n"
                . "- Cargo: " . ($responsable['cargo'] ?? 'N/A') . "\n"
                . "- Celular: " . ($responsable['celular'] ?? 'N/A') . "\n"
                . "- Correo: " . ($responsable['correo'] ?? 'N/A') . "\n"
                . "- Oficina: " . ($responsable['oficina'] ?? 'N/A') . "\n"
                . "- Área: " . ($responsable['area'] ?? 'N/A') . "\n";
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

        $systemPrompt = "Eres un Asistente Técnico y Experto en Soporte de TI de alta experiencia en la UGEL. Tu tarea es analizar la información, características técnicas, programas instalados, responsable e historial de cambios de un equipo tecnológico para emitir un diagnóstico profesional o responder preguntas del usuario.\n\n"
            . "### CONTEXTO DEL EQUIPO:\n"
            . "{$eqDetails}\n"
            . "### RESPONSABLE ASIGNADO:\n"
            . "{$respStr}\n"
            . "### PROGRAMAS Y SOFTWARE INSTALADO:\n"
            . "{$programsStr}\n"
            . "### ESPECIFICACIONES TÉCNICAS (CARACTERÍSTICAS):\n"
            . "{$specs}\n"
            . "### HISTORIAL RECIENTE DE CAMBIOS:\n"
            . "{$historyStr}\n\n"
            . "### CONSULTA / PROBLEMA REPORTADO POR EL OPERADOR:\n"
            . "\"{$problema}\"\n\n"
            . "### REGLAS DE DECISIÓN DE INTENCIÓN:\n"
            . "1. Analiza cuidadosamente la consulta o problema reportado por el operador. Si NO describe un problema técnico ni solicita un diagnóstico técnico (ej. lentitud, falla de hardware, error de software, pantallazo azul, etc.), sino que realiza una consulta fáctica, general o administrativa sobre los datos provistos en el contexto (ej. \"¿quién es el responsable actual?\", \"¿cuál es la IP?\", \"¿qué programas tiene?\", \"¿cuándo se creó?\", etc.):\n"
            . "   - Inicia aclarando de forma breve, cortés y profesional que lo solicitado no corresponde a una descripción de falla técnica o diagnóstico de hardware/software, pero que con gusto le brindarás la respuesta precisa.\n"
            . "   - Responde la consulta exacta de forma directa y clara basándote únicamente en el contexto disponible (sin inventar ni alucinar datos).\n"
            . "   - NO incluyas causas probables, pasos de solución ni recomendaciones preventivas. Sé directo y de longitud breve.\n"
            . "2. Si la consulta describe un problema técnico o solicita un diagnóstico:\n"
            . "   - Ofrece 3 posibles causas ordenadas por probabilidad, vinculándolas a los datos técnicos, software instalado, antigüedad o al historial si es relevante.\n"
            . "   - Ofrece pasos de solución inmediatos ordenados y claros.\n"
            . "   - Recomienda acciones preventivas a largo plazo (por ejemplo, si su vida útil está por expirar o su clasificación es MALO).\n"
            . "   - Formatea la respuesta con Markdown elegante usando negritas, listas ordenadas/desordenadas y secciones claras. Evita saludos introductorios largos y ve al grano.";

        return trim($this->ai->generateText($systemPrompt));
    }
}

