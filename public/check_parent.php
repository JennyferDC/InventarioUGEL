<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: text/plain; charset=utf-8');

$parent = realpath(__DIR__ . '/../../');
echo "Parent directory: $parent\n";

if ($parent && file_exists("$parent/.htaccess")) {
    echo "=== PARENT .HTACCESS ===\n";
    echo file_get_contents("$parent/.htaccess") . "\n";
} else {
    echo "Parent .htaccess not found\n";
}

$currentHtaccess = realpath(__DIR__ . '/../.htaccess');
echo "=== CURRENT ROOT .HTACCESS ($currentHtaccess) ===\n";
if (file_exists($currentHtaccess)) {
    echo file_get_contents($currentHtaccess) . "\n";
}
