<?php

ini_set('memory_limit', '2048M');

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use Illuminate\Contracts\Console\Kernel;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;

function parseSheetHeader($sheet)
{
    $highestRow = min($sheet->getHighestRow(), 25);
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

$files = glob(base_path('rspad-file/B. URO/*.xls')) ?: [];
if (! empty($files)) {
    $f = $files[0];
    echo 'Testing file in B. URO: '.basename($f)."\n";
    $reader = IOFactory::createReaderForFile($f);
    $reader->setReadDataOnly(true);
    $spreadsheet = $reader->load($f);
    $sheet = $spreadsheet->getSheetByName('Lap. kunjungan pasien') ?: ($spreadsheet->getSheetByName('R') ?: $spreadsheet->getActiveSheet());

    $res = parseSheetHeader($sheet);
    echo 'Header Row: '.$res['header_row']."\n";
    echo 'Col Map: '.json_encode($res['col_map'], JSON_PRETTY_PRINT)."\n";

    $r = $res['header_row'] + 1;
    $sample = [];
    foreach ($res['col_map'] as $field => $col) {
        $sample[$field] = (string) $sheet->getCell("{$col}{$r}")->getValue();
    }
    echo "Sample Row {$r}: ".json_encode($sample, JSON_PRETTY_PRINT)."\n";
}
