@extends('layouts.app')

@section('title', 'Laporan RL 3.4 - Rekapitulasi Pengunjung')

@section('content')



<!-- Header Card & Filter Bar -->
<div class="card-panel p-4 mb-4">
    <div class="border-bottom pb-3 mb-3">
        <h5 class="fw-bold mb-1 text-dark">Laporan RL 3.4 - Rekapitulasi Pengunjung</h5>
        <p class="text-muted small mb-0">Laporan rekapitulasi jumlah pengunjung baru dan pengunjung lama rumah sakit</p>
    </div>

    <form action="{{ url()->current() }}" method="GET" class="row g-2 align-items-end">
        <div class="col-lg-3 col-md-6">
            <label class="form-label fw-semibold small text-muted mb-1"><i class="bi bi-calendar-month me-1"></i>Bulan Periode</label>
            <select name="month" class="form-select form-select-sm">
                @foreach($availableMonths as $mNum => $mName)
                    <option value="{{ $mNum }}" {{ $month == (string)$mNum ? 'selected' : '' }}>
                        {{ $mName }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-lg-2 col-md-6">
            <label class="form-label fw-semibold small text-muted mb-1"><i class="bi bi-calendar-event me-1"></i>Tahun Periode</label>
            <select name="year" class="form-select form-select-sm">
                <option value="SEMUA" {{ $year == 'SEMUA' ? 'selected' : '' }}>-- SEMUA TAHUN --</option>
                @if(isset($dbYears) && count($dbYears) > 0)
                    @foreach($dbYears as $y)
                        <option value="{{ $y }}" {{ $year == (string)$y ? 'selected' : '' }}>{{ $y }}</option>
                    @endforeach
                @else
                    @foreach(range(2024, 2030) as $y)
                        <option value="{{ $y }}" {{ $year == (string)$y ? 'selected' : '' }}>{{ $y }}</option>
                    @endforeach
                @endif
            </select>
        </div>
        <div class="col-lg-3 col-md-6">
            <label class="form-label fw-semibold small text-muted mb-1"><i class="bi bi-hospital me-1"></i>Poliklinik</label>
            <select name="poli" class="form-select form-select-sm">
                <option value="SEMUA" {{ ($poli ?? 'SEMUA') == 'SEMUA' ? 'selected' : '' }}>Seluruh Poliklinik</option>
                @if(isset($polikliniks))
                    @foreach($polikliniks as $pName)
                        <option value="{{ $pName }}" {{ ($poli ?? '') == $pName ? 'selected' : '' }}>{{ $pName }}</option>
                    @endforeach
                @endif
            </select>
        </div>
        <div class="col-lg-4 col-md-6 d-flex gap-2">
            <button type="submit" class="btn btn-sm btn-rspad-primary flex-fill py-2 fw-medium shadow-sm d-flex align-items-center justify-content-center gap-1">
                <i class="bi bi-funnel-fill"></i> Terapkan Filter
            </button>
            <button type="button" class="btn btn-sm btn-rspad-primary flex-fill py-2 fw-medium shadow-sm d-flex align-items-center justify-content-center gap-1" data-bs-toggle="modal" data-bs-target="#exportExcelModal" onclick="document.getElementById('formExportExcel').action='{{ route('reports.rl34.export') }}'">
                <i class="bi bi-file-earmark-excel-fill"></i> Download Excel
            </button>
        </div>
    </form>
</div>

<!-- Summary Tables RL 3.4 -->
<div class="card-panel p-4 mb-4">
    <div class="table-responsive">
        <table class="table table-clean table-bordered align-middle text-nowrap">
            <thead style="background-color: #588b8b; color: #ffffff;" class="text-center">
                <tr>
                    <th style="width: 60px;">No.</th>
                    <th style="width: 140px;">Aksi</th>
                    <th>Jenis Pengunjung</th>
                    <th class="text-end" style="width: 200px;">Jumlah</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="text-center">1</td>
                    <td class="text-center">
                        <div class="d-flex gap-1 justify-content-center">
                            <button class="btn btn-sm text-white px-2 py-0" style="background-color: #f87171; font-size: 0.75rem;" data-bs-toggle="modal" data-bs-target="#deleteRL34Modal1">Hapus</button>
                            <button class="btn btn-sm text-white px-2 py-0" style="background-color: #a3e635; color: #3f6212 !important; font-size: 0.75rem;" data-bs-toggle="modal" data-bs-target="#editRL34Modal1">Ubah</button>
                        </div>
                    </td>
                    <td class="fw-semibold text-dark">Pengunjung Baru</td>
                    <td class="text-end fw-bold fs-6">{{ number_format($pengunjungBaru) }}</td>
                </tr>
                <tr>
                    <td class="text-center">2</td>
                    <td class="text-center">
                        <div class="d-flex gap-1 justify-content-center">
                            <button class="btn btn-sm text-white px-2 py-0" style="background-color: #f87171; font-size: 0.75rem;" data-bs-toggle="modal" data-bs-target="#deleteRL34Modal2">Hapus</button>
                            <button class="btn btn-sm text-white px-2 py-0" style="background-color: #a3e635; color: #3f6212 !important; font-size: 0.75rem;" data-bs-toggle="modal" data-bs-target="#editRL34Modal2">Ubah</button>
                        </div>
                    </td>
                    <td class="fw-semibold text-dark">Pengunjung Lama</td>
                    <td class="text-end fw-bold fs-6">{{ number_format($pengunjungLama) }}</td>
                </tr>
            </tbody>
            <tfoot class="fw-bold">
                <tr>
                    <td colspan="3" class="text-center">TOTAL :</td>
                    <td class="text-end fs-5" style="color: var(--palette-5);">{{ number_format($totalPengunjung) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<!-- Deduplicated Patients Verification Table -->
<div class="card-panel p-4">
    <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
        <h6 class="fw-bold mb-0" style="color: var(--palette-5);">Daftar Pasien Unik (RM Unik Periode {{ $availableMonths[$month] ?? $month }} {{ $year }})</h6>
        <span class="badge badge-palette px-2 py-1 font-monospace" style="font-size: 0.775rem;">TOTAL: {{ number_format($patients->total()) }} Pasien</span>
    </div>

    <div class="table-responsive">
        <table class="table table-clean table-hover text-nowrap">
            <thead>
                <tr>
                    <th>No</th>
                    <th>No RM</th>
                    <th>Status Pasien</th>
                    <th>Jenis Kelamin</th>
                    <th>Alamat / Domisili</th>
                </tr>
            </thead>
            <tbody>
                @forelse($patients as $idx => $p)
                <tr>
                    <td>{{ $patients->firstItem() + $idx }}</td>
                    <td class="fw-bold text-dark font-monospace">{{ $p->no_rm }}</td>
                    <td>
                        <span class="badge" style="background-color: var(--palette-1); color: var(--palette-5); border: 1px solid var(--palette-2); font-weight: 600;">
                            {{ $p->status_pasien }}
                        </span>
                    </td>
                    <td>{{ $p->gender === 'L' ? 'Laki-Laki' : 'Perempuan' }}</td>
                    <td class="text-muted small">{{ Str::limit($p->alamat, 65) }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="text-center text-muted py-3">Belum ada data pasien pada periode ini.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-3">
        {{ $patients->links() }}
    </div>
</div>

<!-- EDIT MODAL 1: Pengunjung Baru -->
<div class="modal fade" id="editRL34Modal1" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form action="{{ route('reports.rl34.update') }}" method="POST" class="modal-content">
            @csrf
            @method('PUT')
            <input type="hidden" name="month" value="{{ $month }}">
            <input type="hidden" name="year" value="{{ $year }}">
            <input type="hidden" name="poli" value="{{ $poli }}">
            <input type="hidden" name="status_pasien" value="Pasien Baru">

            <div class="modal-header" style="background-color: #588b8b; color: white;">
                <h5 class="modal-title fs-6 fw-bold">Ubah Status Pengunjung Baru</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Ubah Status Menjadi:</label>
                    <select name="new_status" class="form-select">
                        <option value="Pasien Lama">Pasien Lama</option>
                        <option value="Pasien Baru" selected>Pasien Baru</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-sm text-white" style="background-color: #588b8b;">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<!-- DELETE MODAL 1: Pengunjung Baru -->
<div class="modal fade" id="deleteRL34Modal1" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form action="{{ route('reports.rl34.destroy') }}" method="POST" class="modal-content">
            @csrf
            @method('DELETE')
            <input type="hidden" name="month" value="{{ $month }}">
            <input type="hidden" name="year" value="{{ $year }}">
            <input type="hidden" name="poli" value="{{ $poli }}">
            <input type="hidden" name="status_pasien" value="Pasien Baru">

            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title fs-6 fw-bold">Konfirmasi Hapus Data</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                Apakah Anda yakin ingin menghapus seluruh data <strong>Pengunjung Baru</strong> pada periode {{ $availableMonths[$month] ?? $month }} {{ $year }}? Action ini tidak dapat dibatalkan.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-sm btn-danger">Hapus Data</button>
            </div>
        </form>
    </div>
</div>

<!-- EDIT MODAL 2: Pengunjung Lama -->
<div class="modal fade" id="editRL34Modal2" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form action="{{ route('reports.rl34.update') }}" method="POST" class="modal-content">
            @csrf
            @method('PUT')
            <input type="hidden" name="month" value="{{ $month }}">
            <input type="hidden" name="year" value="{{ $year }}">
            <input type="hidden" name="poli" value="{{ $poli }}">
            <input type="hidden" name="status_pasien" value="Pasien Lama">

            <div class="modal-header" style="background-color: #588b8b; color: white;">
                <h5 class="modal-title fs-6 fw-bold">Ubah Status Pengunjung Lama</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Ubah Status Menjadi:</label>
                    <select name="new_status" class="form-select">
                        <option value="Pasien Baru">Pasien Baru</option>
                        <option value="Pasien Lama" selected>Pasien Lama</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-sm text-white" style="background-color: #588b8b;">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<!-- DELETE MODAL 2: Pengunjung Lama -->
<div class="modal fade" id="deleteRL34Modal2" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form action="{{ route('reports.rl34.destroy') }}" method="POST" class="modal-content">
            @csrf
            @method('DELETE')
            <input type="hidden" name="month" value="{{ $month }}">
            <input type="hidden" name="year" value="{{ $year }}">
            <input type="hidden" name="poli" value="{{ $poli }}">
            <input type="hidden" name="status_pasien" value="Pasien Lama">

            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title fs-6 fw-bold">Konfirmasi Hapus Data</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                Apakah Anda yakin ingin menghapus seluruh data <strong>Pengunjung Lama</strong> pada periode {{ $availableMonths[$month] ?? $month }} {{ $year }}? Action ini tidak dapat dibatalkan.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-sm btn-danger">Hapus Data</button>
            </div>
        </form>
    </div>
</div>

@include('reports.partials.export_modals')
@endsection
