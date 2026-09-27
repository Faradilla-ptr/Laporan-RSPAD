<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ], [
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'password.required' => 'Kata sandi wajib diisi.',
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $user = Auth::user();

            if (! $user->is_approved && $user->role !== 'admin') {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return back()->withErrors([
                    'email' => 'Akun Anda (Petugas) belum disetujui/disahkan oleh Admin (Kaur). Silakan hubungi Admin.',
                ])->with('error', 'Akun Anda belum disetujui oleh Admin (Kaur). Silakan hubungi Admin untuk validasi.')->onlyInput('email');
            }

            $request->session()->regenerate();

            ActivityLogger::log('LOGIN', 'User '.$user->name.' ('.$user->role.') berhasil masuk ke sistem.');

            $role = $user->role === 'admin' ? 'admin' : 'petugas';

            return redirect()->intended(route("{$role}.dashboard"))
                ->with('success', 'Selamat datang kembali, '.$user->name);
        }

        return back()->withErrors([
            'email' => 'Email atau kata sandi yang Anda masukkan salah.',
        ])->with('error', 'Email atau kata sandi yang Anda masukkan salah.')->onlyInput('email');
    }

    public function showRegister()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('auth.register');
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'nip_nrp' => 'nullable|string|max:100',
        ], [
            'name.required' => 'Nama lengkap wajib diisi.',
            'email.required' => 'Alamat email wajib diisi.',
            'email.unique' => 'Alamat email ini sudah terdaftar.',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make(Str::random(16)),
            'role' => 'petugas',
            'is_approved' => false,
            'nip_nrp' => $validated['nip_nrp'] ?? null,
        ]);

        ActivityLogger::log('REGISTRASI_AKUN', 'Pengajuan pendaftaran akun Petugas baru: '.$user->name.' ('.$user->email.').');

        return redirect()->route('login')
            ->with('success', 'Pendaftaran berhasil diajukan! Akun Petugas Anda membutuhkan persetujuan/validasi dari Admin (Kaur). Kata sandi login default akan dikirimkan secara otomatis via email setelah disetujui.');
    }

    public function logout(Request $request)
    {
        if (Auth::check()) {
            ActivityLogger::log('LOGOUT', 'User '.Auth::user()->name.' keluar dari sistem.');
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Anda telah berhasil keluar dari sistem.');
    }
}
