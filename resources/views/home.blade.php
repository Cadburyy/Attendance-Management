@extends('layouts.app')

@section('content')
@php
    $user = Auth::user();
@endphp

<style>
    body, html {
        overflow-x: hidden;
        overflow-y: auto;
    }

    .card-link-hover:hover .card {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.15) !important; 
        transition: transform 0.3s ease-in-out, box-shadow 0.3s ease-in-out;
    }

    .card-link-hover .card {
        transition: transform 0.3s ease-in-out, box-shadow 0.3s ease-in-out;
    }

    .card {
        border-radius: 1rem;
        border: 1px solid #e9ecef; 
        box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,.075)!important;
    }
    
    .chart-container {
        position: relative;
        height: 300px;
        width: 100%;
    }
</style>

<div class="container d-flex flex-column justify-content-center py-5">

    <h2 class="text-center mb-4">
        Welcome, {{ $user->name }} 
        <span role="img" aria-label="wave">👋</span>
    </h2>

    <div class="row mb-5">
        <div class="col-md-6 mb-4">
            <div class="card h-100 shadow-sm">
                <div class="card-header bg-white border-0 pt-3 pb-0">
                    <h5 class="card-title text-center text-primary fw-bold">Stock Produksi</h5>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="prodChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-4">
            <div class="card h-100 shadow-sm">
                <div class="card-header bg-white border-0 pt-3 pb-0">
                    <h5 class="card-title text-center text-info fw-bold">Stock Mutasi</h5>
                </div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="mutChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-2 g-4 justify-content-center mb-5">
        <div class="col">
            <a href="{{ route('items.index') }}" class="text-decoration-none card-link-hover">
                <div class="card h-100 text-center shadow-sm p-3">
                    <div class="card-body d-flex flex-column justify-content-center align-items-center">
                        <i class="fas fa-industry fa-3x mb-3 text-primary"></i>
                        <h5 class="card-title"><strong>Produksi</strong></h5>
                        <p class="card-text text-muted small">Input hasil produksi (Goods, Scrap, Cakalan).</p>
                    </div>
                </div>
            </a>
        </div>
        
        <div class="col">
            <a href="{{ route('mutations.index') }}" class="text-decoration-none card-link-hover">
                <div class="card h-100 text-center shadow-sm p-3">
                    <div class="card-body d-flex flex-column justify-content-center align-items-center">
                        <i class="fas fa-exchange-alt fa-3x mb-3 text-info"></i>
                        <h5 class="card-title"><strong>Mutasi Barang</strong></h5>
                        <p class="card-text text-muted small">Pindahkan stok produksi ke proses lain.</p>
                    </div>
                </div>
            </a>
        </div>

        <div class="col">
            <a href="{{ route('sales.index') }}" class="text-decoration-none card-link-hover">
                <div class="card h-100 text-center shadow-sm p-3">
                    <div class="card-body d-flex flex-column justify-content-center align-items-center">
                        <i class="fas fa-cash-register fa-3x mb-3 text-success"></i>
                        <h5 class="card-title"><strong>Penjualan</strong></h5>
                        <p class="card-text text-muted small">Input penjualan ke customer.</p>
                    </div>
                </div>
            </a>
        </div>

        <div class="col">
            <a href="{{ route('weights.index') }}" class="text-decoration-none card-link-hover">
                <div class="card h-100 text-center shadow-sm p-3">
                    <div class="card-body d-flex flex-column justify-content-center align-items-center">
                        <i class="fas fa-balance-scale fa-3x mb-3 text-warning"></i>
                        <h5 class="card-title"><strong>Master Berat</strong></h5>
                        <p class="card-text text-muted small">Kelola standar berat item/part.</p>
                    </div>
                </div>
            </a>
        </div>
    </div>

    @if (session('status'))
        <div class="alert alert-success text-center mt-5" role="alert">
            {{ session('status') }}
        </div>
    @endif
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const prodLabels = @json($prodLabels);
        const prodBreakdown = @json($prodBreakdown);
        
        const mutLabels = @json($mutLabels);
        const mutBreakdown = @json($mutBreakdown);

        const prodScrapData = prodLabels.map(label => prodBreakdown[label]?.scrap || 0);
        const prodCakalanData = prodLabels.map(label => prodBreakdown[label]?.cakalan || 0);

        const mutScrapData = mutLabels.map(label => mutBreakdown[label]?.scrap || 0);
        const mutCakalanData = mutLabels.map(label => mutBreakdown[label]?.cakalan || 0);

        if(prodLabels.length > 0) {
            new Chart(document.getElementById('prodChart'), {
                type: 'bar',
                data: {
                    labels: prodLabels,
                    datasets: [
                        {
                            label: 'Scrap (KG)',
                            data: prodScrapData,
                            backgroundColor: 'rgba(255, 99, 132, 0.7)',
                            borderWidth: 1
                        },
                        {
                            label: 'Cakalan (KG)',
                            data: prodCakalanData,
                            backgroundColor: 'rgba(255, 206, 86, 0.7)',
                            borderWidth: 1
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        x: { stacked: false },
                        y: { beginAtZero: true }
                    },
                    plugins: {
                        legend: { position: 'top' },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return context.dataset.label + ': ' + context.raw + ' Kg';
                                }
                            }
                        }
                    }
                }
            });
        } else {
            document.getElementById('prodChart').parentNode.innerHTML = '<div class="d-flex align-items-center justify-content-center h-100 text-muted">No Data Available</div>';
        }

        if(mutLabels.length > 0) {
            new Chart(document.getElementById('mutChart'), {
                type: 'bar',
                data: {
                    labels: mutLabels,
                    datasets: [
                        {
                            label: 'Scrap (KG)',
                            data: mutScrapData,
                            backgroundColor: 'rgba(255, 99, 132, 0.7)',
                            borderWidth: 1
                        },
                        {
                            label: 'Cakalan (KG)',
                            data: mutCakalanData,
                            backgroundColor: 'rgba(255, 206, 86, 0.7)',
                            borderWidth: 1
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        x: { stacked: false },
                        y: { beginAtZero: true }
                    },
                    plugins: {
                        legend: { position: 'top' },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return context.dataset.label + ': ' + context.raw + ' Kg';
                                }
                            }
                        }
                    }
                }
            });
        } else {
            document.getElementById('mutChart').parentNode.innerHTML = '<div class="d-flex align-items-center justify-content-center h-100 text-muted">No Data Available</div>';
        }
    });
</script>
@endsection