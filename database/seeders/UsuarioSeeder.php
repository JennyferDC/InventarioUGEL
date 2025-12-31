<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UsuarioSeeder extends Seeder
{
    public function run(): void
    {
        Usuario::insert([
            [
                'usuario' => 'admin',
                'password' => Hash::make('admin123'),
                'nombre_completo' => 'Administrador del Sistema',
                'email' => 'admin@sistema.com',
                'rol' => 'ADMIN',
                'activo' => true,
            ],
            [
                'usuario' => 'miembro',
                'password' => Hash::make('miembro123'),
                'nombre_completo' => 'Usuario Miembro',
                'email' => 'miembro@sistema.com',
                'rol' => 'MIEMBRO',
                'activo' => true,
            ],
        ]);
    }
}
