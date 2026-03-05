<?php

namespace App\Http\Controllers;

use App\Models\CaracteristicaEquipo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CaracteristicaEquipoController extends Controller
{
    /**
     * Lista características. Puede filtrarse por id_equipo.
     */
    public function index(Request $request): JsonResponse
    {
        $query = CaracteristicaEquipo::query()
            ->select('id', 'clave', 'valor', 'id_equipo', 'created_at');

        if ($request->filled('id_equipo')) {
            $query->where('id_equipo', $request->integer('id_equipo'));
        }

        $caracteristicas = $query
            ->orderBy('clave')
            ->get();

        return response()->json([
            'data' => $caracteristicas,
        ]);
    }

    /**
     * Crea una nueva característica.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'clave' => ['required', 'string', 'max:255'],
            'valor' => ['required', 'string', 'max:255'],
            'id_equipo' => ['required', 'integer', 'exists:equipos,id'],
        ]);

        $caracteristica = CaracteristicaEquipo::create($data);

        return response()->json([
            'message' => 'Característica registrada correctamente.',
            'data' => $caracteristica,
        ], 201);
    }

    /**
     * Muestra una característica.
     */
    public function show(CaracteristicaEquipo $caracteristicaEquipo): JsonResponse
    {
        return response()->json([
            'data' => $caracteristicaEquipo,
        ]);
    }

    /**
     * Actualiza una característica.
     */
    public function update(Request $request, CaracteristicaEquipo $caracteristicaEquipo): JsonResponse
    {
        $data = $request->validate([
            'clave' => ['required', 'string', 'max:255'],
            'valor' => ['required', 'string', 'max:255'],
            'id_equipo' => ['required', 'integer', 'exists:equipos,id'],
        ]);

        $caracteristicaEquipo->update($data);

        return response()->json([
            'message' => 'Característica actualizada correctamente.',
            'data' => $caracteristicaEquipo->fresh(),
        ]);
    }

    /**
     * Elimina una característica.
     */
    public function destroy(CaracteristicaEquipo $caracteristicaEquipo): JsonResponse
    {
        $caracteristicaEquipo->delete();

        return response()->json([
            'message' => 'Característica eliminada correctamente.',
        ]);
    }
}
