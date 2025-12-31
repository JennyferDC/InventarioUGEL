<?php

namespace App\Http\Controllers;

use App\Models\Area;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AreaController extends Controller
{
    /**
     * Muestra la vista principal de áreas.
     */
    public function index(): Response
    {
        $areas = Area::select('id', 'nombre', 'descripcion')
            ->orderBy('nombre')
            ->get();

        return Inertia::render('Areas/Index', [
            'areas' => $areas,
        ]);
    }

    /**
     * Crea una nueva área.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:255', 'unique:areas,nombre'],
            'descripcion' => ['nullable', 'string'],
        ]);

        $area = Area::create($data);

        return response()->json([
            'message' => 'Área creada correctamente.',
            'data' => $area,
        ], 201);
    }

    /**
     * Retorna la información de un área específica.
     */
    public function show(Area $area): JsonResponse
    {
        return response()->json([
            'data' => $area,
        ]);
    }

    /**
     * Actualiza un área existente.
     */
    public function update(Request $request, Area $area): JsonResponse
    {
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:255', 'unique:areas,nombre,' . $area->id],
            'descripcion' => ['nullable', 'string'],
        ]);

        $area->update($data);

        return response()->json([
            'message' => 'Área actualizada correctamente.',
            'data' => $area->fresh(),
        ]);
    }

    /**
     * Elimina un área.
     */
    public function destroy(Area $area): JsonResponse
    {
        $area->delete();

        return response()->json([
            'message' => 'Área eliminada correctamente.',
        ]);
    }
}
