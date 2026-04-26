<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::insert([
            [
                'name' => 'Administrador del Sistema',
                'email' => 'admin@sistema.com',
                'password' => Hash::make('admin@sistema.com'),
                'rol' => 'ADMIN',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Usuario Miembro',
                'email' => 'miembro@sistema.com',
                'password' => Hash::make('miembro@sistema.com'),
                'rol' => 'MIEMBRO',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
