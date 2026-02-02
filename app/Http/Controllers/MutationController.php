<?php

namespace App\Http\Controllers;

use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class MutationController extends Controller
{
    function __construct()
    {
         $this->middleware('permission:mutation');
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            if ($request->get('action') === 'get_parts') {
                $mat = $request->get('material');
                $parts = Item::where('transaction_type', 'production')->where('material', $mat)
                            ->whereNotNull('part')->where('part', '!=', '')
                            ->select('part')->distinct()->orderBy('part')->pluck('part');
                return response()->json($parts);
            }

            if ($request->get('action') === 'check_stock') {
                $mat = $request->get('material'); $part = $request->get('part'); $type = $request->get('type');
                $filter = [['material', '=', $mat], ['part', '=', $part]];
                $prod = Item::where('transaction_type', 'production')->where($filter)->sum($type);
                $used = Item::where('transaction_type', 'mutation')->where($filter)->sum($type);
                return response()->json(['stock' => round($prod - $used, 2)]);
            }

            if ($request->get('action') === 'pivot_row_details') {
                $id_list = $request->get('id_list');
                $ids = array_filter(array_map('trim', explode(',', $id_list)));
                
                if (empty($ids)) return response()->json(['details' => []]);

                $query = Item::whereIn('id', $ids);
                $start_date = $request->get('start_date');
                $end_date = $request->get('end_date');
                
                if ($start_date && $end_date) $query->whereBetween('tanggal', [$start_date, $end_date]);
                
                $details = $query->orderBy('tanggal', 'asc')->get();

                $total_display = 0;
                $calcRow = function($item, $metric) {
                    $val = 0;
                    if ($metric === 'mix') $val = $item->scrap + $item->cakalan;
                    else $val = $item->{$metric};

                    return ($item->transaction_type === 'sale') ? -$val : $val;
                };

                foreach($details as $d) $total_display += $calcRow($d, 'mix');

                $first = Item::whereIn('id', $ids)->first();
                $item_key = ($first) ? $first->material . ' - ' . ($first->part ?? 'No Part') : 'N/A';
                
                $stock_awal = 0; 
                if ($start_date) {
                    $allHistory = Item::whereIn('id', $ids)->get();
                    $stock_awal = $allHistory->where('tanggal', '<', $start_date)->sum(function($item) {
                        $val = $item->scrap + $item->cakalan;
                        return ($item->transaction_type === 'sale') ? -$val : $val;
                    });
                }

                $in = 0; $out = 0;
                foreach($details as $d) {
                    $val = $d->scrap + $d->cakalan;
                    if ($d->transaction_type === 'sale') $out += $val;
                    else $in += $val;
                }
                $stock_akhir = $stock_awal + $in - $out;

                return response()->json([
                    'item_key' => $item_key, 
                    'total_display' => $total_display, 
                    'details' => $details,
                    'stock_awal' => $stock_awal, 'in' => $in, 'out' => $out, 'stock_akhir' => $stock_akhir
                ]);
            }
        }

        $materials = Item::where('transaction_type', 'mutation')->select('material')->distinct()->pluck('material');
        $parts = Item::where('transaction_type', 'mutation')->select('part')->distinct()->pluck('part');
        
        $distinctDates = Item::select(DB::raw('DISTINCT YEAR(tanggal) as year, DATE_FORMAT(tanggal, "%Y-%m") as ym'))
            ->orderBy('year', 'desc')->orderBy('ym', 'desc')->get();
        $distinctYears = $distinctDates->pluck('year')->unique()->sortDesc()->values();
        $distinctYearMonths = $distinctDates->groupBy('year')->map(fn($items) => $items->pluck('ym')->unique()->sort());

        $start_date = $request->input('start_date');
        $end_date = $request->input('end_date');
        $material_term = $request->input('material_term');
        $part_term = $request->input('part_term');
        $raw_selections = $request->input('pivot_months', []);
        $mode = $request->input('mode', 'resume');

        $query = Item::query()->orderBy('tanggal', 'desc');

        if ($mode == 'details') {
            $query->where('transaction_type', 'mutation');
        } else {
            $query->whereIn('transaction_type', ['mutation', 'sale']);
        }

        if ($start_date && $end_date) $query->whereBetween('tanggal', [$start_date, $end_date]);
        if ($material_term) $query->where('material', 'LIKE', '%' . $material_term . '%');
        if ($part_term) $query->where('part', 'LIKE', '%' . $part_term . '%');

        $items = $query->get();
        $summary_tree = []; 
        $months = []; 

        if ($mode == 'resume') {
            foreach ($items as $item) {
                $mat = $item->material;
                $part = $item->part ?? 'NO PART';
                $item_id = $item->id;

                $multiplier = ($item->transaction_type === 'sale') ? -1 : 1;
                $val_scrap = $item->scrap * $multiplier;
                $val_cakalan = $item->cakalan * $multiplier;
                $total_all = $val_scrap + $val_cakalan;

                if (!isset($summary_tree[$mat])) $summary_tree[$mat] = ['total_all' => 0, 'parts' => [], 'ids' => []];
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

        return view('mutations.index', compact(
            'items', 'mode', 'summary_tree',
            'start_date', 'end_date', 'material_term', 'part_term',
            'materials', 'parts', 'distinctYears', 'distinctYearMonths', 'raw_selections', 'months'
        ));
    }

    public function create() { $materials = Item::where('transaction_type', 'production')->select('material')->distinct()->orderBy('material')->pluck('material'); return view('mutations.create', compact('materials')); }
    public function store(Request $request) { 
        $request->validate(['tanggal'=>'required|date','material'=>'required','part'=>'required','tipe_barang'=>'required|in:gkg,scrap,cakalan','berat'=>'required|numeric|min:0.001']);
        $col = $request->tipe_barang; $filter = [['material','=',$request->material], ['part','=',$request->part]];
        $prod = Item::where('transaction_type','production')->where($filter)->sum($col);
        $used = Item::where('transaction_type','mutation')->where($filter)->sum($col);
        if ($request->berat > ($prod - $used)) return back()->withInput()->with('error', "Stock Produksi Kurang!");
        Item::create(['transaction_type'=>'mutation','tanggal'=>$request->tanggal,'material'=>strtoupper($request->material),'part'=>strtoupper($request->part),'no_lot'=>null,$col=>$request->berat,'berat_mentah'=>0]);
        return redirect()->route('mutations.index')->with('success','Saved');
    }
    public function edit($id) { $item = Item::where('id',$id)->where('transaction_type','mutation')->firstOrFail(); $materials = Item::where('transaction_type','production')->select('material')->distinct()->orderBy('material')->pluck('material'); return view('mutations.edit', compact('item','materials')); }
    public function update(Request $request, $id) { 
        $item = Item::where('id',$id)->where('transaction_type','mutation')->firstOrFail();
        $request->validate(['tanggal'=>'required|date','material'=>'required','part'=>'required','berat'=>'required|numeric|min:0.001']);
        $col = ($item->scrap > 0) ? 'scrap' : (($item->cakalan > 0) ? 'cakalan' : 'gkg');
        $filter = [['material','=',$request->material], ['part','=',$request->part]];
        $prod = Item::where('transaction_type','production')->where($filter)->sum($col);
        $used = Item::where('transaction_type','mutation')->where($filter)->where('id','!=',$id)->sum($col);
        if ($request->berat > ($prod - $used)) return back()->withInput()->with('error', "Stock Kurang!");
        $item->update(['tanggal'=>$request->tanggal,'material'=>strtoupper($request->material),'part'=>strtoupper($request->part),$col=>$request->berat]);
        return redirect()->route('mutations.index')->with('success','Updated');
    }
    public function destroy($id) { Item::where('id',$id)->where('transaction_type','mutation')->delete(); return back()->with('success','Deleted'); }
    
    public function bulkDestroy(Request $request) { $s=(array)$request->input('selected_ids',[]); $ids=[]; foreach($s as $v) $ids=array_merge($ids, explode(',',$v)); Item::whereIn('id',$ids)->where('transaction_type','mutation')->delete(); return back()->with('success','Deleted'); }

    public function downloadCsv(Request $request)
    {
        $ids = (array)$request->input('selected_ids', []);
        if (empty($ids)) return back()->with('error', 'No data selected');
        $items = Item::whereIn('id', $ids)->orderBy('tanggal', 'desc')->get();
        return $this->streamCsv($items, 'mutations_export_');
    }

    public function downloadPopupCsv(Request $request)
    {
        $id_list = $request->input('id_list');
        $ids = array_filter(array_map('trim', explode(',', $id_list)));
        $items = Item::whereIn('id', $ids)->orderBy('tanggal', 'asc')->get();

        $firstItem = $items->first();
        $headerInfo = $firstItem ? ($firstItem->material . ' - ' . ($firstItem->part ?? 'No Part')) : '';

        return $this->streamCsv($items, 'mutations_resume_detail_export_', $headerInfo);
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

            fputcsv($file, ['Tanggal', 'Material', 'Part', 'Type', 'Scrap', 'Cakalan', 'Total (+/-)']);
            
            foreach ($items as $item) {
                $val = $item->scrap + $item->cakalan;
                $total = ($item->transaction_type === 'sale') ? -$val : $val;
                $typeLabel = ($item->transaction_type === 'sale') ? 'OUT (Sale)' : 'IN (Mutasi)';

                fputcsv($file, [
                    $item->tanggal->format('Y-m-d'), $item->material, $item->part, 
                    $typeLabel, $item->scrap, $item->cakalan, $total
                ]);
            }
            fclose($file);
        };
        return response()->stream($callback, 200, $headers);
    }
}