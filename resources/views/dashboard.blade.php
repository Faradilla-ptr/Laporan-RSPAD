@extends('layouts.app')

@section('title', 'Dashboard Rekapitulasi')

@section('content')
<!-- Filter & Header Bar -->
<div class="card-panel p-4 mb-4" style="border-left: 5px solid var(--palette-5);">
    <form action="{{ route('dashboard') }}" method="GET" class="row g-3 align-items-center">
        <div class="col-lg-5 col-md-12">
            <div>
                <h5 class="fw-bold mb-0 text-dark">Dashboard Rekapitulasi Pelaporan SIMRS</h5>
                <span class="text-muted small">
                    Periode Aktif: <strong>{{ $availableMonths[$month] ?? '' }} {{ $year }}</strong>
                </span>
            </div>
        </div>
        <div class="col-lg-3 col-md-4">
            <label class="form-label fw-semibold small text-muted mb-1"><i class="bi bi-calendar-month me-1"></i>Pilih Bulan</label>
            <select name="month" class="form-select form-select-sm shadow-sm border-secondary-subtle">
                @foreach($availableMonths as $mNum => $mName)
                    <option value="{{ $mNum }}" {{ $month == $mNum ? 'selected' : '' }}>
                        {{ $mName }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-lg-2 col-md-4">
            <label class="form-label fw-semibold small text-muted mb-1"><i class="bi bi-calendar-event me-1"></i>Pilih Tahun</label>
            <select name="year" class="form-select form-select-sm shadow-sm border-secondary-subtle">
                @foreach(range(2024, 2030) as $y)
                    <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-lg-2 col-md-4 mt-auto">
            <button type="submit" class="btn btn-sm btn-rspad-primary w-100 py-2 fw-medium shadow-sm d-flex align-items-center justify-content-center gap-1">
                <i class="bi bi-funnel-fill"></i> Terapkan Filter
            </button>
        </div>
    </form>
</div>

<!-- Metrics Cards (4 Top Cards) -->
<div class="row g-3 mb-4">
    <!-- Card 1: Total Kunjungan -->
    <div class="col-xl-3 col-md-6">
        <div class="metric-card d-flex flex-column justify-content-between">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="metric-title">TOTAL KUNJUNGAN</span>
                <div class="rounded-circle p-2 d-flex align-items-center justify-content-center" style="background-color: rgba(42, 106, 42, 0.1); color: var(--palette-5); width: 38px; height: 38px;">
                    <i class="bi bi-journal-text fs-5"></i>
                </div>
            </div>
            <div>
                <div class="metric-value mb-1">{{ number_format($totalKunjungan) }}</div>
                <p class="metric-desc text-muted"><i class="bi bi-info-circle me-1"></i>Total transaksi kontak registrasi</p>
            </div>
        </div>
    </div>

    <!-- Card 2: Total Pengunjung -->
    <div class="col-xl-3 col-md-6">
        <div class="metric-card d-flex flex-column justify-content-between" style="border-top-color: #3B8A3B !important;">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="metric-title">TOTAL PENGUNJUNG</span>
                <div class="rounded-circle p-2 d-flex align-items-center justify-content-center" style="background-color: rgba(59, 138, 59, 0.1); color: #3B8A3B; width: 38px; height: 38px;">
                    <i class="bi bi-people-fill fs-5"></i>
                </div>
            </div>
            <div>
                <div class="metric-value mb-1" style="color: #3B8A3B !important;">{{ number_format($totalPengunjung) }}</div>
                <p class="metric-desc text-muted"><i class="bi bi-person-check me-1"></i>Pasien Unik (Count Distinct RM)</p>
            </div>
        </div>
    </div>

    <!-- Card 3: Pengunjung Baru -->
    <div class="col-xl-3 col-md-6">
        <div class="metric-card d-flex flex-column justify-content-between" style="border-top-color: #0288D1 !important;">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="metric-title">PENGUNJUNG BARU (RL 3.4)</span>
                <div class="rounded-circle p-2 d-flex align-items-center justify-content-center" style="background-color: rgba(2, 136, 209, 0.1); color: #0288D1; width: 38px; height: 38px;">
                    <i class="bi bi-person-plus-fill fs-5"></i>
                </div>
            </div>
            <div>
                <div class="metric-value mb-1" style="color: #0288D1 !important;">{{ number_format($pengunjungBaru) }}</div>
                <p class="metric-desc text-muted">
                    <span class="fw-semibold text-dark">{{ $totalPengunjung > 0 ? number_format(($pengunjungBaru / $totalPengunjung) * 100, 1) : 0 }}%</span> dari total pengunjung unik
                </p>
            </div>
        </div>
    </div>

    <!-- Card 4: Pengunjung Lama -->
    <div class="col-xl-3 col-md-6">
        <div class="metric-card d-flex flex-column justify-content-between" style="border-top-color: #7c3aed !important;">
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="metric-title">PENGUNJUNG LAMA (RL 3.4)</span>
                <div class="rounded-circle p-2 d-flex align-items-center justify-content-center" style="background-color: rgba(124, 58, 237, 0.1); color: #7c3aed; width: 38px; height: 38px;">
                    <i class="bi bi-arrow-repeat fs-5"></i>
                </div>
            </div>
            <div>
                <div class="metric-value mb-1" style="color: #7c3aed !important;">{{ number_format($pengunjungLama) }}</div>
                <p class="metric-desc text-muted">
                    <span class="fw-semibold text-dark">{{ $totalPengunjung > 0 ? number_format(($pengunjungLama / $totalPengunjung) * 100, 1) : 0 }}%</span> pasien berobat ulangan
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
            <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
                <div>
                    <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-graph-up-arrow me-2" style="color: var(--palette-5);"></i>Grafik Tren Kunjungan vs Pengunjung</h6>
                    <span class="text-muted small">Perbandingan total kontak registrasi vs pasien unik per hari</span>
                </div>
                <span class="badge px-2 py-1" style="background-color: var(--palette-1); color: var(--palette-5); font-weight: 600;">
                    {{ $availableMonths[$month] ?? '' }} {{ $year }}
                </span>
            </div>
            <div class="flex-grow-1 position-relative" style="min-height: 310px;">
                @if(count($dailyTrend) > 0)
                    <canvas id="trendChart"></canvas>
                @else
                    <div class="d-flex align-items-center justify-content-center h-100 text-muted">
                        <i class="bi bi-info-circle me-2"></i> Belum ada data tren untuk bulan ini.
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
                    <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-pie-chart-fill me-2" style="color: var(--palette-5);"></i>Proporsi Kelompok Pasien</h6>
                    <span class="text-muted small">Distribusi kepesertaan Militer, PNS, BPJS & Umum</span>
                </div>
            </div>
            <div class="flex-grow-1 position-relative d-flex align-items-center justify-content-center" style="min-height: 310px;">
                @if(count($kelompokBreakdown) > 0)
                    <canvas id="kelompokChart"></canvas>
                @else
                    <div class="text-muted">
                        <i class="bi bi-info-circle me-2"></i> Belum ada data kelompok.
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
                    <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-bar-chart-line-fill me-2" style="color: var(--palette-5);"></i>10 Poliklinik Teramai</h6>
                    <span class="text-muted small">Poliklinik dengan total kunjungan tertinggi</span>
                </div>
                <span class="badge bg-light text-dark border">Berdasarkan Total Kunjungan</span>
            </div>
            <div class="flex-grow-1 position-relative" style="min-height: 320px;">
                @if(count($poliBreakdown) > 0)
                    <canvas id="poliChart"></canvas>
                @else
                    <div class="d-flex align-items-center justify-content-center h-100 text-muted">
                        <i class="bi bi-info-circle me-2"></i> Belum ada data poliklinik.
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
                    <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-table me-2" style="color: var(--palette-5);"></i>Ringkasan Kelompok Kepesertaan</h6>
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
                                <span class="d-inline-block rounded-circle me-2" style="width: 9px; height: 9px; background-color: var(--palette-5);"></span>
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
            <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-clock-history me-2" style="color: var(--palette-5);"></i>Log Riwayat Import SIMRS Terakhir</h6>
            <span class="text-muted small">5 Berkas Excel SIMRS terakhir yang berhasil diunggah dan diperbarui</span>
        </div>
        <a href="{{ route('imports.index') }}" class="btn btn-sm btn-rspad-primary px-3">
            <i class="bi bi-upload me-1"></i> Kelola Import SIMRS
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
                        <i class="bi bi-file-earmark-excel-fill text-success me-2 fs-6"></i>
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
                            <i class="bi bi-check-circle-fill me-1"></i> Selesai
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

            new Chart(ctxTrend.getContext('2d'), {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Total Kunjungan (Kontak)',
                            data: dailyData.map(d => d.total_kunjungan),
                            borderColor: '#2A6A2A',
                            backgroundColor: 'rgba(42, 106, 42, 0.12)',
                            borderWidth: 2.5,
                            pointRadius: 3,
                            pointHoverRadius: 6,
                            pointBackgroundColor: '#2A6A2A',
                            fill: true,
                            tension: 0.3
                        },
                        {
                            label: 'Total Pengunjung (Pasien Unik)',
                            data: dailyData.map(d => d.total_pengunjung),
                            borderColor: '#3B8A3B',
                            backgroundColor: 'transparent',
                            borderWidth: 2,
                            borderDash: [5, 5],
                            pointRadius: 3,
                            pointHoverRadius: 6,
                            pointBackgroundColor: '#3B8A3B',
                            tension: 0.3
                        }
                    ]
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
                            display: true,
                            grid: { display: false },
                            ticks: { font: { size: 11 }, maxRotation: 45 }
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
                        legend: {
                            position: 'bottom',
                            labels: {
                                boxWidth: 12,
                                boxHeight: 12,
                                font: { size: 11, weight: '500' },
                                padding: 12
                            }
                        },
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
@endsection
