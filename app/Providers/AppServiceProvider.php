<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

/**
 * Service Provider Utama Aplikasi.
 * Mendaftarkan konfigurasi bootstrap, styling paginator, dan variabel global blade view.
 */
class AppServiceProvider extends ServiceProvider
{
    /**
     * Mendaftarkan service atau binding ke service container aplikasi.
     */
    public function register(): void
    {
        //
    }

    /**
     * Menginisialisasi service saat aplikasi dijalankan (bootstrapping).
     */
    public function boot(): void
    {
        // Menggunakan styling Bootstrap 5 untuk render elemen pagination Laravel
        Paginator::useBootstrapFive();

        // Membagikan variabel daftar opsi bulan / periode ($availableMonths) ke seluruh view blade secara global
        View::share('availableMonths', [
            'SEMUA' => 'Seluruh Bulan',
            '1' => 'Januari',
            '2' => 'Februari',
            '3' => 'Maret',
            '4' => 'April',
            '5' => 'Mei',
            '6' => 'Juni',
            '7' => 'Juli',
            '8' => 'Agustus',
            '9' => 'September',
            '10' => 'Oktober',
            '11' => 'November',
            '12' => 'Desember',
            'T1' => 'Triwulan I (Jan - Mar)',
            'T2' => 'Triwulan II (Apr - Jun)',
            'T3' => 'Triwulan III (Jul - Sep)',
            'T4' => 'Triwulan IV (Okt - Des)',
            'S1' => 'Semester 1 (Jan - Jun)',
            'S2' => 'Semester 2 (Jul - Des)',
        ]);
    }
}
