<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UsuarioController extends Controller
{
    /**
     * Muestra la vista principal de miembros.
     */
    public function index(): Response
    {
        $miembros = User::query()
            ->select('id', 'name', 'email', 'rol', 'activo', 'created_at')
            ->orderBy('name')
            ->get();

        return Inertia::render('Miembros/Index', [
            'miembros' => $miembros,
        ]);
    }

    /**
     * Crea un nuevo miembro.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'rol' => ['required', 'in:ADMIN,MIEMBRO'],
            'activo' => ['nullable', 'boolean'],
        ]);

        if (!array_key_exists('activo', $data)) {
            $data['activo'] = true;
        }

        $miembro = User::create($data);

        return response()->json([
            'message' => 'Miembro creado correctamente.',
            'data' => $miembro->only(['id', 'name', 'email', 'rol', 'activo', 'created_at']),
        ], 201);
    }

    /**
     * Retorna la información de un miembro.
     */
    public function show(User $usuario): JsonResponse
    {
        return response()->json([
            'data' => $usuario->only(['id', 'name', 'email', 'rol', 'activo', 'created_at']),
        ]);
    }

    /**
     * Actualiza un miembro existente.
     */
    public function update(Request $request, User $usuario): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $usuario->id],
            'password' => ['nullable', 'string', 'min:8'],
            'rol' => ['required', 'in:ADMIN,MIEMBRO'],
            'activo' => ['required', 'boolean'],
        ]);

        if (empty($data['password'] ?? null)) {
            unset($data['password']);
        }

        $usuario->update($data);

        return response()->json([
            'message' => 'Miembro actualizado correctamente.',
            'data' => $usuario->fresh()->only(['id', 'name', 'email', 'rol', 'activo', 'created_at']),
        ]);
    }

    /**
     * Elimina un miembro.
     */
    public function destroy(User $usuario): JsonResponse
    {
        $usuario->delete();

        return response()->json([
            'message' => 'Miembro eliminado correctamente.',
        ]);
    }
}
