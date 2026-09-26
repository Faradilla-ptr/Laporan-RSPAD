<?php

use App\Models\RawVisit;
use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

$total = RawVisit::count();
echo 'TOTAL IN DB: '.$total."\n";

$byMonth = RawVisit::selectRaw('MONTH(tgl_berobat) as m, count(*) as c')
    ->groupBy('m')
    ->pluck('c', 'm');

echo "BY MONTH:\n";
foreach ($byMonth as $m => $c) {
    echo "  Month {$m}: {$c} records\n";
}
