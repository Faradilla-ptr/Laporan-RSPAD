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
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-1"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path><path d="M9 12l2 2 4-4"></path></svg> Portal Pelaporan Resmi
                    </span>
                    <h4 class="fw-bold text-white lh-base mb-2">Sistem Rekapitulasi Pelaporan Rawat Jalan</h4>
                    <p class="text-white small mb-0 opacity-90">Integrasi data kunjungan pasien rawat jalan SIMRS RSPAD Gatot Soebroto dan Laporan Puskesad.</p>
                </div>
            </div>

            <div class="mt-4">
                <div class="feature-list-item">
                    <div class="feature-icon-box">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="8" y1="13" x2="16" y2="17"></line><line x1="16" y1="13" x2="8" y2="17"></line></svg>
                    </div>
                    <span>Ekspor Excel 31 Kolom Sesuai Standardisasi Form</span>
                </div>
                <div class="feature-list-item">
                    <div class="feature-icon-box">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.21 15.89A10 10 0 1 1 8 2.83"></path><path d="M22 12A10 10 0 0 0 12 2v10z"></path></svg>
                    </div>
                    <span>Klasifikasi Otomatis Kelompok TNI AD & Umum</span>
                </div>
                <div class="feature-list-item">
                    <div class="feature-icon-box">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                    </div>
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
                <div class="alert alert-success border-0 shadow-sm small mb-3 py-2 px-3 d-flex align-items-center gap-2" style="background-color: #E8F5E9; color: #2A6A2A;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg> {{ session('success') }}
                </div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger border-0 shadow-sm small mb-3 py-2 px-3 d-flex align-items-center gap-2">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg> {{ $errors->first() }}
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
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-muted position-absolute start-0 top-50 translate-middle-y ms-3"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
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
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-muted position-absolute start-0 top-50 translate-middle-y ms-3"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                        <button type="button" class="password-toggle-btn d-flex align-items-center justify-content-center" onclick="togglePassword('password', this)" title="Tampilkan/Sembunyikan Kata Sandi">
                            <svg id="eyeIcon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>
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
                <button type="submit" class="btn btn-rspad-auth w-100 mb-3 d-flex align-items-center justify-content-center gap-2">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path><polyline points="10 17 15 12 10 7"></polyline><line x1="15" y1="12" x2="3" y2="12"></line></svg> Masuk Akun
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
                    <span class="d-flex align-items-center gap-1"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color: #d97706;"><path d="M21 2l-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777zm0 0L15.5 7.5m0 0l3 3L22 7l-3-3m-3.5 3.5L19 4"></path></svg> Akun Akses Uji Coba:</span>
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
        const eyeSvg = document.getElementById('eyeIcon');
        if (input.type === 'password') {
            input.type = 'text';
            eyeSvg.innerHTML = '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle>';
        } else {
            input.type = 'password';
            eyeSvg.innerHTML = '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line>';
        }
    }

    function fillDemo(email, password) {
        document.getElementById('email').value = email;
        document.getElementById('password').value = password;
    }
</script>
@endsection
