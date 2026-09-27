<?php

namespace App\Http\Controllers;

use App\Models\RawVisit;
use App\Services\ExcelReportExporter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportPuskesadController extends Controller
{
    public function index(Request $request)
    {
        $latestDate = RawVisit::max('tgl_berobat');
        $defaultMonth = $latestDate ? (string) date('n', strtotime($latestDate)) : (string) date('n');
        $defaultYear = $latestDate ? (string) date('Y', strtotime($latestDate)) : (string) date('Y');

        $month = (string) $request->input('month', $defaultMonth);
        $year = (string) $request->input('year', $defaultYear);
        $poli = $request->input('poli', 'SEMUA');

        $polikliniks = RawVisit::whereNotNull('poliklinik')->where('poliklinik', '!=', '')->distinct('poliklinik')->pluck('poliklinik')->filter()->sort()->values();

        $query = RawVisit::query();
        $this->applyDateFilter($query, $month, $year);

        if ($poli && $poli !== 'SEMUA') {
            $query->where('poliklinik', $poli);
        }

        $allVisits = $query->get();

        $totalKunjunganAll = max(1, $allVisits->count());
        $totalPengunjungAll = max(1, $allVisits->pluck('no_rm')->unique()->count());

        // Define exact ordered list of Puskesad status categories
        $statusCategories = [
            '1. JKN AKTIF' => [
                'TNI AD' => 'TNI AD',
                'PNS AD' => 'PNS AD',
                'KEL AD' => 'KEL AD',
                'TNI AL' => 'TNI AL',
                'PNS AL' => 'PNS AL',
                'KEL AL' => 'KEL AL',
                'TNI AU' => 'TNI AU',
                'PNS AU' => 'PNS AU',
                'KEL AU' => 'KEL AU',
                'PPPK DINAS' => 'PPPK DINAS',
            ],
            '2. NON-DINAS / LAINNYA' => [
                'JKN POLRI' => 'JKN POLRI',
                'PURNAWIRAWAN' => 'PURNAWIRAWAN',
                'BPJS KEMENTERIAN / SWASTA' => 'BPJS KEMENTERIAN / SWASTA',
                'BPJS PBI' => 'BPJS PBI',
                'ASURANSI / LAINNYA' => 'ASURANSI / LAIN-LAIN',
                'TUNAI / UMUM' => 'TUNAI',
            ],
        ];

        // Process statistics per category
        $reportData = [];
        $subTotals = [
            '1. JKN AKTIF' => ['pengunjung' => 0, 'kunjungan' => 0],
            '2. NON-DINAS / LAINNYA' => ['pengunjung' => 0, 'kunjungan' => 0],
        ];

        foreach ($statusCategories as $groupName => $items) {
            foreach ($items as $label => $sysStatus) {
                // Filter visits matching sysStatus
                $matchingVisits = $allVisits->filter(function ($v) use ($sysStatus) {
                    return $v->status_puskesad === $sysStatus;
                });

                $kunjunganCount = $matchingVisits->count();
                $pengunjungCount = $matchingVisits->pluck('no_rm')->unique()->count();

                $pengunjungPct = round(($pengunjungCount / $totalPengunjungAll) * 100, 2);
                $kunjunganPct = round(($kunjunganCount / $totalKunjunganAll) * 100, 2);

                $reportData[$groupName][$label] = [
                    'pengunjung' => $pengunjungCount,
                    'pengunjung_pct' => $pengunjungPct,
                    'kunjungan' => $kunjunganCount,
                    'kunjungan_pct' => $kunjunganPct,
                ];

                $subTotals[$groupName]['pengunjung'] += $pengunjungCount;
                $subTotals[$groupName]['kunjungan'] += $kunjunganCount;
            }
        }

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

        return view('reports.puskesad', compact(
            'month',
            'year',
            'poli',
            'polikliniks',
            'reportData',
            'subTotals',
            'totalPengunjungAll',
            'totalKunjunganAll',
            'dbYears'
        ));
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

        $poliSlug = ($poli && $poli !== 'SEMUA') ? preg_replace('/[^A-Za-z0-9_\-]/', '_', $poli) : 'SEMUA';
        $filename = "Laporan_Rawat_Jalan_Dinas_Puskesad_{$month}_{$year}_{$poliSlug}.xlsx";

        return ExcelReportExporter::exportFullOutput($month, $year, $filename, $poli);
    }
}
