<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Model Eloquent untuk tabel `import_logs`.
 * Mencatat riwayat setiap berkas spreadsheet Excel/CSV yang diimpor ke sistem,
 * mencakup nama berkas, user pengunggah, periode laporan, dan total baris data yang berhasil diproses.
 */
class ImportLog extends Model
{
    /**
     * Atribut yang dapat diisi secara mass-assignment.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'filename',
        'user_id',
        'period_month',
        'period_year',
        'total_rows',
    ];

    /**
     * Relasi ke model User (pengguna yang mengunggah berkas).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relasi ke model RawVisit (seluruh data baris kunjungan yang dihasilkan dari berkas import ini).
     */
    public function visits(): HasMany
    {
        return $this->hasMany(RawVisit::class, 'import_log_id');
    }
}
