<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Absence;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class AbsenceController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $query = Absence::with('user');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date')) {
            $query->whereDate('date', $request->date);
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        $absences = $query->orderBy('date', 'desc')->paginate(15);
        $users = User::all();

        return view('absences.index', compact('absences', 'users'));
    }

    public function create()
    {
        $users = User::all();
        return view('absences.create', compact('users'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'nullable|exists:users,id',
            'employee_name' => 'required|string|max:255',
            'date' => 'required|date',
            'reason' => 'required|string|in:sick,leave,emergency,other',
            'details' => 'nullable|string|max:1000',
        ]);

        $validated['status'] = 'pending';
        if ($request->user()->hasRole('admin')) {
            $validated['status'] = 'approved';
        }

        Absence::create($validated);

        return redirect()->route('absences.index')
            ->with('success', 'Absence request submitted successfully!');
    }

    public function show(Absence $absence)
    {
        return view('absences.show', compact('absence'));
    }

    public function edit(Absence $absence)
    {
        $this->authorize('update', $absence);
        $users = User::all();
        return view('absences.edit', compact('absence', 'users'));
    }

    public function update(Request $request, Absence $absence)
    {
        $this->authorize('update', $absence);

        $validated = $request->validate([
            'user_id' => 'nullable|exists:users,id',
            'employee_name' => 'required|string|max:255',
            'date' => 'required|date',
            'reason' => 'required|string|in:sick,leave,emergency,other',
            'details' => 'nullable|string|max:1000',
            'status' => 'sometimes|in:pending,approved,rejected',
        ]);

        $absence->update($validated);

        return redirect()->route('absences.index')
            ->with('success', 'Absence updated successfully!');
    }

    public function destroy(Absence $absence)
    {
        $this->authorize('delete', $absence);
        $absence->delete();

        return redirect()->route('absences.index')
            ->with('success', 'Absence deleted successfully!');
    }

    public function approve(Absence $absence)
    {
        $this->authorize('update', $absence);
        $absence->update(['status' => 'approved']);

        return redirect()->route('absences.index')
            ->with('success', 'Absence approved successfully!');
    }

    public function reject(Absence $absence)
    {
        $this->authorize('update', $absence);
        $absence->update(['status' => 'rejected']);

        return redirect()->route('absences.index')
            ->with('success', 'Absence rejected successfully!');
    }

    public function bulkDestroy(Request $request)
    {
        $ids = $request->validate(['ids' => 'required|array'])['ids'];
        Absence::whereIn('id', $ids)->delete();

        return redirect()->route('absences.index')
            ->with('success', 'Selected absences deleted successfully!');
    }
}