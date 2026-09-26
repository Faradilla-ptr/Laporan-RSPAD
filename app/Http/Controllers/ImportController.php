<?php

namespace App\Http\Controllers;

use App\Models\ImportLog;
use App\Models\RawVisit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Html;
use PhpOffice\PhpSpreadsheet\Reader\Csv;

class ImportController extends Controller
{
    public function index()
    {
        $importLogs = ImportLog::with('user')->latest()->paginate(10);
        return view('imports.index', compact('importLogs'));
    }

    public function store(Request $request)
    {
        // 1. Comprehensive Validation allowing ALL Excel & Spreadsheet formats
        $allowedExtensions = ['xls', 'xlsx', 'xlsb', 'xlsm', 'xltx', 'xltm', 'csv', 'tsv', 'txt', 'ods', 'slk', 'xml'];

        $request->validate([
            'excel_file'   => 'required|file|max:30720', // Max 30MB
            'period_month' => 'required|integer|between:1,12',
            'period_year'  => 'required|integer|min:2020|max:2030',
        ], [
            'excel_file.required' => 'Berkas Excel wajib dipilih.',
            'excel_file.file'     => 'Berkas yang diunggah tidak valid.',
            'excel_file.max'      => 'Ukuran berkas maksimal adalah 30 MB.',
        ]);

        $file = $request->file('excel_file');
        $ext  = strtolower($file->getClientOriginalExtension());

        // Validate extension
        if (!in_array($ext, $allowedExtensions)) {
            return back()->withErrors([
                'excel_file' => "Format berkas '.{$ext}' tidak didukung. Harap unggah berkas Excel (.xlsx, .xls, .xlsb, .xlsm, .csv, .ods, .tsv, .xml)."
            ])->withInput();
        }

        $fileName = time() . '_' . preg_replace('/[^a-zA-Z0-9_\.-]/', '_', $file->getClientOriginalName());
        $filePath = $file->storeAs('imports', $fileName, 'local');
        $fullPath = storage_path('app/' . $filePath);

        try {
            // 2. Smart Multi-Format Reader Logic
            $spreadsheet = null;

            try {
                // Try standard IOFactory auto-detection (.xlsx, .xls, .csv, .ods, .slk, .xml)
                $spreadsheet = IOFactory::load($fullPath);
            } catch (\Exception $e1) {
                // Fallback 1: Many SIMRS exports generate HTML tables saved with .xls extension
                try {
                    $htmlReader = new Html();
                    $spreadsheet = $htmlReader->load($fullPath);
                } catch (\Exception $e2) {
                    // Fallback 2: CSV / Tab-separated text format
                    try {
                        $csvReader = new Csv();
                        $csvReader->setDelimiter("\t");
                        $spreadsheet = $csvReader->load($fullPath);
                    } catch (\Exception $e3) {
                        throw new \Exception("Gagal membaca struktur berkas Excel/Spreadsheet: " . $e1->getMessage());
                    }
                }
            }

            if (!$spreadsheet) {
                throw new \Exception("Gagal memproses lembar kerja Excel.");
            }

            // Get first or active worksheet
            $sheet = $spreadsheet->getActiveSheet();
            $highestRow = $sheet->getHighestRow();

            $importLog = ImportLog::create([
                'filename' => $file->getClientOriginalName(),
                'user_id' => Auth::id(),
                'period_month' => $request->period_month,
                'period_year' => $request->period_year,
                'total_rows' => 0,
            ]);

            $count = 0;

            // Smart Header Row Detection (Scans rows 1 to 30)
            $startRow = 2;
            for ($r = 1; $r <= 30; $r++) {
                $cellA = strtoupper(trim((string)$sheet->getCell("A{$r}")->getValue()));
                $cellB = strtoupper(trim((string)$sheet->getCell("B{$r}")->getValue()));
                if ($cellA === 'NO' || $cellB === 'NO RM' || str_contains($cellB, 'RM') || str_contains($cellA, 'RM')) {
                    $startRow = $r + 1;
                    break;
                }
            }

            for ($row = $startRow; $row <= $highestRow; $row++) {
                $noRm       = trim((string)$sheet->getCell("B{$row}")->getValue());
                $namaPasien = trim((string)$sheet->getCell("C{$row}")->getValue());

                // Skip blank, header, or total rows
                if (empty($noRm) || strtolower($noRm) === 'no rm' || strtolower($noRm) === 'total' || strtolower($namaPasien) === 'nama pasien') {
                    continue;
                }

                $tglLahir   = trim((string)$sheet->getCell("D{$row}")->getValue());
                $umur       = trim((string)$sheet->getCell("E{$row}")->getValue());
                $noTelp     = trim((string)$sheet->getCell("F{$row}")->getValue());
                $noHp       = trim((string)$sheet->getCell("G{$row}")->getValue());
                $poliklinik = trim((string)$sheet->getCell("H{$row}")->getValue());
                $dokter     = trim((string)$sheet->getCell("I{$row}")->getValue());
                $tglBerobatRaw = trim((string)$sheet->getCell("J{$row}")->getValue());
                $jam        = trim((string)$sheet->getCell("K{$row}")->getValue());
                $noSep      = trim((string)$sheet->getCell("L{$row}")->getValue());
                $noBpjs     = trim((string)$sheet->getCell("M{$row}")->getValue());
                $statusPasien = trim((string)$sheet->getCell("N{$row}")->getValue());
                $jenisRawat   = trim((string)$sheet->getCell("O{$row}")->getValue());
                $jenisPenjamin= trim((string)$sheet->getCell("P{$row}")->getValue());
                $kelompokRaw  = trim((string)$sheet->getCell("Q{$row}")->getValue());
                $pangkat    = trim((string)$sheet->getCell("R{$row}")->getValue());
                $nipNrpPasien = trim((string)$sheet->getCell("S{$row}")->getValue());
                $gender     = trim((string)$sheet->getCell("T{$row}")->getValue());
                $agama      = trim((string)$sheet->getCell("U{$row}")->getValue());
                $pendidikan = trim((string)$sheet->getCell("V{$row}")->getValue());
                $kesatuan   = trim((string)$sheet->getCell("W{$row}")->getValue());
                $instansi   = trim((string)$sheet->getCell("X{$row}")->getValue());
                $kategori   = trim((string)$sheet->getCell("Y{$row}")->getValue());
                $alamat     = trim((string)$sheet->getCell("Z{$row}")->getValue());
                $icd10Utama = trim((string)$sheet->getCell("AA{$row}")->getValue());
                $deskIcdUtama = trim((string)$sheet->getCell("AB{$row}")->getValue());
                $icd10Sek    = trim((string)$sheet->getCell("AC{$row}")->getValue());
                $deskIcdSek  = trim((string)$sheet->getCell("AD{$row}")->getValue());
                $statusRegis = trim((string)$sheet->getCell("AE{$row}")->getValue());

                // Parse Date
                $tglBerobat = sprintf('%04d-%02d-01', $request->period_year, $request->period_month);
                if (!empty($tglBerobatRaw)) {
                    $ts = strtotime($tglBerobatRaw);
                    if ($ts) {
                        $tglBerobat = date('Y-m-d', $ts);
                    }
                }

                // Automatic Kelompok Categorization if empty
                if (empty($kelompokRaw)) {
                    $p   = strtoupper($jenisPenjamin);
                    $pa  = strtoupper($pangkat);
                    $ins = strtoupper($instansi);
                    $kat = strtoupper($kategori);
                    $kes = strtoupper($kesatuan);

                    if (str_contains($p, 'PBI')) {
                        $kelompokRaw = 'BPJS PBI';
                    } elseif (str_contains($p, 'MANDIRI') || str_contains($p, 'SWASTA')) {
                        $kelompokRaw = 'BPJS MANDIRI / SWASTA';
                    } elseif (str_contains($p, 'MILITER') || str_contains($p, 'DINAS') || str_contains($kat, 'MILITER') || !empty($pa)) {
                        if (str_contains($kat, 'KELUARGA') || str_contains($p, 'KELUARGA')) {
                            $kelompokRaw = 'KELUARGA MILITER';
                        } else {
                            $kelompokRaw = 'MILITER TNI AD';
                        }
                    } elseif (str_contains($p, 'PNS') || str_contains($kat, 'PNS') || str_contains($ins, 'KEMHAN') || str_contains($ins, 'TNI')) {
                        if (str_contains($kat, 'KELUARGA') || str_contains($p, 'KELUARGA')) {
                            $kelompokRaw = 'KELUARGA PNS';
                        } else {
                            $kelompokRaw = 'PNS KEMHAN/TNI';
                        }
                    } elseif (str_contains($p, 'PURNA') || str_contains($kat, 'PURNA')) {
                        $kelompokRaw = 'PURNAWIRAWAN';
                    } else {
                        $kelompokRaw = 'UMUM / TUNAI';
                    }
                }

                RawVisit::create([
                    'import_log_id' => $importLog->id,
                    'no_rm' => $noRm,
                    'nama_pasien' => $namaPasien ?: 'PASIEN UNKNOWN',
                    'tgl_lahir' => $tglLahir,
                    'umur' => $umur,
                    'no_telp' => $noTelp,
                    'no_hp' => $noHp,
                    'poliklinik' => $poliklinik ?: 'PENYAKIT DALAM',
                    'dokter' => $dokter,
                    'tgl_berobat' => $tglBerobat,
                    'jam' => $jam,
                    'no_sep' => $noSep,
                    'no_bpjs' => $noBpjs,
                    'status_pasien' => $statusPasien ?: 'Pasien Lama',
                    'jenis_rawat' => $jenisRawat ?: 'WATLAN',
                    'jenis_penjamin' => $jenisPenjamin ?: 'BPJS DINAS',
                    'kelompok' => $kelompokRaw,
                    'pangkat' => $pangkat,
                    'nip_nrp_pasien' => $nipNrpPasien,
                    'gender' => $gender ?: 'L',
                    'agama' => $agama,
                    'pendidikan' => $pendidikan,
                    'kesatuan' => $kesatuan,
                    'instansi' => $instansi,
                    'kategori' => $kategori,
                    'alamat' => $alamat,
                    'icd10_utama' => $icd10Utama,
                    'deskripsi_icd10_utama' => $deskIcdUtama,
                    'icd10_sekunder' => $icd10Sek,
                    'deskripsi_icd10_sekunder' => $deskIcdSek,
                    'status_registrasi' => $statusRegis ?: 'open',
                ]);
                $count++;
            }

            $importLog->update(['total_rows' => $count]);

            return redirect()->route('imports.index')
                ->with('success', "File Excel '{$file->getClientOriginalName()}' ({$ext}) berhasil diimport! Total {$count} data kunjungan berhasil diproses.");

        } catch (\Exception $e) {
            return back()->withErrors(['excel_file' => 'Gagal membaca berkas Excel: ' . $e->getMessage()])->withInput();
        }
    }
}
