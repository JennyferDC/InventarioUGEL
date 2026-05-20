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

        $personasIds = Persona::where('estado', 'ACTIVO')->pluck('id')->toArray();
        if (empty($personasIds)) {
            $personasIds = [1, 2, 3];
        }
        $count = 1;

        foreach ($personasIds as $personaId) {
            $numEquipos = rand(4, 6);

            for ($i = 0; $i < $numEquipos; $i++) {
                $categoria = $faker->randomElement(['equipo', 'componente', 'programa']);
                if ($categoria === 'componente') {
                    $tipo = $faker->randomElement(['COMPONENTE', 'TECLADO', 'MOUSE', 'MONITOR']);
                } elseif ($categoria === 'programa') {
                    $tipo = 'OTRO';
                } else {
                    $tipo = $faker->randomElement(['PC', 'LAPTOP', 'TODO EN UNO', 'OTRO']);
                }

                $cod_informatica = strtoupper(substr($tipo, 0, 3)) . '-' . str_pad($count, 3, '0', STR_PAD_LEFT);
                $count++;

                $estado = $estados[array_rand($estados)];
                $id_persona = ($estado === 'EN USO') ? $personaId : null;

                $equipo = Equipo::create([
                    'cod_informatica' => $cod_informatica,
                    'tipo' => $tipo,
                    'estado' => $estado,
                    'fecha_ingreso' => $faker->dateTimeBetween('-2 years', 'now'),
                    'fecha_disponible_uso' => clone $faker->dateTimeBetween('-1 years', 'now'),
                    'vida_util_anios' => rand(3, 7),
                    'id_persona' => $id_persona,
                    'observacion_tecnica' => ($estado === 'BAJA') ? $faker->sentence(8) : null,
                    'categoria' => $categoria,
                    'ip' => in_array($tipo, ['PC', 'LAPTOP', 'TODO EN UNO']) ? $faker->ipv4 : null,
                    'clasificacion' => $faker->randomElement(['bueno', 'regular', 'malo']),
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
