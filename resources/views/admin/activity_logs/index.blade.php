@extends('layouts.app')

@section('title', 'Log Aktivitas Sistem (Trail Log)')

@section('content')
<div class="card-panel p-4 mb-4">
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 border-bottom pb-3 mb-3">
        <div>
            <h5 class="fw-bold mb-1 text-dark"><i class="bi bi-journal-text me-2" style="color: var(--palette-5);"></i>Log Aktivitas Pengguna (Trail Log)</h5>
            <p class="text-muted small mb-0">Catatan riwayat kegiatan, pengunggahan berkas, ekspor laporan, dan autentikasi pengguna</p>
        </div>
    </div>

    <!-- Search & Filter Bar -->
    <form action="{{ url()->current() }}" method="GET" class="row g-2 mb-3 align-items-center">
        <div class="col-md-8">
            <div class="input-group input-group-sm">
                <span class="input-group-text bg-white text-muted"><i class="bi bi-search"></i></span>
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Cari nama pengguna, jenis aksi, atau riwayat aktivitas..." value="{{ request('search') }}">
            </div>
        </div>
        <div class="col-md-4 d-flex gap-2">
            <button type="submit" class="btn btn-sm btn-rspad-primary w-100 fw-medium">
                <i class="bi bi-funnel-fill me-1"></i> Filter Log
            </button>
            @if(request('search'))
                <a href="{{ route('admin.activity_logs.index') }}" class="btn btn-sm btn-outline-secondary px-3 fw-medium">Reset</a>
            @endif
        </div>
    </form>

    <div class="table-responsive">
        <table class="table table-clean table-bordered table-hover align-middle text-nowrap">
            <thead class="text-white" style="background-color: #23422e;">
                <tr>
                    <th style="width: 50px;" class="text-center">NO</th>
                    <th>WAKTU (WIB)</th>
                    <th>NAMA PENGGUNA</th>
                    <th class="text-center">PERAN</th>
                    <th class="text-center">AKSI / KEGIATAN</th>
                    <th>DESKRIPSI AKTIVITAS</th>
                    <th class="text-center">ALAMAT IP</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $index => $log)
                <tr>
                    <td class="text-center text-muted">{{ $logs->firstItem() + $index }}</td>
                    <td class="small fw-medium text-dark">
                        <i class="bi bi-clock me-1 text-muted"></i>{{ $log->created_at ? $log->created_at->format('d M Y, H:i:s') : '-' }}
                    </td>
                    <td class="fw-semibold text-dark">
                        <div class="d-flex align-items-center gap-2">
                            <div class="rounded-circle text-white d-flex align-items-center justify-content-center fw-bold" style="width: 26px; height: 26px; background-color: var(--palette-4); font-size: 0.72rem;">
                                {{ strtoupper(substr($log->user_name ?: 'P', 0, 1)) }}
                            </div>
                            <span>{{ $log->user_name ?: 'Sistem / Guest' }}</span>
                        </div>
                    </td>
                    <td class="text-center">
                        <span class="badge {{ $log->user_role === 'admin' ? 'bg-primary' : ($log->user_role === 'petugas' ? 'bg-success' : 'bg-secondary') }} px-2 py-1" style="font-size: 0.73rem;">
                            {{ strtoupper($log->user_role ?: 'GUEST') }}
                        </span>
                    </td>
                    <td class="text-center">
                        <span class="badge bg-light text-dark border px-2 py-1 fw-bold" style="font-size: 0.73rem;">
                            {{ $log->action }}
                        </span>
                    </td>
                    <td class="small text-secondary">{{ $log->description ?: '-' }}</td>
                    <td class="text-center text-muted small font-monospace">{{ $log->ip_address ?: '127.0.0.1' }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center text-muted py-4">Belum ada catatan aktivitas sistem.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-3">
        {{ $logs->links() }}
    </div>
</div>
@endsection
