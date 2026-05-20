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
                'correo' => 'juan.perez@ejemplo.com',
                'cargo' => 'Especialista TI',
                'estado' => 'ACTIVO',
                'id_oficina' => 1,
            ],
            [
                'nombre_completo' => 'María López',
                'celular' => '912345678',
                'correo' => 'maria.lopez@ejemplo.com',
                'cargo' => 'Asistente Administrativo',
                'estado' => 'ACTIVO',
                'id_oficina' => 2,
            ],
            [
                'nombre_completo' => 'Carlos Ramírez',
                'celular' => '998877665',
                'correo' => 'carlos.ramirez@ejemplo.com',
                'cargo' => 'Técnico de Soporte',
                'estado' => 'ACTIVO',
                'id_oficina' => 3,
            ],
        ]);
    }
}
