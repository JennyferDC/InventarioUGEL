<?php

namespace App\Http\Controllers;

use App\Models\ComentarioEquipo;
use App\Models\Equipo;
use App\Models\HistorialMovimiento;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ComentarioEquipoController extends Controller
{
    /**
     * Almacena un nuevo comentario asociado a un equipo.
     */
    public function store(Request $request, Equipo $equipo): JsonResponse
    {
        $validated = $request->validate([
            'comentario' => ['required', 'string', 'max:2000'],
        ], [
            'comentario.required' => 'El comentario no puede estar vacío.',
            'comentario.max' => 'El comentario no puede exceder los 2000 caracteres.',
        ]);

        $comentario = $equipo->comentarios()->create([
            'comentario' => trim($validated['comentario']),
            'id_usuario' => auth()->id(),
        ]);

        $comentario->load('usuario:id,name,email');

        // Registro en auditoría
        HistorialMovimiento::create([
            'tipo_accion' => 'MODIFICACION',
            'descripcion' => 'Se agregó un nuevo comentario en el registro del ' . (strtolower($equipo->categoria) === 'programa' ? 'programa' : 'equipo') . '.',
            'fecha_hora' => now(),
            'id_usuario' => auth()->id(),
            'id_equipo' => $equipo->id,
        ]);

        return response()->json([
            'message' => 'Comentario publicado correctamente.',
            'data' => $comentario,
        ], 201);
    }

    /**
     * Elimina un comentario existente.
     */
    public function destroy(ComentarioEquipo $comentario): JsonResponse
    {
        // Permitir eliminar al autor del comentario o a usuarios con rol ADMIN
        if ($comentario->id_usuario !== auth()->id() && strtoupper(auth()->user()->rol ?? '') !== 'ADMIN') {
            return response()->json([
                'message' => 'No tienes permiso para eliminar este comentario.',
            ], 403);
        }

        $equipoId = $comentario->id_equipo;
        $comentario->delete();

        return response()->json([
            'message' => 'Comentario eliminado correctamente.',
            'equipo_id' => $equipoId,
        ]);
    }
}
