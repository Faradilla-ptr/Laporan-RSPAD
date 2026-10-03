@extends('layouts.app')

@section('title', 'Pendaftaran Akun - SIMRS RSPAD')

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
        max-width: 980px;
        display: flex;
        border: 1px solid #e2e8f0;
    }

    /* Left Hero Banner */
    .auth-hero-banner {
        flex: 1.05;
        background: linear-gradient(160deg, #1f3b28 0%, #2b5438 100%);
        color: #ffffff;
        padding: 3rem 2.8rem;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        position: relative;
        overflow: hidden;
    }

    /* Watermark samar */
    .hero-watermark {
        position: absolute;
        right: -30px;
        bottom: -30px;
        width: 250px;
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
        color: rgba(255, 255, 255, 0.82);
        font-size: 0.9rem;
        line-height: 1.6;
        margin-bottom: 0;
    }

    /* Right Form Area */
    .auth-form-area {
        width: 520px;
        padding: 3rem 2.8rem;
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
            padding: 2.2rem 1.75rem;
        }
        .auth-card-container {
            max-width: 480px;
        }
    }

    .form-control-rspad {
        border: 1.5px solid #cbd5e1;
        border-radius: 9px;
        padding: 0.65rem 0.9rem;
        font-size: 0.88rem;
        transition: all 0.2s ease;
    }

    .form-control-rspad:focus {
        border-color: #2b5438;
        box-shadow: 0 0 0 3.5px rgba(43, 84, 56, 0.15);
        outline: none;
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
        
        <!-- Left Hero Banner -->
        <div class="auth-hero-banner">
            <!-- Watermark -->
            <img src="{{ asset('images/logo-rspad.png') }}" alt="" class="hero-watermark">

            <!-- Logo & Identitas Rumah Sakit -->
            <div class="d-flex align-items-center gap-3">
                <img src="{{ asset('images/logo-rspad.png') }}" alt="RSPAD Logo" class="brand-hero-logo">
                <div class="lh-sm">
                    <div class="text-white fw-bold" style="font-size: 0.95rem; letter-spacing: 0.02em;">RSPAD GATOT SOEBROTO</div>
                    <div class="text-white-50 small" style="font-size: 0.8rem;">Puskesad &bull;</div>
                </div>
            </div>

            <!-- Konten Tengah -->
            <div class="my-auto py-4">
                <span class="hero-tag">PENDAFTARAN AKSES</span>
                <h2 class="hero-headline">
                    REGISTRASI PETUGAS PELAPORAN
                </h2>
                <p class="hero-desc">
                    Ajukan akun dinas untuk mengelola rekapitulasi kunjungan dan pengunjung secara resmi.
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
            <div class="d-lg-none text-center mb-3">
                <img src="{{ asset('images/logo-rspad.png') }}" alt="RSPAD Logo" style="height: 48px;">
                <h6 class="fw-bold text-success mb-0 mt-2">RSPAD GATOT SOEBROTO</h6>
                <p class="text-muted small">Registrasi Akun Petugas</p>
            </div>

            <div class="mb-4">
                <h4 class="fw-bold text-dark mb-1">Daftar Akun Baru</h4>
                <p class="text-muted small mb-0">Isi formulir berikut untuk mengajukan akun petugas pelaporan.</p>
            </div>

            @if($errors->any())
                <div class="alert alert-danger border-0 small mb-3 py-2 px-3 d-flex align-items-center gap-2">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg> 
                    <span>{{ $errors->first() }}</span>
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
                               placeholder="Nama beserta gelar" required autofocus>
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-muted position-absolute start-0 top-50 translate-middle-y ms-3"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                    </div>
                </div>

                <!-- Email Input -->
                <div class="mb-3">
                    <label for="email" class="form-label fw-semibold small text-secondary mb-1">Alamat Email Dinas</label>
                    <div class="position-relative">
                        <input type="email" class="form-control form-control-rspad ps-5 @error('email') is-invalid @enderror" 
                               id="email" name="email" value="{{ old('email') }}" 
                               placeholder="nama@rspad.go.id" required>
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-muted position-absolute start-0 top-50 translate-middle-y ms-3"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
                    </div>
                    <div class="text-muted small mt-1" style="font-size: 0.75rem;">Akses dan kata sandi sementara akan dikirimkan ke email ini.</div>
                </div>

                <!-- Peran / Jabatan & NIP/NRP -->
                <div class="row g-2 mb-4">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold small text-secondary mb-1">Hak Akses</label>
                        <div class="position-relative">
                            <input type="text" class="form-control form-control-rspad ps-5 bg-light" value="Petugas Pelaporan" readonly style="cursor: not-allowed;">
                            <input type="hidden" name="role" value="petugas">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-muted position-absolute start-0 top-50 translate-middle-y ms-3"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label for="nip_nrp" class="form-label fw-semibold small text-secondary mb-1">NIP / NRP <span class="fw-normal text-muted">(Opsional)</span></label>
                        <div class="position-relative">
                            <input type="text" class="form-control form-control-rspad ps-5 @error('nip_nrp') is-invalid @enderror" 
                                   id="nip_nrp" name="nip_nrp" value="{{ old('nip_nrp') }}" 
                                   placeholder="Nomor identitas">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-muted position-absolute start-0 top-50 translate-middle-y ms-3"><rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect><line x1="8" y1="21" x2="16" y2="21"></line><line x1="12" y1="17" x2="12" y2="21"></line></svg>
                        </div>
                    </div>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="btn btn-rspad-auth w-100 mb-3 d-flex align-items-center justify-content-center gap-2">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><line x1="20" y1="8" x2="20" y2="14"></line><line x1="23" y1="11" x2="17" y2="11"></line></svg> 
                    <span>Kirim Pengajuan Akun</span>
                </button>
            </form>

            <!-- Login Link -->
            <div class="text-center">
                <span class="text-muted small">Sudah memiliki akun? </span>
                <a href="{{ route('login') }}" class="fw-semibold text-decoration-none" style="color: #2b5438;">Masuk di sini</a>
            </div>

        </div>

    </div>
</div>
@endsection