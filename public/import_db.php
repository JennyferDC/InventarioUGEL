<?php
// Installer / Deployer Helper Script
header('Content-Type: text/plain; charset=utf-8');

echo "=== INICIANDO CONFIGURACIÓN DE POST-DESPLIEGUE ===\n\n";

$host = 'localhost';
$db = 'ugelhuanucogob_inventario';
$user = 'ugelhuanucogob_Henrryinv';
$pass = 'VAbGvd22{DyJC6!(';

// 1. Conexión e importación de la Base de Datos
try {
    echo "1. Conectando a la Base de Datos MySQL ($db)...\n";
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    echo "   -> Conexión a la BD establecida con éxito.\n";

    $sqlFile = __DIR__ . '/../database.sql';
    if (file_exists($sqlFile)) {
        echo "2. Importando archivo database.sql...\n";
        $sql = file_get_contents($sqlFile);
        $pdo->exec($sql);
        echo "   -> Base de datos importada correctamente.\n";
        @unlink($sqlFile);
        echo "   -> Archivo database.sql temporal eliminado.\n";
    } else {
        echo "2. OMITIDO: No se encontró database.sql (posiblemente ya fue importado).\n";
    }

} catch (Exception $e) {
    echo "   ERROR en la Base de Datos: " . $e->getMessage() . "\n";
}

// 2. Creación del enlace simbólico (storage:link)
echo "\n3. Creando enlace simbólico de almacenamiento (storage:link)...\n";
$target = __DIR__ . '/../storage/app/public';
$shortcut = __DIR__ . '/storage';

if (!file_exists($shortcut)) {
    if (function_exists('symlink')) {
        @symlink($target, $shortcut);
        echo "   -> Symlink creado: public/storage -> storage/app/public\n";
    } else {
        echo "   -> AVISO: La función symlink() está deshabilitada en este servidor.\n";
    }
} else {
    echo "   -> El enlace público storage ya existe.\n";
}

// 3. Optimización de caché de Laravel
echo "\n4. Ejecutando optimización de caché en Laravel...\n";
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    Illuminate\Support\Facades\Artisan::call('config:cache');
    echo "   -> artisan config:cache: EXITO\n";
    Illuminate\Support\Facades\Artisan::call('route:cache');
    echo "   -> artisan route:cache: EXITO\n";
    Illuminate\Support\Facades\Artisan::call('view:cache');
    echo "   -> artisan view:cache: EXITO\n";
} catch (Exception $e) {
    echo "   -> AVISO en Artisan cache: " . $e->getMessage() . "\n";
}

echo "\n=== PROCESO DE POST-DESPLIEGUE FINALIZADO EXITOSAMENTE ===\n";
