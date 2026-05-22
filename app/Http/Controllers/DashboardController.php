<?php

namespace App\Http\Controllers;

use App\Models\Equipo;
use App\Models\Persona;
use App\Models\Mantenimiento;
use App\Models\HistorialMovimiento;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        // 1. KPI Metrics
        $totalEquipos = Equipo::where('categoria', 'equipo')->count();
        $totalProgramas = Equipo::where('categoria', 'programa')->count();
        
        $equiposEnUso = Equipo::where('categoria', 'equipo')->where('estado', 'EN USO')->count();
        $equiposLibres = Equipo::where('categoria', 'equipo')->where('estado', 'LIBRE')->count();
        $equiposBaja = Equipo::where('categoria', 'equipo')->where('estado', 'BAJA')->count();
        
        $totalPersonas = Persona::count();
        
        // Identify active maintenance plan "en curso"
        $activePlan = null;
        $planes = \App\Models\CronogramaMantenimiento::with('items.oficina.area')->get();
        $today = \Carbon\Carbon::today();
        
        foreach ($planes as $plan) {
            $startDate = $plan->items->min('fecha_inicio');
            $endDate = $plan->items->max('fecha_fin');
            
            if ($startDate && $endDate) {
                $start = \Carbon\Carbon::parse($startDate);
                $end = \Carbon\Carbon::parse($endDate);
                if ($today->between($start, $end)) {
                    $activePlan = $plan;
                    break;
                }
            }
        }
        
        if (!$activePlan) {
            // Fallback to current year's plan or latest
            $year = date('Y');
            $activePlan = \App\Models\CronogramaMantenimiento::with('items.oficina.area')
                ->where('titulo', 'like', "%{$year}%")
                ->first() ?: \App\Models\CronogramaMantenimiento::with('items.oficina.area')->latest()->first();
        }

        $mantenimientosPendientes = 0;
        $mantenimientosRealizados = 0;
        $proximosMantenimientos = [];

        if ($activePlan) {
            foreach ($activePlan->items as $item) {
                if (!$item->fecha_inicio || !$item->fecha_fin) {
                    continue;
                }
                
                $start = \Carbon\Carbon::parse($item->fecha_inicio);
                $end = \Carbon\Carbon::parse($item->fecha_fin);
                
                if ($today->between($start, $end)) {
                    // En curso
                    $mantenimientosPendientes++;
                    $proximosMantenimientos[] = [
                        'id' => $item->id,
                        'fecha_inicio' => $item->fecha_inicio,
                        'fecha_fin' => $item->fecha_fin,
                        'actividad' => $item->actividad,
                        'oficina' => $item->oficina ? $item->oficina->nombre : 'Sin Oficina',
                        'area' => $item->oficina && $item->oficina->area ? $item->oficina->area->nombre : 'Sin Área',
                        'estado' => 'En curso',
                        'dias' => $start->diffInDays($end) + 1,
                    ];
                } elseif ($today->gt($end)) {
                    // Finalizado
                    $mantenimientosRealizados++;
                } else {
                    $diffDays = $today->diffInDays($start, false);
                    if ($diffDays > 0 && $diffDays <= 7) {
                        // Próximo
                        $mantenimientosPendientes++;
                        $proximosMantenimientos[] = [
                            'id' => $item->id,
                            'fecha_inicio' => $item->fecha_inicio,
                            'fecha_fin' => $item->fecha_fin,
                            'actividad' => $item->actividad,
                            'oficina' => $item->oficina ? $item->oficina->nombre : 'Sin Oficina',
                            'area' => $item->oficina && $item->oficina->area ? $item->oficina->area->nombre : 'Sin Área',
                            'estado' => 'Próximo',
                            'dias' => $start->diffInDays($end) + 1,
                        ];
                    }
                }
            }
            
            // Sort proximosMantenimientos by fecha_inicio ascending
            usort($proximosMantenimientos, function($a, $b) {
                return strcmp($a['fecha_inicio'], $b['fecha_inicio']);
            });
        }

        // 2. Equipments by Classification (BUENO, REGULAR, MALO)
        $clasificaciones = Equipo::where('categoria', 'equipo')
            ->select('clasificacion', DB::raw('count(id) as total'))
            ->whereNotNull('clasificacion')
            ->where('clasificacion', '<>', '')
            ->groupBy('clasificacion')
            ->get()
            ->pluck('total', 'clasificacion')
            ->toArray();

        // Ensure we have values for all classifications
        $distribucionClasificacion = [
            'BUENO' => $clasificaciones['BUENO'] ?? 0,
            'REGULAR' => $clasificaciones['REGULAR'] ?? 0,
            'MALO' => $clasificaciones['MALO'] ?? 0,
        ];

        // 3. Equipments by Type (PC, Laptop, Monitor, etc.)
        $distribucionTipos = Equipo::where('categoria', 'equipo')
            ->select('tipo', DB::raw('count(id) as total'))
            ->groupBy('tipo')
            ->orderByDesc('total')
            ->take(5)
            ->get()
            ->map(fn($item) => [
                'tipo' => strtoupper($item->tipo),
                'total' => $item->total,
            ])
            ->toArray();

        // 4. Equipments by Office (Top 5)
        $distribucionOficinas = Equipo::where('equipos.categoria', 'equipo')
            ->whereNotNull('equipos.id_persona')
            ->join('personas', 'equipos.id_persona', '=', 'personas.id')
            ->join('oficinas', 'personas.id_oficina', '=', 'oficinas.id')
            ->select('oficinas.nombre as oficina', DB::raw('count(equipos.id) as total'))
            ->groupBy('oficinas.nombre')
            ->orderByDesc('total')
            ->take(5)
            ->get()
            ->map(fn($item) => [
                'oficina' => $item->oficina,
                'total' => $item->total,
            ])
            ->toArray();

        // 5. Recent Movements (Auditory logs) - Limit increased to 15 for scroll
        $movimientosRecientes = HistorialMovimiento::with(['usuario:id,name,email', 'equipo:id,cod_informatica,categoria'])
            ->orderBy('fecha_hora', 'desc')
            ->take(15)
            ->get()
            ->map(fn($mov) => [
                'id' => $mov->id,
                'tipo_accion' => $mov->tipo_accion,
                'descripcion' => $mov->descripcion,
                'fecha_hora' => $mov->fecha_hora->toIso8601String(),
                'usuario' => $mov->usuario,
                'equipo' => $mov->equipo,
            ])
            ->toArray();

        // 7. Critical alerts (e.g. Teams registered as MALO state or under BAJA state)
        $alertas = [];
        if ($distribucionClasificacion['MALO'] > 0) {
            $alertas[] = [
                'id' => 'alert_malo',
                'tipo' => 'danger',
                'mensaje' => "Hay {$distribucionClasificacion['MALO']} equipos en clasificación MALO que requieren mantenimiento urgente o renovación.",
            ];
        }
        if ($mantenimientosPendientes > 0) {
            $alertas[] = [
                'id' => 'alert_maint',
                'tipo' => 'warning',
                'mensaje' => "Existen {$mantenimientosPendientes} mantenimientos pendientes en el cronograma actual.",
            ];
        }
        if ($equiposBaja > 0) {
            $alertas[] = [
                'id' => 'alert_baja',
                'tipo' => 'info',
                'mensaje' => "Se registran {$equiposBaja} equipos dados de BAJA en el sistema.",
            ];
        }

        return Inertia::render('Dashboard', [
            'metrics' => [
                'total_equipos' => $totalEquipos,
                'total_programas' => $totalProgramas,
                'equipos_en_uso' => $equiposEnUso,
                'equipos_libres' => $equiposLibres,
                'equipos_baja' => $equiposBaja,
                'total_personas' => $totalPersonas,
                'mantenimientos_pendientes' => $mantenimientosPendientes,
                'mantenimientos_realizados' => $mantenimientosRealizados,
            ],
            'distribucion_clasificacion' => $distribucionClasificacion,
            'distribucion_tipos' => $distribucionTipos,
            'distribucion_oficinas' => $distribucionOficinas,
            'movimientos_recientes' => $movimientosRecientes,
            'proximos_mantenimientos' => $proximosMantenimientos,
            'alertas' => $alertas,
        ]);
    }
}
