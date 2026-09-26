<?php

namespace App\Http\Controllers;

use App\Models\RawVisit;
use App\Services\ExcelReportExporter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportRL35Controller extends Controller
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

        $workDays = max(1, (clone $query)->distinct('tgl_berobat')->count('tgl_berobat'));

        $poliDataRaw = (clone $query)
            ->select(
                'poliklinik',
                DB::raw("SUM(CASE WHEN (LOWER(COALESCE(alamat, '')) LIKE '%jakarta%' OR LOWER(COALESCE(alamat, '')) LIKE '%dki%') AND (UPPER(COALESCE(gender, 'L')) = 'L') THEN 1 ELSE 0 END) as dalam_kota_l"),
                DB::raw("SUM(CASE WHEN (LOWER(COALESCE(alamat, '')) LIKE '%jakarta%' OR LOWER(COALESCE(alamat, '')) LIKE '%dki%') AND (UPPER(COALESCE(gender, 'L')) = 'P') THEN 1 ELSE 0 END) as dalam_kota_p"),
                DB::raw("SUM(CASE WHEN NOT (LOWER(COALESCE(alamat, '')) LIKE '%jakarta%' OR LOWER(COALESCE(alamat, '')) LIKE '%dki%') AND (UPPER(COALESCE(gender, 'L')) = 'L') THEN 1 ELSE 0 END) as luar_kota_l"),
                DB::raw("SUM(CASE WHEN NOT (LOWER(COALESCE(alamat, '')) LIKE '%jakarta%' OR LOWER(COALESCE(alamat, '')) LIKE '%dki%') AND (UPPER(COALESCE(gender, 'L')) = 'P') THEN 1 ELSE 0 END) as luar_kota_p"),
                DB::raw('COUNT(*) as total')
            )
            ->groupBy('poliklinik')
            ->orderByDesc('total')
            ->get();

        $poliData = [];
        $totalKunjunganAll = 0;
        foreach ($poliDataRaw as $row) {
            $poliData[] = [
                'poliklinik' => $row->poliklinik ?: 'LAIN-LAIN',
                'dalam_kota_l' => (int) $row->dalam_kota_l,
                'dalam_kota_p' => (int) $row->dalam_kota_p,
                'luar_kota_l' => (int) $row->luar_kota_l,
                'luar_kota_p' => (int) $row->luar_kota_p,
                'total' => (int) $row->total,
            ];
            $totalKunjunganAll += (int) $row->total;
        }

        $avgPerDay = round($totalKunjunganAll / $workDays, 1);

        return view('reports.rl35', compact(
            'month',
            'year',
            'poli',
            'polikliniks',
            'poliData',
            'totalKunjunganAll',
            'workDays',
            'avgPerDay'
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
        $filename = "Laporan_RL_3.5_RSPAD_{$month}_{$year}_{$poliSlug}.xlsx";

        return ExcelReportExporter::exportRL35($month, $year, $filename, $poli);
    }

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

        $startDate = sprintf('%04d-%02d-01', $year, $month);
        $endDate = date('Y-m-t', strtotime($startDate));

        $updatedCount = RawVisit::whereBetween('tgl_berobat', [$startDate, $endDate])
            ->where('poliklinik', $poliklinik)
            ->update(['poliklinik' => $newPoliklinik]);

        return back()->with('success', "Berhasil memperbarui nama Poliklinik dari '{$poliklinik}' menjadi '{$newPoliklinik}' ({$updatedCount} data terupdate).");
    }

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

        $startDate = sprintf('%04d-%02d-01', $year, $month);
        $endDate = date('Y-m-t', strtotime($startDate));

        $deletedCount = RawVisit::whereBetween('tgl_berobat', [$startDate, $endDate])
            ->where('poliklinik', $poliklinik)
            ->delete();

        return back()->with('success', "Berhasil menghapus {$deletedCount} data kunjungan untuk Poliklinik '{$poliklinik}' pada periode ini.");
    }
}
