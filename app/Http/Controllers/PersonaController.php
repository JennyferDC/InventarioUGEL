<?php

namespace App\Http\Controllers;

use App\Models\Persona;
use App\Models\Oficina;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
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
     * Formatea el nombre completo a Title Case (primera letra de cada palabra en mayúscula).
     */
    private function formatNombreCompleto(?string $nombre): ?string
    {
        if (!$nombre) return null;
        $nombre = trim(preg_replace('/\s+/', ' ', $nombre));
        return mb_convert_case($nombre, MB_CASE_TITLE, 'UTF-8');
    }

    /**
     * Extrae solo los dígitos de un número de celular.
     */
    private function cleanCelularDigits(?string $celular): ?string
    {
        if (!$celular) return null;
        $digits = preg_replace('/\D/', '', $celular);
        return !empty($digits) ? $digits : null;
    }

    /**
     * Formatea el celular con espacios cada 3 dígitos (ej: 987 654 321).
     */
    private function formatCelular(?string $celular): ?string
    {
        $digits = $this->cleanCelularDigits($celular);
        if (!$digits) return null;
        if (strlen($digits) === 9) {
            return substr($digits, 0, 3) . ' ' . substr($digits, 3, 3) . ' ' . substr($digits, 6, 3);
        }
        return $digits;
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
        $data['nombre_completo'] = $this->formatNombreCompleto($data['nombre_completo']);

        // 1. Validar nombre único insensible a mayúsculas/minúsculas
        $nombreExiste = Persona::whereRaw('LOWER(TRIM(nombre_completo)) = ?', [mb_strtolower($data['nombre_completo'], 'UTF-8')])
            ->exists();
        if ($nombreExiste) {
            throw ValidationException::withMessages([
                'nombre_completo' => 'Ya existe una persona registrada con ese nombre completo.',
            ]);
        }

        // 2. Validar celular (9 dígitos) y duplicidad en activos
        if (!empty($data['celular'])) {
            $celularDigits = $this->cleanCelularDigits($data['celular']);
            if (strlen($celularDigits) !== 9) {
                throw ValidationException::withMessages([
                    'celular' => 'El número de celular debe tener exactamente 9 dígitos.',
                ]);
            }

            if ($data['estado'] === 'ACTIVO') {
                $celularExiste = Persona::where('estado', 'ACTIVO')
                    ->whereNotNull('celular')
                    ->whereRaw("REPLACE(celular, ' ', '') = ?", [$celularDigits])
                    ->exists();
                if ($celularExiste) {
                    throw ValidationException::withMessages([
                        'celular' => 'Ya existe una persona activa registrada con este número de celular.',
                    ]);
                }
            }

            $data['celular'] = $this->formatCelular($data['celular']);
        } else {
            $data['celular'] = null;
        }

        // 3. Validar correo duplicado en activos
        if (!empty($data['correo'])) {
            $data['correo'] = trim(mb_strtolower($data['correo'], 'UTF-8'));
            if ($data['estado'] === 'ACTIVO') {
                $correoExiste = Persona::where('estado', 'ACTIVO')
                    ->whereNotNull('correo')
                    ->whereRaw('LOWER(TRIM(correo)) = ?', [$data['correo']])
                    ->exists();
                if ($correoExiste) {
                    throw ValidationException::withMessages([
                        'correo' => 'Ya existe una persona activa registrada con este correo electrónico.',
                    ]);
                }
            }
        } else {
            $data['correo'] = null;
        }

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

        $data['nombre_completo'] = $this->formatNombreCompleto($data['nombre_completo']);

        // 1. Validar nombre único insensible a mayúsculas/minúsculas (excluyendo a la persona actual)
        $nombreExiste = Persona::where('id', '!=', $persona->id)
            ->whereRaw('LOWER(TRIM(nombre_completo)) = ?', [mb_strtolower($data['nombre_completo'], 'UTF-8')])
            ->exists();
        if ($nombreExiste) {
            throw ValidationException::withMessages([
                'nombre_completo' => 'Ya existe otra persona registrada con ese nombre completo.',
            ]);
        }

        // 2. Validar celular (9 dígitos) y duplicidad en activos
        if (!empty($data['celular'])) {
            $celularDigits = $this->cleanCelularDigits($data['celular']);
            if (strlen($celularDigits) !== 9) {
                throw ValidationException::withMessages([
                    'celular' => 'El número de celular debe tener exactamente 9 dígitos.',
                ]);
            }

            if ($data['estado'] === 'ACTIVO') {
                $celularExiste = Persona::where('estado', 'ACTIVO')
                    ->where('id', '!=', $persona->id)
                    ->whereNotNull('celular')
                    ->whereRaw("REPLACE(celular, ' ', '') = ?", [$celularDigits])
                    ->exists();
                if ($celularExiste) {
                    throw ValidationException::withMessages([
                        'celular' => 'Ya existe otra persona activa registrada con este número de celular.',
                    ]);
                }
            }

            $data['celular'] = $this->formatCelular($data['celular']);
        } else {
            $data['celular'] = null;
        }

        // 3. Validar correo duplicado en activos
        if (!empty($data['correo'])) {
            $data['correo'] = trim(mb_strtolower($data['correo'], 'UTF-8'));
            if ($data['estado'] === 'ACTIVO') {
                $correoExiste = Persona::where('estado', 'ACTIVO')
                    ->where('id', '!=', $persona->id)
                    ->whereNotNull('correo')
                    ->whereRaw('LOWER(TRIM(correo)) = ?', [$data['correo']])
                    ->exists();
                if ($correoExiste) {
                    throw ValidationException::withMessages([
                        'correo' => 'Ya existe otra persona activa registrada con este correo electrónico.',
                    ]);
                }
            }
        } else {
            $data['correo'] = null;
        }

        $persona->update($data);

        return response()->json([
            'message' => 'Persona actualizada correctamente.',
            'data' => $persona->fresh()->load('oficina.area')->loadCount('equipos'),
        ]);
    }

    /**
     * Elimina o alterna el estado de una persona.
     */
    public function destroy(Persona $persona): JsonResponse
    {
        $nuevoEstado = $persona->estado === 'ACTIVO' ? 'INACTIVO' : 'ACTIVO';

        if ($nuevoEstado === 'ACTIVO') {
            // Validar que al reactivar no colisione con otras personas activas
            if (!empty($persona->correo)) {
                $correoDuplicado = Persona::where('estado', 'ACTIVO')
                    ->where('id', '!=', $persona->id)
                    ->whereRaw('LOWER(TRIM(correo)) = ?', [mb_strtolower(trim($persona->correo), 'UTF-8')])
                    ->exists();
                if ($correoDuplicado) {
                    return response()->json([
                        'message' => 'No se puede reactivar: ya existe una persona activa con el correo "' . $persona->correo . '".'
                    ], 422);
                }
            }

            $celularDigits = $this->cleanCelularDigits($persona->celular);
            if (!empty($celularDigits)) {
                $celularDuplicado = Persona::where('estado', 'ACTIVO')
                    ->where('id', '!=', $persona->id)
                    ->whereNotNull('celular')
                    ->whereRaw("REPLACE(celular, ' ', '') = ?", [$celularDigits])
                    ->exists();
                if ($celularDuplicado) {
                    return response()->json([
                        'message' => 'No se puede reactivar: ya existe una persona activa con el celular "' . $persona->celular . '".'
                    ], 422);
                }
            }
        }

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
