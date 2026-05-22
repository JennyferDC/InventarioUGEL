<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Persona;
use App\Models\Oficina;

class PersonaSeeder extends Seeder
{
    public function run(): void
    {
        $oficinas = Oficina::all();
        $oficina1 = $oficinas->skip(0)->first()?->id ?? 1;
        $oficina2 = $oficinas->skip(1)->first()?->id ?? 2;
        $oficina3 = $oficinas->skip(2)->first()?->id ?? 3;

        Persona::insert([
            [
                'nombre_completo' => 'Juan Pérez',
                'celular' => '987654321',
                'correo' => 'juan.perez@ejemplo.com',
                'cargo' => 'Especialista TI',
                'estado' => 'ACTIVO',
                'id_oficina' => $oficina1,
            ],
            [
                'nombre_completo' => 'María López',
                'celular' => '912345678',
                'correo' => 'maria.lopez@ejemplo.com',
                'cargo' => 'Asistente Administrativo',
                'estado' => 'ACTIVO',
                'id_oficina' => $oficina2,
            ],
            [
                'nombre_completo' => 'Carlos Ramírez',
                'celular' => '998877665',
                'correo' => 'carlos.ramirez@ejemplo.com',
                'cargo' => 'Técnico de Soporte',
                'estado' => 'ACTIVO',
                'id_oficina' => $oficina3,
            ],
        ]);
    }
}
