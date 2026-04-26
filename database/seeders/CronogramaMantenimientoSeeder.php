<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\CronogramaMantenimiento;

class CronogramaMantenimientoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        CronogramaMantenimiento::create([
            'titulo' => '2026',
            'descripcion' => 'Cronograma general de mantenimiento preventivo, predictivo y correctivo para todas las oficinas en el año 2026.'
        ]);
        
        CronogramaMantenimiento::create([
            'titulo' => '2027',
            'descripcion' => 'Cronograma general de mantenimiento preventivo, predictivo y correctivo para todas las oficinas en el año 2027.'
        ]);
    }
}
