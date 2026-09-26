<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RawVisit extends Model
{
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

    public function importLog(): BelongsTo
    {
        return $this->belongsTo(ImportLog::class);
    }

    /**
     * Determines if patient is Dalam Kota or Luar Kota based on address.
     */
    public function getDomisiliAttribute(): string
    {
        $alamatUpper = strtoupper($this->alamat ?? '');
        if (str_contains($alamatUpper, 'JAKARTA') || str_contains($alamatUpper, 'DKI')) {
            return 'Dalam Kota';
        }

        return 'Luar Kota';
    }

    /**
     * Categorizes patient into Puskesad Status Pasien group.
     */
    /**
     * Categorizes patient into Puskesad Status Pasien group.
     */
    public function getStatusPuskesadAttribute(): string
    {
        $kelompok = strtoupper(trim((string) ($this->kelompok ?? '')));
        $penjamin = strtoupper(trim((string) ($this->jenis_penjamin ?? '')));
        $kesatuan = strtoupper(trim((string) ($this->kesatuan ?? '')));
        $instansi = strtoupper(trim((string) ($this->instansi ?? '')));
        $pangkat = strtoupper(trim((string) ($this->pangkat ?? '')));
        $kategori = strtoupper(trim((string) ($this->kategori ?? '')));

        // 1. Check TNI AL (Militer & Keluarga)
        if ($kelompok === 'AL' || str_contains($kelompok, 'TNI AL')) {
            return 'TNI AL';
        }
        if ($kelompok === 'KEL AL' || (str_contains($kelompok, 'KELUARGA') && (str_contains($kesatuan, 'AL') || str_contains($instansi, 'AL') || str_contains($pangkat, 'AL')))) {
            return 'KEL AL';
        }

        // 2. Check TNI AU (Militer & Keluarga)
        if ($kelompok === 'AU' || str_contains($kelompok, 'TNI AU')) {
            return 'TNI AU';
        }
        if ($kelompok === 'KEL AU' || (str_contains($kelompok, 'KELUARGA') && (str_contains($kesatuan, 'AU') || str_contains($instansi, 'AU') || str_contains($pangkat, 'AU')))) {
            return 'KEL AU';
        }

        // 3. Check PNS (PNS AD / AL / AU)
        if ($kelompok === 'PNS KEMHAN/TNI' || str_contains($kelompok, 'PNS') || str_contains($instansi, 'PNS') || str_contains($pangkat, 'PNS')) {
            if (str_contains($kesatuan, 'AL') || str_contains($instansi, 'AL')) {
                return 'PNS AL';
            }
            if (str_contains($kesatuan, 'AU') || str_contains($instansi, 'AU')) {
                return 'PNS AU';
            }

            return 'PNS AD';
        }

        // 4. Check PPPK Dinas
        if (str_contains($kelompok, 'PPPK DINAS') || str_contains($pangkat, 'PPPK')) {
            return 'PPPK DINAS';
        }

        // 5. Check TNI AD (Militer & Keluarga)
        if ($kelompok === 'AD' || str_contains($kelompok, 'MILITER TNI AD') || str_contains($kelompok, 'TNI AD')) {
            return 'TNI AD';
        }
        if (str_contains($kelompok, 'KELUARGA MILITER') || str_contains($kelompok, 'KEL AD')) {
            return 'KEL AD';
        }

        // Fallback for Dinas AD if penjamin/instansi indicates Dinas/Asabri
        if (str_contains($penjamin, 'BPJS DINAS') || str_contains($penjamin, 'ASABRI') || $instansi === 'TNI') {
            if (str_contains($kategori, 'ANAK') || str_contains($kategori, 'ISTRI') || str_contains($kategori, 'SUAMI') || str_contains($kategori, 'KELUARGA')) {
                return 'KEL AD';
            }

            return 'TNI AD';
        }

        // 6. POLRI
        if ($kelompok === 'POLRI' || str_contains($kelompok, 'POLRI') || str_contains($penjamin, 'POLRI') || str_contains($instansi, 'POLRI')) {
            return 'JKN POLRI';
        }

        // 7. PURNAWIRAWAN
        if ($kelompok === 'PURNAWIRAWAN' || str_contains($kelompok, 'PURNAWIRAWAN') || str_contains($penjamin, 'PURNAWIRAWAN') || str_contains($pangkat, 'PENSIUNAN')) {
            return 'PURNAWIRAWAN';
        }

        // 8. BPJS PBI
        if ($kelompok === 'BPJS PBI' || str_contains($kelompok, 'PBI') || str_contains($penjamin, 'PBI')) {
            return 'BPJS PBI';
        }

        // 9. BPJS SWASTA / KEMENTERIAN
        if (str_contains($kelompok, 'BPJS') || str_contains($penjamin, 'BPJS') || str_contains($penjamin, 'PEGAWAI') || str_contains($penjamin, 'KEMENTRIAN')) {
            return 'BPJS KEMENTERIAN / SWASTA';
        }

        // 10. TUNAI / UMUM
        if (str_contains($kelompok, 'TUNAI') || str_contains($kelompok, 'UMUM') || str_contains($penjamin, 'TUNAI') || str_contains($penjamin, 'UMUM')) {
            return 'TUNAI';
        }

        return 'ASURANSI / LAIN-LAIN';
    }
}
