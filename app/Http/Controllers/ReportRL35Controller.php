<?php

namespace App\Http\Controllers;

use App\Models\RawVisit;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class ReportRL35Controller extends Controller
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

        $visits = $query->get();

        // Calculate working days in month (approx 22 days or days with visits)
        $workDays = max(1, $visits->pluck('tgl_berobat')->unique()->count());

        // Group by Poliklinik
        $poliData = [];
        foreach ($visits->groupBy('poliklinik') as $poliName => $items) {
            $dalamKotaL = 0;
            $dalamKotaP = 0;
            $luarKotaL  = 0;
            $luarKotaP  = 0;

            foreach ($items as $v) {
                $isDalamKota = str_contains(strtoupper($v->alamat ?? ''), 'JAKARTA') || str_contains(strtoupper($v->alamat ?? ''), 'DKI');
                $isLaki = strtoupper($v->gender ?? 'L') === 'L';

                if ($isDalamKota) {
                    if ($isLaki) $dalamKotaL++;
                    else $dalamKotaP++;
                } else {
                    if ($isLaki) $luarKotaL++;
                    else $luarKotaP++;
                }
            }

            $total = $items->count();

            $poliData[] = [
                'poliklinik' => $poliName,
                'dalam_kota_l' => $dalamKotaL,
                'dalam_kota_p' => $dalamKotaP,
                'luar_kota_l'  => $luarKotaL,
                'luar_kota_p'  => $luarKotaP,
                'total'        => $total,
            ];
        }

        // Sort by total descending
        usort($poliData, fn($a, $b) => $b['total'] <=> $a['total']);

        $totalKunjunganAll = $visits->count();
        $avgPerDay = round($totalKunjunganAll / $workDays, 1);

        return view('reports.rl35', compact(
            'month',
            'year',
            'poliData',
            'totalKunjunganAll',
            'workDays',
            'avgPerDay'
        ));
    }

    public function exportExcel(Request $request)
    {
        $month = $request->input('month', 8);
        $year  = $request->input('year', 2026);
        $filename = "Laporan_RL_3.5_RSPAD_{$month}_{$year}.xlsx";

        \App\Services\ExcelReportExporter::exportFullOutput($month, $year, $filename);
    }
}
