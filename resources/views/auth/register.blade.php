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

                <div class="mb-4">
                    <span class="hero-badge-pill mb-3">
                        <i class="bi bi-person-plus-fill"></i> Pendaftaran Akun Pengguna
                    </span>
                    <h4 class="fw-bold text-white lh-base mb-2">Registrasi Petugas & Pengelola Pelaporan</h4>
                    <p class="text-white small mb-0 opacity-90">Daftarkan akun baru untuk mengelola dan memantau pelaporan kunjungan pasien rawat jalan.</p>
                </div>
            </div>

            <div class="mt-4">
                <div class="feature-list-item">
                    <div class="feature-icon-box"><i class="bi bi-person-badge-fill"></i></div>
                    <span>Peran Petugas Input atau Admin Sistem</span>
                </div>
                <div class="feature-list-item">
                    <div class="feature-icon-box"><i class="bi bi-shield-lock-fill"></i></div>
                    <span>Enkripsi Kata Sandi Standar Keamanan RSPAD</span>
                </div>
                <div class="feature-list-item">
                    <div class="feature-icon-box"><i class="bi bi-speedometer2"></i></div>
                    <span>Akses Langsung ke Dashboard & Fitur Ekspor</span>
                </div>
            </div>

            <div class="pt-4 border-top border-white border-opacity-20 text-white extra-small opacity-90">
                &copy; {{ date('Y') }} Subdit Pelaporan Medis RSPAD Gatot Soebroto.
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

            <div class="mb-3">
                <h4 class="fw-bold text-dark mb-1">Daftar Akun Baru</h4>
                <p class="text-muted small mb-0">Lengkapi formulir di bawah ini untuk membuat akun baru.</p>
            </div>

            @if($errors->any())
                <div class="alert alert-danger border-0 shadow-sm small mb-3 py-2 px-3">
                    <i class="bi bi-exclamation-triangle-fill me-1"></i> {{ $errors->first() }}
                </div>
            @endif

            <form action="{{ route('register.post') }}" method="POST">
                @csrf

                <!-- Nama Lengkap -->
                <div class="mb-2.5">
                    <label for="name" class="form-label fw-semibold small text-secondary mb-1">Nama Lengkap</label>
                    <div class="position-relative">
                        <input type="text" class="form-control form-control-rspad ps-5 @error('name') is-invalid @enderror" 
                               id="name" name="name" value="{{ old('name') }}" 
                               placeholder="Contoh: dr. Fara Kusuma" required autofocus>
                        <i class="bi bi-person text-muted position-absolute start-0 top-50 translate-middle-y ms-3"></i>
                    </div>
                </div>

                <!-- Email Input -->
                <div class="mb-2.5">
                    <label for="email" class="form-label fw-semibold small text-secondary mb-1">Alamat Email</label>
                    <div class="position-relative">
                        <input type="email" class="form-control form-control-rspad ps-5 @error('email') is-invalid @enderror" 
                               id="email" name="email" value="{{ old('email') }}" 
                               placeholder="nama@rspad.go.id" required>
                        <i class="bi bi-envelope text-muted position-absolute start-0 top-50 translate-middle-y ms-3"></i>
                    </div>
                </div>

                <!-- Peran / Jabatan & NIP/NRP -->
                <div class="row g-2 mb-2.5">
                    <div class="col-md-6">
                        <label for="role" class="form-label fw-semibold small text-secondary mb-1">Peran Akses</label>
                        <div class="position-relative">
                            <select class="form-select form-control-rspad ps-5 @error('role') is-invalid @enderror" id="role" name="role" required>
                                <option value="petugas" {{ old('role') === 'petugas' ? 'selected' : '' }}>Petugas Input</option>
                                <option value="admin" {{ old('role') === 'admin' ? 'selected' : '' }}>Admin Sistem</option>
                            </select>
                            <i class="bi bi-person-badge text-muted position-absolute start-0 top-50 translate-middle-y ms-3"></i>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label for="nip_nrp" class="form-label fw-semibold small text-secondary mb-1">NIP / NRP <span class="fw-normal text-muted">(Opsional)</span></label>
                        <div class="position-relative">
                            <input type="text" class="form-control form-control-rspad ps-5 @error('nip_nrp') is-invalid @enderror" 
                                   id="nip_nrp" name="nip_nrp" value="{{ old('nip_nrp') }}" 
                                   placeholder="199203152018012002">
                            <i class="bi bi-card-heading text-muted position-absolute start-0 top-50 translate-middle-y ms-3"></i>
                        </div>
                    </div>
                </div>

                <!-- Password Input with Eye Toggle Icon -->
                <div class="mb-2.5">
                    <label for="password" class="form-label fw-semibold small text-secondary mb-1">Kata Sandi</label>
                    <div class="position-relative">
                        <input type="password" class="form-control form-control-rspad ps-5 pe-5 @error('password') is-invalid @enderror" 
                               id="password" name="password" placeholder="Minimal 6 karakter" required>
                        <i class="bi bi-lock text-muted position-absolute start-0 top-50 translate-middle-y ms-3"></i>
                        <button type="button" class="password-toggle-btn" onclick="togglePassword('password', this)" title="Tampilkan/Sembunyikan Kata Sandi">
                            <i class="bi bi-eye-slash fs-6"></i>
                        </button>
                    </div>
                </div>

                <!-- Konfirmasi Password with Eye Toggle Icon -->
                <div class="mb-3.5">
                    <label for="password_confirmation" class="form-label fw-semibold small text-secondary mb-1">Konfirmasi Kata Sandi</label>
                    <div class="position-relative">
                        <input type="password" class="form-control form-control-rspad ps-5 pe-5" 
                               id="password_confirmation" name="password_confirmation" placeholder="Ulangi kata sandi Anda" required>
                        <i class="bi bi-shield-lock text-muted position-absolute start-0 top-50 translate-middle-y ms-3"></i>
                        <button type="button" class="password-toggle-btn" onclick="togglePassword('password_confirmation', this)" title="Tampilkan/Sembunyikan Kata Sandi">
                            <i class="bi bi-eye-slash fs-6"></i>
                        </button>
                    </div>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="btn btn-rspad-auth w-100 mb-3 mt-2">
                    <i class="bi bi-person-check-fill me-1"></i> Daftar Akun Baru
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
</script>
@endsection
