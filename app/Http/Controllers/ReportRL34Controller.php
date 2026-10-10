<?php

namespace App\Http\Controllers;

use App\Models\RawVisit;
use App\Services\ActivityLogger;
use App\Services\ExcelReportExporter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Controller untuk mengelola Laporan RL 3.4 (Pengunjung Rumah Sakit).
 * Menghitung jumlah pasien unik (deduplikasi No RM) yang berkunjung ke rumah sakit
 * pada periode tertentu, diklasifikasikan menjadi Pengunjung Baru dan Pengunjung Lama
 * sesuai Petunjuk Teknis SIRS Kemenkes RI 6.3.
 */
class ReportRL34Controller extends Controller
{
    /**
     * Menampilkan data tabel dan metrik Laporan RL 3.4.
     *
     * @return View
     */
    public function index(Request $request)
    {
        // 1. Tentukan nilai filter default berdasarkan data kunjungan terbaru di database
        $latestDate = RawVisit::max('tgl_berobat');
        $defaultMonth = $latestDate ? (string) date('n', strtotime($latestDate)) : (string) date('n');
        $defaultYear = $latestDate ? (string) date('Y', strtotime($latestDate)) : (string) date('Y');

        // 2. Ambil parameter filter dari request pengguna
        $month = (string) $request->input('month', $defaultMonth);
        $year = (string) $request->input('year', $defaultYear);
        $poli = $request->input('poli', 'SEMUA');

        // 3. Catat aktivitas akses Laporan RL 3.4 ke audit log
        ActivityLogger::log('VIEW_REPORT_RL34', "Melihat Laporan RL 3.4 (Pengunjung Rumah Sakit) Periode {$month}/{$year}, Poliklinik: {$poli}.");

        // 4. Ambil daftar poliklinik unik untuk opsi filter dropdown
        $polikliniks = RawVisit::whereNotNull('poliklinik')->where('poliklinik', '!=', '')->distinct('poliklinik')->pluck('poliklinik')->filter()->sort()->values();

        // 5. Bangun query dasar kunjungan dan terapkan filter rentang tanggal
        $query = RawVisit::query();
        $this->applyDateFilter($query, $month, $year);

        // Jika pengguna memilih filter poliklinik tertentu
        if ($poli && $poli !== 'SEMUA') {
            $query->where('poliklinik', $poli);
        }

        // 6. Logika Deduplikasi Pasien Sesuai Juknis SIRS 6.3 (Satu No RM dihitung sekali dalam satu periode):
        // Kumpulkan daftar No RM unik untuk Pasien Baru (kunjungan perdana ke rumah sakit)
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

        // Total jumlah pengunjung baru unik
        $pengunjungBaru = count($baruRms);

        // Kumpulkan jumlah pengunjung lama unik (pasien yang sudah pernah berobat sebelumnya dan bukan pasien baru di bulan ini)
        $pengunjungLama = (clone $query)
            ->where(function ($q) {
                $q->where('status_pasien', 'Pasien Lama')
                    ->orWhere('status_pasien', 'LAMA')
                    ->orWhere('status_pasien', 'L');
            })
            ->whereNotIn('no_rm', $baruRms) // Pastikan pasien yang sudah dihitung sebagai pasien baru tidak dihitung dobel
            ->distinct('no_rm')
            ->count('no_rm');

        // Total seluruh pengunjung rumah sakit (Pengunjung Baru + Pengunjung Lama)
        $totalPengunjung = $pengunjungBaru + $pengunjungLama;

        // 7. Ambil sampel daftar rincian pasien hasil deduplikasi dengan pagination untuk tabel verifikasi data
        $patientsQuery = RawVisit::query();
        $this->applyDateFilter($patientsQuery, $month, $year);
        if ($poli && $poli !== 'SEMUA') {
            $patientsQuery->where('poliklinik', $poli);
        }

        $patients = $patientsQuery
            ->select(
                'no_rm',
                DB::raw('MAX(nama_pasien) as nama_pasien'),
                DB::raw('MAX(status_pasien) as status_pasien'),
                DB::raw('MAX(gender) as gender'),
                DB::raw('MAX(alamat) as alamat')
            )
            ->groupBy('no_rm')
            ->orderBy('no_rm')
            ->paginate(50)
            ->withQueryString();

        // 8. Ambil daftar tahun yang ada pada data berobat untuk filter dropdown
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

        // 9. Kirim data yang telah dikalkulasi ke view laporan RL 3.4
        return view('reports.rl34', compact(
            'month',
            'year',
            'poli',
            'polikliniks',
            'pengunjungBaru',
            'pengunjungLama',
            'totalPengunjung',
            'patients',
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
     * Mengunduh / export data laporan RL 3.4 ke dalam format berkas Excel (.xlsx).
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

        // Catat aktivitas unduh laporan RL 3.4 ke audit log
        ActivityLogger::log('EXPORT_RL34_EXCEL', "Mengunduh berkas Laporan RL 3.4 Excel Periode {$month}/{$year}, Poliklinik: {$poli}.");

        // Format nama berkas Excel yang diunduh
        $poliSlug = ($poli && $poli !== 'SEMUA') ? preg_replace('/[^A-Za-z0-9_\-]/', '_', $poli) : 'SEMUA';
        $filename = "Laporan_RL_3.4_RSPAD_{$month}_{$year}_{$poliSlug}.xlsx";

        // Generate berkas Excel melalui service ExcelReportExporter
        return ExcelReportExporter::exportRL34($month, $year, $filename, $poli);
    }

    /**
     * Memperbarui status pengunjung (misal mengubah status pasien baru/lama) untuk data pada periode terpilih.
     *
     * @return RedirectResponse
     */
    public function update(Request $request)
    {
        $request->validate([
            'month' => 'required|integer',
            'year' => 'required|integer',
            'status_pasien' => 'required|string',
            'new_status' => 'required|string',
        ]);

        $month = (int) $request->month;
        $year = (int) $request->year;
        $poli = $request->input('poli', 'SEMUA');
        $statusPasien = $request->status_pasien;
        $newStatus = $request->new_status;

        // Susun rentang tanggal awal dan akhir bulan
        $startDate = sprintf('%04d-%02d-01', $year, $month);
        $endDate = date('Y-m-t', strtotime($startDate));

        $query = RawVisit::whereBetween('tgl_berobat', [$startDate, $endDate])
            ->where('status_pasien', $statusPasien);

        if ($poli && $poli !== 'SEMUA') {
            $query->where('poliklinik', $poli);
        }

        // Eksekusi pembaruan status pasien
        $updatedCount = $query->update(['status_pasien' => $newStatus]);

        // Catat perubahan ke audit log
        ActivityLogger::log('UPDATE_RL34_DATA', "Memperbarui {$updatedCount} data status pengunjung dari '{$statusPasien}' menjadi '{$newStatus}' (Periode {$month}/{$year}, Poli: {$poli}).");

        return back()->with('success', "Berhasil memperbarui {$updatedCount} data status pengunjung dari '{$statusPasien}' menjadi '{$newStatus}'.");
    }

    /**
     * Menghapus baris data kunjungan berdasarkan status pengunjung pada periode terpilih.
     *
     * @return RedirectResponse
     */
    public function destroy(Request $request)
    {
        $request->validate([
            'month' => 'required|integer',
            'year' => 'required|integer',
            'status_pasien' => 'required|string',
        ]);

        $month = (int) $request->month;
        $year = (int) $request->year;
        $poli = $request->input('poli', 'SEMUA');
        $statusPasien = $request->status_pasien;

        // Susun rentang tanggal awal dan akhir bulan
        $startDate = sprintf('%04d-%02d-01', $year, $month);
        $endDate = date('Y-m-t', strtotime($startDate));

        $query = RawVisit::whereBetween('tgl_berobat', [$startDate, $endDate])
            ->where('status_pasien', $statusPasien);

        if ($poli && $poli !== 'SEMUA') {
            $query->where('poliklinik', $poli);
        }

        // Eksekusi penghapusan data kunjungan
        $deletedCount = $query->delete();

        // Catat aktivitas penghapusan ke audit log
        ActivityLogger::log('DELETE_RL34_DATA', "Menghapus {$deletedCount} data pengunjung status '{$statusPasien}' (Periode {$month}/{$year}, Poli: {$poli}).");

        return back()->with('success', "Berhasil menghapus {$deletedCount} data pengunjung '{$statusPasien}' pada periode ini.");
    }
}
