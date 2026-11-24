<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\Equipo;
use App\Models\Oficina;
use App\Models\Persona;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgenteHardwareTest extends TestCase
{
    use RefreshDatabase;

    private string $agentToken = 'ugel_agente_hardware_secret_token_2026';
    private int $oficinaId;

    protected function setUp(): void
    {
        parent::setUp();

        User::firstOrCreate(
            ['email' => 'admin@ugelhuanuco.gob.pe'],
            [
                'name' => 'Admin Test',
                'password' => bcrypt('password'),
                'rol' => 'ADMIN',
                'activo' => true,
            ]
        );

        $area = Area::firstOrCreate(['nombre' => 'Área de Prueba']);
        $oficina = Oficina::firstOrCreate(
            ['nombre' => 'Informática'],
            ['area_id' => $area->id]
        );
        $this->oficinaId = $oficina->id;
    }

    public function test_rechaza_peticion_sin_token_de_agente(): void
    {
        $response = $this->postJson('/api/v1/agente/registro', []);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
            ]);
    }

    public function test_rechaza_peticion_con_token_invalido(): void
    {
        $response = $this->withHeaders([
            'X-Agent-Token' => 'token_invalido_xyz',
        ])->postJson('/api/v1/agente/registro', []);

        $response->assertStatus(401);
    }

    public function test_puede_obtener_catalogo_de_oficinas(): void
    {
        $response = $this->getJson('/api/v1/agente/oficinas');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => ['id', 'nombre', 'area_nombre'],
                ],
            ]);
    }

    public function test_registra_equipo_nuevo_en_estado_borrador_y_persona(): void
    {
        $payload = [
            'persona' => [
                'nombre_completo' => 'pedro alberto quispe',
                'id_oficina' => $this->oficinaId,
                'cargo' => 'Técnico de Soporte',
                'celular' => '955112233',
                'correo' => 'pquispe@ugelhuanuco.gob.pe',
            ],
            'equipo' => [
                'nombre' => 'DESKTOP-SOPORTE-01',
                'tipo' => 'PC',
                'ip' => '192.168.1.150',
                'cod_serial' => 'TESTSERIAL12345',
                'caracteristicas' => [
                    ['clave' => 'procesador', 'valor' => 'Intel Core i5-10400 @ 2.90GHz'],
                    ['clave' => 'ram', 'valor' => '16 GB DDR4'],
                    ['clave' => 'almacenamiento', 'valor' => 'SSD Kingston 480 GB'],
                    ['clave' => 'placa_madre', 'valor' => 'Gigabyte H410M'],
                    ['clave' => 'sistema_operativo', 'valor' => 'Windows 10 Pro 64-bit'],
                    ['clave' => 'mac', 'valor' => '11:22:33:44:55:66'],
                ],
            ],
        ];

        $response = $this->withHeaders([
            'X-Agent-Token' => $this->agentToken,
        ])->postJson('/api/v1/agente/registro', $payload);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'action' => 'CREADO_BORRADOR',
            ]);

        // Verificar que la persona se guardó con Title Case
        $this->assertDatabaseHas('personas', [
            'nombre_completo' => 'Pedro Alberto Quispe',
            'id_oficina' => $this->oficinaId,
            'estado' => 'ACTIVO',
        ]);

        // Verificar que el equipo se registró como BORRADOR
        $this->assertDatabaseHas('equipos', [
            'cod_serial' => 'TESTSERIAL12345',
            'estado' => 'BORRADOR',
            'tipo' => 'PC',
        ]);

        // Verificar características guardadas
        $equipo = Equipo::where('cod_serial', 'TESTSERIAL12345')->first();
        $this->assertNotNull($equipo);
        $this->assertStringStartsWith('EQUPC', $equipo->cod_informatica);
        $this->assertCount(6, $equipo->caracteristicas);

        // Verificar historial registrado
        $this->assertDatabaseHas('historial_movimientos', [
            'id_equipo' => $equipo->id,
            'tipo_accion' => 'CREACION',
        ]);
    }

    public function test_actualiza_equipo_existente_detectado_por_mac_sin_duplicar(): void
    {
        // Primero registrar equipo
        $equipo = Equipo::create([
            'cod_informatica' => 'EQUPC999',
            'cod_serial' => 'TESTSERIAL12345',
            'nombre' => 'DESKTOP-EXISTENTE',
            'tipo' => 'PC',
            'estado' => 'BORRADOR',
            'categoria' => 'equipo',
        ]);
        $equipo->caracteristicas()->create([
            'clave' => 'mac',
            'valor' => '11:22:33:44:55:66',
        ]);
        $equipo->caracteristicas()->create([
            'clave' => 'ram',
            'valor' => '8 GB DDR4',
        ]);

        $payload = [
            'persona' => [
                'nombre_completo' => 'Pedro Alberto Quispe',
                'id_oficina' => $this->oficinaId,
            ],
            'equipo' => [
                'nombre' => 'DESKTOP-SOPORTE-MODIFICADO',
                'tipo' => 'PC',
                'ip' => '192.168.1.155',
                'cod_serial' => 'TESTSERIAL12345',
                'caracteristicas' => [
                    ['clave' => 'procesador', 'valor' => 'Intel Core i5-10400 @ 2.90GHz (Actualizado)'],
                    ['clave' => 'ram', 'valor' => '32 GB DDR4'],
                    ['clave' => 'mac', 'valor' => '11:22:33:44:55:66'],
                ],
            ],
        ];

        $response = $this->withHeaders([
            'X-Agent-Token' => $this->agentToken,
        ])->postJson('/api/v1/agente/registro', $payload);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'action' => 'ACTUALIZADO',
            ]);

        // Verificar que solo existe un registro del equipo con ese serial
        $this->assertEquals(1, Equipo::where('cod_serial', 'TESTSERIAL12345')->count());

        // Verificar que la característica ram se actualizó a 32 GB
        $equipoActualizado = Equipo::where('cod_serial', 'TESTSERIAL12345')->first();
        $charRam = $equipoActualizado->caracteristicas()->where('clave', 'ram')->first();
        $this->assertEquals('32 GB DDR4', $charRam->valor);
    }

    public function test_confirmar_equipo_borrador(): void
    {
        $equipo = Equipo::create([
            'cod_informatica' => 'EQUPC888',
            'cod_serial' => 'TESTSERIAL888',
            'tipo' => 'PC',
            'estado' => 'BORRADOR',
            'categoria' => 'equipo',
        ]);

        $response = $this->postJson("/api/v1/equipos/{$equipo->id}/confirmar-borrador", [
            'cod_patrimonial' => 'PAT-2026-9999',
            'clasificacion' => 'BUENO',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $equipo->refresh();
        $this->assertEquals('PAT-2026-9999', $equipo->cod_patrimonial);
        $this->assertEquals('LIBRE', $equipo->estado);
    }

    public function test_descargar_agente_exitoso(): void
    {
        $response = $this->get('/descargas/agente-inventario');

        $response->assertStatus(200);
        $this->assertStringContainsString('agente_ugel.exe', $response->headers->get('Content-Disposition') ?? '');
    }

    public function test_registra_y_reutiliza_programas_detectados(): void
    {
        // 1. Crear un programa previo en el catálogo institucional
        $chrome = Equipo::create([
            'cod_informatica' => 'PRONAV1',
            'nombre' => 'Google Chrome',
            'tipo' => 'Navegador',
            'estado' => 'OPERATIVO',
            'categoria' => 'programa',
        ]);

        $payload = [
            'persona' => [
                'nombre_completo' => 'Carlos Mendoza Ruiz',
                'id_oficina' => $this->oficinaId,
                'celular' => '944888111',
                'correo' => 'cmendoza@ugelhuanuco.gob.pe',
            ],
            'equipo' => [
                'nombre' => 'PC-DOCENTE-01',
                'tipo' => 'PC',
                'cod_serial' => 'SERIAL-PROG-TEST-001',
                'caracteristicas' => [
                    ['clave' => 'procesador', 'valor' => 'Intel Core i7'],
                ],
                'programas' => [
                    ['nombre' => 'Google Chrome', 'version' => '120.0', 'fabricante' => 'Google LLC'],
                    ['nombre' => 'AnyDesk', 'version' => '8.0.8', 'fabricante' => 'AnyDesk Software GmbH'],
                ],
            ],
        ];

        $response = $this->withHeaders([
            'X-Agent-Token' => $this->agentToken,
        ])->postJson('/api/v1/agente/registro', $payload);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'action' => 'CREADO_BORRADOR',
            ]);

        // Verificar que Google Chrome se reutilizó (sigue habiendo solo 1)
        $this->assertEquals(1, Equipo::where('categoria', 'programa')->where('nombre', 'Google Chrome')->count());

        // Verificar que AnyDesk se creó con tipo 'Soporte'
        $anydesk = Equipo::where('categoria', 'programa')->where('nombre', 'AnyDesk')->first();
        $this->assertNotNull($anydesk);
        $this->assertEquals('Soporte', $anydesk->tipo);

        // Verificar que el equipo tiene vinculados ambos programas en equipo_programa
        $equipo = Equipo::where('cod_serial', 'SERIAL-PROG-TEST-001')->first();
        $this->assertNotNull($equipo);
        $this->assertCount(2, $equipo->programas);
        $this->assertTrue($equipo->programas->contains('id', $chrome->id));
        $this->assertTrue($equipo->programas->contains('id', $anydesk->id));
    }

    public function test_reutiliza_persona_por_correo_o_celular(): void
    {
        $persona = Persona::create([
            'nombre_completo' => 'Maria Elena Lopez',
            'id_oficina' => $this->oficinaId,
            'celular' => '933444555',
            'correo' => 'mlopez@ugelhuanuco.gob.pe',
            'estado' => 'ACTIVO',
        ]);

        $payload = [
            'persona' => [
                'nombre_completo' => 'Maria Lopez', // Variación en el nombre
                'id_oficina' => $this->oficinaId,
                'celular' => '933444555',
                'correo' => 'mlopez@ugelhuanuco.gob.pe',
            ],
            'equipo' => [
                'nombre' => 'PC-MARIA',
                'tipo' => 'PC',
                'cod_serial' => 'SERIAL-MARIA-01',
            ],
        ];

        $response = $this->withHeaders([
            'X-Agent-Token' => $this->agentToken,
        ])->postJson('/api/v1/agente/registro', $payload);

        $response->assertStatus(201);

        // Verificar que NO se creó una segunda persona
        $this->assertEquals(1, Persona::where('correo', 'mlopez@ugelhuanuco.gob.pe')->count());

        $equipo = Equipo::where('cod_serial', 'SERIAL-MARIA-01')->first();
        $this->assertEquals($persona->id, $equipo->id_persona);
    }

    public function test_alerta_conflicto_si_celular_pertenece_a_otra_persona(): void
    {
        Persona::create([
            'nombre_completo' => 'Roberto Carlos Diaz',
            'id_oficina' => $this->oficinaId,
            'celular' => '911222333',
            'correo' => 'rdiaz@ugelhuanuco.gob.pe',
            'estado' => 'ACTIVO',
        ]);

        $payload = [
            'persona' => [
                'nombre_completo' => 'Esteban Quito',
                'id_oficina' => $this->oficinaId,
                'celular' => '911222333', // Mismo celular de Roberto
                'correo' => 'equito@ugelhuanuco.gob.pe', // Correo distinto
            ],
            'equipo' => [
                'nombre' => 'PC-ESTEBAN',
                'tipo' => 'PC',
                'cod_serial' => 'SERIAL-ESTEBAN-01',
            ],
        ];

        $response = $this->withHeaders([
            'X-Agent-Token' => $this->agentToken,
        ])->postJson('/api/v1/agente/registro', $payload);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);

        $this->assertStringContainsString('Conflicto de datos', $response->json('message'));
    }
}
