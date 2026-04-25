<?php

namespace App\Http\Controllers;

use App\Models\Oficina;
use App\Models\Area;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OficinaController extends Controller
{
    /**
     * Muestra la vista principal de oficinas.
     */
    public function index(): Response
    {
        $oficinas = Oficina::select('id', 'nombre', 'descripcion', 'area_id', 'created_at')
            ->with('area:id,nombre')
            ->orderBy('nombre')
            ->get();

        $areas = Area::select('id', 'nombre')
            ->orderBy('nombre')
            ->get();

        return Inertia::render('Oficinas/Index', [
            'oficinas' => $oficinas,
            'areas' => $areas,
        ]);
    }

    /**
     * Crea una nueva oficina.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string'],
            'area_id' => ['required', 'integer', 'exists:areas,id'],
        ]);

        $oficina = Oficina::create($data);

        return response()->json([
            'message' => 'Oficina creada correctamente.',
            'data' => $oficina->load('area:id,nombre'),
        ], 201);
    }

    /**
     * Retorna la información de una oficina específica.
     */
    public function show(Oficina $oficina): JsonResponse
    {
        return response()->json([
            'data' => $oficina->load('area:id,nombre'),
        ]);
    }

    /**
     * Actualiza una oficina existente.
     */
    public function update(Request $request, Oficina $oficina): JsonResponse
    {
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string'],
            'area_id' => ['required', 'integer', 'exists:areas,id'],
        ]);

        $oficina->update($data);

        return response()->json([
            'message' => 'Oficina actualizada correctamente.',
            'data' => $oficina->fresh()->load('area:id,nombre'),
        ]);
    }

    /**
     * Elimina una oficina.
     */
    public function destroy(Oficina $oficina): JsonResponse
    {
        $oficina->delete();

        return response()->json([
            'message' => 'Oficina eliminada correctamente.',
        ]);
    }
}
