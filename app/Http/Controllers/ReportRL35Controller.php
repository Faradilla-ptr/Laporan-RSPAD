<?php

namespace App\Http\Controllers;

use App\Models\RawVisit;
use App\Models\WorkDayOverride;
use App\Services\ActivityLogger;
use App\Services\ExcelReportExporter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Controller untuk mengelola Laporan RL 3.5 (Perincian Kunjungan Rawat Jalan).
 * Mengolah data kunjungan per poliklinik sesuai standar formulir SIRS Kemenkes:
 * memisahkan kunjungan pasien Dalam Kota (DKI Jakarta) vs Luar Kota berdasarkan jenis kelamin (L/P),
 * memetakan nama poli rumah sakit ke 34 poli standar RL 3.5, serta menghitung rata-rata kunjungan per hari.
 */
class ReportRL35Controller extends Controller
{
    /**
     * Menampilkan tabel laporan RL 3.5 berdasarkan filter periode dan poliklinik.
     *
     * @return View
     */
    public function index(Request $request)
    {
        // 1. Dapatkan tanggal berobat terbaru dari database sebagai patokan default filter
        $latestDate = RawVisit::max('tgl_berobat');
        $defaultMonth = $latestDate ? (string) date('n', strtotime($latestDate)) : (string) date('n');
        $defaultYear = $latestDate ? (string) date('Y', strtotime($latestDate)) : (string) date('Y');

        // 2. Ambil parameter filter dari request pengguna
        $month = (string) $request->input('month', $defaultMonth);
        $year = (string) $request->input('year', $defaultYear);
        $poli = $request->input('poli', 'SEMUA');

        // 3. Catat aktivitas akses laporan ke audit log
        ActivityLogger::log('VIEW_REPORT_RL35', "Melihat Laporan RL 3.5 (Kunjungan Poliklinik) Periode {$month}/{$year}, Poliklinik: {$poli}.");

        // 4. Ambil daftar poliklinik unik dari database untuk pilihan dropdown filter
        $polikliniks = RawVisit::whereNotNull('poliklinik')->where('poliklinik', '!=', '')->distinct('poliklinik')->pluck('poliklinik')->filter()->sort()->values();

        // 5. Bangun query dasar kunjungan rawat jalan dan terapkan filter periode tanggal
        $query = RawVisit::query();
        $this->applyDateFilter($query, $month, $year);

        // Jika user memilih poliklinik tertentu (bukan SEMUA), terapkan kondisi query
        if ($poli && $poli !== 'SEMUA') {
            $query->where('poliklinik', $poli);
        }

        // 6. Tentukan jumlah hari kerja poliklinik buka:
        // Cek apakah ada override manual dari admin untuk periode ini di tabel work_day_overrides
        $override = WorkDayOverride::where('month', $month)->where('year', $year)->first();
        if ($override) {
            $workDays = (float) $override->work_days;
            $isWorkDaysOverridden = true;
        } else {
            // Jika tidak ada override, hitung jumlah tanggal unik berobat dari database (minimal 1 hari)
            $workDays = max(1, (clone $query)->distinct('tgl_berobat')->count('tgl_berobat'));
            $isWorkDaysOverridden = false;
        }

        // 7. Ambil daftar 34 poliklinik standar formulir RL 3.5 Kemenkes RI
        $stdList = self::getStandardPoliList();

        // Siapkan struktur array penampung data untuk setiap poli standar
        $poliDataMap = [];
        foreach ($stdList as $num => $stdName) {
            $poliDataMap[$stdName] = [
                'no' => (int) $num,
                'poliklinik' => $stdName,
                'dalam_kota_l' => 0, // Kunjungan Pasien Dalam Kota Laki-Laki
                'dalam_kota_p' => 0, // Kunjungan Pasien Dalam Kota Perempuan
                'luar_kota_l' => 0,  // Kunjungan Pasien Luar Kota Laki-Laki
                'luar_kota_p' => 0,  // Kunjungan Pasien Luar Kota Perempuan
                'total' => 0,        // Total Kunjungan Poli
            ];
        }

        // 8. Ambil kondisi SQL untuk mendeteksi alamat Dalam Kota (wilayah DKI Jakarta)
        $dalamCond = RawVisit::getDalamKotaSqlCondition('alamat');

        // 9. Agregasikan data kunjungan per poliklinik langsung dari query database
        $poliDataRaw = (clone $query)
            ->reorder()
            ->select(
                'poliklinik',
                DB::raw("SUM(CASE WHEN {$dalamCond} AND (UPPER(COALESCE(gender, 'L')) = 'L') THEN 1 ELSE 0 END) as dalam_kota_l"),
                DB::raw("SUM(CASE WHEN {$dalamCond} AND (UPPER(COALESCE(gender, 'L')) = 'P') THEN 1 ELSE 0 END) as dalam_kota_p"),
                DB::raw("SUM(CASE WHEN NOT {$dalamCond} AND (UPPER(COALESCE(gender, 'L')) = 'L') THEN 1 ELSE 0 END) as luar_kota_l"),
                DB::raw("SUM(CASE WHEN NOT {$dalamCond} AND (UPPER(COALESCE(gender, 'L')) = 'P') THEN 1 ELSE 0 END) as luar_kota_p"),
                DB::raw('COUNT(*) as total')
            )
            ->groupBy('poliklinik')
            ->get();

        // 10. Lakukan pemetaan (mapping) nama poli database ke daftar poli standar RL 3.5
        $totalKunjunganAll = 0;
        foreach ($poliDataRaw as $row) {
            $stdName = self::normalizePoliToStandard($row->poliklinik);
            if (! isset($poliDataMap[$stdName])) {
                $stdName = 'Lain-Lain';
            }
            $poliDataMap[$stdName]['dalam_kota_l'] += (int) $row->dalam_kota_l;
            $poliDataMap[$stdName]['dalam_kota_p'] += (int) $row->dalam_kota_p;
            $poliDataMap[$stdName]['luar_kota_l'] += (int) $row->luar_kota_l;
            $poliDataMap[$stdName]['luar_kota_p'] += (int) $row->luar_kota_p;
            $poliDataMap[$stdName]['total'] += (int) $row->total;
        }

        // Konversi map menjadi array berurutan
        $poliData = array_values($poliDataMap);

        // Jika filter poliklinik tertentu aktif, saring data hanya untuk poliklinik terpilih
        if ($poli && $poli !== 'SEMUA') {
            $matchedStd = self::normalizePoliToStandard($poli);
            $poliData = array_filter($poliData, function ($item) use ($matchedStd, $poli) {
                return strcasecmp($item['poliklinik'], $matchedStd) === 0 || strcasecmp($item['poliklinik'], $poli) === 0;
            });
            $poliData = array_values($poliData);
        }

        // 11. Hitung deduplikasi total kunjungan rumah sakit per pasien per hari sesuai Juknis SIRS 6.3 Hal 37 Poin 2
        $totalKunjunganAll = (clone $query)->select('no_rm', 'tgl_berobat')->whereNotNull('no_rm')->where('no_rm', '!=', '')->distinct()->count();
        if ($totalKunjunganAll === 0) {
            $totalKunjunganAll = array_sum(array_column($poliData, 'total'));
        }

        // 12. Hitung rata-rata kunjungan per hari (Total Kunjungan dibagi Hari Kerja Buka)
        $avgPerDay = round($totalKunjunganAll / $workDays, 1);

        // 13. Ambil daftar tahun yang tersedia untuk filter dropdown
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

        // 14. Kirim seluruh data ke view tampilan laporan RL 3.5
        return view('reports.rl35', compact(
            'month',
            'year',
            'poli',
            'polikliniks',
            'poliData',
            'totalKunjunganAll',
            'workDays',
            'isWorkDaysOverridden',
            'avgPerDay',
            'dbYears'
        ));
    }

    /**
     * Memperbarui jumlah hari kerja poliklinik buka secara manual (Override Hari Kerja).
     *
     * @return RedirectResponse
     */
    public function updateWorkDays(Request $request)
    {
        // Validasi input bulan, tahun, dan jumlah hari kerja (1 sampai 31 hari)
        $request->validate([
            'month' => 'required|string',
            'year' => 'required|string',
            'work_days' => 'required|numeric|min:1|max:31',
        ]);

        $month = (string) $request->month;
        $year = (string) $request->year;
        $workDays = (float) $request->work_days;

        // Simpan atau perbarui nilai override hari kerja di database
        WorkDayOverride::updateOrCreate(
            ['month' => $month, 'year' => $year],
            ['work_days' => $workDays]
        );

        // Bersihkan cache file Excel agar hasil export sinkron dengan perubahan nilai hari kerja
        ExcelReportExporter::clearCache();

        // Catat perubahan ke audit log
        ActivityLogger::log('UPDATE_RL35_WORK_DAYS', "Memperbarui Rata-Rata Hari Poliklinik Buka (Baris 66) menjadi {$workDays} Hari untuk Periode {$month}/{$year}.");

        // Kembali dengan notifikasi sukses
        return back()->with('success', "Berhasil memperbarui Rata-Rata Hari Poliklinik Buka (Baris 66) menjadi {$workDays} Hari pada periode ini.");
    }

    /**
     * Mendapatkan daftar 34 nama poliklinik standar resmi RL 3.5 Kemenkes RI.
     *
     * @return array<string, string>
     */
    public static function getStandardPoliList()
    {
        return [
            '1' => 'Penyakit Dalam',
            '2' => 'Bedah',
            '3' => 'Kesehatan Anak (Neonatal)',
            '4' => 'Kesehatan Anak (Lainnya)',
            '5' => 'Obstetri & Ginekologi (Ibu Hamil)',
            '6' => 'Obstetri & Ginekologi (Lainnya)',
            '7' => 'Keluarga Berencana',
            '8' => 'Jiwa',
            '9' => 'Napza',
            '10' => 'Psikologi',
            '11' => 'THT',
            '12' => 'Mata',
            '13' => 'Kulit dan Kelamin',
            '14' => 'Gigi & Mulut',
            '15' => 'Geriatri',
            '16' => 'Kardiologi',
            '17' => 'Radiologi',
            '18' => 'Bedah Orthopedi',
            '19' => 'Paru - Paru',
            '20' => 'Kanker',
            '21' => 'Uronefrologi',
            '22' => 'Kusta',
            '23' => 'Umum',
            '24' => 'Rawat Darurat',
            '25' => 'Rehabilitasi Medik',
            '26' => 'Akupungtur Medik',
            '27' => 'Konsultasi Gizi',
            '28' => 'Day Care',
            '29' => 'Medical Check Up',
            '30' => 'Bedah Saraf (Stroke)',
            '31' => 'Bedah Saraf (Lainnya)',
            '32' => 'Saraf (Stroke)',
            '33' => 'Saraf (Lainnya)',
            '34' => 'Lain – Lain',
        ];
    }

    /**
     * Melakukan normalisasi/mapping nama poliklinik mentah dari SIMRS RSPAD
     * menjadi salah satu dari 34 kategori poliklinik standar RL 3.5.
     *
     * @param  string|null  $rawPoli
     * @return string
     */
    public static function normalizePoliToStandard($rawPoli)
    {
        $p = strtoupper(trim((string) $rawPoli));
        if (empty($p)) {
            return 'Lain – Lain';
        }

        // Pengecekan kata kunci spesifik poliklinik
        if (str_contains($p, 'PENYAKIT DALAM') || $p === 'PD') {
            return 'Penyakit Dalam';
        }
        if (str_contains($p, 'ORTO')) {
            return 'Bedah Orthopedi';
        }
        if (str_contains($p, 'BEDAH SARAF') || $p === 'B. SARAF') {
            if (str_contains($p, 'STROKE')) {
                return 'Bedah Saraf (Stroke)';
            }

            return 'Bedah Saraf (Lainnya)';
        }
        if (str_contains($p, 'BEDAH')) {
            return 'Bedah';
        }
        if (str_contains($p, 'ANAK') || str_contains($p, 'PEDIATRI')) {
            if (str_contains($p, 'NEONATAL') || str_contains($p, 'BAYI') || str_contains($p, 'NEO')) {
                return 'Kesehatan Anak (Neonatal)';
            }

            return 'Kesehatan Anak (Lainnya)';
        }
        if (str_contains($p, 'OBGIN') || str_contains($p, 'OBSTETRI') || str_contains($p, 'GINEKOLOGI')) {
            if (str_contains($p, 'HAMIL') || str_contains($p, 'ANC') || str_contains($p, 'MATERNAL')) {
                return 'Obstetri & Ginekologi (Ibu Hamil)';
            }

            return 'Obstetri & Ginekologi (Lainnya)';
        }
        if (str_contains($p, 'KB') || str_contains($p, 'KELUARGA BERENCANA')) {
            return 'Keluarga Berencana';
        }
        if (str_contains($p, 'NAPZA') || str_contains($p, 'NARKIS')) {
            return 'Napza';
        }
        if (str_contains($p, 'PSIKOLOGI')) {
            return 'Psikologi';
        }
        if (str_contains($p, 'JIWA') || str_contains($p, 'PSIKIATRI')) {
            return 'Jiwa';
        }
        if (str_contains($p, 'THT')) {
            return 'THT';
        }
        if (str_contains($p, 'MATA')) {
            return 'Mata';
        }
        if (str_contains($p, 'KULIT') || str_contains($p, 'KELAMIN')) {
            return 'Kulit dan Kelamin';
        }
        if (str_contains($p, 'GIGI') || str_contains($p, 'MULUT')) {
            return 'Gigi & Mulut';
        }
        if (str_contains($p, 'GERIATRI')) {
            return 'Geriatri';
        }
        if (str_contains($p, 'JANTUNG') || str_contains($p, 'KARDIO')) {
            return 'Kardiologi';
        }
        if (str_contains($p, 'RADIOLOGI')) {
            return 'Radiologi';
        }
        if (str_contains($p, 'PARU')) {
            return 'Paru - Paru';
        }
        if (str_contains($p, 'KANKER') || str_contains($p, 'ONKOLOGI')) {
            return 'Kanker';
        }
        if (str_contains($p, 'UROLOGI') || str_contains($p, 'URONEFROLOGI') || str_contains($p, 'URO')) {
            return 'Uronefrologi';
        }
        if (str_contains($p, 'KUSTA')) {
            return 'Kusta';
        }
        if (str_contains($p, 'IGD') || str_contains($p, 'EMERGENCY') || str_contains($p, 'RAWAT DARURAT')) {
            return 'Rawat Darurat';
        }
        if (str_contains($p, 'UMUM')) {
            return 'Umum';
        }
        if (str_contains($p, 'REHAB') || str_contains($p, 'FISIOTERAPI')) {
            return 'Rehabilitasi Medik';
        }
        if (str_contains($p, 'AKUPUNKTUR')) {
            return 'Akupungtur Medik';
        }
        if (str_contains($p, 'GIZI')) {
            return 'Konsultasi Gizi';
        }
        if (str_contains($p, 'DAY CARE') || str_contains($p, 'DAYCARE')) {
            return 'Day Care';
        }
        if (str_contains($p, 'CHECK UP') || str_contains($p, 'MCU')) {
            return 'Medical Check Up';
        }
        if (str_contains($p, 'SARAF') || str_contains($p, 'NEUROLOGI')) {
            if (str_contains($p, 'STROKE')) {
                return 'Saraf (Stroke)';
            }

            return 'Saraf (Lainnya)';
        }

        // Jika tidak cocok dengan pola kata kunci di atas, masukkan ke kategori Lain-Lain
        return 'Lain – Lain';
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
     * Mengunduh / export data laporan RL 3.5 ke dalam format berkas Excel (.xlsx).
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
        ActivityLogger::log('EXPORT_RL35_EXCEL', "Mengunduh berkas Laporan RL 3.5 Excel Periode {$month}/{$year}, Poliklinik: {$poli}.");

        // Format nama berkas Excel yang akan diunduh
        $poliSlug = ($poli && $poli !== 'SEMUA') ? preg_replace('/[^A-Za-z0-9_\-]/', '_', $poli) : 'SEMUA';
        $filename = "Laporan_RL_3.5_RSPAD_{$month}_{$year}_{$poliSlug}.xlsx";

        // Panggil service ExcelReportExporter untuk membuat file Excel
        return ExcelReportExporter::exportRL35($month, $year, $filename, $poli);
    }

    /**
     * Memperbarui nama poliklinik untuk semua kunjungan pada periode bulan dan tahun tertentu.
     *
     * @return RedirectResponse
     */
    public function update(Request $request)
    {
        $request->validate([
            'month' => 'required|integer',
            'year' => 'required|integer',
            'poliklinik' => 'required|string',
            'new_poliklinik' => 'required|string',
        ]);

        $month = (int) $request->month;
        $year = (int) $request->year;
        $poliklinik = $request->poliklinik;
        $newPoliklinik = $request->new_poliklinik;

        // Susun rentang tanggal awal dan akhir bulan
        $startDate = sprintf('%04d-%02d-01', $year, $month);
        $endDate = date('Y-m-t', strtotime($startDate));

        // Eksekusi pembaruan data poliklinik
        $updatedCount = RawVisit::whereBetween('tgl_berobat', [$startDate, $endDate])
            ->where('poliklinik', $poliklinik)
            ->update(['poliklinik' => $newPoliklinik]);

        // Catat aktivitas update ke audit log
        ActivityLogger::log('UPDATE_RL35_DATA', "Memperbarui nama Poliklinik dari '{$poliklinik}' menjadi '{$newPoliklinik}' ({$updatedCount} data terupdate, Periode {$month}/{$year}).");

        return back()->with('success', "Berhasil memperbarui nama Poliklinik dari '{$poliklinik}' menjadi '{$newPoliklinik}' ({$updatedCount} data terupdate).");
    }

    /**
     * Menghapus semua kunjungan untuk suatu poliklinik pada periode bulan dan tahun tertentu.
     *
     * @return RedirectResponse
     */
    public function destroy(Request $request)
    {
        $request->validate([
            'month' => 'required|integer',
            'year' => 'required|integer',
            'poliklinik' => 'required|string',
        ]);

        $month = (int) $request->month;
        $year = (int) $request->year;
        $poliklinik = $request->poliklinik;

        // Susun rentang tanggal awal dan akhir bulan
        $startDate = sprintf('%04d-%02d-01', $year, $month);
        $endDate = date('Y-m-t', strtotime($startDate));

        // Eksekusi penghapusan data
        $deletedCount = RawVisit::whereBetween('tgl_berobat', [$startDate, $endDate])
            ->where('poliklinik', $poliklinik)
            ->delete();

        // Catat aktivitas penghapusan ke audit log
        ActivityLogger::log('DELETE_RL35_DATA', "Menghapus {$deletedCount} data kunjungan untuk Poliklinik '{$poliklinik}' (Periode {$month}/{$year}).");

        return back()->with('success', "Berhasil menghapus {$deletedCount} data kunjungan untuk Poliklinik '{$poliklinik}' pada periode ini.");
    }
}
