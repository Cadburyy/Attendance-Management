<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AttendanceController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $query = Attendance::with('user');

        if ($request->filled('date')) {
            $query->whereDate('date', $request->date);
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $attendances = $query->orderBy('date', 'desc')->paginate(15);
        $users = User::all();

        return view('attendances.index', compact('attendances', 'users'));
    }

    public function create()
    {
        $users = User::all();
        return view('attendances.create', compact('users'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'date' => 'required|date',
            'check_in' => 'nullable|date_format:H:i',
            'check_out' => 'nullable|date_format:H:i',
            'status' => 'required|in:present,absent,late,leave,sick',
            'notes' => 'nullable|string|max:500',
        ]);

        Attendance::create($validated);

        return redirect()->route('attendances.index')
            ->with('success', 'Attendance recorded successfully!');
    }

    public function show(Attendance $attendance)
    {
        return view('attendances.show', compact('attendance'));
    }

    public function edit(Attendance $attendance)
    {
        $users = User::all();
        return view('attendances.edit', compact('attendance', 'users'));
    }

    public function update(Request $request, Attendance $attendance)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'date' => 'required|date',
            'check_in' => 'nullable|date_format:H:i',
            'check_out' => 'nullable|date_format:H:i',
            'status' => 'required|in:present,absent,late,leave,sick',
            'notes' => 'nullable|string|max:500',
        ]);

        $attendance->update($validated);

        return redirect()->route('attendances.index')
            ->with('success', 'Attendance updated successfully!');
    }

    public function destroy(Attendance $attendance)
    {
        $attendance->delete();

        return redirect()->route('attendances.index')
            ->with('success', 'Attendance deleted successfully!');
    }

    public function checkIn(Request $request)
    {
        $user = Auth::user();
        $today = now()->toDateString();

        $attendance = Attendance::firstOrCreate(
            ['user_id' => $user->id, 'date' => $today],
            ['status' => 'present']
        );

        if (!$attendance->check_in) {
            $attendance->update(['check_in' => now()->toTimeString()]);
        }

        return redirect()->back()->with('success', 'Check-in successful!');
    }

    public function checkOut(Request $request)
    {
        $user = Auth::user();
        $today = now()->toDateString();

        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('date', $today)
            ->first();

        if ($attendance && !$attendance->check_out) {
            $attendance->update(['check_out' => now()->toTimeString()]);
        }

        return redirect()->back()->with('success', 'Check-out successful!');
    }

    public function getStats(Request $request)
    {
        $startDate = $request->get('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->get('end_date', now()->toDateString());

        $stats = [
            'total_present' => Attendance::forDateRange($startDate, $endDate)
                ->byStatus('present')->count(),
            'total_absent' => Attendance::forDateRange($startDate, $endDate)
                ->byStatus('absent')->count(),
            'total_late' => Attendance::forDateRange($startDate, $endDate)
                ->byStatus('late')->count(),
            'total_leave' => Attendance::forDateRange($startDate, $endDate)
                ->byStatus('leave')->count(),
            'total_sick' => Attendance::forDateRange($startDate, $endDate)
                ->byStatus('sick')->count(),
        ];

        return response()->json($stats);
    }

    public function bulkDestroy(Request $request)
    {
        $ids = $request->validate(['ids' => 'required|array'])['ids'];
        Attendance::whereIn('id', $ids)->delete();

        return redirect()->route('attendances.index')
            ->with('success', 'Selected attendances deleted successfully!');
    }
}
