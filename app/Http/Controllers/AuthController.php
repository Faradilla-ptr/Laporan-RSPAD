<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Controller untuk mengelola otentikasi pengguna (Login, Register, Logout).
 * Menangani validasi input, verifikasi persetujuan akun petugas, dan pencatatan riwayat aktivitas log.
 */
class AuthController extends Controller
{
    /**
     * Menampilkan halaman form login.
     * Jika pengguna sudah terautentikasi (sudah login), langsung dialihkan ke dashboard.
     *
     * @return RedirectResponse|View
     */
    public function showLogin()
    {
        // Cek jika pengguna sudah dalam keadaan login
        if (Auth::check()) {
            // Arahkan langsung ke rute dashboard utama
            return redirect()->route('dashboard');
        }

        // Tampilkan view form login (resources/views/auth/login.blade.php)
        return view('auth.login');
    }

    /**
     * Memproses autentikasi kredensial login (Email & Password).
     * Memeriksa status persetujuan (approval) oleh Admin untuk role petugas.
     *
     * @return RedirectResponse
     */
    public function login(Request $request)
    {
        // Validasi input data login dengan pesan error kustom bahasa Indonesia
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ], [
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'password.required' => 'Kata sandi wajib diisi.',
        ]);

        // Coba autentikasi menggunakan Auth::attempt dan periksa opsi 'remember me'
        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            // Ambil data user yang berhasil diautentikasi
            $user = Auth::user();

            // Cek apakah akun petugas belum disetujui (is_approved == false) dan bukan admin
            if (! $user->is_approved && $user->role !== 'admin') {
                // Keluarkan sesi user karena belum disetujui
                Auth::logout();
                // Batalkan sesi saat ini demi keamanan
                $request->session()->invalidate();
                // Buat ulang token CSRF
                $request->session()->regenerateToken();

                // Kembalikan ke halaman login dengan pesan peringatan
                return back()->withErrors([
                    'email' => 'Akun Anda (Petugas) belum disetujui/disahkan oleh Admin (Kaur). Silakan hubungi Admin.',
                ])->with('error', 'Akun Anda belum disetujui oleh Admin (Kaur). Silakan hubungi Admin untuk validasi.')->onlyInput('email');
            }

            // Regenerasi ID sesi untuk mencegah serangan Session Fixation
            $request->session()->regenerate();

            // Catat log audit aktivitas bahwa pengguna berhasil login
            ActivityLogger::log('LOGIN', 'User '.$user->name.' ('.$user->role.') berhasil masuk ke sistem.');

            // Tentukan target redirect berdasarkan role (admin atau petugas)
            $role = $user->role === 'admin' ? 'admin' : 'petugas';

            // Arahkan ke rute dashboard sesuai role masing-masing
            return redirect()->intended(route("{$role}.dashboard"))
                ->with('success', 'Selamat datang kembali, '.$user->name);
        }

        // Catat log audit aktivitas jika percobaan login gagal
        ActivityLogger::log('FAILED_LOGIN', 'Percobaan masuk/login gagal untuk email: '.$request->input('email').'.');

        // Kembalikan ke halaman form login dengan notifikasi kredensial salah
        return back()->withErrors([
            'email' => 'Email atau kata sandi yang Anda masukkan salah.',
        ])->with('error', 'Email atau kata sandi yang Anda masukkan salah.')->onlyInput('email');
    }

    /**
     * Menampilkan halaman form pendaftaran (registrasi) akun petugas baru.
     *
     * @return RedirectResponse|View
     */
    public function showRegister()
    {
        // Jika pengguna sudah login, alihkan langsung ke dashboard
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        // Tampilkan view form register (resources/views/auth/register.blade.php)
        return view('auth.register');
    }

    /**
     * Memproses pendaftaran akun petugas baru ke database.
     * Akun yang baru didaftarkan berstatus 'pending' (is_approved = false)
     * dan menunggu persetujuan dari Administrator (Kaur).
     *
     * @return RedirectResponse
     */
    public function register(Request $request)
    {
        // Validasi input form pendaftaran
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'nip_nrp' => 'nullable|string|max:100',
        ], [
            'name.required' => 'Nama lengkap wajib diisi.',
            'email.required' => 'Alamat email wajib diisi.',
            'email.unique' => 'Alamat email ini sudah terdaftar.',
        ]);

        // Buat record user baru dengan status pending persetujuan
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make(Str::random(16)), // Password acak sementara sebelum disetujui admin
            'role' => 'petugas', // Role default pendaftaran adalah petugas
            'is_approved' => false, // Membutuhkan persetujuan admin terlebih dahulu
            'nip_nrp' => $validated['nip_nrp'] ?? null,
        ]);

        // Catat aktivitas registrasi akun ke audit log
        ActivityLogger::log('REGISTRASI_AKUN', 'Pengajuan pendaftaran akun Petugas baru: '.$user->name.' ('.$user->email.').');

        // Arahkan ke halaman login dengan informasi bahwa akun menunggu verifikasi admin
        return redirect()->route('login')
            ->with('success', 'Pendaftaran berhasil diajukan! Akun Petugas Anda membutuhkan persetujuan/validasi dari Admin (Kaur). Kata sandi login default akan dikirimkan secara otomatis via email setelah disetujui.');
    }

    /**
     * Memproses logout pengguna dari aplikasi.
     * Menghapus sesi otentikasi dan melakukan regenerasi token CSRF.
     *
     * @return RedirectResponse
     */
    public function logout(Request $request)
    {
        // Jika ada pengguna yang sedang login, catat riwayat log aktivitas keluar
        if (Auth::check()) {
            ActivityLogger::log('LOGOUT', 'User '.Auth::user()->name.' keluar dari sistem.');
        }

        // Hapus status autentikasi pengguna
        Auth::logout();
        // Hancurkan data sesi aktif
        $request->session()->invalidate();
        // Buat token CSRF baru demi keamanan
        $request->session()->regenerateToken();

        // Redirect kembali ke halaman login dengan pesan sukses
        return redirect()->route('login')->with('success', 'Anda telah berhasil keluar dari sistem.');
    }
}
