<?php

namespace App\Http\Controllers;

use App\Models\ImportLog;
use App\Models\RawVisit;
use App\Services\ActivityLogger;
use App\Services\ExcelReportExporter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Csv;
use PhpOffice\PhpSpreadsheet\Reader\Html;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Controller untuk mengelola proses Import Berkas Spreadsheet Kunjungan Pasien (Excel / CSV).
 * Menangani parsing struktur header dinamis, sanitasi data, inferensi poli & kelompok pasien,
 * penyimpanan bertahap (chunking), serta audit log riwayat import.
 */
class ImportController extends Controller
{
    /**
     * Menampilkan halaman riwayat daftar import berkas beserta paginasi.
     *
     * @return View
     */
    public function index()
    {
        // Catat riwayat akses halaman import ke audit log
        ActivityLogger::log('VIEW_IMPORTS', 'Melihat halaman Riwayat Import Berkas Excel.');

        // Ambil riwayat berkas import dengan relasi user yang mengunggah, diurutkan dari terbaru (10 per halaman)
        $importLogs = ImportLog::with('user')->latest()->paginate(10);

        return view('imports.index', compact('importLogs'));
    }

    /**
     * Memproses unggahan berkas Excel/CSV dan mengekstrak data kunjungan ke database.
     *
     * @return RedirectResponse
     */
    public function store(Request $request)
    {
        // Tingkatkan alokasi memori dan batas waktu eksekusi untuk menangani berkas Excel berukuran besar
        ini_set('memory_limit', '2048M');
        set_time_limit(600);

        // 1. Validasi berkas spreadsheet yang diunggah
        $allowedExtensions = ['xls', 'xlsx', 'xlsb', 'xlsm', 'xltx', 'xltm', 'csv', 'tsv', 'txt', 'ods', 'slk', 'xml'];

        $request->validate([
            'excel_file' => 'required|file|max:30720|mimes:xls,xlsx,xlsb,xlsm,csv,txt,ods,slk,xml', // Maksimal ukuran file 30MB
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

        // Verifikasi ekstensi berkas berada dalam daftar yang diizinkan
        if (! in_array($ext, $allowedExtensions)) {
            return back()->withErrors([
                'excel_file' => "Format berkas '.{$ext}' tidak didukung. Harap unggah berkas Excel (.xlsx, .xls, .xlsb, .xlsm, .csv, .ods, .tsv, .xml).",
            ])->with('error', "Format berkas '.{$ext}' tidak didukung. Harap unggah berkas Excel (.xlsx, .xls, .csv).")->withInput();
        }

        // 2. Buat direktori penyimpanan berkas import di storage lokal jika belum ada
        Storage::disk('local')->makeDirectory('imports');

        // Sanitasi nama berkas agar bebas dari karakter berbahaya
        $safeOriginalName = preg_replace('/[^a-zA-Z0-9_\.-]/', '_', basename($file->getClientOriginalName()));
        $fileName = time().'_'.$safeOriginalName;
        // Simpan berkas fisik ke disk local
        $filePath = $file->storeAs('imports', $fileName, 'local');
        $fullPath = Storage::disk('local')->path($filePath);

        try {
            $spreadsheet = null;

            // 3. Muat berkas menggunakan PhpSpreadsheet dengan fallback reader (Excel -> HTML -> CSV Tab)
            try {
                $reader = IOFactory::createReaderForFile($fullPath);
                if (method_exists($reader, 'setReadDataOnly')) {
                    $reader->setReadDataOnly(true); // Optimalisasi: hanya baca nilai data tanpa style format
                }
                $spreadsheet = $reader->load($fullPath);
            } catch (\Exception $e1) {
                try {
                    // Beberapa berkas .xls sebenarnya adalah tabel HTML terselubung
                    $htmlReader = new Html;
                    $spreadsheet = $htmlReader->load($fullPath);
                } catch (\Exception $e2) {
                    try {
                        // Fallback jika berupa berkas berpemisah tab
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

            // 4. Pilih worksheet yang sesuai (prioritas nama sheet 'R' atau 'Lap. kunjungan pasien' dari SIMRS)
            $sheet = null;
            if ($spreadsheet->sheetNameExists('R')) {
                $sheet = $spreadsheet->getSheetByName('R');
            } elseif ($spreadsheet->sheetNameExists('Lap. kunjungan pasien')) {
                $sheet = $spreadsheet->getSheetByName('Lap. kunjungan pasien');
            } else {
                $sheet = $spreadsheet->getActiveSheet();
            }

            // 5. Analisis posisi header kolom secara dinamis
            $parsed = $this->parseSheetHeader($sheet);
            if (! $parsed || empty($parsed['col_map']['no_rm'])) {
                throw new \Exception("Kolom 'NO RM' tidak ditemukan dalam lembar kerja Excel.");
            }

            $startRow = $parsed['header_row'] + 1; // Baris awal data adalah 1 baris setelah header
            $colMap = $parsed['col_map'];
            $highestRow = $sheet->getHighestRow();

            // 6. Buat rekaman riwayat log import pada database
            $importLog = ImportLog::create([
                'filename' => $file->getClientOriginalName(),
                'user_id' => Auth::id() ?: 1,
                'period_month' => $request->period_month,
                'period_year' => $request->period_year,
                'total_rows' => 0,
            ]);

            $count = 0;
            $fileRows = [];

            // 7. Looping pembacaan data baris demi baris dari berkas Excel
            for ($row = $startRow; $row <= $highestRow; $row++) {
                $noRmCol = $colMap['no_rm'] ?? 'B';
                $noRm = trim((string) $sheet->getCell("{$noRmCol}{$row}")->getValue());

                $namaCol = $colMap['nama_pasien'] ?? 'C';
                $namaPasien = trim((string) $sheet->getCell("{$namaCol}{$row}")->getValue());

                $lowerRm = strtolower($noRm);
                $lowerNama = strtolower($namaPasien);

                // Lewati baris jika No RM kosong, baris duplikat header, atau baris kesimpulan/total
                if (empty($noRm) || $lowerRm === 'no rm' || str_contains($lowerRm, 'total') || str_contains($lowerNama, 'nama pasien') || str_contains($lowerRm, 'pasien baru') || str_contains($lowerRm, 'pasien lama')) {
                    continue;
                }

                // Closure helper untuk mengambil isi sel berdasarkan nama kolom yang terpetakan
                $getField = function ($key) use ($colMap, $sheet, $row) {
                    if (! isset($colMap[$key])) {
                        return '';
                    }

                    return trim((string) $sheet->getCell("{$colMap[$key]}{$row}")->getValue());
                };

                // Ekstrak atribut-atribut kunjungan pasien dari sel baris saat ini
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

                // 8. Format dan pastikan tanggal berobat sesuai dengan periode bulan dan tahun yang dipilih
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

                // 9. Klasifikasikan kelompok pasien secara otomatis berdasarkan kombinasi atribut
                $kelompok = $this->deriveKelompok($kelompokRaw, $jenisPenjamin, $pangkat, $instansi, $kategori, $kesatuan);

                // 10. Siapkan array record kunjungan pasien untuk disimpan
                $fileRows[] = [
                    'import_log_id' => $importLog->id,
                    'no_rm' => $noRm,
                    'nama_pasien' => 'PASIEN ANONYMIZED', // Anonimisasi nama pasien demi privasi rekam medis
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

            // 11. Masukkan data ke database secara bertahap (batch per 500 baris) agar tidak membebani memori
            foreach (array_chunk($fileRows, 500) as $chunk) {
                RawVisit::insert($chunk);
            }

            // 12. Perbarui total baris pada catatan riwayat import
            $importLog->update(['total_rows' => $count]);

            // Catat keberhasilan import ke audit log
            ActivityLogger::log('IMPORT_EXCEL', "Berhasil mengimpor berkas Excel '{$file->getClientOriginalName()}' (Periode {$request->period_month}/{$request->period_year}) sebanyak {$count} data kunjungan.");

            // Bersihkan objek spreadsheet dari memori
            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);

            $role = (Auth::check() && Auth::user()->role === 'admin') ? 'admin' : 'petugas';

            // Redirect kembali ke halaman riwayat import dengan pesan notifikasi sukses
            return redirect()->route("{$role}.imports.index")
                ->with('success', "Berkas Excel '{$file->getClientOriginalName()}' ({$ext}) berhasil diimport! Total {$count} data kunjungan berhasil diproses.");

        } catch (\Exception $e) {
            // Tangani kegagalan import dan kirim pesan kesalahan ke user
            return back()->withErrors(['excel_file' => 'Gagal membaca berkas Excel: '.$e->getMessage()])->with('error', 'Gagal membaca berkas Excel: '.$e->getMessage())->withInput();
        }
    }

    /**
     * Menghapus salah satu riwayat berkas import beserta seluruh data kunjungan yang terkait.
     *
     * @param  int|string  $id
     * @return RedirectResponse
     */
    public function destroy($id)
    {
        $importLog = ImportLog::findOrFail($id);
        $filename = $importLog->filename;

        // 1. Hapus seluruh data baris kunjungan yang terkait dengan riwayat import ini
        $deletedVisits = RawVisit::where('import_log_id', $importLog->id)->delete();

        // 2. Hapus berkas fisik yang tersimpan di disk lokal jika ada
        if ($importLog->filename) {
            Storage::disk('local')->delete('imports/'.$importLog->filename);
        }

        // 3. Bersihkan cache berkas laporan ekspor
        ExcelReportExporter::clearCache();

        // 4. Hapus catatan riwayat import dari database
        $importLog->delete();

        // Catat aktivitas penghapusan ke audit log
        ActivityLogger::log('DELETE_IMPORT', "Menghapus log impor '{$filename}' dan {$deletedVisits} data kunjungannya.");

        return back()->with('success', "Log impor '{$filename}' dan {$deletedVisits} data kunjungannya berhasil dihapus.");
    }

    /**
     * Menghapus seluruh data kunjungan dan semua riwayat berkas import (Reset Data).
     *
     * @return RedirectResponse
     */
    public function truncateAll()
    {
        // 1. Hapus seluruh data pada tabel raw_visits dan import_logs
        RawVisit::query()->delete();
        ImportLog::query()->delete();

        // 2. Bersihkan seluruh berkas di direktori storage imports
        Storage::disk('local')->deleteDirectory('imports');
        Storage::disk('local')->makeDirectory('imports');

        // 3. Bersihkan cache berkas ekspor Excel
        ExcelReportExporter::clearCache();

        // Catat aktivitas pengosongan seluruh data ke audit log
        ActivityLogger::log('TRUNCATE_IMPORTS', 'Mengosongkan seluruh data impor dan kunjungan raw_visits.');

        return back()->with('success', 'Seluruh data impor dan kunjungan berhasil dikosongkan (0 data). Anda dapat mengunggah berkas Excel baru.');
    }

    /**
     * Menormalisasi nama poliklinik berdasarkan nama berkas atau teks mentah poli dari lembar kerja.
     *
     * @param  string  $rawPoli
     * @param  string  $filename
     * @return string
     */
    private function normalizePoliName($rawPoli, $filename)
    {
        // Ambil nama file tanpa ekstensi untuk deteksi nama poli dari nama berkas export SIMRS
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

    /**
     * Memindai 30 baris pertama lembar kerja untuk menemukan letak baris header
     * dan memetakan huruf kolom (A, B, C, ...) ke atribut field database.
     *
     * @param  Worksheet  $sheet
     * @return array{header_row: int, col_map: array<string, string>}|null
     */
    private function parseSheetHeader($sheet)
    {
        $highestRow = min($sheet->getHighestRow(), 30);
        $headerRow = null;

        // Cari baris yang mengandung sel 'NO RM'
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

        // Petakan judul teks kolom pada baris header ke nama field internal aplikasi
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

    /**
     * Menentukan klasifikasi baku kelompok pasien (Militer AD, Keluarga, PNS, BPJS, Purnawirawan, Umum, dll.)
     * berdasarkan informasi penjamin, pangkat, instansi, kategori, dan kesatuan.
     *
     * @param  string  $kelompokRaw
     * @param  string  $jenisPenjamin
     * @param  string  $pangkat
     * @param  string  $instansi
     * @param  string  $kategori
     * @param  string  $kesatuan
     * @return string
     */
    private function deriveKelompok($kelompokRaw, $jenisPenjamin, $pangkat, $instansi, $kategori, $kesatuan)
    {
        $kel = trim(strtoupper($kelompokRaw));
        // Jika kolom kelompok mentah pada Excel sudah memiliki nilai yang jelas
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

        // Jika kolom kelompok kosong, turunkan klasifikasi dari kolom-kolom identitas penjamin
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

        // Kategori cadangan jika tidak ada kondisi yang terpenuhi
        return 'UMUM / TUNAI';
    }
}
