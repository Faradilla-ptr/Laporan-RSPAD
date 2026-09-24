@extends('layouts.app')

@section('title', 'Import Excel SIMRS')

@section('content')
<div class="row g-4">
    <!-- Upload Form Card -->
    <div class="col-md-5">
        <div class="card-panel p-4">
            <div class="border-bottom pb-3 mb-3">
                <h5 class="fw-bold mb-1" style="color: var(--palette-5);">Import File Excel SIMRS</h5>
                <p class="text-muted small mb-0">Unggah file tarikan kedatangan kunjungan dari SIMRS RSPAD</p>
            </div>

            <form action="{{ route('imports.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="mb-3">
                    <label for="excel_file" class="form-label fw-semibold small">Pilih File Excel (.xls / .xlsx)</label>
                    <input type="file" class="form-control form-control-sm" id="excel_file" name="excel_file" accept=".xls,.xlsx,.csv" required>
                    <div class="form-text small text-muted">Maksimal ukuran file 20 MB (Format Excel SIMRS)</div>
                </div>

                <div class="row g-2 mb-4">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold small">Bulan Periode</label>
                        <select name="period_month" class="form-select form-select-sm" required>
                            @foreach(range(1, 12) as $m)
                                <option value="{{ $m }}" {{ date('n') == $m ? 'selected' : '' }}>
                                    {{ DateTime::createFromFormat('!m', $m)->format('F') }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold small">Tahun Periode</label>
                        <select name="period_year" class="form-select form-select-sm" required>
                            @foreach(range(2024, 2030) as $y)
                                <option value="{{ $y }}" {{ date('Y') == $y ? 'selected' : '' }}>{{ $y }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <button type="submit" class="btn btn-rspad-primary w-100 py-2 btn-sm fw-semibold d-flex align-items-center justify-content-center gap-2">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
                    Proses Import Data
                </button>
            </form>
        </div>
    </div>

    <!-- Import History Table -->
    <div class="col-md-7">
        <div class="card-panel p-4">
            <div class="border-bottom pb-3 mb-3">
                <h5 class="fw-bold mb-1" style="color: var(--palette-5);">Riwayat Pengunggahan File</h5>
                <p class="text-muted small mb-0">Daftar berkas SIMRS yang telah diproses ke dalam database</p>
            </div>

            <div class="table-responsive">
                <table class="table table-clean table-hover text-nowrap">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama File</th>
                            <th>Periode</th>
                            <th class="text-center">Jumlah Baris</th>
                            <th>Petugas Import</th>
                            <th>Waktu Upload</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($importLogs as $idx => $log)
                        <tr>
                            <td>{{ $importLogs->firstItem() + $idx }}</td>
                            <td class="fw-semibold text-dark">{{ $log->filename }}</td>
                            <td>{{ DateTime::createFromFormat('!m', $log->period_month)->format('M') }} {{ $log->period_year }}</td>
                            <td class="text-center">
                                <span class="badge" style="background-color: var(--palette-1); color: var(--palette-5); border: 1px solid var(--palette-2); font-weight: 600;">
                                    {{ number_format($log->total_rows) }}
                                </span>
                            </td>
                            <td>{{ $log->user->name ?? 'System' }}</td>
                            <td class="text-muted small">{{ $log->created_at->format('d/m/Y H:i') }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-3">Belum ada riwayat pengunggahan.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-3">
                {{ $importLogs->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
