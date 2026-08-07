<?php
// Script temporal para ejecutar migraciones y optimizaciones en producción
define('LARAVEL_START', microtime(true));

// Token de seguridad
$token = $_GET['token'] ?? '';
$expectedToken = 'VAbGvd22DyJC6';

if ($token !== $expectedToken) {
    header('HTTP/1.1 403 Forbidden');
    die('ERROR: Acceso no autorizado.');
}

// Cargar Laravel (desde public/ hacia la raíz)
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Artisan;

echo "<h3>Iniciando tareas de mantenimiento en produccion...</h3>";

try {
    echo "1. Limpiando cache antigua...<br>";
    try {
        Artisan::call('config:clear');
        echo "config:clear exit code: " . Artisan::output() . "<br>";
    } catch (Exception $e) {
        echo "Warning: config:clear failed: " . $e->getMessage() . "<br>";
    }
    try {
        Artisan::call('route:clear');
        echo "route:clear exit code: " . Artisan::output() . "<br>";
    } catch (Exception $e) {
        echo "Warning: route:clear failed: " . $e->getMessage() . "<br>";
    }
    try {
        Artisan::call('view:clear');
        echo "view:clear exit code: " . Artisan::output() . "<br>";
    } catch (Exception $e) {
        echo "Warning: view:clear failed: " . $e->getMessage() . "<br>";
    }
    try {
        Artisan::call('cache:clear');
        echo "cache:clear exit code: " . Artisan::output() . "<br>";
    } catch (Exception $e) {
        echo "Warning: cache:clear failed: " . $e->getMessage() . "<br>";
    }


    echo "2. Ejecutando migraciones (migrate)...<br>";
    $migrateExitCode = Artisan::call('migrate', ['--force' => true]);
    echo "Codigo de salida de migracion: $migrateExitCode<br>";
    echo "<pre>" . Artisan::output() . "</pre>";

    echo "4. Limpiando y manteniendo cache dinámica de Laravel...<br>";
    Artisan::call('config:clear');
    Artisan::call('route:clear');
    Artisan::call('view:clear');
    echo "<pre>" . Artisan::output() . "</pre>";

    echo "<h3>Mantenimiento completado con exito.</h3>";
} catch (Exception $e) {
    echo "<h3>ERROR durante el mantenimiento:</h3>";
    echo "<pre>" . $e->getMessage() . "</pre>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
