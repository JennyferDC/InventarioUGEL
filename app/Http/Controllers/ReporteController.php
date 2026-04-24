<?php

namespace App\Http\Controllers;

use App\Models\Equipo;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class ReporteController extends Controller
{
    private function getQueryByReporte(Request $request)
    {
        $tipo = $request->tipo_reporte;
        $filtros = $request->filtros;
        
        $query = Equipo::with('persona.area', 'caracteristicas');

        if ($tipo === 'inventario_general') {
            $query->whereIn('tipo', $filtros['tipos'] ?? [])
                  ->whereIn('estado', $filtros['estados'] ?? []);
        } elseif ($tipo === 'ficha_tecnica') {
            $query->where('id', $filtros['equipo_id'] ?? 0);
        } elseif ($tipo === 'historial_ingresos') {
            $query->whereBetween('fecha_ingreso', [$filtros['fecha_inicio'] ?? '1900-01-01', $filtros['fecha_fin'] ?? '2100-01-01']);
        } elseif ($tipo === 'inventario_persona') {
            $query->where('id_persona', $filtros['persona_id'] ?? 0);
        } elseif ($tipo === 'inventario_area') {
            $query->whereHas('persona', function ($q) use ($filtros) {
                $q->whereIn('id_area', $filtros['areas'] ?? []);
            });
        }
        
        return $query->orderBy('cod_informatica')->get();
    }

    /**
     * Genera un reporte de equipos en formato PDF.
     */
    public function equiposPdf(Request $request)
    {
        $request->validate([
            'tipo_reporte' => 'required|string',
            'filtros' => 'required|array',
        ]);

        $equipos = $this->getQueryByReporte($request);

        if ($request->tipo_reporte === 'ficha_tecnica') {
            $equipo = $equipos->first();
            if (!$equipo) return abort(404, 'Equipo no encontrado');
            
            $filtros = $request->filtros;
            $otros_equipos = collect();
            if (!empty($filtros['incluir_otros_equipos']) && $equipo->persona) {
                $otros_equipos = Equipo::where('id_persona', $equipo->persona->id)
                                        ->where('id', '!=', $equipo->id)
                                        ->get();
            }

            $pdf = Pdf::loadView('reportes.ficha_tecnica_pdf', compact('equipo', 'filtros', 'otros_equipos'));
            return $pdf->download('ficha_tecnica_' . $equipo->cod_informatica . '.pdf');
        }

        $titulo = "Reporte General de Equipos";
        if ($request->tipo_reporte === 'historial_ingresos') $titulo = "Historial de Ingresos";
        if ($request->tipo_reporte === 'inventario_area') $titulo = "Inventario por Áreas";
        if ($request->tipo_reporte === 'inventario_persona') $titulo = "Equipos Asignados al Responsable";

        $pdf = Pdf::loadView('reportes.equipos_pdf', compact('equipos', 'titulo'));
        return $pdf->download('reporte_' . date('Y_m_d_H_i_s') . '.pdf');
    }

    /**
     * Genera un reporte de equipos en formato Excel.
     */
    public function equiposExcel(Request $request)
    {
        $request->validate([
            'tipo_reporte' => 'required|string',
            'filtros' => 'required|array',
        ]);

        if ($request->tipo_reporte === 'ficha_tecnica') {
            return abort(400, 'Excel no soportado para ficha técnica');
        }

        $equipos = $this->getQueryByReporte($request);

        $fileName = 'reporte_' . date('Y_m_d_H_i_s') . '.csv';

        $headers = [
            'Content-type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=$fileName",
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0'
        ];

        $columns = ['Código', 'Tipo', 'Estado', 'Fecha Ingreso', 'Vida Útil (Años)', 'Responsable', 'Área'];

        $callback = function () use ($equipos, $columns) {
            $file = fopen('php://output', 'w');
            
            fputs($file, "\xEF\xBB\xBF");
            fputcsv($file, $columns, ',');

            foreach ($equipos as $equipo) {
                $row = [
                    $equipo->cod_informatica,
                    $equipo->tipo,
                    $equipo->estado,
                    $equipo->fecha_ingreso ? date('d/m/Y', strtotime($equipo->fecha_ingreso)) : 'S/R',
                    $equipo->vida_util_anios ?? '-',
                    $equipo->persona ? $equipo->persona->nombre_completo : 'Sin asignar',
                    ($equipo->persona && $equipo->persona->area) ? $equipo->persona->area->nombre : '-'
                ];
                fputcsv($file, $row, ',');
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
