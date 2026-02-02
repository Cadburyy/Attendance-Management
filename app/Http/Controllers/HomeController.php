<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Item; 
use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $items = Item::all();
        $grouped = $items->groupBy('material');

        $prodLabels = [];
        $prodData = [];
        $prodBreakdown = [];

        $mutLabels = [];
        $mutData = [];
        $mutBreakdown = [];

        foreach ($grouped as $material => $transactions) {
            $pScrap = $transactions->where('transaction_type', 'production')->sum('scrap');
            $pCakalan = $transactions->where('transaction_type', 'production')->sum('cakalan');
            
            $mScrap = $transactions->where('transaction_type', 'mutation')->sum('scrap');
            $mCakalan = $transactions->where('transaction_type', 'mutation')->sum('cakalan');

            $sScrap = $transactions->where('transaction_type', 'sale')->sum('scrap');
            $sCakalan = $transactions->where('transaction_type', 'sale')->sum('cakalan');

            $netProdScrap = max(0, $pScrap - $mScrap);
            $netProdCakalan = max(0, $pCakalan - $mCakalan);
            $totalProd = $netProdScrap + $netProdCakalan;

            if ($totalProd > 0) {
                $prodLabels[] = $material;
                $prodData[] = round($totalProd, 2);
                $prodBreakdown[$material] = [
                    'scrap' => round($netProdScrap, 2),
                    'cakalan' => round($netProdCakalan, 2)
                ];
            }

            $netMutScrap = max(0, $mScrap - $sScrap);
            $netMutCakalan = max(0, $mCakalan - $sCakalan);
            $totalMut = $netMutScrap + $netMutCakalan;

            if ($totalMut > 0) {
                $mutLabels[] = $material;
                $mutData[] = round($totalMut, 2);
                $mutBreakdown[$material] = [
                    'scrap' => round($netMutScrap, 2),
                    'cakalan' => round($netMutCakalan, 2)
                ];
            }
        }

        return view('home', compact(
            'prodLabels', 'prodData', 'prodBreakdown',
            'mutLabels', 'mutData', 'mutBreakdown'
        ));
    }
}