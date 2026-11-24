<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\CronogramaMantenimiento;
use App\Models\Oficina;
use App\Models\Area;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use Inertia\Inertia;

class CronogramaMantenimientoController extends Controller
{
    public function index()
    {
        $planes = CronogramaMantenimiento::with('items')->get()->map(function($plan) {
            $items = $plan->items;
            return [
                'id' => $plan->id,
                'titulo' => $plan->titulo,
                'descripcion' => $plan->descripcion,
                'cantidad_oficinas' => $items->count(),
                'fecha_inicio' => $items->min('fecha_inicio'),
                'fecha_fin' => $items->max('fecha_fin'),
            ];
        });

        return Inertia::render('PlanMantenimiento/Index', [
            'planes' => $planes
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'titulo' => 'required|string|unique:cronograma_mantenimientos,titulo',
            'descripcion' => 'nullable|string',
        ], [
            'titulo.unique' => 'Ya existe un plan de mantenimiento para este año.',
        ]);

        CronogramaMantenimiento::create($validated);

        return redirect()->route('mantenimiento.index')->with('flash', [
            'banner' => 'Plan de mantenimiento creado exitosamente.',
            'bannerStyle' => 'success',
        ]);
    }

    public function show($id)
    {
        $plan = CronogramaMantenimiento::with('items.oficina.area')->findOrFail($id);
        $oficinas = \App\Models\Oficina::with('area')->orderBy('nombre')->get();
        
        return Inertia::render('PlanMantenimiento/Show', [
            'plan' => $plan,
            'oficinas' => $oficinas
        ]);
    }

    public function publicShow()
    {
        $year = date('Y');
        $plan = CronogramaMantenimiento::with('items.oficina.area')
            ->where('titulo', 'like', "%{$year}%")
            ->firstOrFail();
        
        return Inertia::render('PlanMantenimiento/Public', [
            'plan' => $plan,
        ]);
    }

    public function update(Request $request, $id)
    {
        $plan = CronogramaMantenimiento::findOrFail($id);

        $validated = $request->validate([
            'titulo' => 'required|string|unique:cronograma_mantenimientos,titulo,' . $id,
            'descripcion' => 'nullable|string',
        ], [
            'titulo.unique' => 'Ya existe un plan de mantenimiento para este año.',
        ]);

        $plan->update($validated);

        return redirect()->back()->with('flash', [
            'banner' => 'Plan de mantenimiento actualizado exitosamente.',
            'bannerStyle' => 'success',
        ]);
    }

    public function pdf(Request $request, $id)
    {
        $plan = CronogramaMantenimiento::with('items.oficina.area')->findOrFail($id);

        $nombrePlan = "Plan anual de mantenimiento informático - {$plan->titulo}";
        $anio = preg_match('/\b(20\d{2})\b/', $plan->titulo, $matches) ? $matches[1] : date('Y', strtotime($plan->created_at ?? 'now'));

        $nombreArea = 'Todas las unidades funcionales';
        $nombreOficina = 'Todas las oficinas';
        $mostrarColumnaOficina = true;

        $queryItems = $plan->items;

        $oficinasInput = $request->input('oficinas');
        if (!empty($oficinasInput)) {
            $oficinasIds = is_array($oficinasInput) ? $oficinasInput : explode(',', (string) $oficinasInput);
            $queryItems = $plan->items->whereIn('id_oficinas', $oficinasIds);

            $oficinasEncontradas = Oficina::with('area')->whereIn('id', $oficinasIds)->get();
            $areasEncontradas = $oficinasEncontradas->map(fn($o) => $o->area)->filter()->unique('id');

            if ($oficinasEncontradas->count() === 1) {
                $primeraOficina = $oficinasEncontradas->first();
                $nombreOficina = $primeraOficina->nombre;
                $nombreArea = $primeraOficina->area ? $primeraOficina->area->nombre : 'Sin Área Asignada';
                $mostrarColumnaOficina = false;
            } elseif ($areasEncontradas->count() === 1) {
                $nombreArea = $areasEncontradas->first()->nombre;
                $totalOficinasArea = Oficina::where('area_id', $areasEncontradas->first()->id)->count();
                if ($oficinasEncontradas->count() >= $totalOficinasArea) {
                    $nombreOficina = 'Todas las oficinas de la unidad';
                } else {
                    $nombreOficina = $oficinasEncontradas->pluck('nombre')->join(', ');
                }
                $mostrarColumnaOficina = true;
            } else {
                $totalOficinasPlan = $plan->items->pluck('id_oficinas')->unique()->count();
                if ($oficinasEncontradas->count() >= $totalOficinasPlan) {
                    $nombreArea = 'Todas las unidades funcionales';
                    $nombreOficina = 'Todas las oficinas';
                } else {
                    $nombreArea = $areasEncontradas->count() . ' Unidades Funcionales seleccionadas';
                    $nombreOficina = $oficinasEncontradas->count() . ' Oficinas seleccionadas';
                }
                $mostrarColumnaOficina = true;
            }
        } else {
            // Compatibilidad con parámetros individuales (tipo_filtro y filtro_id)
            $tipoFiltro = $request->input('tipo_filtro', 'todos');
            $filtroId = $request->input('filtro_id');

            if ($tipoFiltro === 'oficina' && $filtroId) {
                $oficinaObj = Oficina::with('area')->find($filtroId);
                if ($oficinaObj) {
                    $nombreOficina = $oficinaObj->nombre;
                    $nombreArea = $oficinaObj->area ? $oficinaObj->area->nombre : 'Sin Área Asignada';
                }
                $queryItems = $queryItems->where('id_oficinas', $filtroId);
                $mostrarColumnaOficina = false;
            } elseif ($tipoFiltro === 'area' && $filtroId) {
                $areaObj = Area::find($filtroId);
                if ($areaObj) {
                    $nombreArea = $areaObj->nombre;
                    $nombreOficina = 'Todas las oficinas de la unidad';
                }
                $queryItems = $queryItems->filter(function ($item) use ($filtroId) {
                    return $item->oficina && ($item->oficina->area_id == $filtroId || ($item->oficina->area && $item->oficina->area->id == $filtroId));
                });
                $mostrarColumnaOficina = true;
            }
        }

        $items = $queryItems->sortBy('fecha_inicio')->values();

        $today = date('Y-m-d');
        foreach ($items as $item) {
            if ($item->fecha_inicio && $item->fecha_fin) {
                $s = new \DateTime($item->fecha_inicio);
                $e = new \DateTime($item->fecha_fin);
                $diff = $s->diff($e);
                $item->dias = ($diff->days + 1) . ' ' . (($diff->days + 1) === 1 ? 'día' : 'días');
            } else {
                $item->dias = '-';
            }

            if (!empty($item->estado) && in_array($item->estado, ['Completado', 'Cancelado'])) {
                $item->estado_display = $item->estado;
                $item->estado_class = $item->estado === 'Completado' ? 'status-completado' : 'status-cancelado';
            } elseif ($item->fecha_inicio && $item->fecha_fin) {
                if ($today > $item->fecha_fin) {
                    $item->estado_display = 'Finalizado';
                    $item->estado_class = 'status-finalizado';
                } elseif ($today >= $item->fecha_inicio && $today <= $item->fecha_fin) {
                    $item->estado_display = 'En curso';
                    $item->estado_class = 'status-encurso';
                } else {
                    $item->estado_display = !empty($item->estado) ? $item->estado : 'Pendiente';
                    $item->estado_class = 'status-pendiente';
                }
            } else {
                $item->estado_display = !empty($item->estado) ? $item->estado : 'Pendiente';
                $item->estado_class = 'status-pendiente';
            }
        }

        $pdf = Pdf::loadView('reportes.cronograma_pdf', compact(
            'plan',
            'nombrePlan',
            'anio',
            'nombreArea',
            'nombreOficina',
            'items',
            'mostrarColumnaOficina'
        ))->setPaper('a4', 'portrait');

        $slugOficina = Str::slug($nombreOficina . '_' . $anio, '_');
        $fileName = 'cronograma_' . ($slugOficina ?: 'mantenimiento') . '.pdf';

        return $pdf->download($fileName);
    }

    public function destroy($id)
    {
        // TODO
    }
}
