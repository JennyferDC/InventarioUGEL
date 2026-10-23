<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: text/plain; charset=utf-8');

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

echo "=== SERVER VARIABLES FOR CURRENT REQUEST ===\n";
echo "REQUEST_URI: " . $_SERVER['REQUEST_URI'] . "\n";
echo "SCRIPT_NAME: " . ($_SERVER['SCRIPT_NAME'] ?? '') . "\n";
echo "PHP_SELF: " . ($_SERVER['PHP_SELF'] ?? '') . "\n\n";

$req = Request::capture();
echo "Request baseUrl: '" . $req->getBaseUrl() . "'\n";
echo "Request pathInfo: '" . $req->getPathInfo() . "'\n";
echo "Request path: '" . $req->path() . "'\n\n";

echo "=== TESTING ROUTES FOR '/' ===\n";
try {
    $r = Request::create('/', 'GET');
    $route = Route::getRoutes()->match($r);
    echo "Matched route for '/': " . $route->uri() . " (methods: " . implode(',', $route->methods()) . ")\n";
} catch (\Throwable $t) {
    echo "Match '/' failed: " . $t->getMessage() . "\n";
}

echo "\n=== TESTING ROUTES FOR '/Henrry' ===\n";
try {
    $r = Request::create('/Henrry', 'GET');
    $route = Route::getRoutes()->match($r);
    echo "Matched route for '/Henrry': " . $route->uri() . " (methods: " . implode(',', $route->methods()) . ")\n";
} catch (\Throwable $t) {
    echo "Match '/Henrry' failed: " . $t->getMessage() . "\n";
}

echo "\n=== ALL NON-GET ROUTES ===\n";
foreach (Route::getRoutes() as $rt) {
    if (!in_array('GET', $rt->methods())) {
        echo implode('|', $rt->methods()) . " -> " . $rt->uri() . "\n";
    }
}
