<?php

ini_set('memory_limit', '2048M');

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\ImportLog;
use App\Models\RawVisit;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

echo "==================================================\n";
echo "   RE-SEEDING REAL DATA FOR 3 MONTHS (JUN, JUL, AUG)\n";
echo "==================================================\n\n";

// 1. Clear old data
echo "Clearing existing RawVisit and ImportLog records...\n";
\Illuminate\Support\Facades\Schema::disableForeignKeyConstraints();
RawVisit::truncate();
ImportLog::truncate();
\Illuminate\Support\Facades\Schema::enableForeignKeyConstraints();
echo "Tables cleared successfully.\n\n";

$petugas = User::where('role', 'petugas')->first() ?: User::first();

$folders = [
    ['path' => base_path('rspad-file/JUNI'), 'month' => 6, 'year' => 2026, 'label' => 'Juni 2026'],
    ['path' => base_path('rspad-file/B. URO'), 'month' => 7, 'year' => 2026, 'label' => 'Juli 2026'],
    ['path' => base_path('rspad-file/AGUSTUS'), 'month' => 8, 'year' => 2026, 'label' => 'Agustus 2026'],
];

$totalVisitsInserted = 0;

foreach ($folders as $fInfo) {
    $folderPath = $fInfo['path'];
    $month = $fInfo['month'];
    $year  = $fInfo['year'];
    $label = $fInfo['label'];

    if (!is_dir($folderPath)) {
        echo "Folder not found: {$folderPath}\n";
        continue;
    }

    $files = glob("{$folderPath}/*.xls");
    $fCount = count($files);
    echo "--- Processing {$label} ({$fCount} files) ---\n";

    foreach ($files as $filePath) {
        $filename = basename($filePath);
        try {
            $spreadsheet = IOFactory::load($filePath);
            
            // Prefer 'R' sheet (pure raw transactions) or 'Lap. kunjungan pasien' or Sheet 0
            $sheet = null;
            if ($spreadsheet->sheetNameExists('R')) {
                $sheet = $spreadsheet->getSheetByName('R');
            } elseif ($spreadsheet->sheetNameExists('Lap. kunjungan pasien')) {
                $sheet = $spreadsheet->getSheetByName('Lap. kunjungan pasien');
            } else {
                $sheet = $spreadsheet->getSheet(0);
            }

            $highestRow = $sheet->getHighestRow();

            // Detect start row and poliklinik
            $startRow = 2; // Default for Sheet R
            $poliFromHeader = null;

            if ($sheet->getTitle() === 'Lap. kunjungan pasien') {
                $startRow = 14;
                for ($r = 1; $r <= 20; $r++) {
                    $cellA = strtoupper(trim((string)$sheet->getCell("A{$r}")->getValue()));
                    $cellB = strtoupper(trim((string)$sheet->getCell("B{$r}")->getValue()));

                    if ($cellA === 'POLIKLINIK') {
                        $poliFromHeader = trim(str_replace(':', '', (string)$sheet->getCell("B{$r}")->getValue()));
                    }

                    if ($cellA === 'NO' && ($cellB === 'NO RM' || str_contains($cellB, 'RM'))) {
                        $startRow = $r + 1;
                    }
                }
            }

            if (empty($poliFromHeader)) {
                $poliFromHeader = pathinfo($filename, PATHINFO_FILENAME);
            }

            $importLog = ImportLog::create([
                'filename' => "{$label}/{$filename}",
                'user_id' => $petugas ? $petugas->id : 1,
                'period_month' => $month,
                'period_year' => $year,
                'total_rows' => 0,
            ]);

            $fileVisitCount = 0;
            $fileRows = [];

            for ($row = $startRow; $row <= $highestRow; $row++) {
                $cellA      = trim((string)$sheet->getCell("A{$row}")->getValue());
                $noRm       = trim((string)$sheet->getCell("B{$row}")->getValue());
                $namaPasien = trim((string)$sheet->getCell("C{$row}")->getValue());

                $lowerRm   = strtolower($noRm);
                $lowerNama = strtolower($namaPasien);
                $lowerA    = strtolower($cellA);

                if (empty($noRm) || $lowerRm === 'no rm' || str_contains($lowerRm, 'total') || str_contains($lowerNama, 'nama pasien') || str_contains($lowerA, 'pasien baru') || str_contains($lowerA, 'pasien lama') || str_contains($lowerRm, 'pasien baru') || str_contains($lowerRm, 'pasien lama')) {
                    continue;
                }

                $tglLahir   = trim((string)$sheet->getCell("D{$row}")->getValue());
                $umur       = trim((string)$sheet->getCell("E{$row}")->getValue());

                // Detect column mapping based on sheet format
                if ($sheet->getTitle() === 'Lap. kunjungan pasien') {
                    $noTelp     = trim((string)$sheet->getCell("F{$row}")->getValue());
                    $noHp       = trim((string)$sheet->getCell("G{$row}")->getValue());
                    $poliklinik = trim((string)$sheet->getCell("H{$row}")->getValue()) ?: $poliFromHeader;
                    $dokter     = trim((string)$sheet->getCell("I{$row}")->getValue());
                    $tglBerobatRaw = trim((string)$sheet->getCell("J{$row}")->getValue());
                    $jam        = trim((string)$sheet->getCell("K{$row}")->getValue());
                    $noSep      = trim((string)$sheet->getCell("L{$row}")->getValue());
                    $noBpjs     = trim((string)$sheet->getCell("M{$row}")->getValue());
                    $statusPasien = trim((string)$sheet->getCell("N{$row}")->getValue());
                    $jenisRawat   = trim((string)$sheet->getCell("O{$row}")->getValue());
                    $jenisPenjamin= trim((string)$sheet->getCell("P{$row}")->getValue());
                    $kelompokRaw  = trim((string)$sheet->getCell("Q{$row}")->getValue());
                    $pangkat    = trim((string)$sheet->getCell("R{$row}")->getValue());
                    $nipNrpPasien = trim((string)$sheet->getCell("S{$row}")->getValue());
                    $gender     = trim((string)$sheet->getCell("T{$row}")->getValue());
                    $agama      = trim((string)$sheet->getCell("U{$row}")->getValue());
                    $pendidikan = trim((string)$sheet->getCell("V{$row}")->getValue());
                    $kesatuan   = trim((string)$sheet->getCell("W{$row}")->getValue());
                    $instansi   = trim((string)$sheet->getCell("X{$row}")->getValue());
                    $kategori   = trim((string)$sheet->getCell("Y{$row}")->getValue());
                    $alamat     = trim((string)$sheet->getCell("Z{$row}")->getValue());
                    $icd10Utama = trim((string)$sheet->getCell("AA{$row}")->getValue());
                    $deskIcdUtama = trim((string)$sheet->getCell("AB{$row}")->getValue());
                    $icd10Sek    = trim((string)$sheet->getCell("AC{$row}")->getValue());
                    $deskIcdSek  = trim((string)$sheet->getCell("AD{$row}")->getValue());
                    $statusRegis = trim((string)$sheet->getCell("AE{$row}")->getValue());
                } else {
                    // Sheet 'R' mapping
                    $statusPasien = trim((string)$sheet->getCell("F{$row}")->getValue());
                    $noTelp     = trim((string)$sheet->getCell("G{$row}")->getValue());
                    $noHp       = trim((string)$sheet->getCell("H{$row}")->getValue());
                    $poliklinik = trim((string)$sheet->getCell("I{$row}")->getValue()) ?: $poliFromHeader;
                    $dokter     = trim((string)$sheet->getCell("J{$row}")->getValue());
                    $tglBerobatRaw = trim((string)$sheet->getCell("K{$row}")->getValue());
                    $jam        = trim((string)$sheet->getCell("L{$row}")->getValue());
                    $noSep      = trim((string)$sheet->getCell("M{$row}")->getValue());
                    $noBpjs     = trim((string)$sheet->getCell("N{$row}")->getValue());
                    $jenisRawat   = trim((string)$sheet->getCell("O{$row}")->getValue());
                    $jenisPenjamin= trim((string)$sheet->getCell("P{$row}")->getValue());
                    $kelompokRaw  = trim((string)$sheet->getCell("Q{$row}")->getValue());
                    $pangkat    = trim((string)$sheet->getCell("R{$row}")->getValue());
                    $nipNrpPasien = trim((string)$sheet->getCell("S{$row}")->getValue());
                    $gender     = trim((string)$sheet->getCell("T{$row}")->getValue());
                    $agama      = trim((string)$sheet->getCell("U{$row}")->getValue());
                    $pendidikan = trim((string)$sheet->getCell("V{$row}")->getValue());
                    $kesatuan   = trim((string)$sheet->getCell("W{$row}")->getValue());
                    $instansi   = trim((string)$sheet->getCell("X{$row}")->getValue());
                    $kategori   = trim((string)$sheet->getCell("Y{$row}")->getValue());
                    $alamat     = trim((string)$sheet->getCell("Z{$row}")->getValue());
                    $icd10Utama = trim((string)$sheet->getCell("AA{$row}")->getValue());
                    $deskIcdUtama = trim((string)$sheet->getCell("AB{$row}")->getValue());
                    $icd10Sek    = trim((string)$sheet->getCell("AC{$row}")->getValue());
                    $deskIcdSek  = trim((string)$sheet->getCell("AD{$row}")->getValue());
                    $statusRegis = trim((string)$sheet->getCell("AE{$row}")->getValue());
                }

                // Date formatting
                $mStr = sprintf('%02d', $month);
                $tglBerobat = "{$year}-{$mStr}-01";
                if (!empty($tglBerobatRaw)) {
                    $ts = strtotime($tglBerobatRaw);
                    if ($ts) {
                        $tglBerobat = date('Y-m-d', $ts);
                    }
                }

                // Auto derive kelompok if blank
                if (empty($kelompokRaw)) {
                    $p   = strtoupper($jenisPenjamin);
                    $pa  = strtoupper($pangkat);
                    $ins = strtoupper($instansi);
                    $kat = strtoupper($kategori);
                    $kes = strtoupper($kesatuan);

                    if (str_contains($p, 'PBI')) {
                        $kelompokRaw = 'BPJS PBI';
                    } elseif (str_contains($p, 'MANDIRI') || str_contains($p, 'SWASTA')) {
                        $kelompokRaw = 'BPJS MANDIRI / SWASTA';
                    } elseif (str_contains($p, 'MILITER') || str_contains($p, 'DINAS') || str_contains($kat, 'MILITER') || !empty($pa)) {
                        if (str_contains($kat, 'KELUARGA') || str_contains($p, 'KELUARGA') || str_contains($kat, 'ISTRI') || str_contains($kat, 'ANAK')) {
                            $kelompokRaw = 'KELUARGA MILITER';
                        } else {
                            $kelompokRaw = 'MILITER TNI AD';
                        }
                    } elseif (str_contains($p, 'PNS') || str_contains($kat, 'PNS') || str_contains($ins, 'KEMHAN') || str_contains($ins, 'TNI')) {
                        if (str_contains($kat, 'KELUARGA') || str_contains($p, 'KELUARGA') || str_contains($kat, 'ISTRI') || str_contains($kat, 'ANAK')) {
                            $kelompokRaw = 'KELUARGA PNS';
                        } else {
                            $kelompokRaw = 'PNS KEMHAN/TNI';
                        }
                    } elseif (str_contains($p, 'PURNA') || str_contains($kat, 'PURNA')) {
                        $kelompokRaw = 'PURNAWIRAWAN';
                    } else {
                        $kelompokRaw = 'UMUM / TUNAI';
                    }
                }

                $fileRows[] = [
                    'import_log_id' => $importLog->id,
                    'no_rm' => $noRm,
                    'nama_pasien' => $namaPasien ?: 'PASIEN UNKNOWN',
                    'tgl_lahir' => $tglLahir,
                    'umur' => $umur,
                    'no_telp' => $noTelp,
                    'no_hp' => $noHp,
                    'poliklinik' => $poliklinik ?: $poliFromHeader,
                    'dokter' => $dokter,
                    'tgl_berobat' => $tglBerobat,
                    'jam' => $jam,
                    'no_sep' => $noSep,
                    'no_bpjs' => $noBpjs,
                    'status_pasien' => $statusPasien ?: 'Pasien Lama',
                    'jenis_rawat' => $jenisRawat ?: 'WATLAN',
                    'jenis_penjamin' => $jenisPenjamin ?: 'BPJS DINAS',
                    'kelompok' => $kelompokRaw,
                    'pangkat' => $pangkat,
                    'nip_nrp_pasien' => $nipNrpPasien,
                    'gender' => $gender ?: 'L',
                    'agama' => $agama,
                    'pendidikan' => $pendidikan,
                    'kesatuan' => $kesatuan,
                    'instansi' => $instansi,
                    'kategori' => $kategori,
                    'alamat' => $alamat,
                    'icd10_utama' => $icd10Utama,
                    'deskripsi_icd10_utama' => $deskIcdUtama,
                    'icd10_sekunder' => $icd10Sek,
                    'deskripsi_icd10_sekunder' => $deskIcdSek,
                    'status_registrasi' => $statusRegis ?: 'open',
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ];

                $fileVisitCount++;
                $totalVisitsInserted++;
            }

            // Batch insert in chunks of 500 with explicit transaction commit
            DB::beginTransaction();
            foreach (array_chunk($fileRows, 500) as $chunk) {
                RawVisit::insert($chunk);
            }
            DB::commit();

            $importLog->update(['total_rows' => $fileVisitCount]);
            echo "  [OK] {$filename} => {$fileVisitCount} records inserted.\n";

            if (isset($spreadsheet)) {
                $spreadsheet->disconnectWorksheets();
                unset($spreadsheet);
            }
            gc_collect_cycles();

        } catch (\Exception $e) {
            echo "  [ERROR] {$filename} => " . $e->getMessage() . "\n";
            if (isset($spreadsheet)) {
                $spreadsheet->disconnectWorksheets();
                unset($spreadsheet);
            }
            gc_collect_cycles();
        }
    }
    echo "\n";
}

echo "==================================================\n";
echo " IMPORT COMPLETED! Total records in database: {$totalVisitsInserted}\n";
echo "==================================================\n";
