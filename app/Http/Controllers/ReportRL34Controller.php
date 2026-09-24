<?php

namespace App\Http\Controllers;

use App\Models\RawVisit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class ReportRL34Controller extends Controller
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

        // Deduplication Logic for RL 3.4
        // 1 No RM unique counted 1 time
        $pengunjungBaru = (clone $query)->where('status_pasien', 'Pasien Baru')->distinct('no_rm')->count('no_rm');
        $pengunjungLama = (clone $query)->where('status_pasien', 'Pasien Lama')->distinct('no_rm')->count('no_rm');
        $totalPengunjung = $pengunjungBaru + $pengunjungLama;

        // Sample list of deduplicated patients for verification
        $patients = RawVisit::query()
            ->when($month, fn($q) => $q->whereMonth('tgl_berobat', $month))
            ->when($year, fn($q) => $q->whereYear('tgl_berobat', $year))
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
            'pengunjungBaru',
            'pengunjungLama',
            'totalPengunjung',
            'patients'
        ));
    }

    public function exportExcel(Request $request)
    {
        $month = $request->input('month', 8);
        $year  = $request->input('year', 2026);
        $filename = "Laporan_RL_3.4_RSPAD_{$month}_{$year}.xlsx";

        \App\Services\ExcelReportExporter::exportFullOutput($month, $year, $filename);
    }
}
