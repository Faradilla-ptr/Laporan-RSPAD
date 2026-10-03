<?php

namespace App\Http\Controllers;

use App\Models\RawVisit;
use App\Services\ActivityLogger;
use App\Services\ExcelReportExporter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportRL34Controller extends Controller
{
    public function index(Request $request)
    {
        $latestDate = RawVisit::max('tgl_berobat');
        $defaultMonth = $latestDate ? (string) date('n', strtotime($latestDate)) : (string) date('n');
        $defaultYear = $latestDate ? (string) date('Y', strtotime($latestDate)) : (string) date('Y');

        $month = (string) $request->input('month', $defaultMonth);
        $year = (string) $request->input('year', $defaultYear);
        $poli = $request->input('poli', 'SEMUA');

        ActivityLogger::log('VIEW_REPORT_RL34', "Melihat Laporan RL 3.4 (Pengunjung Rumah Sakit) Periode {$month}/{$year}, Poliklinik: {$poli}.");

        $polikliniks = RawVisit::whereNotNull('poliklinik')->where('poliklinik', '!=', '')->distinct('poliklinik')->pluck('poliklinik')->filter()->sort()->values();

        $query = RawVisit::query();
        $this->applyDateFilter($query, $month, $year);

        if ($poli && $poli !== 'SEMUA') {
            $query->where('poliklinik', $poli);
        }

        // Deduplication Logic for RL 3.4
        $pengunjungBaru = (clone $query)->where('status_pasien', 'Pasien Baru')->distinct('no_rm')->count('no_rm');
        $pengunjungLama = (clone $query)->where('status_pasien', 'Pasien Lama')->distinct('no_rm')->count('no_rm');
        $totalPengunjung = $pengunjungBaru + $pengunjungLama;

        // Sample list of deduplicated patients for verification
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
            ->paginate(15)
            ->withQueryString();

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

        ActivityLogger::log('EXPORT_RL34_EXCEL', "Mengunduh berkas Laporan RL 3.4 Excel Periode {$month}/{$year}, Poliklinik: {$poli}.");

        $poliSlug = ($poli && $poli !== 'SEMUA') ? preg_replace('/[^A-Za-z0-9_\-]/', '_', $poli) : 'SEMUA';
        $filename = "Laporan_RL_3.4_RSPAD_{$month}_{$year}_{$poliSlug}.xlsx";

        return ExcelReportExporter::exportRL34($month, $year, $filename, $poli);
    }

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

        $startDate = sprintf('%04d-%02d-01', $year, $month);
        $endDate = date('Y-m-t', strtotime($startDate));

        $query = RawVisit::whereBetween('tgl_berobat', [$startDate, $endDate])
            ->where('status_pasien', $statusPasien);

        if ($poli && $poli !== 'SEMUA') {
            $query->where('poliklinik', $poli);
        }

        $updatedCount = $query->update(['status_pasien' => $newStatus]);

        ActivityLogger::log('UPDATE_RL34_DATA', "Memperbarui {$updatedCount} data status pengunjung dari '{$statusPasien}' menjadi '{$newStatus}' (Periode {$month}/{$year}, Poli: {$poli}).");

        return back()->with('success', "Berhasil memperbarui {$updatedCount} data status pengunjung dari '{$statusPasien}' menjadi '{$newStatus}'.");
    }

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

        $startDate = sprintf('%04d-%02d-01', $year, $month);
        $endDate = date('Y-m-t', strtotime($startDate));

        $query = RawVisit::whereBetween('tgl_berobat', [$startDate, $endDate])
            ->where('status_pasien', $statusPasien);

        if ($poli && $poli !== 'SEMUA') {
            $query->where('poliklinik', $poli);
        }

        $deletedCount = $query->delete();

        ActivityLogger::log('DELETE_RL34_DATA', "Menghapus {$deletedCount} data pengunjung status '{$statusPasien}' (Periode {$month}/{$year}, Poli: {$poli}).");

        return back()->with('success', "Berhasil menghapus {$deletedCount} data pengunjung '{$statusPasien}' pada periode ini.");
    }
}
