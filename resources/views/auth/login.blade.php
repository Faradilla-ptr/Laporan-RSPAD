@extends('layouts.app')

@section('title', 'Login Pengguna')

@section('content')
<div class="row justify-content-center my-5 py-3">
    <div class="col-md-4">
        <div class="card-panel p-4">
            <div class="text-center pb-3 mb-4 border-bottom">
                <img src="{{ asset('images/logo-rspad.png') }}" alt="RSPAD Logo" class="mb-2" style="height: 54px; width: auto; object-fit: contain;">
                <h5 class="fw-extrabold mb-0 text-uppercase" style="color: var(--palette-5); font-weight: 800; letter-spacing: 0.03em;">RSPAD</h5>
                <p class="fw-semibold mb-0" style="color: var(--palette-4); font-size: 0.95rem;">Gatot Soebroto</p>
            </div>

            <form action="{{ route('login.post') }}" method="POST">
                @csrf
                <div class="mb-3">
                    <label for="email" class="form-label fw-medium small">Alamat Email</label>
                    <input type="email" class="form-control form-control-sm @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email') }}" placeholder="email@rspad.go.id" required autofocus>
                    @error('email')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label fw-medium small">Kata Sandi</label>
                    <input type="password" class="form-control form-control-sm @error('password') is-invalid @enderror" id="password" name="password" placeholder="••••••••" required>
                    @error('password')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-4 form-check">
                    <input type="checkbox" class="form-check-input" id="remember" name="remember">
                    <label class="form-check-label small text-muted" for="remember">Ingat Sesi Login</label>
                </div>

                <button type="submit" class="btn btn-rspad-primary w-100 py-2 btn-sm fw-medium">
                    Masuk Akun
                </button>
            </form>

            <div class="mt-4 pt-3 border-top bg-light p-3 rounded text-muted small">
                <span class="fw-semibold text-dark d-block mb-1">Akun Akses Uji Coba:</span>
                <div class="font-monospace" style="font-size: 0.775rem;">
                    <div>Petugas: <strong>petugas@rspad.go.id</strong></div>
                    <div>Admin: <strong>admin@rspad.go.id</strong></div>
                    <div>Pimpinan: <strong>pimpinan@rspad.go.id</strong></div>
                    <div class="mt-1 text-secondary">Password: <code>password123</code></div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
