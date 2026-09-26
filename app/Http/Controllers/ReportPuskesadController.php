<?php

namespace App\Http\Controllers;

use App\Models\RawVisit;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class ReportPuskesadController extends Controller
{
    public function index(Request $request)
    {
        $month = $request->input('month', 8);
        $year  = $request->input('year', 2026);
        $poli  = $request->input('poli', 'SEMUA');

        $polikliniks = RawVisit::distinct('poliklinik')->pluck('poliklinik')->filter()->sort()->values();

        $query = RawVisit::query();
        if ($month) {
            $query->whereMonth('tgl_berobat', $month);
        }
        if ($year) {
            $query->whereYear('tgl_berobat', $year);
        }
        if ($poli && $poli !== 'SEMUA') {
            $query->where('poliklinik', $poli);
        }

        $allVisits = $query->get();

        $totalKunjunganAll = max(1, $allVisits->count());
        $totalPengunjungAll = max(1, $allVisits->pluck('no_rm')->unique()->count());

        // Define exact ordered list of Puskesad status categories
        $statusCategories = [
            '1. JKN AKTIF' => [
                'a. TNI AD' => 'TNI AD',
                'b. PNS AD' => 'PNS AD',
                'c. KEL AD' => 'KEL AD',
                'd. TNI AL' => 'TNI AL',
                'e. PNS AL' => 'PNS AL',
                'f. KEL AL' => 'KEL AL',
                'g. TNI AU' => 'TNI AU',
                'h. PNS AU' => 'PNS AU',
                'i. KEL AU' => 'KEL AU',
                'j. PPPK DINAS' => 'PPPK DINAS',
            ],
            '2. NON-DINAS / LAINNYA' => [
                'a. JKN POLRI' => 'JKN POLRI',
                'b. PURNAWIRAWAN' => 'PURNAWIRAWAN',
                'c. BPJS KEMENTERIAN / SWASTA' => 'BPJS KEMENTERIAN / SWASTA',
                'd. BPJS PBI' => 'BPJS PBI',
                'e. ASURANSI / LAINNYA' => 'ASURANSI / LAIN-LAIN',
                'f. TUNAI / UMUM' => 'TUNAI',
            ]
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
                $kunjunganPct  = round(($kunjunganCount / $totalKunjunganAll) * 100, 2);

                $reportData[$groupName][$label] = [
                    'pengunjung' => $pengunjungCount,
                    'pengunjung_pct' => $pengunjungPct,
                    'kunjungan' => $kunjunganCount,
                    'kunjungan_pct' => $kunjunganPct,
                ];

                $subTotals[$groupName]['pengunjung'] += $pengunjungCount;
                $subTotals[$groupName]['kunjungan']  += $kunjunganCount;
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
        $month = $request->input('month', 8);
        $year  = $request->input('year', 2026);
        $poli  = $request->input('poli', 'SEMUA');

        $poliSlug = ($poli && $poli !== 'SEMUA') ? preg_replace('/[^A-Za-z0-9_\-]/', '_', $poli) : 'SEMUA';
        $filename = "Laporan_Rawat_Jalan_Dinas_Puskesad_{$month}_{$year}_{$poliSlug}.xlsx";

        \App\Services\ExcelReportExporter::exportFullOutput($month, $year, $filename, $poli);
    }
}
