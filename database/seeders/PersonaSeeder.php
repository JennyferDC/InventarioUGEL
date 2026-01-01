<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Persona;

class PersonaSeeder extends Seeder
{

    public function run(): void
    {
        Persona::insert([
            [
                'nombre_completo' => 'Juan Pérez',
                'id_area' => 1,
            ],
            [
                'nombre_completo' => 'María López',
                'id_area' => 2,
            ],
            [
                'nombre_completo' => 'Carlos Ramírez',
                'id_area' => 3,
            ],
        ]);
    }
}
