<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

header('Content-Type: text/plain; charset=utf-8');

try {
    echo "=== COLUMNAS DE EQUIPOS ===\n";
    $cols = \Illuminate\Support\Facades\Schema::getColumnListing('equipos');
    echo implode(', ', $cols) . "\n\n";
} catch (\Throwable $t) {
    echo "ERROR en columnas: " . $t->getMessage() . "\n\n";
}

try {
    echo "=== PROBANDO QUERY DE INVENTARIO/EQUIPOS ===\n";
    $req = \Illuminate\Http\Request::create('/inventario/equipos', 'GET');
    $controller = new \App\Http\Controllers\EquipoController();
    $res = $controller->index($req);
    echo "Query OK!\n\n";
} catch (\Throwable $t) {
    echo "ERROR en EquipoController::index:\n" . $t->getMessage() . "\n" . $t->getFile() . ':' . $t->getLine() . "\n" . $t->getTraceAsString() . "\n\n";
}

try {
    echo "=== ULTIMAS LINEAS DE LARAVEL.LOG ===\n";
    $logPath = storage_path('logs/laravel.log');
    if (file_exists($logPath)) {
        $lines = array_slice(file($logPath), -50);
        echo implode('', $lines) . "\n";
    } else {
        echo "laravel.log no existe\n";
    }
} catch (\Throwable $t) {
    echo "ERROR al leer log: " . $t->getMessage() . "\n";
}
