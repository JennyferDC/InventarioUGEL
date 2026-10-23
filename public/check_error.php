<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: text/plain; charset=utf-8');

$logPath = __DIR__ . '/../storage/logs/laravel.log';
if (!file_exists($logPath)) {
    die("Log not found at $logPath");
}

$content = file_get_contents($logPath);
$errors = preg_split('/\[\d{4}-\d{2}-\d{2}[^\]]+\]/', $content);
$lastErrors = array_slice($errors, -3);

foreach ($lastErrors as $err) {
    echo "==================== ERROR ====================\n";
    $lines = explode("\n", trim($err));
    echo implode("\n", array_slice($lines, 0, 15)) . "\n\n";
}
