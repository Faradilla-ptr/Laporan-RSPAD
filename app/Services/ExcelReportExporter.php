<?php

namespace App\Services;

use App\Http\Controllers\ReportRL35Controller;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ExcelReportExporter
{
    private static function getCachePath($type, $month, $year, $poli)
    {
        $dir = storage_path('app/exports');
        if (! is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }
        $pSlug = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $poli ?: 'SEMUA');

        return "{$dir}/{$type}_{$month}_{$year}_{$pSlug}.xlsx";
    }

    public static function clearCache()
    {
        $dir = storage_path('app/exports');
        if (is_dir($dir)) {
            $files = glob("{$dir}/*.xlsx");
            foreach ($files as $f) {
                @unlink($f);
            }
        }
    }

    public static function getPeriodText($month, $year)
    {
        $monthsMap = [
            'SEMUA' => 'Seluruh Bulan',
            '1' => 'Januari', '2' => 'Februari', '3' => 'Maret', '4' => 'April',
            '5' => 'Mei', '6' => 'Juni', '7' => 'Juli', '8' => 'Agustus',
            '9' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember',
            '01' => 'Januari', '02' => 'Februari', '03' => 'Maret', '04' => 'April',
            '05' => 'Mei', '06' => 'Juni', '07' => 'Juli', '08' => 'Agustus',
            '09' => 'September', '10' => 'Oktober', '11' => 'November', '12' => 'Desember',
            'T1' => 'Triwulan I (Jan - Mar)', 'T2' => 'Triwulan II (Apr - Jun)',
            'T3' => 'Triwulan III (Jul - Sep)', 'T4' => 'Triwulan IV (Okt - Des)',
            'S1' => 'Semester 1 (Jan - Jun)', 'S2' => 'Semester 2 (Jul - Des)',
        ];

        $mKey = (string) $month;
        $mText = $monthsMap[$mKey] ?? (is_numeric($mKey) ? ($monthsMap[(int) $mKey] ?? $mKey) : $mKey);
        $yText = ($year === 'SEMUA' || empty($year)) ? 'Semua Tahun' : (string) $year;

        if ($mKey === 'SEMUA' && $yText === 'Semua Tahun') {
            return 'Semua Periode';
        }

        return "{$mText} {$yText}";
    }

    public static function getPeriodDateRangeText($month, $year)
    {
        $yStr = ($year === 'SEMUA' || empty($year)) ? 'Semua Tahun' : (string) $year;
        $mStr = (string) $month;

        if (is_numeric($mStr)) {
            $mInt = (int) $mStr;
            $mPad = sprintf('%02d', $mInt);
            if ($yStr !== 'Semua Tahun') {
                $lastDay = date('t', strtotime("{$yStr}-{$mPad}-01"));

                return "01/{$mPad}/{$yStr} s/d {$lastDay}/{$mPad}/{$yStr}";
            }

            return "Bulan {$mPad} {$yStr}";
        }

        switch ($mStr) {
            case 'T1':
                return "01/01/{$yStr} s/d 31/03/{$yStr}";
            case 'T2':
                return "01/04/{$yStr} s/d 30/06/{$yStr}";
            case 'T3':
                return "01/07/{$yStr} s/d 30/09/{$yStr}";
            case 'T4':
                return "01/10/{$yStr} s/d 31/12/{$yStr}";
            case 'S1':
                return "01/01/{$yStr} s/d 30/06/{$yStr}";
            case 'S2':
                return "01/07/{$yStr} s/d 31/12/{$yStr}";
            default:
                return "SEMUA PERIODE ({$yStr})";
        }
    }

    /**
     * Normalize Pasien Status to 'Pasien Baru' or 'Pasien Lama'
     */
    public static function normalizeStatus($status)
    {
        $st = strtolower(trim((string) $status));
        if (str_contains($st, 'baru') || $st === 'b') {
            return 'Pasien Baru';
        }

        return 'Pasien Lama';
    }

    /**
     * Normalize Gender to 'L' or 'P'
     */
    public static function normalizeGender($gender)
    {
        $g = strtoupper(trim((string) $gender));
        if (str_starts_with($g, 'P') || str_starts_with($g, 'F') || str_contains($g, 'WANITA') || str_contains($g, 'PEREMPUAN')) {
            return 'P';
        }

        return 'L';
    }

    /**
     * Normalize Kelompok Name (splits PNS KEMHAN/TNI to PNS AD / AL / AU, KELUARGA MILITER to KEL AD)
     */
    public static function normalizeKelompokName($v)
    {
        static $alRms = null;
        static $auRms = null;

        if ($alRms === null) {
            $alRms = DB::table('raw_visits')
                ->whereIn('kelompok', ['AL', 'KEL AL', 'PNS AL'])
                ->pluck('no_rm')
                ->filter()
                ->unique()
                ->flip()
                ->toArray();

            $auRms = DB::table('raw_visits')
                ->whereIn('kelompok', ['AU', 'KEL AU', 'PNS AU'])
                ->pluck('no_rm')
                ->filter()
                ->unique()
                ->flip()
                ->toArray();
        }

        $kel = trim((string) (is_object($v) ? ($v->kelompok ?? '') : ($v['kelompok'] ?? '')));
        $kelUpper = strtoupper($kel);
        $kesatuan = strtoupper(trim((string) (is_object($v) ? ($v->kesatuan ?? '') : ($v['kesatuan'] ?? ''))));
        $instansi = strtoupper(trim((string) (is_object($v) ? ($v->instansi ?? '') : ($v['instansi'] ?? ''))));
        $pangkat = strtoupper(trim((string) (is_object($v) ? ($v->pangkat ?? '') : ($v['pangkat'] ?? ''))));
        $rm = trim((string) (is_object($v) ? ($v->no_rm ?? '') : ($v['no_rm'] ?? '')));

        if (in_array($kelUpper, ['AD', 'AL', 'AU', 'KEL AD', 'KEL AL', 'KEL AU', 'PNS AD', 'PNS AL', 'PNS AU', 'MILITER TNI AD', 'PPPK DINAS', 'PPPK KEMENTRIAN', 'BPJS PBI', 'BPJS MANDIRI / SWASTA', 'UMUM / TUNAI', 'PURNAWIRAWAN', 'POLRI'])) {
            return $kel;
        }

        if ($kelUpper === 'KELUARGA MILITER') {
            return 'KEL AD';
        }

        if ($kelUpper === 'PNS KEMHAN/TNI' || $kelUpper === 'PNS' || str_contains($kelUpper, 'PNS')) {
            $alKeywords = ['MABESAL', 'DISKUAL', 'DISPENAL', 'KOARMARDA', 'KOLINLAMIL', 'LANTAMAL', 'MARINIR', 'TNI AL', ' AL '];
            foreach ($alKeywords as $kw) {
                if (str_contains($kesatuan, $kw) || str_contains($instansi, $kw) || str_contains($pangkat, $kw)) {
                    return 'PNS AL';
                }
            }

            $auKeywords = ['KOOPSAU', 'RSAU', 'MABESAU', 'LANUD', 'TNI AU', ' AU '];
            foreach ($auKeywords as $kw) {
                if (str_contains($kesatuan, $kw) || str_contains($instansi, $kw) || str_contains($pangkat, $kw)) {
                    return 'PNS AU';
                }
            }

            if (isset($alRms[$rm])) {
                return 'PNS AL';
            }
            if (isset($auRms[$rm])) {
                return 'PNS AU';
            }

            return 'PNS AD';
        }

        return $kel;
    }

    /**
     * Full Output Export (All 7 Sheets for Laporan Puskesad / Full Report)
     */
    public static function exportFullOutput($month, $year, $filename = null, $poli = null, $savePath = null)
    {
        ini_set('memory_limit', '1024M');
        set_time_limit(300);

        $m = (string) ($month ?: 'SEMUA');
        $y = (string) ($year ?: 'SEMUA');

        $cacheFile = self::getCachePath('puskesad', $m, $y, $poli);

        if (! $savePath && file_exists($cacheFile) && filesize($cacheFile) > 0) {
            if (! $filename) {
                $filename = "Laporan_Puskesad_RSPAD_{$m}_{$y}.xlsx";
            }

            return response()->download($cacheFile, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ]);
        }

        $targetFile = $savePath ?: $cacheFile;

        $query = DB::table('raw_visits');
        self::applyDateFilter($query, $m, $y);
        if ($poli && $poli !== 'SEMUA') {
            $query->where('poliklinik', $poli);
        }

        $visits = $query->orderBy('tgl_berobat')->orderBy('id')->get();

        $spreadsheet = new Spreadsheet;

        $darkGreenHeader = '2A6A2A';
        $mStr = is_numeric($m) ? sprintf('%02d', (int) $m) : (string) $m;
        $yStr = (string) $y;
        $poliTitle = ($poli && $poli !== 'SEMUA') ? strtoupper($poli) : 'SEMUA POLI';

        // Unique patients by RM
        $uniquePatients = [];
        foreach ($visits as $v) {
            $rmKey = trim((string) $v->no_rm);
            if (! empty($rmKey) && ! isset($uniquePatients[$rmKey])) {
                $uniquePatients[$rmKey] = $v;
            }
        }

        // ----------------------------------------------------
        // 1. SHEET: LAPORAN PUSKESAD
        // ----------------------------------------------------
        $sheetPuskesad = $spreadsheet->getActiveSheet();
        $sheetPuskesad->setTitle('LAPORAN PUSKESAD');
        $sheetPuskesad->setShowGridLines(true);

        $bulanText = self::getPeriodText($m, $y);

        $sheetPuskesad->setCellValue('A1', 'RSPAD GATOT SOEBROTO');
        $sheetPuskesad->setCellValue('A2', 'INSTALASI REKAM MEDIS DAN INFOKES');
        $sheetPuskesad->setCellValue('A3', 'LAPORAN PELAYANAN RAWAT JALAN');
        $sheetPuskesad->setCellValue('A4', strtoupper("PERIODE {$bulanText}"));

        $sheetPuskesad->mergeCells('A1:F1');
        $sheetPuskesad->mergeCells('A2:F2');
        $sheetPuskesad->mergeCells('A3:F3');
        $sheetPuskesad->mergeCells('A4:F4');

        $titleStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '00B050']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ];
        $sheetPuskesad->getStyle('A1:F4')->applyFromArray($titleStyle);

        // Header Table
        $sheetPuskesad->setCellValue('A5', 'NO');
        $sheetPuskesad->setCellValue('B5', 'STATUS PASIEN');
        $sheetPuskesad->setCellValue('C5', $bulanText);

        $sheetPuskesad->mergeCells('A5:A7');
        $sheetPuskesad->mergeCells('B5:B7');
        $sheetPuskesad->mergeCells('C5:F5');

        $sheetPuskesad->setCellValue('C6', 'PENGUNJUNG');
        $sheetPuskesad->setCellValue('E6', 'KUNJUNGAN');

        $sheetPuskesad->mergeCells('C6:D6');
        $sheetPuskesad->mergeCells('E6:F6');

        $sheetPuskesad->setCellValue('C7', 'JUMLAH');
        $sheetPuskesad->setCellValue('D7', '%');
        $sheetPuskesad->setCellValue('E7', 'JUMLAH');
        $sheetPuskesad->setCellValue('F7', '%');

        $tableHeaderStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => '000000'], 'size' => 10],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '00B050']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]],
        ];
        $sheetPuskesad->getStyle('A5:F7')->applyFromArray($tableHeaderStyle);

        $getCategoryKey = function ($v) {
            $kel = trim(strtoupper((string) ($v->kelompok ?? '')));
            $pen = trim(strtoupper((string) ($v->jenis_penjamin ?? '')));
            $kes = trim(strtoupper((string) ($v->kesatuan ?? '')));
            $ins = trim(strtoupper((string) ($v->instansi ?? '')));
            $pa = trim(strtoupper((string) ($v->pangkat ?? '')));
            $kat = trim(strtoupper((string) ($v->kategori ?? '')));

            if ($kel === 'AD' || str_contains($kel, 'MILITER TNI AD') || str_contains($kel, 'TNI AD')) {
                return '1a';
            }
            if ($kel === 'PNS AD' || ($kel === 'PNS KEMHAN/TNI' && ! str_contains($kes, 'AL') && ! str_contains($ins, 'AL') && ! str_contains($kes, 'AU') && ! str_contains($ins, 'AU'))) {
                return '1b';
            }
            if ($kel === 'KEL AD' || $kel === 'KELUARGA MILITER') {
                return '1c';
            }

            if ($kel === 'AL' || str_contains($kel, 'TNI AL')) {
                return '1d';
            }
            if ($kel === 'PNS AL' || ($kel === 'PNS KEMHAN/TNI' && (str_contains($kes, 'AL') || str_contains($ins, 'AL')))) {
                return '1e';
            }
            if ($kel === 'KEL AL' || ($kel === 'KELUARGA MILITER' && (str_contains($kes, 'AL') || str_contains($ins, 'AL')))) {
                return '1f';
            }

            if ($kel === 'AU' || str_contains($kel, 'TNI AU')) {
                return '1g';
            }
            if ($kel === 'PNS AU' || ($kel === 'PNS KEMHAN/TNI' && (str_contains($kes, 'AU') || str_contains($ins, 'AU')))) {
                return '1h';
            }
            if ($kel === 'KEL AU' || ($kel === 'KELUARGA MILITER' && (str_contains($kes, 'AU') || str_contains($ins, 'AU')))) {
                return '1i';
            }

            if (str_contains($kel, 'PPPK DINAS') || str_contains($pa, 'PPPK DINAS')) {
                return '1j';
            }

            if ($kel === 'POLRI' || str_contains($kel, 'POLRI') || str_contains($pen, 'POLRI')) {
                if (str_contains($kel, 'PNS') || str_contains($pa, 'PNS')) {
                    return '2b';
                }
                if (str_contains($kel, 'KEL') || str_contains($kat, 'KEL') || str_contains($kat, 'ANAK') || str_contains($kat, 'ISTRI')) {
                    return '2c';
                }

                return '2a';
            }

            if ($kel === 'PURNAWIRAWAN' || str_contains($kel, 'PURNA') || str_contains($pen, 'PURNA') || str_contains($pa, 'PENSIUN')) {
                return '3';
            }

            if (str_contains($pen, 'KEMENTERIAN') || str_contains($pen, 'KEMENTRIAN') || str_contains($kel, 'KEMENTERIAN') || str_contains($kel, 'KEMENTRIAN')) {
                if (str_contains($kel, 'PPPK') || str_contains($pa, 'PPPK')) {
                    return '5';
                }

                return '4';
            }

            if (str_contains($kel, 'PPPK KEMENT') || str_contains($kel, 'PPPK KEMHAN')) {
                return '5';
            }

            if ($kel === 'BPJS PBI' || str_contains($kel, 'PBI') || str_contains($pen, 'PBI')) {
                return '6a';
            }
            if (str_contains($kel, 'KETENAGAKERJAAN') || str_contains($pen, 'KETENAGAKERJAAN') || str_contains($pen, 'TENAGA KERJA')) {
                return '6c';
            }
            if (str_contains($kel, 'BPJS') || str_contains($pen, 'BPJS') || str_contains($pen, 'MANDIRI') || str_contains($kel, 'MANDIRI')) {
                return '6b';
            }

            if (str_contains($pen, 'SWASTA') || str_contains($kel, 'SWASTA')) {
                return '7';
            }
            if (str_contains($pen, 'RSPAD') || str_contains($pen, 'JAMINAN')) {
                return '8';
            }
            if (str_contains($pen, 'BAKSOS')) {
                return '9';
            }

            return '10';
        };

        $countsK = [];
        $countsP = [];

        foreach ($visits as $v) {
            $cat = $getCategoryKey($v);
            $countsK[$cat] = ($countsK[$cat] ?? 0) + 1;
        }

        foreach ($uniquePatients as $p) {
            $cat = $getCategoryKey($p);
            $countsP[$cat] = ($countsP[$cat] ?? 0) + 1;
        }

        $totPAll = max(1, count($uniquePatients));
        $totKAll = max(1, count($visits));

        $rPus = 8;

        // 1. JKN AKTIF
        $sheetPuskesad->setCellValue("A{$rPus}", '1');
        $sheetPuskesad->setCellValue("B{$rPus}", 'JKN AKTIF');
        $rPus++;
        $start1 = $rPus;

        $items1 = [
            '1a' => 'a. TNI AD',
            '1b' => 'b. PNS AD',
            '1c' => 'c. KEL AD',
            '1d' => 'd. TNI AL',
            '1e' => 'e. PNS AL',
            '1f' => 'f. KEL AL',
            '1g' => 'g. TNI AU',
            '1h' => 'h. PNS AU',
            '1i' => 'i. KEL AU',
            '1j' => 'j. PPPK DINAS',
        ];

        foreach ($items1 as $key => $lbl) {
            $cP = $countsP[$key] ?? 0;
            $cK = $countsK[$key] ?? 0;
            $sheetPuskesad->setCellValue("B{$rPus}", $lbl);
            $sheetPuskesad->setCellValue("C{$rPus}", $cP);
            $sheetPuskesad->setCellValue("D{$rPus}", $cP / $totPAll);
            $sheetPuskesad->setCellValue("E{$rPus}", $cK);
            $sheetPuskesad->setCellValue("F{$rPus}", $cK / $totKAll);
            $rPus++;
        }
        $end1 = $rPus - 1;

        $sub1P = array_sum(array_intersect_key($countsP, $items1));
        $sub1K = array_sum(array_intersect_key($countsK, $items1));

        $sheetPuskesad->setCellValue("B{$rPus}", 'SUB TOTAL');
        $sheetPuskesad->setCellValue("C{$rPus}", "=SUM(C{$start1}:C{$end1})");
        $sheetPuskesad->setCellValue("D{$rPus}", $sub1P / $totPAll);
        $sheetPuskesad->setCellValue("E{$rPus}", "=SUM(E{$start1}:E{$end1})");
        $sheetPuskesad->setCellValue("F{$rPus}", $sub1K / $totKAll);
        $sheetPuskesad->getStyle("A{$rPus}:F{$rPus}")->getFont()->setBold(true);
        $sub1Row = $rPus;
        $rPus++;

        // 2. JKN POLRI
        $sheetPuskesad->setCellValue("A{$rPus}", '2');
        $sheetPuskesad->setCellValue("B{$rPus}", 'JKN POLRI');
        $rPus++;
        $start2 = $rPus;

        $items2 = [
            '2a' => 'a. POLRI',
            '2b' => 'b. PNS POLRI',
            '2c' => 'c. KEL POLRI',
        ];

        foreach ($items2 as $key => $lbl) {
            $cP = $countsP[$key] ?? 0;
            $cK = $countsK[$key] ?? 0;
            $sheetPuskesad->setCellValue("B{$rPus}", $lbl);
            $sheetPuskesad->setCellValue("C{$rPus}", $cP);
            $sheetPuskesad->setCellValue("D{$rPus}", $cP / $totPAll);
            $sheetPuskesad->setCellValue("E{$rPus}", $cK);
            $sheetPuskesad->setCellValue("F{$rPus}", $cK / $totKAll);
            $rPus++;
        }
        $end2 = $rPus - 1;

        $sub2P = array_sum(array_intersect_key($countsP, $items2));
        $sub2K = array_sum(array_intersect_key($countsK, $items2));

        $sheetPuskesad->setCellValue("B{$rPus}", 'SUB TOTAL');
        $sheetPuskesad->setCellValue("C{$rPus}", "=SUM(C{$start2}:C{$end2})");
        $sheetPuskesad->setCellValue("D{$rPus}", $sub2P / $totPAll);
        $sheetPuskesad->setCellValue("E{$rPus}", "=SUM(E{$start2}:E{$end2})");
        $sheetPuskesad->setCellValue("F{$rPus}", $sub2K / $totKAll);
        $sheetPuskesad->getStyle("A{$rPus}:F{$rPus}")->getFont()->setBold(true);
        $sub2Row = $rPus;
        $rPus++;

        $standaloneRows = [
            '3' => ['no' => '3', 'lbl' => 'JKN PURNAWIRAWAN', 'key' => '3'],
            '4' => ['no' => '4', 'lbl' => 'JKN KEMENTRIAN', 'key' => '4'],
            '5' => ['no' => '5', 'lbl' => 'PPPK KEMENTERIAN', 'key' => '5'],
        ];

        $standaloneRowIndices = [];
        foreach ($standaloneRows as $st) {
            $cP = $countsP[$st['key']] ?? 0;
            $cK = $countsK[$st['key']] ?? 0;
            $sheetPuskesad->setCellValue("A{$rPus}", $st['no']);
            $sheetPuskesad->setCellValue("B{$rPus}", $st['lbl']);
            $sheetPuskesad->setCellValue("C{$rPus}", $cP);
            $sheetPuskesad->setCellValue("D{$rPus}", $cP / $totPAll);
            $sheetPuskesad->setCellValue("E{$rPus}", $cK);
            $sheetPuskesad->setCellValue("F{$rPus}", $cK / $totKAll);
            $standaloneRowIndices[] = $rPus;
            $rPus++;
        }

        // 6. JKN UMUM
        $sheetPuskesad->setCellValue("A{$rPus}", '6');
        $sheetPuskesad->setCellValue("B{$rPus}", 'JKN UMUM');
        $rPus++;
        $start6 = $rPus;

        $items6 = [
            '6a' => 'a. PBI',
            '6b' => 'b. MANDIRI',
            '6c' => 'c. TENAGA KERJA',
        ];

        foreach ($items6 as $key => $lbl) {
            $cP = $countsP[$key] ?? 0;
            $cK = $countsK[$key] ?? 0;
            $sheetPuskesad->setCellValue("B{$rPus}", $lbl);
            $sheetPuskesad->setCellValue("C{$rPus}", $cP);
            $sheetPuskesad->setCellValue("D{$rPus}", $cP / $totPAll);
            $sheetPuskesad->setCellValue("E{$rPus}", $cK);
            $sheetPuskesad->setCellValue("F{$rPus}", $cK / $totKAll);
            $rPus++;
        }
        $end6 = $rPus - 1;

        $standaloneRows2 = [
            '7' => ['no' => '7', 'lbl' => 'SWASTA', 'key' => '7'],
            '8' => ['no' => '8', 'lbl' => 'JAMINAN RSPAD', 'key' => '8'],
            '9' => ['no' => '9', 'lbl' => 'BAKSOS', 'key' => '9'],
            '10' => ['no' => '10', 'lbl' => 'ASURANSI', 'key' => '10'],
        ];

        foreach ($standaloneRows2 as $st) {
            $cP = $countsP[$st['key']] ?? 0;
            $cK = $countsK[$st['key']] ?? 0;
            $sheetPuskesad->setCellValue("A{$rPus}", $st['no']);
            $sheetPuskesad->setCellValue("B{$rPus}", $st['lbl']);
            $sheetPuskesad->setCellValue("C{$rPus}", $cP);
            $sheetPuskesad->setCellValue("D{$rPus}", $cP / $totPAll);
            $sheetPuskesad->setCellValue("E{$rPus}", $cK);
            $sheetPuskesad->setCellValue("F{$rPus}", $cK / $totKAll);
            $standaloneRowIndices[] = $rPus;
            $rPus++;
        }

        $actualGrandRow = $rPus;
        $sheetPuskesad->setCellValue("B{$actualGrandRow}", 'JUMLAH');

        $cSumCells = "C{$sub1Row}+C{$sub2Row}+".implode('+', array_map(fn ($r) => "C{$r}", $standaloneRowIndices))."+SUM(C{$start6}:C{$end6})";
        $eSumCells = "E{$sub1Row}+E{$sub2Row}+".implode('+', array_map(fn ($r) => "E{$r}", $standaloneRowIndices))."+SUM(E{$start6}:E{$end6})";

        $sheetPuskesad->setCellValue("C{$actualGrandRow}", "={$cSumCells}");
        $sheetPuskesad->setCellValue("D{$actualGrandRow}", 1.0);
        $sheetPuskesad->setCellValue("E{$actualGrandRow}", "={$eSumCells}");
        $sheetPuskesad->setCellValue("F{$actualGrandRow}", 1.0);

        $sheetPuskesad->getStyle("A{$actualGrandRow}:F{$actualGrandRow}")->getFont()->setBold(true);
        $sheetPuskesad->getStyle("A8:F{$actualGrandRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        $sheetPuskesad->getStyle("D8:D{$actualGrandRow}")->getNumberFormat()->setFormatCode('0.00%');
        $sheetPuskesad->getStyle("F8:F{$actualGrandRow}")->getNumberFormat()->setFormatCode('0.00%');

        $rFoot = $actualGrandRow + 3;
        $sheetPuskesad->setCellValue("E{$rFoot}", "Jakarta, {$bulanText} {$yStr}");
        $rFoot++;
        $sheetPuskesad->setCellValue("E{$rFoot}", 'Instal Rekam Medis dan Infokes');
        $rFoot += 3;
        $sheetPuskesad->setCellValue("E{$rFoot}", 'I Wayan Sandi, S.K.M');
        $rFoot++;
        $sheetPuskesad->setCellValue("E{$rFoot}", 'Ecol Ckm (K) NRP 119400044...');
        $sheetPuskesad->getStyle('E'.($actualGrandRow + 3).":E{$rFoot}")->getFont()->setBold(true);

        self::autoFitColumns($sheetPuskesad, 'A', 'F');
        $sheetPuskesad->getColumnDimension('B')->setWidth(32);

        // ----------------------------------------------------
        // 2. SHEET: JK
        // ----------------------------------------------------
        $sheetJK = $spreadsheet->createSheet();
        $sheetJK->setTitle('JK');
        $sheetJK->setShowGridLines(true);

        $jkCounts = ['L' => 0, 'P' => 0];
        foreach ($visits as $p) {
            $g = self::normalizeGender($p->gender);
            $jkCounts[$g]++;
        }

        $sheetJK->setCellValue('A3', 'Count of NO RM');
        $sheetJK->setCellValue('A4', 'KELAMIN');
        $sheetJK->setCellValue('B4', 'Total');
        $sheetJK->setCellValue('A5', 'L');
        $sheetJK->setCellValue('B5', $jkCounts['L']);
        $sheetJK->setCellValue('A6', 'P');
        $sheetJK->setCellValue('B6', $jkCounts['P']);
        $sheetJK->setCellValue('A7', '(blank)');
        $sheetJK->setCellValue('B7', 0);
        $sheetJK->setCellValue('A8', 'Grand Total');
        $sheetJK->setCellValue('B8', '=SUM(B5:B7)');

        $sheetJK->getStyle('A4:B4')->getFont()->setBold(true);
        $sheetJK->getStyle('A8:B8')->getFont()->setBold(true);
        $sheetJK->getStyle('A4:B8')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $sheetJK->getColumnDimension('A')->setWidth(18);
        $sheetJK->getColumnDimension('B')->setWidth(14);

        // ----------------------------------------------------
        // 2. SHEET: PIVOT P
        // ----------------------------------------------------
        $sheetPivotP = $spreadsheet->createSheet();
        $sheetPivotP->setTitle('PIVOT P');
        $sheetPivotP->setShowGridLines(true);

        $sheetPivotP->setCellValue('A3', 'Count of NO RM');
        $sheetPivotP->setCellValue('A4', 'JENIS PEMBAYARAN');
        $sheetPivotP->setCellValue('B4', 'KELOMPOK');
        $sheetPivotP->setCellValue('C4', 'Total');
        $sheetPivotP->getStyle('A4:C4')->getFont()->setBold(true);

        $pivotPData = [];
        foreach ($uniquePatients as $p) {
            $pen = trim((string) $p->jenis_penjamin) ?: 'LAIN-LAIN';
            $kel = self::normalizeKelompokName($p);
            if (! isset($pivotPData[$pen])) {
                $pivotPData[$pen] = [];
            }
            if (! isset($pivotPData[$pen][$kel])) {
                $pivotPData[$pen][$kel] = 0;
            }
            $pivotPData[$pen][$kel]++;
        }

        ksort($pivotPData);

        $rpP = 5;
        $subtotalRowsP = [];

        foreach ($pivotPData as $penName => $kelGroup) {
            ksort($kelGroup);
            $groupStartRow = $rpP;
            $isFirstInGroup = true;

            foreach ($kelGroup as $kelName => $cnt) {
                if ($isFirstInGroup) {
                    $sheetPivotP->setCellValue("A{$rpP}", $penName);
                    $isFirstInGroup = false;
                }
                if (! empty($kelName)) {
                    $sheetPivotP->setCellValue("B{$rpP}", $kelName);
                }
                $sheetPivotP->setCellValue("C{$rpP}", $cnt);
                $rpP++;
            }

            $groupEndRow = $rpP - 1;
            $sheetPivotP->setCellValue("A{$rpP}", "{$penName} Total");
            $sheetPivotP->setCellValue("C{$rpP}", "=SUM(C{$groupStartRow}:C{$groupEndRow})");
            $sheetPivotP->getStyle("A{$rpP}:C{$rpP}")->getFont()->setBold(true);
            $subtotalRowsP[] = "C{$rpP}";
            $rpP++;
        }

        $sheetPivotP->setCellValue("A{$rpP}", 'Grand Total');
        if (! empty($subtotalRowsP)) {
            $sheetPivotP->setCellValue("C{$rpP}", '=SUM('.implode(',', $subtotalRowsP).')');
        } else {
            $sheetPivotP->setCellValue("C{$rpP}", 0);
        }
        $sheetPivotP->getStyle("A{$rpP}:C{$rpP}")->getFont()->setBold(true);
        $sheetPivotP->getStyle("A4:C{$rpP}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        $sheetPivotP->getColumnDimension('A')->setWidth(28);
        $sheetPivotP->getColumnDimension('B')->setWidth(26);
        $sheetPivotP->getColumnDimension('C')->setWidth(14);

        // ----------------------------------------------------
        // 3. SHEET: P
        // ----------------------------------------------------
        $sheetP = $spreadsheet->createSheet();
        $sheetP->setTitle('P');
        $sheetP->setShowGridLines(true);

        $headersDetail = [
            'NO', 'NO RM', 'NAMA PASIEN', 'TANGGAL LAHIR', 'USIA', 'TYPE PASIEN',
            'NO. TELP', 'NO. PONSEL', 'POLI', 'DOKTER', 'TANGGAL', 'JAM', 'NO SEP', 'NO PESERTA',
            'JENIS RAWAT', 'JENIS PEMBAYARAN', 'KELOMPOK', 'PANGKAT', 'NRP', 'KELAMIN',
            'AGAMA', 'PENDIDIKAN', 'KESATUAN', 'ANGKATAN', 'HUBUNGAN KELUARGA', 'ALAMAT',
            'KODE', 'DIAGNOSA AWAL', 'KODE', 'DIAGNOSA AKHIR', 'STATUS',
        ];

        foreach ($headersDetail as $colIdx => $hText) {
            $colLetter = Coordinate::stringFromColumnIndex($colIdx + 1);
            $sheetP->setCellValue("{$colLetter}1", $hText);
        }
        $sheetP->getStyle('A1:AE1')->getFont()->setBold(true);

        $rowsP = [];
        $noP = 1;
        foreach ($uniquePatients as $v) {
            $rowsP[] = self::formatDetailRow($noP++, $v);
        }
        $sheetP->fromArray($rowsP, null, 'A2', true);
        unset($rowsP);
        self::autoFitColumns($sheetP, 'A', 'AE');

        // ----------------------------------------------------
        // 4. SHEET: PIVOT K
        // ----------------------------------------------------
        $sheetPivotK = $spreadsheet->createSheet();
        $sheetPivotK->setTitle('PIVOT K');
        $sheetPivotK->setShowGridLines(true);

        $sheetPivotK->setCellValue('A3', 'Count of NO RM');
        $sheetPivotK->setCellValue('C3', 'TYPE PASIEN');
        $sheetPivotK->setCellValue('A4', 'JENIS PEMBAYARAN');
        $sheetPivotK->setCellValue('B4', 'KELOMPOK');
        $sheetPivotK->setCellValue('C4', 'Pasien Baru');
        $sheetPivotK->setCellValue('D4', 'Pasien Lama');
        $sheetPivotK->setCellValue('E4', '(blank)');
        $sheetPivotK->setCellValue('F4', 'Grand Total');
        $sheetPivotK->getStyle('A4:F4')->getFont()->setBold(true);

        $pivotKData = [];
        foreach ($visits as $v) {
            $pen = trim((string) $v->jenis_penjamin) ?: 'LAIN-LAIN';
            $kel = self::normalizeKelompokName($v);
            $stKey = self::normalizeStatus($v->status_pasien) === 'Pasien Baru' ? 'baru' : 'lama';
            if (! isset($pivotKData[$pen])) {
                $pivotKData[$pen] = [];
            }
            if (! isset($pivotKData[$pen][$kel])) {
                $pivotKData[$pen][$kel] = ['baru' => 0, 'lama' => 0];
            }
            $pivotKData[$pen][$kel][$stKey]++;
        }

        ksort($pivotKData);

        $rpK = 5;
        $subtotalRowsK = [];

        foreach ($pivotKData as $penName => $kelGroup) {
            ksort($kelGroup);
            $groupStartRow = $rpK;
            $isFirstInGroup = true;

            foreach ($kelGroup as $kelName => $c) {
                if ($isFirstInGroup) {
                    $sheetPivotK->setCellValue("A{$rpK}", $penName);
                    $isFirstInGroup = false;
                }
                if (! empty($kelName)) {
                    $sheetPivotK->setCellValue("B{$rpK}", $kelName);
                }
                if ($c['baru'] > 0) {
                    $sheetPivotK->setCellValue("C{$rpK}", $c['baru']);
                }
                if ($c['lama'] > 0) {
                    $sheetPivotK->setCellValue("D{$rpK}", $c['lama']);
                }
                $sheetPivotK->setCellValue("F{$rpK}", "=SUM(C{$rpK}:D{$rpK})");
                $rpK++;
            }

            $groupEndRow = $rpK - 1;
            $sheetPivotK->setCellValue("A{$rpK}", "{$penName} Total");
            $sheetPivotK->setCellValue("C{$rpK}", "=SUM(C{$groupStartRow}:C{$groupEndRow})");
            $sheetPivotK->setCellValue("D{$rpK}", "=SUM(D{$groupStartRow}:D{$groupEndRow})");
            $sheetPivotK->setCellValue("F{$rpK}", "=SUM(F{$groupStartRow}:F{$groupEndRow})");
            $sheetPivotK->getStyle("A{$rpK}:F{$rpK}")->getFont()->setBold(true);

            $subtotalRowsK[] = $rpK;
            $rpK++;
        }

        $sheetPivotK->setCellValue("A{$rpK}", 'Grand Total');
        if (! empty($subtotalRowsK)) {
            $cSub = array_map(fn ($r) => "C{$r}", $subtotalRowsK);
            $dSub = array_map(fn ($r) => "D{$r}", $subtotalRowsK);
            $fSub = array_map(fn ($r) => "F{$r}", $subtotalRowsK);

            $sheetPivotK->setCellValue("C{$rpK}", '=SUM('.implode(',', $cSub).')');
            $sheetPivotK->setCellValue("D{$rpK}", '=SUM('.implode(',', $dSub).')');
            $sheetPivotK->setCellValue("F{$rpK}", '=SUM('.implode(',', $fSub).')');
        } else {
            $sheetPivotK->setCellValue("C{$rpK}", 0);
            $sheetPivotK->setCellValue("D{$rpK}", 0);
            $sheetPivotK->setCellValue("F{$rpK}", 0);
        }
        $sheetPivotK->getStyle("A{$rpK}:F{$rpK}")->getFont()->setBold(true);
        $sheetPivotK->getStyle("A4:F{$rpK}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        $sheetPivotK->getColumnDimension('A')->setWidth(28);
        $sheetPivotK->getColumnDimension('B')->setWidth(26);
        $sheetPivotK->getColumnDimension('C')->setWidth(16);
        $sheetPivotK->getColumnDimension('D')->setWidth(16);
        $sheetPivotK->getColumnDimension('E')->setWidth(10);
        $sheetPivotK->getColumnDimension('F')->setWidth(16);

        // ----------------------------------------------------
        // 5. SHEET: R
        // ----------------------------------------------------
        $sheetR = $spreadsheet->createSheet();
        $sheetR->setTitle('R');
        $sheetR->setShowGridLines(true);

        foreach ($headersDetail as $colIdx => $hText) {
            $colLetter = Coordinate::stringFromColumnIndex($colIdx + 1);
            $sheetR->setCellValue("{$colLetter}1", $hText);
        }
        $sheetR->getStyle('A1:AE1')->getFont()->setBold(true);

        $rowsR = [];
        $noR = 1;
        foreach ($visits as $v) {
            $rowsR[] = self::formatDetailRow($noR++, $v);
        }
        $sheetR->fromArray($rowsR, null, 'A2', true);
        unset($rowsR);
        self::autoFitColumns($sheetR, 'A', 'AE');

        // ----------------------------------------------------
        // 6. SHEET: Lap. kunjungan pasien
        // ----------------------------------------------------
        $sheetLap = $spreadsheet->createSheet();
        $sheetLap->setTitle('Lap. kunjungan pasien');
        $sheetLap->setShowGridLines(true);

        $sheetLap->setCellValue('A1', 'MARKAS BESAR TNI ANGKATAN DARAT');
        $sheetLap->setCellValue('A2', 'RSPAD GATOT SOEBROTO');
        $sheetLap->setCellValue('A3', 'Jl. Abdul Rahman Saleh No. 24, Jakarta Pusat');
        $sheetLap->setCellValue('A4', 'Telp : (021) 3441008, 3840702, Fax : (021) 3520619');
        $sheetLap->getStyle('A1:A2')->getFont()->setBold(true)->setSize(11);
        $sheetLap->getStyle('A3:A4')->getFont()->setSize(9.5);

        $sheetLap->setCellValue('A6', 'LAPORAN KUNJUNGAN PASIEN');
        $sheetLap->getStyle('A6')->getFont()->setBold(true)->setSize(12);

        $sheetLap->setCellValue('A7', 'LOKASI');
        $sheetLap->setCellValue('B7', ': RSPAD');
        $sheetLap->setCellValue('A8', 'POLIKLINIK');
        $sheetLap->setCellValue('B8', ": {$poliTitle}");
        $sheetLap->setCellValue('A9', 'JENIS RAWAT');
        $sheetLap->setCellValue('B9', ': WATLAN');
        $sheetLap->setCellValue('A10', 'STATUS REGIS');
        $sheetLap->setCellValue('B10', ': OPEN');
        $sheetLap->setCellValue('A11', 'TANGGAL');
        $sheetLap->setCellValue('B11', ': '.self::getPeriodDateRangeText($m, $y));

        $sheetLap->getStyle('A7:A11')->getFont()->setBold(true)->setSize(10);
        $sheetLap->getStyle('B7:B11')->getFont()->setSize(10);

        foreach ($headersDetail as $colIdx => $hText) {
            $colLetter = Coordinate::stringFromColumnIndex($colIdx + 1);
            $sheetLap->setCellValue("{$colLetter}13", $hText);
        }

        $headerStyle1 = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $darkGreenHeader]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'FFFFFF']]],
        ];
        $sheetLap->getStyle('A13:AE13')->applyFromArray($headerStyle1);

        $rowsLap = [];
        foreach ($visits as $idx => $v) {
            $rowsLap[] = self::formatLapRow($idx + 1, $v);
        }
        $sheetLap->fromArray($rowsLap, null, 'A14', true);
        unset($rowsLap);
        self::autoFitColumns($sheetLap, 'A', 'AE');

        // ----------------------------------------------------
        // 7. SHEET: rekap kunjungan
        // ----------------------------------------------------
        $sheetRekap = $spreadsheet->createSheet();
        $sheetRekap->setTitle('rekap kunjungan');
        $sheetRekap->setShowGridLines(true);

        $sheetRekap->setCellValue('A1', 'MARKAS BESAR TNI ANGKATAN DARAT');
        $sheetRekap->setCellValue('A2', 'RSPAD GATOT SOEBROTO');
        $sheetRekap->setCellValue('A3', 'Jl. Abdul Rahman Saleh No. 24, Jakarta Pusat');
        $sheetRekap->setCellValue('A4', 'Telp : (021) 3441008, 3840702, Fax : (021) 3520619');

        $sheetRekap->getStyle('A1:A2')->getFont()->setBold(true)->setSize(11);
        $sheetRekap->getStyle('A3:A4')->getFont()->setSize(9.5);

        $sheetRekap->setCellValue('A6', 'REKAP KUNJUNGAN');
        $sheetRekap->getStyle('A6')->getFont()->setBold(true)->setSize(12);

        $sheetRekap->setCellValue('A7', 'LOKASI');
        $sheetRekap->setCellValue('B7', ': RSPAD');
        $sheetRekap->setCellValue('A8', 'POLIKLINIK');
        $sheetRekap->setCellValue('B8', ": {$poliTitle}");
        $sheetRekap->setCellValue('A9', 'JENIS RAWAT');
        $sheetRekap->setCellValue('B9', ': WATLAN');
        $sheetRekap->setCellValue('A10', 'STATUS REGIS');
        $sheetRekap->setCellValue('B10', ': OPEN');
        $sheetRekap->setCellValue('A11', 'TANGGAL');
        $sheetRekap->setCellValue('B11', ': '.self::getPeriodDateRangeText($m, $y));

        $sheetRekap->setCellValue('A13', 'NO');
        $sheetRekap->setCellValue('B13', 'GOLONGAN PERSONIL');
        $sheetRekap->setCellValue('C13', 'JUMLAH PASIEN BARU');
        $sheetRekap->setCellValue('D13', 'JUMLAH PASIEN LAMA');
        $sheetRekap->setCellValue('E13', 'JUMLAH');

        $sheetRekap->getStyle('A13:E13')->applyFromArray($headerStyle1);

        $rekapGroup = [];
        foreach ($visits as $v) {
            $kel = $v->jenis_penjamin ?: 'LAIN-LAIN';
            if (! isset($rekapGroup[$kel])) {
                $rekapGroup[$kel] = ['baru' => 0, 'lama' => 0, 'total' => 0];
            }
            if (self::normalizeStatus($v->status_pasien) === 'Pasien Baru') {
                $rekapGroup[$kel]['baru']++;
            } else {
                $rekapGroup[$kel]['lama']++;
            }
            $rekapGroup[$kel]['total']++;
        }

        $rRekap = 14;
        $noRekap = 1;
        $startRekapRow = 14;

        foreach ($rekapGroup as $kelName => $counts) {
            $sheetRekap->setCellValue("A{$rRekap}", $noRekap++);
            $sheetRekap->setCellValue("B{$rRekap}", $kelName);
            $sheetRekap->setCellValue("C{$rRekap}", $counts['baru']);
            $sheetRekap->setCellValue("D{$rRekap}", $counts['lama']);
            $sheetRekap->setCellValue("E{$rRekap}", "=SUM(C{$rRekap}:D{$rRekap})");
            $rRekap++;
        }

        $endRekapRow = $rRekap - 1;
        $sheetRekap->setCellValue("A{$rRekap}", 'TOTAL');
        if ($endRekapRow >= $startRekapRow) {
            $sheetRekap->setCellValue("C{$rRekap}", "=SUM(C{$startRekapRow}:C{$endRekapRow})");
            $sheetRekap->setCellValue("D{$rRekap}", "=SUM(D{$startRekapRow}:D{$endRekapRow})");
            $sheetRekap->setCellValue("E{$rRekap}", "=SUM(E{$startRekapRow}:E{$endRekapRow})");
        } else {
            $sheetRekap->setCellValue("C{$rRekap}", 0);
            $sheetRekap->setCellValue("D{$rRekap}", 0);
            $sheetRekap->setCellValue("E{$rRekap}", 0);
        }
        $sheetRekap->getStyle("A{$rRekap}:E{$rRekap}")->getFont()->setBold(true);
        $sheetRekap->getStyle("A13:E{$rRekap}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        self::autoFitColumns($sheetRekap, 'A', 'E');

        // Save writer output to targetFile
        $writer = new Xlsx($spreadsheet);
        $writer->save($targetFile);
        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet, $writer);

        if ($savePath) {
            return null;
        }

        if (! $filename) {
            $filename = "Laporan_Puskesad_RSPAD_{$m}_{$y}.xlsx";
        }

        return response()->download($targetFile, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Export ONLY RL 3.4 (Pengunjung) Data & Sheets
     */
    public static function exportRL34($month, $year, $filename = null, $poli = null, $savePath = null)
    {
        ini_set('memory_limit', '1024M');
        set_time_limit(300);

        $m = (string) ($month ?: 'SEMUA');
        $y = (string) ($year ?: 'SEMUA');

        $cacheFile = self::getCachePath('rl34', $m, $y, $poli);

        if (! $savePath && file_exists($cacheFile) && filesize($cacheFile) > 0) {
            if (! $filename) {
                $filename = "Laporan_RL_3.4_Pengunjung_{$m}_{$y}.xlsx";
            }

            return response()->download($cacheFile, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ]);
        }

        $targetFile = $savePath ?: $cacheFile;

        $query = DB::table('raw_visits');
        self::applyDateFilter($query, $m, $y);
        if ($poli && $poli !== 'SEMUA') {
            $query->where('poliklinik', $poli);
        }

        $visits = $query->orderBy('tgl_berobat')->orderBy('id')->get();

        $uniquePatients = [];
        foreach ($visits as $v) {
            $rmKey = trim((string) $v->no_rm);
            if (! empty($rmKey) && ! isset($uniquePatients[$rmKey])) {
                $uniquePatients[$rmKey] = $v;
            }
        }

        $pengunjungBaru = 0;
        $pengunjungLama = 0;
        foreach ($uniquePatients as $p) {
            if (self::normalizeStatus($p->status_pasien) === 'Pasien Baru') {
                $pengunjungBaru++;
            } else {
                $pengunjungLama++;
            }
        }

        $spreadsheet = new Spreadsheet;
        $darkGreenHeader = '588B8B';

        // ----------------------------------------------------
        // 1. SHEET: RL 3.4 - Summary Pengunjung
        // ----------------------------------------------------
        $sheetSummary = $spreadsheet->getActiveSheet();
        $sheetSummary->setTitle('RL 3.4 - Pengunjung');
        $sheetSummary->setShowGridLines(true);

        $sheetSummary->setCellValue('A1', 'RSPAD GATOT SOEBROTO');
        $sheetSummary->setCellValue('A2', 'LAPORAN REKAPITULASI PENGUNJUNG (RL 3.4)');
        $sheetSummary->setCellValue('A3', 'PERIODE: '.self::getPeriodText($m, $y).' | POLIKLINIK: '.($poli ?: 'SEMUA POLIKLINIK'));
        $sheetSummary->getStyle('A1:A2')->getFont()->setBold(true)->setSize(12);

        $sheetSummary->setCellValue('A5', 'NO');
        $sheetSummary->setCellValue('B5', 'JENIS PENGUNJUNG');
        $sheetSummary->setCellValue('C5', 'JUMLAH PENGUNJUNG');
        $sheetSummary->getStyle('A5:C5')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $darkGreenHeader]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);

        $sheetSummary->setCellValue('A6', 1);
        $sheetSummary->setCellValue('B6', 'Pengunjung Baru');
        $sheetSummary->setCellValue('C6', $pengunjungBaru);

        $sheetSummary->setCellValue('A7', 2);
        $sheetSummary->setCellValue('B7', 'Pengunjung Lama');
        $sheetSummary->setCellValue('C7', $pengunjungLama);

        $sheetSummary->setCellValue('A8', 'TOTAL PENGUNJUNG');
        $sheetSummary->setCellValue('C8', '=SUM(C6:C7)');
        $sheetSummary->getStyle('A8:C8')->getFont()->setBold(true);
        $sheetSummary->getStyle('A5:C8')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        $sheetSummary->getColumnDimension('A')->setWidth(8);
        $sheetSummary->getColumnDimension('B')->setWidth(30);
        $sheetSummary->getColumnDimension('C')->setWidth(24);

        // ----------------------------------------------------
        // 2. SHEET: JK
        // ----------------------------------------------------
        $sheetJK = $spreadsheet->createSheet();
        $sheetJK->setTitle('JK');
        $sheetJK->setShowGridLines(true);

        $jkCounts = ['L' => 0, 'P' => 0];
        foreach ($visits as $p) {
            $g = self::normalizeGender($p->gender);
            $jkCounts[$g]++;
        }

        $sheetJK->setCellValue('A3', 'Count of NO RM');
        $sheetJK->setCellValue('A4', 'KELAMIN');
        $sheetJK->setCellValue('B4', 'Total');
        $sheetJK->setCellValue('A5', 'L');
        $sheetJK->setCellValue('B5', $jkCounts['L']);
        $sheetJK->setCellValue('A6', 'P');
        $sheetJK->setCellValue('B6', $jkCounts['P']);
        $sheetJK->setCellValue('A7', '(blank)');
        $sheetJK->setCellValue('A8', 'Grand Total');
        $sheetJK->setCellValue('B8', '=SUM(B5:B7)');

        $sheetJK->getStyle('A4:B4')->getFont()->setBold(true);
        $sheetJK->getStyle('A8:B8')->getFont()->setBold(true);
        $sheetJK->getStyle('A4:B8')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $sheetJK->getColumnDimension('A')->setWidth(18);
        $sheetJK->getColumnDimension('B')->setWidth(14);

        // ----------------------------------------------------
        // 3. SHEET: PIVOT P
        // ----------------------------------------------------
        $sheetPivotP = $spreadsheet->createSheet();
        $sheetPivotP->setTitle('PIVOT P');
        $sheetPivotP->setShowGridLines(true);

        $sheetPivotP->setCellValue('A3', 'Count of NO RM');
        $sheetPivotP->setCellValue('A4', 'JENIS PEMBAYARAN');
        $sheetPivotP->setCellValue('B4', 'KELOMPOK');
        $sheetPivotP->setCellValue('C4', 'Total');
        $sheetPivotP->getStyle('A4:C4')->getFont()->setBold(true);

        $pivotPData = [];
        foreach ($uniquePatients as $p) {
            $pen = trim((string) $p->jenis_penjamin) ?: 'LAIN-LAIN';
            $kel = self::normalizeKelompokName($p);
            if (! isset($pivotPData[$pen])) {
                $pivotPData[$pen] = [];
            }
            if (! isset($pivotPData[$pen][$kel])) {
                $pivotPData[$pen][$kel] = 0;
            }
            $pivotPData[$pen][$kel]++;
        }

        ksort($pivotPData);

        $rpP = 5;
        $subtotalRowsP = [];

        foreach ($pivotPData as $penName => $kelGroup) {
            ksort($kelGroup);
            $groupStartRow = $rpP;
            $isFirstInGroup = true;

            foreach ($kelGroup as $kelName => $cnt) {
                if ($isFirstInGroup) {
                    $sheetPivotP->setCellValue("A{$rpP}", $penName);
                    $isFirstInGroup = false;
                }
                if (! empty($kelName)) {
                    $sheetPivotP->setCellValue("B{$rpP}", $kelName);
                }
                $sheetPivotP->setCellValue("C{$rpP}", $cnt);
                $rpP++;
            }

            $groupEndRow = $rpP - 1;
            $sheetPivotP->setCellValue("A{$rpP}", "{$penName} Total");
            $sheetPivotP->setCellValue("C{$rpP}", "=SUM(C{$groupStartRow}:C{$groupEndRow})");
            $sheetPivotP->getStyle("A{$rpP}:C{$rpP}")->getFont()->setBold(true);
            $subtotalRowsP[] = "C{$rpP}";
            $rpP++;
        }

        $sheetPivotP->setCellValue("A{$rpP}", 'Grand Total');
        if (! empty($subtotalRowsP)) {
            $sheetPivotP->setCellValue("C{$rpP}", '=SUM('.implode(',', $subtotalRowsP).')');
        } else {
            $sheetPivotP->setCellValue("C{$rpP}", 0);
        }
        $sheetPivotP->getStyle("A{$rpP}:C{$rpP}")->getFont()->setBold(true);
        $sheetPivotP->getStyle("A4:C{$rpP}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        $sheetPivotP->getColumnDimension('A')->setWidth(28);
        $sheetPivotP->getColumnDimension('B')->setWidth(26);
        $sheetPivotP->getColumnDimension('C')->setWidth(14);

        // ----------------------------------------------------
        // 4. SHEET: P
        // ----------------------------------------------------
        $sheetP = $spreadsheet->createSheet();
        $sheetP->setTitle('P');
        $sheetP->setShowGridLines(true);

        $headersDetail = [
            'NO', 'NO RM', 'NAMA PASIEN', 'TANGGAL LAHIR', 'USIA', 'TYPE PASIEN',
            'NO. TELP', 'NO. PONSEL', 'POLI', 'DOKTER', 'TANGGAL', 'JAM', 'NO SEP', 'NO PESERTA',
            'JENIS RAWAT', 'JENIS PEMBAYARAN', 'KELOMPOK', 'PANGKAT', 'NRP', 'KELAMIN',
            'AGAMA', 'PENDIDIKAN', 'KESATUAN', 'ANGKATAN', 'HUBUNGAN KELUARGA', 'ALAMAT',
            'KODE', 'DIAGNOSA AWAL', 'KODE', 'DIAGNOSA AKHIR', 'STATUS',
        ];

        foreach ($headersDetail as $colIdx => $hText) {
            $colLetter = Coordinate::stringFromColumnIndex($colIdx + 1);
            $sheetP->setCellValue("{$colLetter}1", $hText);
        }
        $sheetP->getStyle('A1:AE1')->getFont()->setBold(true);

        $rowsP = [];
        $noP = 1;
        foreach ($uniquePatients as $v) {
            $rowsP[] = self::formatDetailRow($noP++, $v);
        }
        $sheetP->fromArray($rowsP, null, 'A2', true);
        unset($rowsP);
        self::autoFitColumns($sheetP, 'A', 'AE');

        $writer = new Xlsx($spreadsheet);
        $writer->save($targetFile);
        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet, $writer);

        if ($savePath) {
            return null;
        }

        if (! $filename) {
            $filename = "Laporan_RL_3.4_Pengunjung_{$m}_{$y}.xlsx";
        }

        return response()->download($targetFile, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Export ONLY RL 3.5 (Kunjungan Poli) Data & Sheets
     */
    public static function exportRL35($month, $year, $filename = null, $poli = null, $savePath = null)
    {
        ini_set('memory_limit', '1024M');
        set_time_limit(300);

        $m = (string) ($month ?: 'SEMUA');
        $y = (string) ($year ?: 'SEMUA');

        $cacheFile = self::getCachePath('rl35', $m, $y, $poli);

        if (! $savePath && file_exists($cacheFile) && filesize($cacheFile) > 0) {
            if (! $filename) {
                $filename = "Laporan_RL_3.5_Kunjungan_Poli_{$m}_{$y}.xlsx";
            }

            return response()->download($cacheFile, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ]);
        }

        $targetFile = $savePath ?: $cacheFile;

        $query = DB::table('raw_visits');
        self::applyDateFilter($query, $m, $y);
        if ($poli && $poli !== 'SEMUA') {
            $query->where('poliklinik', $poli);
        }

        $visits = $query->orderBy('tgl_berobat')->orderBy('id')->get();

        $spreadsheet = new Spreadsheet;
        $darkGreenHeader = '588B8B';

        // ----------------------------------------------------
        // 1. SHEET: RL 3.5 - Kunjungan Poli
        // ----------------------------------------------------
        $sheetSummary = $spreadsheet->getActiveSheet();
        $sheetSummary->setTitle('RL 3.5 - Kunjungan Poli');
        $sheetSummary->setShowGridLines(true);

        $sheetSummary->setCellValue('A1', 'RSPAD GATOT SOEBROTO');
        $sheetSummary->setCellValue('A2', 'LAPORAN REKAPITULASI KUNJUNGAN POLIKLINIK (RL 3.5)');
        $sheetSummary->setCellValue('A3', 'PERIODE: '.self::getPeriodText($m, $y).' | POLIKLINIK: '.($poli ?: 'SEMUA POLIKLINIK'));
        $sheetSummary->getStyle('A1:A2')->getFont()->setBold(true)->setSize(12);

        $sheetSummary->setCellValue('A5', 'NO');
        $sheetSummary->setCellValue('B5', 'JENIS KEGIATAN');
        $sheetSummary->setCellValue('C5', 'KUNJUNGAN PASIEN DALAM KOTA (L)');
        $sheetSummary->setCellValue('D5', 'KUNJUNGAN PASIEN DALAM KOTA (P)');
        $sheetSummary->setCellValue('E5', 'KUNJUNGAN PASIEN LUAR KOTA (L)');
        $sheetSummary->setCellValue('F5', 'KUNJUNGAN PASIEN LUAR KOTA (P)');
        $sheetSummary->setCellValue('G5', 'TOTAL KUNJUNGAN');

        $sheetSummary->getStyle('A5:G5')->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $darkGreenHeader]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
        ]);

        $stdList = ReportRL35Controller::getStandardPoliList();
        $poliDataMap = [];
        foreach ($stdList as $num => $stdName) {
            $poliDataMap[$stdName] = [
                'no' => (int) $num,
                'poliklinik' => $stdName,
                'dalam_l' => 0,
                'dalam_p' => 0,
                'luar_l' => 0,
                'luar_p' => 0,
                'total' => 0,
            ];
        }

        $poliDataRaw = (clone $query)
            ->reorder()
            ->select(
                'poliklinik',
                DB::raw("SUM(CASE WHEN (LOWER(COALESCE(alamat, '')) LIKE '%jakarta%' OR LOWER(COALESCE(alamat, '')) LIKE '%dki%') AND (UPPER(COALESCE(gender, 'L')) = 'L') THEN 1 ELSE 0 END) as dalam_l"),
                DB::raw("SUM(CASE WHEN (LOWER(COALESCE(alamat, '')) LIKE '%jakarta%' OR LOWER(COALESCE(alamat, '')) LIKE '%dki%') AND (UPPER(COALESCE(gender, 'L')) = 'P') THEN 1 ELSE 0 END) as dalam_p"),
                DB::raw("SUM(CASE WHEN NOT (LOWER(COALESCE(alamat, '')) LIKE '%jakarta%' OR LOWER(COALESCE(alamat, '')) LIKE '%dki%') AND (UPPER(COALESCE(gender, 'L')) = 'L') THEN 1 ELSE 0 END) as luar_l"),
                DB::raw("SUM(CASE WHEN NOT (LOWER(COALESCE(alamat, '')) LIKE '%jakarta%' OR LOWER(COALESCE(alamat, '')) LIKE '%dki%') AND (UPPER(COALESCE(gender, 'L')) = 'P') THEN 1 ELSE 0 END) as luar_p"),
                DB::raw('COUNT(*) as total')
            )
            ->groupBy('poliklinik')
            ->get();

        foreach ($poliDataRaw as $row) {
            $stdName = ReportRL35Controller::normalizePoliToStandard($row->poliklinik);
            if (! isset($poliDataMap[$stdName])) {
                $stdName = 'Lain-Lain';
            }
            $poliDataMap[$stdName]['dalam_l'] += (int) $row->dalam_l;
            $poliDataMap[$stdName]['dalam_p'] += (int) $row->dalam_p;
            $poliDataMap[$stdName]['luar_l'] += (int) $row->luar_l;
            $poliDataMap[$stdName]['luar_p'] += (int) $row->luar_p;
            $poliDataMap[$stdName]['total'] += (int) $row->total;
        }

        if ($poli && $poli !== 'SEMUA') {
            $matchedStd = ReportRL35Controller::normalizePoliToStandard($poli);
            $filteredPoliMap = [];
            foreach ($poliDataMap as $kName => $val) {
                if (strcasecmp($kName, $matchedStd) === 0 || strcasecmp($kName, $poli) === 0) {
                    $filteredPoliMap[$kName] = $val;
                }
            }
            $poliDataMap = $filteredPoliMap;
        }

        $rS = 6;
        foreach ($poliDataMap as $pRow) {
            $sheetSummary->setCellValue("A{$rS}", $pRow['no']);
            $sheetSummary->setCellValue("B{$rS}", $pRow['poliklinik']);
            $sheetSummary->setCellValue("C{$rS}", $pRow['dalam_l']);
            $sheetSummary->setCellValue("D{$rS}", $pRow['dalam_p']);
            $sheetSummary->setCellValue("E{$rS}", $pRow['luar_l']);
            $sheetSummary->setCellValue("F{$rS}", $pRow['luar_p']);
            $sheetSummary->setCellValue("G{$rS}", "=SUM(C{$rS}:F{$rS})");
            $rS++;
        }

        $endRowS = $rS - 1;
        $sheetSummary->setCellValue("A{$rS}", 'TOTAL SELURUH KUNJUNGAN');
        if ($endRowS >= 6) {
            $sheetSummary->setCellValue("C{$rS}", "=SUM(C6:C{$endRowS})");
            $sheetSummary->setCellValue("D{$rS}", "=SUM(D6:D{$endRowS})");
            $sheetSummary->setCellValue("E{$rS}", "=SUM(E6:E{$endRowS})");
            $sheetSummary->setCellValue("F{$rS}", "=SUM(F6:F{$endRowS})");
            $sheetSummary->setCellValue("G{$rS}", "=SUM(G6:G{$endRowS})");
        } else {
            $sheetSummary->setCellValue("C{$rS}", 0);
            $sheetSummary->setCellValue("D{$rS}", 0);
            $sheetSummary->setCellValue("E{$rS}", 0);
            $sheetSummary->setCellValue("F{$rS}", 0);
            $sheetSummary->setCellValue("G{$rS}", 0);
        }
        $sheetSummary->getStyle("A{$rS}:G{$rS}")->getFont()->setBold(true);
        $sheetSummary->getStyle("A5:G{$rS}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        self::autoFitColumns($sheetSummary, 'A', 'G');

        // ----------------------------------------------------
        // 2. SHEET: PIVOT K
        // ----------------------------------------------------
        $sheetPivotK = $spreadsheet->createSheet();
        $sheetPivotK->setTitle('PIVOT K');
        $sheetPivotK->setShowGridLines(true);

        $sheetPivotK->setCellValue('A3', 'Count of NO RM');
        $sheetPivotK->setCellValue('C3', 'TYPE PASIEN');
        $sheetPivotK->setCellValue('A4', 'JENIS PEMBAYARAN');
        $sheetPivotK->setCellValue('B4', 'KELOMPOK');
        $sheetPivotK->setCellValue('C4', 'Pasien Baru');
        $sheetPivotK->setCellValue('D4', 'Pasien Lama');
        $sheetPivotK->setCellValue('E4', '(blank)');
        $sheetPivotK->setCellValue('F4', 'Grand Total');
        $sheetPivotK->getStyle('A4:F4')->getFont()->setBold(true);

        $pivotKData = [];
        foreach ($visits as $v) {
            $pen = trim((string) $v->jenis_penjamin) ?: 'LAIN-LAIN';
            $kel = self::normalizeKelompokName($v);
            $stKey = self::normalizeStatus($v->status_pasien) === 'Pasien Baru' ? 'baru' : 'lama';
            if (! isset($pivotKData[$pen])) {
                $pivotKData[$pen] = [];
            }
            if (! isset($pivotKData[$pen][$kel])) {
                $pivotKData[$pen][$kel] = ['baru' => 0, 'lama' => 0];
            }
            $pivotKData[$pen][$kel][$stKey]++;
        }

        ksort($pivotKData);

        $rpK = 5;
        $subtotalRowsK = [];

        foreach ($pivotKData as $penName => $kelGroup) {
            ksort($kelGroup);
            $groupStartRow = $rpK;
            $isFirstInGroup = true;

            foreach ($kelGroup as $kelName => $c) {
                if ($isFirstInGroup) {
                    $sheetPivotK->setCellValue("A{$rpK}", $penName);
                    $isFirstInGroup = false;
                }
                if (! empty($kelName)) {
                    $sheetPivotK->setCellValue("B{$rpK}", $kelName);
                }
                if ($c['baru'] > 0) {
                    $sheetPivotK->setCellValue("C{$rpK}", $c['baru']);
                }
                if ($c['lama'] > 0) {
                    $sheetPivotK->setCellValue("D{$rpK}", $c['lama']);
                }
                $sheetPivotK->setCellValue("F{$rpK}", "=SUM(C{$rpK}:D{$rpK})");
                $rpK++;
            }

            $groupEndRow = $rpK - 1;
            $sheetPivotK->setCellValue("A{$rpK}", "{$penName} Total");
            $sheetPivotK->setCellValue("C{$rpK}", "=SUM(C{$groupStartRow}:C{$groupEndRow})");
            $sheetPivotK->setCellValue("D{$rpK}", "=SUM(D{$groupStartRow}:D{$groupEndRow})");
            $sheetPivotK->setCellValue("F{$rpK}", "=SUM(F{$groupStartRow}:F{$groupEndRow})");
            $sheetPivotK->getStyle("A{$rpK}:F{$rpK}")->getFont()->setBold(true);

            $subtotalRowsK[] = $rpK;
            $rpK++;
        }

        $sheetPivotK->setCellValue("A{$rpK}", 'Grand Total');
        if (! empty($subtotalRowsK)) {
            $cSub = array_map(fn ($r) => "C{$r}", $subtotalRowsK);
            $dSub = array_map(fn ($r) => "D{$r}", $subtotalRowsK);
            $fSub = array_map(fn ($r) => "F{$r}", $subtotalRowsK);

            $sheetPivotK->setCellValue("C{$rpK}", '=SUM('.implode(',', $cSub).')');
            $sheetPivotK->setCellValue("D{$rpK}", '=SUM('.implode(',', $dSub).')');
            $sheetPivotK->setCellValue("F{$rpK}", '=SUM('.implode(',', $fSub).')');
        } else {
            $sheetPivotK->setCellValue("C{$rpK}", 0);
            $sheetPivotK->setCellValue("D{$rpK}", 0);
            $sheetPivotK->setCellValue("F{$rpK}", 0);
        }
        $sheetPivotK->getStyle("A{$rpK}:F{$rpK}")->getFont()->setBold(true);
        $sheetPivotK->getStyle("A4:F{$rpK}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        $sheetPivotK->getColumnDimension('A')->setWidth(28);
        $sheetPivotK->getColumnDimension('B')->setWidth(26);
        $sheetPivotK->getColumnDimension('C')->setWidth(16);
        $sheetPivotK->getColumnDimension('D')->setWidth(16);
        $sheetPivotK->getColumnDimension('E')->setWidth(10);
        $sheetPivotK->getColumnDimension('F')->setWidth(16);

        // ----------------------------------------------------
        // 3. SHEET: R
        // ----------------------------------------------------
        $sheetR = $spreadsheet->createSheet();
        $sheetR->setTitle('R');
        $sheetR->setShowGridLines(true);

        $headersDetail = [
            'NO', 'NO RM', 'NAMA PASIEN', 'TANGGAL LAHIR', 'USIA', 'TYPE PASIEN',
            'NO. TELP', 'NO. PONSEL', 'POLI', 'DOKTER', 'TANGGAL', 'JAM', 'NO SEP', 'NO PESERTA',
            'JENIS RAWAT', 'JENIS PEMBAYARAN', 'KELOMPOK', 'PANGKAT', 'NRP', 'KELAMIN',
            'AGAMA', 'PENDIDIKAN', 'KESATUAN', 'ANGKATAN', 'HUBUNGAN KELUARGA', 'ALAMAT',
            'KODE', 'DIAGNOSA AWAL', 'KODE', 'DIAGNOSA AKHIR', 'STATUS',
        ];

        foreach ($headersDetail as $colIdx => $hText) {
            $colLetter = Coordinate::stringFromColumnIndex($colIdx + 1);
            $sheetR->setCellValue("{$colLetter}1", $hText);
        }
        $sheetR->getStyle('A1:AE1')->getFont()->setBold(true);

        $rowsR = [];
        $noR = 1;
        foreach ($visits as $v) {
            $rowsR[] = self::formatDetailRow($noR++, $v);
        }
        $sheetR->fromArray($rowsR, null, 'A2', true);
        unset($rowsR);
        self::autoFitColumns($sheetR, 'A', 'AE');

        $writer = new Xlsx($spreadsheet);
        $writer->save($targetFile);
        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet, $writer);

        if ($savePath) {
            return null;
        }

        if (! $filename) {
            $filename = "Laporan_RL_3.5_Kunjungan_Poli_{$m}_{$y}.xlsx";
        }

        return response()->download($targetFile, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    private static function autoFitColumns($sheet, $startCol, $endCol)
    {
        $startIdx = Coordinate::columnIndexFromString($startCol);
        $endIdx = Coordinate::columnIndexFromString($endCol);

        for ($i = $startIdx; $i <= $endIdx; $i++) {
            $colLetter = Coordinate::stringFromColumnIndex($i);
            $sheet->getColumnDimension($colLetter)->setAutoSize(true);
        }
    }

    private static function formatDetailRow($idx, $v)
    {
        return [
            $idx,
            (string) ($v->no_rm ?? ''),
            (string) ($v->nama_pasien ?? ''),
            (string) ($v->tgl_lahir ?? ''),
            (string) ($v->umur ?? ''),
            self::normalizeStatus($v->status_pasien),
            (string) ($v->no_telp ?? ''),
            (string) ($v->no_hp ?? ''),
            (string) ($v->poliklinik ?? ''),
            (string) ($v->dokter ?? ''),
            (string) ($v->tgl_berobat ?? ''),
            (string) ($v->jam ?? ''),
            (string) ($v->no_sep ?? ''),
            (string) ($v->no_bpjs ?? ''),
            (string) ($v->jenis_rawat ?? ''),
            (string) ($v->jenis_penjamin ?? ''),
            (string) ($v->kelompok ?? ''),
            (string) ($v->pangkat ?? ''),
            (string) ($v->nip_nrp_pasien ?? ''),
            self::normalizeGender($v->gender),
            (string) ($v->agama ?? ''),
            (string) ($v->pendidikan ?? ''),
            (string) ($v->kesatuan ?? ''),
            (string) ($v->instansi ?? ''),
            (string) ($v->kategori ?? ''),
            (string) ($v->alamat ?? ''),
            (string) ($v->icd10_utama ?? ''),
            (string) ($v->deskripsi_icd10_utama ?? ''),
            (string) ($v->icd10_sekunder ?? ''),
            (string) ($v->deskripsi_icd10_sekunder ?? ''),
            (string) ($v->status_registrasi ?? 'open'),
        ];
    }

    private static function formatLapRow($idx, $v)
    {
        return [
            $idx,
            (string) ($v->no_rm ?? ''),
            (string) ($v->nama_pasien ?? ''),
            (string) ($v->tgl_lahir ?? ''),
            (string) ($v->umur ?? ''),
            (string) ($v->no_telp ?? ''),
            (string) ($v->no_hp ?? ''),
            (string) ($v->poliklinik ?? ''),
            (string) ($v->dokter ?? ''),
            (string) ($v->tgl_berobat ?? ''),
            (string) ($v->jam ?? ''),
            '',
            (string) ($v->no_sep ?? ''),
            (string) ($v->no_bpjs ?? ''),
            (string) ($v->jenis_rawat ?? ''),
            (string) ($v->jenis_penjamin ?? ''),
            (string) ($v->kelompok ?? ''),
            (string) ($v->pangkat ?? ''),
            (string) ($v->nip_nrp_pasien ?? ''),
            self::normalizeGender($v->gender),
            (string) ($v->agama ?? ''),
            (string) ($v->pendidikan ?? ''),
            (string) ($v->kesatuan ?? ''),
            (string) ($v->instansi ?? ''),
            (string) ($v->kategori ?? ''),
            (string) ($v->alamat ?? ''),
            (string) ($v->icd10_utama ?? ''),
            (string) ($v->deskripsi_icd10_utama ?? ''),
            (string) ($v->icd10_sekunder ?? ''),
            (string) ($v->deskripsi_icd10_sekunder ?? ''),
            (string) ($v->status_registrasi ?? 'open'),
        ];
    }

    private static function applyDateFilter($query, $month, $year)
    {
        $yearStr = (string) $year;
        $monthStr = (string) $month;

        if ($yearStr !== 'SEMUA' && (int) $yearStr > 1900) {
            $query->whereYear('tgl_berobat', (int) $yearStr);
        }

        if ($monthStr === 'SEMUA') {
            // No month filter
        } elseif (in_array($monthStr, ['T1', 'T2', 'T3', 'T4', 'S1', 'S2'])) {
            $ranges = [
                'T1' => [1, 3], 'T2' => [4, 6], 'T3' => [7, 9], 'T4' => [10, 12],
                'S1' => [1, 6], 'S2' => [7, 12],
            ];
            $range = $ranges[$monthStr];
            $query->where(function ($q) use ($range) {
                if (DB::connection()->getDriverName() === 'sqlite') {
                    $q->whereRaw("CAST(strftime('%m', tgl_berobat) AS INTEGER) BETWEEN ? AND ?", $range);
                } else {
                    $q->whereBetween(DB::raw('MONTH(tgl_berobat)'), $range);
                }
            });
        } else {
            $query->whereMonth('tgl_berobat', (int) $monthStr);
        }

        return $query;
    }
}
