<?php

namespace App\Http\Controllers;

use App\Models\RawVisit;
use App\Services\ExcelReportExporter;
use Illuminate\Http\Request;

class ReportPuskesadController extends Controller
{
    public function index(Request $request)
    {
        $month = (int) $request->input('month', (int) date('n'));
        if ($month < 1 || $month > 12) {
            $month = (int) date('n');
        }
        $year = (int) $request->input('year', (int) date('Y'));
        if ($year < 2000 || $year > 2100) {
            $year = (int) date('Y');
        }
        $poli = $request->input('poli', 'SEMUA');

        $startDate = sprintf('%04d-%02d-01', $year, $month);
        $endDate = date('Y-m-t', strtotime($startDate));

        $polikliniks = RawVisit::whereNotNull('poliklinik')->where('poliklinik', '!=', '')->distinct('poliklinik')->pluck('poliklinik')->filter()->sort()->values();

        $query = RawVisit::query()->whereBetween('tgl_berobat', [$startDate, $endDate]);
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

        return view('reports.puskesad', compact(
            'month',
            'year',
            'poli',
            'polikliniks',
            'reportData',
            'subTotals',
            'totalPengunjungAll',
            'totalKunjunganAll'
        ));
    }

    public function exportExcel(Request $request)
    {
        $month = (int) $request->input('month', 8);
        if ($month < 1 || $month > 12) {
            $month = 8;
        }
        $year = (int) $request->input('year', 2026);
        if ($year < 2000 || $year > 2100) {
            $year = 2026;
        }
        $poli = $request->input('poli', 'SEMUA');

        $poliSlug = ($poli && $poli !== 'SEMUA') ? preg_replace('/[^A-Za-z0-9_\-]/', '_', $poli) : 'SEMUA';
        $filename = "Laporan_Rawat_Jalan_Dinas_Puskesad_{$month}_{$year}_{$poliSlug}.xlsx";

        return ExcelReportExporter::exportFullOutput($month, $year, $filename, $poli);
    }
}
