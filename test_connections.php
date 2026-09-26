<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

echo "=== TESTING MYSQL CONNECTION ===\n";
try {
    $mysqlCount = DB::connection('mysql')->table('raw_visits')->count();
    echo 'MySQL raw_visits count: '.$mysqlCount."\n";
} catch (Exception $e) {
    echo 'MySQL Error: '.$e->getMessage()."\n";
}

echo "\n=== TESTING SQLITE CONNECTION ===\n";
try {
    $sqliteCount = DB::connection('sqlite')->table('raw_visits')->count();
    echo 'SQLite raw_visits count: '.$sqliteCount."\n";
} catch (Exception $e) {
    echo 'SQLite Error: '.$e->getMessage()."\n";
}
