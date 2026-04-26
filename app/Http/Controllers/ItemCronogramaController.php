<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ItemCronograma;

class ItemCronogramaController extends Controller
{
    public function store(Request $request, $planId)
    {
        $validated = $request->validate([
            'id_oficinas' => 'required|exists:oficinas,id',
            'actividad' => 'required|string|max:255',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
            'estado' => 'nullable|in:Pendiente,En curso,Completado,Cancelado',
        ]);

        $validated['id_cronograma'] = $planId;

        ItemCronograma::create($validated);

        return redirect()->back()->with('success', 'Actividad agregada exitosamente.');
    }

    public function update(Request $request, $id)
    {
        $item = ItemCronograma::findOrFail($id);

        $validated = $request->validate([
            'id_oficinas' => 'required|exists:oficinas,id',
            'actividad' => 'required|string|max:255',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
            'estado' => 'nullable|in:Pendiente,En curso,Completado,Cancelado',
        ]);

        $item->update($validated);

        return redirect()->back()->with('success', 'Actividad actualizada exitosamente.');
    }
}
