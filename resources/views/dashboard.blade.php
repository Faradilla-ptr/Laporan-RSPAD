@extends('layouts.app')

@section('title', 'Dashboard Rekapitulasi')

@section('content')
<!-- Filter & Header Bar -->
<div class="card-panel p-4 mb-4">
    <form action="{{ route('dashboard') }}" method="GET" class="row g-3 align-items-center">
        <div class="col-md-5">
            <h5 class="fw-bold mb-1" style="color: var(--palette-5);">Dashboard Rekapitulasi Pelaporan SIMRS</h5>
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
            <button type="submit" class="btn btn-sm btn-rspad-primary w-100 py-1 fw-medium">
                Terapkan Filter
            </button>
        </div>
    </form>
</div>

<!-- Metrics Cards (Uniform RSPAD Green Style) -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="metric-card">
            <div class="metric-title">TOTAL KUNJUNGAN</div>
            <div class="metric-value">{{ number_format($totalKunjungan) }}</div>
            <p class="metric-desc">Total transaksi kontak registrasi rawat jalan</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="metric-card">
            <div class="metric-title">TOTAL PENGUNJUNG</div>
            <div class="metric-value">{{ number_format($totalPengunjung) }}</div>
            <p class="metric-desc">Pasien Unik (Count Distinct No. RM)</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="metric-card">
            <div class="metric-title">PENGUNJUNG BARU (RL 3.4)</div>
            <div class="metric-value">{{ number_format($pengunjungBaru) }}</div>
            <p class="metric-desc">Pasien pertama kali mendaftar berobat</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="metric-card">
            <div class="metric-title">PENGUNJUNG LAMA (RL 3.4)</div>
            <div class="metric-value">{{ number_format($pengunjungLama) }}</div>
            <p class="metric-desc">Pasien berobat ulangan / lanjutan</p>
        </div>
    </div>
</div>

<!-- Visualizations Row 1: Line Chart & Doughnut Chart -->
<div class="row g-3 mb-4">
    <!-- Trend Line Chart -->
    <div class="col-md-7">
        <div class="card-panel p-4 h-100">
            <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
                <div>
                    <h6 class="fw-bold mb-0 text-dark">Grafik Tren Kunjungan vs Pengunjung</h6>
                    <span class="text-muted small">Perbandingan total kontak vs pasien unik per hari</span>
                </div>
                <span class="badge" style="background-color: var(--palette-1); color: var(--palette-5); font-weight: 600;">
                    {{ DateTime::createFromFormat('!m', $month)->format('F') }} {{ $year }}
                </span>
            </div>
            <div style="height: 280px;">
                <canvas id="trendChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Kelompok Distribution Doughnut Chart -->
    <div class="col-md-5">
        <div class="card-panel p-4 h-100">
            <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
                <div>
                    <h6 class="fw-bold mb-0 text-dark">Proporsi Kelompok Pasien</h6>
                    <span class="text-muted small">TNI AD, PNS, BPJS, Purnawirawan & Umum</span>
                </div>
            </div>
            <div style="height: 280px; position: relative;">
                <canvas id="kelompokChart"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Poliklinik Top 10 Table & Chart -->
<div class="row g-3 mb-4">
    <!-- Top 10 Poli Bar Chart -->
    <div class="col-md-7">
        <div class="card-panel p-4 h-100">
            <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
                <h6 class="fw-bold mb-0 text-dark">10 Poliklinik Teramai</h6>
                <span class="text-muted small">Berdasarkan Total Kunjungan</span>
            </div>
            <div style="height: 300px;">
                <canvas id="poliChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Kelompok Detail Table -->
    <div class="col-md-5">
        <div class="card-panel p-4 h-100">
            <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
                <h6 class="fw-bold mb-0 text-dark">Ringkasan Kelompok Kepesertaan</h6>
                <a href="{{ route('reports.puskesad') }}" class="btn btn-sm btn-outline-rspad">Puskesad &rarr;</a>
            </div>
            <div class="table-responsive">
                <table class="table table-clean table-hover text-nowrap">
                    <thead>
                        <tr>
                            <th>Kelompok / Golongan</th>
                            <th class="text-center">Kunjungan</th>
                            <th class="text-center">Pengunjung</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($kelompokBreakdown as $k)
                        <tr>
                            <td class="fw-semibold text-dark">{{ $k->kelompok }}</td>
                            <td class="text-center fw-bold" style="color: var(--palette-5);">{{ number_format($k->total_kunjungan) }}</td>
                            <td class="text-center">{{ number_format($k->total_pengunjung) }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="3" class="text-center text-muted py-3">Belum ada data.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    // 1. Trend Line Chart (Kunjungan vs Pengunjung)
    const dailyData = @json($dailyTrend);
    const ctxTrend = document.getElementById('trendChart').getContext('2d');
    new Chart(ctxTrend, {
        type: 'line',
        data: {
            labels: dailyData.map(d => d.date),
            datasets: [
                {
                    label: 'Total Kunjungan (Kontak)',
                    data: dailyData.map(d => d.total_kunjungan),
                    borderColor: '#2A6A2A',
                    backgroundColor: 'rgba(42, 106, 42, 0.12)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.2
                },
                {
                    label: 'Total Pengunjung (Pasien Unik)',
                    data: dailyData.map(d => d.total_pengunjung),
                    borderColor: '#5DAA5D',
                    backgroundColor: 'transparent',
                    borderWidth: 2,
                    borderDash: [4, 4],
                    tension: 0.2
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'top', labels: { boxWidth: 12, font: { size: 11 } } }
            },
            scales: {
                x: { display: false, grid: { display: false }, ticks: { display: false } },
                y: { beginAtZero: true, grid: { color: '#f1f5f9' } }
            }
        }
    });

    // 2. Kelompok Doughnut Chart
    const kelompokData = @json($kelompokBreakdown);
    const ctxKelompok = document.getElementById('kelompokChart').getContext('2d');
    new Chart(ctxKelompok, {
        type: 'doughnut',
        data: {
            labels: kelompokData.map(k => k.kelompok),
            datasets: [{
                data: kelompokData.map(k => k.total_kunjungan),
                backgroundColor: [
                    '#2A6A2A', '#3B8A3B', '#5DAA5D', '#8CCB8C', '#B2E0B2', '#153815', '#64748B', '#0F172A'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'right', labels: { boxWidth: 12, font: { size: 10 } } }
            }
        }
    });

    // 3. Top 10 Poli Bar Chart
    const poliData = @json($poliBreakdown);
    const ctxPoli = document.getElementById('poliChart').getContext('2d');
    new Chart(ctxPoli, {
        type: 'bar',
        data: {
            labels: poliData.map(p => p.poliklinik),
            datasets: [{
                label: 'Jumlah Kunjungan',
                data: poliData.map(p => p.total),
                backgroundColor: '#3B8A3B',
                borderRadius: 4
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                x: { beginAtZero: true, grid: { color: '#f1f5f9' } },
                y: { grid: { display: false } }
            }
        }
    });
</script>
@endsection
