<?php

namespace App\Http\Controllers;

use App\Services\AI\AITasksService;
use App\Models\Equipo;
use Illuminate\Http\Request;

class AIController extends Controller
{
    protected AITasksService $aiTasks;

    public function __construct(AITasksService $aiTasks)
    {
        $this->aiTasks = $aiTasks;
    }

    /**
     * Endpoint para profesionalizar observaciones técnicas de baja con IA.
     */
    public function mejorarObservacionTecnica(Request $request)
    {
        $request->validate([
            'observacion' => ['required', 'string', 'max:5000'],
        ]);

        try {
            $resultado = $this->aiTasks->mejorarObservacionTecnica($request->input('observacion'));

            $metadata = null;
            $aiService = app(\App\Services\AI\Contracts\AIService::class);
            if ($aiService instanceof \App\Services\AI\AIManager) {
                $metadata = $aiService->getLastResponseMetadata();
            }

            return response()->json([
                'success' => true,
                'resultado' => trim($resultado, " \t\n\r\0\x0B\"'"),
                'provider' => $metadata['provider'] ?? 'Gemini',
                'model' => $metadata['model'] ?? 'gemini-2.5-flash',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'No se pudo procesar con IA: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Endpoint para generar un diagnóstico técnico de un equipo con IA.
     */
    public function diagnosticarEquipo(Request $request)
    {
        $request->validate([
            'equipo_id' => ['required', 'exists:equipos,id'],
            'problema' => ['required', 'string', 'max:5000'],
        ]);

        try {
            $equipo = Equipo::with([
                'caracteristicas',
                'programas',
                'persona.oficina.area',
                'historialMovimientos' => function ($query) {
                    $query->orderBy('fecha_hora', 'desc')->orderBy('id', 'desc')->take(15);
                }
            ])->findOrFail($request->input('equipo_id'));

            // Convert characteristics to array structure expected by the service
            $caracteristicas = $equipo->caracteristicas->map(function ($c) {
                return [
                    'clave' => $c->clave,
                    'valor' => $c->valor,
                ];
            })->toArray();

            // Convert history to array structure expected by the service
            $historial = $equipo->historialMovimientos->map(function ($h) {
                return [
                    'fecha_hora' => $h->fecha_hora ? $h->fecha_hora->format('Y-m-d H:i:s') : '',
                    'tipo_accion' => $h->tipo_accion,
                    'descripcion' => $h->descripcion,
                ];
            })->toArray();

            // Convert programs to array structure
            $programas = $equipo->programas->map(function ($p) {
                return [
                    'cod_informatica' => $p->cod_informatica,
                    'nombre' => $p->nombre,
                    'tipo' => $p->tipo,
                    'estado' => $p->estado,
                ];
            })->toArray();

            // Convert responsible person to array structure
            $responsable = null;
            if ($equipo->persona) {
                $responsable = [
                    'nombre_completo' => $equipo->persona->nombre_completo,
                    'cargo' => $equipo->persona->cargo,
                    'celular' => $equipo->persona->celular,
                    'correo' => $equipo->persona->correo,
                    'oficina' => $equipo->persona->oficina ? $equipo->persona->oficina->nombre : null,
                    'area' => ($equipo->persona->oficina && $equipo->persona->oficina->area) ? $equipo->persona->oficina->area->nombre : null,
                ];
            }

            $equipoData = $equipo->toArray();

            $resultado = $this->aiTasks->diagnosticarEquipo(
                $equipoData,
                $caracteristicas,
                $historial,
                $programas,
                $responsable,
                $request->input('problema')
            );

            $metadata = null;
            $aiService = app(\App\Services\AI\Contracts\AIService::class);
            if ($aiService instanceof \App\Services\AI\AIManager) {
                $metadata = $aiService->getLastResponseMetadata();
            }

            return response()->json([
                'success' => true,
                'resultado' => $resultado,
                'provider' => $metadata['provider'] ?? 'Gemini',
                'model' => $metadata['model'] ?? 'gemini-2.5-flash',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'No se pudo generar el diagnóstico con IA: ' . $e->getMessage(),
            ], 500);
        }
    }
}

