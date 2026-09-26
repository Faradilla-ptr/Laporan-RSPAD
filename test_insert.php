<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\RawVisit;
use App\Models\ImportLog;
use Illuminate\Support\Facades\DB;

$log = ImportLog::create([
    'filename' => 'test.xls',
    'user_id' => 1,
    'period_month' => 6,
    'period_year' => 2026,
    'total_rows' => 1
]);

echo "Log ID created: " . $log->id . "\n";

RawVisit::create([
    'import_log_id' => $log->id,
    'no_rm' => '12345678',
    'nama_pasien' => 'TEST PASIEN',
    'poliklinik' => 'BEDAH ANAK',
    'tgl_berobat' => '2026-06-01',
    'status_pasien' => 'Pasien Lama',
    'jenis_rawat' => 'WATLAN',
    'jenis_penjamin' => 'BPJS DINAS',
    'kelompok' => 'MILITAR AD',
    'gender' => 'L',
    'status_registrasi' => 'open'
]);

echo "RawVisit count after insert: " . RawVisit::count() . "\n";
