<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: text/plain; charset=utf-8');

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "config('app.url'): " . config('app.url') . "\n";
echo "url('/'): " . url('/') . "\n";
echo "route('login'): " . route('login') . "\n";
echo "route('logout'): " . route('logout') . "\n";
echo "redirect('/')->getTargetUrl(): " . redirect('/')->getTargetUrl() . "\n";
echo "redirect(route('login'))->getTargetUrl(): " . redirect(route('login'))->getTargetUrl() . "\n";
