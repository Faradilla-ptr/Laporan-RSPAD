<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model Eloquent untuk tabel `raw_visits`.
 * Menyimpan seluruh data rekaman kunjungan pasien rawat jalan hasil import berkas SIMRS RSPAD.
 * Berisi logika bisnis untuk klasifikasi wilayah (Dalam Kota vs Luar Kota) dan klasifikasi dinas militer Puskesad.
 */
class RawVisit extends Model
{
    /**
     * Atribut-atribut tabel yang dapat diisi secara mass-assignment (Mass Assignable).
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'import_log_id',
        'no_rm',
        'nama_pasien',
        'tgl_lahir',
        'umur',
        'no_telp',
        'no_hp',
        'poliklinik',
        'dokter',
        'tgl_berobat',
        'jam',
        'no_sep',
        'no_bpjs',
        'status_pasien',
        'jenis_rawat',
        'jenis_penjamin',
        'kelompok',
        'pangkat',
        'nip_nrp_pasien',
        'gender',
        'agama',
        'pendidikan',
        'kesatuan',
        'instansi',
        'kategori',
        'alamat',
        'icd10_utama',
        'deskripsi_icd10_utama',
        'icd10_sekunder',
        'deskripsi_icd10_sekunder',
        'status_registrasi',
    ];

    /**
     * Relasi ke model ImportLog (setiap data kunjungan berasal dari satu proses import).
     */
    public function importLog(): BelongsTo
    {
        return $this->belongsTo(ImportLog::class);
    }

    /**
     * Menghasilkan string klausa kondisi SQL untuk menyaring pasien "Dalam Kota" (Wilayah Jakarta Pusat).
     * Berdasarkan kaidah pelaporan RSPAD:
     * - Dalam Kota: Pasien yang beralamat di wilayah Jakarta Pusat (Gambir, Tanah Abang, Menteng, Senen, Cempaka Putih, Johar Baru, Kemayoran, Sawah Besar).
     * - Luar Kota: Pasien dari wilayah Jakarta lainnya (Jaksel, Jaktim, Jakbar, Jakut, Kep. Seribu) dan luar DKI Jakarta.
     *
     * @param  string  $column  Nama kolom alamat (default: 'alamat')
     * @return string String kondisi SQL untuk query builder
     */
    public static function getDalamKotaSqlCondition(string $column = 'alamat'): string
    {
        $col = "LOWER(COALESCE({$column}, ''))";

        // Kata kunci kecamatan dan sebutan untuk Jakarta Pusat
        $pusatKeywords = [
            '%jakarta pusat%', '%jak-pus%', '%jakpus%', '%jak pus%', '%jakarta pst%', '%jak pst%',
            '%gambir%', '%tanah abang%', '%menteng%', '%senen%', '%cempaka putih%', '%johar baru%',
            '%kemayoran%', '%sawah besar%',
        ];

        // Kata kunci wilayah Jakarta lainnya yang harus dikecualikan dari kategori Jakarta Pusat
        $otherKeywords = [
            '%jakarta selatan%', '%jak-sel%', '%jaksel%', '%jak sel%',
            '%jakarta timur%', '%jak-tim%', '%jaktim%', '%jak tim%',
            '%jakarta barat%', '%jak-bar%', '%jakbar%', '%jak bar%',
            '%jakarta utara%', '%jak-ut%', '%jakut%', '%jak ut%',
            '%kepulauan seribu%', '%p. seribu%', '%pulau seribu%',
        ];

        // Rangkai query LIKE untuk Jakarta Pusat dan NOT LIKE untuk wilayah Jakarta lainnya
        $pusatLikes = implode(' OR ', array_map(fn ($k) => "{$col} LIKE '{$k}'", $pusatKeywords));
        $otherLikes = implode(' OR ', array_map(fn ($k) => "{$col} LIKE '{$k}'", $otherKeywords));

        return "(({$pusatLikes}) AND NOT ({$otherLikes}))";
    }

    /**
     * Accessor: Menentukan apakah alamat pasien terkategori "Dalam Kota" (Jakarta Pusat) atau "Luar Kota".
     * Dapat diakses langsung melalui atribut `$rawVisit->domisili`.
     *
     * @return string 'Dalam Kota' | 'Luar Kota'
     */
    public function getDomisiliAttribute(): string
    {
        $a = strtoupper(trim((string) ($this->alamat ?? '')));

        if (empty($a)) {
            return 'Luar Kota';
        }

        // Cek jika alamat mengandung wilayah Jakarta selain Jakarta Pusat
        $isOtherJakarta = (
            str_contains($a, 'JAKARTA SELATAN') || str_contains($a, 'JAK-SEL') || str_contains($a, 'JAKSEL') || str_contains($a, 'JAK SEL') || str_contains($a, 'JAKARTA STN') ||
            str_contains($a, 'JAKARTA TIMUR') || str_contains($a, 'JAK-TIM') || str_contains($a, 'JAKTIM') || str_contains($a, 'JAK TIM') || str_contains($a, 'JAKARTA TMR') ||
            str_contains($a, 'JAKARTA BARAT') || str_contains($a, 'JAK-BAR') || str_contains($a, 'JAKBAR') || str_contains($a, 'JAK BAR') || str_contains($a, 'JAKARTA BRT') ||
            str_contains($a, 'JAKARTA UTARA') || str_contains($a, 'JAK-UT') || str_contains($a, 'JAKUT') || str_contains($a, 'JAK UT') || str_contains($a, 'JAKARTA UTR') ||
            str_contains($a, 'KEPULAUAN SERIBU') || str_contains($a, 'P. SERIBU') || str_contains($a, 'PULAU SERIBU')
        );

        if ($isOtherJakarta) {
            return 'Luar Kota';
        }

        // Cek jika alamat cocok dengan kata kunci Jakarta Pusat
        $isJakartaPusat = (
            str_contains($a, 'JAKARTA PUSAT') ||
            str_contains($a, 'JAK-PUS') ||
            str_contains($a, 'JAKPUS') ||
            str_contains($a, 'JAK PUS') ||
            str_contains($a, 'JAKARTA PST') ||
            str_contains($a, 'JAK PST') ||
            str_contains($a, 'GAMBIR') ||
            str_contains($a, 'TANAH ABANG') ||
            str_contains($a, 'MENTENG') ||
            str_contains($a, 'SENEN') ||
            str_contains($a, 'CEMPAKA PUTIH') ||
            str_contains($a, 'JOHAR BARU') ||
            str_contains($a, 'KEMAYORAN') ||
            str_contains($a, 'SAWAH BESAR')
        );

        return $isJakartaPusat ? 'Dalam Kota' : 'Luar Kota';
    }

    /**
     * Accessor: Mengklasifikasikan rekaman kunjungan ke salah satu dari status baku Laporan Dinas Puskesad.
     * Dapat diakses langsung melalui atribut `$rawVisit->status_puskesad`.
     */
    public function getStatusPuskesadAttribute(): string
    {
        $penjamin = strtoupper(trim((string) ($this->jenis_penjamin ?? '')));
        $kelompok = strtoupper(trim((string) ($this->kelompok ?? '')));
        $kesatuan = strtoupper(trim((string) ($this->kesatuan ?? '')));
        $instansi = strtoupper(trim((string) ($this->instansi ?? '')));
        $pangkat = strtoupper(trim((string) ($this->pangkat ?? '')));
        $kategori = strtoupper(trim((string) ($this->kategori ?? '')));

        // 1. KHUSUS ASABRI DIMASUKKAN KE BPJS DINAS KELOMPOK AD
        if ($penjamin === 'ASABRI' || str_contains($penjamin, 'ASABRI')) {
            return 'TNI AD';
        }

        // 2. CEK UTAMA PERTAMA KALI: BPJS DINAS
        if ($penjamin === 'BPJS DINAS' || str_starts_with($penjamin, 'BPJS DINAS') || str_contains($penjamin, 'DINAS')) {
            // Cek kolom KELOMPOK: 'AD' 'AL' 'AU' 'PNS AD' 'PNS AL' 'PNS AU' 'KEL AD' 'KEL AU' 'KEL AL'
            if ($kelompok === 'AD' || $kelompok === 'MILITER TNI AD' || str_contains($kelompok, 'TNI AD')) {
                return 'TNI AD';
            }
            if ($kelompok === 'PNS AD') {
                return 'PNS AD';
            }
            if ($kelompok === 'KEL AD' || $kelompok === 'KELUARGA MILITER') {
                return 'KEL AD';
            }
            if ($kelompok === 'AL' || str_contains($kelompok, 'TNI AL')) {
                return 'TNI AL';
            }
            if ($kelompok === 'PNS AL') {
                return 'PNS AL';
            }
            if ($kelompok === 'KEL AL') {
                return 'KEL AL';
            }
            if ($kelompok === 'AU' || str_contains($kelompok, 'TNI AU')) {
                return 'TNI AU';
            }
            if ($kelompok === 'PNS AU') {
                return 'PNS AU';
            }
            if ($kelompok === 'KEL AU') {
                return 'KEL AU';
            }

            // Fallback PNS di BPJS DINAS
            if (str_contains($kelompok, 'PNS') || str_contains($pangkat, 'PNS')) {
                if (str_contains($kesatuan, 'AL') || str_contains($instansi, 'AL')) {
                    return 'PNS AL';
                }
                if (str_contains($kesatuan, 'AU') || str_contains($instansi, 'AU')) {
                    return 'PNS AU';
                }

                return 'PNS AD';
            }

            // Fallback Keluarga di BPJS DINAS
            if (str_contains($kelompok, 'KEL') || str_contains($kategori, 'KELUARGA') || str_contains($kategori, 'ISTRI') || str_contains($kategori, 'ANAK') || str_contains($kategori, 'SUAMI')) {
                if (str_contains($kesatuan, 'AL') || str_contains($instansi, 'AL')) {
                    return 'KEL AL';
                }
                if (str_contains($kesatuan, 'AU') || str_contains($instansi, 'AU')) {
                    return 'KEL AU';
                }

                return 'KEL AD';
            }

            // Fallback Matra AL / AU
            if (str_contains($kesatuan, 'AL') || str_contains($instansi, 'AL')) {
                return 'TNI AL';
            }
            if (str_contains($kesatuan, 'AU') || str_contains($instansi, 'AU')) {
                return 'TNI AU';
            }

            return 'TNI AD';
        }

        // 3. BPJS KEMENTERIAN (Cek kolom KELOMPOK apakah masuk 'PPPK DINAS' atau 'PPPK KEMENTERIAN')
        if (str_contains($penjamin, 'KEMENTERIAN') || str_contains($penjamin, 'KEMENTRIAN')) {
            if ($kelompok === 'PPPK DINAS' || str_contains($kelompok, 'PPPK DINAS') || str_contains($pangkat, 'PPPK DINAS')) {
                return 'PPPK DINAS';
            }
            if ($kelompok === 'PPPK KEMENTERIAN' || $kelompok === 'PPPK KEMENTRIAN' || str_contains($kelompok, 'PPPK') || str_contains($pangkat, 'PPPK')) {
                return 'PPPK KEMENTERIAN';
            }

            return 'JKN KEMENTERIAN';
        }

        // 4. BPJS POLRI (Cek kolom KELOMPOK masuk PNS POLRI atau KEL POLRI)
        if (str_contains($penjamin, 'POLRI') || $kelompok === 'POLRI') {
            if ($kelompok === 'PNS POLRI' || str_contains($kelompok, 'PNS') || str_contains($pangkat, 'PNS')) {
                return 'PNS POLRI';
            }
            if ($kelompok === 'KEL POLRI' || str_contains($kelompok, 'KEL') || str_contains($kategori, 'KELUARGA') || str_contains($kategori, 'ISTRI') || str_contains($kategori, 'ANAK') || str_contains($kategori, 'SUAMI')) {
                return 'KEL POLRI';
            }

            return 'POLRI';
        }

        // 5. JENIS BPJS YANG LAIN SESUAIKAN DENGAN KOLOM 'JENIS PEMBAYARAN'
        if (str_contains($penjamin, 'PURNAWIRAWAN')) {
            return 'JKN PURNAWIRAWAN';
        }
        if (str_contains($penjamin, 'PBI')) {
            return 'PBI';
        }
        if (str_contains($penjamin, 'KETENAGAKERJAAN') || str_contains($penjamin, 'TENAGA KERJA')) {
            return 'TENAGA KERJA';
        }
        if (str_contains($penjamin, 'MANDIRI')) {
            return 'MANDIRI';
        }
        if (str_contains($penjamin, 'PEGAWAI SWASTA') || str_contains($penjamin, 'SWASTA')) {
            return 'SWASTA';
        }
        if (str_contains($penjamin, 'RSPAD') || str_contains($penjamin, 'YANSUS')) {
            return 'JAMINAN RSPAD';
        }
        if (str_contains($penjamin, 'BAKSOS')) {
            return 'BAKSOS';
        }

        // Fallback untuk baris di mana jenis penjamin kosong namun kolom kelompok memiliki data
        if ($penjamin === '' || $penjamin === '--') {
            if (str_contains($kelompok, 'PURNA')) {
                return 'JKN PURNAWIRAWAN';
            }
            if (str_contains($kelompok, 'PBI')) {
                return 'PBI';
            }
            if (str_contains($kelompok, 'SWASTA')) {
                return 'SWASTA';
            }
            if (str_contains($kelompok, 'MANDIRI')) {
                return 'MANDIRI';
            }
        }

        // Default untuk Asuransi, Taspen, Umum, Tunai, dan jaminan komersial lainnya
        return 'ASURANSI';
    }
}
