<?php

namespace App\Http\Controllers;

use App\Models\ImportLog;
use App\Models\RawVisit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $month = (int) $request->input('month', 6);
        $year = (int) $request->input('year', 2026);

        $query = RawVisit::query();
        if ($month) {
            $query->whereMonth('tgl_berobat', $month);
        }
        if ($year) {
            $query->whereYear('tgl_berobat', $year);
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

        // Daily Trend (For Line Chart)
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

        $recentImports = ImportLog::with('user')->latest()->limit(5)->get();

        // Available Months & Years for Filter Dropdowns
        $availableMonths = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];

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
            'recentImports',
            'availableMonths'
        ));
    }
}
