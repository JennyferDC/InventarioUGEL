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
        
        $equiposTipos = ['pc', 'laptop', 'todo en uno', 'monitor', 'teclado', 'mouse', 'otro (equipo)'];
        $programasTipos = ['institucional', 'navegador', 'ofimática', 'soporte', 'antivirus', 'otro (programas)'];
        
        $estados = ['LIBRE', 'EN USO', 'BAJA'];
        $caracteristicasNombres = ['Marca', 'Modelo', 'Serie', 'Color', 'Procesador', 'RAM', 'Almacenamiento', 'Pantalla'];

        $personasIds = Persona::where('estado', 'ACTIVO')->pluck('id')->toArray();
        if (empty($personasIds)) {
            $personasIds = [1, 2, 3];
        }
        
        $count = 1;

        // We will seed a total of ~50 records to make the inventory look realistic.
        for ($i = 0; $i < 50; $i++) {
            $categoria = $faker->randomElement(['equipo', 'programa']);
            
            if ($categoria === 'programa') {
                $tipo = $faker->randomElement($programasTipos);
                $abrevCat = 'PRO';
                $abrevTipoMap = [
                    'institucional' => 'INS',
                    'navegador' => 'NAV',
                    'ofimática' => 'OFI',
                    'ofimatica' => 'OFI',
                    'soporte' => 'SOP',
                    'antivirus' => 'ANT',
                    'otro (programas)' => 'OTP',
                    'otro' => 'OTP',
                ];
                $abrevTipo = $abrevTipoMap[$tipo] ?? 'OTP';
                
                $nombresProgramas = [
                    'Kaspersky Endpoint Security',
                    'Siga - UGEL',
                    'Siaf - UGEL',
                    'Windows 11 Pro',
                    'Office 365 LTSC',
                    'Adobe Acrobat Reader',
                    'Chrome Browser Enterprise',
                    'Sistema de Control de Asistencias'
                ];
                $nombre = $faker->randomElement($nombresProgramas);
                $nombre_usuario = $faker->userName();
                $cod_patrimonial = null;
                
                // Programs do not have IP, life span, dates, responsibles, classification, or state in the UI.
                $estado = null;
                $fecha_ingreso = null;
                $fecha_disponible_uso = null;
                $vida_util_anios = null;
                $id_persona = null;
                $ip = null;
                $clasificacion = null;
                $observacion_tecnica = null;
            } else {
                $tipo = $faker->randomElement($equiposTipos);
                $abrevCat = 'EQU';
                $abrevTipoMap = [
                    'pc' => 'PC',
                    'laptop' => 'LAP',
                    'todo en uno' => 'TEU',
                    'monitor' => 'MON',
                    'teclado' => 'TEC',
                    'mouse' => 'MOU',
                    'otro' => 'OTR',
                ];
                $abrevTipo = $abrevTipoMap[$tipo] ?? 'OTR';
                
                $nombre = 'PC-' . strtoupper($faker->bothify('???-###'));
                $nombre_usuario = $faker->name();
                $cod_patrimonial = $faker->numerify('74089900####');
                
                $estado = $faker->randomElement($estados);
                $personaId = $faker->randomElement($personasIds);
                $id_persona = ($estado === 'EN USO') ? $personaId : null;
                $fecha_ingreso = $faker->dateTimeBetween('-3 years', '-1 years')->format('Y-m-d');
                $fecha_disponible_uso = $faker->dateTimeBetween('-1 years', 'now')->format('Y-m-d');
                $vida_util_anios = rand(3, 8);
                $ip = in_array($tipo, ['pc', 'laptop', 'todo en uno']) ? $faker->ipv4 : null;
                $clasificacion = $faker->randomElement(['BUENO', 'REGULAR', 'MALO']);
                $observacion_tecnica = ($estado === 'BAJA') ? $faker->sentence(8) : null;
            }

            $cod_informatica = $abrevCat . $abrevTipo . $count;
            $count++;

            $equipo = Equipo::create([
                'cod_informatica' => $cod_informatica,
                'cod_patrimonial' => $cod_patrimonial,
                'nombre' => $nombre,
                'nombre_usuario' => $nombre_usuario,
                'tipo' => $tipo,
                'estado' => $estado,
                'fecha_ingreso' => $fecha_ingreso,
                'fecha_disponible_uso' => $fecha_disponible_uso,
                'vida_util_anios' => $vida_util_anios,
                'id_persona' => $id_persona,
                'observacion_tecnica' => $observacion_tecnica,
                'categoria' => $categoria,
                'ip' => $ip,
                'clasificacion' => $clasificacion,
            ]);

            // Only seed technical specifications (characteristics) for physical equipment
            if ($categoria === 'equipo') {
                $numCaracteristicas = rand(3, 6);
                $claves = $faker->randomElements($caracteristicasNombres, $numCaracteristicas);

                foreach ($claves as $clave) {
                    $valor = '';
                    switch ($clave) {
                        case 'Marca':
                            $valor = $faker->randomElement(['HP', 'Dell', 'Lenovo', 'Asus', 'Logitech', 'Samsung']);
                            break;
                        case 'Modelo':
                            $valor = strtoupper($faker->bothify('??-####'));
                            break;
                        case 'Serie':
                            $valor = strtoupper($faker->bothify('SN-######'));
                            break;
                        case 'Color':
                            $valor = $faker->randomElement(['Negro', 'Plomo', 'Blanco', 'Plateado']);
                            break;
                        case 'Procesador':
                            $valor = $faker->randomElement(['Intel Core i5', 'Intel Core i7', 'AMD Ryzen 5', 'AMD Ryzen 7']);
                            break;
                        case 'RAM':
                            $valor = $faker->randomElement(['8 GB', '16 GB', '32 GB']);
                            break;
                        case 'Almacenamiento':
                            $valor = $faker->randomElement(['256 GB SSD', '512 GB SSD', '1 TB HDD']);
                            break;
                        case 'Pantalla':
                            $valor = $faker->randomElement(['14 pulgadas', '15.6 pulgadas', '21.5 pulgadas', '24 pulgadas']);
                            break;
                        default:
                            $valor = $faker->word;
                    }

                    CaracteristicaEquipo::create([
                        'id_equipo' => $equipo->id,
                        'clave' => $clave,
                        'valor' => $valor,
                    ]);
                }
            }
        }
    }
}

