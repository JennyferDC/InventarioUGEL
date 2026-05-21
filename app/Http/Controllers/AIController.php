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

            return response()->json([
                'success' => true,
                'resultado' => trim($resultado, " \t\n\r\0\x0B\"'"),
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

            $equipoData = $equipo->toArray();

            $resultado = $this->aiTasks->diagnosticarEquipo(
                $equipoData,
                $caracteristicas,
                $historial,
                $request->input('problema')
            );

            return response()->json([
                'success' => true,
                'resultado' => $resultado,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'No se pudo generar el diagnóstico con IA: ' . $e->getMessage(),
            ], 500);
        }
    }
}

