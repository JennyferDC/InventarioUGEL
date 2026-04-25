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
                'celular' => '987654321',
                'estado' => 'ACTIVO',
                'id_oficina' => 1,
            ],
            [
                'nombre_completo' => 'María López',
                'celular' => '912345678',
                'estado' => 'ACTIVO',
                'id_oficina' => 2,
            ],
            [
                'nombre_completo' => 'Carlos Ramírez',
                'celular' => '998877665',
                'estado' => 'INACTIVO',
                'id_oficina' => 3,
            ],
        ]);
    }
}
