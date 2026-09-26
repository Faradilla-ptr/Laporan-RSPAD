@extends('layouts.app')

@section('title', 'Masuk Akun')

@section('content')
<style>
    .auth-page-wrapper {
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #ffffff;
        padding: 2rem 1rem;
        position: relative;
        overflow: hidden;
    }

    /* Ambient Decorative Elements */
    .auth-page-wrapper::before {
        content: '';
        position: absolute;
        width: 450px;
        height: 450px;
        border-radius: 50%;
        background: radial-gradient(circle, rgba(91, 118, 83, 0.05) 0%, rgba(255, 255, 255, 0) 70%);
        top: -100px;
        left: -100px;
        pointer-events: none;
    }

    .auth-page-wrapper::after {
        content: '';
        position: absolute;
        width: 500px;
        height: 500px;
        border-radius: 50%;
        background: radial-gradient(circle, rgba(91, 118, 83, 0.04) 0%, rgba(255, 255, 255, 0) 70%);
        bottom: -150px;
        right: -100px;
        pointer-events: none;
    }

    .auth-card-container {
        background: #ffffff;
        border-radius: 16px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        overflow: hidden;
        width: 100%;
        max-width: 960px;
        display: flex;
        position: relative;
        z-index: 10;
        border: 1px solid #e2e8f0;
    }

    /* Left Hero Banner (Desktop) */
    .auth-hero-banner {
        flex: 1;
        background: linear-gradient(145deg, #23422E 0%, #2E5A3C 100%);
        color: #ffffff;
        padding: 3rem 2.5rem;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        position: relative;
        overflow: hidden;
    }

    .auth-hero-banner::before {
        content: '';
        position: absolute;
        top: 0; right: 0; bottom: 0; left: 0;
        background: radial-gradient(circle at top right, rgba(255, 255, 255, 0.12), transparent 60%);
        pointer-events: none;
    }

    .brand-hero-logo {
        height: 64px;
        width: auto;
        filter: drop-shadow(0 4px 8px rgba(0,0,0,0.3));
    }

    .hero-badge-pill {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: rgba(255, 255, 255, 0.2);
        backdrop-filter: blur(8px);
        padding: 6px 14px;
        border-radius: 50px;
        font-size: 0.8rem;
        font-weight: 700;
        color: #ffffff !important;
        border: 1px solid rgba(255, 255, 255, 0.35);
    }

    .feature-list-item {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 1rem;
        font-size: 0.9rem;
        color: #ffffff !important;
        font-weight: 500;
    }

    .feature-icon-box {
        width: 34px;
        height: 34px;
        border-radius: 8px;
        background: rgba(255, 255, 255, 0.22);
        display: flex;
        align-items: center;
        justify-content: center;
        color: #ffffff !important;
        flex-shrink: 0;
    }

    /* Right Form Area */
    .auth-form-area {
        width: 480px;
        padding: 3rem 2.5rem;
        display: flex;
        flex-direction: column;
        justify-content: center;
        background: #ffffff;
    }

    @media (max-width: 991.98px) {
        .auth-hero-banner {
            display: none;
        }
        .auth-form-area {
            width: 100%;
            padding: 2.5rem 1.75rem;
        }
        .auth-card-container {
            max-width: 460px;
        }
    }

    .form-control-rspad {
        border: 1.5px solid #cbd5e1;
        border-radius: 10px;
        padding: 0.7rem 0.9rem;
        font-size: 0.9rem;
        transition: all 0.2s ease;
    }

    .form-control-rspad:focus {
        border-color: #2E5A3C;
        box-shadow: 0 0 0 4px rgba(46, 90, 60, 0.15);
        outline: none;
    }

    .password-toggle-btn {
        position: absolute;
        right: 12px;
        top: 50%;
        transform: translateY(-50%);
        background: transparent;
        border: none;
        color: #565c59;
        cursor: pointer;
        padding: 4px 6px;
        border-radius: 4px;
        transition: color 0.2s ease;
        z-index: 5;
    }

    .password-toggle-btn:hover {
        color: #2E5A3C;
    }

    .btn-rspad-auth {
        background: linear-gradient(135deg, #2E5A3C 0%, #3B6E4A 100%);
        color: #ffffff !important;
        font-weight: 600;
        padding: 0.75rem;
        border-radius: 10px;
        border: none;
        font-size: 0.95rem;
        box-shadow: 0 4px 12px rgba(46, 90, 60, 0.25);
        transition: all 0.2s ease;
    }

    .btn-rspad-auth:hover {
        background: linear-gradient(135deg, #23422E 0%, #2E5A3C 100%);
        box-shadow: 0 6px 16px rgba(46, 90, 60, 0.35);
        color: #ffffff !important;
        transform: translateY(-1px);
    }

    .demo-credentials-box {
        background: #F8FAFC;
        border: 1px solid #E2E8F0;
        border-radius: 10px;
        padding: 1rem;
        font-size: 0.8rem;
    }

    .demo-chip {
        display: inline-block;
        background: #E2E8F0;
        color: #334155;
        padding: 2px 8px;
        border-radius: 4px;
        font-size: 0.75rem;
        cursor: pointer;
        transition: all 0.2s ease;
        border: none;
    }

    .demo-chip:hover {
        background: #2E5A3C;
        color: #ffffff;
    }
</style>

<div class="auth-page-wrapper">
    <div class="auth-card-container">
        
        <!-- Left Hero Banner -->
        <div class="auth-hero-banner">
            <div>
                <div class="d-flex align-items-center gap-3 mb-4">
                    <img src="{{ asset('images/logo-rspad.png') }}" alt="RSPAD Logo" class="brand-hero-logo">
                    <div>
                        <h3 class="fw-bold text-white mb-0 tracking-wide">RSPAD</h3>
                        <p class="text-white mb-0 font-monospace small opacity-90">GATOT SOEBROTO</p>
                    </div>
                </div>

                <div class="mb-4">
                    <span class="hero-badge-pill mb-3">
                        <i class="bi bi-shield-check"></i> Portal Pelaporan Resmi
                    </span>
                    <h4 class="fw-bold text-white lh-base mb-2">Sistem Rekapitulasi Pelaporan Rawat Jalan</h4>
                    <p class="text-white small mb-0 opacity-90">Integrasi data kunjungan pasien rawat jalan SIMRS RSPAD Gatot Soebroto dan Laporan Puskesad.</p>
                </div>
            </div>

            <div class="mt-4">
                <div class="feature-list-item">
                    <div class="feature-icon-box"><i class="bi bi-file-earmark-excel-fill"></i></div>
                    <span>Ekspor Excel 31 Kolom Sesuai Standardisasi Form</span>
                </div>
                <div class="feature-list-item">
                    <div class="feature-icon-box"><i class="bi bi-pie-chart-fill"></i></div>
                    <span>Klasifikasi Otomatis Kelompok TNI AD & Umum</span>
                </div>
                <div class="feature-list-item">
                    <div class="feature-icon-box"><i class="bi bi-lock-fill"></i></div>
                    <span>Akses Berbasis Peran (Petugas & Admin)</span>
                </div>
            </div>

            <div class="pt-4 border-top border-white border-opacity-20 text-white extra-small opacity-90">
                &copy; {{ date('Y') }} Subdit Pelaporan Medis RSPAD Gatot Soebroto.
            </div>
        </div>

        <!-- Right Form Area -->
        <div class="auth-form-area">

            <!-- Mobile Logo Header -->
            <div class="d-lg-none text-center mb-4">
                <img src="{{ asset('images/logo-rspad.png') }}" alt="RSPAD Logo" style="height: 50px;">
                <h5 class="fw-bold text-success mb-0 mt-2">RSPAD GATOT SOEBROTO</h5>
                <p class="text-muted small">Sistem Pelaporan Rawat Jalan</p>
            </div>

            <div class="mb-4">
                <h4 class="fw-bold text-dark mb-1">Masuk Akun</h4>
                <p class="text-muted small">Silakan masukkan email dan kata sandi Anda.</p>
            </div>

            @if(session('success'))
                <div class="alert alert-success border-0 shadow-sm small mb-3 py-2 px-3" style="background-color: #E8F5E9; color: #2A6A2A;">
                    <i class="bi bi-check-circle-fill me-1"></i> {{ session('success') }}
                </div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger border-0 shadow-sm small mb-3 py-2 px-3">
                    <i class="bi bi-exclamation-triangle-fill me-1"></i> {{ $errors->first() }}
                </div>
            @endif

            <form action="{{ route('login.post') }}" method="POST">
                @csrf

                <!-- Email Input -->
                <div class="mb-3">
                    <label for="email" class="form-label fw-semibold small text-secondary">Alamat Email</label>
                    <div class="position-relative">
                        <input type="email" class="form-control form-control-rspad ps-5 @error('email') is-invalid @enderror" 
                               id="email" name="email" value="{{ old('email') }}" 
                               placeholder="nama@rspad.go.id" required autofocus>
                        <i class="bi bi-envelope text-muted position-absolute start-0 top-50 translate-middle-y ms-3"></i>
                    </div>
                </div>

                <!-- Password Input with Eye Toggle Icon -->
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label for="password" class="form-label fw-semibold small text-secondary mb-0">Kata Sandi</label>
                    </div>
                    <div class="position-relative">
                        <input type="password" class="form-control form-control-rspad ps-5 pe-5 @error('password') is-invalid @enderror" 
                               id="password" name="password" placeholder="••••••••" required>
                        <i class="bi bi-lock text-muted position-absolute start-0 top-50 translate-middle-y ms-3"></i>
                        <button type="button" class="password-toggle-btn" onclick="togglePassword('password', this)" title="Tampilkan/Sembunyikan Kata Sandi">
                            <i class="bi bi-eye-slash fs-6"></i>
                        </button>
                    </div>
                </div>

                <!-- Remember Me -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="remember" name="remember">
                        <label class="form-check-label small text-muted" for="remember">Ingat Sesi Login</label>
                    </div>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="btn btn-rspad-auth w-100 mb-3">
                    <i class="bi bi-box-arrow-in-right me-1"></i> Masuk Akun
                </button>
            </form>

            <!-- Register Link -->
            <div class="text-center mb-4">
                <span class="text-muted small">Belum memiliki akun? </span>
                <a href="{{ route('register') }}" class="fw-semibold text-decoration-none" style="color: #2E5A3C;">Daftar Akun Baru</a>
            </div>

            <!-- Quick Demo Credentials Selector -->
            <div class="demo-credentials-box">
                <div class="fw-bold text-dark mb-2 d-flex align-items-center justify-content-between">
                    <span><i class="bi bi-key-fill text-warning me-1"></i> Akun Akses Uji Coba:</span>
                    <span class="text-muted extra-small">Klik untuk isi otomatis</span>
                </div>
                <div class="d-flex flex-wrap gap-1 mb-2">
                    <button type="button" class="demo-chip" onclick="fillDemo('petugas@rspad.go.id', 'password123')">Petugas</button>
                    <button type="button" class="demo-chip" onclick="fillDemo('admin@rspad.go.id', 'password123')">Admin</button>
                </div>
                <div class="text-muted" style="font-size: 0.75rem;">
                    Password default: <code class="bg-white px-1 border rounded">password123</code>
                </div>
            </div>

        </div>

    </div>
</div>
@endsection

@section('scripts')
<script>
    function togglePassword(inputId, btn) {
        const input = document.getElementById(inputId);
        const icon = btn.querySelector('i');
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.remove('bi-eye-slash');
            icon.classList.add('bi-eye');
        } else {
            input.type = 'password';
            icon.classList.remove('bi-eye');
            icon.classList.add('bi-eye-slash');
        }
    }

    function fillDemo(email, password) {
        document.getElementById('email').value = email;
        document.getElementById('password').value = password;
    }
</script>
@endsection
