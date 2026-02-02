@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow border-primary">
                <div class="card-header bg-warning text-primary fw-bold">
                    <h5 class="mb-0">Tambah Data Berat</h5>
                </div>
                <form action="{{ route('weights.store') }}" method="POST">
                    @csrf
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Material</label>
                            <input type="text" name="material" class="form-control text-uppercase @error('material') is-invalid @enderror" value="{{ old('material') }}" required autocomplete="off" oninput="this.value = this.value.toUpperCase()">
                            @error('material')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Part</label>
                            <input type="text" name="part" class="form-control text-uppercase @error('part') is-invalid @enderror" value="{{ old('part') }}" required autocomplete="off" oninput="this.value = this.value.toUpperCase()">
                            @error('part')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Standard Berat (Gram)</label>
                            <input type="number" step="0.001" name="weight" class="form-control @error('weight') is-invalid @enderror" value="{{ old('weight') }}" required autocomplete="off">
                            @error('weight')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="card-footer text-end">
                        <a href="{{ route('weights.index') }}" class="btn btn-secondary me-2">Cancel</a>
                        <button type="submit" class="btn btn-primary">Save Configuration</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection