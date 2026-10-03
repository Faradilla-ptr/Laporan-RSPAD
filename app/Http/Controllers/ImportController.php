<?php

namespace App\Http\Controllers;

use App\Models\ImportLog;
use App\Models\RawVisit;
use App\Services\ActivityLogger;
use App\Services\ExcelReportExporter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Csv;
use PhpOffice\PhpSpreadsheet\Reader\Html;

class ImportController extends Controller
{
    public function index()
    {
        ActivityLogger::log('VIEW_IMPORTS', 'Melihat halaman Riwayat Import Berkas Excel.');

        $importLogs = ImportLog::with('user')->latest()->paginate(10);

        return view('imports.index', compact('importLogs'));
    }

    public function store(Request $request)
    {
        ini_set('memory_limit', '2048M');
        set_time_limit(600);

        // 1. Validation for Excel / Spreadsheet uploads
        $allowedExtensions = ['xls', 'xlsx', 'xlsb', 'xlsm', 'xltx', 'xltm', 'csv', 'tsv', 'txt', 'ods', 'slk', 'xml'];

        $request->validate([
            'excel_file' => 'required|file|max:30720|mimes:xls,xlsx,xlsb,xlsm,csv,txt,ods,slk,xml', // Max 30MB
            'period_month' => 'required|integer|between:1,12',
            'period_year' => 'required|integer|min:2020|max:2030',
        ], [
            'excel_file.required' => 'Berkas Excel wajib dipilih.',
            'excel_file.file' => 'Berkas yang diunggah tidak valid.',
            'excel_file.max' => 'Ukuran berkas maksimal adalah 30 MB.',
            'excel_file.mimes' => 'Format berkas tidak didukung. Harap unggah berkas spreadsheet (Excel/CSV).',
        ]);

        $file = $request->file('excel_file');
        $ext = strtolower($file->getClientOriginalExtension());

        if (! in_array($ext, $allowedExtensions)) {
            return back()->withErrors([
                'excel_file' => "Format berkas '.{$ext}' tidak didukung. Harap unggah berkas Excel (.xlsx, .xls, .xlsb, .xlsm, .csv, .ods, .tsv, .xml).",
            ])->with('error', "Format berkas '.{$ext}' tidak didukung. Harap unggah berkas Excel (.xlsx, .xls, .csv).")->withInput();
        }

        // Ensure imports directory exists on local disk
        Storage::disk('local')->makeDirectory('imports');

        $safeOriginalName = preg_replace('/[^a-zA-Z0-9_\.-]/', '_', basename($file->getClientOriginalName()));
        $fileName = time().'_'.$safeOriginalName;
        $filePath = $file->storeAs('imports', $fileName, 'local');
        $fullPath = Storage::disk('local')->path($filePath);

        try {
            $spreadsheet = null;

            try {
                $reader = IOFactory::createReaderForFile($fullPath);
                if (method_exists($reader, 'setReadDataOnly')) {
                    $reader->setReadDataOnly(true);
                }
                $spreadsheet = $reader->load($fullPath);
            } catch (\Exception $e1) {
                try {
                    $htmlReader = new Html;
                    $spreadsheet = $htmlReader->load($fullPath);
                } catch (\Exception $e2) {
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

            $importLog = ImportLog::create([
                'filename' => $file->getClientOriginalName(),
                'user_id' => Auth::id() ?: 1,
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
                $rawPoli = $getField('poliklinik');
                $poliklinik = $this->normalizePoliName($rawPoli, $file->getClientOriginalName());
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

                // Enforce requested month and year for imported visits
                $mStr = sprintf('%02d', $request->period_month);
                $yStr = $request->period_year;
                $tglBerobat = "{$yStr}-{$mStr}-01";
                if (! empty($tglBerobatRaw)) {
                    $ts = strtotime($tglBerobatRaw);
                    if ($ts) {
                        $dStr = date('Y-m-d', $ts);
                        if (str_starts_with($dStr, "{$yStr}-{$mStr}")) {
                            $tglBerobat = $dStr;
                        } else {
                            $day = date('d', $ts);
                            $tglBerobat = "{$yStr}-{$mStr}-{$day}";
                        }
                    }
                }

                $kelompok = $this->deriveKelompok($kelompokRaw, $jenisPenjamin, $pangkat, $instansi, $kategori, $kesatuan);

                $fileRows[] = [
                    'import_log_id' => $importLog->id,
                    'no_rm' => $noRm,
                    'nama_pasien' => 'PASIEN ANONYMIZED',
                    'tgl_lahir' => $tglLahir,
                    'umur' => $umur,
                    'no_telp' => $noTelp,
                    'no_hp' => $noHp,
                    'poliklinik' => $poliklinik,
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

            ActivityLogger::log('IMPORT_EXCEL', "Berhasil mengimpor berkas Excel '{$file->getClientOriginalName()}' (Periode {$request->period_month}/{$request->period_year}) sebanyak {$count} data kunjungan.");

            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);

            $role = (Auth::check() && Auth::user()->role === 'admin') ? 'admin' : 'petugas';

            return redirect()->route("{$role}.imports.index")
                ->with('success', "Berkas Excel '{$file->getClientOriginalName()}' ({$ext}) berhasil diimport! Total {$count} data kunjungan berhasil diproses.");

        } catch (\Exception $e) {
            return back()->withErrors(['excel_file' => 'Gagal membaca berkas Excel: '.$e->getMessage()])->with('error', 'Gagal membaca berkas Excel: '.$e->getMessage())->withInput();
        }
    }

    public function destroy($id)
    {
        $importLog = ImportLog::findOrFail($id);
        $filename = $importLog->filename;

        // 1. Delete associated raw visit rows
        $deletedVisits = RawVisit::where('import_log_id', $importLog->id)->delete();

        // 2. Delete physical file on disk if exists
        if ($importLog->filename) {
            Storage::disk('local')->delete('imports/'.$importLog->filename);
        }

        // 3. Clear export file cache
        ExcelReportExporter::clearCache();

        // 4. Delete the import log record
        $importLog->delete();

        ActivityLogger::log('DELETE_IMPORT', "Menghapus log impor '{$filename}' dan {$deletedVisits} data kunjungannya.");

        return back()->with('success', "Log impor '{$filename}' dan {$deletedVisits} data kunjungannya berhasil dihapus.");
    }

    public function truncateAll()
    {
        // 1. Delete all raw visits and import logs
        RawVisit::query()->delete();
        ImportLog::query()->delete();

        // 2. Clean storage imports directory
        Storage::disk('local')->deleteDirectory('imports');
        Storage::disk('local')->makeDirectory('imports');

        // 3. Clear export file cache
        ExcelReportExporter::clearCache();

        ActivityLogger::log('TRUNCATE_IMPORTS', 'Mengosongkan seluruh data impor dan kunjungan raw_visits.');

        return back()->with('success', 'Seluruh data impor dan kunjungan berhasil dikosongkan (0 data). Anda dapat mengunggah berkas Excel baru.');
    }

    private function normalizePoliName($rawPoli, $filename)
    {
        $fn = strtoupper(pathinfo($filename, PATHINFO_FILENAME));

        if (str_contains($fn, 'ANAK')) {
            return 'BEDAH ANAK';
        }
        if (str_contains($fn, 'DIGEST')) {
            return 'BEDAH DIGESTIF';
        }
        if (str_contains($fn, 'ORTO')) {
            return 'BEDAH ORTOPEDI';
        }
        if (str_contains($fn, 'PLASTIK')) {
            return 'BEDAH PLASTIK';
        }
        if (str_contains($fn, 'THORA')) {
            return 'BEDAH THORAKS';
        }
        if (str_contains($fn, 'TUMOR')) {
            return 'BEDAH TUMOR';
        }
        if (str_contains($fn, 'URO')) {
            return 'BEDAH UROLOGI';
        }
        if (str_contains($fn, 'VASKULER')) {
            return 'BEDAH VASKULER';
        }
        if ($fn === 'B. SARAF' || str_starts_with($fn, 'B. SARAF') || str_contains($fn, 'BEDAH SARAF')) {
            return 'BEDAH SARAF';
        }
        if (str_contains($fn, 'SARAF')) {
            return 'SARAF';
        }
        if (str_contains($fn, 'JANTUNG')) {
            return 'JANTUNG';
        }
        if (str_contains($fn, 'MATA')) {
            return 'MATA';
        }
        if (str_contains($fn, 'OBGIN')) {
            return 'OBGIN';
        }
        if (str_contains($fn, 'PARU')) {
            return 'PARU';
        }
        if (str_contains($fn, 'GIGI')) {
            return 'GIGI & MULUT';
        }
        if ($fn === 'PD' || str_contains($fn, 'PD ') || str_contains($fn, 'PENYAKIT DALAM')) {
            return 'PENYAKIT DALAM';
        }

        $cleanRaw = trim(strtoupper($rawPoli));
        if (! empty($cleanRaw)) {
            if (str_starts_with($cleanRaw, 'PENYAKIT DALAM')) {
                return 'PENYAKIT DALAM';
            }

            return $cleanRaw;
        }

        return $fn;
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
            if (in_array($kel, [
                'AD', 'AL', 'AU', 'KEL AD', 'KEL AL', 'KEL AU',
                'PNS AD', 'PNS AL', 'PNS AU', 'MILITER TNI AD', 'KELUARGA MILITER',
                'PNS KEMHAN/TNI', 'PPPK DINAS', 'PPPK KEMENTRIAN', 'BPJS PBI',
                'BPJS MANDIRI / SWASTA', 'UMUM / TUNAI', 'PURNAWIRAWAN', 'POLRI',
            ])) {
                return $kel;
            }

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
