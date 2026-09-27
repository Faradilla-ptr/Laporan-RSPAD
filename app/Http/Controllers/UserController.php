<?php

namespace App\Http\Controllers;

use App\Mail\AccountApprovedMail;
use App\Mail\AccountRejectedMail;
use App\Models\ProfileRequest;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class UserController extends Controller
{
    public function index()
    {
        $pendingUsers = User::where('is_approved', false)->latest()->get();
        $pendingProfileRequests = ProfileRequest::with('user')->where('status', 'pending')->latest()->get();
        $approvedUsers = User::where('is_approved', true)->latest()->paginate(10);

        return view('admin.users.index', compact('pendingUsers', 'pendingProfileRequests', 'approvedUsers'));
    }

    public function approve(User $user)
    {
        $defaultPassword = 'petugas_rspad123';

        $user->update([
            'is_approved' => true,
            'password' => Hash::make($defaultPassword),
        ]);

        ActivityLogger::log('APPROVE_USER', 'Admin menyetujui pendaftaran akun Petugas '.$user->name.' ('.$user->email.').');

        try {
            Mail::to($user->email)->send(new AccountApprovedMail($user, $defaultPassword));
        } catch (\Exception $e) {
            Log::error("Gagal mengirim email persetujuan ke {$user->email}: ".$e->getMessage());
        }

        return back()->with('success', "Akun Petugas {$user->name} ({$user->email}) berhasil disetujui. Password default '{$defaultPassword}' telah di-generate dan notifikasi email telah dikirim.");
    }

    public function reject(User $user)
    {
        if ($user->role === 'admin') {
            return back()->with('error', 'Tidak dapat menghapus akun Admin.');
        }

        $userName = $user->name;
        $userEmail = $user->email;

        ActivityLogger::log('REJECT_USER', 'Admin menolak pendaftaran akun Petugas '.$userName.' ('.$userEmail.').');

        try {
            Mail::to($userEmail)->send(new AccountRejectedMail($user));
        } catch (\Exception $e) {
            Log::error("Gagal mengirim email penolakan ke {$userEmail}: ".$e->getMessage());
        }

        $user->delete();

        return back()->with('success', "Pendaftaran akun {$userName} ({$userEmail}) telah ditolak/dihapus dan notifikasi email telah dikirim.");
    }

    public function approveProfileRequest($id)
    {
        $pReq = ProfileRequest::with('user')->findOrFail($id);
        $user = $pReq->user;

        if (! $user) {
            $pReq->delete();

            return back()->with('error', 'Akun user tidak ditemukan.');
        }

        if (! empty($pReq->new_name)) {
            $user->name = $pReq->new_name;
        }
        if (! empty($pReq->new_email)) {
            $user->email = $pReq->new_email;
        }
        if (! empty($pReq->new_nip_nrp)) {
            $user->nip_nrp = $pReq->new_nip_nrp;
        }
        if (! empty($pReq->new_password)) {
            $user->password = $pReq->new_password;
        }
        $user->save();

        $pReq->update(['status' => 'approved']);

        ActivityLogger::log('APPROVE_PROFILE_REQUEST', 'Admin menyetujui permohonan perubahan profil/password Petugas '.$user->name.'.');

        return back()->with('success', "Permohonan perubahan profil/password Petugas {$user->name} berhasil disetujui!");
    }

    public function rejectProfileRequest($id)
    {
        $pReq = ProfileRequest::with('user')->findOrFail($id);
        $pReq->update(['status' => 'rejected']);

        ActivityLogger::log('REJECT_PROFILE_REQUEST', 'Admin menolak permohonan perubahan profil Petugas '.($pReq->user->name ?? '').'.');

        return back()->with('success', 'Permohonan perubahan profil/password Petugas telah ditolak.');
    }
}
