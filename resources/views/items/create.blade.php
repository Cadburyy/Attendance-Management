@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card shadow border-primary">
                <div class="card-header bg-warning text-primary fw-bold">
                    <h5 class="mb-0">Input Data Produksi Baru</h5>
                </div>
                <form action="{{ route('items.store') }}" method="POST">
                    @csrf
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Tanggal</label>
                                <input type="date" name="tanggal" class="form-control" value="{{ date('Y-m-d') }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Material</label>
                                <input type="text" name="material" id="input_material" class="form-control text-uppercase" required autocomplete="off" oninput="this.value = this.value.toUpperCase()" list="material_options">
                                <datalist id="material_options">
                                </datalist>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-3">
                                <label class="form-label fw-bold">Part</label>
                                <input type="text" name="part" id="input_part" class="form-control text-uppercase" autocomplete="off" oninput="this.value = this.value.toUpperCase()" list="part_options">
                                <datalist id="part_options">
                                </datalist>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-bold">No Lot</label>
                                <input type="text" name="no_lot" class="form-control text-uppercase" autocomplete="off" oninput="this.value = this.value.toUpperCase()">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-bold">Kode</label>
                                <input type="text" name="kode" class="form-control text-uppercase" autocomplete="off" oninput="this.value = this.value.toUpperCase()">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-bold">Berat Mentah</label>
                                <input type="number" step="0.001" name="berat_mentah" class="form-control" value="0" autocomplete="off" onblur="this.value = parseFloat(this.value).toFixed(2)">
                            </div>
                        </div>

                        <hr>

                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Barang Jadi (PCS)</label>
                                <input type="number" step="0.001" name="gpcs" id="input_gpcs" class="form-control" value="0" autocomplete="off" onblur="this.value = parseFloat(this.value).toFixed(2)">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Barang/Gram</label>
                                <input type="number" step="0.001" id="input_gweight" class="form-control bg-secondary-subtle" readonly tabindex="-1">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Barang Jadi (KG)</label>
                                <input type="number" step="0.001" name="gkg" id="input_gkg" class="form-control bg-secondary-subtle" autocomplete="off" readonly>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Scrap (KG)</label>
                                <input type="number" step="0.001" name="scrap" class="form-control" value="0" autocomplete="off" onblur="this.value = parseFloat(this.value).toFixed(2)">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Cakalan (KG)</label>
                                <input type="number" step="0.001" name="cakalan" class="form-control" value="0" autocomplete="off" onblur="this.value = parseFloat(this.value).toFixed(2)">
                            </div>
                        </div>
                    </div>
                    <div class="card-footer text-end">
                        <a href="{{ route('items.index') }}" class="btn btn-secondary me-2">Batal</a>
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const weightsData = @json($weights ?? []); 

        const materialInput = document.getElementById('input_material');
        const partInput = document.getElementById('input_part');
        const materialList = document.getElementById('material_options');
        const partList = document.getElementById('part_options');
        
        const gpcsInput = document.getElementById('input_gpcs');
        const gweightInput = document.getElementById('input_gweight');
        const gkgInput = document.getElementById('input_gkg');

        const materials = [...new Set(weightsData.map(w => w.material))];
        materials.sort().forEach(mat => {
            const option = document.createElement('option');
            option.value = mat;
            materialList.appendChild(option);
        });

        function updatePartOptions() {
            const currentMaterial = materialInput.value.toUpperCase();
            
            partList.innerHTML = '';
            
            const relevantWeights = weightsData.filter(w => w.material === currentMaterial);
            
            const parts = [...new Set(relevantWeights.map(w => w.part))];
            
            parts.sort().forEach(part => {
                const option = document.createElement('option');
                option.value = part;
                partList.appendChild(option);
            });

            findWeight();
        }

        function findWeight() {
            const currentMaterial = materialInput.value.toUpperCase();
            const currentPart = partInput.value.toUpperCase();
            
            const found = weightsData.find(w => w.material === currentMaterial && w.part === currentPart);
            
            if (found) {
                gweightInput.value = found.weight;
                calculateGkg(); 
            } else {
                gweightInput.value = ''; 
                calculateGkg();
            }
        }

        function calculateGkg() {
            const pcs = parseFloat(gpcsInput.value) || 0;
            const weight = parseFloat(gweightInput.value) || 0;
            
            if (pcs > 0 && weight > 0) {
                const total = (pcs * weight) / 1000;
                gkgInput.value = total.toFixed(2); 
            } else {
                gkgInput.value = '0';
            }
        }

        materialInput.addEventListener('input', updatePartOptions);
        materialInput.addEventListener('change', updatePartOptions); 
        
        partInput.addEventListener('input', findWeight);
        partInput.addEventListener('change', findWeight);

        if (gpcsInput && gweightInput && gkgInput) {
            gpcsInput.addEventListener('input', calculateGkg);
        }
    });
</script>
@endsection