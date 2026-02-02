@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow border-primary">
                <div class="card-header bg-primary text-primary fw-bold">
                    <h5 class="mb-0">Input Mutasi Penjualan</h5>
                </div>
                <form action="{{ route('sales.store') }}" method="POST">
                    @csrf
                    <div class="card-body">
                        @if (session('error'))
                            <div class="alert alert-danger">{{ session('error') }}</div>
                        @endif

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Tanggal</label>
                                <input type="date" name="tanggal" class="form-control" value="{{ date('Y-m-d') }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Customer</label>
                                <input type="text" name="customer" class="form-control text-uppercase" required autocomplete="off">
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Material</label>
                                <input type="text" name="material" id="input_material" class="form-control text-uppercase" list="material_list" required autocomplete="off">
                                <datalist id="material_list">
                                    @foreach($materials as $m)
                                        <option value="{{ $m }}">
                                    @endforeach
                                </datalist>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Part</label>
                                <input type="text" name="part" id="input_part" class="form-control text-uppercase" list="part_list" required autocomplete="off" disabled>
                                <datalist id="part_list"></datalist>
                            </div>
                        </div>

                        <hr>

                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Tipe Barang</label>
                                <select name="tipe_barang" id="input_type" class="form-select" required>
                                    <option value="scrap">Scrap</option>
                                    <option value="cakalan">Cakalan</option>
                                </select>
                            </div>
                             <div class="col-md-4">
                                <label class="form-label fw-bold">Sisa Stock (KG)</label>
                                <input type="text" id="display_stock" class="form-control bg-secondary-subtle" readonly tabindex="-1">
                                <small class="text-muted d-block mt-1">*Stock di Mutasi</small>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Berat (KG)</label>
                                <input type="number" step="0.001" name="berat" class="form-control" required>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer text-end">
                        <a href="{{ route('sales.index') }}" class="btn btn-secondary me-2">Batal</a>
                        <button type="submit" class="btn btn-primary">Tambah Penjualan</button>
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
        const typeSelect = $('#input_type');
        const stockDisplay = $('#display_stock');

        function fetchStock() {
            const mat = matInput.val();
            const part = partInput.val();
            const type = typeSelect.val();

            if (mat && part && type) {
                stockDisplay.val('Checking...');
                $.ajax({
                    url: '{{ route("sales.index") }}',
                    data: { action: 'check_stock', material: mat, part: part, type: type },
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
            partInput.val('').prop('disabled', true);
            partList.empty();
            stockDisplay.val('0.00');

            if (mat) {
                $.ajax({
                    url: '{{ route("sales.index") }}',
                    data: { action: 'get_parts', material: mat },
                    success: function(res) {
                        let html = '';
                        res.forEach(function(p) { html += `<option value="${p}">`; });
                        partList.html(html);
                        partInput.prop('disabled', false);
                    }
                });
            }
        });

        partInput.on('input change', fetchStock);
        typeSelect.change(fetchStock);
    });
</script>
@endsection