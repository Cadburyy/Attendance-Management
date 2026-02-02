@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="text-dark">📦 Data Mutasi Barang</h1>
        <div>
            <a href="{{ route('mutations.create') }}" class="btn btn-primary shadow-sm">
                <i class="fas fa-plus me-1"></i> Input Mutasi
            </a>
        </div>
    </div>

    @if (session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif
    @if (session('error')) <div class="alert alert-danger">{{ session('error') }}</div> @endif

    <form method="GET" action="{{ route('mutations.index') }}" class="row mb-4" id="filterForm">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-light fw-bold">Filter Data</div>
                <div class="card-body">
                    <div class="row g-3 mb-2">
                        <div class="col-lg-3 col-md-6"><label class="form-label fw-bold">Start Date</label><input type="date" name="start_date" value="{{ $start_date }}" class="form-control"></div>
                        <div class="col-lg-3 col-md-6"><label class="form-label fw-bold">End Date</label><input type="date" name="end_date" value="{{ $end_date }}" class="form-control"></div>
                        <div class="col-lg-3 col-md-6"><label class="form-label">Material</label><input list="materials" name="material_term" class="form-control" value="{{ $material_term }}" oninput="this.value = this.value.toUpperCase()"><datalist id="materials">@foreach($materials as $mat)<option value="{{ $mat }}">@endforeach</datalist></div>
                        <div class="col-lg-3 col-md-6"><label class="form-label">Part</label><input list="parts" name="part_term" class="form-control" value="{{ $part_term }}" oninput="this.value = this.value.toUpperCase()"><datalist id="parts">@foreach($parts as $part)<option value="{{ $part }}">@endforeach</datalist></div>
                    </div>
                </div>
                <div class="card-footer d-flex justify-content-end gap-2">
                    @if ($mode == 'details')
                        <button type="button" id="downloadCsvBtn" class="btn btn-dark shadow-sm"><i class="fas fa-file-csv me-1"></i> Download CSV</button>
                        <button type="button" id="bulkDeleteBtn" class="btn btn-danger shadow-sm">Delete Selected</button>
                    @endif
                    <input type="hidden" name="mode" value="{{ $mode }}">
                    <button type="submit" class="btn btn-success shadow"><i class="fas fa-search me-1"></i>Cari</button>
                    <a href="{{ route('mutations.index') }}" class="btn btn-outline-secondary shadow"><i class="fas fa-redo"></i> Reset</a>
                </div>
            </div>
        </div>
    </form>
    
    <form id="bulkDeleteForm" method="POST" action="{{ route('mutations.bulkDestroy') }}" style="display:none;">@csrf<div id="bulkDeleteIdsContainer"></div></form>

    <div class="d-flex mb-3 gap-2">
        <a href="{{ route('mutations.index', array_merge(request()->query(), ['mode' => 'resume'])) }}" class="btn {{ $mode == 'resume' ? 'btn-info text-white shadow-lg' : 'btn-outline-info' }}">Resume</a>
        <a href="{{ route('mutations.index', array_merge(request()->query(), ['mode' => 'details'])) }}" class="btn {{ $mode == 'details' ? 'btn-info text-white shadow-lg' : 'btn-outline-info' }}">Details</a>
    </div>

    <div class="card shadow-lg">
        <div class="card-header bg-info text-black">
            {{ $mode == 'details' ? 'Hasil Data - Details' : 'Hasil Data - Resume Mutasi' }}
        </div>
        <div class="card-body p-0">
            @if (($items->isEmpty() && $mode == 'details') || ($mode == 'resume' && empty($summary_tree)))
                <p class="text-center text-muted p-4">Tidak ada data ditemukan.</p>
            @else
                <div class="table-responsive" style="max-height: 70vh;">
                    @if ($mode == 'details')
                        <table class="table table-bordered table-striped table-hover table-sm mb-0">
                            <thead class="bg-light sticky-top">
                                <tr>
                                    <th><input type="checkbox" id="select-all-details"></th><th class="text-center">Aksi</th><th>Tanggal</th><th>Material</th><th>Part</th><th>Tipe</th><th class="text-end">Berat (KG)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($items as $item)
                                    <tr>
                                        <td><input type="checkbox" class="select-detail" name="selected_ids[]" value="{{ $item->id }}"></td>
                                        <td class="text-center"><a href="{{ route('mutations.edit', $item->id) }}" class="btn btn-sm btn-warning"><i class="fas fa-edit"></i></a></td>
                                        <td>{{ $item->tanggal->format('d/m/Y') }}</td>
                                        <td>{{ $item->material }}</td><td>{{ $item->part }}</td>
                                        <td>
                                            @if($item->scrap > 0) <span class="badge bg-warning text-dark">Scrap</span>
                                            @elseif($item->cakalan > 0) <span class="badge bg-secondary">Cakalan</span>
                                            @endif
                                        </td>
                                        <td class="text-end fw-bold">{{ number_format($item->scrap + $item->cakalan, 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @elseif ($mode == 'resume')
                        <table class="table table-bordered table-striped table-hover table-sm mb-0">
                            <thead class="bg-light sticky-top">
                                <tr>
                                    <th style="width:30px;" class="text-center">+/-</th>
                                    <th class="text-nowrap bg-primary text-white">Description</th>
                                    <th class="text-nowrap text-center" style="min-width:90px;">Total Stock (Scrap + Cakalan)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($summary_tree as $material => $matData)
                                    @php
                                        $matUniqueId = md5($material);
                                        $matIds = implode(',', array_unique($matData['ids']));
                                        $matTotal = $matData['total_all'];
                                    @endphp
                                    <tr class="resume-row parent-row" style="background-color: #f0f0f0; cursor: pointer;" 
                                        data-id-list="{{ $matIds }}" 
                                        data-level="material" 
                                        data-name="{{ $material }}">
                                        <td class="text-center stop-propagation">
                                            <button type="button" class="btn btn-sm btn-outline-secondary toggle-btn" 
                                                data-target=".child-mat-{{ $matUniqueId }}"><i class="fas fa-plus"></i></button>
                                        </td>
                                        <td class="fw-bold text-primary">{{ $material }}</td>
                                        <td class="text-end fw-bold font-monospace bg-light {{ $matTotal < 0 ? 'text-danger' : '' }}">{{ number_format($matTotal, 2, ',', '.') }}</td>
                                    </tr>
                                    @foreach($matData['parts'] as $part => $partData)
                                        @php
                                            $partUniqueId = md5($material . $part);
                                            $partIds = implode(',', array_unique($partData['ids']));
                                            $partTotal = $partData['total_all'];
                                        @endphp
                                        <tr class="child-mat-{{ $matUniqueId }} parent-row" style="display:none; background-color: #fff; cursor: pointer;"
                                             data-id-list="{{ $partIds }}" 
                                             data-level="part"
                                             data-name="{{ $part }}"
                                             data-material="{{ $material }}">
                                            <td class="text-center stop-propagation">
                                                 <button type="button" class="btn btn-sm btn-light border btn-xs ms-2 toggle-btn" 
                                                    data-target=".child-part-{{ $partUniqueId }}"><i class="fas fa-plus fa-xs"></i></button>
                                            </td>
                                            <td class="ps-4 fw-bold text-dark"><i class="fas fa-cube me-1 text-muted"></i> {{ $part }}</td>
                                            <td class="text-end font-monospace fw-bold small {{ $partTotal < 0 ? 'text-danger' : '' }}">{{ number_format($partTotal, 2, ',', '.') }}</td>
                                        </tr>
                                        @foreach(['scrap' => 'Scrap', 'cakalan' => 'Cakalan'] as $metricKey => $label)
                                            @if(($partData['total_'.$metricKey] ?? 0) != 0)
                                            <tr class="child-part-{{ $partUniqueId }}" style="display:none; background-color: #fdfdfd;">
                                                <td></td>
                                                <td class="ps-5 text-dark small"><i class="fas fa-arrow-right me-1"></i> {{ $label }}</td>
                                                <td class="text-end font-monospace small {{ $partData['total_'.$metricKey] < 0 ? 'text-danger' : '' }}">{{ number_format($partData['total_'.$metricKey], 2, ',', '.') }}</td>
                                            </tr>
                                            @endif
                                        @endforeach
                                    @endforeach
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            @endif
        </div>
    </div>

    <div class="modal fade" id="pivotDetailModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl"> 
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title">Detail Transaksi</h5>
                    <div class="ms-auto">
                        <form id="popupDownloadForm" method="POST" action="{{ route('mutations.downloadPopupCsv') }}" class="d-inline">
                            @csrf
                            <input type="hidden" name="id_list" id="popup_id_list">
                            <input type="hidden" name="start_date" id="popup_start_date">
                            <input type="hidden" name="end_date" id="popup_end_date">
                            <button type="submit" class="btn btn-light btn-sm"><i class="fas fa-download"></i> Download CSV</button>
                        </form>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                </div>
                <div class="modal-body">
                    <div id="detail-loading" class="text-center p-5"><div class="spinner-border text-primary"></div><p>Memuat data...</p></div>
                    <div id="detail-content" style="display: none;">
                        <div id="modal-material-header" class="mb-3"></div>
                        <div class="table-responsive" style="max-height: 50vh;"><div id="detail-table-container"></div></div>
                        <div class="mt-3 border-top pt-3">
                             <table class="table table-bordered table-sm text-center">
                                <thead class="table-light">
                                    <tr><th>In (Mut)</th><th>Out (Sale)</th><th>Stock Akhir</th></tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td class="fw-bold text-success" id="res_in"></td>
                                        <td class="fw-bold text-danger" id="res_out"></td>
                                        <td class="fw-bold" id="res_stock_akhir"></td>
                                    </tr>
                                </tbody>
                             </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-sm"><div class="modal-content border-danger"><div class="modal-header bg-danger text-white"><h5 class="modal-title">Konfirmasi</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body text-center"><p class="text-danger fw-bold">Hapus data?</p><p id="deleteRecordDesc"></p></div><div class="modal-footer justify-content-center"><button class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button><button class="btn btn-danger btn-sm" id="confirmDeleteBtn">Hapus</button></div></div></div></div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
function formatNumberJS(v) { if (v == null || isNaN(v)) return '0'; return parseFloat(v).toLocaleString('id-ID', {minimumFractionDigits: 2, maximumFractionDigits: 2}); }
function formatDateJS(d) { if (!d) return '-'; if (d.indexOf('T')>-1) d=d.split('T')[0]; const p=d.split('-'); if(p.length===3) return `${p[2]}/${p[1]}/${p[0]}`; return d; }

$(function() {
    const start_date = '{{ $start_date }}';
    const end_date = '{{ $end_date }}';

    $(document).on('click', '.toggle-btn', function(e) {
        e.stopPropagation();
        const btn = $(this);
        const target = $(btn.data('target'));
        const icon = btn.find('i');
        if (target.is(':visible')) { target.hide(); icon.removeClass('fa-minus').addClass('fa-plus'); } else { target.show(); icon.removeClass('fa-plus').addClass('fa-minus'); }
    });
    $(document).on('click', '.stop-propagation', function(e) { e.stopPropagation(); });

    $(document).on('click', '.parent-row', function(e) {
        if ($(e.target).closest('.toggle-btn').length) return;
        const idList = $(this).data('id-list');
        
        const level = $(this).data('level');
        const name = $(this).data('name');
        let headerTitle = name;
        if (level === 'part') {
            const materialName = $(this).data('material');
            headerTitle = materialName + ' - ' + name;
        }

        $('#popup_id_list').val(idList);
        $('#popup_start_date').val(start_date);
        $('#popup_end_date').val(end_date);

        $('#pivotDetailModal').modal('show');
        $('#detail-content').hide(); $('#detail-loading').show(); 

        $.ajax({
            url: '{{ route("mutations.index") }}',
            data: { action: 'pivot_row_details', id_list: idList, start_date: start_date, end_date: end_date },
            success: function(res) {
                $('#detail-loading').hide(); $('#detail-content').show();
                
                $('#modal-material-header').html(`
                    <div class="alert alert-light border shadow-sm">
                        <small class="text-muted text-uppercase fw-bold d-block">MATERIAL / PART</small>
                        <span class="fw-bold text-primary fs-5">${headerTitle}</span>
                    </div>
                `);
                
                $('#res_in').text(formatNumberJS(res.in));
                $('#res_out').text(formatNumberJS(res.out));
                $('#res_stock_akhir').text(formatNumberJS(res.stock_akhir));

                let html = '<div class="text-center text-muted">No details.</div>';
                if(res.details && res.details.length > 0) {
                    let tableHead = '<tr><th>Tanggal</th><th>Material</th><th>Part</th><th>Type</th><th class="text-end">Scrap (KG)</th><th class="text-end">Cakalan (KG)</th><th class="text-end">Total (KG)</th></tr>';
                    html = '<table class="table table-sm table-striped table-bordered mb-0"><thead class="bg-white sticky-top">' + tableHead + '</thead><tbody>';
                    
                    let runningBalance = 0;

                    res.details.forEach(d => {
                        let isSale = d.transaction_type === 'sale';
                        let typeLabel = isSale ? 'OUT' : 'IN';
                        let badgeClass = isSale ? 'bg-danger' : 'bg-success';
                        
                        let s = parseFloat(d.scrap)||0;
                        let c = parseFloat(d.cakalan)||0;
                        let val = s + c;

                        if (isSale) {
                            runningBalance -= val;
                        } else {
                            runningBalance += val;
                        }

                        let textClass = isSale ? 'text-danger' : 'text-success';

                        html += `<tr><td>${formatDateJS(d.tanggal)}</td><td>${d.material}</td><td>${d.part||'-'}</td><td><span class="badge ${badgeClass}">${typeLabel}</span></td>
                        <td class="text-end">${formatNumberJS(s)}</td>
                        <td class="text-end">${formatNumberJS(c)}</td>
                        <td class="text-end fw-bold">${formatNumberJS(runningBalance)}</td></tr>`;
                    });
                    html += '</tbody></table>';
                }
                $('#detail-table-container').html(html);
            }
        });
    });

    $('#select-all-details').on('change', function() { $('.select-detail').prop('checked', $(this).is(':checked')); });
    $('#bulkDeleteBtn').on('click', function() { const s=$('.select-detail:checked').map((_,e)=>$(e).val()).get(); if(!s.length) return alert('Pilih data.'); $('#bulkDeleteIdsContainer').empty(); s.forEach(v=>$('<input>').attr({type:'hidden',name:'selected_ids[]',value:v}).appendTo('#bulkDeleteIdsContainer')); $('#deleteRecordDesc').text('Total '+s.length+' records.'); $('#deleteConfirmModal').modal('show'); });
    $('#downloadCsvBtn').on('click', function() { 
        const s = $('.select-detail:checked').map((_, e) => $(e).val()).get();
        if (!s.length) return alert('Pilih data untuk di-download.');
        let form = $('<form method="POST" action="{{ route('mutations.downloadCsv') }}">');
        form.append('<input type="hidden" name="_token" value="{{ csrf_token() }}">');
        s.forEach(v => form.append('<input type="hidden" name="selected_ids[]" value="' + v + '">'));
        $('body').append(form);
        form.submit();
        form.remove();
    });
    $('#confirmDeleteBtn').on('click', function() { $('#bulkDeleteForm').submit(); });
});
</script>
@endsection