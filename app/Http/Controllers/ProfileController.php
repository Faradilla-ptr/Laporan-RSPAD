<?php

namespace App\Http\Controllers;

use App\Models\ProfileRequest;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

/**
 * Controller untuk mengelola profil akun pengguna.
 * Membedakan alur pembaruan profil:
 * - Admin (Kaur): Perubahan profil langsung disimpan ke database.
 * - Petugas: Perubahan profil (nama, email, NIP/NRP, password) diajukan sebagai permohonan (ProfileRequest)
 *   yang membutuhkan persetujuan/validasi dari Admin terlebih dahulu.
 */
class ProfileController extends Controller
{
    /**
     * Menampilkan halaman profil akun pengguna yang sedang login.
     * Menampilkan informasi akun, status permohonan yang sedang pending, dan riwayat permohonan terakhir.
     *
     * @return View
     */
    public function index()
    {
        // Dapatkan data pengguna yang sedang login
        $user = Auth::user();

        // Catat aktivitas melihat profil ke audit log
        ActivityLogger::log('VIEW_PROFILE', 'Melihat halaman profil pengguna.');

        // Ambil permohonan perubahan profil pengguna yang masih berstatus 'pending' (jika ada)
        $pendingRequest = ProfileRequest::where('user_id', $user->id)->where('status', 'pending')->latest()->first();

        // Ambil maksimal 5 riwayat permohonan perubahan profil sebelumnya
        $recentRequests = ProfileRequest::where('user_id', $user->id)->latest()->limit(5)->get();

        return view('profile.index', compact('user', 'pendingRequest', 'recentRequests'));
    }

    /**
     * Memproses pengajuan pembaruan profil atau kata sandi pengguna.
     *
     * @return RedirectResponse
     */
    public function update(Request $request)
    {
        $user = Auth::user();

        // Validasi input data profil baru
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,'.$user->id, // Email unik kecuali milik user saat ini
            'nip_nrp' => 'nullable|string|max:100',
            'new_password' => 'nullable|string|min:6|confirmed', // Password baru minimal 6 karakter dan harus sama dengan konfirmasi
        ], [
            'name.required' => 'Nama lengkap wajib diisi.',
            'email.required' => 'Alamat email wajib diisi.',
            'email.unique' => 'Email ini sudah digunakan oleh akun lain.',
            'new_password.min' => 'Password baru minimal 6 karakter.',
            'new_password.confirmed' => 'Konfirmasi password baru tidak cocok.',
        ]);

        // ALUR ADMIN: Perubahan langsung diterapkan ke database
        if ($user->role === 'admin') {
            $user->name = $validated['name'];
            $user->email = $validated['email'];
            $user->nip_nrp = $validated['nip_nrp'] ?? null;
            if (! empty($validated['new_password'])) {
                $user->password = Hash::make($validated['new_password']);
            }
            $user->save();

            // Catat pembaruan profil admin ke audit log
            ActivityLogger::log('UPDATE_PROFILE', 'Admin '.$user->name.' memperbarui profil dan kata sandinya.');

            return back()->with('success', 'Profil Admin berhasil diperbarui.');
        }

        // ALUR PETUGAS: Membuat tiket permohonan (ProfileRequest) yang menunggu persetujuan Admin
        $hasPassword = ! empty($validated['new_password']);
        $reqType = $hasPassword ? 'CHANGE_PASSWORD' : 'UPDATE_PROFILE';

        // Hapus permohonan pending sebelumnya milik petugas ini jika ada, agar digantikan oleh permohonan terbaru
        ProfileRequest::where('user_id', $user->id)->where('status', 'pending')->delete();

        // Simpan permohonan baru dengan status 'pending'
        ProfileRequest::create([
            'user_id' => $user->id,
            'request_type' => $reqType,
            'new_name' => $validated['name'],
            'new_email' => $validated['email'],
            'new_nip_nrp' => $validated['nip_nrp'] ?? null,
            'new_password' => $hasPassword ? Hash::make($validated['new_password']) : null,
            'status' => 'pending',
        ]);

        // Catat pengajuan permohonan ke audit log
        ActivityLogger::log('REQUEST_PROFILE_UPDATE', 'Petugas '.$user->name.' mengajukan permohonan perubahan profil/password ke Admin.');

        return back()->with('success', 'Permohonan perubahan profil/password Anda telah berhasil diajukan! Menunggu verifikasi dan persetujuan dari Admin (Kaur).');
    }
}
