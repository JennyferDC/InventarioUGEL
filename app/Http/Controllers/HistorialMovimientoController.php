<?php

namespace App\Http\Controllers;

use App\Models\Equipo;
use App\Models\HistorialMovimiento;
use Illuminate\Http\JsonResponse;

class HistorialMovimientoController extends Controller
{
    /**
     * Obtiene el historial de cambios de un equipo o programa.
     */
    public function getHistorial(Equipo $equipo): JsonResponse
    {
        $historial = HistorialMovimiento::with('usuario:id,name,email')
            ->where('id_equipo', $equipo->id)
            ->orderBy('fecha_hora', 'desc')
            ->get();

        return response()->json([
            'data' => $historial
        ]);
    }
}
