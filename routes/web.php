<?php

use App\Http\Controllers\AreaController;
use App\Http\Controllers\EquipoController;
use App\Http\Controllers\PersonaController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
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

    Route::get('/inventario', [EquipoController::class, 'index'])->name('equipos.index');
    Route::post('/inventario', [EquipoController::class, 'store'])->name('equipos.store');
    Route::get('/inventario/{equipo}', [EquipoController::class, 'show'])->name('equipos.show');
    Route::put('/inventario/{equipo}', [EquipoController::class, 'update'])->name('equipos.update');
    Route::delete('/inventario/{equipo}', [EquipoController::class, 'destroy'])->name('equipos.destroy');
});
