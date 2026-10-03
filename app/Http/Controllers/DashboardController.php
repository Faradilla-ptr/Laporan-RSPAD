<?php

namespace App\Http\Controllers;

use App\Models\ImportLog;
use App\Models\RawVisit;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $latestDate = RawVisit::max('tgl_berobat');
        $defaultMonth = $latestDate ? (string) date('n', strtotime($latestDate)) : (string) date('n');
        $defaultYear = $latestDate ? (string) date('Y', strtotime($latestDate)) : (string) date('Y');

        $month = (string) $request->input('month', $defaultMonth);
        $year = (string) $request->input('year', $defaultYear);
        $chartFilter = $request->input('chart_filter', 'SEMUA'); // SEMUA, KUNJUNGAN, PENGUNJUNG

        ActivityLogger::log('VIEW_DASHBOARD', 'Melihat Dashboard Statistik '.($month !== 'SEMUA' ? 'Bulan '.$month : 'Semua Bulan').' Tahun '.$year.'.');

        $query = RawVisit::query();

        if ($year !== 'SEMUA') {
            $query->whereYear('tgl_berobat', (int) $year);
        }

        if ($month === 'SEMUA') {
            // All months selected
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

        // Key Metrics
        $totalKunjungan = (clone $query)->count();
        $totalPengunjung = (clone $query)->distinct('no_rm')->count('no_rm');
        $pengunjungBaru = (clone $query)->where('status_pasien', 'Pasien Baru')->distinct('no_rm')->count('no_rm');
        $pengunjungLama = (clone $query)->where('status_pasien', 'Pasien Lama')->distinct('no_rm')->count('no_rm');

        // TNI AD & Dinas vs BPJS & Umum Breakdown
        $totalMiliterTni = (clone $query)->whereIn('kelompok', [
            'MILITER TNI AD', 'KELUARGA MILITER', 'PNS KEMHAN/TNI', 'KELUARGA PNS', 'PURNAWIRAWAN', 'MILITER TNI AL', 'MILITER TNI AU',
        ])->count();

        $totalBpjsUmum = (clone $query)->whereIn('kelompok', [
            'BPJS PBI', 'BPJS MANDIRI / SWASTA', 'UMUM / TUNAI', 'POLRI & KELUARGA',
        ])->count();

        // Kelompok Breakdown (For Doughnut Chart & Table)
        $kelompokBreakdown = (clone $query)
            ->select('kelompok', DB::raw('count(*) as total_kunjungan'), DB::raw('count(distinct no_rm) as total_pengunjung'))
            ->groupBy('kelompok')
            ->orderByDesc('total_kunjungan')
            ->get();

        // Poliklinik Top 10 Breakdown (For Bar Chart)
        $poliBreakdown = (clone $query)
            ->select('poliklinik', DB::raw('count(*) as total'))
            ->whereNotNull('poliklinik')
            ->where('poliklinik', '!=', '')
            ->groupBy('poliklinik')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        // Daily / Monthly Trend (For Line Chart)
        if ($month === 'SEMUA') {
            $monthExpr = DB::connection()->getDriverName() === 'sqlite'
                ? "CAST(strftime('%m', tgl_berobat) AS INTEGER)"
                : 'MONTH(tgl_berobat)';

            $monthlyQuery = (clone $query)
                ->select(
                    DB::raw("{$monthExpr} as m_num"),
                    DB::raw('count(*) as total_kunjungan'),
                    DB::raw('count(distinct no_rm) as total_pengunjung')
                )
                ->whereNotNull('tgl_berobat')
                ->groupBy(DB::raw($monthExpr))
                ->orderBy('m_num', 'asc')
                ->get()
                ->keyBy('m_num');

            $monthNames = [
                1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
                5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
                9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
            ];

            $dailyTrend = collect();
            foreach ($monthNames as $num => $name) {
                $row = $monthlyQuery->get($num);
                $dailyTrend->push((object) [
                    'date' => $name,
                    'total_kunjungan' => $row ? (int) $row->total_kunjungan : 0,
                    'total_pengunjung' => $row ? (int) $row->total_pengunjung : 0,
                ]);
            }
        } else {
            $dailyTrend = (clone $query)
                ->select(
                    DB::raw('DATE(tgl_berobat) as date'),
                    DB::raw('count(*) as total_kunjungan'),
                    DB::raw('count(distinct no_rm) as total_pengunjung')
                )
                ->whereNotNull('tgl_berobat')
                ->groupBy(DB::raw('DATE(tgl_berobat)'))
                ->orderBy('date', 'asc')
                ->get();
        }

        $recentImports = ImportLog::with('user')->latest()->limit(5)->get();

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

        return view('dashboard', compact(
            'month',
            'year',
            'chartFilter',
            'totalKunjungan',
            'totalPengunjung',
            'pengunjungBaru',
            'pengunjungLama',
            'totalMiliterTni',
            'totalBpjsUmum',
            'kelompokBreakdown',
            'poliBreakdown',
            'dailyTrend',
            'recentImports',
            'dbYears'
        ));
    }
}
