<?php

namespace App\Http\Controllers;

use App\Services\AI\AITasksService;
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
}
