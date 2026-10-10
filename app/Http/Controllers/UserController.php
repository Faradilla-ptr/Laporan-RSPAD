<?php

namespace App\Http\Controllers;

use App\Mail\AccountApprovedMail;
use App\Mail\AccountRejectedMail;
use App\Models\ProfileRequest;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

/**
 * Controller khusus Administrator (Kaur) untuk mengelola akun pengguna dan permohonan profil.
 * Mencakup proses verifikasi/persetujuan pendaftaran akun petugas baru,
 * penolakan akun, serta persetujuan permohonan perubahan profil/password petugas.
 */
class UserController extends Controller
{
    /**
     * Menampilkan halaman manajemen pengguna yang berisi daftar akun pending,
     * permohonan pembaruan profil yang menunggu validasi, dan akun petugas yang telah aktif.
     *
     * @return View
     */
    public function index()
    {
        // Catat aktivitas pembukaan halaman manajemen user ke audit log
        ActivityLogger::log('VIEW_USERS', 'Admin melihat halaman Kelola Pengguna & Permohonan Akun.');

        // Ambil daftar akun yang belum disetujui (status is_approved = false)
        $pendingUsers = User::where('is_approved', false)->latest()->get();

        // Ambil daftar permohonan perubahan profil dengan status 'pending' beserta relasi user
        $pendingProfileRequests = ProfileRequest::with('user')->where('status', 'pending')->latest()->get();

        // Ambil daftar pengguna yang sudah disetujui/aktif dengan paginasi 10 data per halaman
        $approvedUsers = User::where('is_approved', true)->latest()->paginate(10);

        return view('admin.users.index', compact('pendingUsers', 'pendingProfileRequests', 'approvedUsers'));
    }

    /**
     * Menyetujui pendaftaran akun petugas baru.
     * Mengaktifkan akun (is_approved = true), membuat kata sandi default,
     * dan mengirimkan surat notifikasi email ke petugas.
     *
     * @return RedirectResponse
     */
    public function approve(User $user)
    {
        // Kata sandi default sistem yang diberikan kepada petugas baru
        $defaultPassword = 'petugas_rspad123';

        // Perbarui status akun menjadi aktif dan set kata sandi yang telah di-hash
        $user->update([
            'is_approved' => true,
            'password' => Hash::make($defaultPassword),
        ]);

        // Catat aksi persetujuan akun ke audit log
        ActivityLogger::log('APPROVE_USER', 'Admin menyetujui pendaftaran akun Petugas '.$user->name.' ('.$user->email.').');

        // Kirimkan notifikasi email berisi informasi akun dan password default
        try {
            Mail::to($user->email)->send(new AccountApprovedMail($user, $defaultPassword));
        } catch (\Exception $e) {
            // Jika pengiriman email gagal (misal koneksi SMTP offline), catat ke error log Laravel tanpa membatalkan transaksi
            Log::error("Gagal mengirim email persetujuan ke {$user->email}: ".$e->getMessage());
        }

        return back()->with('success', "Akun Petugas {$user->name} ({$user->email}) berhasil disetujui. Password default '{$defaultPassword}' telah di-generate dan notifikasi email telah dikirim.");
    }

    /**
     * Menolak pengajuan pendaftaran akun petugas dan menghapus data user dari sistem.
     *
     * @return RedirectResponse
     */
    public function reject(User $user)
    {
        // Lindungi akun administrator agar tidak dapat dihapus melalui fitur ini
        if ($user->role === 'admin') {
            return back()->with('error', 'Tidak dapat menghapus akun Admin.');
        }

        $userName = $user->name;
        $userEmail = $user->email;

        // Catat penolakan pendaftaran akun ke audit log
        ActivityLogger::log('REJECT_USER', 'Admin menolak pendaftaran akun Petugas '.$userName.' ('.$userEmail.').');

        // Kirim email notifikasi bahwa pengajuan pendaftaran akun ditolak
        try {
            Mail::to($userEmail)->send(new AccountRejectedMail($user));
        } catch (\Exception $e) {
            Log::error("Gagal mengirim email penolakan ke {$userEmail}: ".$e->getMessage());
        }

        // Hapus rekaman user dari database
        $user->delete();

        return back()->with('success', "Pendaftaran akun {$userName} ({$userEmail}) telah ditolak/dihapus dan notifikasi email telah dikirim.");
    }

    /**
     * Menyetujui permohonan perubahan profil (nama, email, NIP/NRP, atau kata sandi) dari petugas.
     *
     * @param  int|string  $id
     * @return RedirectResponse
     */
    public function approveProfileRequest($id)
    {
        // Temukan rekaman permohonan perubahan profil beserta data usernya
        $pReq = ProfileRequest::with('user')->findOrFail($id);
        $user = $pReq->user;

        // Jika akun user terkait sudah tidak ada di sistem
        if (! $user) {
            $pReq->delete();

            return back()->with('error', 'Akun user tidak ditemukan.');
        }

        // Terapkan perubahan nama jika diajukan
        if (! empty($pReq->new_name)) {
            $user->name = $pReq->new_name;
        }
        // Terapkan perubahan email jika diajukan
        if (! empty($pReq->new_email)) {
            $user->email = $pReq->new_email;
        }
        // Terapkan perubahan NIP/NRP jika diajukan
        if (! empty($pReq->new_nip_nrp)) {
            $user->nip_nrp = $pReq->new_nip_nrp;
        }
        // Terapkan kata sandi baru jika diajukan
        if (! empty($pReq->new_password)) {
            $user->password = $pReq->new_password;
        }
        // Simpan perubahan data user ke database
        $user->save();

        // Ubah status permohonan profil menjadi 'approved'
        $pReq->update(['status' => 'approved']);

        // Catat aksi persetujuan ke audit log
        ActivityLogger::log('APPROVE_PROFILE_REQUEST', 'Admin menyetujui permohonan perubahan profil/password Petugas '.$user->name.'.');

        return back()->with('success', "Permohonan perubahan profil/password Petugas {$user->name} berhasil disetujui!");
    }

    /**
     * Menolak permohonan perubahan profil dari petugas.
     *
     * @param  int|string  $id
     * @return RedirectResponse
     */
    public function rejectProfileRequest($id)
    {
        // Temukan rekaman permohonan profil dan ubah statusnya menjadi 'rejected'
        $pReq = ProfileRequest::with('user')->findOrFail($id);
        $pReq->update(['status' => 'rejected']);

        // Catat penolakan ke audit log
        ActivityLogger::log('REJECT_PROFILE_REQUEST', 'Admin menolak permohonan perubahan profil Petugas '.($pReq->user->name ?? '').'.');

        return back()->with('success', 'Permohonan perubahan profil/password Petugas telah ditolak.');
    }
}
