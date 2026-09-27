<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

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
            'password' => 'required|string|min:6|confirmed',
            'role' => 'required|in:petugas,admin',
            'nip_nrp' => 'nullable|string|max:100',
        ], [
            'name.required' => 'Nama lengkap wajib diisi.',
            'email.required' => 'Alamat email wajib diisi.',
            'email.unique' => 'Alamat email ini sudah terdaftar.',
            'password.required' => 'Kata sandi wajib diisi.',
            'password.min' => 'Kata sandi minimal 6 karakter.',
            'password.confirmed' => 'Konfirmasi kata sandi tidak cocok.',
            'role.required' => 'Peran/jabatan wajib dipilih.',
        ]);

        $isApproved = ($validated['role'] === 'admin');

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'is_approved' => $isApproved,
            'nip_nrp' => $validated['nip_nrp'] ?? null,
        ]);

        if (! $isApproved) {
            return redirect()->route('login')
                ->with('success', 'Pendaftaran berhasil! Akun Petugas Anda membutuhkan persetujuan/validasi dari Admin (Kaur) sebelum dapat digunakan untuk login.');
        }

        Auth::login($user);

        return redirect()->route('admin.dashboard')
            ->with('success', 'Registrasi berhasil! Selamat datang di Sistem Informasi Pelaporan RSPAD Gatot Soebroto.');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Anda telah berhasil keluar dari sistem.');
    }
}
