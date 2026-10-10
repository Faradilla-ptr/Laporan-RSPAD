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
        $kelompok = strtoupper(trim((string) ($this->kelompok ?? '')));
        $penjamin = strtoupper(trim((string) ($this->jenis_penjamin ?? '')));
        $kesatuan = strtoupper(trim((string) ($this->kesatuan ?? '')));
        $instansi = strtoupper(trim((string) ($this->instansi ?? '')));
        $pangkat = strtoupper(trim((string) ($this->pangkat ?? '')));
        $kategori = strtoupper(trim((string) ($this->kategori ?? '')));

        // 1. JKN AKTIF
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
        if ($kelompok === 'PPPK DINAS') {
            return 'PPPK DINAS';
        }
        if ($kelompok === 'PNS KEMHAN/TNI') {
            if (str_contains($kesatuan, 'AL') || str_contains($instansi, 'AL')) {
                return 'PNS AL';
            }
            if (str_contains($kesatuan, 'AU') || str_contains($instansi, 'AU')) {
                return 'PNS AU';
            }

            return 'PNS AD';
        }
        if ($kelompok === 'KELUARGA PNS') {
            if (str_contains($kesatuan, 'AL') || str_contains($instansi, 'AL')) {
                return 'KEL AL';
            }
            if (str_contains($kesatuan, 'AU') || str_contains($instansi, 'AU')) {
                return 'KEL AU';
            }

            return 'KEL AD';
        }

        // Fallback for JKN DINAS AD
        if (str_contains($penjamin, 'BPJS DINAS') || $instansi === 'TNI') {
            if (str_contains($kategori, 'ANAK') || str_contains($kategori, 'ISTRI') || str_contains($kategori, 'SUAMI') || str_contains($kategori, 'KELUARGA')) {
                return 'KEL AD';
            }

            return 'TNI AD';
        }

        // 2. JKN POLRI
        if ($kelompok === 'POLRI' || str_contains($kelompok, 'POLRI') || $penjamin === 'BPJS POLRI' || str_contains($instansi, 'POLRI')) {
            if (str_contains($kelompok, 'PNS') || str_contains($pangkat, 'PNS')) {
                return 'PNS POLRI';
            }
            if (str_contains($kelompok, 'KEL') || str_contains($kategori, 'KELUARGA')) {
                return 'KEL POLRI';
            }

            return 'POLRI';
        }
        if ($kelompok === 'KEL POLRI') {
            return 'KEL POLRI';
        }
        if ($kelompok === 'PNS POLRI') {
            return 'PNS POLRI';
        }

        // 3. JKN PURNAWIRAWAN
        if ($kelompok === 'PURNAWIRAWAN' || str_contains($kelompok, 'PURNAWIRAWAN') || $penjamin === 'BPJS PURNAWIRAWAN' || $penjamin === 'ASABRI') {
            return 'JKN PURNAWIRAWAN';
        }

        // 4. JKN KEMENTERIAN & 5. PPPK KEMENTERIAN
        if ($kelompok === 'PPPK KEMENTERIAN' || $kelompok === 'PPPK KEMENTRIAN') {
            return 'PPPK KEMENTERIAN';
        }
        if ($penjamin === 'BPJS KEMENTRIAN' || str_contains($kelompok, 'KEMENTERIAN') || str_contains($kelompok, 'KEMENTRIAN')) {
            return 'JKN KEMENTERIAN';
        }

        // 6. JKN UMUM (PBI, MANDIRI, TENAGA KERJA)
        if ($kelompok === 'BPJS PBI' || $penjamin === 'BPJS PBI' || str_contains($penjamin, 'PBI')) {
            return 'PBI';
        }
        if ($penjamin === 'BPJS KETENAGAKERJAAN' || str_contains($penjamin, 'KETENAGAKERJAAN')) {
            return 'TENAGA KERJA';
        }
        if ($penjamin === 'BPJS MANDIRI' || str_contains($penjamin, 'MANDIRI')) {
            return 'MANDIRI';
        }

        // 7. SWASTA
        if ($penjamin === 'BPJS PEGAWAI SWASTA' || str_contains($kelompok, 'SWASTA')) {
            return 'SWASTA';
        }

        // 8. JAMINAN RSPAD
        if ($penjamin === 'BPJS RSPAD' || $penjamin === 'YANSUS RSPAD' || str_contains($penjamin, 'RSPAD')) {
            return 'JAMINAN RSPAD';
        }

        // 9. BAKSOS
        if (str_contains($kelompok, 'BAKSOS') || str_contains($penjamin, 'BAKSOS')) {
            return 'BAKSOS';
        }

        // 10. ASURANSI / MANDIRI Fallback
        if ($penjamin === 'BPJS MANDIRI / SWASTA' || $kelompok === 'BPJS MANDIRI / SWASTA') {
            return 'MANDIRI';
        }

        return 'ASURANSI';
    }
}
