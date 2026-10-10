<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model Eloquent untuk tabel `profile_requests`.
 * Menyimpan pengajuan permohonan perubahan data profil atau kata sandi dari petugas,
 * yang memerlukan otorisasi dan persetujuan dari Administrator sebelum diterapkan.
 */
class ProfileRequest extends Model
{
    /**
     * Atribut yang dapat diisi secara mass-assignment.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'request_type',
        'new_name',
        'new_email',
        'new_password',
        'new_nip_nrp',
        'status',
    ];

    /**
     * Relasi ke model User (petugas yang mengajukan permohonan perubahan profil).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
