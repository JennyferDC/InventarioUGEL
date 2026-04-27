<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Equipo;
use App\Models\Persona;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class EquipoController extends Controller
{
    /**
     * Muestra la vista principal del inventario de equipos.
     */
    public function index(): Response
    {
        $equipos = Equipo::with(['persona:id,nombre_completo,id_oficina,celular', 'persona.oficina.area:id,nombre'])
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

        $personas = Persona::with('oficina:id,nombre,area_id', 'oficina.area:id,nombre')
            ->select('id', 'nombre_completo', 'id_oficina')
            ->orderBy('nombre_completo')
            ->get();

        $areas = Area::select('id', 'nombre')->orderBy('nombre')->get();

        return Inertia::render('Inventario/Index', [
            'equipos' => $equipos,
            'personas' => $personas,
            'areas' => $areas,
        ]);
    }

    public function showByCodigo($cod_informatica): Response
    {
        $equipo = Equipo::with([
            'persona:id,nombre_completo,id_oficina,celular', 
            'persona.oficina.area:id,nombre', 
            'caracteristicas:id,clave,valor,id_equipo'
        ])
        ->where('cod_informatica', $cod_informatica)
        ->firstOrFail();

        $otrosEquipos = [];
        if ($equipo->id_persona) {
            $otrosEquipos = Equipo::where('id_persona', $equipo->id_persona)
                ->where('id', '!=', $equipo->id)
                ->select('id', 'cod_informatica', 'tipo', 'estado')
                ->get();
        }

        $personas = Persona::with('oficina:id,nombre,area_id', 'oficina.area:id,nombre')
            ->select('id', 'nombre_completo', 'id_oficina')
            ->orderBy('nombre_completo')
            ->get();

        $areas = Area::select('id', 'nombre')->orderBy('nombre')->get();

        return Inertia::render('Inventario/Show', [
            'equipo' => $equipo,
            'otrosEquipos' => $otrosEquipos,
            'personas' => $personas,
            'areas' => $areas,
        ]);
    }

    /**
     * Crea un nuevo equipo.
     */
    public function store(Request $request): JsonResponse
    {
        $messages = [
            'cod_informatica.required' => 'El código de informática es obligatorio.',
            'cod_informatica.unique' => 'Este código de informática ya está en uso.',
            'tipo.required' => 'El tipo de equipo es obligatorio.',
            'estado.required' => 'El estado es obligatorio.',
            'id_persona.exists' => 'El responsable seleccionado no es válido.',
            'vida_util_anios.min' => 'La vida útil debe ser mínimo 0 años.',
            'fecha_disponible_uso.date' => 'La fecha no tiene un formato válido.',
            'caracteristicas.*.clave.required_with' => 'La clave es requerida si hay valor.',
            'caracteristicas.*.valor.required_with' => 'El valor es requerido si hay clave.',
        ];

        $data = $request->validate([
            'cod_informatica' => ['required', 'string', 'max:255', 'unique:equipos,cod_informatica'],
            'tipo' => ['required', 'string', 'max:255'],
            'estado' => ['required', 'string', 'max:255'],
            'fecha_ingreso' => ['nullable', 'date'],
            'fecha_disponible_uso' => ['nullable', 'date'],
            'vida_util_anios' => ['nullable', 'integer', 'min:0'],
            'id_persona' => ['nullable', 'exists:personas,id'],
            'caracteristicas' => ['nullable', 'array'],
            'caracteristicas.*.clave' => ['required_with:caracteristicas.*.valor', 'string', 'max:255'],
            'caracteristicas.*.valor' => ['required_with:caracteristicas.*.clave', 'string', 'max:255'],
        ], $messages);

        $caracteristicas = $data['caracteristicas'] ?? [];
        unset($data['caracteristicas']);

        $equipo = DB::transaction(function () use ($data, $caracteristicas) {
            $equipo = Equipo::create($data);

            $caracteristicasPayload = collect($caracteristicas)
                ->filter(fn ($item) => is_array($item) && trim((string)($item['clave'] ?? '')) !== '' && trim((string)($item['valor'] ?? '')) !== '')
                ->map(fn ($item) => [
                    'clave' => $item['clave'],
                    'valor' => $item['valor'],
                ])
                ->values()
                ->all();

            if (count($caracteristicasPayload) > 0) {
                $equipo->caracteristicas()->createMany($caracteristicasPayload);
            }

            return $equipo;
        });

        $equipo->load(['persona:id,nombre_completo,id_oficina,celular', 'persona.oficina.area:id,nombre', 'caracteristicas:id,clave,valor,id_equipo']);

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
            'data' => $equipo->load(['persona:id,nombre_completo,id_oficina,celular', 'persona.oficina.area:id,nombre', 'caracteristicas:id,clave,valor,id_equipo']),
        ]);
    }

    /**
     * Actualiza un equipo existente.
     */
    public function update(Request $request, Equipo $equipo)
    {
        $messages = [
            'cod_informatica.required' => 'El código de informática es obligatorio.',
            'cod_informatica.unique' => 'Este código de informática ya está en uso.',
            'tipo.required' => 'El tipo de equipo es obligatorio.',
            'estado.required' => 'El estado es obligatorio.',
            'id_persona.exists' => 'El responsable seleccionado no es válido.',
            'vida_util_anios.min' => 'La vida útil debe ser mínimo 0 años.',
            'fecha_disponible_uso.date' => 'La fecha no tiene un formato válido.',
            'caracteristicas.*.clave.required_with' => 'La clave es requerida si hay valor.',
            'caracteristicas.*.valor.required_with' => 'El valor es requerido si hay clave.',
        ];

        $data = $request->validate([
            'cod_informatica' => ['required', 'string', 'max:255', 'unique:equipos,cod_informatica,' . $equipo->id],
            'tipo' => ['required', 'string', 'max:255'],
            'estado' => ['required', 'string', 'max:255'],
            'fecha_ingreso' => ['nullable', 'date'],
            'fecha_disponible_uso' => ['nullable', 'date'],
            'vida_util_anios' => ['nullable', 'integer', 'min:0'],
            'id_persona' => ['nullable', 'exists:personas,id'],
            'caracteristicas' => ['nullable', 'array'],
            'caracteristicas.*.clave' => ['required_with:caracteristicas.*.valor', 'string', 'max:255'],
            'caracteristicas.*.valor' => ['required_with:caracteristicas.*.clave', 'string', 'max:255'],
        ], $messages);

        $caracteristicas = $data['caracteristicas'] ?? null;
        unset($data['caracteristicas']);

        DB::transaction(function () use ($equipo, $data, $caracteristicas) {
            $equipo->update($data);

            if (is_array($caracteristicas)) {
                $equipo->caracteristicas()->delete();

                $caracteristicasPayload = collect($caracteristicas)
                    ->filter(fn ($item) => is_array($item) && trim((string)($item['clave'] ?? '')) !== '' && trim((string)($item['valor'] ?? '')) !== '')
                    ->map(fn ($item) => [
                        'clave' => $item['clave'],
                        'valor' => $item['valor'],
                    ])
                    ->values()
                    ->all();

                if (count($caracteristicasPayload) > 0) {
                    $equipo->caracteristicas()->createMany($caracteristicasPayload);
                }
            }
        });

        if ($request->header('X-Inertia')) {
            return redirect()->back();
        }

        return response()->json([
            'message' => 'Equipo actualizado correctamente.',
            'data' => $equipo->fresh()->load(['persona:id,nombre_completo,id_oficina,celular', 'persona.oficina.area:id,nombre', 'caracteristicas:id,clave,valor,id_equipo']),
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
