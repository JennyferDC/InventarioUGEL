<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Area;

class AreaSeeder extends Seeder
{

    public function run(): void
    {
        Area::insert([
            [
                'nombre' => 'Sistemas',
                'descripcion' => 'Área de tecnologías de la información',
            ],
            [
                'nombre' => 'Administración',
                'descripcion' => 'Área administrativa',
            ],
            [
                'nombre' => 'Contabilidad',
                'descripcion' => 'Área contable',
            ],
        ]);
    }
}
