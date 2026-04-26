<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\ItemCronograma;
use App\Models\CronogramaMantenimiento;
use App\Models\Oficina;
use Carbon\Carbon;

class ItemCronogramaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $cronogramas = CronogramaMantenimiento::all();
        $oficinas = Oficina::all();

        if ($cronogramas->isEmpty() || $oficinas->isEmpty()) {
            return;
        }

        foreach ($cronogramas as $cronograma) {
            $fechaBase = Carbon::createFromFormat('Y-m-d', substr($cronograma->titulo, -4) . '-01-15');
            
            foreach ($oficinas as $index => $oficina) {
                $fechaInicio = $fechaBase->copy()->addDays($index * 2);
                $fechaFin = $fechaInicio->copy()->addDays(1);
                
                ItemCronograma::create([
                    'id_oficinas' => $oficina->id,
                    'id_cronograma' => $cronograma->id,
                    'fecha_inicio' => $fechaInicio->format('Y-m-d'),
                    'fecha_fin' => $fechaFin->format('Y-m-d'),
                    'actividad' => 'MANTENIMIENTO PREDICTIVO, PREVENTIVO Y CORRECTIVO',
                    'estado' => 'Pendiente'
                ]);
            }
        }
    }
}
