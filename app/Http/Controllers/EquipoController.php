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
    public function index(Request $request): Response
    {
        $categoriaInicial = $request->is('inventario/programas*') ? 'programa' : 'equipo';

        $equipos = Equipo::with(['persona:id,nombre_completo,id_oficina,celular,correo,cargo', 'persona.oficina.area:id,nombre'])
            ->select(
                'id',
                'cod_informatica',
                'cod_patrimonial',
                'nombre',
                'nombre_usuario',
                'tipo',
                'estado',
                'fecha_disponible_uso',
                'vida_util_anios',
                'id_persona',
                'observacion_tecnica',
                'categoria',
                'ip',
                'clasificacion'
            )
            ->orderBy('cod_informatica')
            ->get();

        $personas = Persona::with('oficina:id,nombre,area_id', 'oficina.area:id,nombre')
            ->select('id', 'nombre_completo', 'id_oficina', 'estado')
            ->orderBy('nombre_completo')
            ->get();

        $areas = Area::select('id', 'nombre')->orderBy('nombre')->get();

        return Inertia::render('Inventario/Index', [
            'equipos' => $equipos,
            'personas' => $personas,
            'areas' => $areas,
            'categoriaInicial' => $categoriaInicial,
        ]);
    }

    public function showByCodigo($cod_informatica): Response
    {
        $equipo = Equipo::with([
            'persona:id,nombre_completo,id_oficina,celular,correo,cargo', 
            'persona.oficina.area:id,nombre', 
            'caracteristicas:id,clave,valor,id_equipo',
            'programas:id,cod_informatica,nombre,tipo,estado'
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
            ->select('id', 'nombre_completo', 'id_oficina', 'estado')
            ->orderBy('nombre_completo')
            ->get();

        $areas = Area::select('id', 'nombre')->orderBy('nombre')->get();

        // Get all software programs available for transfer/association
        $programasDisponibles = Equipo::where('categoria', 'programa')
            ->select('id', 'cod_informatica', 'nombre', 'tipo', 'estado')
            ->orderBy('nombre')
            ->get();

        return Inertia::render('Inventario/Show', [
            'equipo' => $equipo,
            'otrosEquipos' => $otrosEquipos,
            'personas' => $personas,
            'areas' => $areas,
            'programasDisponibles' => $programasDisponibles,
        ]);
    }

    /**
     * Crea un nuevo equipo.
     */
    public function store(Request $request): JsonResponse
    {
        $messages = [
            'tipo.required' => 'El tipo es obligatorio.',
            'id_persona.exists' => 'El responsable seleccionado no es válido.',
            'vida_util_anios.min' => 'La vida útil debe ser mínimo 0 años.',
            'fecha_disponible_uso.date' => 'La fecha no tiene un formato válido.',
            'caracteristicas.*.clave.required_with' => 'La clave es requerida si hay valor.',
            'caracteristicas.*.valor.required_with' => 'El valor es requerido si hay clave.',
        ];

        $data = $request->validate([
            'cod_patrimonial' => ['nullable', 'string', 'max:255'],
            'nombre' => ['nullable', 'string', 'max:255'],
            'nombre_usuario' => ['nullable', 'string', 'max:255'],
            'tipo' => ['required', 'string', 'max:255'],
            'estado' => ['nullable', 'string', 'max:255'],
            'fecha_ingreso' => ['nullable', 'date'],
            'fecha_disponible_uso' => ['nullable', 'date'],
            'vida_util_anios' => ['nullable', 'integer', 'min:0'],
            'id_persona' => ['nullable', 'exists:personas,id'],
            'caracteristicas' => ['nullable', 'array'],
            'caracteristicas.*.clave' => ['required_with:caracteristicas.*.valor', 'string', 'max:255'],
            'caracteristicas.*.valor' => ['required_with:caracteristicas.*.clave', 'string', 'max:255'],
            'observacion_tecnica' => ['nullable', 'string'],
            'categoria' => ['nullable', 'string', 'max:255'],
            'ip' => ['nullable', 'string', 'max:255'],
            'clasificacion' => ['nullable', 'string', 'max:255'],
        ], $messages);

        $data['categoria'] = $data['categoria'] ?? 'equipo';

        if (($data['estado'] ?? '') !== 'BAJA') {
            $data['observacion_tecnica'] = null;
        }
        if (!in_array(strtoupper($data['tipo'] ?? ''), ['PC', 'LAPTOP', 'TODO EN UNO'])) {
            $data['ip'] = null;
        }

        $caracteristicas = $data['caracteristicas'] ?? [];
        unset($data['caracteristicas']);

        $equipo = DB::transaction(function () use ($data, $caracteristicas) {
            $ultimoId = DB::table('equipos')->lockForUpdate()->max('id') ?? 0;
            $nuevoId = $ultimoId + 1;

            $categoria = strtolower($data['categoria'] ?? 'equipo');
            $tipo = strtolower($data['tipo'] ?? '');

            $abrevCat = ($categoria === 'programa') ? 'PRO' : 'EQU';

            $abrevTipoMap = [
                'pc' => 'PC',
                'laptop' => 'LAP',
                'todo en uno' => 'TEU',
                'monitor' => 'MON',
                'teclado' => 'TEC',
                'mouse' => 'MOU',
                'otro' => 'OTR',
                'otro (equipos)' => 'OTR',
                'institucional' => 'INS',
                'navegador' => 'NAV',
                'ofimática' => 'OFI',
                'ofimatica' => 'OFI',
                'soporte' => 'SOP',
                'antivirus' => 'ANT',
                'otro (programas)' => 'OTP',
            ];

            $abrevTipo = $abrevTipoMap[$tipo] ?? (($categoria === 'programa') ? 'OTP' : 'OTR');

            $data['cod_informatica'] = $abrevCat . $abrevTipo . $nuevoId;

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

            // Log Creation
            $desc = 'Se registró el ' . (strtolower($equipo->categoria) === 'programa' ? 'programa' : 'equipo') . ' en el inventario.';
            if (count($caracteristicasPayload) > 0) {
                $desc .= ' Con ' . count($caracteristicasPayload) . ' características técnicas iniciales.';
            }

            \App\Models\HistorialMovimiento::create([
                'tipo_accion' => 'CREACION',
                'descripcion' => $desc,
                'fecha_hora' => now(),
                'id_usuario' => auth()->id(),
                'id_equipo' => $equipo->id,
            ]);

            return $equipo;
        });

        $equipo->load(['persona:id,nombre_completo,id_oficina,celular,correo,cargo', 'persona.oficina.area:id,nombre', 'caracteristicas:id,clave,valor,id_equipo']);

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
            'data' => $equipo->load(['persona:id,nombre_completo,id_oficina,celular,correo,cargo', 'persona.oficina.area:id,nombre', 'caracteristicas:id,clave,valor,id_equipo']),
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
            'cod_informatica' => ['nullable', 'string', 'max:255', 'unique:equipos,cod_informatica,' . $equipo->id],
            'cod_patrimonial' => ['nullable', 'string', 'max:255'],
            'nombre' => ['nullable', 'string', 'max:255'],
            'nombre_usuario' => ['nullable', 'string', 'max:255'],
            'tipo' => ['required', 'string', 'max:255'],
            'estado' => ['nullable', 'string', 'max:255'],
            'fecha_ingreso' => ['nullable', 'date'],
            'fecha_disponible_uso' => ['nullable', 'date'],
            'vida_util_anios' => ['nullable', 'integer', 'min:0'],
            'id_persona' => ['nullable', 'exists:personas,id'],
            'caracteristicas' => ['nullable', 'array'],
            'caracteristicas.*.clave' => ['required_with:caracteristicas.*.valor', 'string', 'max:255'],
            'caracteristicas.*.valor' => ['required_with:caracteristicas.*.clave', 'string', 'max:255'],
            'observacion_tecnica' => ['nullable', 'string'],
            'categoria' => ['nullable', 'string', 'max:255'],
            'ip' => ['nullable', 'string', 'max:255'],
            'clasificacion' => ['nullable', 'string', 'max:255'],
        ], $messages);

        if (($data['estado'] ?? '') !== 'BAJA') {
            $data['observacion_tecnica'] = null;
        }
        if (!in_array(strtoupper($data['tipo'] ?? ''), ['PC', 'LAPTOP', 'TODO EN UNO'])) {
            $data['ip'] = null;
        }

        $caracteristicas = $data['caracteristicas'] ?? null;
        unset($data['caracteristicas']);

        DB::transaction(function () use ($equipo, $data, $caracteristicas) {
            // 1. Capture old characteristics map
            $oldCharsMap = $equipo->caracteristicas->pluck('valor', 'clave')->toArray();

            // 2. Capture old attributes for detail comparison
            $originalAttributes = $equipo->getOriginal();

            // 3. Perform update
            $equipo->update($data);

            // 4. Capture changes in the model
            $changes = $equipo->getChanges();
            $descriptions = [];

            // Helper lists for beautiful labels and relation resolving
            $fieldLabels = [
                'cod_patrimonial' => 'CÓDIGO PATRIMONIAL',
                'nombre' => strtolower($equipo->categoria) === 'programa' ? 'NOMBRE DEL PROGRAMA' : 'NOMBRE DEL EQUIPO',
                'nombre_usuario' => 'CUENTA',
                'tipo' => 'TIPO',
                'estado' => 'ESTADO',
                'fecha_ingreso' => 'FECHA DE INGRESO',
                'fecha_disponible_uso' => 'DISPONIBLE DESDE',
                'vida_util_anios' => 'VIDA ÚTIL (AÑOS)',
                'ip' => 'DIRECCIÓN IP',
                'clasificacion' => 'CLASIFICACIÓN',
                'observacion_tecnica' => 'OBSERVACIÓN TÉCNICA',
            ];

            foreach ($changes as $field => $newValue) {
                if ($field === 'updated_at') continue;
                $oldValue = $originalAttributes[$field] ?? null;

                if ($field === 'id_persona') {
                    $oldPersonName = $oldValue ? (\App\Models\Persona::find($oldValue)?->nombre_completo ?? 'Ninguno') : 'Ninguno';
                    $newPersonName = $newValue ? (\App\Models\Persona::find($newValue)?->nombre_completo ?? 'Ninguno') : 'Ninguno';
                    
                    if ($oldValue && !$newValue) {
                        $descriptions[] = "Se liberó el " . (strtolower($equipo->categoria) === 'programa' ? 'programa' : 'equipo') . " (se quitó el RESPONSABLE '{$oldPersonName}').";
                    } elseif (!$oldValue && $newValue) {
                        $descriptions[] = "Se asignó el " . (strtolower($equipo->categoria) === 'programa' ? 'programa' : 'equipo') . " a '{$newPersonName}'.";
                    } else {
                        $descriptions[] = "Se cambió el RESPONSABLE de '{$oldPersonName}' a '{$newPersonName}'.";
                    }
                } else {
                    $label = $fieldLabels[$field] ?? strtoupper($field);
                    $oldDisp = $oldValue ? "'{$oldValue}'" : "'Ninguno'";
                    $newDisp = $newValue ? "'{$newValue}'" : "'Ninguno'";
                    
                    if ($field === 'observacion_tecnica') {
                        $descriptions[] = "Se actualizó la OBSERVACIÓN TÉCNICA.";
                    } else {
                        $descriptions[] = "Se cambió el campo {$label} de {$oldDisp} a {$newDisp}.";
                    }
                }
            }

            // 5. Check if characteristics changed
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

                $newCharsMap = collect($caracteristicasPayload)->pluck('valor', 'clave')->toArray();

                // 5.1 Handle deleted characteristics
                $deletedChars = array_diff_key($oldCharsMap, $newCharsMap);
                foreach ($deletedChars as $clave => $valor) {
                    \App\Models\HistorialMovimiento::create([
                        'tipo_accion' => 'ELIMINACION',
                        'descripcion' => "Se eliminó la característica técnica: " . strtoupper($clave) . " con valor '{$valor}'.",
                        'fecha_hora' => now(),
                        'id_usuario' => auth()->id(),
                        'id_equipo' => $equipo->id,
                    ]);
                }

                // 5.2 Handle added characteristics
                $addedChars = array_diff_key($newCharsMap, $oldCharsMap);
                foreach ($addedChars as $clave => $valor) {
                    $descriptions[] = "Se agregó la característica técnica: " . strtoupper($clave) . " con valor '{$valor}'.";
                }

                // 5.3 Handle updated characteristics
                foreach ($newCharsMap as $clave => $valor) {
                    if (array_key_exists($clave, $oldCharsMap) && $oldCharsMap[$clave] !== $valor) {
                        $descriptions[] = "Se cambió el valor de la característica técnica " . strtoupper($clave) . " de '{$oldCharsMap[$clave]}' a '{$valor}'.";
                    }
                }
            }

            // 6. Log history if there were changes
            if (count($descriptions) > 0) {
                \App\Models\HistorialMovimiento::create([
                    'tipo_accion' => 'MODIFICACION',
                    'descripcion' => implode("\n", $descriptions),
                    'fecha_hora' => now(),
                    'id_usuario' => auth()->id(),
                    'id_equipo' => $equipo->id,
                ]);
            }
        });

        if ($request->header('X-Inertia')) {
            return redirect()->back();
        }

        return response()->json([
            'message' => 'Equipo actualizado correctamente.',
            'data' => $equipo->fresh()->load(['persona:id,nombre_completo,id_oficina,celular,correo,cargo', 'persona.oficina.area:id,nombre', 'caracteristicas:id,clave,valor,id_equipo']),
        ]);
    }

    /**
     * Actualiza la lista de programas asociados al equipo.
     */
    public function updateProgramas(Request $request, Equipo $equipo)
    {
        $validated = $request->validate([
            'programa_ids' => ['nullable', 'array'],
            'programa_ids.*' => ['exists:equipos,id'],
        ]);

        $ids = $validated['programa_ids'] ?? [];

        DB::transaction(function () use ($equipo, $ids) {
            // Get currently installed program names to log differences
            $oldProgramNames = $equipo->programas->pluck('nombre', 'id')->toArray();
            
            // Sync relationships
            $equipo->programas()->sync($ids);
            
            // Reload and get new program names
            $equipo->load('programas');
            $newProgramNames = $equipo->programas->pluck('nombre', 'id')->toArray();

            // Formulate descriptions of added / removed programs
            $added = array_diff_key($newProgramNames, $oldProgramNames);
            $removed = array_diff_key($oldProgramNames, $newProgramNames);
            
            $descriptions = [];
            if (count($added) > 0) {
                $descriptions[] = "Se instalaron los siguientes programas: " . implode(', ', $added);
            }
            if (count($removed) > 0) {
                $descriptions[] = "Se desinstalaron los siguientes programas: " . implode(', ', $removed);
            }

            if (count($descriptions) > 0) {
                \App\Models\HistorialMovimiento::create([
                    'tipo_accion' => 'MODIFICACION',
                    'descripcion' => implode("\n", $descriptions),
                    'fecha_hora' => now(),
                    'id_usuario' => auth()->id(),
                    'id_equipo' => $equipo->id,
                ]);
            }
        });

        if ($request->header('X-Inertia')) {
            return redirect()->back();
        }

        return response()->json([
            'message' => 'Programas del equipo actualizados correctamente.',
            'data' => $equipo->fresh()->load('programas:id,cod_informatica,nombre,tipo,estado'),
        ]);
    }

    /**
     * Elimina un equipo.
     */
    public function destroy(Equipo $equipo): JsonResponse
    {
        // Log Deletion
        \App\Models\HistorialMovimiento::create([
            'tipo_accion' => 'ELIMINACION',
            'descripcion' => 'Se eliminó el ' . (strtolower($equipo->categoria) === 'programa' ? 'programa' : 'equipo') . ' ' . $equipo->cod_informatica . ' (' . $equipo->nombre . ').',
            'fecha_hora' => now(),
            'id_usuario' => auth()->id(),
            'id_equipo' => $equipo->id,
        ]);

        $equipo->delete();

        return response()->json([
            'message' => 'Equipo eliminado correctamente.',
        ]);
    }
}
