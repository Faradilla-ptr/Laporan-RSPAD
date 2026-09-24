<?php

namespace App\Http\Controllers;

use App\Models\ImportLog;
use App\Models\RawVisit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ImportController extends Controller
{
    public function index()
    {
        $importLogs = ImportLog::with('user')->latest()->paginate(10);
        return view('imports.index', compact('importLogs'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'excel_file' => 'required|file|mimes:xls,xlsx,csv|max:20480',
            'period_month' => 'required|integer|between:1,12',
            'period_year'  => 'required|integer|min:2020|max:2030',
        ]);

        $file = $request->file('excel_file');
        $fileName = time() . '_' . $file->getClientOriginalName();
        $filePath = $file->storeAs('imports', $fileName, 'local');
        $fullPath = storage_path('app/' . $filePath);

        try {
            $spreadsheet = IOFactory::load($fullPath);
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
            // Detect header row or start from row 2 / row 13
            $startRow = 2;
            for ($r = 1; $r <= 20; $r++) {
                $cellA = strtoupper(trim((string)$sheet->getCell("A{$r}")->getValue()));
                $cellB = strtoupper(trim((string)$sheet->getCell("B{$r}")->getValue()));
                if ($cellA === 'NO' || $cellB === 'NO RM' || str_contains($cellB, 'RM')) {
                    $startRow = $r + 1;
                    break;
                }
            }

            for ($row = $startRow; $row <= $highestRow; $row++) {
                $noRm = trim((string)$sheet->getCell("B{$row}")->getValue());
                if (empty($noRm) || strtolower($noRm) === 'no rm' || strtolower($noRm) === 'total') {
                    continue;
                }

                $namaPasien = trim((string)$sheet->getCell("C{$row}")->getValue());
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

                $tglBerobat = sprintf('%04d-%02d-01', $request->period_year, $request->period_month);
                if (!empty($tglBerobatRaw)) {
                    $ts = strtotime($tglBerobatRaw);
                    if ($ts) {
                        $tglBerobat = date('Y-m-d', $ts);
                    }
                }

                $kelompokRaw = trim((string)$sheet->getCell("Q{$row}")->getValue());
                if (empty($kelompokRaw)) {
                    $p   = strtoupper($jenisPenjamin);
                    $pa  = strtoupper($pangkat);
                    $ins = strtoupper($instansi);
                    $kat = strtoupper($kategori);
                    $kes = strtoupper($kesatuan);

                    if (str_contains($p, 'DINAS') || str_contains($p, 'ASABRI') || $ins === 'TNI' || str_contains($kes, 'KODAM') || str_contains($kes, 'KOREM') || str_contains($kes, 'KODIM') || str_contains($kes, 'YON')) {
                        if (str_contains($kat, 'ISTRI') || str_contains($kat, 'ANAK') || str_contains($kat, 'SUAMI') || str_contains($kat, 'KELUARGA')) {
                            $kelompokRaw = 'KELUARGA MILITER';
                        } elseif (str_contains($pa, 'PNS') || str_contains($ins, 'PNS') || str_contains($pa, 'III/') || str_contains($pa, 'IV/')) {
                            $kelompokRaw = 'PNS KEMHAN/TNI';
                        } else {
                            $kelompokRaw = 'MILITER TNI AD';
                        }
                    } elseif (str_contains($p, 'KEMENTRIAN') || str_contains($p, 'KEMENTERIAN') || $ins === 'KEMENTERIAN') {
                        if (str_contains($kat, 'ISTRI') || str_contains($kat, 'ANAK') || str_contains($kat, 'SUAMI')) {
                            $kelompokRaw = 'KELUARGA PNS';
                        } else {
                            $kelompokRaw = 'PNS KEMHAN/TNI';
                        }
                    } elseif (str_contains($p, 'PURNAWIRAWAN') || str_contains($pa, 'PENSIUN') || str_contains($kat, 'PURNAWIRAWAN')) {
                        $kelompokRaw = 'PURNAWIRAWAN';
                    } elseif (str_contains($p, 'PBI')) {
                        $kelompokRaw = 'BPJS PBI';
                    } elseif (str_contains($p, 'MANDIRI') || str_contains($p, 'PEGAWAI') || str_contains($p, 'SWASTA') || str_contains($p, 'KETENAGAKERJAAN')) {
                        $kelompokRaw = 'BPJS MANDIRI / SWASTA';
                    } elseif (str_contains($p, 'TUNAI') || str_contains($p, 'UMUM')) {
                        $kelompokRaw = 'UMUM / TUNAI';
                    } else {
                        $kelompokRaw = !empty($p) ? $p : 'ASURANSI / LAIN-LAIN';
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
                ->with('success', "File Excel '{$file->getClientOriginalName()}' berhasil diimport! Total {$count} data kunjungan berhasil diproses.");

        } catch (\Exception $e) {
            return back()->withErrors(['excel_file' => 'Gagal membaca file Excel: ' . $e->getMessage()]);
        }
    }
}
