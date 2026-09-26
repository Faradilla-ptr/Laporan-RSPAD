<?php

ini_set('memory_limit', '2048M');

use App\Models\ImportLog;
use App\Models\RawVisit;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

echo "==================================================\n";
echo "   RE-SEEDING REAL DATA FOR 2 MONTHS (JUN & JUL)\n";
echo "==================================================\n\n";

// 1. Clear old data
echo "Clearing existing RawVisit and ImportLog records...\n";
Schema::disableForeignKeyConstraints();
RawVisit::truncate();
ImportLog::truncate();
Schema::enableForeignKeyConstraints();
echo "Tables cleared successfully.\n\n";

$petugas = User::where('role', 'petugas')->first() ?: User::first();

// Only June and July (August excluded per user request)
$folders = [
    ['path' => base_path('rspad-file/JUNI'), 'month' => 6, 'year' => 2026, 'label' => 'Juni 2026'],
    ['path' => base_path('rspad-file/B. URO'), 'month' => 7, 'year' => 2026, 'label' => 'Juli 2026'],
];

function normalizePoliName($rawPoli, $filename)
{
    $fn = strtoupper(pathinfo($filename, PATHINFO_FILENAME));

    if (str_contains($fn, 'ANAK')) {
        return 'BEDAH ANAK';
    }
    if (str_contains($fn, 'DIGEST')) {
        return 'BEDAH DIGESTIF';
    }
    if (str_contains($fn, 'ORTO')) {
        return 'BEDAH ORTOPEDI';
    }
    if (str_contains($fn, 'PLASTIK')) {
        return 'BEDAH PLASTIK';
    }
    if (str_contains($fn, 'THORA')) {
        return 'BEDAH THORAKS';
    }
    if (str_contains($fn, 'TUMOR')) {
        return 'BEDAH TUMOR';
    }
    if (str_contains($fn, 'URO')) {
        return 'BEDAH UROLOGI';
    }
    if (str_contains($fn, 'VASKULER')) {
        return 'BEDAH VASKULER';
    }
    if ($fn === 'B. SARAF' || str_starts_with($fn, 'B. SARAF') || str_contains($fn, 'BEDAH SARAF')) {
        return 'BEDAH SARAF';
    }
    if (str_contains($fn, 'SARAF')) {
        return 'SARAF';
    }
    if (str_contains($fn, 'JANTUNG')) {
        return 'JANTUNG';
    }
    if (str_contains($fn, 'MATA')) {
        return 'MATA';
    }
    if (str_contains($fn, 'OBGIN')) {
        return 'OBGIN';
    }
    if (str_contains($fn, 'PARU')) {
        return 'PARU';
    }
    if (str_contains($fn, 'GIGI')) {
        return 'GIGI & MULUT';
    }
    if ($fn === 'PD' || str_contains($fn, 'PD ') || str_contains($fn, 'PENYAKIT DALAM')) {
        return 'PENYAKIT DALAM';
    }

    $cleanRaw = trim(strtoupper($rawPoli));
    if (! empty($cleanRaw)) {
        if (str_starts_with($cleanRaw, 'PENYAKIT DALAM')) {
            return 'PENYAKIT DALAM';
        }

        return $cleanRaw;
    }

    return $fn;
}

function parseSheetHeader($sheet)
{
    $highestRow = min($sheet->getHighestRow(), 30);
    $headerRow = null;

    for ($r = 1; $r <= $highestRow; $r++) {
        for ($c = 'A'; $c != 'AE'; $c++) {
            $val = strtoupper(trim((string) $sheet->getCell("{$c}{$r}")->getValue()));
            if ($val === 'NO RM' || $val === 'NO. RM' || str_contains($val, 'NO RM')) {
                $headerRow = $r;
                break 2;
            }
        }
    }

    if (! $headerRow) {
        return null;
    }

    $colMap = [];
    $highestColumn = $sheet->getHighestColumn();
    $highestColIndex = Coordinate::columnIndexFromString($highestColumn);

    for ($col = 1; $col <= $highestColIndex; $col++) {
        $colLetter = Coordinate::stringFromColumnIndex($col);
        $headerText = strtoupper(trim((string) $sheet->getCell("{$colLetter}{$headerRow}")->getValue()));

        if (empty($headerText)) {
            continue;
        }

        if (in_array($headerText, ['NO RM', 'NO. RM', 'NORM'])) {
            $colMap['no_rm'] = $colLetter;
        } elseif (in_array($headerText, ['NAMA PASIEN', 'NAMA'])) {
            $colMap['nama_pasien'] = $colLetter;
        } elseif (in_array($headerText, ['TANGGAL LAHIR', 'TGL LAHIR', 'TGL. LAHIR'])) {
            $colMap['tgl_lahir'] = $colLetter;
        } elseif (in_array($headerText, ['USIA', 'UMUR'])) {
            $colMap['umur'] = $colLetter;
        } elseif (in_array($headerText, ['TYPE PASIEN', 'STATUS PASIEN', 'PASIEN'])) {
            $colMap['status_pasien'] = $colLetter;
        } elseif (in_array($headerText, ['JENIS PEMBAYARAN', 'JENIS PENJAMIN', 'PEMBAYARAN', 'PENJAMIN'])) {
            $colMap['jenis_penjamin'] = $colLetter;
        } elseif (in_array($headerText, ['KELOMPOK'])) {
            $colMap['kelompok'] = $colLetter;
        } elseif (in_array($headerText, ['PANGKAT'])) {
            $colMap['pangkat'] = $colLetter;
        } elseif (in_array($headerText, ['KESATUAN'])) {
            $colMap['kesatuan'] = $colLetter;
        } elseif (in_array($headerText, ['ANGKATAN', 'INSTANSI'])) {
            $colMap['instansi'] = $colLetter;
        } elseif (in_array($headerText, ['HUBUNGAN KELUARGA', 'KATEGORI'])) {
            $colMap['kategori'] = $colLetter;
        } elseif (in_array($headerText, ['NO PESERTA', 'NO. PESERTA', 'NO BPJS', 'NO. BPJS'])) {
            $colMap['no_bpjs'] = $colLetter;
        } elseif (in_array($headerText, ['NO. TELP', 'NO TELP', 'TELP'])) {
            $colMap['no_telp'] = $colLetter;
        } elseif (in_array($headerText, ['NO. PONSEL', 'NO PONSEL', 'NO HP', 'NO. HP', 'HP', 'PONSEL'])) {
            $colMap['no_hp'] = $colLetter;
        } elseif (in_array($headerText, ['POLI', 'POLIKLINIK'])) {
            $colMap['poliklinik'] = $colLetter;
        } elseif (in_array($headerText, ['DOKTER'])) {
            $colMap['dokter'] = $colLetter;
        } elseif (in_array($headerText, ['TANGGAL', 'TANGGAL BEROBAT', 'TGL BEROBAT', 'TGL. BEROBAT'])) {
            $colMap['tgl_berobat'] = $colLetter;
        } elseif (in_array($headerText, ['JAM'])) {
            $colMap['jam'] = $colLetter;
        } elseif (in_array($headerText, ['NO SEP', 'NO. SEP', 'SEP'])) {
            $colMap['no_sep'] = $colLetter;
        } elseif (in_array($headerText, ['JENIS RAWAT'])) {
            $colMap['jenis_rawat'] = $colLetter;
        } elseif (in_array($headerText, ['NRP', 'NIP', 'NIP/NRP', 'NIP NRP', 'NIP/NRP PASIEN'])) {
            $colMap['nip_nrp_pasien'] = $colLetter;
        } elseif (in_array($headerText, ['KELAMIN', 'SEX', 'GENDER'])) {
            $colMap['gender'] = $colLetter;
        } elseif (in_array($headerText, ['AGAMA'])) {
            $colMap['agama'] = $colLetter;
        } elseif (in_array($headerText, ['PENDIDIKAN'])) {
            $colMap['pendidikan'] = $colLetter;
        } elseif (in_array($headerText, ['ALAMAT'])) {
            $colMap['alamat'] = $colLetter;
        } elseif (in_array($headerText, ['DIAGNOSA AWAL', 'ICD 10 UTAMA', 'DESKRIPSI ICD 10 UTAMA'])) {
            $colMap['deskripsi_icd10_utama'] = $colLetter;
        } elseif (in_array($headerText, ['DIAGNOSA AKHIR', 'ICD 10 SEKUNDER', 'DESKRIPSI ICD 10 SEKUNDER'])) {
            $colMap['deskripsi_icd10_sekunder'] = $colLetter;
        }
    }

    return ['header_row' => $headerRow, 'col_map' => $colMap];
}

function deriveKelompok($kelompokRaw, $jenisPenjamin, $pangkat, $instansi, $kategori, $kesatuan)
{
    $kel = trim(strtoupper($kelompokRaw));
    if (! empty($kel) && ! in_array($kel, ['--', 'TIDAK ADA', 'NULL'])) {
        if (str_contains($kel, 'MILITER') || str_contains($kel, 'TNI AD') || $kel === 'TNI AD') {
            return 'MILITER TNI AD';
        }
        if (str_contains($kel, 'KEL') && (str_contains($kel, 'AD') || str_contains($kel, 'MILITER'))) {
            return 'KELUARGA MILITER';
        }
        if (str_contains($kel, 'PNS') && ! str_contains($kel, 'KEL')) {
            return 'PNS KEMHAN/TNI';
        }
        if (str_contains($kel, 'PNS') && str_contains($kel, 'KEL')) {
            return 'KELUARGA PNS';
        }
        if (str_contains($kel, 'PURNA')) {
            return 'PURNAWIRAWAN';
        }
        if (str_contains($kel, 'PBI')) {
            return 'BPJS PBI';
        }
        if (str_contains($kel, 'BPJS') || str_contains($kel, 'SWASTA')) {
            return 'BPJS MANDIRI / SWASTA';
        }
        if (str_contains($kel, 'UMUM') || str_contains($kel, 'TUNAI')) {
            return 'UMUM / TUNAI';
        }

        return $kel;
    }

    $p = strtoupper($jenisPenjamin);
    $pa = strtoupper($pangkat);
    $ins = strtoupper($instansi);
    $kat = strtoupper($kategori);
    $kes = strtoupper($kesatuan);

    if (str_contains($p, 'PBI')) {
        return 'BPJS PBI';
    }
    if (str_contains($p, 'MANDIRI') || str_contains($p, 'SWASTA')) {
        return 'BPJS MANDIRI / SWASTA';
    }
    if (str_contains($p, 'PURNA') || str_contains($kat, 'PURNA') || str_contains($pa, 'PENSIUN')) {
        return 'PURNAWIRAWAN';
    }
    if (str_contains($p, 'PNS') || str_contains($kat, 'PNS') || str_contains($ins, 'KEMHAN') || str_contains($pa, 'III/') || str_contains($pa, 'IV/') || str_contains($pa, 'II/') || str_contains($pa, 'I/')) {
        if (str_contains($kat, 'KELUARGA') || str_contains($p, 'KELUARGA') || str_contains($kat, 'ISTRI') || str_contains($kat, 'ANAK') || str_contains($kat, 'SUAMI')) {
            return 'KELUARGA PNS';
        }

        return 'PNS KEMHAN/TNI';
    }
    if (str_contains($p, 'MILITER') || str_contains($p, 'DINAS') || str_contains($kat, 'MILITER') || str_contains($ins, 'TNI') || ! empty($pa)) {
        if (str_contains($kat, 'KELUARGA') || str_contains($p, 'KELUARGA') || str_contains($kat, 'ISTRI') || str_contains($kat, 'ANAK') || str_contains($kat, 'SUAMI') || str_contains($kel, 'KEL AD')) {
            return 'KELUARGA MILITER';
        }

        return 'MILITER TNI AD';
    }

    return 'UMUM / TUNAI';
}

$totalVisitsInserted = 0;

foreach ($folders as $fInfo) {
    $folderPath = $fInfo['path'];
    $month = $fInfo['month'];
    $year = $fInfo['year'];
    $label = $fInfo['label'];

    if (! is_dir($folderPath)) {
        echo "Folder not found: {$folderPath}\n";

        continue;
    }

    $files = glob("{$folderPath}/*.xls");
    $fCount = count($files);
    echo "--- Processing {$label} ({$fCount} files) ---\n";

    foreach ($files as $filePath) {
        $filename = basename($filePath);
        try {
            $reader = IOFactory::createReaderForFile($filePath);
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($filePath);

            $sheet = null;
            if ($spreadsheet->sheetNameExists('R')) {
                $sheet = $spreadsheet->getSheetByName('R');
            } elseif ($spreadsheet->sheetNameExists('Lap. kunjungan pasien')) {
                $sheet = $spreadsheet->getSheetByName('Lap. kunjungan pasien');
            } else {
                $sheet = $spreadsheet->getSheet(0);
            }

            $parsed = parseSheetHeader($sheet);
            if (! $parsed || empty($parsed['col_map']['no_rm'])) {
                echo "  [SKIP] {$filename} => No RM column not found.\n";
                $spreadsheet->disconnectWorksheets();
                unset($spreadsheet);

                continue;
            }

            $startRow = $parsed['header_row'] + 1;
            $colMap = $parsed['col_map'];
            $highestRow = $sheet->getHighestRow();

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
                $noRmCol = $colMap['no_rm'] ?? 'B';
                $noRm = trim((string) $sheet->getCell("{$noRmCol}{$row}")->getValue());

                $namaCol = $colMap['nama_pasien'] ?? 'C';
                $namaPasien = trim((string) $sheet->getCell("{$namaCol}{$row}")->getValue());

                $lowerRm = strtolower($noRm);
                $lowerNama = strtolower($namaPasien);

                if (empty($noRm) || $lowerRm === 'no rm' || str_contains($lowerRm, 'total') || str_contains($lowerNama, 'nama pasien') || str_contains($lowerRm, 'pasien baru') || str_contains($lowerRm, 'pasien lama')) {
                    continue;
                }

                $getField = function ($key) use ($colMap, $sheet, $row) {
                    if (! isset($colMap[$key])) {
                        return '';
                    }

                    return trim((string) $sheet->getCell("{$colMap[$key]}{$row}")->getValue());
                };

                $tglLahir = $getField('tgl_lahir');
                $umur = $getField('umur');
                $statusPasien = $getField('status_pasien');
                $jenisPenjamin = $getField('jenis_penjamin');
                $kelompokRaw = $getField('kelompok');
                $pangkat = $getField('pangkat');
                $kesatuan = $getField('kesatuan');
                $instansi = $getField('instansi');
                $kategori = $getField('kategori');
                $noBpjs = $getField('no_bpjs');
                $noTelp = $getField('no_telp');
                $noHp = $getField('no_hp');
                $rawPoli = $getField('poliklinik');
                $poliklinik = normalizePoliName($rawPoli, $filename);
                $dokter = $getField('dokter');
                $tglBerobatRaw = $getField('tgl_berobat');
                $jam = $getField('jam');
                $noSep = $getField('no_sep');
                $jenisRawat = $getField('jenis_rawat');
                $nipNrpPasien = $getField('nip_nrp_pasien');
                $gender = $getField('gender');
                $agama = $getField('agama');
                $pendidikan = $getField('pendidikan');
                $alamat = $getField('alamat');
                $deskIcdUtama = $getField('deskripsi_icd10_utama');
                $deskIcdSek = $getField('deskripsi_icd10_sekunder');

                // Enforce exact month & year for the folder being imported
                $mStr = sprintf('%02d', $month);
                $tglBerobat = "{$year}-{$mStr}-01";
                if (! empty($tglBerobatRaw)) {
                    $ts = strtotime($tglBerobatRaw);
                    if ($ts) {
                        $dStr = date('Y-m-d', $ts);
                        $targetMonthStr = sprintf('%04d-%02d', $year, $month);
                        if (str_starts_with($dStr, $targetMonthStr)) {
                            $tglBerobat = $dStr;
                        } else {
                            // If day is valid, retain day within target month
                            $day = date('d', $ts);
                            $tglBerobat = "{$year}-{$mStr}-{$day}";
                        }
                    }
                }

                $kelompok = deriveKelompok($kelompokRaw, $jenisPenjamin, $pangkat, $instansi, $kategori, $kesatuan);

                $fileRows[] = [
                    'import_log_id' => $importLog->id,
                    'no_rm' => $noRm,
                    'nama_pasien' => $namaPasien ?: 'PASIEN UNKNOWN',
                    'tgl_lahir' => $tglLahir,
                    'umur' => $umur,
                    'no_telp' => $noTelp,
                    'no_hp' => $noHp,
                    'poliklinik' => $poliklinik,
                    'dokter' => $dokter,
                    'tgl_berobat' => $tglBerobat,
                    'jam' => $jam,
                    'no_sep' => $noSep,
                    'no_bpjs' => $noBpjs,
                    'status_pasien' => $statusPasien ?: 'Pasien Lama',
                    'jenis_rawat' => $jenisRawat ?: 'WATLAN',
                    'jenis_penjamin' => $jenisPenjamin ?: 'BPJS DINAS',
                    'kelompok' => $kelompok,
                    'pangkat' => $pangkat,
                    'nip_nrp_pasien' => $nipNrpPasien,
                    'gender' => $gender ?: 'L',
                    'agama' => $agama,
                    'pendidikan' => $pendidikan,
                    'kesatuan' => $kesatuan,
                    'instansi' => $instansi,
                    'kategori' => $kategori,
                    'alamat' => $alamat,
                    'icd10_utama' => '',
                    'deskripsi_icd10_utama' => $deskIcdUtama,
                    'icd10_sekunder' => '',
                    'deskripsi_icd10_sekunder' => $deskIcdSek,
                    'status_registrasi' => 'open',
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ];

                $fileVisitCount++;
                $totalVisitsInserted++;
            }

            DB::beginTransaction();
            foreach (array_chunk($fileRows, 500) as $chunk) {
                RawVisit::insert($chunk);
            }
            DB::commit();

            $importLog->update(['total_rows' => $fileVisitCount]);
            echo "  [OK] {$filename} => {$fileVisitCount} records inserted as '{$poliklinik}'.\n";

            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);
            gc_collect_cycles();

        } catch (Exception $e) {
            echo "  [ERROR] {$filename} => ".$e->getMessage()."\n";
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
