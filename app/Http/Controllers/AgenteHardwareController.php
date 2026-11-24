<?php

namespace App\Http\Controllers;

use App\Models\Equipo;
use App\Models\HistorialMovimiento;
use App\Models\Oficina;
use App\Models\Persona;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

class AgenteHardwareController extends Controller
{
    /**
     * Devuelve el catálogo oficial de oficinas para que el agente Go
     * pueda sincronizarse o actualizar su caché local dinámicamente.
     */
    public function oficinas(): JsonResponse
    {
        $oficinas = Oficina::with('area:id,nombre')
            ->select('id', 'nombre', 'descripcion', 'area_id')
            ->orderBy('nombre')
            ->get()
            ->map(function ($o) {
                return [
                    'id' => $o->id,
                    'nombre' => $o->nombre,
                    'descripcion' => $o->descripcion,
                    'area_nombre' => $o->area?->nombre ?? 'UGEL Huánuco',
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $oficinas,
        ]);
    }

    /**
     * Endpoint receptor para registro de hardware y usuario desde el Agente USB.
     * Registra al responsable (si no existe) y da de alta el equipo en estado 'BORRADOR'.
     */
    public function registro(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            // Validación de Persona
            'persona' => ['required', 'array'],
            'persona.nombre_completo' => ['required', 'string', 'max:255'],
            'persona.id_oficina' => ['required', 'integer', 'exists:oficinas,id'],
            'persona.cargo' => ['nullable', 'string', 'max:255'],
            'persona.celular' => ['nullable', 'string', 'max:20'],
            'persona.correo' => ['nullable', 'email', 'max:255'],

            // Validación de Equipo
            'equipo' => ['required', 'array'],
            'equipo.nombre' => ['nullable', 'string', 'max:255'],
            'equipo.tipo' => ['required', 'string', 'max:50'],
            'equipo.ip' => ['nullable', 'string', 'max:45'],
            'equipo.cod_serial' => ['nullable', 'string', 'max:255'],
            'equipo.caracteristicas' => ['nullable', 'array'],
            'equipo.caracteristicas.*.clave' => ['required_with:equipo.caracteristicas.*.valor', 'string', 'max:255'],
            'equipo.caracteristicas.*.valor' => ['required_with:equipo.caracteristicas.*.clave', 'string', 'max:255'],
            'equipo.programas' => ['nullable', 'array'],
            'equipo.programas.*.nombre' => ['required_with:equipo.programas', 'string', 'max:255'],
            'equipo.programas.*.version' => ['nullable', 'string', 'max:255'],
            'equipo.programas.*.fabricante' => ['nullable', 'string', 'max:255'],
            'equipo.programas.*.fecha_instalacion' => ['nullable', 'string', 'max:50'],
        ], [
            'persona.required' => 'Los datos del responsable son requeridos.',
            'persona.nombre_completo.required' => 'El nombre completo del responsable es obligatorio.',
            'persona.id_oficina.required' => 'La oficina del responsable es obligatoria.',
            'persona.id_oficina.exists' => 'La oficina especificada no existe en el sistema.',
            'equipo.required' => 'La ficha técnica del equipo es requerida.',
            'equipo.tipo.required' => 'El tipo de equipo es obligatorio.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación en los datos recibidos.',
                'errors' => $validator->errors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $personaData = $request->input('persona');
        $equipoData = $request->input('equipo');

        return DB::transaction(function () use ($personaData, $equipoData) {
            // 1. PROCESAR PERSONA (Reglas de reutilización y prevención de duplicados)
            $nombreRaw = trim(preg_replace('/\s+/', ' ', (string)$personaData['nombre_completo']));
            $nombreTitle = mb_convert_case($nombreRaw, MB_CASE_TITLE, 'UTF-8');
            $correoInput = !empty($personaData['correo']) ? trim(mb_strtolower((string)$personaData['correo'], 'UTF-8')) : null;
            $celularRaw = !empty($personaData['celular']) ? preg_replace('/\D/', '', (string)$personaData['celular']) : null;

            $persona = null;

            // Prioridad 1: Reutilizar por Correo Electrónico Institucional (si ya pertenece a una persona registrada)
            if ($correoInput) {
                $persona = Persona::where('estado', 'ACTIVO')
                    ->whereNotNull('correo')
                    ->whereRaw('LOWER(TRIM(correo)) = ?', [$correoInput])
                    ->first();
            }

            // Prioridad 2: Reutilizar por Nombre Completo
            if (!$persona) {
                $persona = Persona::whereRaw('LOWER(TRIM(nombre_completo)) = ?', [mb_strtolower($nombreTitle, 'UTF-8')])
                    ->first();
            }

            // Prioridad 3: Reutilizar por Celular (verificando que no haya conflicto de correo)
            if (!$persona && $celularRaw && strlen($celularRaw) === 9) {
                $personaPorCelular = Persona::where('estado', 'ACTIVO')
                    ->whereNotNull('celular')
                    ->whereRaw("REPLACE(celular, ' ', '') = ?", [$celularRaw])
                    ->first();

                if ($personaPorCelular) {
                    $correoExistente = $personaPorCelular->correo ? trim(mb_strtolower($personaPorCelular->correo, 'UTF-8')) : null;
                    if ($correoInput && $correoExistente && $correoInput !== $correoExistente) {
                        return response()->json([
                            'success' => false,
                            'message' => "Conflicto de datos: El número de celular {$celularRaw} ya está registrado a nombre de otra persona activa ({$personaPorCelular->nombre_completo}).",
                        ], Response::HTTP_UNPROCESSABLE_ENTITY);
                    }
                    $persona = $personaPorCelular;
                }
            }

            if (!$persona) {
                // Celular
                $celularFormateado = null;
                if (!empty($personaData['celular'])) {
                    $celularDigits = preg_replace('/\D/', '', (string)$personaData['celular']);
                    if (strlen($celularDigits) !== 9) {
                        return response()->json([
                            'success' => false,
                            'message' => 'El número de celular del responsable debe tener exactamente 9 dígitos.',
                        ], Response::HTTP_UNPROCESSABLE_ENTITY);
                    }

                    $celularExiste = Persona::where('estado', 'ACTIVO')
                        ->whereNotNull('celular')
                        ->whereRaw("REPLACE(celular, ' ', '') = ?", [$celularDigits])
                        ->exists();

                    if ($celularExiste) {
                        return response()->json([
                            'success' => false,
                            'message' => "Conflicto de datos: Ya existe otra persona activa registrada con el número de celular {$celularDigits}.",
                        ], Response::HTTP_UNPROCESSABLE_ENTITY);
                    }

                    $celularFormateado = substr($celularDigits, 0, 3) . ' ' . substr($celularDigits, 3, 3) . ' ' . substr($celularDigits, 6, 3);
                }

                // Correo
                $correoLimpio = null;
                if (!empty($personaData['correo'])) {
                    $correoLimpio = trim(mb_strtolower((string)$personaData['correo'], 'UTF-8'));
                    $correoExiste = Persona::where('estado', 'ACTIVO')
                        ->whereNotNull('correo')
                        ->whereRaw('LOWER(TRIM(correo)) = ?', [$correoLimpio])
                        ->exists();

                    if ($correoExiste) {
                        return response()->json([
                            'success' => false,
                            'message' => "Conflicto de datos: Ya existe otra persona activa registrada con el correo {$correoLimpio}.",
                        ], Response::HTTP_UNPROCESSABLE_ENTITY);
                    }
                }

                $persona = Persona::create([
                    'nombre_completo' => $nombreTitle,
                    'id_oficina' => $personaData['id_oficina'],
                    'cargo' => !empty($personaData['cargo']) ? trim($personaData['cargo']) : null,
                    'celular' => $celularFormateado,
                    'correo' => $correoLimpio,
                    'estado' => 'ACTIVO',
                ]);
            } else {
                // Si la persona ya existía pero no tenía cargo/celular/correo y el agente los suministra, completarlos
                $updates = [];
                if (empty($persona->cargo) && !empty($personaData['cargo'])) {
                    $updates['cargo'] = trim($personaData['cargo']);
                }
                if (empty($persona->celular) && !empty($personaData['celular'])) {
                    $digits = preg_replace('/\D/', '', (string)$personaData['celular']);
                    if (strlen($digits) === 9) {
                        $updates['celular'] = substr($digits, 0, 3) . ' ' . substr($digits, 3, 3) . ' ' . substr($digits, 6, 3);
                    }
                }
                if (empty($persona->correo) && !empty($personaData['correo'])) {
                    $updates['correo'] = trim(mb_strtolower((string)$personaData['correo'], 'UTF-8'));
                }
                if (!empty($updates)) {
                    $persona->update($updates);
                }
            }

            // 2. DETECCIÓN DE DUPLICIDAD DE EQUIPO (por Serial o MAC)
            $codSerial = trim((string)($equipoData['cod_serial'] ?? ''));
            $caracteristicas = $equipoData['caracteristicas'] ?? [];
            $macAddress = null;

            foreach ($caracteristicas as $c) {
                if (strtolower(trim($c['clave'] ?? '')) === 'mac') {
                    $macAddress = strtoupper(trim($c['valor'] ?? ''));
                    break;
                }
            }

            $equipoExistente = null;

            // Búsqueda por número de serie
            if ($codSerial !== '') {
                $equipoExistente = Equipo::where('cod_serial', $codSerial)->first();
            }

            // Búsqueda alternativa por dirección MAC física
            if (!$equipoExistente && $macAddress !== null && $macAddress !== '') {
                $equipoExistente = Equipo::whereHas('caracteristicas', function ($q) use ($macAddress) {
                    $q->where('clave', 'mac')->where('valor', $macAddress);
                })->first();
            }

            // Obtener usuario administrador para auditoría
            $adminUser = User::where('rol', 'ADMIN')->first() ?? User::first();
            $adminUserId = $adminUser?->id ?? 1;

            $caracteristicasPayload = collect($caracteristicas)
                ->filter(fn ($item) => is_array($item) && trim((string)($item['clave'] ?? '')) !== '' && trim((string)($item['valor'] ?? '')) !== '')
                ->map(fn ($item) => [
                    'clave' => strtolower(trim((string)$item['clave'])),
                    'valor' => trim((string)$item['valor']),
                ])
                ->values()
                ->all();

            // CASO A: El equipo ya existe -> Actualizar hardware sin sobrescribir responsable ni cod_patrimonial
            if ($equipoExistente) {
                $equipoExistente->ip = $equipoData['ip'] ?? $equipoExistente->ip;
                if (!empty($equipoData['nombre'])) {
                    $equipoExistente->nombre = $equipoData['nombre'];
                }
                if (empty($equipoExistente->id_persona)) {
                    $equipoExistente->id_persona = $persona->id;
                }
                $equipoExistente->save();

                // Actualizar o crear características técnicas
                foreach ($caracteristicasPayload as $char) {
                    $equipoExistente->caracteristicas()->updateOrCreate(
                        ['clave' => $char['clave']],
                        ['valor' => $char['valor']]
                    );
                }

                // Sincronizar programas detectados (reutilizando existentes o creando nuevos)
                $programasData = $equipoData['programas'] ?? [];
                $programaIds = $this->procesarProgramasDetectados($programasData);
                if (count($programaIds) > 0) {
                    $equipoExistente->programas()->syncWithoutDetaching($programaIds);
                }

                HistorialMovimiento::create([
                    'tipo_accion' => 'MODIFICACION',
                    'descripcion' => 'Hardware y programas actualizados automáticamente mediante Agente USB.',
                    'fecha_hora' => now(),
                    'id_usuario' => $adminUserId,
                    'id_equipo' => $equipoExistente->id,
                ]);

                $equipoExistente->load([
                    'persona:id,nombre_completo,id_oficina,celular,correo,cargo',
                    'persona.oficina.area:id,nombre',
                    'caracteristicas:id,clave,valor,id_equipo',
                    'programas:id,cod_informatica,nombre,tipo,estado',
                ]);

                return response()->json([
                    'success' => true,
                    'action' => 'ACTUALIZADO',
                    'message' => "El equipo {$equipoExistente->cod_informatica} ya existía en el inventario. Sus características y programas fueron actualizados.",
                    'data' => $equipoExistente,
                ], Response::HTTP_OK);
            }

            // CASO B: Equipo nuevo -> Registrar en estado 'BORRADOR'
            $ultimoId = DB::table('equipos')->lockForUpdate()->max('id') ?? 0;
            $nuevoId = $ultimoId + 1;

            $tipoRaw = strtolower(trim((string)($equipoData['tipo'] ?? 'pc')));
            $abrevTipoMap = [
                'pc' => 'PC',
                'laptop' => 'LAP',
                'todo en uno' => 'TEU',
                'servidor' => 'SER',
            ];
            $abrevTipo = $abrevTipoMap[$tipoRaw] ?? 'EQU';
            $codInformatica = 'EQU' . $abrevTipo . $nuevoId;

            $nuevoEquipo = Equipo::create([
                'cod_informatica' => $codInformatica,
                'cod_patrimonial' => null, // Pendiente de asignación por administración
                'cod_serial' => $codSerial !== '' ? $codSerial : null,
                'nombre' => $equipoData['nombre'] ?? "DESKTOP-{$nuevoId}",
                'nombre_usuario' => null,
                'tipo' => strtoupper($equipoData['tipo'] ?? 'PC'),
                'estado' => 'BORRADOR',
                'categoria' => 'equipo',
                'clasificacion' => 'REGULAR',
                'ip' => $equipoData['ip'] ?? null,
                'fecha_ingreso' => now()->toDateString(),
                'id_persona' => $persona->id,
            ]);

            if (count($caracteristicasPayload) > 0) {
                $nuevoEquipo->caracteristicas()->createMany($caracteristicasPayload);
            }

            // Procesar y asociar programas detectados
            $programasData = $equipoData['programas'] ?? [];
            $programaIds = $this->procesarProgramasDetectados($programasData);
            if (count($programaIds) > 0) {
                $nuevoEquipo->programas()->sync($programaIds);
            }

            HistorialMovimiento::create([
                'tipo_accion' => 'CREACION',
                'descripcion' => 'Equipo, usuario y programas registrados automáticamente mediante Agente USB en estado BORRADOR',
                'fecha_hora' => now(),
                'id_usuario' => $adminUserId,
                'id_equipo' => $nuevoEquipo->id,
            ]);

            $nuevoEquipo->load([
                'persona:id,nombre_completo,id_oficina,celular,correo,cargo',
                'persona.oficina.area:id,nombre',
                'caracteristicas:id,clave,valor,id_equipo',
                'programas:id,cod_informatica,nombre,tipo,estado',
            ]);

            return response()->json([
                'success' => true,
                'action' => 'CREADO_BORRADOR',
                'message' => "Equipo registrado exitosamente en estado BORRADOR con código {$codInformatica}.",
                'data' => $nuevoEquipo,
            ], Response::HTTP_CREATED);
        });
    }

    /**
     * Confirma un equipo que se encuentra en estado 'BORRADOR', asignando opcionalmente
     * el código patrimonial y cambiándolo a 'EN USO' (o 'LIBRE').
     */
    public function confirmarBorrador(Request $request, Equipo $equipo): JsonResponse
    {
        if (strtoupper($equipo->estado ?? '') !== 'BORRADOR') {
            return response()->json([
                'success' => false,
                'message' => "El equipo {$equipo->cod_informatica} no se encuentra en estado BORRADOR.",
            ], Response::HTTP_BAD_REQUEST);
        }

        $validated = $request->validate([
            'cod_patrimonial' => ['nullable', 'string', 'max:255', 'unique:equipos,cod_patrimonial,' . $equipo->id],
            'clasificacion' => ['nullable', 'string', 'in:BUENO,REGULAR,MALO'],
            'estado' => ['nullable', 'string', 'in:EN USO,LIBRE,OPERATIVO'],
        ]);

        $nuevoEstado = $validated['estado'] ?? ($equipo->id_persona ? 'EN USO' : 'LIBRE');
        if ($nuevoEstado === 'OPERATIVO') {
            $nuevoEstado = $equipo->id_persona ? 'EN USO' : 'LIBRE';
        }

        $equipo->update([
            'cod_patrimonial' => $validated['cod_patrimonial'] ?? $equipo->cod_patrimonial,
            'clasificacion' => $validated['clasificacion'] ?? ($equipo->clasificacion ?: 'BUENO'),
            'estado' => $nuevoEstado,
        ]);

        $adminUser = User::where('rol', 'ADMIN')->first() ?? User::first();
        $adminUserId = auth()->id() ?? ($adminUser?->id ?? 1);

        HistorialMovimiento::create([
            'tipo_accion' => 'MODIFICACION',
            'descripcion' => "Equipo {$equipo->cod_informatica} confirmado desde estado BORRADOR a {$nuevoEstado} por el administrador.",
            'fecha_hora' => now(),
            'id_usuario' => $adminUserId,
            'id_equipo' => $equipo->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => "El equipo {$equipo->cod_informatica} ha sido confirmado y pasó a estado {$nuevoEstado}.",
            'data' => $equipo->fresh(['persona:id,nombre_completo,id_oficina', 'caracteristicas:id,clave,valor']),
        ]);
    }

    /**
     * Descarga el ejecutable portable del agente de hardware para memorias USB.
     */
    public function descargarAgente(): \Symfony\Component\HttpFoundation\BinaryFileResponse|\Illuminate\Http\JsonResponse
    {
        $path = public_path('descargas/agente_ugel.exe');

        if (!file_exists($path)) {
            $storagePath = storage_path('app/public/descargas/agente_ugel.exe');
            if (file_exists($storagePath)) {
                $path = $storagePath;
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'El ejecutable del agente portable aún no ha sido publicado en el servidor.'
                ], Response::HTTP_NOT_FOUND);
            }
        }

        return response()->download($path, 'agente_ugel.exe', [
            'Content-Type' => 'application/vnd.microsoft.portable-executable',
            'Cache-Control' => 'no-cache, must-revalidate',
        ]);
    }

    /**
     * Procesa los programas detectados por el agente, reutilizando los existentes
     * en el catálogo institucional o registrando nuevos bajo la categoría 'programa'.
     * Retorna los IDs de los programas para vincularlos en equipo_programa.
     */
    private function procesarProgramasDetectados(array $programas): array
    {
        $programaIds = [];
        $tiposAbrevMap = [
            'institucional' => 'INS',
            'navegador' => 'NAV',
            'ofimática' => 'OFI',
            'ofimatica' => 'OFI',
            'soporte' => 'SOP',
            'antivirus' => 'ANT',
            'otro (programas)' => 'OTP',
        ];

        foreach ($programas as $p) {
            $nombre = trim((string)($p['nombre'] ?? ''));
            if ($nombre === '') {
                continue;
            }

            // 1. Buscar si ya existe en el catálogo de programas
            $progExistente = Equipo::where('categoria', 'programa')
                ->whereRaw('LOWER(TRIM(nombre)) = ?', [mb_strtolower($nombre, 'UTF-8')])
                ->first();

            if ($progExistente) {
                $programaIds[] = $progExistente->id;
                continue;
            }

            // 2. Si no existe, inferir tipo de programa según palabras clave
            $lower = mb_strtolower($nombre, 'UTF-8');
            $tipo = 'Otro (programas)';

            if (preg_match('/chrome|firefox|edge|opera|brave|safari|browser/i', $lower)) {
                $tipo = 'Navegador';
            } elseif (preg_match('/office|word|excel|powerpoint|access|libreoffice|acrobat|pdf|wps|foxit/i', $lower)) {
                $tipo = 'Ofimática';
            } elseif (preg_match('/antivirus|kaspersky|defender|eset|nod32|malwarebytes|avast|avg|bitdefender|symantec|mcafee/i', $lower)) {
                $tipo = 'Antivirus';
            } elseif (preg_match('/anydesk|teamviewer|winrar|7-zip|zip|driver|support|soporte|ultraviewer|rustdesk|ccleaner/i', $lower)) {
                $tipo = 'Soporte';
            } elseif (preg_match('/ugel|siagie|siaf|siga|minedu|escalafon|nexus|reniec/i', $lower)) {
                $tipo = 'Institucional';
            }

            $tipoKey = mb_strtolower($tipo, 'UTF-8');
            $abrevTipo = $tiposAbrevMap[$tipoKey] ?? 'OTP';

            $ultimoId = DB::table('equipos')->max('id') ?? 0;
            $nuevoId = $ultimoId + 1;
            $codInformatica = 'PRO' . $abrevTipo . $nuevoId;

            $version = trim((string)($p['version'] ?? ''));
            $fabricante = trim((string)($p['fabricante'] ?? ''));
            $descParts = [];
            if ($version !== '') $descParts[] = "Versión: {$version}";
            if ($fabricante !== '') $descParts[] = "Fabricante: {$fabricante}";
            $descripcion = count($descParts) > 0 ? implode(' | ', $descParts) : 'Detectado automáticamente por el Agente de Hardware';

            $nuevoProg = Equipo::create([
                'cod_informatica' => $codInformatica,
                'nombre' => $nombre,
                'tipo' => $tipo,
                'estado' => 'OPERATIVO',
                'categoria' => 'programa',
                'descripcion' => $descripcion,
                'fecha_ingreso' => now()->toDateString(),
            ]);

            $programaIds[] = $nuevoProg->id;
        }

        return array_values(array_unique($programaIds));
    }
}
