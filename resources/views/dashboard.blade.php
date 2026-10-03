@extends('layouts.app')

@section('title', 'Dashboard Rekapitulasi')

@section('content')
<!-- Filter & Header Bar -->
<div class="card-panel p-4 mb-4" style="border-left: 4px solid var(--palette-5);">
    <form action="{{ url()->current() }}" method="GET" class="row g-3 align-items-center">
        <div class="col-lg-4 col-md-12">
            <div>
                <h5 class="fw-bold mb-0 text-dark">Dashboard Rekapitulasi SIMRS</h5>
                <span class="text-muted small">
                    Periode Aktif: <strong>{{ $availableMonths[$month] ?? $month }} {{ $year == 'SEMUA' ? 'Semua Tahun' : $year }}</strong>
                </span>
            </div>
        </div>
        <div class="col-lg-3 col-md-4">
            <label class="form-label fw-semibold small text-muted mb-1">Pilih Bulan / Periode</label>
            <select name="month" class="form-select form-select-sm shadow-sm border-secondary-subtle">
                @foreach($availableMonths as $mNum => $mName)
                    <option value="{{ $mNum }}" {{ $month == (string)$mNum ? 'selected' : '' }}>
                        {{ $mName }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-lg-3 col-md-4">
            <label class="form-label fw-semibold small text-muted mb-1">Pilih Tahun</label>
            <select name="year" class="form-select form-select-sm shadow-sm border-secondary-subtle">
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
        <div class="col-lg-2 col-md-4 mt-auto">
            <button type="submit" class="btn btn-sm btn-rspad-primary w-100 py-2 fw-medium shadow-sm">
                Terapkan Filter
            </button>
        </div>
    </form>
</div>

<!-- Metrics Cards (4 Top Cards) -->
<div class="row g-3 mb-4">
    <!-- Card 1: Total Kunjungan -->
    <div class="col-xl-3 col-md-6">
        <div class="metric-card d-flex flex-column justify-content-between">
            <div class="mb-2">
                <span class="metric-title">TOTAL KUNJUNGAN</span>
            </div>
            <div>
                <div class="metric-value mb-1">{{ number_format($totalKunjungan) }}</div>
                <p class="metric-desc text-muted">Total Seluruh Kunjungan Saat Ini</p>
            </div>
        </div>
    </div>

    <!-- Card 2: Total Pengunjung -->
    <div class="col-xl-3 col-md-6">
        <div class="metric-card d-flex flex-column justify-content-between" style="border-top-color: #3B8A3B !important;">
            <div class="mb-2">
                <span class="metric-title">TOTAL PENGUNJUNG</span>
            </div>
            <div>
                <div class="metric-value mb-1" style="color: #3B8A3B !important;">{{ number_format($totalPengunjung) }}</div>
                <p class="metric-desc text-muted">Total Seluruh Pengunjung Saat Ini</p>
            </div>
        </div>
    </div>

    <!-- Card 3: Pengunjung Baru -->
    <div class="col-xl-3 col-md-6">
        <div class="metric-card d-flex flex-column justify-content-between" style="border-top-color: #0288D1 !important;">
            <div class="mb-2">
                <span class="metric-title">PENGUNJUNG BARU (RL 3.4)</span>
            </div>
            <div>
                <div class="metric-value mb-1" style="color: #0288D1 !important;">{{ number_format($pengunjungBaru) }}</div>
                <p class="metric-desc text-muted">
                    <span class="fw-semibold text-dark">{{ $totalPengunjung > 0 ? number_format(($pengunjungBaru / $totalPengunjung) * 100, 1) : 0 }}%</span> dari total pengunjung
                </p>
            </div>
        </div>
    </div>

    <!-- Card 4: Pengunjung Lama -->
    <div class="col-xl-3 col-md-6">
        <div class="metric-card d-flex flex-column justify-content-between" style="border-top-color: #7c3aed !important;">
            <div class="mb-2">
                <span class="metric-title">PENGUNJUNG LAMA (RL 3.4)</span>
            </div>
            <div>
                <div class="metric-value mb-1" style="color: #7c3aed !important;">{{ number_format($pengunjungLama) }}</div>
                <p class="metric-desc text-muted">
                    <span class="fw-semibold text-dark">{{ $totalPengunjung > 0 ? number_format(($pengunjungLama / $totalPengunjung) * 100, 1) : 0 }}%</span> Pengunjung Lama
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Visualizations Row 1: Line Chart & Doughnut Chart -->
<div class="row g-3 mb-4">
    <!-- Trend Line Chart -->
    <div class="col-lg-7 col-md-12">
        <div class="card-panel p-4 h-100 d-flex flex-column">
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 border-bottom pb-2 gap-2">
                <div>
                    <h6 class="fw-bold mb-0 text-dark">Grafik Tren Kunjungan dan Pengunjung</h6>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <select id="chartFilterSelect" class="form-select form-select-sm border-secondary-subtle" style="font-size: 0.8rem; font-weight: 600;">
                        <option value="SEMUA" {{ ($chartFilter ?? 'SEMUA') == 'SEMUA' ? 'selected' : '' }}>Semua Grafik</option>
                        <option value="KUNJUNGAN" {{ ($chartFilter ?? '') == 'KUNJUNGAN' ? 'selected' : '' }}>Hanya Kunjungan</option>
                        <option value="PENGUNJUNG" {{ ($chartFilter ?? '') == 'PENGUNJUNG' ? 'selected' : '' }}>Hanya Pengunjung</option>
                    </select>
                    <button type="button" class="btn btn-sm btn-outline-success d-inline-flex align-items-center justify-content-center p-1.5 px-2 rounded-2 shadow-xs" 
                            onclick="openChartDownloadModal('trendChart', 'Grafik Tren Kunjungan dan Pengunjung')" 
                            title="Unduh Grafik HD (PNG/JPEG)" style="height: 31px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                    </button>
                </div>
            </div>
            <div class="flex-grow-1 position-relative" style="height: 270px; min-height: 250px;">
                @if(count($dailyTrend) > 0)
                    <canvas id="trendChart"></canvas>
                @else
                    <div class="d-flex align-items-center justify-content-center h-100 text-muted">
                        Belum ada data tren untuk bulan ini.
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Kelompok Distribution Doughnut Chart -->
    <div class="col-lg-5 col-md-12">
        <div class="card-panel p-4 h-100 d-flex flex-column">
            <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
                <div>
                    <h6 class="fw-bold mb-0 text-dark">Proporsi Kelompok Pasien</h6>
                </div>
                <button type="button" class="btn btn-sm btn-outline-success d-inline-flex align-items-center justify-content-center p-1.5 px-2 rounded-2 shadow-xs" 
                        onclick="openChartDownloadModal('kelompokChart', 'Proporsi Kelompok Pasien')" 
                        title="Unduh Grafik HD (PNG/JPEG)" style="height: 31px;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                </button>
            </div>
            <div class="flex-grow-1 position-relative d-flex flex-column align-items-center justify-content-between">
                @if(count($kelompokBreakdown) > 0)
                    <div style="position: relative; width: 100%; height: 155px;" class="my-1">
                        <canvas id="kelompokChart"></canvas>
                    </div>
                    <div class="w-100 border-top pt-2 mt-auto">
                        <div style="max-height: 115px; overflow-y: auto; padding-right: 2px;">
                            <div class="row row-cols-2 g-1.5">
                                @foreach($kelompokBreakdown as $idx => $kb)
                                @php
                                    $paletteColors = ['#2A6A2A', '#3B8A3B', '#5DAA5D', '#8CCB8C', '#0288D1', '#7c3aed', '#f59e0b', '#06b6d4', '#ec4899', '#64748b'];
                                    $color = $paletteColors[$idx % count($paletteColors)];
                                    $pct = $totalKunjungan > 0 ? number_format(($kb->total_kunjungan / $totalKunjungan) * 100, 1) : 0;
                                @endphp
                                <div class="col">
                                    <div class="d-flex align-items-center justify-content-between px-2 py-1 rounded" style="background-color: #f8fafc; gap: 6px;">
                                        <div class="d-flex align-items-center text-truncate gap-2" style="min-width: 0;">
                                            <span class="rounded-circle flex-shrink-0" style="width: 8px; height: 8px; background-color: {{ $color }}; display: inline-block;"></span>
                                            <span class="fw-semibold text-dark text-truncate" style="font-size: 0.74rem;" title="{{ $kb->kelompok }}">{{ $kb->kelompok }}</span>
                                        </div>
                                        <span class="text-muted fw-bold flex-shrink-0" style="font-size: 0.72rem;">{{ number_format($kb->total_kunjungan) }} <small class="text-secondary fw-normal">({{ $pct }}%)</small></span>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @else
                    <div class="text-muted my-auto">
                        Belum ada data kelompok.
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Visualizations Row 2: Top 10 Poli Bar Chart & Ringkasan Kelompok Table -->
<div class="row g-3 mb-4">
    <!-- Top 10 Poli Bar Chart -->
    <div class="col-lg-7 col-md-12">
        <div class="card-panel p-4 h-100 d-flex flex-column">
            <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
                <div>
                    <h6 class="fw-bold mb-0 text-dark">10 Poliklinik Terbanyak</h6>
                    <span class="text-muted small">Poliklinik dengan total kunjungan tertinggi</span>
                </div>
                <span class="badge bg-light text-dark border">Berdasarkan Total Kunjungan</span>
            </div>
            <div class="flex-grow-1 position-relative" style="min-height: 320px;">
                @if(count($poliBreakdown) > 0)
                    <canvas id="poliChart"></canvas>
                @else
                    <div class="d-flex align-items-center justify-content-center h-100 text-muted">
                        Belum ada data poliklinik.
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Kelompok Detail Table -->
    <div class="col-lg-5 col-md-12">
        <div class="card-panel p-4 h-100 d-flex flex-column">
            <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
                <div>
                    <h6 class="fw-bold mb-0 text-dark">Ringkasan Kelompok Kepesertaan</h6>
                    <span class="text-muted small">Detail kunjungan & pengunjung per kategori</span>
                </div>
                <a href="{{ route('reports.puskesad') }}" class="btn btn-sm btn-outline-rspad px-3 fw-medium">
                    Puskesad &rarr;
                </a>
            </div>
            <div class="table-responsive flex-grow-1">
                <table class="table table-clean table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Kelompok / Golongan</th>
                            <th class="text-end">Kunjungan</th>
                            <th class="text-end">Pengunjung</th>
                            <th class="text-center" style="width: 25%;">% Vis</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($kelompokBreakdown as $k)
                        @php
                            $pct = $totalKunjungan > 0 ? ($k->total_kunjungan / $totalKunjungan) * 100 : 0;
                        @endphp
                        <tr>
                            <td class="fw-semibold text-dark">
                                <span class="d-inline-block rounded-circle me-2" style="width: 8px; height: 8px; background-color: var(--palette-5);"></span>
                                {{ $k->kelompok }}
                            </td>
                            <td class="text-end fw-bold" style="color: var(--palette-5);">{{ number_format($k->total_kunjungan) }}</td>
                            <td class="text-end text-muted">{{ number_format($k->total_pengunjung) }}</td>
                            <td class="text-center">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="progress flex-grow-1" style="height: 6px;">
                                        <div class="progress-bar" role="progressbar" style="width: {{ $pct }}%; background-color: var(--palette-5);" aria-valuenow="{{ $pct }}" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                    <span class="small text-muted fw-semibold" style="font-size: 0.75rem; min-width: 32px;">{{ number_format($pct, 1) }}%</span>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-4">Belum ada data rekapitulasi.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Visualizations Row 3: Recent Imports Audit Log -->
<div class="card-panel p-4 mb-2">
    <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
        <div>
            <h6 class="fw-bold mb-0 text-dark">Log Riwayat Import SIMRS Terakhir</h6>
            <span class="text-muted small">5 Berkas Excel SIMRS terakhir yang berhasil diunggah</span>
        </div>
        <a href="{{ route('imports.index') }}" class="btn btn-sm btn-rspad-primary px-3">
            Kelola Import SIMRS
        </a>
    </div>
    <div class="table-responsive">
        <table class="table table-clean table-hover text-nowrap mb-0">
            <thead>
                <tr>
                    <th>Nama Berkas Excel</th>
                    <th>Periode Laporan</th>
                    <th class="text-center">Total Baris</th>
                    <th>Petugas Import</th>
                    <th>Waktu Import</th>
                    <th class="text-center">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($recentImports as $log)
                <tr>
                    <td class="fw-semibold text-dark">
                        {{ $log->filename }}
                    </td>
                    <td>
                        <span class="badge bg-light text-dark border">
                            {{ $availableMonths[$log->period_month] ?? $log->period_month }} {{ $log->period_year }}
                        </span>
                    </td>
                    <td class="text-center fw-bold" style="color: var(--palette-5);">
                        {{ number_format($log->total_rows) }}
                    </td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <div class="rounded-circle text-white d-flex align-items-center justify-content-center fw-bold" style="width: 24px; height: 24px; background-color: var(--palette-4); font-size: 0.7rem;">
                                {{ strtoupper(substr($log->user->name ?? 'P', 0, 1)) }}
                            </div>
                            <span class="small">{{ $log->user->name ?? 'Petugas SIMRS' }}</span>
                        </div>
                    </td>
                    <td class="text-muted small">
                        {{ $log->created_at ? $log->created_at->format('d M Y, H:i') : '-' }} WIB
                    </td>
                    <td class="text-center">
                        <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                            Selesai
                        </span>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center text-muted py-4">Belum ada riwayat import berkas.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Global Chart Defaults
        Chart.defaults.font.family = "'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif";
        Chart.defaults.color = '#64748b';

        // Color Palette definitions
        const palette = [
            '#2A6A2A', // Dark Green
            '#3B8A3B', // Forest Green
            '#5DAA5D', // Medium Green
            '#8CCB8C', // Soft Sage
            '#0288D1', // Cyan/Blue
            '#7c3aed', // Purple
            '#f59e0b', // Amber
            '#06b6d4', // Teal
            '#ec4899', // Pink
            '#64748b'  // Slate
        ];

        // 1. Trend Line Chart (Kunjungan vs Pengunjung)
        const dailyData = @json($dailyTrend);
        const ctxTrend = document.getElementById('trendChart');

        if (ctxTrend && dailyData && dailyData.length > 0) {
            const labels = dailyData.map(d => {
                if (!d.date) return '';
                const parts = d.date.split('-');
                if (parts.length === 3) {
                    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
                    const mIdx = parseInt(parts[1], 10) - 1;
                    return `${parts[2]} ${months[mIdx] || ''}`;
                }
                return d.date;
            });

            const dsKunjungan = {
                label: 'Total Kunjungan (Kontak)',
                data: dailyData.map(d => d.total_kunjungan),
                borderColor: '#1e532b',
                backgroundColor: 'rgba(30, 83, 43, 0.15)',
                borderWidth: 2.5,
                pointRadius: 3,
                pointHoverRadius: 6,
                pointBackgroundColor: '#1e532b',
                fill: true,
                tension: 0.3
            };

            const dsPengunjung = {
                label: 'Total Pengunjung (Pasien Unik)',
                data: dailyData.map(d => d.total_pengunjung),
                borderColor: '#d97706',
                backgroundColor: 'rgba(217, 119, 6, 0.12)',
                borderWidth: 2.5,
                borderDash: [4, 4],
                pointRadius: 3.5,
                pointHoverRadius: 6,
                pointBackgroundColor: '#d97706',
                fill: true,
                tension: 0.3
            };

            const trendChartInstance = new Chart(ctxTrend.getContext('2d'), {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [dsKunjungan, dsPengunjung]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        mode: 'index',
                        intersect: false,
                    },
                    plugins: {
                        legend: {
                            position: 'top',
                            align: 'end',
                            labels: {
                                boxWidth: 14,
                                boxHeight: 14,
                                usePointStyle: true,
                                font: { size: 12, weight: '600' },
                                padding: 15
                            }
                        },
                        tooltip: {
                            backgroundColor: 'rgba(15, 23, 42, 0.9)',
                            padding: 12,
                            cornerRadius: 8,
                            titleFont: { size: 13, weight: 'bold' },
                            bodyFont: { size: 12 },
                            callbacks: {
                                label: function(context) {
                                    let label = context.dataset.label || '';
                                    if (label) { label += ': '; }
                                    if (context.parsed.y !== null) {
                                        label += new Intl.NumberFormat('id-ID').format(context.parsed.y) + ' orang';
                                    }
                                    return label;
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            display: false,
                        },
                        y: {
                            beginAtZero: true,
                            grid: { color: '#f1f5f9' },
                            ticks: {
                                font: { size: 11 },
                                callback: function(value) {
                                    return new Intl.NumberFormat('id-ID').format(value);
                                }
                            }
                        }
                    }
                }
            });

            // Handle live filtering of chart lines
            const chartFilterSelect = document.getElementById('chartFilterSelect');
            if (chartFilterSelect) {
                chartFilterSelect.addEventListener('change', function() {
                    const val = this.value;
                    if (val === 'KUNJUNGAN') {
                        trendChartInstance.setDatasetVisibility(0, true);
                        trendChartInstance.setDatasetVisibility(1, false);
                    } else if (val === 'PENGUNJUNG') {
                        trendChartInstance.setDatasetVisibility(0, false);
                        trendChartInstance.setDatasetVisibility(1, true);
                    } else {
                        trendChartInstance.setDatasetVisibility(0, true);
                        trendChartInstance.setDatasetVisibility(1, true);
                    }
                    trendChartInstance.update();
                });
            }
        }

        // 2. Kelompok Doughnut Chart
        const kelompokData = @json($kelompokBreakdown);
        const ctxKelompok = document.getElementById('kelompokChart');

        if (ctxKelompok && kelompokData && kelompokData.length > 0) {
            const totalVisits = kelompokData.reduce((acc, curr) => acc + Number(curr.total_kunjungan), 0);

            // Center Text Plugin
            const centerTextPlugin = {
                id: 'centerText',
                afterDraw(chart) {
                    const { ctx, chartArea: { left, top, width, height } } = chart;
                    ctx.save();
                    ctx.font = '700 18px Inter, sans-serif';
                    ctx.fillStyle = '#2A6A2A';
                    ctx.textAlign = 'center';
                    ctx.textBaseline = 'middle';
                    ctx.fillText(new Intl.NumberFormat('id-ID').format(totalVisits), left + width / 2, top + height / 2 - 8);
                    
                    ctx.font = '500 11px Inter, sans-serif';
                    ctx.fillStyle = '#64748b';
                    ctx.fillText('Kunjungan', left + width / 2, top + height / 2 + 12);
                    ctx.restore();
                }
            };

            new Chart(ctxKelompok.getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: kelompokData.map(k => k.kelompok),
                    datasets: [{
                        data: kelompokData.map(k => k.total_kunjungan),
                        backgroundColor: palette.slice(0, kelompokData.length),
                        borderWidth: 2,
                        borderColor: '#ffffff',
                        hoverOffset: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '70%',
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: 'rgba(15, 23, 42, 0.9)',
                            padding: 12,
                            cornerRadius: 8,
                            callbacks: {
                                label: function(context) {
                                    const val = context.parsed;
                                    const pct = totalVisits > 0 ? ((val / totalVisits) * 100).toFixed(1) : 0;
                                    return ` ${context.label}: ${new Intl.NumberFormat('id-ID').format(val)} (${pct}%)`;
                                }
                            }
                        }
                    }
                },
                plugins: [centerTextPlugin]
            });
        }

        // 3. Top 10 Poli Horizontal Bar Chart
        const poliData = @json($poliBreakdown);
        const ctxPoli = document.getElementById('poliChart');

        if (ctxPoli && poliData && poliData.length > 0) {
            new Chart(ctxPoli.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: poliData.map(p => p.poliklinik),
                    datasets: [{
                        label: 'Jumlah Kunjungan',
                        data: poliData.map(p => p.total),
                        backgroundColor: '#3B8A3B',
                        hoverBackgroundColor: '#2A6A2A',
                        borderRadius: 6,
                        barThickness: 18
                    }]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: 'rgba(15, 23, 42, 0.9)',
                            padding: 12,
                            cornerRadius: 8,
                            callbacks: {
                                label: function(context) {
                                    return ` Total Kunjungan: ${new Intl.NumberFormat('id-ID').format(context.parsed.x)} pasien`;
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            beginAtZero: true,
                            grid: { color: '#f1f5f9' },
                            ticks: {
                                font: { size: 11 },
                                callback: function(value) {
                                    return new Intl.NumberFormat('id-ID').format(value);
                                }
                            }
                        },
                        y: {
                            grid: { display: false },
                            ticks: { font: { size: 11, weight: '600' } }
                        }
                    }
                }
            });
        }
    });
</script>

<!-- Modal Download Grafik HD -->
<div class="modal fade" id="downloadChartModal" tabindex="-1" aria-labelledby="downloadChartModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px; overflow: hidden;">
            <div class="modal-header text-white py-3 px-4" style="background: linear-gradient(135deg, #1b4d3e 0%, #2e7d32 100%) !important;">
                <div class="d-flex align-items-center gap-2">
                    <div class="bg-white bg-opacity-20 rounded-circle p-2 d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                    </div>
                    <div>
                        <h6 class="modal-title fw-bold mb-0 text-white" id="downloadChartModalLabel">Unduh Grafik HD</h6>
                        <small class="text-white-50" style="font-size: 0.75rem;">Simpan grafik statistik ke format gambar resolusi tinggi</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <div class="modal-body p-4">
                <!-- Selected Chart Info -->
                <div class="alert alert-light border d-flex align-items-center gap-2 py-2 px-3 mb-3 rounded-3" style="background-color: #f8fafc;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" class="text-success flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 002 2h2a2 2 0 002-2z" />
                    </svg>
                    <div class="overflow-hidden">
                        <span class="text-muted d-block" style="font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 700;">Target Grafik</span>
                        <span id="targetChartTitleText" class="fw-bold text-dark text-truncate d-block" style="font-size: 0.88rem;">Grafik Tren Kunjungan</span>
                    </div>
                </div>
                
                <form id="chartDownloadForm">
                    <input type="hidden" id="targetChartId" value="trendChart">

                    <!-- 1. Format Gambar -->
                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark small mb-2">1. Pilih Format Gambar</label>
                        <div class="row g-2">
                            <div class="col-6">
                                <input type="radio" class="btn-check" name="imageFormat" id="formatPng" value="png" checked>
                                <label class="btn btn-outline-success w-100 py-2 px-3 text-start d-flex align-items-center justify-content-between rounded-3" for="formatPng">
                                    <div>
                                        <div class="fw-bold" style="font-size: 0.85rem;">PNG</div>
                                        <div class="text-muted" style="font-size: 0.68rem;">Format HD Jernih</div>
                                    </div>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill" style="font-size: 0.62rem;">Rekomendasi</span>
                                </label>
                            </div>
                            <div class="col-6">
                                <input type="radio" class="btn-check" name="imageFormat" id="formatJpeg" value="jpeg">
                                <label class="btn btn-outline-success w-100 py-2 px-3 text-start d-flex align-items-center justify-content-between rounded-3" for="formatJpeg">
                                    <div>
                                        <div class="fw-bold" style="font-size: 0.85rem;">JPEG</div>
                                        <div class="text-muted" style="font-size: 0.68rem;">Format Standar JPG</div>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- 2. Tingkat Kualitas / Resolusi HD -->
                    <div class="mb-3">
                        <label class="form-label fw-bold text-dark small mb-2">2. Pilih Kualitas & Resolusi Gambar</label>
                        <div class="d-flex flex-column gap-2">
                            <!-- Medium Quality -->
                            <input type="radio" class="btn-check" name="imageQuality" id="qualityMedium" value="1">
                            <label class="btn btn-outline-secondary py-2 px-3 text-start d-flex align-items-center justify-content-between rounded-3" for="qualityMedium">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge bg-secondary text-white rounded-2 px-2 py-1" style="font-size: 0.68rem;">1x</span>
                                    <div>
                                        <div class="fw-bold text-dark" style="font-size: 0.82rem;">Medium (Standar)</div>
                                        <div class="text-muted" style="font-size: 0.68rem;">~1200 x 600 px (Kualitas Tampilan Layar)</div>
                                    </div>
                                </div>
                            </label>

                            <!-- High Quality (Default) -->
                            <input type="radio" class="btn-check" name="imageQuality" id="qualityHigh" value="2" checked>
                            <label class="btn btn-outline-success py-2 px-3 text-start d-flex align-items-center justify-content-between rounded-3" for="qualityHigh">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge bg-success text-white rounded-2 px-2 py-1" style="font-size: 0.68rem;">2x</span>
                                    <div>
                                        <div class="fw-bold" style="font-size: 0.82rem;">High HD (Tinggi Tajam)</div>
                                        <div class="text-muted" style="font-size: 0.68rem;">~2400 x 1200 px (Resolusi Tajam HD)</div>
                                    </div>
                                </div>
                                <span class="badge bg-success text-white rounded-pill px-2 py-0.5" style="font-size: 0.62rem;">Populer</span>
                            </label>

                            <!-- Ultra HD 4K Quality -->
                            <input type="radio" class="btn-check" name="imageQuality" id="qualityUltra" value="3">
                            <label class="btn btn-outline-success py-2 px-3 text-start d-flex align-items-center justify-content-between rounded-3" for="qualityUltra">
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge bg-dark text-white rounded-2 px-2 py-1" style="font-size: 0.68rem;">3x</span>
                                    <div>
                                        <div class="fw-bold" style="font-size: 0.82rem;">Ultra HD / 4K (Super Jernih)</div>
                                        <div class="text-muted" style="font-size: 0.68rem;">~3600 x 1800 px (300 DPI / Cetak Dokumen)</div>
                                    </div>
                                </div>
                                <span class="badge bg-warning text-dark fw-bold rounded-pill px-2 py-0.5" style="font-size: 0.62rem;">MAX HD</span>
                            </label>
                        </div>
                    </div>

                    <!-- 3. Option Background -->
                    <div class="mb-1">
                        <label class="form-label fw-bold text-dark small mb-1">3. Latar Belakang Grafik</label>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="transparentBgCheck">
                            <label class="form-check-label text-dark small" for="transparentBgCheck">
                                Latar belakang transparan <small class="text-muted">(Khusus format PNG)</small>
                            </label>
                        </div>
                    </div>
                </form>
            </div>

            <div class="modal-footer bg-light px-4 py-3 border-top d-flex justify-content-between">
                <button type="button" class="btn btn-sm btn-outline-secondary px-3" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-sm btn-success fw-bold px-4 d-inline-flex align-items-center gap-2 shadow-sm" onclick="executeChartHDDownload()" style="background-color: #1b4d3e; border-color: #1b4d3e;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                    Unduh Gambar HD
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    // Handle toggle transparent checkbox based on format selection
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('input[name="imageFormat"]').forEach(radio => {
            radio.addEventListener('change', function() {
                const transparentCheck = document.getElementById('transparentBgCheck');
                if (this.value === 'jpeg') {
                    transparentCheck.checked = false;
                    transparentCheck.disabled = true;
                } else {
                    transparentCheck.disabled = false;
                }
            });
        });
    });

    function openChartDownloadModal(chartId, title) {
        const chartInstance = Chart.getChart(chartId);
        if (!chartInstance) {
            alert('Grafik belum siap atau data kosong.');
            return;
        }
        
        document.getElementById('targetChartId').value = chartId;
        document.getElementById('targetChartTitleText').innerText = title;
        
        const modalEl = document.getElementById('downloadChartModal');
        const modal = new bootstrap.Modal(modalEl);
        modal.show();
    }

    function executeChartHDDownload() {
        const chartId = document.getElementById('targetChartId').value;
        const chartTitle = document.getElementById('targetChartTitleText').innerText;
        const originalChart = Chart.getChart(chartId);
        
        if (!originalChart) {
            alert('Grafik tidak ditemukan!');
            return;
        }

        const format = document.querySelector('input[name="imageFormat"]:checked').value;
        const scaleLevel = parseInt(document.querySelector('input[name="imageQuality"]:checked').value, 10);
        const isTransparent = document.getElementById('transparentBgCheck').checked && format === 'png';

        // Scale factors for crisp High-DPI rendering
        const dpiMultiplier = scaleLevel === 3 ? 3.5 : (scaleLevel === 2 ? 2.0 : 1.0);
        
        // Base canvas aspect ratio for export graphics
        let baseWidth = 1400;
        let baseHeight = 780;

        if (chartId === 'kelompokChart') {
            baseWidth = 1500;
            baseHeight = 820;
        } else if (chartId === 'poliChart') {
            baseWidth = 1400;
            baseHeight = 850;
        }

        const targetWidth = Math.round(baseWidth * dpiMultiplier);
        const targetHeight = Math.round(baseHeight * dpiMultiplier);

        const offscreenCanvas = document.createElement('canvas');
        offscreenCanvas.width = targetWidth;
        offscreenCanvas.height = targetHeight;
        const offscreenCtx = offscreenCanvas.getContext('2d');

        // Fill background
        if (!isTransparent || format === 'jpeg') {
            offscreenCtx.fillStyle = '#ffffff';
            offscreenCtx.fillRect(0, 0, targetWidth, targetHeight);
        }

        const originalConfig = originalChart.config;
        const sFont = (size) => Math.round(size * dpiMultiplier);

        // Header Banner Plugin
        const headerBannerPlugin = {
            id: 'exportHeaderBanner',
            beforeDraw(chart) {
                const ctx = chart.ctx;
                ctx.save();
                
                // Top header bg
                ctx.fillStyle = '#f8fafc';
                ctx.fillRect(0, 0, chart.width, sFont(70));
                
                // Green border line
                ctx.fillStyle = '#1b4d3e';
                ctx.fillRect(0, sFont(67), chart.width, sFont(3));

                // Title Text
                ctx.font = `bold ${sFont(18)}px Inter, sans-serif`;
                ctx.fillStyle = '#1b4d3e';
                ctx.fillText(`RSPAD GATOT SOEBROTO - ${chartTitle.toUpperCase()}`, sFont(25), sFont(32));

                // Subtitle
                ctx.font = `500 ${sFont(11)}px Inter, sans-serif`;
                ctx.fillStyle = '#64748b';
                ctx.fillText(`Dokumen Resmi Pelaporan & Grafik Statistik RSPAD Gatot Soebroto Jakarta Pusat`, sFont(25), sFont(54));

                ctx.restore();
            }
        };

        // White background plugin
        const whiteBackgroundPlugin = {
            id: 'hdWhiteBackground',
            beforeDraw: (chart) => {
                if (!isTransparent || format === 'jpeg') {
                    const ctx = chart.ctx;
                    ctx.save();
                    ctx.globalCompositeOperation = 'destination-over';
                    ctx.fillStyle = '#ffffff';
                    ctx.fillRect(0, 0, chart.width, chart.height);
                    ctx.restore();
                }
            }
        };

        // Deep clone datasets
        const clonedDatasets = originalConfig.data.datasets.map((ds, dsIdx) => {
            const newDs = Object.assign({}, ds);
            newDs.borderWidth = (ds.borderWidth || 2) * Math.max(1, dpiMultiplier * 0.85);
            newDs.pointRadius = (ds.pointRadius || 3) * Math.max(1, dpiMultiplier * 0.9);
            newDs.pointHoverRadius = (ds.pointHoverRadius || 5) * dpiMultiplier;
            return newDs;
        });

        const customPlugins = [whiteBackgroundPlugin, headerBannerPlugin];

        // Layout paddings for top banner & side legends
        const exportPadding = {
            top: sFont(85),
            bottom: sFont(25),
            left: sFont(30),
            right: chartId === 'kelompokChart' ? sFont(420) : sFont(45)
        };

        let tempOptions = {
            responsive: false,
            maintainAspectRatio: false,
            animation: false,
            devicePixelRatio: 1,
            layout: { padding: exportPadding },
            plugins: {
                legend: { display: false }
            }
        };

        // SPECIFIC CUSTOMIZATIONS PER CHART TYPE
        if (chartId === 'trendChart') {
            // SHOW DATES ON X-AXIS & TOP LEGEND
            tempOptions.plugins.legend = {
                display: true,
                position: 'top',
                align: 'end',
                labels: {
                    font: { size: sFont(13), weight: 'bold', family: "'Inter', sans-serif" },
                    boxWidth: sFont(16),
                    boxHeight: sFont(16),
                    padding: sFont(18),
                    usePointStyle: true
                }
            };
            
            tempOptions.scales = {
                x: {
                    display: true,
                    grid: { color: '#e2e8f0' },
                    ticks: {
                        font: { size: sFont(11), weight: 'bold', family: "'Inter', sans-serif" },
                        color: '#334155',
                        maxRotation: 45,
                        minRotation: 0
                    }
                },
                y: {
                    display: true,
                    beginAtZero: true,
                    grid: { color: '#f1f5f9' },
                    ticks: {
                        font: { size: sFont(12), weight: '600', family: "'Inter', sans-serif" },
                        color: '#475569',
                        callback: function(val) { return new Intl.NumberFormat('id-ID').format(val); }
                    }
                }
            };

            // DATA LABELS PLUGIN FOR TREND CHART (Numbers above/below data points)
            const trendDataLabelsPlugin = {
                id: 'trendDataLabels',
                afterDatasetsDraw(chart) {
                    const ctx = chart.ctx;
                    ctx.save();
                    
                    chart.data.datasets.forEach((dataset, datasetIndex) => {
                        const meta = chart.getDatasetMeta(datasetIndex);
                        if (meta.hidden) return;

                        const isKunjungan = datasetIndex === 0;
                        ctx.fillStyle = isKunjungan ? '#1e532b' : '#d97706';
                        ctx.font = `bold ${sFont(10.5)}px Inter, sans-serif`;
                        ctx.textAlign = 'center';

                        meta.data.forEach((element, index) => {
                            const val = dataset.data[index];
                            if (val !== null && val !== undefined) {
                                const formattedVal = new Intl.NumberFormat('id-ID').format(val);
                                if (isKunjungan) {
                                    ctx.textBaseline = 'bottom';
                                    ctx.fillText(formattedVal, element.x, element.y - sFont(5));
                                } else {
                                    ctx.textBaseline = 'top';
                                    ctx.fillText(formattedVal, element.x, element.y + sFont(5));
                                }
                            }
                        });
                    });
                    
                    ctx.restore();
                }
            };
            customPlugins.push(trendDataLabelsPlugin);

        } else if (chartId === 'kelompokChart') {
            // DOUGHNUT CHART: CENTER TEXT & FULL SIDE LEGEND
            const kelompokList = @json($kelompokBreakdown);
            const totalVisitsAll = kelompokList.reduce((acc, curr) => acc + Number(curr.total_kunjungan), 0);

            // Center Text Plugin
            const centerTextPluginHD = {
                id: 'centerTextHD',
                afterDraw(chart) {
                    const { ctx, chartArea: { left, top, width, height } } = chart;
                    ctx.save();
                    ctx.font = `700 ${sFont(24)}px Inter, sans-serif`;
                    ctx.fillStyle = '#1b4d3e';
                    ctx.textAlign = 'center';
                    ctx.textBaseline = 'middle';
                    ctx.fillText(new Intl.NumberFormat('id-ID').format(totalVisitsAll), left + width / 2, top + height / 2 - sFont(10));
                    
                    ctx.font = `600 ${sFont(13)}px Inter, sans-serif`;
                    ctx.fillStyle = '#64748b';
                    ctx.fillText('Kunjungan Pasien', left + width / 2, top + height / 2 + sFont(15));
                    ctx.restore();
                }
            };
            customPlugins.push(centerTextPluginHD);

            // Side Legend Plugin (Keterangan Warna)
            const sideLegendPlugin = {
                id: 'sideLegend',
                afterDraw(chart) {
                    const ctx = chart.ctx;
                    ctx.save();

                    const chartArea = chart.chartArea;
                    const startX = chartArea.right + sFont(40);
                    let startY = chartArea.top + sFont(15);

                    ctx.font = `bold ${sFont(14)}px Inter, sans-serif`;
                    ctx.fillStyle = '#0f172a';
                    ctx.fillText('KETERANGAN KELOMPOK PASIEN:', startX, startY);
                    startY += sFont(28);

                    const paletteColors = ['#2A6A2A', '#3B8A3B', '#5DAA5D', '#8CCB8C', '#0288D1', '#7c3aed', '#f59e0b', '#06b6d4', '#ec4899', '#64748b'];
                    
                    kelompokList.forEach((item, idx) => {
                        const color = paletteColors[idx % paletteColors.length];
                        const countFormatted = new Intl.NumberFormat('id-ID').format(item.total_kunjungan);
                        const pct = totalVisitsAll > 0 ? ((item.total_kunjungan / totalVisitsAll) * 100).toFixed(1) : '0';

                        // Color Dot
                        ctx.fillStyle = color;
                        ctx.beginPath();
                        ctx.arc(startX + sFont(8), startY - sFont(5), sFont(6), 0, Math.PI * 2);
                        ctx.fill();

                        // Label
                        ctx.font = `600 ${sFont(12)}px Inter, sans-serif`;
                        ctx.fillStyle = '#1e293b';
                        const labelText = `${item.kelompok}: `;
                        ctx.fillText(labelText, startX + sFont(22), startY);

                        const labelW = ctx.measureText(labelText).width;
                        ctx.font = `bold ${sFont(12)}px Inter, sans-serif`;
                        ctx.fillStyle = '#0f172a';
                        ctx.fillText(`${countFormatted} (${pct}%)`, startX + sFont(22) + labelW, startY);

                        startY += sFont(26);
                    });

                    ctx.restore();
                }
            };
            customPlugins.push(sideLegendPlugin);

        } else if (chartId === 'poliChart') {
            // TOP 10 POLI BAR CHART: VALUE LABELS AT BAR END
            tempOptions.scales = {
                x: {
                    display: true,
                    beginAtZero: true,
                    grid: { color: '#f1f5f9' },
                    ticks: {
                        font: { size: sFont(11), weight: '600', family: "'Inter', sans-serif" },
                        callback: function(val) { return new Intl.NumberFormat('id-ID').format(val); }
                    }
                },
                y: {
                    display: true,
                    grid: { display: false },
                    ticks: { font: { size: sFont(12), weight: 'bold', family: "'Inter', sans-serif" }, color: '#1e293b' }
                }
            };

            const poliBarLabelsPlugin = {
                id: 'poliBarLabels',
                afterDatasetsDraw(chart) {
                    const ctx = chart.ctx;
                    ctx.save();
                    ctx.font = `bold ${sFont(12)}px Inter, sans-serif`;
                    ctx.fillStyle = '#1e532b';
                    ctx.textAlign = 'left';
                    ctx.textBaseline = 'middle';

                    const meta = chart.getDatasetMeta(0);
                    meta.data.forEach((bar, index) => {
                        const val = chart.data.datasets[0].data[index];
                        if (val !== null && val !== undefined) {
                            const formattedVal = new Intl.NumberFormat('id-ID').format(val) + ' pasien';
                            ctx.fillText(formattedVal, bar.x + sFont(8), bar.y);
                        }
                    });

                    ctx.restore();
                }
            };
            customPlugins.push(poliBarLabelsPlugin);
        }

        // Render offscreen chart
        const tempChart = new Chart(offscreenCtx, {
            type: originalConfig.type,
            data: {
                labels: originalConfig.data.labels,
                datasets: clonedDatasets
            },
            options: tempOptions,
            plugins: customPlugins
        });

        tempChart.update();

        // Export image Data URL
        const mimeType = format === 'jpeg' ? 'image/jpeg' : 'image/png';
        const qualityParam = format === 'jpeg' ? 0.98 : undefined;
        const imageDataUrl = offscreenCanvas.toDataURL(mimeType, qualityParam);

        tempChart.destroy();

        // Download file
        const dateStr = new Date().toISOString().slice(0, 10);
        const cleanTitle = chartTitle.replace(/[^a-zA-Z0-9_\-]/g, '_');
        const qualityLabel = scaleLevel === 3 ? '4K_UltraHD' : (scaleLevel === 2 ? 'High_HD' : 'Medium');
        const filename = `${cleanTitle}_${dateStr}_${qualityLabel}.${format}`;

        const link = document.createElement('a');
        link.download = filename;
        link.href = imageDataUrl;
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);

        // Close modal
        const modalEl = document.getElementById('downloadChartModal');
        const modal = bootstrap.Modal.getInstance(modalEl);
        if (modal) {
            modal.hide();
        }
    }
</script>
@endsection
