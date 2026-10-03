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
            <button type="button" class="btn btn-sm btn-outline-rspad fw-medium" data-bs-toggle="modal" data-bs-target="#exportExcelModal" onclick="document.getElementById('formExportExcel').action='{{ route('reports.puskesad.export') }}'">
                Export Excel (.xlsx)
            </button>
            <button onclick="window.print()" class="btn btn-sm btn-rspad-primary fw-medium">
                Cetak Laporan
            </button>
        </div>
    </div>

    <form action="{{ url()->current() }}" method="GET" class="row g-2 align-items-center">
        <div class="col-md-3">
            <label class="form-label fw-semibold small text-muted mb-1">Bulan Periode</label>
            <select name="month" class="form-select form-select-sm">
                @foreach($availableMonths as $mNum => $mName)
                    <option value="{{ $mNum }}" {{ $month == (string)$mNum ? 'selected' : '' }}>
                        {{ $mName }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label fw-semibold small text-muted mb-1">Tahun Periode</label>
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
        <div class="col-md-4">
            <label class="form-label fw-semibold small text-muted mb-1">Poliklinik</label>
            <select name="poli" class="form-select form-select-sm">
                <option value="SEMUA" {{ ($poli ?? 'SEMUA') == 'SEMUA' ? 'selected' : '' }}>Seluruh Poliklinik</option>
                @if(isset($polikliniks))
                    @foreach($polikliniks as $pName)
                        <option value="{{ $pName }}" {{ ($poli ?? '') == $pName ? 'selected' : '' }}>{{ $pName }}</option>
                    @endforeach
                @endif
            </select>
        </div>
        <div class="col-md-2 mt-auto">
            <button type="submit" class="btn btn-sm btn-rspad-primary w-100 py-1 fw-medium">
                Terapkan Filter
            </button>
        </div>
    </form>
</div>

<!-- Main Table Puskesad -->
<div class="card-panel p-4 mb-4">
    <div class="table-responsive">
        <table class="table table-clean table-bordered table-hover text-nowrap">
            <thead class="text-center text-white" style="background-color: #23422e;">
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
                <tr class="fw-bold bg-white">
                    <td colspan="6" class="text-uppercase text-dark ps-3 py-2" style="font-size: 0.95rem;">
                        {{ $groupName }}
                    </td>
                </tr>

                @foreach($items as $label => $data)
                <tr>
                    <td></td>
                    <td class="ps-4 fw-medium text-dark">{{ $label }}</td>
                    <td class="text-center text-dark">{{ number_format($data['pengunjung']) }}</td>
                    <td class="text-center text-secondary">{{ $data['pengunjung_pct'] }}%</td>
                    <td class="text-center fw-medium text-dark">{{ number_format($data['kunjungan']) }}</td>
                    <td class="text-center text-secondary">{{ $data['kunjungan_pct'] }}%</td>
                </tr>
                @endforeach

                <!-- Subtotal Row -->
                <tr class="fw-bold bg-white">
                    <td colspan="2" class="text-end text-dark">SUB TOTAL {{ strtoupper($groupName) }}:</td>
                    <td class="text-center text-dark fw-bold">{{ number_format($subTotals[$groupName]['pengunjung']) }}</td>
                    <td class="text-center text-dark fw-bold">{{ round(($subTotals[$groupName]['pengunjung'] / max(1, $totalPengunjungAll)) * 100, 2) }}%</td>
                    <td class="text-center text-dark fw-bold">{{ number_format($subTotals[$groupName]['kunjungan']) }}</td>
                    <td class="text-center text-dark fw-bold">{{ round(($subTotals[$groupName]['kunjungan'] / max(1, $totalKunjunganAll)) * 100, 2) }}%</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot class="fw-bold text-center bg-white">
                <tr>
                    <td colspan="2" class="text-end text-dark">GRAND TOTAL SELURUH PELAYANAN:</td>
                    <td class="text-dark fw-bold">{{ number_format($totalPengunjungAll) }}</td>
                    <td class="text-dark fw-bold">100%</td>
                    <td class="text-dark fw-bold">{{ number_format($totalKunjunganAll) }}</td>
                    <td class="text-dark fw-bold">100%</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

@include('reports.partials.export_modals')
@endsection
