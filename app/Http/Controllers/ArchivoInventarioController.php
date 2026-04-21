<?php

namespace App\Http\Controllers;

use App\Models\ArchivoInventario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ArchivoInventarioController extends Controller
{
    /**
     * Vista principal de Archivos.
     */
    public function index(): Response
    {
        $archivos = ArchivoInventario::with('usuario:id,name,email')
            ->select('id', 'nombre_archivo', 'ruta', 'fecha_carga', 'id_usuario', 'created_at')
            ->orderByDesc('fecha_carga')
            ->orderByDesc('id')
            ->get()
            ->map(function ($archivo) {
                $archivo->url = $archivo->ruta ? asset('storage/' . $archivo->ruta) : null;
                return $archivo;
            });

        return Inertia::render('Archivos/Index', [
            'archivos' => $archivos,
        ]);
    }

    /**
     * Crear archivo (subida).
     */
    public function store(Request $request): JsonResponse
    {
        $userId = (int) $request->user()->id;

        if ($request->hasFile('archivos')) {
            $data = $request->validate([
                'archivos' => ['required', 'array', 'min:1'],
                'archivos.*' => ['required', 'file', 'max:20480'],
                'nombres' => ['required', 'array'],
                'nombres.*' => ['required', 'string', 'max:255'],
                'fechas' => ['required', 'array'],
                'fechas.*' => ['required', 'date'],
            ]);

            $files = $request->file('archivos');
            $created = DB::transaction(function () use ($files, $data, $userId) {
                $items = [];
                foreach ($files as $index => $file) {
                    $path = $file->store('archivos', 'public');

                    $items[] = ArchivoInventario::create([
                        'nombre_archivo' => $data['nombres'][$index] ?? '',
                        'ruta' => $path,
                        'fecha_carga' => $data['fechas'][$index] ?? now()->toDateString(),
                        'id_usuario' => $userId,
                    ]);
                }
                return $items;
            });

            $created = collect($created)
                ->each(function ($archivo) {
                    $archivo->load('usuario:id,name,email');
                    $archivo->url = $archivo->ruta ? asset('storage/' . $archivo->ruta) : null;
                })
                ->values();

            return response()->json([
                'message' => 'Archivos cargados correctamente.',
                'data' => $created,
            ], 201);
        }

        $data = $request->validate([
            'archivo' => ['required', 'file', 'max:20480'],
            'nombre_archivo' => ['required', 'string', 'max:255'],
            'fecha_carga' => ['required', 'date'],
        ]);

        $archivoInventario = DB::transaction(function () use ($data, $userId) {
            $file = $data['archivo'];
            $path = $file->store('archivos', 'public');

            return ArchivoInventario::create([
                'nombre_archivo' => $data['nombre_archivo'],
                'ruta' => $path,
                'fecha_carga' => $data['fecha_carga'],
                'id_usuario' => $userId,
            ]);
        });

        $archivoInventario->load('usuario:id,name,email');
        $archivoInventario->url = $archivoInventario->ruta
            ? asset('storage/' . $archivoInventario->ruta)
            : null;

        return response()->json([
            'message' => 'Archivo cargado correctamente.',
            'data' => $archivoInventario,
        ], 201);
    }

    /**
     * Mostrar archivo.
     */
    public function show(ArchivoInventario $archivoInventario): JsonResponse
    {
        $archivoInventario->load('usuario:id,name,email');
        $archivoInventario->url = $archivoInventario->ruta
            ? asset('storage/' . $archivoInventario->ruta)
            : null;

        return response()->json([
            'data' => $archivoInventario,
        ]);
    }

    /**
     * Actualizar (solo nombre + fecha opcional).
     */
    public function update(Request $request, ArchivoInventario $archivoInventario): JsonResponse
    {
        $data = $request->validate([
            'nombre_archivo' => ['required', 'string', 'max:255'],
            'fecha_carga' => ['nullable', 'date'],
        ]);

        $archivoInventario->update($data);

        $archivoInventario->load('usuario:id,name,email');
        $archivoInventario->url = $archivoInventario->ruta
            ? asset('storage/' . $archivoInventario->ruta)
            : null;

        return response()->json([
            'message' => 'Archivo actualizado correctamente.',
            'data' => $archivoInventario,
        ]);
    }

    /**
     * Eliminar.
     */
    public function destroy(ArchivoInventario $archivoInventario): JsonResponse
    {
        DB::transaction(function () use ($archivoInventario) {
            if ($archivoInventario->ruta) {
                Storage::disk('public')->delete($archivoInventario->ruta);
            }
            $archivoInventario->delete();
        });

        return response()->json([
            'message' => 'Archivo eliminado correctamente.',
        ]);
    }
}
