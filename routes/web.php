<?php

use App\Http\Controllers\AreaController;
use App\Http\Controllers\ArchivoInventarioController;
use App\Http\Controllers\CaracteristicaEquipoController;
use App\Http\Controllers\EquipoController;
use App\Http\Controllers\OficinaController;
use App\Http\Controllers\PersonaController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\UsuarioController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');

    Route::get('/areas', [AreaController::class, 'index'])->name('areas.index');
    Route::post('/areas', [AreaController::class, 'store'])->name('areas.store');
    Route::get('/areas/{area}', [AreaController::class, 'show'])->name('areas.show');
    Route::put('/areas/{area}', [AreaController::class, 'update'])->name('areas.update');
    Route::delete('/areas/{area}', [AreaController::class, 'destroy'])->name('areas.destroy');

    Route::get('/personas', [PersonaController::class, 'index'])->name('personas.index');
    Route::post('/personas', [PersonaController::class, 'store'])->name('personas.store');
    Route::get('/personas/{persona}', [PersonaController::class, 'show'])->name('personas.show');
    Route::put('/personas/{persona}', [PersonaController::class, 'update'])->name('personas.update');
    Route::delete('/personas/{persona}', [PersonaController::class, 'destroy'])->name('personas.destroy');

    Route::get('/oficinas', [OficinaController::class, 'index'])->name('oficinas.index');
    Route::post('/oficinas', [OficinaController::class, 'store'])->name('oficinas.store');
    Route::get('/oficinas/{oficina}', [OficinaController::class, 'show'])->name('oficinas.show');
    Route::put('/oficinas/{oficina}', [OficinaController::class, 'update'])->name('oficinas.update');
    Route::delete('/oficinas/{oficina}', [OficinaController::class, 'destroy'])->name('oficinas.destroy');


    Route::get('/inventario', [EquipoController::class, 'index'])->name('equipos.index');
    Route::post('/inventario', [EquipoController::class, 'store'])->name('equipos.store');
    Route::get('/inventario/equipo/{cod_informatica}', [EquipoController::class, 'showByCodigo'])->name('equipos.showByCodigo');
    Route::get('/inventario/{equipo}', [EquipoController::class, 'show'])->name('equipos.show');
    Route::put('/inventario/{equipo}', [EquipoController::class, 'update'])->name('equipos.update');
    Route::delete('/inventario/{equipo}', [EquipoController::class, 'destroy'])->name('equipos.destroy');

    Route::get('/miembros', [UsuarioController::class, 'index'])->name('miembros.index');
    Route::post('/miembros', [UsuarioController::class, 'store'])->name('miembros.store');
    Route::get('/miembros/{usuario}', [UsuarioController::class, 'show'])->name('miembros.show');
    Route::put('/miembros/{usuario}', [UsuarioController::class, 'update'])->name('miembros.update');
    Route::delete('/miembros/{usuario}', [UsuarioController::class, 'destroy'])->name('miembros.destroy');

    Route::get('/mantenimiento', [App\Http\Controllers\CronogramaMantenimientoController::class, 'index'])->name('mantenimiento.index');
    Route::post('/mantenimiento', [App\Http\Controllers\CronogramaMantenimientoController::class, 'store'])->name('mantenimiento.store');
    Route::get('/mantenimiento/{mantenimiento}', [App\Http\Controllers\CronogramaMantenimientoController::class, 'show'])->name('mantenimiento.show');
    Route::put('/mantenimiento/{mantenimiento}', [App\Http\Controllers\CronogramaMantenimientoController::class, 'update'])->name('mantenimiento.update');
    Route::delete('/mantenimiento/{mantenimiento}', [App\Http\Controllers\CronogramaMantenimientoController::class, 'destroy'])->name('mantenimiento.destroy');

    Route::get('/api/caracteristicas', [CaracteristicaEquipoController::class, 'index'])->name('api.caracteristicas.index');
    Route::post('/api/caracteristicas', [CaracteristicaEquipoController::class, 'store'])->name('api.caracteristicas.store');
    Route::get('/api/caracteristicas/{caracteristicaEquipo}', [CaracteristicaEquipoController::class, 'show'])->name('api.caracteristicas.show');
    Route::put('/api/caracteristicas/{caracteristicaEquipo}', [CaracteristicaEquipoController::class, 'update'])->name('api.caracteristicas.update');
    Route::delete('/api/caracteristicas/{caracteristicaEquipo}', [CaracteristicaEquipoController::class, 'destroy'])->name('api.caracteristicas.destroy');

    Route::get('/archivos', [ArchivoInventarioController::class, 'index'])->name('archivos.index');
    Route::post('/archivos', [ArchivoInventarioController::class, 'store'])->name('archivos.store');
    Route::get('/archivos/{archivoInventario}', [ArchivoInventarioController::class, 'show'])->name('archivos.show');
    Route::put('/archivos/{archivoInventario}', [ArchivoInventarioController::class, 'update'])->name('archivos.update');
    Route::delete('/archivos/{archivoInventario}', [ArchivoInventarioController::class, 'destroy'])->name('archivos.destroy');

    Route::post('/reportes/equipos/pdf', [ReporteController::class, 'equiposPdf'])->name('reportes.equipos.pdf');
    Route::post('/reportes/equipos/excel', [ReporteController::class, 'equiposExcel'])->name('reportes.equipos.excel');
});
