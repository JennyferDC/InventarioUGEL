<?php

use App\Models\User;
use App\Models\Equipo;
use App\Models\Persona;
use App\Models\Oficina;
use Inertia\Testing\AssertableInertia as Assert;

test('unauthenticated users are redirected from dashboard to login', function () {
    $response = $this->get('/dashboard');

    $response->assertRedirect('/login');
});

test('authenticated users can access the dashboard and receive correct data props', function () {
    $user = User::factory()->create();

    // Create some initial records to verify calculations do not fail on empty database
    $oficina = Oficina::create([
        'nombre' => 'Oficina Test',
        'area_id' => 1, // Supposing area exists or is mocked/seeded
    ]);

    $persona = Persona::create([
        'nombre_completo' => 'Colaborador Test',
        'estado' => 'ACTIVO',
        'id_oficina' => $oficina->id,
    ]);

    Equipo::create([
        'cod_informatica' => 'EQUTST1',
        'nombre' => 'PC-TEST',
        'tipo' => 'pc',
        'estado' => 'EN USO',
        'categoria' => 'equipo',
        'id_persona' => $persona->id,
        'clasificacion' => 'BUENO',
    ]);

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertStatus(200);

    // Verify Inertia rendering and structure of dashboard props
    $response->assertInertia(fn (Assert $page) => $page
        ->component('Dashboard')
        ->has('metrics', fn (Assert $metrics) => $metrics
            ->has('total_equipos')
            ->has('total_programas')
            ->has('equipos_en_uso')
            ->has('equipos_libres')
            ->has('equipos_baja')
            ->has('total_personas')
            ->has('mantenimientos_pendientes')
            ->has('mantenimientos_realizados')
        )
        ->has('distribucion_clasificacion')
        ->has('distribucion_tipos')
        ->has('distribucion_oficinas')
        ->has('movimientos_recientes')
        ->has('proximos_mantenimientos')
        ->has('alertas')
    );
});
