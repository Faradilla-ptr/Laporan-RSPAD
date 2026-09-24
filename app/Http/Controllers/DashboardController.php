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
        $totalKunjungan = (clone $query)->count();
        $totalPengunjung = (clone $query)->distinct('no_rm')->count('no_rm');
        $pengunjungBaru  = (clone $query)->where('status_pasien', 'Pasien Baru')->distinct('no_rm')->count('no_rm');
        $pengunjungLama  = (clone $query)->where('status_pasien', 'Pasien Lama')->distinct('no_rm')->count('no_rm');

        // Poliklinik Breakdown
        $poliBreakdown = (clone $query)
            ->select('poliklinik', DB::raw('count(*) as total'))
            ->groupBy('poliklinik')
            ->orderByDesc('total')
            ->limit(7)
            ->get();

        // Status Dinas Puskesad Breakdown
        $allVisits = (clone $query)->get();
        $statusPuskesadStats = [];
        $totalPengunjungAll = max(1, $totalPengunjung);
        $totalKunjunganAll  = max(1, $totalKunjungan);

        // Group visits by StatusPuskesad
        $groupedByPuskesad = $allVisits->groupBy(function ($item) {
            return $item->status_puskesad;
        });

        foreach ($groupedByPuskesad as $statusName => $visits) {
            $kunjunganCount = $visits->count();
            $pengunjungCount = $visits->pluck('no_rm')->unique()->count();

            $statusPuskesadStats[$statusName] = [
                'pengunjung' => $pengunjungCount,
                'pengunjung_pct' => round(($pengunjungCount / $totalPengunjungAll) * 100, 2),
                'kunjungan' => $kunjunganCount,
                'kunjungan_pct' => round(($kunjunganCount / $totalKunjunganAll) * 100, 2),
            ];
        }

        // Daily trend for chart
        $dailyTrend = (clone $query)
            ->select(DB::raw('DATE(tgl_berobat) as date'), DB::raw('count(*) as total'))
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
            'poliBreakdown',
            'statusPuskesadStats',
            'dailyTrend',
            'recentImports'
        ));
    }
}
