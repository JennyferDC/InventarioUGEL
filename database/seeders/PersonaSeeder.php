<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

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