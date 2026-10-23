<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: text/plain; charset=utf-8');

$logPath = __DIR__ . '/../storage/logs/laravel.log';
$content = file_get_contents($logPath);
preg_match_all('/\[(\d{4}-\d{2}-\d{2}[^\]]+)\]\s+([^\n]+)/', $content, $matches);

$count = count($matches[0]);
echo "Total log entries: $count\n\n";

for ($i = max(0, $count - 5); $i < $count; $i++) {
    echo "[" . $matches[1][$i] . "] " . $matches[2][$i] . "\n";
}
