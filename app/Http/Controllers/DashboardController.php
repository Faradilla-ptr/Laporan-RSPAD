<?php

namespace App\Http\Controllers;

use App\Models\RawVisit;
use App\Models\ImportLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $month = $request->input('month', 8);
        $year  = $request->input('year', 2026);

        $query = RawVisit::query();
        if ($month) {
            $query->whereMonth('tgl_berobat', $month);
        }
        if ($year) {
            $query->whereYear('tgl_berobat', $year);
        }

        // Metrics
        $totalKunjungan  = (clone $query)->count();
        $totalPengunjung = (clone $query)->distinct('no_rm')->count('no_rm');
        $pengunjungBaru  = (clone $query)->where('status_pasien', 'Pasien Baru')->distinct('no_rm')->count('no_rm');
        $pengunjungLama  = (clone $query)->where('status_pasien', 'Pasien Lama')->distinct('no_rm')->count('no_rm');

        // Total TNI AD & Tanggungan vs General / BPJS
        $totalMiliterTni = (clone $query)->whereIn('kelompok', ['MILITER TNI AD', 'KELUARGA MILITER', 'PNS KEMHAN/TNI', 'KELUARGA PNS', 'PURNAWIRAWAN'])->count();
        $totalBpjsUmum   = (clone $query)->whereIn('kelompok', ['BPJS PBI', 'BPJS MANDIRI / SWASTA', 'UMUM / TUNAI'])->count();

        // Kelompok Breakdown (For Pie / Doughnut Chart)
        $kelompokBreakdown = (clone $query)
            ->select('kelompok', DB::raw('count(*) as total_kunjungan'), DB::raw('count(distinct no_rm) as total_pengunjung'))
            ->groupBy('kelompok')
            ->orderByDesc('total_kunjungan')
            ->get();

        // Poliklinik Top 10 Breakdown (For Bar Chart)
        $poliBreakdown = (clone $query)
            ->select('poliklinik', DB::raw('count(*) as total'))
            ->groupBy('poliklinik')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        // Daily Trend (For Line Chart)
        $dailyTrend = (clone $query)
            ->select(DB::raw('DATE(tgl_berobat) as date'), DB::raw('count(*) as total_kunjungan'), DB::raw('count(distinct no_rm) as total_pengunjung'))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $recentImports = ImportLog::with('user')->latest()->limit(5)->get();

        return view('dashboard', compact(
            'month',
            'year',
            'totalKunjungan',
            'totalPengunjung',
            'pengunjungBaru',
            'pengunjungLama',
            'totalMiliterTni',
            'totalBpjsUmum',
            'kelompokBreakdown',
            'poliBreakdown',
            'dailyTrend',
            'recentImports'
        ));
    }
}
