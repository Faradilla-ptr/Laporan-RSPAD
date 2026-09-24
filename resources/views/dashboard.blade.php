@extends('layouts.app')

@section('title', 'Dashboard Rekapitulasi')

@section('content')
<!-- Filter & Header -->
<div class="card-panel p-4 mb-4">
    <form action="{{ route('dashboard') }}" method="GET" class="row g-3 align-items-center">
        <div class="col-md-5">
            <h5 class="fw-bold mb-1" style="color: var(--palette-5);">Dashboard Rekapitulasi Pelaporan</h5>
            <span class="text-muted small">Periode Aktif: <strong>{{ DateTime::createFromFormat('!m', $month)->format('F') }} {{ $year }}</strong></span>
        </div>
        <div class="col-md-3">
            <label class="form-label fw-semibold small text-muted mb-1">Pilih Bulan</label>
            <select name="month" class="form-select form-select-sm">
                @foreach(range(1, 12) as $m)
                    <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>
                        {{ DateTime::createFromFormat('!m', $m)->format('F') }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label fw-semibold small text-muted mb-1">Pilih Tahun</label>
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

<!-- Metrics Cards (Uniform Style & Color) -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="metric-card">
            <div class="metric-title">TOTAL KUNJUNGAN</div>
            <div class="metric-value">{{ number_format($totalKunjungan) }}</div>
            <p class="metric-desc">Total transaksi kedatangan rawat jalan</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="metric-card">
            <div class="metric-title">TOTAL PENGUNJUNG</div>
            <div class="metric-value">{{ number_format($totalPengunjung) }}</div>
            <p class="metric-desc">Individu / No RM Unik</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="metric-card">
            <div class="metric-title">PENGUNJUNG BARU (RL 3.4)</div>
            <div class="metric-value">{{ number_format($pengunjungBaru) }}</div>
            <p class="metric-desc">Pasien berobat pertama kali</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="metric-card">
            <div class="metric-title">PENGUNJUNG LAMA (RL 3.4)</div>
            <div class="metric-value">{{ number_format($pengunjungLama) }}</div>
            <p class="metric-desc">Pasien berobat ulangan</p>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <!-- Chart Card -->
    <div class="col-md-8">
        <div class="card-panel p-4 h-100">
            <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
                <h6 class="fw-bold mb-0 text-dark">Grafik Tren Kunjungan Harian</h6>
                <span class="badge badge-palette px-2 py-1">{{ DateTime::createFromFormat('!m', $month)->format('F') }} {{ $year }}</span>
            </div>
            <div style="height: 280px;">
                <canvas id="dailyChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Poliklinik Table -->
    <div class="col-md-4">
        <div class="card-panel p-4 h-100">
            <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
                <h6 class="fw-bold mb-0 text-dark">Poliklinik Terbanyak</h6>
                <span class="text-muted small">Top 7 Poli</span>
            </div>
            <table class="table table-clean table-hover">
                <thead>
                    <tr>
                        <th>Poliklinik</th>
                        <th class="text-end">Jumlah</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($poliBreakdown as $p)
                    <tr>
                        <td class="fw-medium text-dark">{{ $p->poliklinik }}</td>
                        <td class="text-end fw-bold" style="color: var(--palette-5);">{{ number_format($p->total) }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="2" class="text-center text-muted">Belum ada data.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Status Pasien Dinas Puskesad Table -->
<div class="card-panel p-4 mb-4">
    <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
        <div>
            <h6 class="fw-bold mb-0" style="color: var(--palette-5);">Ringkasan Status Pasien Dinas (Puskesad)</h6>
            <span class="text-muted small">Pengelompokan Golongan Personel & Persentase Kontribusi</span>
        </div>
        <a href="{{ route('reports.puskesad') }}" class="btn btn-sm btn-outline-rspad d-flex align-items-center gap-1">
            Detail Laporan Puskesad &rarr;
        </a>
    </div>

    <div class="table-responsive">
        <table class="table table-clean table-hover text-nowrap">
            <thead>
                <tr>
                    <th>Status Pasien Dinas</th>
                    <th class="text-center">Pengunjung (RM Unik)</th>
                    <th class="text-center">% Pengunjung</th>
                    <th class="text-center">Kunjungan (Transaksi)</th>
                    <th class="text-center">% Kunjungan</th>
                </tr>
            </thead>
            <tbody>
                @forelse($statusPuskesadStats as $statusName => $s)
                <tr>
                    <td class="fw-semibold text-dark">{{ $statusName }}</td>
                    <td class="text-center">{{ number_format($s['pengunjung']) }}</td>
                    <td class="text-center text-muted">{{ $s['pengunjung_pct'] }}%</td>
                    <td class="text-center fw-bold" style="color: var(--palette-5);">{{ number_format($s['kunjungan']) }}</td>
                    <td class="text-center text-muted">{{ $s['kunjungan_pct'] }}%</td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="text-center text-muted">Belum ada data transaksi kunjungan.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

@section('scripts')
<script>
    const ctx = document.getElementById('dailyChart').getContext('2d');
    const dailyData = @json($dailyTrend);
    
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: dailyData.map(d => d.date),
            datasets: [{
                label: 'Kunjungan',
                data: dailyData.map(d => d.total),
                borderColor: '#3B8A3B',
                backgroundColor: 'rgba(93, 170, 93, 0.15)',
                borderWidth: 2,
                fill: true,
                pointBackgroundColor: '#2A6A2A',
                pointRadius: 3,
                tension: 0.2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                x: {
                    display: false
                },
                y: {
                    beginAtZero: true,
                    grid: { color: '#f1f5f9' }
                }
            }
        }
    });
</script>
@endsection
