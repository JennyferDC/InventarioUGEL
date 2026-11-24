<?php

use App\Http\Controllers\AgenteHardwareController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

/*
|--------------------------------------------------------------------------
| Rutas del Agente de Hardware UGEL
|--------------------------------------------------------------------------
*/
Route::prefix('v1/agente')->group(function () {
    // Catálogo de oficinas para sincronización dinámica del agente
    Route::get('/oficinas', [AgenteHardwareController::class, 'oficinas']);

    // Registro seguro de hardware y responsable vía USB
    Route::middleware('agent.token')->group(function () {
        Route::post('/registro', [AgenteHardwareController::class, 'registro']);
    });
});

// Confirmación de borrador vía API
Route::post('/v1/equipos/{equipo}/confirmar-borrador', [AgenteHardwareController::class, 'confirmarBorrador']);
