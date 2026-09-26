@extends('layouts.app')

@section('title', 'Import Excel SIMRS')

@section('content')
<div class="row g-4">
    <!-- Upload Form Card -->
    <div class="col-md-5">
        <div class="card-panel p-4">
            <div class="border-bottom pb-3 mb-3">
                <h5 class="fw-bold mb-1 text-dark">Import File Excel SIMRS</h5>
                <p class="text-muted small mb-0">Unggah berkas tarikan kedatangan kunjungan dari SIMRS RSPAD</p>
            </div>

            <form action="{{ route('imports.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="mb-3">
                    <label for="excel_file" class="form-label fw-semibold small">Pilih File Excel / Spreadsheet</label>
                    <input type="file" class="form-control form-control-sm @error('excel_file') is-invalid @enderror" 
                           id="excel_file" name="excel_file" 
                           accept=".xls,.xlsx,.xlsb,.xlsm,.xltx,.xltm,.csv,.tsv,.txt,.ods,.slk,.xml" required>
                    <div class="form-text small text-muted">
                        Mendukung semua tipe file Excel (.xlsx, .xls, .xlsb, .xlsm, .csv, .ods, .tsv, .xml) hingga 30 MB.
                    </div>
                    @error('excel_file')
                        <div class="invalid-feedback small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <div class="row g-2 mb-4">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold small">Bulan Periode</label>
                        <select name="period_month" class="form-select form-select-sm" required>
                            @foreach(range(1, 12) as $m)
                                <option value="{{ $m }}" {{ (old('period_month', date('n')) == $m) ? 'selected' : '' }}>
                                    {{ DateTime::createFromFormat('!m', $m)->format('F') }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold small">Tahun Periode</label>
                        <select name="period_year" class="form-select form-select-sm" required>
                            @foreach(range(2024, 2030) as $y)
                                <option value="{{ $y }}" {{ (old('period_year', date('Y')) == $y) ? 'selected' : '' }}>{{ $y }}</option>
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
            <div class="d-flex align-items-center justify-content-between border-bottom pb-3 mb-3">
                <div>
                    <h5 class="fw-bold mb-1 text-dark">Riwayat Pengunggahan File</h5>
                    <p class="text-muted small mb-0">Daftar berkas SIMRS yang telah diproses ke dalam database</p>
                </div>
                @if($importLogs->count() > 0 || \App\Models\RawVisit::count() > 0)
                <button type="button" class="btn btn-outline-danger btn-sm fw-semibold d-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#modalTruncateAll">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                    Hapus Semua Data
                </button>
                @endif
            </div>

            <div class="table-responsive">
                <table class="table table-clean table-hover text-nowrap align-middle">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama File</th>
                            <th>Periode</th>
                            <th class="text-center">Jumlah Baris</th>
                            <th>Petugas Import</th>
                            <th>Waktu Upload</th>
                            <th class="text-center">Aksi</th>
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
                            <td class="text-center">
                                <button type="button" class="btn btn-outline-danger btn-sm py-1 px-2 text-decoration-none d-inline-flex align-items-center gap-1"
                                        data-bs-toggle="modal" data-bs-target="#modalDeleteLog{{ $log->id }}" title="Hapus berkas ini beserta datanya">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                                    Hapus
                                </button>
                            </td>
                        </tr>

                        <!-- Modal Delete Single Log -->
                        <div class="modal fade" id="modalDeleteLog{{ $log->id }}" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content">
                                    <div class="modal-header border-bottom-0 pb-0">
                                        <h6 class="modal-title fw-bold text-danger">Konfirmasi Hapus File Import</h6>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body py-3">
                                        <p class="mb-2">Apakah Anda yakin ingin menghapus berkas import <strong>{{ $log->filename }}</strong>?</p>
                                        <div class="alert alert-warning small mb-0 py-2">
                                             Tindakan ini akan menghapus seluruh <strong>{{ number_format($log->total_rows) }} baris data kunjungan</strong> yang terkait dengan file ini dari database.
                                        </div>
                                    </div>
                                    <div class="modal-footer border-top-0 pt-0">
                                        <button type="button" class="btn btn-light btn-sm fw-semibold" data-bs-dismiss="modal">Batal</button>
                                        <form action="{{ route('imports.destroy', $log->id) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger btn-sm fw-semibold">Ya, Hapus Data</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">Belum ada riwayat pengunggahan file.</td>
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

<!-- Modal Truncate All Data -->
<div class="modal fade" id="modalTruncateAll" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-bottom-0 pb-0">
                <h6 class="modal-title fw-bold text-danger">Kosongkan Seluruh Data SIMRS (Reset 0)</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body py-3">
                <p class="mb-2">Apakah Anda yakin ingin menghapus <strong>SELURUH data kunjungan dan riwayat import</strong> dari sistem?</p>
                <div class="alert alert-danger small mb-0 py-2">
                     <strong>PERINGATAN:</strong> Seluruh {{ number_format(\App\Models\RawVisit::count()) }} data kunjungan akan dihapus permanen dari database. Aplikasi akan kembali menjadi 0 data sehingga Anda dapat mengunggah berkas Excel baru dari awal.
                </div>
            </div>
            <div class="modal-footer border-top-0 pt-0">
                <button type="button" class="btn btn-light btn-sm fw-semibold" data-bs-dismiss="modal">Batal</button>
                <form action="{{ route('imports.truncateAll') }}" method="POST" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger btn-sm fw-semibold">Ya, Hapus Semua (Reset 0)</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
