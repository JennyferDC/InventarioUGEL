<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Oficina;

class OficinaSeeder extends Seeder
{
    public function run(): void
    {
        Oficina::insert([
            // Sistemas
            ['nombre' => 'Soporte Técnico', 'descripcion' => 'Mantenimiento de equipos de computo en general', 'area_id' => 1],
            ['nombre' => 'Desarrollo de Software', 'descripcion' => 'Creación y mantenimiento de sistemas informáticos', 'area_id' => 1],
            ['nombre' => 'Redes y Comunicaciones', 'descripcion' => 'Administración de la infraestructura de red local', 'area_id' => 1],
            ['nombre' => 'Seguridad Informática', 'descripcion' => 'Protección de datos y sistemas de información', 'area_id' => 1],
            
            // Administración
            ['nombre' => 'Recursos Humanos', 'descripcion' => 'Gestión del personal y bienestar laboral general', 'area_id' => 2],
            ['nombre' => 'Logística y Almacén', 'descripcion' => 'Control de inventarios y distribución de recursos', 'area_id' => 2],
            ['nombre' => 'Trámite Documentario', 'descripcion' => 'Recepción y derivación de documentos oficiales externos', 'area_id' => 2],
            ['nombre' => 'Servicios Generales', 'descripcion' => 'Mantenimiento de instalaciones y limpieza general', 'area_id' => 2],
            ['nombre' => 'Asesoría Legal', 'descripcion' => 'Revisión y aprobación de contratos y convenios', 'area_id' => 2],
            ['nombre' => 'Relaciones Públicas', 'descripcion' => 'Atención al usuario y comunicaciones externas corporativas', 'area_id' => 2],
            
            // Contabilidad
            ['nombre' => 'Tesorería', 'descripcion' => 'Gestión de flujos de caja y pagos', 'area_id' => 3],
            ['nombre' => 'Presupuesto', 'descripcion' => 'Planificación y control del gasto público anual', 'area_id' => 3],
            ['nombre' => 'Auditoría Interna', 'descripcion' => 'Revisión de procesos contables y cumplimiento normativo', 'area_id' => 3],
            ['nombre' => 'Remuneraciones', 'descripcion' => 'Cálculo de planillas y pago de sueldos', 'area_id' => 3],
            ['nombre' => 'Patrimonio', 'descripcion' => 'Registro y control de bienes patrimoniales institucionales', 'area_id' => 3],
        ]);
    }
}
