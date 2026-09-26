@extends('layouts.app')

@section('title', 'Laporan RL 3.5 - Rekapitulasi Kunjungan')

@section('content')

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
    <i class="fas me-2">✅</i> {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

<!-- Header Card & Filter Bar -->
<div class="card-panel p-4 mb-4">
    <div class="border-bottom pb-3 mb-3">
        <h5 class="fw-bold mb-1 text-dark">Laporan RL 3.5 - Rekapitulasi Kunjungan Poliklinik</h5>
        <p class="text-muted small mb-0">Laporan rekapitulasi total kunjungan pasien baru, lama, dan jenis pembayaran per poliklinik</p>
    </div>

    <form action="{{ route('reports.rl35') }}" method="GET" class="row g-2 align-items-end">
        <div class="col-lg-3 col-md-6">
            <label class="form-label fw-semibold small text-muted mb-1"><i class="bi bi-calendar-month me-1"></i>Bulan Periode</label>
            <select name="month" class="form-select form-select-sm">
                @foreach(range(1, 12) as $m)
                    <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>
                        {{ DateTime::createFromFormat('!m', $m)->format('F') }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-lg-2 col-md-6">
            <label class="form-label fw-semibold small text-muted mb-1"><i class="bi bi-calendar-event me-1"></i>Tahun Periode</label>
            <select name="year" class="form-select form-select-sm">
                @foreach(range(2024, 2030) as $y)
                    <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-lg-3 col-md-6">
            <label class="form-label fw-semibold small text-muted mb-1"><i class="bi bi-hospital me-1"></i>Poliklinik</label>
            <select name="poli" class="form-select form-select-sm">
                <option value="SEMUA" {{ ($poli ?? 'SEMUA') == 'SEMUA' ? 'selected' : '' }}>-- SEMUA POLIKLINIK --</option>
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
            <button type="button" class="btn btn-sm btn-rspad-primary flex-fill py-2 fw-medium shadow-sm d-flex align-items-center justify-content-center gap-1" data-bs-toggle="modal" data-bs-target="#exportExcelModal" onclick="document.getElementById('formExportExcel').action='{{ route('reports.rl35.export') }}'">
                <i class="bi bi-file-earmark-excel-fill"></i> Download Excel
            </button>
        </div>
    </form>
</div>

<!-- Main Table RL 3.5 -->
<div class="card-panel p-4 mb-4">
    <div class="table-responsive">
        <table class="table table-clean table-bordered table-hover text-nowrap align-middle">
            <thead class="text-center">
                <tr>
                    <th rowspan="2" style="width: 50px;">No.</th>
                    <th rowspan="2" style="width: 140px;">Aksi</th>
                    <th rowspan="2">Jenis Kegiatan</th>
                    <th colspan="2">Kunjungan Pasien Dalam Kota</th>
                    <th colspan="2">Kunjungan Pasien Luar Kota</th>
                    <th rowspan="2">Total Kunjungan</th>
                </tr>
                <tr>
                    <th>Laki-Laki</th>
                    <th>Perempuan</th>
                    <th>Laki-Laki</th>
                    <th>Perempuan</th>
                </tr>
            </thead>
            <tbody>
                @forelse($poliData as $idx => $p)
                <tr>
                    <td class="text-center">{{ $idx + 1 }}</td>
                    <td class="text-center">
                        <div class="d-flex gap-1 justify-content-center">
                            <button class="btn btn-sm text-white px-2 py-0" style="background-color: #f87171; font-size: 0.75rem;" data-bs-toggle="modal" data-bs-target="#deletePoliModal{{ $idx }}">Hapus</button>
                            <button class="btn btn-sm text-white px-2 py-0" style="background-color: #a3e635; color: #3f6212 !important; font-size: 0.75rem;" data-bs-toggle="modal" data-bs-target="#editPoliModal{{ $idx }}">Ubah</button>
                        </div>

                        <!-- EDIT MODAL -->
                        <div class="modal fade text-start" id="editPoliModal{{ $idx }}" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog">
                                <form action="{{ route('reports.rl35.update') }}" method="POST" class="modal-content">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="month" value="{{ $month }}">
                                    <input type="hidden" name="year" value="{{ $year }}">
                                    <input type="hidden" name="poliklinik" value="{{ $p['poliklinik'] }}">

                                    <div class="modal-header" style="background-color: #588b8b; color: white;">
                                        <h5 class="modal-title fs-6 fw-bold">Ubah Nama Poliklinik</h5>
                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="mb-3">
                                            <label class="form-label fw-semibold">Nama Poliklinik Saat Ini:</label>
                                            <input type="text" class="form-control" value="{{ $p['poliklinik'] }}" readonly>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label fw-semibold">Nama Poliklinik Baru:</label>
                                            <input type="text" name="new_poliklinik" class="form-control" value="{{ $p['poliklinik'] }}" required>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Batal</button>
                                        <button type="submit" class="btn btn-sm text-white" style="background-color: #588b8b;">Simpan Perubahan</button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- DELETE MODAL -->
                        <div class="modal fade text-start" id="deletePoliModal{{ $idx }}" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog">
                                <form action="{{ route('reports.rl35.destroy') }}" method="POST" class="modal-content">
                                    @csrf
                                    @method('DELETE')
                                    <input type="hidden" name="month" value="{{ $month }}">
                                    <input type="hidden" name="year" value="{{ $year }}">
                                    <input type="hidden" name="poliklinik" value="{{ $p['poliklinik'] }}">

                                    <div class="modal-header bg-danger text-white">
                                        <h5 class="modal-title fs-6 fw-bold">Konfirmasi Hapus Data Poliklinik</h5>
                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        Apakah Anda yakin ingin menghapus seluruh data kunjungan untuk <strong>{{ $p['poliklinik'] }}</strong> pada periode {{ DateTime::createFromFormat('!m', $month)->format('F') }} {{ $year }}? Total <strong>{{ number_format($p['total']) }}</strong> data kunjungan akan dihapus. Action ini tidak dapat dibatalkan.
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Batal</button>
                                        <button type="submit" class="btn btn-sm btn-danger">Hapus Data</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </td>
                    <td class="fw-semibold text-dark">{{ $p['poliklinik'] }}</td>
                    <td class="text-center">{{ number_format($p['dalam_kota_l']) }}</td>
                    <td class="text-center">{{ number_format($p['dalam_kota_p']) }}</td>
                    <td class="text-center">{{ number_format($p['luar_kota_l']) }}</td>
                    <td class="text-center">{{ number_format($p['luar_kota_p']) }}</td>
                    <td class="text-center fw-bold" style="color: var(--palette-5);">{{ number_format($p['total']) }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center text-muted py-3">Belum ada data kunjungan pada periode ini.</td>
                </tr>
                @endforelse
            </tbody>
            <tfoot style="background-color: rgba(178, 224, 178, 0.25);" class="fw-bold">
                <tr>
                    <td colspan="3" class="text-end" style="color: var(--palette-5);">TOTAL SELURUH KUNJUNGAN:</td>
                    <td class="text-center" style="color: var(--palette-5);">{{ number_format(array_sum(array_column($poliData, 'dalam_kota_l'))) }}</td>
                    <td class="text-center" style="color: var(--palette-5);">{{ number_format(array_sum(array_column($poliData, 'dalam_kota_p'))) }}</td>
                    <td class="text-center" style="color: var(--palette-5);">{{ number_format(array_sum(array_column($poliData, 'luar_kota_l'))) }}</td>
                    <td class="text-center" style="color: var(--palette-5);">{{ number_format(array_sum(array_column($poliData, 'luar_kota_p'))) }}</td>
                    <td class="text-center fs-5" style="color: var(--palette-5);">{{ number_format($totalKunjunganAll) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

@include('reports.partials.export_modals')
@endsection
