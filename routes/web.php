<?php

// Import controller yang digunakan untuk menangani request pada masing-masing rute
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportPuskesadController;
use App\Http\Controllers\ReportRL34Controller;
use App\Http\Controllers\ReportRL35Controller;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rute Autentikasi dengan Proteksi Brute Force (Rate Limiting)
|--------------------------------------------------------------------------
| Membatasi percobaan request maksimal 5 kali dalam 1 menit (throttle:5,1)
| untuk mencegah serangan brute force pada form login dan pendaftaran akun.
*/
Route::middleware(['throttle:5,1'])->group(function () {
    // Memproses data form login yang dikirimkan pengguna (metode POST)
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
    // Memproses data pendaftaran akun baru petugas (metode POST)
    Route::post('/register', [AuthController::class, 'register'])->name('register.post');
});

// Menampilkan halaman antarmuka form login (metode GET)
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');

// Menampilkan halaman antarmuka form registrasi akun (metode GET)
Route::get('/register', [AuthController::class, 'showRegister'])->name('register');

// Memproses logout pengguna dan menghapus sesi autentikasi (metode POST)
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

/*
|--------------------------------------------------------------------------
| Rute Terproteksi (Hanya Pengguna yang Sudah Login / Middleware 'auth')
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->group(function () {
    /*
     * Rute Profil Pengguna (Dapat diakses oleh Admin maupun Petugas)
     */
    // Menampilkan halaman profil akun pengguna yang sedang login
    Route::get('/profile', [ProfileController::class, 'index'])->name('profile.index');
    // Menyimpan perubahan pembaruan profil pengguna (metode PUT)
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');

    /*
     * Fungsi Helper untuk Redirect Dinamis Berdasarkan Peran (Role) Pengguna.
     * Mengarahkan URL umum ke prefix role masing-masing (/admin/... atau /petugas/...)
     * dengan tetap mempertahankan query string filter (seperti bulan & tahun).
     */
    $getRolePath = function ($path = 'dashboard') {
        // Cek apakah pengguna yang sedang login memiliki role 'admin' atau 'petugas'
        $role = (Auth::check() && Auth::user()->role === 'admin') ? 'admin' : 'petugas';
        // Ambil query parameter dari URL saat ini (misal: ?month=1&year=2024)
        $queryString = request()->getQueryString();
        // Susun target URL sesuai role pengguna
        $target = "/{$role}/{$path}".($queryString ? "?{$queryString}" : '');

        // Pertahankan flash message session (alert sukses/gagal) saat redirect
        if (request()->hasSession()) {
            request()->session()->reflash();
        }

        // Eksekusi redirect ke target URL yang sudah disusun
        return redirect()->to($target);
    };

    // Redirect root URL (/) langsung ke halaman dashboard sesuai role
    Route::get('/', fn () => $getRolePath('dashboard'));
    // Redirect /dashboard ke dashboard role masing-masing
    Route::get('/dashboard', fn () => $getRolePath('dashboard'))->name('dashboard');

    /*
     * Rute Manajemen Import Data Kunjungan Pasien
     */
    // Redirect halaman daftar riwayat import sesuai role
    Route::get('/imports', fn () => $getRolePath('imports'))->name('imports.index');
    // Memproses unggahan file Excel/CSV kunjungan pasien (dibatasi 10 request/menit)
    Route::post('/imports', [ImportController::class, 'store'])->middleware('throttle:10,1')->name('imports.store');
    // Menghapus seluruh data kunjungan dan riwayat import (dibatasi 5 request/menit)
    Route::delete('/imports/all', [ImportController::class, 'truncateAll'])->middleware('throttle:5,1')->name('imports.truncateAll');
    // Menghapus data import spesifik berdasarkan ID riwayat import
    Route::delete('/imports/{id}', [ImportController::class, 'destroy'])->name('imports.destroy');

    /*
     * Rute Laporan RL 3.4 (Kunjungan Spesialisasi / Rujukan)
     */
    // Redirect URL umum laporan RL 3.4 ke URL role
    Route::get('/reports/rl34', fn () => $getRolePath('reports/rl34'))->name('reports.rl34');
    // Mengunduh / export data laporan RL 3.4 dalam format Excel
    Route::get('/reports/rl34/export', [ReportRL34Controller::class, 'exportExcel'])->name('reports.rl34.export');
    // Mengedit / memperbarui data rincian kunjungan RL 3.4 (inline edit)
    Route::put('/reports/rl34', [ReportRL34Controller::class, 'update'])->middleware('throttle:30,1')->name('reports.rl34.update');
    // Menghapus data rincian baris laporan RL 3.4
    Route::delete('/reports/rl34', [ReportRL34Controller::class, 'destroy'])->middleware('throttle:10,1')->name('reports.rl34.destroy');

    /*
     * Rute Laporan RL 3.5 (Perincian Kunjungan Rawat Jalan per Poliklinik)
     */
    // Redirect URL umum laporan RL 3.5 ke URL role
    Route::get('/reports/rl35', fn () => $getRolePath('reports/rl35'))->name('reports.rl35');
    // Mengunduh / export data laporan RL 3.5 dalam format Excel
    Route::get('/reports/rl35/export', [ReportRL35Controller::class, 'exportExcel'])->name('reports.rl35.export');
    // Mengedit / memperbarui data rincian baris laporan RL 3.5 (inline edit)
    Route::put('/reports/rl35', [ReportRL35Controller::class, 'update'])->middleware('throttle:30,1')->name('reports.rl35.update');
    // Mengubah jumlah hari kerja efektif bulanan untuk perhitungan rata-rata kunjungan per hari
    Route::put('/reports/rl35/work-days', [ReportRL35Controller::class, 'updateWorkDays'])->middleware('throttle:30,1')->name('reports.rl35.workDays');
    // Menghapus data kunjungan baris laporan RL 3.5
    Route::delete('/reports/rl35', [ReportRL35Controller::class, 'destroy'])->middleware('throttle:10,1')->name('reports.rl35.destroy');

    /*
     * Rute Laporan Puskesad (Kategori Pasien Militer, PNS, BPJS, Umum, dll.)
     */
    // Redirect URL umum laporan Puskesad ke URL role
    Route::get('/reports/puskesad', fn () => $getRolePath('reports/puskesad'))->name('reports.puskesad');
    // Mengunduh / export data laporan Puskesad dalam format Excel
    Route::get('/reports/puskesad/export', [ReportPuskesadController::class, 'exportExcel'])->name('reports.puskesad.export');

    /*
     * Pendaftaran Rute Eksplisit Berdasarkan Prefix Role (/admin/* dan /petugas/*)
     * Memastikan kedua role memiliki endpoint halaman tampilan dan operasional yang sesuai.
     */
    foreach (['admin', 'petugas'] as $role) {
        Route::prefix($role)->name("{$role}.")->group(function () {
            // Halaman dashboard utama (analisis statistik, grafik, dan ringkasan kunjungan)
            Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

            // Halaman riwayat dan proses import file
            Route::get('/imports', [ImportController::class, 'index'])->name('imports.index');
            Route::post('/imports', [ImportController::class, 'store'])->middleware('throttle:10,1')->name('imports.store');
            Route::delete('/imports/all', [ImportController::class, 'truncateAll'])->middleware('throttle:5,1')->name('imports.truncateAll');
            Route::delete('/imports/{id}', [ImportController::class, 'destroy'])->name('imports.destroy');

            // Halaman dan pengolahan laporan RL 3.4
            Route::get('/reports/rl34', [ReportRL34Controller::class, 'index'])->name('reports.rl34');
            Route::get('/reports/rl34/export', [ReportRL34Controller::class, 'exportExcel'])->name('reports.rl34.export');
            Route::put('/reports/rl34', [ReportRL34Controller::class, 'update'])->middleware('throttle:30,1')->name('reports.rl34.update');
            Route::delete('/reports/rl34', [ReportRL34Controller::class, 'destroy'])->middleware('throttle:10,1')->name('reports.rl34.destroy');

            // Halaman dan pengolahan laporan RL 3.5
            Route::get('/reports/rl35', [ReportRL35Controller::class, 'index'])->name('reports.rl35');
            Route::get('/reports/rl35/export', [ReportRL35Controller::class, 'exportExcel'])->name('reports.rl35.export');
            Route::put('/reports/rl35', [ReportRL35Controller::class, 'update'])->middleware('throttle:30,1')->name('reports.rl35.update');
            Route::put('/reports/rl35/work-days', [ReportRL35Controller::class, 'updateWorkDays'])->middleware('throttle:30,1')->name('reports.rl35.workDays');
            Route::delete('/reports/rl35', [ReportRL35Controller::class, 'destroy'])->middleware('throttle:10,1')->name('reports.rl35.destroy');

            // Halaman dan pengolahan laporan Puskesad
            Route::get('/reports/puskesad', [ReportPuskesadController::class, 'index'])->name('reports.puskesad');
            Route::get('/reports/puskesad/export', [ReportPuskesadController::class, 'exportExcel'])->name('reports.puskesad.export');
        });
    }

    /*
     * Rute Khusus Administrator (/admin/*)
     * Berisi fitur manajemen akun pengguna, persetujuan (approval), dan audit activity logs.
     */
    Route::prefix('admin')->name('admin.')->group(function () {
        // Menampilkan daftar seluruh akun pengguna (petugas yang aktif maupun pending)
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        // Menyetujui akun petugas baru agar dapat login ke sistem
        Route::post('/users/{user}/approve', [UserController::class, 'approve'])->name('users.approve');
        // Menolak dan menghapus pendaftaran akun petugas
        Route::delete('/users/{user}/reject', [UserController::class, 'reject'])->name('users.reject');

        // Menyetujui pengajuan permohonan perubahan profil (seperti ganti role/email/nama)
        Route::post('/profile-requests/{id}/approve', [UserController::class, 'approveProfileRequest'])->name('profile_requests.approve');
        // Menolak pengajuan permohonan perubahan profil
        Route::delete('/profile-requests/{id}/reject', [UserController::class, 'rejectProfileRequest'])->name('profile_requests.reject');

        // Menampilkan log jejak audit aktivitas (siapa melakukan apa, kapan, dan IP address)
        Route::get('/activity-logs', [ActivityLogController::class, 'index'])->name('activity_logs.index');
    });
});
