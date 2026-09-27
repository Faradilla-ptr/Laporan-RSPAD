@extends('layouts.app')

@section('title', 'Pendaftaran Akun Baru')

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
        max-width: 980px;
        display: flex;
        position: relative;
        z-index: 10;
        border: 1px solid #e2e8f0;
    }

    /* Left Hero Banner */
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


    /* Right Form Area */
    .auth-form-area {
        width: 520px;
        padding: 2.5rem 2.5rem;
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
            padding: 2rem 1.5rem;
        }
        .auth-card-container {
            max-width: 500px;
        }
    }

    .form-control-rspad {
        border: 1.5px solid #cbd5e1;
        border-radius: 10px;
        padding: 0.65rem 0.9rem;
        font-size: 0.88rem;
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

                <div class="my-auto py-3">
                    <span class="hero-badge-pill mb-3" style="font-size: 0.9rem; padding: 8px 18px;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="me-1"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><line x1="20" y1="8" x2="20" y2="14"></line><line x1="23" y1="11" x2="17" y2="17"></line></svg> Pendaftaran Akun Pengguna
                    </span>
                    <h2 class="fw-bold text-white lh-sm mb-3 fs-2">Registrasi Petugas Pelaporan Medis</h2>
                    <p class="text-white fs-6 mb-4 opacity-90 lh-base">Daftarkan akun baru untuk mengelola, mengunggah, dan memantau rekapitulasi pelaporan kunjungan pasien rawat jalan SIMRS RSPAD Gatot Soebroto.</p>
                    
                    <div class="p-3.5 rounded-3 border border-white border-opacity-25" style="background: rgba(255,255,255,0.08);">
                        <div class="d-flex align-items-center gap-2 text-white fw-bold mb-1 fs-6">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg> Validasi & Keamanan Akun
                        </div>
                        <p class="text-white extra-small mb-0 opacity-85">Setiap pendaftaran akun Petugas akan melalui tahap pengesahan oleh Admin (Kaur). Kata sandi login default akan dikirim secara otomatis via Email setelah disetujui.</p>
                    </div>
                </div>
            </div>

            <div class="pt-3 border-top border-white border-opacity-20 text-white extra-small opacity-75">
                Subdit Pelaporan Medis RSPAD Gatot Soebroto &copy; {{ date('Y') }}
            </div>
        </div>

        <!-- Right Form Area -->
        <div class="auth-form-area">

            <!-- Mobile Logo Header -->
            <div class="d-lg-none text-center mb-3">
                <img src="{{ asset('images/logo-rspad.png') }}" alt="RSPAD Logo" style="height: 46px;">
                <h5 class="fw-bold text-success mb-0 mt-2">RSPAD GATOT SOEBROTO</h5>
                <p class="text-muted small">Registrasi Akun Baru</p>
            </div>

            <div class="mb-4">
                <h3 class="fw-bold text-dark mb-1">Daftar Akun Baru</h3>
                <p class="text-muted small mb-0">Lengkapi formulir nama dan email Anda untuk pengajuan akun Petugas.</p>
            </div>

            @if($errors->any())
                <div class="alert alert-danger border-0 shadow-sm small mb-3 py-2 px-3 d-flex align-items-center gap-2">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg> {{ $errors->first() }}
                </div>
            @endif

            <form action="{{ route('register.post') }}" method="POST">
                @csrf

                <!-- Nama Lengkap -->
                <div class="mb-3">
                    <label for="name" class="form-label fw-semibold small text-secondary mb-1">Nama Lengkap</label>
                    <div class="position-relative">
                        <input type="text" class="form-control form-control-rspad ps-5 @error('name') is-invalid @enderror" 
                               id="name" name="name" value="{{ old('name') }}" 
                               placeholder="Contoh: dr. Fara Kusuma" required autofocus>
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-muted position-absolute start-0 top-50 translate-middle-y ms-3"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                    </div>
                </div>

                <!-- Email Input -->
                <div class="mb-3">
                    <label for="email" class="form-label fw-semibold small text-secondary mb-1">Alamat Email Aktif</label>
                    <div class="position-relative">
                        <input type="email" class="form-control form-control-rspad ps-5 @error('email') is-invalid @enderror" 
                               id="email" name="email" value="{{ old('email') }}" 
                               placeholder="nama@rspad.go.id" required>
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-muted position-absolute start-0 top-50 translate-middle-y ms-3"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
                    </div>
                    <span class="text-muted extra-small d-block mt-1"><i class="bi bi-info-circle me-1"></i>Notifikasi persetujuan dan kata sandi login akan dikirim ke email ini.</span>
                </div>

                <!-- Peran / Jabatan & NIP/NRP -->
                <div class="row g-2 mb-4">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold small text-secondary mb-1">Peran Akses</label>
                        <div class="position-relative">
                            <input type="text" class="form-control form-control-rspad ps-5 bg-light" value="Petugas Input" readonly style="cursor: not-allowed;">
                            <input type="hidden" name="role" value="petugas">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-muted position-absolute start-0 top-50 translate-middle-y ms-3"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle></svg>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label for="nip_nrp" class="form-label fw-semibold small text-secondary mb-1">NIP / NRP <span class="fw-normal text-muted">(Opsional)</span></label>
                        <div class="position-relative">
                            <input type="text" class="form-control form-control-rspad ps-5 @error('nip_nrp') is-invalid @enderror" 
                                   id="nip_nrp" name="nip_nrp" value="{{ old('nip_nrp') }}" 
                                   placeholder="199203152018012002">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-muted position-absolute start-0 top-50 translate-middle-y ms-3"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect><line x1="8" y1="21" x2="16" y2="21"></line><line x1="12" y1="17" x2="12" y2="21"></line></svg>
                        </div>
                    </div>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="btn btn-rspad-auth w-100 mb-3 py-2.5 d-flex align-items-center justify-content-center gap-2">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><line x1="20" y1="8" x2="20" y2="14"></line><line x1="23" y1="11" x2="17" y2="11"></line></svg> Ajukan Pendaftaran Akun
                </button>
            </form>

            <!-- Login Link -->
            <div class="text-center">
                <span class="text-muted small">Sudah memiliki akun? </span>
                <a href="{{ route('login') }}" class="fw-semibold text-decoration-none" style="color: #2A6A2A;">Masuk di Sini</a>
            </div>

        </div>

    </div>
</div>
@endsection

@section('scripts')
<script>
    function togglePassword(inputId, btn) {
        const input = document.getElementById(inputId);
        const eyeSvg = btn.querySelector('svg');
        if (input.type === 'password') {
            input.type = 'text';
            if (eyeSvg) {
                eyeSvg.innerHTML = '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle>';
            }
        } else {
            input.type = 'password';
            if (eyeSvg) {
                eyeSvg.innerHTML = '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line>';
            }
        }
    }
</script>
@endsection
