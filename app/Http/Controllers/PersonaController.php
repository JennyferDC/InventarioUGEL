<?php

namespace App\Http\Controllers;

use App\Models\Persona;
use App\Models\Area;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PersonaController extends Controller
{
    /**
     * Muestra la vista principal de personas.
     */
    public function index(): Response
    {
        $personas = Persona::with('area:id,nombre')
            ->select('id', 'nombre_completo', 'id_area', 'created_at')
            ->orderBy('nombre_completo')
            ->get();

        $areas = Area::select('id', 'nombre')
            ->orderBy('nombre')
            ->get();

        return Inertia::render('Personas/Index', [
            'personas' => $personas,
            'areas' => $areas,
        ]);
    }

    /**
     * Crea una nueva persona.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'nombre_completo' => ['required', 'string', 'max:255'],
            'id_area' => ['required', 'integer', 'exists:areas,id'],
        ]);

        $persona = Persona::create($data);

        return response()->json([
            'message' => 'Persona creada correctamente.',
            'data' => $persona->load('area:id,nombre'),
        ], 201);
    }

    /**
     * Retorna la información de una persona específica.
     */
    public function show(Persona $persona): JsonResponse
    {
        return response()->json([
            'data' => $persona->load('area:id,nombre'),
        ]);
    }

    /**
     * Actualiza una persona existente.
     */
    public function update(Request $request, Persona $persona): JsonResponse
    {
        $data = $request->validate([
            'nombre_completo' => ['required', 'string', 'max:255'],
            'id_area' => ['required', 'integer', 'exists:areas,id'],
        ]);

        $persona->update($data);

        return response()->json([
            'message' => 'Persona actualizada correctamente.',
            'data' => $persona->fresh()->load('area:id,nombre'),
        ]);
    }

    /**
     * Elimina una persona.
     */
    public function destroy(Persona $persona): JsonResponse
    {
        $persona->delete();

        return response()->json([
            'message' => 'Persona eliminada correctamente.',
        ]);
    }
}
