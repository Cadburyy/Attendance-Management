@extends('layouts.app')

@section('content')

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="text-dark">⚖️ Data Berat Barang</h1>
        <a class="btn btn-primary shadow-sm" href="{{ route('weights.create') }}">
            <i class="fas fa-plus me-1"></i> Tambah Berat
        </a>
    </div>

    @if ($message = Session::get('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
            <p class="mb-0">{{ $message }}</p>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card shadow-lg mb-4">
        <div class="card-header bg-light text-dark">
            <h5 class="mb-0">🔍 Filter Data Berat</h5>
        </div>
        <div class="card-body">
            <form action="{{ route('weights.index') }}" method="GET">
                <div class="row g-3">
                    <div class="col-md-5">
                        <label for="material_search" class="form-label fw-bold">Material</label>
                        <input type="text" name="material_search" id="material_search" class="form-control" 
                               value="{{ request('material_search') }}"
                               list="materialSuggestions">
                        <datalist id="materialSuggestions">
                            @foreach ($uniqueMaterials as $material)
                                <option value="{{ $material }}">
                            @endforeach
                        </datalist>
                    </div>

                    <div class="col-md-5">
                        <label for="part_search" class="form-label fw-bold">Part</label>
                        <input type="text" name="part_search" id="part_search" class="form-control" 
                               value="{{ request('part_search') }}"
                               list="partSuggestions">
                        <datalist id="partSuggestions">
                            @foreach ($uniqueParts as $part)
                                <option value="{{ $part }}">
                            @endforeach
                        </datalist>
                    </div>

                    <div class="col-md-2 d-flex align-items-end">
                        <button class="btn btn-success me-2 flex-grow-1" type="submit">
                            <i class="fas fa-search me-1"></i> Cari
                        </button>
                        <a href="{{ route('weights.index') }}" class="btn btn-outline-secondary" title="Reset Filter">
                            <i class="fas fa-redo"></i> Reset
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-lg">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover table-sm mb-0">
                    <thead class="bg-light sticky-top">
                        <tr>
                            <th class="ps-3 text-nowrap">Material</th>
                            <th class="text-nowrap">Part</th>
                            <th class="text-nowrap text-end">Berat (g)</th>
                            <th width="200px" class="text-center text-nowrap">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($weights as $weight)
                        <tr>
                            <td class="ps-3 fw-bold">{{ $weight->material }}</td>
                            <td>{{ $weight->part }}</td>
                            <td class="text-end font-monospace">{{ number_format($weight->weight, 2) }}</td>
                            <td class="text-center text-nowrap">
                                <a class="btn btn-warning btn-sm me-1" href="{{ route('weights.edit',$weight->id) }}">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <button type="button" class="btn btn-danger btn-sm" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#deleteConfirmationModal"
                                        data-weight-id="{{ $weight->id }}">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="text-center py-4">
                                <p class="lead text-muted">Data berat tidak ditemukan.</p>
                                @if (request('material_search') || request('part_search'))
                                <p class="mb-0 mt-2">Coba kata kunci lain atau <a href="{{ route('weights.index') }}" class="fw-bold">reset filter</a>.</p>
                                @endif
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-3">
        {!! $weights->links() !!} 
    </div>
</div>

<div class="modal fade" id="deleteConfirmationModal" tabindex="-1" aria-labelledby="deleteConfirmationModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteConfirmationModalLabel"><i class="fas fa-exclamation-triangle me-2"></i> Konfirmasi Hapus Data</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Anda yakin ingin menghapus konfigurasi berat ini? Aksi ini tidak dapat dibatalkan.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <form id="deleteWeightForm" method="POST" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Ya, Hapus Permanen</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const deleteConfirmationModal = document.getElementById('deleteConfirmationModal');

    if (deleteConfirmationModal) {
        deleteConfirmationModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget; 
            const weightId = button.getAttribute('data-weight-id');

            const form = document.getElementById('deleteWeightForm');

            form.action = `/weights/${weightId}`; 
        });
    }
});
</script>

@endsection