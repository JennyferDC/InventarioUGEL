<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use App\Models\ItemCronograma;
use App\Models\Oficina;
use Carbon\Carbon;

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
        ], [
            'id_oficinas.required' => 'Debe seleccionar una oficina.',
            'id_oficinas.exists' => 'La oficina seleccionada no es válida.',
            'actividad.required' => 'La actividad es obligatoria.',
            'fecha_inicio.required' => 'La fecha de inicio es obligatoria.',
            'fecha_fin.required' => 'La fecha de fin es obligatoria.',
            'fecha_fin.after_or_equal' => 'La fecha de fin debe ser igual o posterior a la fecha de inicio.',
        ]);

        // Validar que las actividades de una misma área no se crucen en tiempo dentro de este plan
        $oficina = Oficina::find($request->id_oficinas);
        $areaId = $oficina?->area_id;

        $conflictingItem = ItemCronograma::where('id_cronograma', $planId)
            ->whereHas('oficina', function ($query) use ($areaId, $request) {
                if ($areaId) {
                    $query->where('area_id', $areaId);
                } else {
                    $query->where('id', $request->id_oficinas);
                }
            })
            ->where(function ($query) use ($request) {
                $query->where('fecha_inicio', '<=', $request->fecha_fin)
                      ->where('fecha_fin', '>=', $request->fecha_inicio);
            })
            ->with('oficina.area')
            ->first();

        if ($conflictingItem) {
            $areaNombre = $conflictingItem->oficina?->area?->nombre;
            $oficinaNombre = $conflictingItem->oficina?->nombre ?? 'la oficina';
            $mensajeArea = $areaNombre ? "en el área '{$areaNombre}' ({$oficinaNombre})" : "en {$oficinaNombre}";

            throw ValidationException::withMessages([
                'fecha_inicio' => "Ya existe una actividad programada {$mensajeArea} del " . 
                    Carbon::parse($conflictingItem->fecha_inicio)->format('d/m/Y') . " al " . 
                    Carbon::parse($conflictingItem->fecha_fin)->format('d/m/Y') . " ('{$conflictingItem->actividad}'). Las actividades de una misma área no pueden cruzarse en fechas.",
            ]);
        }

        $validated['id_cronograma'] = $planId;

        ItemCronograma::create($validated);

        return redirect()->back()->with('flash', [
            'banner' => 'Actividad agregada exitosamente.',
            'bannerStyle' => 'success',
        ]);
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
        ], [
            'id_oficinas.required' => 'Debe seleccionar una oficina.',
            'id_oficinas.exists' => 'La oficina seleccionada no es válida.',
            'actividad.required' => 'La actividad es obligatoria.',
            'fecha_inicio.required' => 'La fecha de inicio es obligatoria.',
            'fecha_fin.required' => 'La fecha de fin es obligatoria.',
            'fecha_fin.after_or_equal' => 'La fecha de fin debe ser igual o posterior a la fecha de inicio.',
        ]);

        // Validar que las actividades de una misma área no se crucen en tiempo dentro de este plan (excluyendo el item actual)
        $oficina = Oficina::find($request->id_oficinas);
        $areaId = $oficina?->area_id;

        $conflictingItem = ItemCronograma::where('id_cronograma', $item->id_cronograma)
            ->where('id', '!=', $item->id)
            ->whereHas('oficina', function ($query) use ($areaId, $request) {
                if ($areaId) {
                    $query->where('area_id', $areaId);
                } else {
                    $query->where('id', $request->id_oficinas);
                }
            })
            ->where(function ($query) use ($request) {
                $query->where('fecha_inicio', '<=', $request->fecha_fin)
                      ->where('fecha_fin', '>=', $request->fecha_inicio);
            })
            ->with('oficina.area')
            ->first();

        if ($conflictingItem) {
            $areaNombre = $conflictingItem->oficina?->area?->nombre;
            $oficinaNombre = $conflictingItem->oficina?->nombre ?? 'la oficina';
            $mensajeArea = $areaNombre ? "en el área '{$areaNombre}' ({$oficinaNombre})" : "en {$oficinaNombre}";

            throw ValidationException::withMessages([
                'fecha_inicio' => "Ya existe una actividad programada {$mensajeArea} del " . 
                    Carbon::parse($conflictingItem->fecha_inicio)->format('d/m/Y') . " al " . 
                    Carbon::parse($conflictingItem->fecha_fin)->format('d/m/Y') . " ('{$conflictingItem->actividad}'). Las actividades de una misma área no pueden cruzarse en fechas.",
            ]);
        }

        $item->update($validated);

        return redirect()->back()->with('flash', [
            'banner' => 'Actividad actualizada exitosamente.',
            'bannerStyle' => 'success',
        ]);
    }

    public function destroy($id)
    {
        $item = ItemCronograma::findOrFail($id);
        $item->delete();

        return redirect()->back()->with('flash', [
            'banner' => 'Actividad eliminada exitosamente.',
            'bannerStyle' => 'success',
        ]);
    }
}
