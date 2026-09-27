@extends('layouts.app')

@section('title', 'Validasi & Pengelolaan Akun Petugas')

@section('content')
<div class="card-panel p-4 mb-4">
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 border-bottom pb-3 mb-3">
        <div>
            <h5 class="fw-bold mb-1 text-dark">Validasi & Pengelolaan Akun Pengguna</h5>
            <p class="text-muted small mb-0">Persetujuan pendaftaran akun Petugas baru oleh Admin (Kaur) dan daftar akun aktif</p>
        </div>
    </div>

    <!-- Tab 1: Pendaftaran Menunggu Persetujuan (Pending Approval) -->
    <div class="mb-4">
        <div class="d-flex align-items-center gap-2 mb-3">
            <h6 class="fw-bold text-dark mb-0">Antrean Validasi Akun Petugas Baru</h6>
            <span class="badge bg-warning text-dark rounded-pill">{{ count($pendingUsers) }} Menunggu</span>
        </div>

        @if(count($pendingUsers) === 0)
            <div class="alert alert-light border small text-muted d-flex align-items-center gap-2">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                Tidak ada antrean pendaftaran akun Petugas baru yang membutuhkan validasi saat ini.
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-clean table-bordered align-middle text-nowrap">
                    <thead>
                        <tr>
                            <th style="width: 50px;">No</th>
                            <th>Nama Lengkap</th>
                            <th>Email</th>
                            <th>NIP / NRP</th>
                            <th>Tanggal Daftar</th>
                            <th style="width: 180px;" class="text-center">Aksi Validasi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($pendingUsers as $index => $u)
                            <tr>
                                <td class="text-center">{{ $index + 1 }}</td>
                                <td>
                                    <span class="fw-semibold text-dark">{{ $u->name }}</span>
                                </td>
                                <td>{{ $u->email }}</td>
                                <td>{{ $u->nip_nrp ?: '-' }}</td>
                                <td>{{ $u->created_at->format('d M Y H:i') }}</td>
                                <td class="text-center">
                                    <div class="d-flex justify-content-center gap-1">
                                        <form action="{{ route('admin.users.approve', $u->id) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-success py-1 px-3 d-flex align-items-center gap-1" title="Setujui Akun Ini">
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg> Disetujui
                                            </button>
                                        </form>
                                        <form action="{{ route('admin.users.reject', $u->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Tolak dan hapus pendaftaran akun ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger py-1 px-2 d-flex align-items-center gap-1" title="Tolak Pendaftaran">
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg> Tolak
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <!-- Tab 2: Antrean Permohonan Ubah Profil / Password Petugas -->
    <div class="mb-4 pt-3 border-top">
        <div class="d-flex align-items-center gap-2 mb-3">
            <h6 class="fw-bold text-dark mb-0">Antrean Permohonan Perubahan Profil & Password Petugas</h6>
            <span class="badge bg-info text-dark rounded-pill">{{ isset($pendingProfileRequests) ? count($pendingProfileRequests) : 0 }} Permohonan</span>
        </div>

        @if(!isset($pendingProfileRequests) || count($pendingProfileRequests) === 0)
            <div class="alert alert-light border small text-muted d-flex align-items-center gap-2">
                <i class="bi bi-check-circle"></i> Tidak ada permohonan perubahan profil atau password dari Petugas saat ini.
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-clean table-bordered align-middle text-nowrap">
                    <thead class="bg-light">
                        <tr>
                            <th style="width: 50px;">No</th>
                            <th>Nama Petugas</th>
                            <th>Email Akun</th>
                            <th>Jenis Permohonan</th>
                            <th>Data Baru Yang Diajukan</th>
                            <th>Waktu Pengajuan</th>
                            <th style="width: 180px;" class="text-center">Aksi Verifikasi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($pendingProfileRequests as $pIdx => $pReq)
                            <tr>
                                <td class="text-center">{{ $pIdx + 1 }}</td>
                                <td class="fw-semibold text-dark">{{ $pReq->user->name ?? '-' }}</td>
                                <td>{{ $pReq->user->email ?? '-' }}</td>
                                <td>
                                    <span class="badge bg-light text-dark border fw-bold">
                                        {{ $pReq->request_type === 'CHANGE_PASSWORD' ? 'Ubah Password' : 'Ubah Data Profil' }}
                                    </span>
                                </td>
                                <td class="small">
                                    @if($pReq->new_name && $pReq->new_name !== ($pReq->user->name ?? ''))
                                        <div>Nama: <strong>{{ $pReq->new_name }}</strong></div>
                                    @endif
                                    @if($pReq->new_email && $pReq->new_email !== ($pReq->user->email ?? ''))
                                        <div>Email: <strong>{{ $pReq->new_email }}</strong></div>
                                    @endif
                                    @if($pReq->new_password)
                                        <div class="text-success fw-bold"><i class="bi bi-key-fill me-1"></i>Permohonan Kata Sandi Baru</div>
                                    @endif
                                </td>
                                <td class="small text-muted">{{ $pReq->created_at ? $pReq->created_at->format('d M Y H:i') : '-' }}</td>
                                <td class="text-center">
                                    <div class="d-flex justify-content-center gap-1">
                                        <form action="{{ route('admin.profile_requests.approve', $pReq->id) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-success py-1 px-3 d-flex align-items-center gap-1">
                                                <i class="bi bi-check-lg"></i> Setujui
                                            </button>
                                        </form>
                                        <form action="{{ route('admin.profile_requests.reject', $pReq->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Tolak permohonan perubahan profil ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger py-1 px-2 d-flex align-items-center gap-1">
                                                <i class="bi bi-x-lg"></i> Tolak
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <!-- Tab 3: Daftar Akun Terverifikasi -->
    <div class="mt-4 pt-3 border-top">
        <h6 class="fw-bold text-dark mb-3">Daftar Akun Terverifikasi / Aktif</h6>
        <div class="table-responsive">
            <table class="table table-clean table-bordered align-middle text-nowrap">
                <thead>
                    <tr>
                        <th style="width: 50px;">No</th>
                        <th>Nama Lengkap</th>
                        <th>Email</th>
                        <th>Peran Akses</th>
                        <th>NIP / NRP</th>
                        <th>Status Validasi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($approvedUsers as $index => $u)
                        <tr>
                            <td class="text-center">{{ $approvedUsers->firstItem() + $index }}</td>
                            <td class="fw-semibold text-dark">{{ $u->name }}</td>
                            <td>{{ $u->email }}</td>
                            <td>
                                <span class="badge {{ $u->role === 'admin' ? 'bg-primary' : 'bg-secondary' }}">
                                    {{ strtoupper($u->role) }}
                                </span>
                            </td>
                            <td>{{ $u->nip_nrp ?: '-' }}</td>
                            <td>
                                <span class="badge bg-success d-inline-flex align-items-center gap-1">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg> Terverifikasi
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-3">
            {{ $approvedUsers->links() }}
        </div>
    </div>
</div>
@endsection
