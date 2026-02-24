@extends('layouts.app')

@section('content')
<div class="dashboard-container">
    <div class="dashboard-header mb-5">
        <div>
            <h1 class="display-5 fw-bold mb-2" style="color: #0d3b66;">Welcome back, {{ Auth::user()->name }}! 👋</h1>
            <p class="text-muted fs-5">{{ now()->format('l, F d, Y') }}</p>
        </div>
    </div>

    @if(count($anomalies) > 0)
        <div class="anomalies-section mb-4">
            @foreach($anomalies as $anomaly)
                <div class="alert alert-{{ $anomaly['type'] }} alert-dismissible fade show alerts-custom" role="alert">
                    <div class="d-flex gap-3 align-items-start">
                        <i class="fas {{ $anomaly['icon'] }} fa-lg mt-1"></i>
                        <div class="flex-grow-1">
                            <strong class="d-block mb-1">{{ $anomaly['title'] }}</strong>
                            <p class="mb-0 text-opacity-85">{{ $anomaly['message'] }}</p>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <div class="row g-4 mb-5">
        <div class="col-lg-3 col-md-6 col-sm-6">
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #0d3b66 0%, #1a5490 100%);">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-content">
                    <p class="stat-label">Kehadiran Hari Ini</p>
                    <h2 class="stat-value">{{ $totalAttendanceToday }}</h2>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 col-sm-6">
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #e63946 0%, #f45e67 100%);">
                    <i class="fas fa-times-circle"></i>
                </div>
                <div class="stat-content">
                    <p class="stat-label">Absensi Hari Ini</p>
                    <h2 class="stat-value">{{ $totalAbsenceToday }}</h2>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 col-sm-6">
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #f77f00 0%, #fb9e2e 100%);">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <div class="stat-content">
                    <p class="stat-label">Hadir Bulan Ini</p>
                    <h2 class="stat-value">{{ $monthlyStats['present'] }}</h2>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 col-sm-6">
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #06a77d 0%, #14c7a5 100%);">
                    <i class="fas fa-hourglass-half"></i>
                </div>
                <div class="stat-content">
                    <p class="stat-label">Permintaan Tertunda</p>
                    <h2 class="stat-value">{{ $pendingAbsences }}</h2>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-5">
        <div class="col-lg-8">
            <div class="chart-card">
                <div class="chart-header">
                    <h5 class="mb-0">Ringkasan Kehadiran 7 Hari</h5>
                </div>
                <div class="chart-body">
                    <canvas id="attendanceChart"></canvas>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="chart-card">
                <div class="chart-header">
                    <h5 class="mb-0">Distribusi Status Bulanan</h5>
                </div>
                <div class="chart-body">
                    <canvas id="monthlyStatsChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="chart-card">
                <div class="chart-header">
                    <h5 class="mb-0">Ringkasan Absensi 7 Hari</h5>
                </div>
                <div class="chart-body">
                    <canvas id="absenceChart"></canvas>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="chart-card">
                <div class="chart-header">
                    <h5 class="mb-0">Permintaan Absensi Terbaru</h5>
                </div>
                <div class="absence-list">
                    @forelse($recentAbsences as $absence)
                        <div class="absence-item">
                            <div class="absence-info">
                                <p class="absence-name">{{ $absence->employee_name }}</p>
                                <small class="absence-details">{{ $absence->date->format('M d, Y') }} • {{ ucfirst($absence->reason) }}</small>
                            </div>
                            <span class="badge-status bg-{{ $absence->status == 'pending' ? 'warning' : ($absence->status == 'approved' ? 'success' : 'danger') }}">
                                {{ ucfirst($absence->status) }}
                            </span>
                        </div>
                    @empty
                        <div class="empty-state">
                            <i class="fas fa-inbox"></i>
                            <p>Tidak ada permintaan absensi terbaru</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .dashboard-container {
        animation: fadeIn 0.6s ease-in-out;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .dashboard-header {
        padding: 10px 0;
    }

    .alerts-custom {
        background: white;
        border-left: 4px solid;
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
        animation: slideIn 0.4s ease-out;
    }

    @keyframes slideIn {
        from {
            opacity: 0;
            transform: translateX(-20px);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }

    .stat-card {
        background: white;
        border-radius: 16px;
        padding: 25px;
        display: flex;
        align-items: center;
        gap: 20px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        border: 1px solid rgba(0, 0, 0, 0.05);
    }

    .stat-card:hover {
        transform: translateY(-12px);
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.12);
    }

    .stat-icon {
        width: 70px;
        height: 70px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 32px;
        flex-shrink: 0;
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
    }

    .stat-content {
        flex: 1;
    }

    .stat-label {
        color: #6b7280;
        font-size: 13px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin: 0 0 8px 0;
    }

    .stat-value {
        color: #0d3b66;
        font-size: 32px;
        font-weight: 700;
        margin: 0;
    }

    .chart-card {
        background: white;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        border: 1px solid rgba(0, 0, 0, 0.05);
    }

    .chart-card:hover {
        box-shadow: 0 15px 40px rgba(0, 0, 0, 0.12);
    }

    .chart-header {
        background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        padding: 24px 28px;
        border-bottom: 1px solid rgba(0, 0, 0, 0.05);
    }

    .chart-header h5 {
        color: #0d3b66;
        font-weight: 700;
        font-size: 16px;
    }

    .chart-body {
        padding: 28px;
        position: relative;
        height: 350px;
    }

    .absence-list {
        padding: 28px;
    }

    .absence-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 18px 0;
        border-bottom: 1px solid rgba(0, 0, 0, 0.06);
        transition: all 0.3s ease;
    }

    .absence-item:last-child {
        border-bottom: none;
    }

    .absence-item:hover {
        background: rgba(13, 59, 102, 0.02);
        padding-left: 10px;
    }

    .absence-info {
        flex: 1;
    }

    .absence-name {
        font-weight: 600;
        color: #1f2937;
        margin: 0 0 6px 0;
        font-size: 15px;
    }

    .absence-details {
        color: #9ca3af;
        font-size: 13px;
        margin: 0;
    }

    .badge-status {
        padding: 6px 14px;
        border-radius: 8px;
        font-size: 12px;
        font-weight: 600;
        color: white;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }

    .empty-state {
        text-align: center;
        padding: 40px 20px;
        color: #9ca3af;
    }

    .empty-state i {
        font-size: 48px;
        color: #d1d5db;
        margin-bottom: 12px;
        display: block;
    }

    .empty-state p {
        margin: 0;
        font-size: 15px;
    }

    @media (max-width: 768px) {
        .stat-card {
            padding: 20px;
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            font-size: 28px;
        }

        .stat-value {
            font-size: 28px;
        }

        .chart-body {
            height: 280px;
        }
    }
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const labels = @json($labels);
        const presentData = @json($presentData);
        const absentData = @json($absentData);
        const lateData = @json($lateData);
        const absenceData = @json($absenceData);

        const colors = {
            primary: '#0d3b66',
            danger: '#e63946',
            warning: '#f77f00',
            success: '#06a77d'
        };

        const chartDefaults = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    labels: {
                        usePointStyle: true,
                        padding: 20,
                        font: { size: 13, weight: 600 },
                        color: '#1f2937'
                    }
                }
            }
        };

        if(document.getElementById('attendanceChart')) {
            new Chart(document.getElementById('attendanceChart'), {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Hadir',
                            data: presentData,
                            borderColor: colors.primary,
                            backgroundColor: 'rgba(13, 59, 102, 0.08)',
                            borderWidth: 3,
                            fill: true,
                            tension: 0.4,
                            pointRadius: 6,
                            pointBackgroundColor: colors.primary,
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2,
                            pointHoverRadius: 8,
                        },
                        {
                            label: 'Absen',
                            data: absentData,
                            borderColor: colors.danger,
                            backgroundColor: 'rgba(230, 57, 70, 0.08)',
                            borderWidth: 3,
                            fill: true,
                            tension: 0.4,
                            pointRadius: 6,
                            pointBackgroundColor: colors.danger,
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2,
                            pointHoverRadius: 8,
                        },
                        {
                            label: 'Terlambat',
                            data: lateData,
                            borderColor: colors.warning,
                            backgroundColor: 'rgba(247, 127, 0, 0.08)',
                            borderWidth: 3,
                            fill: true,
                            tension: 0.4,
                            pointRadius: 6,
                            pointBackgroundColor: colors.warning,
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2,
                            pointHoverRadius: 8,
                        }
                    ]
                },
                options: {
                    ...chartDefaults,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: { stepSize: 1, color: '#6b7280', font: { weight: 500 } },
                            grid: { drawBorder: false, color: 'rgba(0, 0, 0, 0.05)' }
                        },
                        x: {
                            grid: { display: false, drawBorder: false },
                            ticks: { color: '#6b7280', font: { weight: 500 } }
                        }
                    }
                }
            });
        }

        if(document.getElementById('monthlyStatsChart')) {
            new Chart(document.getElementById('monthlyStatsChart'), {
                type: 'doughnut',
                data: {
                    labels: ['Hadir', 'Absen', 'Terlambat', 'Izin', 'Sakit'],
                    datasets: [{
                        data: [@json($monthlyStats['present']), @json($monthlyStats['absent']), @json($monthlyStats['late']), @json($monthlyStats['leave']), @json($monthlyStats['sick'])],
                        backgroundColor: [
                            colors.primary,
                            colors.danger,
                            colors.warning,
                            '#3b5998',
                            '#6c757d'
                        ],
                        borderColor: '#fff',
                        borderWidth: 3
                    }]
                },
                options: {
                    ...chartDefaults,
                    plugins: {
                        ...chartDefaults.plugins,
                        legend: {
                            position: 'bottom',
                            labels: {
                                usePointStyle: true,
                                padding: 15,
                                font: { size: 12, weight: 600 },
                                color: '#1f2937'
                            }
                        }
                    }
                }
            });
        }

        if(document.getElementById('absenceChart')) {
            new Chart(document.getElementById('absenceChart'), {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Permintaan Absensi',
                        data: absenceData,
                        backgroundColor: colors.danger,
                        borderRadius: 10,
                        borderSkipped: false,
                        hoverBackgroundColor: '#d63235',
                    }]
                },
                options: {
                    ...chartDefaults,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: { stepSize: 1, color: '#6b7280', font: { weight: 500 } },
                            grid: { drawBorder: false, color: 'rgba(0, 0, 0, 0.05)' }
                        },
                        x: {
                            grid: { display: false, drawBorder: false },
                            ticks: { color: '#6b7280', font: { weight: 500 } }
                        }
                    }
                }
            });
        }
    });
</script>
@endsection