<?php

namespace App\Http\Controllers;

use App\Models\Weight;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class WeightController extends Controller
{
    function __construct()
    {
        $this->middleware('permission:weight');
    }

    public function index(Request $request)
    {
        $materialSearch = $request->input('material_search');
        $partSearch = $request->input('part_search');

        $query = Weight::orderBy('material', 'asc')
                       ->orderBy('part', 'asc');
        
        if ($materialSearch) {
            $query->where('material', 'like', '%' . $materialSearch . '%');
        }

        if ($partSearch) {
            $query->where('part', 'like', '%' . $partSearch . '%');
        }

        $weights = $query->paginate(10)->appends([
            'material_search' => $materialSearch,
            'part_search' => $partSearch,
        ]);

        $uniqueMaterials = Weight::select('material')->distinct()->pluck('material');
        $uniqueParts = Weight::select('part')->distinct()->pluck('part');

        return view('weights.index', compact('weights', 'uniqueMaterials', 'uniqueParts'));
    }

    public function create()
    {
        return view('weights.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'part' => 'required|string|max:255',
            'weight' => 'required|numeric',
            'material' => [
                'required', 
                'string', 
                'max:255',
                Rule::unique('weights')->where(function ($query) use ($request) {
                    return $query->where('material', $request->material)
                                     ->where('part', $request->part);
                })
            ],
        ], [
            'material.unique' => 'The combination of Material and Part already exists.',
        ]);

        Weight::create($request->all());

        return redirect()->route('weights.index')
            ->with('success', 'Weight created successfully.');
    }

    public function edit(Weight $weight)
    {
        return view('weights.edit', compact('weight'));
    }

    public function update(Request $request, Weight $weight)
    {
        $request->validate([
            'weight' => 'required|numeric',
            'material' => [
                'required', 
                'string', 
                'max:255',
                Rule::unique('weights')->where(function ($query) use ($request) {
                    return $query->where('material', $request->material)
                                     ->where('part', $request->part);
                })->ignore($weight->id)
            ],
            'part' => 'required|string|max:255',
        ], [
            'material.unique' => 'The combination of Material and Part already exists.',
        ]);

        $weight->update($request->all());

        return redirect()->route('weights.index')
            ->with('success', 'Weight updated successfully');
    }

    public function destroy(Weight $weight)
    {
        $weight->delete();

        return redirect()->route('weights.index')
            ->with('success', 'Weight deleted successfully');
    }
}