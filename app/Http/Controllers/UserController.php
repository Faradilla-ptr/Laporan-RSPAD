<?php

namespace App\Http\Controllers;

use App\Models\User;

class UserController extends Controller
{
    public function index()
    {
        $pendingUsers = User::where('is_approved', false)->latest()->get();
        $approvedUsers = User::where('is_approved', true)->latest()->paginate(10);

        return view('admin.users.index', compact('pendingUsers', 'approvedUsers'));
    }

    public function approve(User $user)
    {
        $user->update(['is_approved' => true]);

        return back()->with('success', "Akun Petugas {$user->name} ({$user->email}) berhasil disetujui dan kini dapat login ke sistem.");
    }

    public function reject(User $user)
    {
        if ($user->role === 'admin') {
            return back()->with('error', 'Tidak dapat menghapus akun Admin.');
        }

        $userName = $user->name;
        $user->delete();

        return back()->with('success', "Pendaftaran akun {$userName} telah ditolak/dihapus.");
    }
}
