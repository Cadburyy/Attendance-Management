@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow border-warning">
                <div class="card-header bg-warning text-dark fw-bold">
                    <h5 class="mb-0">Edit Mutasi Barang</h5>
                </div>
                <form action="{{ route('mutations.update', $item->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="card-body">
                        @if (session('error'))
                            <div class="alert alert-danger">{{ session('error') }}</div>
                        @endif

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Tanggal</label>
                                <input type="date" name="tanggal" class="form-control" value="{{ $item->tanggal->format('Y-m-d') }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Material</label>
                                <input type="text" name="material" id="input_material" class="form-control text-uppercase" list="material_list" value="{{ $item->material }}" required autocomplete="off">
                                <datalist id="material_list">
                                    @foreach($materials as $m)
                                        <option value="{{ $m }}">
                                    @endforeach
                                </datalist>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Part</label>
                            <input type="text" name="part" id="input_part" class="form-control text-uppercase" list="part_list" value="{{ $item->part }}" required autocomplete="off">
                            <datalist id="part_list">
                            </datalist>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Tipe Barang</label>
                                <input type="text" class="form-control bg-light" value="{{ $item->scrap > 0 ? 'Scrap' : ($item->cakalan > 0 ? 'Cakalan' : 'Barang Jadi (GKG)') }}" readonly>
                                <input type="hidden" id="input_type" value="{{ $item->scrap > 0 ? 'scrap' : ($item->cakalan > 0 ? 'cakalan' : 'gkg') }}">
                            </div>
                             <div class="col-md-4">
                                <label class="form-label fw-bold">Sisa Stock (KG)</label>
                                <input type="text" id="display_stock" class="form-control bg-secondary-subtle" value="Loading..." readonly tabindex="-1">
                                <small class="text-muted d-block mt-1">*Stock di Produksi</small>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Berat (KG)</label>
                                <input type="number" step="0.001" name="berat" class="form-control" value="{{ $item->scrap > 0 ? $item->scrap : ($item->cakalan > 0 ? $item->cakalan : $item->gkg) }}" required>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer text-end">
                        <a href="{{ route('mutations.index') }}" class="btn btn-secondary me-2">Batal</a>
                        <button type="submit" class="btn btn-warning">Update Mutasi</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    $(document).ready(function() {
        const matInput = $('#input_material');
        const partInput = $('#input_part');
        const partList = $('#part_list');
        const typeVal = $('#input_type').val();
        const stockDisplay = $('#display_stock');

        function fetchParts(material) {
            if (!material) {
                partList.empty();
                return;
            }
            $.ajax({
                url: '{{ route("mutations.index") }}',
                data: { action: 'get_parts', material: material },
                success: function(res) {
                    let html = '';
                    res.forEach(function(p) { html += `<option value="${p}">`; });
                    partList.html(html);
                }
            });
        }

        function fetchStock() {
            const mat = matInput.val();
            const part = partInput.val();

            if (mat && part) {
                stockDisplay.val('Checking...');
                $.ajax({
                    url: '{{ route("mutations.index") }}',
                    data: { action: 'check_stock', material: mat, part: part, type: typeVal },
                    success: function(res) {
                        stockDisplay.val(res.stock);
                    },
                    error: function() { stockDisplay.val('Error'); }
                });
            } else {
                stockDisplay.val('0.00');
            }
        }

        matInput.on('input change', function() {
            const mat = $(this).val();
            fetchParts(mat);
            fetchStock();
        });

        partInput.on('input change', fetchStock);

        const initialMat = matInput.val();
        if(initialMat) {
            fetchParts(initialMat);
            fetchStock();
        }
    });
</script>
@endsection