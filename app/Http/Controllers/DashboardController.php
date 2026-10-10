<?php

namespace App\Http\Controllers;

use App\Models\ImportLog;
use App\Models\RawVisit;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Controller untuk mengelola halaman Dashboard Statistik Utama.
 * Menghitung KPI metrik kunjungan, perbandingan pasien baru vs lama,
 * distribusi kelompok pasien (Militer/BPJS/Umum), poli terpopuler, dan tren kunjungan.
 */
class DashboardController extends Controller
{
    /**
     * Menampilkan dashboard statistik berdasarkan filter bulan, tahun, dan tipe grafik.
     *
     * @return View
     */
    public function index(Request $request)
    {
        // 1. Tentukan nilai default filter tanggal berdasarkan data kunjungan terbaru di database
        $latestDate = RawVisit::max('tgl_berobat');
        // Jika data ada, gunakan bulan dari tanggal terbaru, jika tidak gunakan bulan saat ini
        $defaultMonth = $latestDate ? (string) date('n', strtotime($latestDate)) : (string) date('n');
        // Jika data ada, gunakan tahun dari tanggal terbaru, jika tidak gunakan tahun saat ini
        $defaultYear = $latestDate ? (string) date('Y', strtotime($latestDate)) : (string) date('Y');

        // 2. Ambil parameter filter dari request user (jika kosong gunakan nilai default)
        $month = (string) $request->input('month', $defaultMonth);
        $year = (string) $request->input('year', $defaultYear);
        $chartFilter = $request->input('chart_filter', 'SEMUA'); // Pilihan: SEMUA, KUNJUNGAN, atau PENGUNJUNG

        // 3. Catat aktivitas pembukaan dashboard ke audit log
        ActivityLogger::log('VIEW_DASHBOARD', 'Melihat Dashboard Statistik '.($month !== 'SEMUA' ? 'Bulan '.$month : 'Semua Bulan').' Tahun '.$year.'.');

        // 4. Inisialisasi query dasar untuk model RawVisit (data kunjungan rawat jalan)
        $query = RawVisit::query();

        // 5. Terapkan filter tahun jika user tidak memilih 'SEMUA'
        if ($year !== 'SEMUA') {
            $query->whereYear('tgl_berobat', (int) $year);
        }

        // 6. Terapkan filter bulan (Bisa berupa angka 1-12, Triwulan T1-T4, Semester S1-S2, atau 'SEMUA')
        if ($month === 'SEMUA') {
            // Jika memilih 'SEMUA', tidak ada filter bulan yang diterapkan
        } elseif (in_array($month, ['T1', 'T2', 'T3', 'T4', 'S1', 'S2'])) {
            // Rentang bulan untuk Triwulan dan Semester
            $ranges = [
                'T1' => [1, 3], 'T2' => [4, 6], 'T3' => [7, 9], 'T4' => [10, 12],
                'S1' => [1, 6], 'S2' => [7, 12],
            ];
            $range = $ranges[$month];
            // Filter rentang bulan dengan penyesuaian driver database (SQLite / MySQL)
            $query->where(function ($q) use ($range) {
                if (DB::connection()->getDriverName() === 'sqlite') {
                    $q->whereRaw("CAST(strftime('%m', tgl_berobat) AS INTEGER) BETWEEN ? AND ?", $range);
                } else {
                    $q->whereBetween(DB::raw('MONTH(tgl_berobat)'), $range);
                }
            });
        } else {
            // Filter untuk bulan tunggal (1 sampai 12)
            $query->whereMonth('tgl_berobat', (int) $month);
        }

        // 7. Hitung Metrik Utama (Key Metrics)
        // Total Kunjungan: Menghitung seluruh transaksi/rekaman berobat
        $totalKunjungan = (clone $query)->count();
        // Total Pengunjung: Menghitung jumlah pasien unik berdasarkan nomor rekam medis (no_rm)
        $totalPengunjung = (clone $query)->whereNotNull('no_rm')->where('no_rm', '!=', '')->distinct('no_rm')->count('no_rm');

        // Ambil daftar No RM untuk pasien baru pada periode filter
        $baruRms = (clone $query)
            ->where(function ($q) {
                $q->where('status_pasien', 'Pasien Baru')
                    ->orWhere('status_pasien', 'BARU')
                    ->orWhere('status_pasien', 'B');
            })
            ->whereNotNull('no_rm')
            ->where('no_rm', '!=', '')
            ->distinct()
            ->pluck('no_rm')
            ->toArray();

        // Jumlah pengunjung unik yang berstatus baru
        $pengunjungBaru = count($baruRms);

        // Jumlah pengunjung unik lama (pasien lama yang tidak terdaftar sebagai pasien baru di periode ini)
        $pengunjungLama = (clone $query)
            ->where(function ($q) {
                $q->where('status_pasien', 'Pasien Lama')
                    ->orWhere('status_pasien', 'LAMA')
                    ->orWhere('status_pasien', 'L');
            })
            ->whereNotIn('no_rm', $baruRms)
            ->distinct('no_rm')
            ->count('no_rm');

        // 8. Hitung Perbandingan Pasien Militer TNI & Dinas vs BPJS & Pasien Umum
        // Kategori Militer TNI AD, AL, AU, PNS Kemhan dan Keluarganya
        $totalMiliterTni = (clone $query)->whereIn('kelompok', [
            'MILITER TNI AD', 'KELUARGA MILITER', 'PNS KEMHAN/TNI', 'KELUARGA PNS', 'PURNAWIRAWAN', 'MILITER TNI AL', 'MILITER TNI AU',
        ])->count();

        // Kategori BPJS dan Pasien Umum/Tunai
        $totalBpjsUmum = (clone $query)->whereIn('kelompok', [
            'BPJS PBI', 'BPJS MANDIRI / SWASTA', 'UMUM / TUNAI', 'POLRI & KELUARGA',
        ])->count();

        // 9. Distribusi Berdasarkan Kelompok Pasien (Untuk Doughnut Chart & Tabel Rincian)
        $kelompokBreakdown = (clone $query)
            ->select('kelompok', DB::raw('count(*) as total_kunjungan'), DB::raw('count(distinct no_rm) as total_pengunjung'))
            ->groupBy('kelompok')
            ->orderByDesc('total_kunjungan')
            ->get();

        // 10. Top 10 Poliklinik dengan Kunjungan Terbanyak (Untuk Bar Chart)
        $poliBreakdown = (clone $query)
            ->select('poliklinik', DB::raw('count(*) as total'))
            ->whereNotNull('poliklinik')
            ->where('poliklinik', '!=', '')
            ->groupBy('poliklinik')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        // 11. Tren Kunjungan Harian / Bulanan (Untuk Line Chart)
        if ($month === 'SEMUA') {
            // Jika memilih semua bulan, tampilkan tren per bulan (Januari - Desember)
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
            // Jika memilih bulan spesifik, tampilkan tren harian (tanggal per tanggal)
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

        // 12. Ambil 5 riwayat import data terakhir beserta data user yang mengunggah
        $recentImports = ImportLog::with('user')->latest()->limit(5)->get();

        // 13. Ambil daftar tahun unik yang tersedia di database untuk opsi dropdown filter
        $yearExpr = DB::connection()->getDriverName() === 'sqlite' ? "strftime('%Y', tgl_berobat) as y" : 'YEAR(tgl_berobat) as y';
        $dbYears = RawVisit::selectRaw($yearExpr)
            ->whereNotNull('tgl_berobat')
            ->distinct()
            ->pluck('y')
            ->filter()
            ->sort()
            ->values()
            ->toArray();

        // Jika database belum memiliki data tanggal, sediakan opsi tahun default
        if (empty($dbYears)) {
            $dbYears = [2024, 2025, 2026];
        }

        // 14. Kirim semua variabel data yang telah dihitung ke view blade dashboard
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
