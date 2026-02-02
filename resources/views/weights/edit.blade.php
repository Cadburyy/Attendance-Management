@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow border-warning">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0">Edit Berat - {{ $weight->material }} / {{ $weight->part }}</h5>
                </div>
                <form action="{{ route('weights.update', $weight->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Material</label>
                            <input type="text" name="material" class="form-control text-uppercase @error('material') is-invalid @enderror" value="{{ old('material', $weight->material) }}" required autocomplete="off" oninput="this.value = this.value.toUpperCase()">
                            @error('material')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Part</label>
                            <input type="text" name="part" class="form-control text-uppercase @error('part') is-invalid @enderror" value="{{ old('part', $weight->part) }}" required autocomplete="off" oninput="this.value = this.value.toUpperCase()">
                            @error('part')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Standard Berat (Gram)</label>
                            <input type="number" step="0.0001" name="weight" class="form-control @error('weight') is-invalid @enderror" value="{{ old('weight', $weight->weight) }}" required autocomplete="off">
                            @error('weight')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="card-footer text-end">
                        <a href="{{ route('weights.index') }}" class="btn btn-secondary me-2">Batal</a>
                        <button type="submit" class="btn btn-warning">Update Konfigurasi</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection