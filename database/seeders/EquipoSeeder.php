<?php

namespace Database\Seeders;


use Illuminate\Database\Seeder;
use App\Models\Equipo;

class EquipoSeeder extends Seeder
{
    public function run(): void
    {
        Equipo::insert([
            [
                'cod_informatica' => 'PC-001',
                'tipo' => 'PC',
                'estado' => 'ACTIVO',
                'fecha_disponible_uso' => now(),
                'vida_util_anios' => 5,
                'id_persona' => 1,
            ],
            [
                'cod_informatica' => 'LAP-002',
                'tipo' => 'LAPTOP',
                'estado' => 'ACTIVO',
                'fecha_disponible_uso' => now(),
                'vida_util_anios' => 4,
                'id_persona' => 2,
            ],
            [
                'cod_informatica' => 'IMP-003',
                'tipo' => 'IMPRESORA',
                'estado' => 'MANTENIMIENTO',
                'fecha_disponible_uso' => now(),
                'vida_util_anios' => 6,
                'id_persona' => 3,
            ],
        ]);
    }
}
