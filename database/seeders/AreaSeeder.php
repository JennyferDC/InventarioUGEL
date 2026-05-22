<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Area;

class AreaSeeder extends Seeder
{

    public function run(): void
    {
        $areas = [
            [
                'nombre' => 'Dirección',
                'descripcion' => 'Despacho de Dirección y alta dirección de la institución.',
            ],
            [
                'nombre' => 'Unidad de R.R.H.H.',
                'descripcion' => 'Unidad de Recursos Humanos encargado de la gestión integral del personal.',
            ],
            [
                'nombre' => 'Unidad de Gestión Administrativa',
                'descripcion' => 'Unidad encargada de los recursos financieros, logísticos y de soporte administrativo.',
            ],
            [
                'nombre' => 'Unidad de Gestión Pedagógica',
                'descripcion' => 'Unidad encargada de la dirección, acompañamiento y desarrollo pedagógico en la jurisdicción.',
            ],
            [
                'nombre' => 'Unidad de Asesoría Jurídica',
                'descripcion' => 'Unidad responsable del asesoramiento legal y jurídico de la institución.',
            ],
            [
                'nombre' => 'Unidad de Planeamiento',
                'descripcion' => 'Unidad encargada de la planificación, presupuesto, racionalización e infraestructura.',
            ],
        ];

        foreach ($areas as $area) {
            Area::create($area);
        }
    }
}
