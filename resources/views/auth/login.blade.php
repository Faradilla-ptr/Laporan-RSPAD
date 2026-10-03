@extends('layouts.app')

@section('title', 'Masuk Akun - SIMRS RSPAD')

@section('content')
<style>
    .auth-page-wrapper {
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #f1f5f9;
        padding: 2rem 1rem;
    }

    .auth-card-container {
        background: #ffffff;
        border-radius: 18px;
        box-shadow: 0 12px 35px -8px rgba(15, 23, 42, 0.12);
        overflow: hidden;
        width: 100%;
        max-width: 940px;
        display: flex;
        border: 1px solid #e2e8f0;
    }

    /* Left Hero Banner */
    .auth-hero-banner {
        flex: 1.1;
        background: linear-gradient(160deg, #1f3b28 0%, #2b5438 100%);
        color: #ffffff;
        padding: 3rem 2.8rem;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        position: relative;
        overflow: hidden;
    }

    /* Watermark halus di background agar tidak kosong */
    .hero-watermark {
        position: absolute;
        right: -30px;
        bottom: -30px;
        width: 240px;
        height: auto;
        opacity: 0.07;
        pointer-events: none;
    }

    .brand-hero-logo {
        height: 52px;
        width: auto;
    }

    .hero-tag {
        display: inline-block;
        font-size: 0.75rem;
        letter-spacing: 0.14em;
        text-transform: uppercase;
        font-weight: 700;
        color: #86efac;
        margin-bottom: 0.75rem;
    }

    .hero-headline {
        font-size: 1.75rem;
        font-weight: 700;
        line-height: 1.35;
        color: #ffffff;
        margin-bottom: 1rem;
    }

    .hero-desc {
        color: rgba(255, 255, 255, 0.8);
        font-size: 0.9rem;
        line-height: 1.6;
        margin-bottom: 0;
    }

    /* Right Form Area */
    .auth-form-area {
        width: 450px;
        padding: 3.2rem 2.8rem;
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
            max-width: 440px;
        }
    }

    .form-control-rspad {
        border: 1.5px solid #cbd5e1;
        border-radius: 9px;
        padding: 0.68rem 0.9rem;
        font-size: 0.9rem;
        transition: all 0.2s ease;
    }

    .form-control-rspad:focus {
        border-color: #2b5438;
        box-shadow: 0 0 0 3.5px rgba(43, 84, 56, 0.15);
        outline: none;
    }

    .password-toggle-btn {
        position: absolute;
        right: 12px;
        top: 50%;
        transform: translateY(-50%);
        background: transparent;
        border: none;
        color: #94a3b8;
        cursor: pointer;
        padding: 4px 6px;
        border-radius: 4px;
        transition: color 0.2s ease;
        z-index: 5;
    }

    .password-toggle-btn:hover {
        color: #2b5438;
    }

    .btn-rspad-auth {
        background: #2b5438;
        color: #ffffff !important;
        font-weight: 600;
        padding: 0.72rem;
        border-radius: 9px;
        border: none;
        font-size: 0.92rem;
        transition: all 0.2s ease;
    }

    .btn-rspad-auth:hover {
        background: #21412c;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(43, 84, 56, 0.25);
    }
</style>

<div class="auth-page-wrapper">
    <div class="auth-card-container">
        
        <!-- Left Hero Banner (Elegan & Pas) -->
        <div class="auth-hero-banner">
            <!-- Watermark samar di sudut latar -->
            <img src="{{ asset('images/logo-rspad.png') }}" alt="" class="hero-watermark">

            <!-- Logo & Identitas Rumah Sakit -->
            <div class="d-flex align-items-center gap-3">
                <img src="{{ asset('images/logo-rspad.png') }}" alt="RSPAD Logo" class="brand-hero-logo">
                <div class="lh-sm">
                    <div class="text-white fw-bold" style="font-size: 0.95rem; letter-spacing: 0.02em;">RSPAD GATOT SOEBROTO</div>
                    <div class="text-white-50 small" style="font-size: 0.8rem;">Puskesad &bull; </div>
                </div>
            </div>

            <!-- Konten Tengah: Tidak sepi, tidak kepenuhan -->
            <div class="my-auto py-4">
                <span class="hero-tag">SISTEM LAPORAN REKPITULASI</span>
                <h2 class="hero-headline">
                    KUNJUNGAN DAN PENGUNJUNG
                </h2>
                <p class="hero-desc">
                    RSPAD Gatot Soebroto Puskesad.
                </p>
            </div>

            <!-- Catatan Kaki Sisi Kiri -->
            <div class="pt-3 border-top border-white border-opacity-15 text-white-50 small" style="font-size: 0.78rem;">
                Sistem Informasi Manajemen Rumah Sakit &copy; {{ date('Y') }}
            </div>
        </div>

        <!-- Right Form Area -->
        <div class="auth-form-area">

            <!-- Mobile Logo Header -->
            <div class="d-lg-none text-center mb-4">
                <img src="{{ asset('images/logo-rspad.png') }}" alt="RSPAD Logo" style="height: 48px;">
                <h6 class="fw-bold text-success mb-0 mt-2">RSPAD GATOT SOEBROTO</h6>
                <p class="text-muted small">Rekapitulasi Kunjungan Pasien</p>
            </div>

            <!-- Form Title -->
            <div class="mb-4">
                <h4 class="fw-bold text-dark mb-1">Masuk Akun</h4>
                <p class="text-muted small">Gunakan email dinas dan kata sandi Anda.</p>
            </div>

            @if(session('success'))
                <div class="alert alert-success border-0 small mb-3 py-2 px-3 d-flex align-items-center gap-2" style="background-color: #E8F5E9; color: #2A6A2A;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg> {{ session('success') }}
                </div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger border-0 small mb-3 py-2 px-3 d-flex align-items-center gap-2">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg> {{ $errors->first() }}
                </div>
            @endif

            <form action="{{ route('login.post') }}" method="POST">
                @csrf

                <!-- Email Input -->
                <div class="mb-3">
                    <label for="email" class="form-label fw-semibold small text-secondary">Alamat Email Dinas</label>
                    <div class="position-relative">
                        <input type="email" class="form-control form-control-rspad ps-5 @error('email') is-invalid @enderror" 
                               id="email" name="email" value="{{ old('email') }}" 
                               placeholder="nama@rspad.go.id" required autofocus>
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-muted position-absolute start-0 top-50 translate-middle-y ms-3"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
                    </div>
                </div>

                <!-- Password Input with Toggle -->
                <div class="mb-3">
                    <label for="password" class="form-label fw-semibold small text-secondary mb-1">Kata Sandi</label>
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
                        <label class="form-check-label small text-muted" for="remember">Ingat sesi di perangkat ini</label>
                    </div>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="btn btn-rspad-auth w-100 mb-3 d-flex align-items-center justify-content-center gap-2">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path><polyline points="10 17 15 12 10 7"></polyline><line x1="15" y1="12" x2="3" y2="12"></line></svg> Masuk Akun
                </button>
            </form>

            <!-- Register Link -->
            <div class="text-center">
                <span class="text-muted small">Belum punya akun? </span>
                <a href="{{ route('register') }}" class="fw-semibold text-decoration-none" style="color: #2b5438;">Daftar di sini</a>
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
</script>
@endsection