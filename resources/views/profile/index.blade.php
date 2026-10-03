@extends('layouts.app')

@section('title', 'Profil Pengguna')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8 col-md-10">
        
        <!-- Header Card -->
        <div class="card-panel p-4 mb-4">
            <div class="d-flex align-items-center gap-3 border-bottom pb-3">
                <div class="rounded-circle text-white d-flex align-items-center justify-content-center fw-bold fs-4 shadow-sm" style="width: 52px; height: 52px; background: linear-gradient(135deg, #2E5A3C 0%, #3B6E4A 100%);">
                    {{ strtoupper(substr($user->name, 0, 1)) }}
                </div>
                <div>
                    <h5 class="fw-bold text-dark mb-0">{{ $user->name }}</h5>
                    <div class="d-flex align-items-center gap-2 mt-1">
                        <span class="badge {{ $user->role === 'admin' ? 'bg-primary' : 'bg-success' }} px-2 py-1">
                            {{ strtoupper($user->role) }} {{ $user->role === 'admin' ? '(KAUR / ADMIN)' : '(PETUGAS INPUT)' }}
                        </span>
                        <span class="text-muted small">{{ $user->email }}</span>
                    </div>
                </div>
            </div>

            @if(session('success'))
                <div class="alert alert-success border-0 small my-3 py-2 px-3" style="background-color: #E8F5E9; color: #2A6A2A;">
                    {{ session('success') }}
                </div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger border-0 small my-3 py-2 px-3">
                    {{ $errors->first() }}
                </div>
            @endif

            <!-- Petugas Pending Request Warning -->
            @if($user->role === 'petugas' && isset($pendingRequest) && $pendingRequest)
                <div class="alert alert-warning border-0 small my-3 py-3 px-3" style="background-color: #FFF8E1; color: #856404;">
                    <div>
                        <strong>Permohonan Perubahan Profil Dalam Antrean</strong><br>
                        Anda memiliki permohonan {{ $pendingRequest->request_type === 'CHANGE_PASSWORD' ? 'perubahan kata sandi' : 'pembaruan data profil' }} yang sedang menunggu validasi & persetujuan dari Admin (Kaur).
                    </div>
                </div>
            @endif

            <!-- Profile Edit Form -->
            <form action="{{ route('profile.update') }}" method="POST" class="mt-4">
                @csrf
                @method('PUT')

                <h6 class="fw-bold text-dark mb-3">Pengaturan Data Diri & Kata Sandi</h6>

                <!-- Nama Lengkap -->
                <div class="mb-3">
                    <label for="name" class="form-label fw-semibold small text-secondary">Nama Lengkap</label>
                    <input type="text" name="name" id="name" class="form-control form-control-sm" value="{{ old('name', $user->name) }}" required>
                </div>

                <!-- Email Address -->
                <div class="mb-3">
                    <label for="email" class="form-label fw-semibold small text-secondary">Alamat Email</label>
                    <input type="email" name="email" id="email" class="form-control form-control-sm" value="{{ old('email', $user->email) }}" required>
                    @if($user->role === 'petugas')
                        <span class="text-muted extra-small d-block mt-1">Perubahan email atau kata sandi oleh Petugas memerlukan persetujuan Admin.</span>
                    @endif
                </div>

                <!-- NIP / NRP -->
                <div class="mb-3">
                    <label for="nip_nrp" class="form-label fw-semibold small text-secondary">NIP / NRP <span class="fw-normal text-muted">(Opsional)</span></label>
                    <input type="text" name="nip_nrp" id="nip_nrp" class="form-control form-control-sm" value="{{ old('nip_nrp', $user->nip_nrp) }}" placeholder="199203152018012002">
                </div>

                <hr class="my-4">

                <h6 class="fw-bold text-dark mb-3">Ubah Kata Sandi Login</h6>

                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <label for="new_password" class="form-label fw-semibold small text-secondary">Kata Sandi Baru <span class="fw-normal text-muted">(Kosongkan jika tidak diubah)</span></label>
                        <input type="password" name="new_password" id="new_password" class="form-control form-control-sm" placeholder="Minimal 6 karakter">
                    </div>
                    <div class="col-md-6">
                        <label for="new_password_confirmation" class="form-label fw-semibold small text-secondary">Konfirmasi Kata Sandi Baru</label>
                        <input type="password" name="new_password_confirmation" id="new_password_confirmation" class="form-control form-control-sm" placeholder="Ulangi kata sandi baru">
                    </div>
                </div>

                <div class="d-flex justify-content-end mt-4">
                    <button type="submit" class="btn btn-sm btn-rspad-primary px-4 py-2 fw-medium shadow-sm">
                        {{ $user->role === 'admin' ? 'Simpan Perubahan' : 'Ajukan Perubahan ke Admin' }}
                    </button>
                </div>
            </form>
        </div>

        <!-- Recent Profile Requests History (For Petugas) -->
        @if($user->role === 'petugas' && isset($recentRequests) && count($recentRequests) > 0)
        <div class="card-panel p-4 mb-4">
            <h6 class="fw-bold text-dark mb-3"><i class="bi bi-clock-history me-2" style="color: var(--palette-5);"></i>Riwayat Permohonan Perubahan Profil</h6>
            <div class="table-responsive">
                <table class="table table-clean table-bordered align-middle small text-nowrap">
                    <thead>
                        <tr>
                            <th>Waktu Pengajuan</th>
                            <th>Jenis Permohonan</th>
                            <th>Nama Baru</th>
                            <th>Email Baru</th>
                            <th class="text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentRequests as $req)
                        <tr>
                            <td class="text-muted">{{ $req->created_at ? $req->created_at->format('d M Y H:i') : '-' }}</td>
                            <td>
                                <span class="badge bg-light text-dark border">
                                    {{ $req->request_type === 'CHANGE_PASSWORD' ? 'Ubah Password' : 'Ubah Data Profil' }}
                                </span>
                            </td>
                            <td>{{ $req->new_name ?: '-' }}</td>
                            <td>{{ $req->new_email ?: '-' }}</td>
                            <td class="text-center">
                                @if($req->status === 'approved')
                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">Disetujui Admin</span>
                                @elseif($req->status === 'rejected')
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1">Ditolak Admin</span>
                                @else
                                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-1">Menunggu Validasi</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

    </div>
</div>
@endsection
