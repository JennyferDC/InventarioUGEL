<?php
// Simple isolated DB Importer & Debugger
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: text/plain; charset=utf-8');

echo "=== DIAGNÓSTICO Y CONEXIÓN BASE DE DATOS ===\n";

$host = 'localhost';
$db = 'ugelhuanucogob_inventario';
$user = 'ugelhuanucogob_Henrryinv';
$pass = 'VAbGvd22{DyJC6!(';

try {
    echo "Intento de conexión a MySQL (host: $host, db: $db, user: $user)...\n";
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    echo "¡CONEXIÓN EXITOSA!\n\n";

    $sqlFile = __DIR__ . '/../database.sql';
    if (file_exists($sqlFile)) {
        echo "Leyendo database.sql (" . filesize($sqlFile) . " bytes)...\n";
        $sql = file_get_contents($sqlFile);
        echo "Ejecutando sentencias SQL...\n";
        $pdo->exec($sql);
        echo "¡BASE DE DATOS IMPORTADA CON ÉXITO!\n";
        @unlink($sqlFile);
        echo "database.sql eliminado.\n";
    } else {
        echo "database.sql no encontrado o ya fue procesado.\n";
    }
} catch (Exception $e) {
    echo "ERROR PDO: " . $e->getMessage() . "\n";
}

echo "\n--- Creación de Symlink ---\n";
$target = __DIR__ . '/../storage/app/public';
$shortcut = __DIR__ . '/storage';
if (!file_exists($shortcut)) {
    if (@symlink($target, $shortcut)) {
        echo "Symlink creado correctamente.\n";
    } else {
        echo "No se pudo crear el symlink automáticamente (permiso o función deshabilitada).\n";
    }
} else {
    echo "Symlink ya existente.\n";
}

echo "\n=== PROCESO COMPLETADO ===";
