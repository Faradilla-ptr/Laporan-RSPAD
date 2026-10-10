<?php

namespace App\Http\Controllers;

use App\Models\RawVisit;
use App\Services\ActivityLogger;
use App\Services\ExcelReportExporter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Controller untuk mengelola Laporan Rawat Jalan Dinas Puskesad.
 * Mengklasifikasikan kunjungan dan pengunjung unik ke dalam 10 kategori status dinas militer
 * dan kepesertaan jaminan (TNI AD, AL, AU, Polri, Purnawirawan, Kementerian, BPJS/JKN, Swasta, Asuransi, dll.)
 * serta menghitung persentase kontribusi masing-masing kelompok.
 */
class ReportPuskesadController extends Controller
{
    /**
     * Menampilkan data tabel ringkasan Laporan Puskesad beserta subtotal dan persentasenya.
     *
     * @return View
     */
    public function index(Request $request)
    {
        // 1. Dapatkan tanggal berobat terbaru dari database sebagai nilai awal filter
        $latestDate = RawVisit::max('tgl_berobat');
        $defaultMonth = $latestDate ? (string) date('n', strtotime($latestDate)) : (string) date('n');
        $defaultYear = $latestDate ? (string) date('Y', strtotime($latestDate)) : (string) date('Y');

        // 2. Ambil parameter filter dari request pengguna
        $month = (string) $request->input('month', $defaultMonth);
        $year = (string) $request->input('year', $defaultYear);
        $poli = $request->input('poli', 'SEMUA');

        // 3. Catat aktivitas pembukaan Laporan Puskesad ke audit log
        ActivityLogger::log('VIEW_REPORT_PUSKESAD', "Melihat Laporan Rawat Jalan Dinas Puskesad Periode {$month}/{$year}, Poliklinik: {$poli}.");

        // 4. Ambil daftar poliklinik unik untuk filter dropdown
        $polikliniks = RawVisit::whereNotNull('poliklinik')->where('poliklinik', '!=', '')->distinct('poliklinik')->pluck('poliklinik')->filter()->sort()->values();

        // 5. Bangun query dasar kunjungan dan terapkan filter tanggal
        $query = RawVisit::query();
        $this->applyDateFilter($query, $month, $year);

        // Jika user memfilter poliklinik tertentu
        if ($poli && $poli !== 'SEMUA') {
            $query->where('poliklinik', $poli);
        }

        // Ambil semua koleksi data kunjungan sesuai filter
        $allVisits = $query->get();

        // Hitung total seluruh kunjungan dan total seluruh pengunjung unik (minimal 1 agar tidak terjadi division by zero)
        $totalKunjunganAll = max(1, $allVisits->count());
        $totalPengunjungAll = max(1, $allVisits->pluck('no_rm')->unique()->count());

        // 6. Definisikan susunan 10 kategori kelompok dinas Puskesad sesuai format baku laporan militer
        $statusCategories = [
            '1. JKN AKTIF' => [
                'has_subtotal' => true,
                'items' => [
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
            ],
            '2. JKN POLRI' => [
                'has_subtotal' => true,
                'items' => [
                    'POLRI' => 'POLRI',
                    'PNS POLRI' => 'PNS POLRI',
                    'KEL POLRI' => 'KEL POLRI',
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
                    'PBI' => 'PBI',
                    'MANDIRI' => 'MANDIRI',
                    'TENAGA KERJA' => 'TENAGA KERJA',
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

        // 7. Proses kalkulasi statistik (Kunjungan, Pengunjung, Persentase, dan Subtotal) per kategori
        $reportData = [];
        $subTotals = [];

        foreach ($statusCategories as $groupName => $groupMeta) {
            // Inisialisasi struktur penampung subtotal grup
            $subTotals[$groupName] = [
                'has_subtotal' => $groupMeta['has_subtotal'],
                'pengunjung' => 0,
                'pengunjung_pct' => 0,
                'kunjungan' => 0,
                'kunjungan_pct' => 0,
            ];

            // Iterasi setiap item status dalam kelompok
            foreach ($groupMeta['items'] as $label => $sysStatus) {
                // Filter kunjungan yang cocok dengan status puskesad item ini
                $matchingVisits = $allVisits->filter(function ($v) use ($sysStatus) {
                    return $v->status_puskesad === $sysStatus;
                });

                // Hitung jumlah transaksi berobat (kunjungan) dan jumlah pasien unik (pengunjung)
                $kunjunganCount = $matchingVisits->count();
                $pengunjungCount = $matchingVisits->pluck('no_rm')->unique()->count();

                // Hitung proporsi persentase terhadap total keseluruhan
                $pengunjungPct = round(($pengunjungCount / $totalPengunjungAll) * 100, 2);
                $kunjunganPct = round(($kunjunganCount / $totalKunjunganAll) * 100, 2);

                // Simpan hasil hitungan item
                $reportData[$groupName][$label] = [
                    'pengunjung' => $pengunjungCount,
                    'pengunjung_pct' => $pengunjungPct,
                    'kunjungan' => $kunjunganCount,
                    'kunjungan_pct' => $kunjunganPct,
                ];

                // Akumulasikan ke subtotal kelompok
                $subTotals[$groupName]['pengunjung'] += $pengunjungCount;
                $subTotals[$groupName]['kunjungan'] += $kunjunganCount;
            }

            // Hitung persentase subtotal terhadap total keseluruhan
            $subTotals[$groupName]['pengunjung_pct'] = round(($subTotals[$groupName]['pengunjung'] / $totalPengunjungAll) * 100, 2);
            $subTotals[$groupName]['kunjungan_pct'] = round(($subTotals[$groupName]['kunjungan'] / $totalKunjunganAll) * 100, 2);
        }

        // 8. Ambil daftar tahun yang tersedia untuk filter dropdown
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

        // 9. Kirim hasil kalkulasi ke view tampilan laporan Puskesad
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

    /**
     * Menerapkan filter tahun dan bulan (termasuk Triwulan/Semester) ke instance Eloquent Query.
     *
     * @param  Builder  $query
     * @param  string  $month
     * @param  string  $year
     * @return Builder
     */
    private function applyDateFilter($query, $month, $year)
    {
        // Filter tahun
        if ($year !== 'SEMUA') {
            $query->whereYear('tgl_berobat', (int) $year);
        }

        // Filter bulan / Triwulan / Semester
        if ($month === 'SEMUA') {
            // Seluruh bulan
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

    /**
     * Mengunduh / export data laporan Rawat Jalan Puskesad ke dalam format berkas Excel (.xlsx).
     *
     * @return BinaryFileResponse
     */
    public function exportExcel(Request $request)
    {
        $latestDate = RawVisit::max('tgl_berobat');
        $defaultMonth = $latestDate ? (int) date('n', strtotime($latestDate)) : (int) date('n');
        $defaultYear = $latestDate ? (int) date('Y', strtotime($latestDate)) : (int) date('Y');

        $month = (string) $request->input('month', $defaultMonth);
        $year = (string) $request->input('year', $defaultYear);
        $poli = $request->input('poli', 'SEMUA');

        // Catat aktivitas ekspor ke audit log
        ActivityLogger::log('EXPORT_PUSKESAD_EXCEL', "Mengunduh berkas Laporan Rawat Jalan Dinas Puskesad Excel Periode {$month}/{$year}, Poliklinik: {$poli}.");

        // Format nama berkas Excel yang diunduh
        $poliSlug = ($poli && $poli !== 'SEMUA') ? preg_replace('/[^A-Za-z0-9_\-]/', '_', $poli) : 'SEMUA';
        $filename = "Laporan_Rawat_Jalan_Dinas_Puskesad_{$month}_{$year}_{$poliSlug}.xlsx";

        // Generate berkas Excel menggunakan method exportFullOutput dari ExcelReportExporter
        return ExcelReportExporter::exportFullOutput($month, $year, $filename, $poli);
    }
}
