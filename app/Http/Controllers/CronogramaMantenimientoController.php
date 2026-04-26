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
        // TODO
    }

    public function show($id)
    {
        $plan = CronogramaMantenimiento::with('items.oficina.area')->findOrFail($id);
        
        return Inertia::render('PlanMantenimiento/Show', [
            'plan' => $plan
        ]);
    }

    public function update(Request $request, $id)
    {
        // TODO
    }

    public function destroy($id)
    {
        // TODO
    }
}
