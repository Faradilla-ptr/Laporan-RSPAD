<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model Eloquent untuk tabel `activity_logs`.
 * Menyimpan jejak audit sistem (Audit Trail Log) untuk memonitor aktivitas pengguna,
 * termasuk login, manipulasi data laporan, impor berkas, serta alamat IP perangkat.
 */
class ActivityLog extends Model
{
    /**
     * Atribut yang dapat diisi secara mass-assignment.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'user_name',
        'user_role',
        'action',
        'description',
        'ip_address',
    ];

    /**
     * Relasi ke model User (pengguna yang melakukan aksi).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
