<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Model Pengguna (User) untuk Otentikasi dan Otorisasi Multi-Role.
 * Mendukung role 'admin' (Kepala Urusan / Kaur) dan 'petugas' (Pengolah Data).
 * Dilengkapi atribut persetujuan `is_approved` untuk membatasi akses login petugas baru sebelum divalidasi.
 */
#[Fillable(['name', 'email', 'password', 'role', 'is_approved', 'nip_nrp'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Konfigurasi tipe data casting otomatis untuk atribut model.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed', // Hash password otomatis saat disimpan
            'is_approved' => 'boolean', // Konversi status approval menjadi tipe boolean (true/false)
        ];
    }
}
