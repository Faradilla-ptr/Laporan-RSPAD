<?php

namespace App\Http\Controllers;

use App\Models\RawVisit;
use App\Services\ActivityLogger;
use App\Services\ExcelReportExporter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportRL35Controller extends Controller
{
    public function index(Request $request)
    {
        $latestDate = RawVisit::max('tgl_berobat');
        $defaultMonth = $latestDate ? (string) date('n', strtotime($latestDate)) : (string) date('n');
        $defaultYear = $latestDate ? (string) date('Y', strtotime($latestDate)) : (string) date('Y');

        $month = (string) $request->input('month', $defaultMonth);
        $year = (string) $request->input('year', $defaultYear);
        $poli = $request->input('poli', 'SEMUA');

        ActivityLogger::log('VIEW_REPORT_RL35', "Melihat Laporan RL 3.5 (Kunjungan Poliklinik) Periode {$month}/{$year}, Poliklinik: {$poli}.");

        $polikliniks = RawVisit::whereNotNull('poliklinik')->where('poliklinik', '!=', '')->distinct('poliklinik')->pluck('poliklinik')->filter()->sort()->values();

        $query = RawVisit::query();
        $this->applyDateFilter($query, $month, $year);

        if ($poli && $poli !== 'SEMUA') {
            $query->where('poliklinik', $poli);
        }

        $workDays = max(1, (clone $query)->distinct('tgl_berobat')->count('tgl_berobat'));

        $stdList = self::getStandardPoliList();

        $poliDataMap = [];
        foreach ($stdList as $num => $stdName) {
            $poliDataMap[$stdName] = [
                'no' => (int) $num,
                'poliklinik' => $stdName,
                'dalam_kota_l' => 0,
                'dalam_kota_p' => 0,
                'luar_kota_l' => 0,
                'luar_kota_p' => 0,
                'total' => 0,
            ];
        }

        $dalamCond = RawVisit::getDalamKotaSqlCondition('alamat');

        $poliDataRaw = (clone $query)
            ->reorder()
            ->select(
                'poliklinik',
                DB::raw("SUM(CASE WHEN {$dalamCond} AND (UPPER(COALESCE(gender, 'L')) = 'L') THEN 1 ELSE 0 END) as dalam_kota_l"),
                DB::raw("SUM(CASE WHEN {$dalamCond} AND (UPPER(COALESCE(gender, 'L')) = 'P') THEN 1 ELSE 0 END) as dalam_kota_p"),
                DB::raw("SUM(CASE WHEN NOT {$dalamCond} AND (UPPER(COALESCE(gender, 'L')) = 'L') THEN 1 ELSE 0 END) as luar_kota_l"),
                DB::raw("SUM(CASE WHEN NOT {$dalamCond} AND (UPPER(COALESCE(gender, 'L')) = 'P') THEN 1 ELSE 0 END) as luar_kota_p"),
                DB::raw('COUNT(*) as total')
            )
            ->groupBy('poliklinik')
            ->get();

        $totalKunjunganAll = 0;
        foreach ($poliDataRaw as $row) {
            $stdName = self::normalizePoliToStandard($row->poliklinik);
            if (! isset($poliDataMap[$stdName])) {
                $stdName = 'Lain-Lain';
            }
            $poliDataMap[$stdName]['dalam_kota_l'] += (int) $row->dalam_kota_l;
            $poliDataMap[$stdName]['dalam_kota_p'] += (int) $row->dalam_kota_p;
            $poliDataMap[$stdName]['luar_kota_l'] += (int) $row->luar_kota_l;
            $poliDataMap[$stdName]['luar_kota_p'] += (int) $row->luar_kota_p;
            $poliDataMap[$stdName]['total'] += (int) $row->total;

            $totalKunjunganAll += (int) $row->total;
        }

        $poliData = array_values($poliDataMap);

        if ($poli && $poli !== 'SEMUA') {
            $matchedStd = self::normalizePoliToStandard($poli);
            $poliData = array_filter($poliData, function ($item) use ($matchedStd, $poli) {
                return strcasecmp($item['poliklinik'], $matchedStd) === 0 || strcasecmp($item['poliklinik'], $poli) === 0;
            });
            $poliData = array_values($poliData);
        }

        $avgPerDay = round($totalKunjunganAll / $workDays, 1);

        $yearExpr = DB::connection()->getDriverName() === 'sqlite' ? "strftime('%Y', tgl_berobat) as y" : 'YEAR(tgl_berobat) as y';
        $dbYears = RawVisit::selectRaw($yearExpr)
            ->whereNotNull('tgl_berobat')
            ->distinct()
            ->pluck('y')
            ->filter()
            ->sort()
            ->values()
            ->toArray();

        if (empty($dbYears)) {
            $dbYears = [2024, 2025, 2026];
        }

        return view('reports.rl35', compact(
            'month',
            'year',
            'poli',
            'polikliniks',
            'poliData',
            'totalKunjunganAll',
            'workDays',
            'avgPerDay',
            'dbYears'
        ));
    }

    public static function getStandardPoliList()
    {
        return [
            '1' => 'Penyakit Dalam',
            '2' => 'Bedah',
            '3' => 'Kesehatan Anak',
            '4' => 'Obstetri & Ginekologi',
            '5' => 'Saraf',
            '6' => 'Jiwa',
            '7' => 'THT-KL',
            '8' => 'Mata',
            '9' => 'Kulit & Kelamin',
            '10' => 'Gigi & Mulut',
            '11' => 'Jantung & Pembuluh Darah',
            '12' => 'Paru',
            '13' => 'Bedah Saraf',
            '14' => 'Bedah Anak',
            '15' => 'Bedah Plastik',
            '16' => 'Bedah Ortopedi',
            '17' => 'Bedah Urologi',
            '18' => 'Bedah Vaskuler',
            '19' => 'Bedah Thoraks',
            '20' => 'Bedah Digestif',
            '21' => 'Bedah Tumor / Onkologi',
            '22' => 'Gizi Klinik',
            '23' => 'Rehabilitasi Medis',
            '24' => 'Akupunktur Medis',
            '25' => 'Radioterapi',
            '26' => 'Kedokteran Nuklir',
            '27' => 'Anestesiologi & Terapi Intensif',
            '28' => 'Kedokteran Jiwa / Psikiatri',
            '29' => 'General Check Up (MCU)',
            '30' => 'Geriatri',
            '31' => 'Konsultasi Psikologi',
            '32' => 'VCT / HIV-AIDS',
            '33' => 'TB-DOTS',
            '34' => 'Lain-Lain',
        ];
    }

    public static function normalizePoliToStandard($rawPoli)
    {
        $p = strtoupper(trim((string) $rawPoli));
        if (empty($p)) {
            return 'Lain-Lain';
        }

        if (str_contains($p, 'PENYAKIT DALAM') || $p === 'PD') {
            return 'Penyakit Dalam';
        }
        if (str_contains($p, 'DIGEST')) {
            return 'Bedah Digestif';
        }
        if (str_contains($p, 'ORTO')) {
            return 'Bedah Ortopedi';
        }
        if (str_contains($p, 'PLASTIK')) {
            return 'Bedah Plastik';
        }
        if (str_contains($p, 'THORA')) {
            return 'Bedah Thoraks';
        }
        if (str_contains($p, 'TUMOR') || str_contains($p, 'ONKOLOGI')) {
            return 'Bedah Tumor / Onkologi';
        }
        if (str_contains($p, 'UROLOGI') || str_contains($p, 'URO')) {
            return 'Bedah Urologi';
        }
        if (str_contains($p, 'VASKULER')) {
            return 'Bedah Vaskuler';
        }
        if (str_contains($p, 'BEDAH ANAK')) {
            return 'Bedah Anak';
        }
        if (str_contains($p, 'BEDAH SARAF') || $p === 'B. SARAF') {
            return 'Bedah Saraf';
        }
        if (str_contains($p, 'BEDAH')) {
            return 'Bedah';
        }
        if (str_contains($p, 'ANAK')) {
            return 'Kesehatan Anak';
        }
        if (str_contains($p, 'OBGIN') || str_contains($p, 'OBSTETRI') || str_contains($p, 'GINEKOLOGI')) {
            return 'Obstetri & Ginekologi';
        }
        if (str_contains($p, 'SARAF') || str_contains($p, 'NEUROLOGI')) {
            return 'Saraf';
        }
        if (str_contains($p, 'THT')) {
            return 'THT-KL';
        }
        if (str_contains($p, 'MATA')) {
            return 'Mata';
        }
        if (str_contains($p, 'KULIT') || str_contains($p, 'KELAMIN')) {
            return 'Kulit & Kelamin';
        }
        if (str_contains($p, 'GIGI') || str_contains($p, 'MULUT')) {
            return 'Gigi & Mulut';
        }
        if (str_contains($p, 'JANTUNG') || str_contains($p, 'KARDIO')) {
            return 'Jantung & Pembuluh Darah';
        }
        if (str_contains($p, 'PARU')) {
            return 'Paru';
        }
        if (str_contains($p, 'GIZI')) {
            return 'Gizi Klinik';
        }
        if (str_contains($p, 'REHAB') || str_contains($p, 'FISIOTERAPI')) {
            return 'Rehabilitasi Medis';
        }
        if (str_contains($p, 'AKUPUNKTUR')) {
            return 'Akupunktur Medis';
        }
        if (str_contains($p, 'RADIOTERAPI')) {
            return 'Radioterapi';
        }
        if (str_contains($p, 'NUKLIR')) {
            return 'Kedokteran Nuklir';
        }
        if (str_contains($p, 'ANESTESI')) {
            return 'Anestesiologi & Terapi Intensif';
        }
        if (str_contains($p, 'JIWA') || str_contains($p, 'PSIKIATRI')) {
            return 'Jiwa';
        }
        if (str_contains($p, 'CHECK UP') || str_contains($p, 'MCU')) {
            return 'General Check Up (MCU)';
        }
        if (str_contains($p, 'GERIATRI')) {
            return 'Geriatri';
        }
        if (str_contains($p, 'PSIKOLOGI')) {
            return 'Konsultasi Psikologi';
        }
        if (str_contains($p, 'HIV') || str_contains($p, 'VCT')) {
            return 'VCT / HIV-AIDS';
        }
        if (str_contains($p, 'TB') || str_contains($p, 'DOTS')) {
            return 'TB-DOTS';
        }

        return 'Lain-Lain';
    }

    private function applyDateFilter($query, $month, $year)
    {
        if ($year !== 'SEMUA') {
            $query->whereYear('tgl_berobat', (int) $year);
        }

        if ($month === 'SEMUA') {
            // All months
        } elseif (in_array($month, ['T1', 'T2', 'T3', 'T4', 'S1', 'S2'])) {
            $ranges = [
                'T1' => [1, 3], 'T2' => [4, 6], 'T3' => [7, 9], 'T4' => [10, 12],
                'S1' => [1, 6], 'S2' => [7, 12],
            ];
            $range = $ranges[$month];
            $query->where(function ($q) use ($range) {
                if (DB::connection()->getDriverName() === 'sqlite') {
                    $q->whereRaw("CAST(strftime('%m', tgl_berobat) AS INTEGER) BETWEEN ? AND ?", $range);
                } else {
                    $q->whereBetween(DB::raw('MONTH(tgl_berobat)'), $range);
                }
            });
        } else {
            $query->whereMonth('tgl_berobat', (int) $month);
        }

        return $query;
    }

    public function exportExcel(Request $request)
    {
        $latestDate = RawVisit::max('tgl_berobat');
        $defaultMonth = $latestDate ? (int) date('n', strtotime($latestDate)) : (int) date('n');
        $defaultYear = $latestDate ? (int) date('Y', strtotime($latestDate)) : (int) date('Y');

        $month = (string) $request->input('month', $defaultMonth);
        $year = (string) $request->input('year', $defaultYear);
        $poli = $request->input('poli', 'SEMUA');

        ActivityLogger::log('EXPORT_RL35_EXCEL', "Mengunduh berkas Laporan RL 3.5 Excel Periode {$month}/{$year}, Poliklinik: {$poli}.");

        $poliSlug = ($poli && $poli !== 'SEMUA') ? preg_replace('/[^A-Za-z0-9_\-]/', '_', $poli) : 'SEMUA';
        $filename = "Laporan_RL_3.5_RSPAD_{$month}_{$year}_{$poliSlug}.xlsx";

        return ExcelReportExporter::exportRL35($month, $year, $filename, $poli);
    }

    public function update(Request $request)
    {
        $request->validate([
            'month' => 'required|integer',
            'year' => 'required|integer',
            'poliklinik' => 'required|string',
            'new_poliklinik' => 'required|string',
        ]);

        $month = (int) $request->month;
        $year = (int) $request->year;
        $poliklinik = $request->poliklinik;
        $newPoliklinik = $request->new_poliklinik;

        $startDate = sprintf('%04d-%02d-01', $year, $month);
        $endDate = date('Y-m-t', strtotime($startDate));

        $updatedCount = RawVisit::whereBetween('tgl_berobat', [$startDate, $endDate])
            ->where('poliklinik', $poliklinik)
            ->update(['poliklinik' => $newPoliklinik]);

        ActivityLogger::log('UPDATE_RL35_DATA', "Memperbarui nama Poliklinik dari '{$poliklinik}' menjadi '{$newPoliklinik}' ({$updatedCount} data terupdate, Periode {$month}/{$year}).");

        return back()->with('success', "Berhasil memperbarui nama Poliklinik dari '{$poliklinik}' menjadi '{$newPoliklinik}' ({$updatedCount} data terupdate).");
    }

    public function destroy(Request $request)
    {
        $request->validate([
            'month' => 'required|integer',
            'year' => 'required|integer',
            'poliklinik' => 'required|string',
        ]);

        $month = (int) $request->month;
        $year = (int) $request->year;
        $poliklinik = $request->poliklinik;

        $startDate = sprintf('%04d-%02d-01', $year, $month);
        $endDate = date('Y-m-t', strtotime($startDate));

        $deletedCount = RawVisit::whereBetween('tgl_berobat', [$startDate, $endDate])
            ->where('poliklinik', $poliklinik)
            ->delete();

        ActivityLogger::log('DELETE_RL35_DATA', "Menghapus {$deletedCount} data kunjungan untuk Poliklinik '{$poliklinik}' (Periode {$month}/{$year}).");

        return back()->with('success', "Berhasil menghapus {$deletedCount} data kunjungan untuk Poliklinik '{$poliklinik}' pada periode ini.");
    }
}
