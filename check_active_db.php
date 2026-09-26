<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use App\Models\RawVisit;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

echo "=== LARAVEL ACTIVE DB INFO ===\n";
echo 'Driver: '.DB::connection()->getDriverName()."\n";
echo 'Database Name: '.DB::connection()->getDatabaseName()."\n";
echo 'RawVisit Count: '.RawVisit::count()."\n";

$byMonth = RawVisit::selectRaw("strftime('%m', tgl_berobat) as m, count(*) as c")
    ->groupBy('m')
    ->orderBy('m')
    ->pluck('c', 'm');

echo "\nBY MONTH:\n";
foreach ($byMonth as $m => $c) {
    echo "  Month {$m}: {$c} kunjungan\n";
}
