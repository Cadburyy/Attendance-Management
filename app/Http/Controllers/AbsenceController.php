<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Absence;

class AbsenceController extends Controller
{
    public function index()
    {
        $absences = Absence::latest()->paginate(10);
        return view('absences.index', compact('absences'));
    }

    public function create()
    {
        return view('absences.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'employee_name' => 'required|string|max:255',
            'date' => 'required|date',
            'reason' => 'required|string',
            'details' => 'nullable|string',
        ]);

        Absence::create([
            'employee_name' => $request->employee_name,
            'date' => $request->date,
            'reason' => $request->reason,
            'details' => $request->details,
            'status' => 'pending', 
        ]);

        return redirect()->route('login')->with('success', 'Absence request submitted successfully!');
    }
}