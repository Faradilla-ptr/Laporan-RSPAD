<?php

namespace App\Http\Controllers;

use App\Models\ProfileRequest;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $pendingRequest = ProfileRequest::where('user_id', $user->id)->where('status', 'pending')->latest()->first();
        $recentRequests = ProfileRequest::where('user_id', $user->id)->latest()->limit(5)->get();

        return view('profile.index', compact('user', 'pendingRequest', 'recentRequests'));
    }

    public function update(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,'.$user->id,
            'nip_nrp' => 'nullable|string|max:100',
            'new_password' => 'nullable|string|min:6|confirmed',
        ], [
            'name.required' => 'Nama lengkap wajib diisi.',
            'email.required' => 'Alamat email wajib diisi.',
            'email.unique' => 'Email ini sudah digunakan oleh akun lain.',
            'new_password.min' => 'Password baru minimal 6 karakter.',
            'new_password.confirmed' => 'Konfirmasi password baru tidak cocok.',
        ]);

        if ($user->role === 'admin') {
            // Admin updates immediately
            $user->name = $validated['name'];
            $user->email = $validated['email'];
            $user->nip_nrp = $validated['nip_nrp'] ?? null;
            if (! empty($validated['new_password'])) {
                $user->password = Hash::make($validated['new_password']);
            }
            $user->save();

            ActivityLogger::log('UPDATE_PROFILE', 'Admin '.$user->name.' memperbarui profil dan kata sandinya.');

            return back()->with('success', 'Profil Admin berhasil diperbarui.');
        }

        // Petugas creates a profile request for Admin verification
        $hasPassword = ! empty($validated['new_password']);
        $reqType = $hasPassword ? 'CHANGE_PASSWORD' : 'UPDATE_PROFILE';

        // Check if there is already a pending request
        ProfileRequest::where('user_id', $user->id)->where('status', 'pending')->delete();

        ProfileRequest::create([
            'user_id' => $user->id,
            'request_type' => $reqType,
            'new_name' => $validated['name'],
            'new_email' => $validated['email'],
            'new_nip_nrp' => $validated['nip_nrp'] ?? null,
            'new_password' => $hasPassword ? Hash::make($validated['new_password']) : null,
            'status' => 'pending',
        ]);

        ActivityLogger::log('REQUEST_PROFILE_UPDATE', 'Petugas '.$user->name.' mengajukan permohonan perubahan profil/password ke Admin.');

        return back()->with('success', 'Permohonan perubahan profil/password Anda telah berhasil diajukan! Menunggu verifikasi dan persetujuan dari Admin (Kaur).');
    }
}
