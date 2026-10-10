<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Controller khusus Administrator untuk memantau jejak audit aktivitas sistem (Audit Trail Log).
 * Menampilkan catatan siapa pengguna yang melakukan aksi, jenis aksi, deskripsi kegiatan,
 * alamat IP pengakses, serta waktu eksekusi.
 */
class ActivityLogController extends Controller
{
    /**
     * Menampilkan daftar log riwayat aktivitas sistem dengan fitur pencarian dan paginasi.
     *
     * @return View
     */
    public function index(Request $request)
    {
        // Catat aktivitas membuka halaman audit log sistem
        ActivityLogger::log('VIEW_ACTIVITY_LOGS', 'Admin melihat Log Aktivitas Sistem (Trail Log).'.($request->filled('search') ? ' Cari: '.$request->search : ''));

        // Bangun query aktivitas diurutkan dari yang paling baru
        $query = ActivityLog::with('user')->latest();

        // Terapkan filter pencarian teks jika input 'search' terisi
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('user_name', 'like', "%{$search}%")
                    ->orWhere('action', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('ip_address', 'like', "%{$search}%");
            });
        }

        // Ambil data log dengan batas 20 baris per halaman
        $logs = $query->paginate(20);

        return view('admin.activity_logs.index', compact('logs'));
    }
}
