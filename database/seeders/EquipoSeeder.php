<?php

namespace Database\Seeders;


use Illuminate\Database\Seeder;
use App\Models\Equipo;

use App\Models\CaracteristicaEquipo;
use App\Models\Persona;
use Faker\Factory as Faker;

class EquipoSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create();
        $tipos = ['PC', 'LAPTOP', 'TODO EN UNO', 'COMPONENTE', 'TECLADO', 'MOUSE', 'OTRO', 'MONITOR'];
        $estados = ['LIBRE', 'EN USO', 'BAJA'];
        $caracteristicasNombres = ['Marca', 'Modelo', 'Serie', 'Color', 'Procesador', 'RAM', 'Almacenamiento', 'Pantalla'];

        $personasIds = [1, 2, 3];
        $count = 1;

        foreach ($personasIds as $personaId) {
            $numEquipos = rand(4, 6);

            for ($i = 0; $i < $numEquipos; $i++) {
                $tipo = $tipos[array_rand($tipos)];
                $cod_informatica = strtoupper(substr($tipo, 0, 3)) . '-' . str_pad($count, 3, '0', STR_PAD_LEFT);
                $count++;

                $equipo = Equipo::create([
                    'cod_informatica' => $cod_informatica,
                    'tipo' => $tipo,
                    'estado' => $estados[array_rand($estados)],
                    'fecha_ingreso' => $faker->dateTimeBetween('-2 years', 'now'),
                    'fecha_disponible_uso' => clone $faker->dateTimeBetween('-1 years', 'now'),
                    'vida_util_anios' => rand(3, 7),
                    'id_persona' => $personaId,
                ]);

                $numCaracteristicas = rand(3, 7);
                $claves = $faker->randomElements($caracteristicasNombres, $numCaracteristicas);

                foreach ($claves as $clave) {
                    CaracteristicaEquipo::create([
                        'id_equipo' => $equipo->id,
                        'clave' => $clave,
                        'valor' => $faker->word,
                    ]);
                }
            }
        }
    }
}
