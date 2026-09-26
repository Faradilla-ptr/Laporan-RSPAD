@extends('layouts.app')

@section('title', 'Laporan RL 3.4 - Rekapitulasi Pengunjung')

@section('content')
<!-- Header Title -->
<div class="d-flex align-items-center justify-content-between mb-3">
    <h4 class="fw-bold mb-0" style="color: var(--palette-5);">RL 3.4 - Pengunjung</h4>
</div>

<!-- Header Card & Filter Bar -->
<div class="card-panel p-4 mb-4">
    <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
        <button class="btn btn-sm btn-rspad-primary px-3 fw-bold" style="background-color: #588b8b;">+</button>
        <button class="btn btn-sm text-white fw-medium px-3" style="background-color: #588b8b;" type="button" data-bs-toggle="collapse" data-bs-target="#filterCollapse">Filter</button>
        <a href="{{ route('reports.rl34.export', ['month' => $month, 'year' => $year, 'poli' => $poli ?? 'SEMUA']) }}" class="btn btn-sm text-white fw-medium px-3" style="background-color: #588b8b;">
            <i class="fas me-1">📊</i> Download Excel
        </a>
        <a href="{{ route('reports.export-zip', ['month' => $month, 'year' => $year]) }}" class="btn btn-sm text-white fw-medium px-3" style="background-color: #2D6A4F;">
            <i class="fas me-1">📦</i> Download Batch ZIP (Semua Poli)
        </a>
    </div>

    <!-- Filter Form Collapse -->
    <div class="collapse show" id="filterCollapse">
        <form action="{{ route('reports.rl34') }}" method="GET" class="row g-2 align-items-center border-top pt-3 mt-2">
            <div class="col-md-3">
                <label class="form-label fw-semibold small text-muted mb-1">Bulan Periode</label>
                <select name="month" class="form-select form-select-sm">
                    @foreach(range(1, 12) as $m)
                        <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>
                            {{ DateTime::createFromFormat('!m', $m)->format('F') }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-semibold small text-muted mb-1">Tahun Periode</label>
                <select name="year" class="form-select form-select-sm">
                    @foreach(range(2024, 2030) as $y)
                        <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label fw-semibold small text-muted mb-1">Poliklinik</label>
                <select name="poli" class="form-select form-select-sm">
                    <option value="SEMUA" {{ ($poli ?? 'SEMUA') == 'SEMUA' ? 'selected' : '' }}>-- SEMUA POLIKLINIK --</option>
                    @if(isset($polikliniks))
                        @foreach($polikliniks as $pName)
                            <option value="{{ $pName }}" {{ ($poli ?? '') == $pName ? 'selected' : '' }}>{{ $pName }}</option>
                        @endforeach
                    @endif
                </select>
            </div>
            <div class="col-md-2 mt-auto">
                <button type="submit" class="btn btn-sm btn-rspad-primary w-100 py-1">
                    Terapkan Filter
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Summary Tables RL 3.4 (Matching output (1).jpeg) -->
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
                            <button class="btn btn-sm text-white px-2 py-0" style="background-color: #f87171; font-size: 0.75rem;">Hapus</button>
                            <button class="btn btn-sm text-white px-2 py-0" style="background-color: #a3e635; color: #3f6212 !important; font-size: 0.75rem;">Ubah</button>
                        </div>
                    </td>
                    <td class="fw-semibold text-dark">Pengunjung Baru</td>
                    <td class="text-end fw-bold fs-6">{{ number_format($pengunjungBaru) }}</td>
                </tr>
                <tr>
                    <td class="text-center">2</td>
                    <td class="text-center">
                        <div class="d-flex gap-1 justify-content-center">
                            <button class="btn btn-sm text-white px-2 py-0" style="background-color: #f87171; font-size: 0.75rem;">Hapus</button>
                            <button class="btn btn-sm text-white px-2 py-0" style="background-color: #a3e635; color: #3f6212 !important; font-size: 0.75rem;">Ubah</button>
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
        <h6 class="fw-bold mb-0" style="color: var(--palette-5);">Daftar Pasien Unik (RM Unik Periode {{ DateTime::createFromFormat('!m', $month)->format('F') }} {{ $year }})</h6>
        <span class="badge badge-palette px-2 py-1 font-monospace" style="font-size: 0.775rem;">TOTAL: {{ number_format($patients->total()) }} Pasien</span>
    </div>

    <div class="table-responsive">
        <table class="table table-clean table-hover text-nowrap">
            <thead>
                <tr>
                    <th>No</th>
                    <th>No RM</th>
                    <th>Nama Pasien</th>
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
                    <td class="fw-medium text-dark">{{ $p->nama_pasien }}</td>
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
                    <td colspan="6" class="text-center text-muted py-3">Belum ada data pasien pada periode ini.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-3">
        {{ $patients->links() }}
    </div>
</div>
@endsection
