<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

echo "Normalizing kelompok values in raw_visits...\n";

DB::statement("UPDATE raw_visits SET kelompok = 'MILITER TNI AD' WHERE kelompok IN ('AD', 'TNI AD')");
DB::statement("UPDATE raw_visits SET kelompok = 'KELUARGA MILITER' WHERE kelompok IN ('KEL AD', 'KEL AL', 'KEL AU', 'KEL MILITER')");
DB::statement("UPDATE raw_visits SET kelompok = 'MILITER TNI AL' WHERE kelompok IN ('AL')");
DB::statement("UPDATE raw_visits SET kelompok = 'MILITER TNI AU' WHERE kelompok IN ('AU')");
DB::statement("UPDATE raw_visits SET kelompok = 'PNS KEMHAN/TNI' WHERE kelompok IN ('PPPK DINAS', 'PPPK KEMENTERIAN', 'PPPK KEMENTRIAN')");
DB::statement("UPDATE raw_visits SET kelompok = 'POLRI & KELUARGA' WHERE kelompok IN ('POLRI', 'KEL POLRI')");

echo "Done! Current breakdown across database:\n";
$res = DB::table('raw_visits')
    ->select('kelompok', DB::raw('count(*) as total'))
    ->groupBy('kelompok')
    ->orderByDesc('total')
    ->get();

echo json_encode($res, JSON_PRETTY_PRINT)."\n";
