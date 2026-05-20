<?php

namespace App\Http\Controllers;

use App\Models\Persona;
use App\Models\Oficina;
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
        $personas = Persona::select('id', 'nombre_completo', 'celular', 'correo', 'cargo', 'estado', 'id_oficina', 'created_at')
            ->with(['oficina.area', 'equipos'])
            ->withCount('equipos')
            ->orderBy('nombre_completo')
            ->get();

        $oficinas = Oficina::with('area:id,nombre')
            ->select('id', 'nombre', 'area_id')
            ->orderBy('nombre')
            ->get();

        return Inertia::render('Personas/Index', [
            'personas' => $personas,
            'oficinas' => $oficinas,
        ]);
    }

    /**
     * Crea una nueva persona.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'nombre_completo' => ['required', 'string', 'max:255'],
            'celular' => ['nullable', 'string', 'max:20'],
            'correo' => ['nullable', 'email', 'max:255'],
            'cargo' => ['nullable', 'string', 'max:255'],
            'estado' => ['nullable', 'in:ACTIVO,INACTIVO'],
            'id_oficina' => ['required', 'integer', 'exists:oficinas,id'],
        ], [
            'nombre_completo.required' => 'El nombre completo es obligatorio.',
            'id_oficina.required' => 'La oficina es obligatoria.',
            'id_oficina.exists' => 'La oficina seleccionada no es válida.',
        ]);

        $data['estado'] = $data['estado'] ?? 'ACTIVO';

        $persona = Persona::create($data);

        return response()->json([
            'message' => 'Persona creada correctamente.',
            'data' => $persona->fresh()->load('oficina.area')->loadCount('equipos'),
        ], 201);
    }

    /**
     * Retorna la información de una persona específica.
     */
    public function show(Persona $persona): JsonResponse
    {
        return response()->json([
            'data' => $persona->load('oficina.area'),
        ]);
    }

    /**
     * Actualiza una persona existente.
     */
    public function update(Request $request, Persona $persona): JsonResponse
    {
        $data = $request->validate([
            'nombre_completo' => ['required', 'string', 'max:255'],
            'celular' => ['nullable', 'string', 'max:20'],
            'correo' => ['nullable', 'email', 'max:255'],
            'cargo' => ['nullable', 'string', 'max:255'],
            'estado' => ['required', 'in:ACTIVO,INACTIVO'],
            'id_oficina' => ['required', 'integer', 'exists:oficinas,id'],
        ], [
            'nombre_completo.required' => 'El nombre completo es obligatorio.',
            'id_oficina.required' => 'La oficina es obligatoria.',
            'id_oficina.exists' => 'La oficina seleccionada no es válida.',
        ]);

        $persona->update($data);

        return response()->json([
            'message' => 'Persona actualizada correctamente.',
            'data' => $persona->fresh()->load('oficina.area')->loadCount('equipos'),
        ]);
    }

    /**
     * Elimina una persona.
     */
    public function destroy(Persona $persona): JsonResponse
    {
        $nuevoEstado = $persona->estado === 'ACTIVO' ? 'INACTIVO' : 'ACTIVO';

        if ($nuevoEstado === 'INACTIVO') {
            // Liberar equipos
            $persona->equipos()->update([
                'id_persona' => null,
                'estado' => 'LIBRE'
            ]);
        }

        $persona->update(['estado' => $nuevoEstado]);

        return response()->json([
            'message' => 'Estado de la cuenta actualizado correctamente.',
            'data' => $persona->fresh()->load('oficina.area')->loadCount('equipos'),
        ]);
    }
}
