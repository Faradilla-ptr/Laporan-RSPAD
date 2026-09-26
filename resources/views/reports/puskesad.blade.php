@extends('layouts.app')

@section('title', 'Laporan Rawat Jalan Dinas - Puskesad')

@section('content')
<!-- Header Card & Filter -->
<div class="card-panel p-4 mb-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-3 border-bottom pb-3">
        <div>
            <h5 class="fw-bold mb-1 text-dark">Laporan Pelayanan Rawat Jalan Dinas (Puskesad)</h5>
            <span class="text-muted small">Laporan Status Pasien & Golongan Personel ke Pusat Kesehatan Angkatan Darat</span>
        </div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-sm btn-outline-rspad d-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#exportExcelModal" onclick="document.getElementById('formExportExcel').action='{{ route('reports.puskesad.export') }}'">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                Export Excel (.xlsx)
            </button>
            <button onclick="window.print()" class="btn btn-sm btn-rspad-primary d-flex align-items-center gap-1">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
                Cetak Laporan
            </button>
        </div>
    </div>

    <form action="{{ url()->current() }}" method="GET" class="row g-2 align-items-center">
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
                Filter Periode
            </button>
        </div>
    </form>
</div>

<!-- Main Table Puskesad -->
<div class="card-panel p-4 mb-4">
    <div class="table-responsive">
        <table class="table table-clean table-bordered table-hover text-nowrap">
            <thead class="text-center">
                <tr>
                    <th rowspan="2" style="width: 50px;">NO</th>
                    <th rowspan="2">STATUS PASIEN / GOLONGAN PERSONEL</th>
                    <th colspan="2">PENGUNJUNG (RM UNIK)</th>
                    <th colspan="2">KUNJUNGAN (TRANSAKSI)</th>
                </tr>
                <tr>
                    <th>JUMLAH</th>
                    <th>%</th>
                    <th>JUMLAH</th>
                    <th>%</th>
                </tr>
            </thead>
            <tbody>
                @foreach($reportData as $groupName => $items)
                <!-- Group Header -->
                <tr style="background-color: rgba(93, 170, 93, 0.12);" class="fw-bold">
                    <td colspan="6" class="text-uppercase" style="color: var(--palette-5);">
                        {{ $groupName }}
                    </td>
                </tr>

                @foreach($items as $label => $data)
                <tr>
                    <td></td>
                    <td class="ps-4 fw-medium text-dark">{{ $label }}</td>
                    <td class="text-center">{{ number_format($data['pengunjung']) }}</td>
                    <td class="text-center text-muted">{{ $data['pengunjung_pct'] }}%</td>
                    <td class="text-center fw-semibold" style="color: var(--palette-5);">{{ number_format($data['kunjungan']) }}</td>
                    <td class="text-center text-muted">{{ $data['kunjungan_pct'] }}%</td>
                </tr>
                @endforeach

                <!-- Subtotal Row -->
                <tr style="background-color: rgba(178, 224, 178, 0.25);" class="fw-bold">
                    <td colspan="2" class="text-end" style="color: var(--palette-5);">SUB TOTAL {{ strtoupper($groupName) }}:</td>
                    <td class="text-center" style="color: var(--palette-5);">{{ number_format($subTotals[$groupName]['pengunjung']) }}</td>
                    <td class="text-center" style="color: var(--palette-5);">{{ round(($subTotals[$groupName]['pengunjung'] / max(1, $totalPengunjungAll)) * 100, 2) }}%</td>
                    <td class="text-center fs-6" style="color: var(--palette-5);">{{ number_format($subTotals[$groupName]['kunjungan']) }}</td>
                    <td class="text-center" style="color: var(--palette-5);">{{ round(($subTotals[$groupName]['kunjungan'] / max(1, $totalKunjunganAll)) * 100, 2) }}%</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot style="background-color: var(--palette-5); color: #ffffff;" class="fw-bold text-center">
                <tr>
                    <td colspan="2" class="text-end">GRAND TOTAL SELURUH PELAYANAN:</td>
                    <td>{{ number_format($totalPengunjungAll) }}</td>
                    <td>100%</td>
                    <td style="color: var(--palette-1);" class="fs-5">{{ number_format($totalKunjunganAll) }}</td>
                    <td>100%</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

@include('reports.partials.export_modals')
@endsection
