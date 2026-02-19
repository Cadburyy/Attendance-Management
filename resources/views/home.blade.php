@extends('layouts.app')

@section('content')
<div class="dashboard-content">
    <!-- Header -->
    <div class="mb-4">
        <h1 class="mb-2 fw-bold" style="color: #0d3b66;">Selamat Datang, {{ Auth::user()->name }}! 👋</h1>
        <p class="text-muted">{{ now()->format('l, F d, Y') }}</p>
    </div>

    <!-- Anomaly Alerts -->
    @if(count($anomalies) > 0)
        <div class="row mb-4">
            <div class="col-12">
                @foreach($anomalies as $anomaly)
                    <div class="alert alert-{{ $anomaly['type'] }} alert-dismissible fade show d-flex align-items-center gap-3 mb-2" role="alert">
                        <i class="fas {{ $anomaly['icon'] }} fa-lg"></i>
                        <div>
                            <strong>{{ $anomaly['title'] }}</strong>
                            <p class="mb-0">{{ $anomaly['message'] }}</p>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Quick Stats Cards -->
    <div class="row g-3 mb-4">
        <div class="col-lg-3 col-md-6">
            <div class="card border-0 shadow-sm rounded-3 h-100" style="border-top: 4px solid #0d3b66;">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="text-muted mb-1 small">Kehadiran Hari Ini</p>
                            <h3 class="mb-0 fw-bold" style="color: #0d3b66;">{{ $totalAttendanceToday }}</h3>
                        </div>
                        <div style="width: 50px; height: 50px; background-color: rgba(13, 59, 102, 0.1); border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-check-circle" style="color: #0d3b66; font-size: 24px;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6">
            <div class="card border-0 shadow-sm rounded-3 h-100" style="border-top: 4px solid #e63946;">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="text-muted mb-1 small">Absensi Hari Ini</p>
                            <h3 class="mb-0 fw-bold" style="color: #e63946;">{{ $totalAbsenceToday }}</h3>
                        </div>
                        <div style="width: 50px; height: 50px; background-color: rgba(230, 57, 70, 0.1); border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-times-circle" style="color: #e63946; font-size: 24px;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6">
            <div class="card border-0 shadow-sm rounded-3 h-100" style="border-top: 4px solid #f77f00;">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="text-muted mb-1 small">Hadir Bulan Ini</p>
                            <h3 class="mb-0 fw-bold" style="color: #f77f00;">{{ $monthlyStats['present'] }}</h3>
                        </div>
                        <div style="width: 50px; height: 50px; background-color: rgba(247, 127, 0, 0.1); border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-calendar-check" style="color: #f77f00; font-size: 24px;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6">
            <div class="card border-0 shadow-sm rounded-3 h-100" style="border-top: 4px solid #06a77d;">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="text-muted mb-1 small">Permintaan Tertunda</p>
                            <h3 class="mb-0 fw-bold" style="color: #06a77d;">{{ $pendingAbsences }}</h3>
                        </div>
                        <div style="width: 50px; height: 50px; background-color: rgba(6, 168, 125, 0.1); border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-hourglass-half" style="color: #06a77d; font-size: 24px;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row g-3 mb-4">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm rounded-3 h-100">
                <div class="card-header bg-white border-bottom border-light py-3">
                    <h6 class="mb-0 fw-bold" style="color: #0d3b66;">Ringkasan Kehadiran 7 Hari</h6>
                </div>
                <div class="card-body">
                    <div style="position: relative; height: 300px;">
                        <canvas id="attendanceChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card border-0 shadow-sm rounded-3 h-100">
                <div class="card-header bg-white border-bottom border-light py-3">
                    <h6 class="mb-0 fw-bold" style="color: #0d3b66;">Distribusi Status Bulanan</h6>
                </div>
                <div class="card-body">
                    <div style="position: relative; height: 300px;">
                        <canvas id="monthlyStatsChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Second Row of Charts -->
    <div class="row g-3 mb-4">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm rounded-3 h-100">
                <div class="card-header bg-white border-bottom border-light py-3">
                    <h6 class="mb-0 fw-bold" style="color: #0d3b66;">Ringkasan Absensi 7 Hari</h6>
                </div>
                <div class="card-body">
                    <div style="position: relative; height: 300px;">
                        <canvas id="absenceChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card border-0 shadow-sm rounded-3 h-100">
                <div class="card-header bg-white border-bottom border-light py-3">
                    <h6 class="mb-0 fw-bold" style="color: #0d3b66;">Permintaan Absensi Terbaru</h6>
                </div>
                <div class="card-body">
                    @forelse($recentAbsences as $absence)
                        <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                            <div>
                                <p class="mb-0 fw-semibold">{{ $absence->employee_name }}</p>
                                <small class="text-muted">{{ $absence->date->format('M d, Y') }} • {{ $absence->reason }}</small>
                            </div>
                            <span class="badge bg-{{ $absence->status == 'pending' ? 'warning' : ($absence->status == 'approved' ? 'success' : 'danger') }}">
                                {{ ucfirst($absence->status) }}
                            </span>
                        </div>
                    @empty
                        <p class="text-muted text-center py-4 mb-0">Tidak ada permintaan absensi terbaru</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .card {
        transition: all 0.3s ease;
    }

    .card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 30px rgba(13, 59, 102, 0.15) !important;
    }

    .dashboard-content {
        padding: 20px;
    }

    @media (max-width: 768px) {
        .dashboard-content {
            padding: 12px;
        }

        .card {
            margin-bottom: 12px;
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

        // Chart colors
        const colors = {
            primary: '#0d3b66',
            danger: '#e63946',
            warning: '#f77f00',
            success: '#06a77d'
        };

        // 7-Day Attendance Chart
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
                            backgroundColor: 'rgba(13, 59, 102, 0.1)',
                            borderWidth: 3,
                            fill: true,
                            tension: 0.4,
                            pointRadius: 5,
                            pointBackgroundColor: colors.primary,
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2,
                        },
                        {
                            label: 'Absen',
                            data: absentData,
                            borderColor: colors.danger,
                            backgroundColor: 'rgba(230, 57, 70, 0.1)',
                            borderWidth: 3,
                            fill: true,
                            tension: 0.4,
                            pointRadius: 5,
                            pointBackgroundColor: colors.danger,
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2,
                        },
                        {
                            label: 'Terlambat',
                            data: lateData,
                            borderColor: colors.warning,
                            backgroundColor: 'rgba(247, 127, 0, 0.1)',
                            borderWidth: 3,
                            fill: true,
                            tension: 0.4,
                            pointRadius: 5,
                            pointBackgroundColor: colors.warning,
                            pointBorderColor: '#fff',
                            pointBorderWidth: 2,
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top',
                            labels: {
                                usePointStyle: true,
                                boxWidth: 6,
                                padding: 20,
                                font: { size: 12, weight: 600 }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: { stepSize: 1 },
                            grid: { drawBorder: false, color: 'rgba(0,0,0,0.05)' }
                        },
                        x: {
                            grid: { display: false, drawBorder: false }
                        }
                    }
                }
            });
        }

        // Monthly Stats Doughnut Chart
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
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                usePointStyle: true,
                                boxWidth: 6,
                                padding: 15,
                                font: { size: 11, weight: 600 }
                            }
                        }
                    }
                }
            });
        }

        // 7-Day Absence Chart
        if(document.getElementById('absenceChart')) {
            new Chart(document.getElementById('absenceChart'), {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Permintaan Absensi',
                        data: absenceData,
                        backgroundColor: colors.danger,
                        borderRadius: 8,
                        borderSkipped: false,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: { stepSize: 1 },
                            grid: { drawBorder: false, color: 'rgba(0,0,0,0.05)' }
                        },
                        x: {
                            grid: { display: false, drawBorder: false }
                        }
                    }
                }
            });
        }
    });
</script>
@endsection