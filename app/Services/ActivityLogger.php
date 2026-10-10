<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

/**
 * Service pembantu (Helper Service) untuk mencatat seluruh jejak audit aktivitas ke tabel activity_logs.
 * Menjamin pencatatan berjalan secara konsisten tanpa menghentikan proses utama jika terjadi exception.
 */
class ActivityLogger
{
    /**
     * Menyimpan riwayat aktivitas baru ke dalam database.
     *
     * @param  string  $action  Kode aksi atau kategori aktivitas (contoh: 'LOGIN', 'IMPORT_EXCEL', dll.)
     * @param  string|null  $description  Penjelasan rinci mengenai kegiatan yang dilakukan
     */
    public static function log(string $action, ?string $description = null): ?ActivityLog
    {
        try {
            // Ambil data user yang sedang aktif melakukan aksi
            $user = Auth::user();

            // Simpan catatan aktivitas ke tabel activity_logs
            return ActivityLog::create([
                'user_id' => $user ? $user->id : null,
                'user_name' => $user ? $user->name : 'Sistem / Pengunjung Publik',
                'user_role' => $user ? $user->role : 'guest',
                'action' => $action,
                'description' => $description,
                'ip_address' => Request::ip(), // Rekam alamat IP komputer/perangkat pengguna
            ]);
        } catch (\Exception $e) {
            // Tangkap dan abaikan error pencatatan agar tidak menggagalkan proses bisnis utama aplikasi
            return null;
        }
    }
}
