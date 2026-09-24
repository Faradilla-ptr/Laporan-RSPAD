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
    public function getStatusPuskesadAttribute(): string
    {
        $penjamin = strtoupper($this->jenis_penjamin ?? '');
        $instansi = strtoupper($this->instansi ?? '');
        $kategori = strtoupper($this->kategori ?? '');
        $pangkat  = strtoupper($this->pangkat ?? '');
        $kesatuan = strtoupper($this->kesatuan ?? '');

        // 1. Check TNI / PNS / KEL AD
        if (str_contains($penjamin, 'BPJS DINAS') || str_contains($penjamin, 'ASABRI') || $instansi === 'TNI' || str_contains($kesatuan, 'AD') || str_contains($kesatuan, 'KODAM') || str_contains($kesatuan, 'KOREM') || str_contains($kesatuan, 'KODIM') || str_contains($kesatuan, 'YON')) {
            // Check if AL or AU
            if (str_contains($kesatuan, 'AL') || str_contains($instansi, 'AL') || str_contains($pangkat, 'AL')) {
                if (str_contains($kategori, 'ISTRI') || str_contains($kategori, 'ANAK') || str_contains($kategori, 'KELUARGA')) {
                    return 'KEL AL';
                }
                if (str_contains($pangkat, 'PNS') || str_contains($instansi, 'PNS')) {
                    return 'PNS AL';
                }
                return 'TNI AL';
            }

            if (str_contains($kesatuan, 'AU') || str_contains($instansi, 'AU') || str_contains($pangkat, 'AU')) {
                if (str_contains($kategori, 'ISTRI') || str_contains($kategori, 'ANAK') || str_contains($kategori, 'KELUARGA')) {
                    return 'KEL AU';
                }
                if (str_contains($pangkat, 'PNS') || str_contains($instansi, 'PNS')) {
                    return 'PNS AU';
                }
                return 'TNI AU';
            }

            // Otherwise default AD
            if (str_contains($kategori, 'ISTRI') || str_contains($kategori, 'ANAK') || str_contains($kategori, 'KELUARGA') || str_contains($kategori, 'SUAMI')) {
                return 'KEL AD';
            }
            if (str_contains($pangkat, 'PNS') || str_contains($instansi, 'PNS') || str_contains($kategori, 'PNS')) {
                return 'PNS AD';
            }
            if (str_contains($pangkat, 'PPPK') || str_contains($kategori, 'PPPK')) {
                return 'PPPK DINAS';
            }
            return 'TNI AD';
        }

        // 2. POLRI
        if (str_contains($penjamin, 'POLRI') || str_contains($instansi, 'POLRI')) {
            return 'JKN POLRI';
        }

        // 3. PURNAWIRAWAN
        if (str_contains($penjamin, 'PURNAWIRAWAN') || str_contains($pangkat, 'PENSIUNAN') || str_contains($kategori, 'PURNAWIRAWAN')) {
            return 'PURNAWIRAWAN';
        }

        // 4. BPJS PBI
        if (str_contains($penjamin, 'PBI')) {
            return 'BPJS PBI';
        }

        // 5. BPJS SWASTA / KEMENTERIAN
        if (str_contains($penjamin, 'BPJS') || str_contains($penjamin, 'PEGAWAI')) {
            return 'BPJS KEMENTERIAN / SWASTA';
        }

        // 6. TUNAI
        if (str_contains($penjamin, 'TUNAI') || str_contains($penjamin, 'UMUM')) {
            return 'TUNAI';
        }

        return 'ASURANSI / LAIN-LAIN';
    }
}
