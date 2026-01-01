<?php

namespace App\Http\Controllers;

use App\Models\Equipo;
use App\Models\Persona;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EquipoController extends Controller
{
    /**
     * Muestra la vista principal del inventario de equipos.
     */
    public function index(): Response
    {
        $equipos = Equipo::with('persona:id,nombre_completo')
            ->select(
                'id',
                'cod_informatica',
                'tipo',
                'estado',
                'fecha_disponible_uso',
                'vida_util_anios',
                'id_persona'
            )
            ->orderBy('cod_informatica')
            ->get();

        $personas = Persona::select('id', 'nombre_completo')
            ->orderBy('nombre_completo')
            ->get();

        return Inertia::render('Inventario/Index', [
            'equipos' => $equipos,
            'personas' => $personas,
        ]);
    }

    /**
     * Crea un nuevo equipo.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'cod_informatica' => ['required', 'string', 'max:255', 'unique:equipos,cod_informatica'],
            'tipo' => ['required', 'string', 'max:255'],
            'estado' => ['required', 'string', 'max:255'],
            'fecha_disponible_uso' => ['nullable', 'date'],
            'vida_util_anios' => ['nullable', 'integer', 'min:0'],
            'id_persona' => ['nullable', 'exists:personas,id'],
        ]);

        $equipo = Equipo::create($data)->load('persona:id,nombre_completo');

        return response()->json([
            'message' => 'Equipo registrado correctamente.',
            'data' => $equipo,
        ], 201);
    }

    /**
     * Muestra un equipo específico.
     */
    public function show(Equipo $equipo): JsonResponse
    {
        return response()->json([
            'data' => $equipo->load('persona:id,nombre_completo'),
        ]);
    }

    /**
     * Actualiza un equipo existente.
     */
    public function update(Request $request, Equipo $equipo): JsonResponse
    {
        $data = $request->validate([
            'cod_informatica' => ['required', 'string', 'max:255', 'unique:equipos,cod_informatica,' . $equipo->id],
            'tipo' => ['required', 'string', 'max:255'],
            'estado' => ['required', 'string', 'max:255'],
            'fecha_disponible_uso' => ['nullable', 'date'],
            'vida_util_anios' => ['nullable', 'integer', 'min:0'],
            'id_persona' => ['nullable', 'exists:personas,id'],
        ]);

        $equipo->update($data);

        return response()->json([
            'message' => 'Equipo actualizado correctamente.',
            'data' => $equipo->fresh()->load('persona:id,nombre_completo'),
        ]);
    }

    /**
     * Elimina un equipo.
     */
    public function destroy(Equipo $equipo): JsonResponse
    {
        $equipo->delete();

        return response()->json([
            'message' => 'Equipo eliminado correctamente.',
        ]);
    }
}
