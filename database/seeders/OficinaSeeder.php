<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Oficina;
use App\Models\Area;

class OficinaSeeder extends Seeder
{
    public function run(): void
    {
        $mappings = [
            'Dirección' => [
                [
                    'nombre' => 'Dirección',
                    'descripcion' => 'Oficina principal de Dirección.'
                ]
            ],
            'Unidad de R.R.H.H.' => [
                [
                    'nombre' => 'Procesos Administrativos',
                    'descripcion' => 'Gestión de procesos administrativos disciplinarios y otros del personal.'
                ],
                [
                    'nombre' => 'Planillas',
                    'descripcion' => 'Procesamiento de planillas de pago, remuneraciones y beneficios sociales.'
                ],
                [
                    'nombre' => 'Escalafón',
                    'descripcion' => 'Registro y control del historial laboral y legajos del personal.'
                ],
                [
                    'nombre' => 'Bienestar Social',
                    'descripcion' => 'Servicios de bienestar, salud y asistencia social al trabajador.'
                ],
            ],
            'Unidad de Gestión Administrativa' => [
                [
                    'nombre' => 'Abastecimiento',
                    'descripcion' => 'Adquisiciones, contrataciones y distribución de bienes y servicios.'
                ],
                [
                    'nombre' => 'Contabilidad',
                    'descripcion' => 'Registro contable de las operaciones financieras y presupuestales.'
                ],
                [
                    'nombre' => 'Tesorería',
                    'descripcion' => 'Gestión de caja, pagos, giros y conciliación bancaria.'
                ],
                [
                    'nombre' => 'Patrimonio',
                    'descripcion' => 'Control, registro y conciliación física y contable de los bienes patrimoniales.'
                ],
                [
                    'nombre' => 'Informática',
                    'descripcion' => 'Soporte técnico, redes, telecomunicaciones y sistemas informáticos.'
                ],
            ],
            'Unidad de Gestión Pedagógica' => [
                [
                    'nombre' => 'EVA',
                    'descripcion' => 'Educación Básica Alternativa.'
                ],
                [
                    'nombre' => 'EVE',
                    'descripcion' => 'Educación Básica Especial.'
                ],
                [
                    'nombre' => 'Inicial - Especial',
                    'descripcion' => 'Acompañamiento y soporte técnico pedagógico al nivel inicial y especial.'
                ],
                [
                    'nombre' => 'Primaria',
                    'descripcion' => 'Monitoreo, supervisión y gestión del nivel primario educativo.'
                ],
                [
                    'nombre' => 'Secundaria',
                    'descripcion' => 'Monitoreo, supervisión y gestión del nivel secundario educativo.'
                ],
            ],
            'Unidad de Asesoría Jurídica' => [
                [
                    'nombre' => 'Secretaría Jurídica',
                    'descripcion' => 'Recepción, trámite y archivo de expedientes y resoluciones legales.'
                ]
            ],
            'Unidad de Planeamiento' => [
                [
                    'nombre' => 'Planeación y Racionalización',
                    'descripcion' => 'Planificación estratégica institucional, estructura orgánica y racionalización.'
                ],
                [
                    'nombre' => 'Presupuesto',
                    'descripcion' => 'Programación y formulación del presupuesto institucional.'
                ],
                [
                    'nombre' => 'Infraestructura',
                    'descripcion' => 'Supervisión y control de proyectos de infraestructura y locales escolares.'
                ],
                [
                    'nombre' => 'SIAGIE Estadística',
                    'descripcion' => 'Sistema de Información de Apoyo a la Gestión de la Institución Educativa y estadísticas.'
                ],
            ],
        ];

        foreach ($mappings as $areaNombre => $oficinas) {
            $area = Area::where('nombre', $areaNombre)->first();
            if ($area) {
                foreach ($oficinas as $oficinaData) {
                    Oficina::create([
                        'nombre' => $oficinaData['nombre'],
                        'descripcion' => $oficinaData['descripcion'],
                        'area_id' => $area->id,
                    ]);
                }
            }
        }
    }
}
