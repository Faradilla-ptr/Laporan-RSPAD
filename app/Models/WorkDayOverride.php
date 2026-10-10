<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Model Eloquent untuk tabel `work_day_overrides`.
 * Menyimpan nilai penyesuaian manual (override) jumlah hari kerja poliklinik buka pada periode bulan/tahun tertentu.
 * Digunakan pada formulir Laporan RL 3.5 (Baris 66) untuk menghitung rata-rata kunjungan per hari (Baris 77).
 */
class WorkDayOverride extends Model
{
    /**
     * Atribut yang dapat diisi secara mass-assignment.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'month',
        'year',
        'work_days',
    ];
}
