<?php

namespace App\Http\Controllers;

use App\Models\RawVisit;
use App\Services\ActivityLogger;
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

        ActivityLogger::log('VIEW_REPORT_PUSKESAD', "Melihat Laporan Rawat Jalan Dinas Puskesad Periode {$month}/{$year}, Poliklinik: {$poli}.");

        $polikliniks = RawVisit::whereNotNull('poliklinik')->where('poliklinik', '!=', '')->distinct('poliklinik')->pluck('poliklinik')->filter()->sort()->values();

        $query = RawVisit::query();
        $this->applyDateFilter($query, $month, $year);

        if ($poli && $poli !== 'SEMUA') {
            $query->where('poliklinik', $poli);
        }

        $allVisits = $query->get();

        $totalKunjunganAll = max(1, $allVisits->count());
        $totalPengunjungAll = max(1, $allVisits->pluck('no_rm')->unique()->count());

        // Define exact ordered list of 10 Puskesad status categories matching official report format
        $statusCategories = [
            '1. JKN AKTIF' => [
                'has_subtotal' => true,
                'items' => [
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
            ],
            '2. JKN POLRI' => [
                'has_subtotal' => true,
                'items' => [
                    'a. POLRI' => 'POLRI',
                    'b. PNS POLRI' => 'PNS POLRI',
                    'c. KEL POLRI' => 'KEL POLRI',
                ],
            ],
            '3. JKN PURNAWIRAWAN' => [
                'has_subtotal' => false,
                'items' => [
                    'JKN PURNAWIRAWAN' => 'JKN PURNAWIRAWAN',
                ],
            ],
            '4. JKN KEMENTERIAN' => [
                'has_subtotal' => false,
                'items' => [
                    'JKN KEMENTERIAN' => 'JKN KEMENTERIAN',
                ],
            ],
            '5. PPPK KEMENTERIAN' => [
                'has_subtotal' => false,
                'items' => [
                    'PPPK KEMENTERIAN' => 'PPPK KEMENTERIAN',
                ],
            ],
            '6. JKN UMUM' => [
                'has_subtotal' => true,
                'items' => [
                    'a. PBI' => 'PBI',
                    'b. MANDIRI' => 'MANDIRI',
                    'c. TENAGA KERJA' => 'TENAGA KERJA',
                ],
            ],
            '7. SWASTA' => [
                'has_subtotal' => false,
                'items' => [
                    'SWASTA' => 'SWASTA',
                ],
            ],
            '8. JAMINAN RSPAD' => [
                'has_subtotal' => false,
                'items' => [
                    'JAMINAN RSPAD' => 'JAMINAN RSPAD',
                ],
            ],
            '9. BAKSOS' => [
                'has_subtotal' => false,
                'items' => [
                    'BAKSOS' => 'BAKSOS',
                ],
            ],
            '10. ASURANSI' => [
                'has_subtotal' => false,
                'items' => [
                    'ASURANSI' => 'ASURANSI',
                ],
            ],
        ];

        // Process statistics per category
        $reportData = [];
        $subTotals = [];

        foreach ($statusCategories as $groupName => $groupMeta) {
            $subTotals[$groupName] = [
                'has_subtotal' => $groupMeta['has_subtotal'],
                'pengunjung' => 0,
                'pengunjung_pct' => 0,
                'kunjungan' => 0,
                'kunjungan_pct' => 0,
            ];

            foreach ($groupMeta['items'] as $label => $sysStatus) {
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

            $subTotals[$groupName]['pengunjung_pct'] = round(($subTotals[$groupName]['pengunjung'] / $totalPengunjungAll) * 100, 2);
            $subTotals[$groupName]['kunjungan_pct'] = round(($subTotals[$groupName]['kunjungan'] / $totalKunjunganAll) * 100, 2);
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
            'statusCategories',
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

        ActivityLogger::log('EXPORT_PUSKESAD_EXCEL', "Mengunduh berkas Laporan Rawat Jalan Dinas Puskesad Excel Periode {$month}/{$year}, Poliklinik: {$poli}.");

        $poliSlug = ($poli && $poli !== 'SEMUA') ? preg_replace('/[^A-Za-z0-9_\-]/', '_', $poli) : 'SEMUA';
        $filename = "Laporan_Rawat_Jalan_Dinas_Puskesad_{$month}_{$year}_{$poliSlug}.xlsx";

        return ExcelReportExporter::exportFullOutput($month, $year, $filename, $poli);
    }
}
