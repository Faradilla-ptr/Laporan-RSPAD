<?php

namespace App\Http\Controllers;

use App\Models\RawVisit;
use App\Services\ExcelReportExporter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportRL34Controller extends Controller
{
    public function index(Request $request)
    {
        $month = (int) $request->input('month', (int) date('n'));
        if ($month < 1 || $month > 12) {
            $month = (int) date('n');
        }
        $year = (int) $request->input('year', (int) date('Y'));
        if ($year < 2000 || $year > 2100) {
            $year = (int) date('Y');
        }
        $poli = $request->input('poli', 'SEMUA');

        $startDate = sprintf('%04d-%02d-01', $year, $month);
        $endDate = date('Y-m-t', strtotime($startDate));

        $polikliniks = RawVisit::whereNotNull('poliklinik')->where('poliklinik', '!=', '')->distinct('poliklinik')->pluck('poliklinik')->filter()->sort()->values();

        $query = RawVisit::query()->whereBetween('tgl_berobat', [$startDate, $endDate]);
        if ($poli && $poli !== 'SEMUA') {
            $query->where('poliklinik', $poli);
        }

        // Deduplication Logic for RL 3.4
        $pengunjungBaru = (clone $query)->where('status_pasien', 'Pasien Baru')->distinct('no_rm')->count('no_rm');
        $pengunjungLama = (clone $query)->where('status_pasien', 'Pasien Lama')->distinct('no_rm')->count('no_rm');
        $totalPengunjung = $pengunjungBaru + $pengunjungLama;

        // Sample list of deduplicated patients for verification
        $patients = RawVisit::query()
            ->whereBetween('tgl_berobat', [$startDate, $endDate])
            ->when($poli && $poli !== 'SEMUA', fn ($q) => $q->where('poliklinik', $poli))
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

        return view('reports.rl34', compact(
            'month',
            'year',
            'poli',
            'polikliniks',
            'pengunjungBaru',
            'pengunjungLama',
            'totalPengunjung',
            'patients'
        ));
    }

    public function exportExcel(Request $request)
    {
        $month = (int) $request->input('month', (int) date('n'));
        if ($month < 1 || $month > 12) {
            $month = (int) date('n');
        }
        $year = (int) $request->input('year', (int) date('Y'));
        if ($year < 2000 || $year > 2100) {
            $year = (int) date('Y');
        }
        $poli = $request->input('poli', 'SEMUA');

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

        return back()->with('success', "Berhasil menghapus {$deletedCount} data pengunjung '{$statusPasien}' pada periode ini.");
    }
}
