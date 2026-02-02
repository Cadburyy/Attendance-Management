<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Weight;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ItemController extends Controller
{
    function __construct()
    {
         $this->middleware('permission:item');
    }

    public function index(Request $request)
    {
        if ($request->ajax() && $request->get('action') === 'pivot_row_details') {
            $material = $request->get('material');
            $part = $request->get('part');
            $level = $request->get('level');
            $start_date = $request->get('start_date');
            $end_date = $request->get('end_date');

            $query = Item::whereIn('transaction_type', ['production', 'mutation'])
                         ->where('material', $material);

            if ($level === 'part') {
                if ($part === 'NO PART' || empty($part)) {
                    $query->where(function($q) {
                        $q->whereNull('part')->orWhere('part', '');
                    });
                } else {
                    $query->where('part', $part);
                }
            }

            $stock_awal = 0;
            if ($start_date) {
                $qAwal = clone $query;
                $history = $qAwal->where('tanggal', '<', $start_date)->get();
                
                $stock_awal = $history->sum(function($item) {
                    $val = $item->scrap + $item->cakalan;
                    return ($item->transaction_type === 'mutation') ? -$val : $val;
                });
            }

            $qDetails = clone $query;
            if ($start_date && $end_date) {
                $qDetails->whereBetween('tanggal', [$start_date, $end_date]);
            }
            $details = $qDetails->orderBy('tanggal', 'asc')->get();

            $in = 0; $out = 0;
            foreach($details as $d) {
                $val = $d->scrap + $d->cakalan;
                if ($d->transaction_type === 'mutation') $out += $val;
                else $in += $val;
            }
            $stock_akhir = $stock_awal + $in - $out;

            $item_key = $material;
            if ($level === 'part') {
                $item_key .= ' - ' . ($part ?: 'NO PART');
            }
            $item_key .= " [Net Stock (Scrap+Cakalan)]";

            return response()->json([
                'item_key' => $item_key, 
                'details' => $details,
                'stock_awal' => round($stock_awal, 2), 
                'in' => round($in, 2), 
                'out' => round($out, 2), 
                'stock_akhir' => round($stock_akhir, 2)
            ]);
        }

        $materials = Item::where('transaction_type', 'production')->select('material')->distinct()->pluck('material');
        $parts = Item::where('transaction_type', 'production')->select('part')->distinct()->pluck('part');
        
        $distinctDates = Item::select(DB::raw('DISTINCT YEAR(tanggal) as year, DATE_FORMAT(tanggal, "%Y-%m") as ym'))
            ->orderBy('year', 'desc')->orderBy('ym', 'desc')->get();
        $distinctYears = $distinctDates->pluck('year')->unique()->sortDesc()->values();
        $distinctYearMonths = $distinctDates->groupBy('year')->map(fn($items) => $items->pluck('ym')->unique()->sort());

        $start_date = $request->input('start_date');
        $end_date = $request->input('end_date');
        $material_term = $request->input('material_term');
        $part_term = $request->input('part_term');
        $mode = $request->input('mode', 'resume');

        $query = Item::query()->orderBy('tanggal', 'desc');

        if ($mode == 'details') {
            $query->where('transaction_type', 'production');
        } else {
            $query->whereIn('transaction_type', ['production', 'mutation']);
        }

        if ($start_date && $end_date) {
            $query->whereBetween('tanggal', [$start_date, $end_date]);
        }
        if ($material_term) {
            $query->where('material', 'LIKE', '%' . $material_term . '%');
        }
        if ($part_term) {
            $query->where('part', 'LIKE', '%' . $part_term . '%');
        }

        $items = $query->get();
        $summary_tree = []; 

        if ($mode == 'resume') {
            foreach ($items as $item) {
                $mat = $item->material;
                $part = $item->part ?? 'NO PART';
                $item_id = $item->id;

                $multiplier = ($item->transaction_type === 'mutation') ? -1 : 1;
                $val_scrap = $item->scrap * $multiplier;
                $val_cakalan = $item->cakalan * $multiplier;
                $total_all = $val_scrap + $val_cakalan;

                if (!isset($summary_tree[$mat])) {
                    $summary_tree[$mat] = ['total_all' => 0, 'parts' => [], 'ids' => []];
                }
                if (!isset($summary_tree[$mat]['parts'][$part])) {
                    $summary_tree[$mat]['parts'][$part] = [
                        'total_all' => 0, 'ids' => [],
                        'total_scrap' => 0, 'total_cakalan' => 0
                    ];
                }

                $summary_tree[$mat]['total_all'] += $total_all;
                $summary_tree[$mat]['ids'][] = $item_id;

                $pNode = &$summary_tree[$mat]['parts'][$part];
                $pNode['ids'][] = $item_id;
                $pNode['total_all'] += $total_all;
                $pNode['total_scrap'] += $val_scrap;
                $pNode['total_cakalan'] += $val_cakalan;
            }
        }

        return view('items.index', compact(
            'items', 'mode', 'summary_tree',
            'start_date', 'end_date', 'material_term', 'part_term',
            'materials', 'parts', 'distinctYears', 'distinctYearMonths'
        ));
    }

    public function create() { $weights = Weight::all(); return view('items.create', compact('weights')); }
    
    public function store(Request $request) { 
        $request->validate(['tanggal'=>'required|date','material'=>'required|string']); 
        $d=$request->all(); $this->uppercaseFields($d); 
        $d['transaction_type'] = 'production'; 
        Item::create($d); 
        return redirect()->route('items.index')->with('success','Created'); 
    }
    
    public function edit($id) { $item = Item::findOrFail($id); $weights = Weight::all(); return view('items.edit', compact('item','weights')); }
    
    public function update(Request $request, $id) { 
        $request->validate(['tanggal'=>'required|date','material'=>'required|string']);
        $item = Item::findOrFail($id); $d=$request->all(); $this->uppercaseFields($d); $item->update($d);
        return redirect()->route('items.index')->with('success','Updated'); 
    }
    
    public function destroy($id) { Item::destroy($id); return back()->with('success','Deleted'); }
    
    public function bulkDestroy(Request $request) { 
        $s=(array)$request->input('selected_ids',[]); if(empty($s)) return back()->with('error','No selection');
        $ids=[]; foreach($s as $v) $ids=array_merge($ids, explode(',',$v));
        Item::whereIn('id',$ids)->delete(); return back()->with('success','Deleted');
    }
    
    public function downloadCsv(Request $request)
    {
        $ids = (array)$request->input('selected_ids', []);
        if (empty($ids)) return back()->with('error', 'No data selected');
        $items = Item::whereIn('id', $ids)->orderBy('tanggal', 'desc')->get();
        return $this->streamCsv($items, 'items_production_export_');
    }

    public function downloadPopupCsv(Request $request)
    {
        $material = $request->input('material');
        $part = $request->input('part');
        $level = $request->input('level');
        $start_date = $request->input('start_date');
        $end_date = $request->input('end_date');

        $query = Item::whereIn('transaction_type', ['production', 'mutation'])
                     ->where('material', $material);

        if ($level === 'part') {
            if ($part === 'NO PART' || empty($part)) {
                $query->where(function($q) { $q->whereNull('part')->orWhere('part', ''); });
            } else {
                $query->where('part', $part);
            }
        }

        if ($start_date && $end_date) {
            $query->whereBetween('tanggal', [$start_date, $end_date]);
        }

        $items = $query->orderBy('tanggal', 'asc')->get();
        $headerInfo = $material . ($level === 'part' ? ' - ' . $part : '');

        return $this->streamCsv($items, 'items_resume_detail_export_', $headerInfo);
    }

    private function streamCsv($items, $prefix, $headerInfo = null) {
        $filename = $prefix . date('Ymd') . ".csv";
        $headers = ["Content-type" => "text/csv", "Content-Disposition" => "attachment; filename=$filename", "Pragma" => "no-cache", "Cache-Control" => "must-revalidate, post-check=0, pre-check=0", "Expires" => "0"];
        $callback = function() use($items, $headerInfo) {
            $file = fopen('php://output', 'w');
            
            if($headerInfo) {
                fputcsv($file, ['MATERIAL: ' . $headerInfo]);
                fputcsv($file, []);
            }

            fputcsv($file, ['Tanggal', 'Material', 'Part', 'Lot', 'Type', 'Scrap', 'Cakalan', 'Total (+/-)']);
            
            foreach ($items as $item) {
                $val = $item->scrap + $item->cakalan;
                $total = ($item->transaction_type === 'mutation') ? -$val : $val;
                $typeLabel = ($item->transaction_type === 'mutation') ? 'OUT (Mutasi)' : 'IN (Produksi)';

                fputcsv($file, [
                    $item->tanggal->format('Y-m-d'), 
                    $item->material, 
                    $item->part, 
                    $item->no_lot,
                    $typeLabel,
                    $item->scrap, 
                    $item->cakalan,
                    $total
                ]);
            }
            fclose($file);
        };
        return response()->stream($callback, 200, $headers);
    }

    private function uppercaseFields(array &$data) { 
        foreach(['material','part','no_lot','kode'] as $f) if(isset($data[$f])&&is_string($data[$f])) $data[$f]=strtoupper($data[$f]); 
    }
}