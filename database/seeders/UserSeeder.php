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
                'name' => 'Henry Gonzales',
                'email' => 'informatica@ugelhuanuco.gob.pe',
                'password' => Hash::make('informatica@ugelhuanuco.gob.pe'),
                'rol' => 'ADMIN',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Jennyfer Del Castillo',
                'email' => 'scolyperez@gmail.com',
                'password' => Hash::make('scolyperez@gmail.com'),
                'rol' => 'MIEMBRO',
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
