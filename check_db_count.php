<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$total = \App\Models\RawVisit::count();
echo "TOTAL IN DB: " . $total . "\n";

$byMonth = \App\Models\RawVisit::selectRaw('MONTH(tgl_berobat) as m, count(*) as c')
    ->groupBy('m')
    ->pluck('c', 'm');

echo "BY MONTH:\n";
foreach ($byMonth as $m => $c) {
    echo "  Month {$m}: {$c} records\n";
}
