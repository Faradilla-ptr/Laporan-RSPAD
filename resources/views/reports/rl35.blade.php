@extends('layouts.app')

@section('title', 'Laporan RL 3.5 - Rekapitulasi Kunjungan')

@section('content')
<!-- Title & Header Bar -->
<div class="d-flex align-items-center justify-content-between mb-3">
    <h4 class="fw-bold mb-0" style="color: var(--palette-5);">RL 3.5 - Kunjungan</h4>
</div>

<!-- Header Card & Filter Bar (Matching output (2).jpeg) -->
<div class="card-panel p-4 mb-4">
    <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
        <button class="btn btn-sm btn-rspad-primary px-3 fw-bold" style="background-color: #588b8b;">+</button>
        <button class="btn btn-sm text-white fw-medium px-3" style="background-color: #588b8b;" type="button" data-bs-toggle="collapse" data-bs-target="#filterCollapse">Filter</button>
        <a href="{{ route('reports.rl35.export', ['month' => $month, 'year' => $year]) }}" class="btn btn-sm text-white fw-medium px-3" style="background-color: #588b8b;">
            Download
        </a>
    </div>

    <div class="text-muted small mb-3">
        <em>filtered bynama: RS Umum PAD Gatot Soebroto, filtered byperiode: {{ $year }}-{{ $month }}</em>
    </div>

    <!-- Filter Form Collapse -->
    <div class="collapse show" id="filterCollapse">
        <form action="{{ route('reports.rl35') }}" method="GET" class="row g-2 align-items-center border-top pt-3 mt-2">
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
            <div class="col-md-2 mt-auto">
                <button type="submit" class="btn btn-sm btn-rspad-primary w-100 py-1">
                    Terapkan Filter
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Main Table RL 3.5 (Matching output (2).jpeg) -->
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
                            <button class="btn btn-sm text-white px-2 py-0" style="background-color: #f87171; font-size: 0.75rem;">Hapus</button>
                            <button class="btn btn-sm text-white px-2 py-0" style="background-color: #a3e635; color: #3f6212 !important; font-size: 0.75rem;">Ubah</button>
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
@endsection
