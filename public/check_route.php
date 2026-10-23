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

// Simular la petición real tal como entra por public/index.php
$server = $_SERVER;
$server['REQUEST_URI'] = '/Henrry/';
$server['REQUEST_METHOD'] = 'GET';
$server['SCRIPT_NAME'] = '/Henrry/index.php';
$server['PHP_SELF'] = '/Henrry/index.php';

$req = Request::create('/Henrry/', 'GET', [], [], [], $server);

echo "BaseUrl: '" . $req->getBaseUrl() . "'\n";
echo "PathInfo: '" . $req->getPathInfo() . "'\n";
echo "Path: '" . $req->path() . "'\n";

try {
    $response = $app->handleRequest($req);
    echo "Response status: " . $response->getStatusCode() . "\n";
    echo "Response headers: " . json_encode($response->headers->all()) . "\n";
    echo "Response content preview: " . substr($response->getContent(), 0, 200) . "\n";
} catch (\Throwable $t) {
    echo "Exception: " . get_class($t) . ": " . $t->getMessage() . "\n" . $t->getTraceAsString() . "\n";
}
