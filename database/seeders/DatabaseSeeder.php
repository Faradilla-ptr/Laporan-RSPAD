<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\ImportLog;
use App\Models\RawVisit;
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
                'nip_nrp' => '198501012010121001',
            ]
        );

        $petugas = User::updateOrCreate(
            ['email' => 'petugas@rspad.go.id'],
            [
                'name' => 'Petugas Pelaporan (Fara)',
                'password' => Hash::make('password123'),
                'role' => 'petugas',
                'nip_nrp' => '199203152018012002',
            ]
        );

        $pimpinan = User::updateOrCreate(
            ['email' => 'pimpinan@rspad.go.id'],
            [
                'name' => 'Kepala Bagian Pelaporan',
                'password' => Hash::make('password123'),
                'role' => 'pimpinan',
                'nip_nrp' => '21960097120275',
            ]
        );

        // 2. Import Real XLS Sample Data if available
        $xlsPath = base_path('extracted/laporan-rspad/LAPORAN_KUNJUNGAN_PASIEN_1790146382.xls');
        if (file_exists($xlsPath)) {
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
                    $noRm = trim((string)$sheet->getCell("B{$row}")->getValue());
                    if (empty($noRm)) {
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

                    // Format date Y-m-d
                    $tglBerobat = '2026-08-01';
                    if (!empty($tglBerobatRaw)) {
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
                $this->command->error("Error seeding XLS data: " . $e->getMessage());
            }
        }
    }
}
