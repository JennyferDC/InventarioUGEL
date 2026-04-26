<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\CronogramaMantenimiento;
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
        $oficinas = \App\Models\Oficina::orderBy('nombre')->get();
        
        return Inertia::render('PlanMantenimiento/Show', [
            'plan' => $plan,
            'oficinas' => $oficinas
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

    public function destroy($id)
    {
        // TODO
    }
}
