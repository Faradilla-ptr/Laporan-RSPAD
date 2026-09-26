<?php

namespace App\Http\Controllers;

use App\Models\ImportLog;
use App\Models\RawVisit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Csv;
use PhpOffice\PhpSpreadsheet\Reader\Html;

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
            'excel_file' => 'required|file|max:30720', // Max 30MB
            'period_month' => 'required|integer|between:1,12',
            'period_year' => 'required|integer|min:2020|max:2030',
        ], [
            'excel_file.required' => 'Berkas Excel wajib dipilih.',
            'excel_file.file' => 'Berkas yang diunggah tidak valid.',
            'excel_file.max' => 'Ukuran berkas maksimal adalah 30 MB.',
        ]);

        $file = $request->file('excel_file');
        $ext = strtolower($file->getClientOriginalExtension());

        // Validate extension
        if (! in_array($ext, $allowedExtensions)) {
            return back()->withErrors([
                'excel_file' => "Format berkas '.{$ext}' tidak didukung. Harap unggah berkas Excel (.xlsx, .xls, .xlsb, .xlsm, .csv, .ods, .tsv, .xml).",
            ])->withInput();
        }

        $fileName = time().'_'.preg_replace('/[^a-zA-Z0-9_\.-]/', '_', $file->getClientOriginalName());
        $filePath = $file->storeAs('imports', $fileName, 'local');
        $fullPath = storage_path('app/'.$filePath);

        try {
            // 2. Smart Multi-Format Reader Logic
            $spreadsheet = null;

            try {
                // Try standard IOFactory auto-detection (.xlsx, .xls, .csv, .ods, .slk, .xml)
                $spreadsheet = IOFactory::load($fullPath);
            } catch (\Exception $e1) {
                // Fallback 1: Many SIMRS exports generate HTML tables saved with .xls extension
                try {
                    $htmlReader = new Html;
                    $spreadsheet = $htmlReader->load($fullPath);
                } catch (\Exception $e2) {
                    // Fallback 2: CSV / Tab-separated text format
                    try {
                        $csvReader = new Csv;
                        $csvReader->setDelimiter("\t");
                        $spreadsheet = $csvReader->load($fullPath);
                    } catch (\Exception $e3) {
                        throw new \Exception('Gagal membaca struktur berkas Excel/Spreadsheet: '.$e1->getMessage());
                    }
                }
            }

            if (! $spreadsheet) {
                throw new \Exception('Gagal memproses lembar kerja Excel.');
            }

            // Prefer 'R', 'Lap. kunjungan pasien', or active sheet
            $sheet = null;
            if ($spreadsheet->sheetNameExists('R')) {
                $sheet = $spreadsheet->getSheetByName('R');
            } elseif ($spreadsheet->sheetNameExists('Lap. kunjungan pasien')) {
                $sheet = $spreadsheet->getSheetByName('Lap. kunjungan pasien');
            } else {
                $sheet = $spreadsheet->getActiveSheet();
            }

            $parsed = $this->parseSheetHeader($sheet);
            if (! $parsed || empty($parsed['col_map']['no_rm'])) {
                throw new \Exception("Kolom 'NO RM' tidak ditemukan dalam lembar kerja Excel.");
            }

            $startRow = $parsed['header_row'] + 1;
            $colMap = $parsed['col_map'];
            $highestRow = $sheet->getHighestRow();

            $defaultPoli = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);

            $importLog = ImportLog::create([
                'filename' => $file->getClientOriginalName(),
                'user_id' => Auth::id(),
                'period_month' => $request->period_month,
                'period_year' => $request->period_year,
                'total_rows' => 0,
            ]);

            $count = 0;
            $fileRows = [];

            for ($row = $startRow; $row <= $highestRow; $row++) {
                $noRmCol = $colMap['no_rm'] ?? 'B';
                $noRm = trim((string) $sheet->getCell("{$noRmCol}{$row}")->getValue());

                $namaCol = $colMap['nama_pasien'] ?? 'C';
                $namaPasien = trim((string) $sheet->getCell("{$namaCol}{$row}")->getValue());

                $lowerRm = strtolower($noRm);
                $lowerNama = strtolower($namaPasien);

                if (empty($noRm) || $lowerRm === 'no rm' || str_contains($lowerRm, 'total') || str_contains($lowerNama, 'nama pasien') || str_contains($lowerRm, 'pasien baru') || str_contains($lowerRm, 'pasien lama')) {
                    continue;
                }

                $getField = function ($key) use ($colMap, $sheet, $row) {
                    if (! isset($colMap[$key])) {
                        return '';
                    }

                    return trim((string) $sheet->getCell("{$colMap[$key]}{$row}")->getValue());
                };

                $tglLahir = $getField('tgl_lahir');
                $umur = $getField('umur');
                $statusPasien = $getField('status_pasien');
                $jenisPenjamin = $getField('jenis_penjamin');
                $kelompokRaw = $getField('kelompok');
                $pangkat = $getField('pangkat');
                $kesatuan = $getField('kesatuan');
                $instansi = $getField('instansi');
                $kategori = $getField('kategori');
                $noBpjs = $getField('no_bpjs');
                $noTelp = $getField('no_telp');
                $noHp = $getField('no_hp');
                $poliklinik = $getField('poliklinik') ?: $defaultPoli;
                $dokter = $getField('dokter');
                $tglBerobatRaw = $getField('tgl_berobat');
                $jam = $getField('jam');
                $noSep = $getField('no_sep');
                $jenisRawat = $getField('jenis_rawat');
                $nipNrpPasien = $getField('nip_nrp_pasien');
                $gender = $getField('gender');
                $agama = $getField('agama');
                $pendidikan = $getField('pendidikan');
                $alamat = $getField('alamat');
                $deskIcdUtama = $getField('deskripsi_icd10_utama');
                $deskIcdSek = $getField('deskripsi_icd10_sekunder');

                // Parse Date
                $tglBerobat = sprintf('%04d-%02d-01', $request->period_year, $request->period_month);
                if (! empty($tglBerobatRaw)) {
                    $ts = strtotime($tglBerobatRaw);
                    if ($ts) {
                        $tglBerobat = date('Y-m-d', $ts);
                    }
                }

                $kelompok = $this->deriveKelompok($kelompokRaw, $jenisPenjamin, $pangkat, $instansi, $kategori, $kesatuan);

                $fileRows[] = [
                    'import_log_id' => $importLog->id,
                    'no_rm' => $noRm,
                    'nama_pasien' => $namaPasien ?: 'PASIEN UNKNOWN',
                    'tgl_lahir' => $tglLahir,
                    'umur' => $umur,
                    'no_telp' => $noTelp,
                    'no_hp' => $noHp,
                    'poliklinik' => $poliklinik ?: $defaultPoli,
                    'dokter' => $dokter,
                    'tgl_berobat' => $tglBerobat,
                    'jam' => $jam,
                    'no_sep' => $noSep,
                    'no_bpjs' => $noBpjs,
                    'status_pasien' => $statusPasien ?: 'Pasien Lama',
                    'jenis_rawat' => $jenisRawat ?: 'WATLAN',
                    'jenis_penjamin' => $jenisPenjamin ?: 'BPJS DINAS',
                    'kelompok' => $kelompok,
                    'pangkat' => $pangkat,
                    'nip_nrp_pasien' => $nipNrpPasien,
                    'gender' => $gender ?: 'L',
                    'agama' => $agama,
                    'pendidikan' => $pendidikan,
                    'kesatuan' => $kesatuan,
                    'instansi' => $instansi,
                    'kategori' => $kategori,
                    'alamat' => $alamat,
                    'icd10_utama' => '',
                    'deskripsi_icd10_utama' => $deskIcdUtama,
                    'icd10_sekunder' => '',
                    'deskripsi_icd10_sekunder' => $deskIcdSek,
                    'status_registrasi' => 'open',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
                $count++;
            }

            foreach (array_chunk($fileRows, 500) as $chunk) {
                RawVisit::insert($chunk);
            }

            $importLog->update(['total_rows' => $count]);

            return redirect()->route('imports.index')
                ->with('success', "File Excel '{$file->getClientOriginalName()}' ({$ext}) berhasil diimport! Total {$count} data kunjungan berhasil diproses.");

        } catch (\Exception $e) {
            return back()->withErrors(['excel_file' => 'Gagal membaca berkas Excel: '.$e->getMessage()])->withInput();
        }
    }

    private function parseSheetHeader($sheet)
    {
        $highestRow = min($sheet->getHighestRow(), 30);
        $headerRow = null;

        for ($r = 1; $r <= $highestRow; $r++) {
            for ($c = 'A'; $c != 'AE'; $c++) {
                $val = strtoupper(trim((string) $sheet->getCell("{$c}{$r}")->getValue()));
                if ($val === 'NO RM' || $val === 'NO. RM' || str_contains($val, 'NO RM')) {
                    $headerRow = $r;
                    break 2;
                }
            }
        }

        if (! $headerRow) {
            return null;
        }

        $colMap = [];
        $highestColumn = $sheet->getHighestColumn();
        $highestColIndex = Coordinate::columnIndexFromString($highestColumn);

        for ($col = 1; $col <= $highestColIndex; $col++) {
            $colLetter = Coordinate::stringFromColumnIndex($col);
            $headerText = strtoupper(trim((string) $sheet->getCell("{$colLetter}{$headerRow}")->getValue()));

            if (empty($headerText)) {
                continue;
            }

            if (in_array($headerText, ['NO RM', 'NO. RM', 'NORM'])) {
                $colMap['no_rm'] = $colLetter;
            } elseif (in_array($headerText, ['NAMA PASIEN', 'NAMA'])) {
                $colMap['nama_pasien'] = $colLetter;
            } elseif (in_array($headerText, ['TANGGAL LAHIR', 'TGL LAHIR', 'TGL. LAHIR'])) {
                $colMap['tgl_lahir'] = $colLetter;
            } elseif (in_array($headerText, ['USIA', 'UMUR'])) {
                $colMap['umur'] = $colLetter;
            } elseif (in_array($headerText, ['TYPE PASIEN', 'STATUS PASIEN', 'PASIEN'])) {
                $colMap['status_pasien'] = $colLetter;
            } elseif (in_array($headerText, ['JENIS PEMBAYARAN', 'JENIS PENJAMIN', 'PEMBAYARAN', 'PENJAMIN'])) {
                $colMap['jenis_penjamin'] = $colLetter;
            } elseif (in_array($headerText, ['KELOMPOK'])) {
                $colMap['kelompok'] = $colLetter;
            } elseif (in_array($headerText, ['PANGKAT'])) {
                $colMap['pangkat'] = $colLetter;
            } elseif (in_array($headerText, ['KESATUAN'])) {
                $colMap['kesatuan'] = $colLetter;
            } elseif (in_array($headerText, ['ANGKATAN', 'INSTANSI'])) {
                $colMap['instansi'] = $colLetter;
            } elseif (in_array($headerText, ['HUBUNGAN KELUARGA', 'KATEGORI'])) {
                $colMap['kategori'] = $colLetter;
            } elseif (in_array($headerText, ['NO PESERTA', 'NO. PESERTA', 'NO BPJS', 'NO. BPJS'])) {
                $colMap['no_bpjs'] = $colLetter;
            } elseif (in_array($headerText, ['NO. TELP', 'NO TELP', 'TELP'])) {
                $colMap['no_telp'] = $colLetter;
            } elseif (in_array($headerText, ['NO. PONSEL', 'NO PONSEL', 'NO HP', 'NO. HP', 'HP', 'PONSEL'])) {
                $colMap['no_hp'] = $colLetter;
            } elseif (in_array($headerText, ['POLI', 'POLIKLINIK'])) {
                $colMap['poliklinik'] = $colLetter;
            } elseif (in_array($headerText, ['DOKTER'])) {
                $colMap['dokter'] = $colLetter;
            } elseif (in_array($headerText, ['TANGGAL', 'TANGGAL BEROBAT', 'TGL BEROBAT', 'TGL. BEROBAT'])) {
                $colMap['tgl_berobat'] = $colLetter;
            } elseif (in_array($headerText, ['JAM'])) {
                $colMap['jam'] = $colLetter;
            } elseif (in_array($headerText, ['NO SEP', 'NO. SEP', 'SEP'])) {
                $colMap['no_sep'] = $colLetter;
            } elseif (in_array($headerText, ['JENIS RAWAT'])) {
                $colMap['jenis_rawat'] = $colLetter;
            } elseif (in_array($headerText, ['NRP', 'NIP', 'NIP/NRP', 'NIP NRP', 'NIP/NRP PASIEN'])) {
                $colMap['nip_nrp_pasien'] = $colLetter;
            } elseif (in_array($headerText, ['KELAMIN', 'SEX', 'GENDER'])) {
                $colMap['gender'] = $colLetter;
            } elseif (in_array($headerText, ['AGAMA'])) {
                $colMap['agama'] = $colLetter;
            } elseif (in_array($headerText, ['PENDIDIKAN'])) {
                $colMap['pendidikan'] = $colLetter;
            } elseif (in_array($headerText, ['ALAMAT'])) {
                $colMap['alamat'] = $colLetter;
            } elseif (in_array($headerText, ['DIAGNOSA AWAL', 'ICD 10 UTAMA', 'DESKRIPSI ICD 10 UTAMA'])) {
                $colMap['deskripsi_icd10_utama'] = $colLetter;
            } elseif (in_array($headerText, ['DIAGNOSA AKHIR', 'ICD 10 SEKUNDER', 'DESKRIPSI ICD 10 SEKUNDER'])) {
                $colMap['deskripsi_icd10_sekunder'] = $colLetter;
            }
        }

        return ['header_row' => $headerRow, 'col_map' => $colMap];
    }

    private function deriveKelompok($kelompokRaw, $jenisPenjamin, $pangkat, $instansi, $kategori, $kesatuan)
    {
        $kel = trim(strtoupper($kelompokRaw));
        if (! empty($kel) && ! in_array($kel, ['--', 'TIDAK ADA', 'NULL'])) {
            if (str_contains($kel, 'MILITER') || str_contains($kel, 'TNI AD') || $kel === 'TNI AD') {
                return 'MILITER TNI AD';
            }
            if (str_contains($kel, 'KEL') && (str_contains($kel, 'AD') || str_contains($kel, 'MILITER'))) {
                return 'KELUARGA MILITER';
            }
            if (str_contains($kel, 'PNS') && ! str_contains($kel, 'KEL')) {
                return 'PNS KEMHAN/TNI';
            }
            if (str_contains($kel, 'PNS') && str_contains($kel, 'KEL')) {
                return 'KELUARGA PNS';
            }
            if (str_contains($kel, 'PURNA')) {
                return 'PURNAWIRAWAN';
            }
            if (str_contains($kel, 'PBI')) {
                return 'BPJS PBI';
            }
            if (str_contains($kel, 'BPJS') || str_contains($kel, 'SWASTA')) {
                return 'BPJS MANDIRI / SWASTA';
            }
            if (str_contains($kel, 'UMUM') || str_contains($kel, 'TUNAI')) {
                return 'UMUM / TUNAI';
            }

            return $kel;
        }

        $p = strtoupper($jenisPenjamin);
        $pa = strtoupper($pangkat);
        $ins = strtoupper($instansi);
        $kat = strtoupper($kategori);
        $kes = strtoupper($kesatuan);

        if (str_contains($p, 'PBI')) {
            return 'BPJS PBI';
        }
        if (str_contains($p, 'MANDIRI') || str_contains($p, 'SWASTA')) {
            return 'BPJS MANDIRI / SWASTA';
        }
        if (str_contains($p, 'PURNA') || str_contains($kat, 'PURNA') || str_contains($pa, 'PENSIUN')) {
            return 'PURNAWIRAWAN';
        }
        if (str_contains($p, 'PNS') || str_contains($kat, 'PNS') || str_contains($ins, 'KEMHAN') || str_contains($pa, 'III/') || str_contains($pa, 'IV/') || str_contains($pa, 'II/') || str_contains($pa, 'I/')) {
            if (str_contains($kat, 'KELUARGA') || str_contains($p, 'KELUARGA') || str_contains($kat, 'ISTRI') || str_contains($kat, 'ANAK') || str_contains($kat, 'SUAMI')) {
                return 'KELUARGA PNS';
            }

            return 'PNS KEMHAN/TNI';
        }
        if (str_contains($p, 'MILITER') || str_contains($p, 'DINAS') || str_contains($kat, 'MILITER') || str_contains($ins, 'TNI') || ! empty($pa)) {
            if (str_contains($kat, 'KELUARGA') || str_contains($p, 'KELUARGA') || str_contains($kat, 'ISTRI') || str_contains($kat, 'ANAK') || str_contains($kat, 'SUAMI') || str_contains($kel, 'KEL AD')) {
                return 'KELUARGA MILITER';
            }

            return 'MILITER TNI AD';
        }

        return 'UMUM / TUNAI';
    }
}
