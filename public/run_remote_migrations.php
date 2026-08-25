<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: text/plain; charset=utf-8');

echo "=== CORRIENDO MIGRACIONES Y SEEDERS EN SERVIDOR REMOTO ===\n\n";

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

try {
    echo "1. Probando conexión PDO con Laravel DB...\n";
    DB::connection()->getPdo();
    echo "   -> Conexión a la base de datos establecida con éxito.\n\n";

    echo "2. Ejecutando artisan migrate:fresh --seed...\n";
    Artisan::call('migrate:fresh', ['--seed' => true, '--force' => true]);
    echo Artisan::output() . "\n";
    echo "   -> Migraciones y seeders completados.\n\n";

    echo "3. Ejecutando artisan storage:link...\n";
    try {
        Artisan::call('storage:link');
        echo Artisan::output() . "\n";
    } catch (Exception $se) {
        echo "   Notice symlink: " . $se->getMessage() . "\n";
    }

    echo "4. Optimizando cachés de Laravel...\n";
    Artisan::call('config:cache');
    Artisan::call('route:cache');
    Artisan::call('view:cache');
    echo "   -> Caché de configuración, rutas y vistas generada con éxito.\n\n";

    echo "=== DESPLIEGUE FINALIZADO CON ÉXITO ===";

} catch (Exception $e) {
    echo "ERROR DURANTE LA EJECUCIÓN:\n" . $e->getMessage() . "\n" . $e->getTraceAsString();
}
