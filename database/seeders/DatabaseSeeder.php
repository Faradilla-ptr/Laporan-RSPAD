<?php

namespace Database\Seeders;

use App\Models\ImportLog;
use App\Models\RawVisit;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use PhpOffice\PhpSpreadsheet\IOFactory;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Seed Users
        $admin = User::updateOrCreate(
            ['email' => 'admin@rspad.go.id'],
            [
                'name' => 'Admin Pelaporan RSPAD',
                'password' => Hash::make('password123'),
                'role' => 'admin',
                'is_approved' => true,
                'nip_nrp' => '198501012010121001',
            ]
        );

        $petugas = User::updateOrCreate(
            ['email' => 'petugas@rspad.go.id'],
            [
                'name' => 'Petugas Pelaporan (Fara)',
                'password' => Hash::make('password123'),
                'role' => 'petugas',
                'is_approved' => true,
                'nip_nrp' => '199203152018012002',
            ]
        );

        // 2. Import Real XLS Sample Data if available and table is empty
        $xlsPath = database_path('seeders/data/sample_laporan.xls');
        if (! file_exists($xlsPath)) {
            $xlsPath = base_path('extracted/laporan-rspad/LAPORAN_KUNJUNGAN_PASIEN_1790146382.xls');
        }

        if (file_exists($xlsPath) && RawVisit::count() === 0) {
            $this->command->info("Seeding data from real XLS: {$xlsPath}");
            try {
                $spreadsheet = IOFactory::load($xlsPath);
                $sheet = $spreadsheet->getSheet(0); // Sheet 1: Lap. kunjungan pasien
                $highestRow = $sheet->getHighestRow();

                $importLog = ImportLog::create([
                    'filename' => 'LAPORAN_KUNJUNGAN_PASIEN_1790146382.xls',
                    'user_id' => $petugas->id,
                    'period_month' => 8,
                    'period_year' => 2026,
                    'total_rows' => max(0, $highestRow - 12),
                ]);

                $count = 0;
                for ($row = 13; $row <= $highestRow; $row++) {
                    $noRm = trim((string) $sheet->getCell("B{$row}")->getValue());
                    $namaPasien = trim((string) $sheet->getCell("C{$row}")->getValue());

                    if (empty($noRm) || strtolower($noRm) === 'no rm' || strtolower($namaPasien) === 'nama pasien') {
                        continue;
                    }

                    $tglLahir = trim((string) $sheet->getCell("D{$row}")->getValue());
                    $umur = trim((string) $sheet->getCell("E{$row}")->getValue());
                    $noTelp = trim((string) $sheet->getCell("F{$row}")->getValue());
                    $noHp = trim((string) $sheet->getCell("G{$row}")->getValue());
                    $poliklinik = trim((string) $sheet->getCell("H{$row}")->getValue());
                    $dokter = trim((string) $sheet->getCell("I{$row}")->getValue());
                    $tglBerobatRaw = trim((string) $sheet->getCell("J{$row}")->getValue());
                    $jam = trim((string) $sheet->getCell("K{$row}")->getValue());
                    $noSep = trim((string) $sheet->getCell("L{$row}")->getValue());
                    $noBpjs = trim((string) $sheet->getCell("M{$row}")->getValue());
                    $statusPasien = trim((string) $sheet->getCell("N{$row}")->getValue());
                    $jenisRawat = trim((string) $sheet->getCell("O{$row}")->getValue());
                    $jenisPenjamin = trim((string) $sheet->getCell("P{$row}")->getValue());
                    $kelompokRaw = trim((string) $sheet->getCell("Q{$row}")->getValue());
                    $pangkat = trim((string) $sheet->getCell("R{$row}")->getValue());
                    $nipNrpPasien = trim((string) $sheet->getCell("S{$row}")->getValue());
                    $gender = trim((string) $sheet->getCell("T{$row}")->getValue());
                    $agama = trim((string) $sheet->getCell("U{$row}")->getValue());
                    $pendidikan = trim((string) $sheet->getCell("V{$row}")->getValue());
                    $kesatuan = trim((string) $sheet->getCell("W{$row}")->getValue());
                    $instansi = trim((string) $sheet->getCell("X{$row}")->getValue());
                    $kategori = trim((string) $sheet->getCell("Y{$row}")->getValue());
                    $alamat = trim((string) $sheet->getCell("Z{$row}")->getValue());
                    $icd10Utama = trim((string) $sheet->getCell("AA{$row}")->getValue());
                    $deskIcdUtama = trim((string) $sheet->getCell("AB{$row}")->getValue());
                    $icd10Sek = trim((string) $sheet->getCell("AC{$row}")->getValue());
                    $deskIcdSek = trim((string) $sheet->getCell("AD{$row}")->getValue());
                    $statusRegis = trim((string) $sheet->getCell("AE{$row}")->getValue());

                    // Derive kelompok if blank
                    if (empty($kelompokRaw)) {
                        $p = strtoupper($jenisPenjamin);
                        $pa = strtoupper($pangkat);
                        $ins = strtoupper($instansi);
                        $kat = strtoupper($kategori);
                        $kes = strtoupper($kesatuan);

                        if (str_contains($p, 'PBI')) {
                            $kelompokRaw = 'BPJS PBI';
                        } elseif (str_contains($p, 'MANDIRI') || str_contains($p, 'SWASTA')) {
                            $kelompokRaw = 'BPJS MANDIRI / SWASTA';
                        } elseif (str_contains($p, 'MILITER') || str_contains($p, 'DINAS') || str_contains($kat, 'MILITER') || ! empty($pa)) {
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

                    $tglBerobat = '2026-08-01';
                    if (! empty($tglBerobatRaw)) {
                        $tglBerobat = date('Y-m-d', strtotime($tglBerobatRaw));
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
                $this->command->info("Seeded {$count} visits successfully!");
            } catch (\Exception $e) {
                $this->command->error('Error seeding XLS data: '.$e->getMessage());
            }
        }
    }
}
