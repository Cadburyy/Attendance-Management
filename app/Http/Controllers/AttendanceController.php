<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AttendanceController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:attendance');
    }

    public function index(Request $request)
    {
        $roles = Role::pluck('name', 'name')->all();
        $query = Attendance::with('user');

        if (Auth::user()->can('override')) {
            $query->where('status', '!=', 'pending');
        } else {
            $query->where('user_id', Auth::id());
        }

        if ($request->filled('name') && Auth::user()->can('override')) {
            $query->whereHas('user', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->name . '%');
            });
        }

        if ($request->filled('role') && Auth::user()->can('override')) {
            $query->whereHas('user', function ($q) use ($request) {
                $q->role($request->role);
            });
        }

        if ($request->filled('date')) {
            $query->whereDate('date', $request->date);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $attendances = $query->orderBy('date', 'desc')->paginate(15)->appends($request->query());
        $users = User::all();

        return view('attendances.index', compact('attendances', 'users', 'roles'));
    }

    public function create()
    {
        if (Auth::user()->can('override')) {
            $users = User::all();
        } else {
            $users = User::where('id', Auth::id())->get();
        }
        return view('attendances.create', compact('users'));
    }

    public function store(Request $request)
    {
        $isOverride = Auth::user()->can('override');
        $dateValidation = $isOverride 
            ? 'required|date' 
            : 'required|date|after_or_equal:' . now()->subDay()->toDateString() . '|before_or_equal:' . now()->addDay()->toDateString();

        $rules = [
            'user_id' => 'required|exists:users,id',
            'date' => $dateValidation,
            'check_in' => 'nullable',
            'check_out' => 'nullable',
            'status' => 'required|in:present,absent,late,leave,sick',
            'notes' => 'nullable|string|max:500',
        ];

        if (!$isOverride) {
            $rules['override_reason'] = 'nullable|string|max:500';
        }

        $validated = $request->validate($rules);

        if (!$isOverride && $validated['user_id'] != Auth::id()) {
            return redirect()->route('home')->with('error', 'You cannot access the page.');
        }

        $existingRecord = Attendance::where('user_id', $validated['user_id'])
                                    ->whereDate('date', $validated['date'])
                                    ->first();

        if (!$isOverride) {
            $reason = $validated['override_reason'] ?? 'Pengajuan absensi baru (Manual)';

            if ($existingRecord) {
                $existingRecord->update([
                    'requested_status' => $validated['status'],
                    'requested_check_in' => $validated['check_in'] ?? $existingRecord->check_in,
                    'requested_check_out' => $validated['check_out'] ?? $existingRecord->check_out,
                    'override_status' => 'pending',
                    'override_reason' => $reason,
                    'notes' => $validated['notes'] ?? $existingRecord->notes,
                ]);
            } else {
                Attendance::create([
                    'user_id' => $validated['user_id'],
                    'date' => $validated['date'],
                    'status' => 'pending',
                    'requested_status' => $validated['status'],
                    'requested_check_in' => $validated['check_in'],
                    'requested_check_out' => $validated['check_out'],
                    'override_status' => 'pending',
                    'override_reason' => $reason,
                    'notes' => $validated['notes'] ?? null,
                ]);
            }

            return redirect()->route('attendances.index')
                ->with('success', 'Attendance request submitted successfully! Waiting for HR approval.');
        } else {
            $validated['override_status'] = 'override';
            
            if ($existingRecord) {
                $existingRecord->update($validated);
            } else {
                Attendance::create($validated);
            }

            return redirect()->route('attendances.index')
                ->with('success', 'Attendance recorded successfully!');
        }
    }

    public function show(Attendance $attendance)
    {
        if (!Auth::user()->can('override') && $attendance->user_id !== Auth::id()) {
            return redirect()->route('home')->with('error', 'You cannot access the page.');
        }
        return view('attendances.show', compact('attendance'));
    }

    public function edit(Attendance $attendance)
    {
        if (!Auth::user()->can('override') && $attendance->user_id !== Auth::id()) {
            return redirect()->route('home')->with('error', 'You cannot access the page.');
        }
        $users = User::all();
        return view('attendances.edit', compact('attendance', 'users'));
    }

    public function update(Request $request, Attendance $attendance)
    {
        if (!Auth::user()->can('override') && $attendance->user_id !== Auth::id()) {
            return redirect()->route('home')->with('error', 'You cannot access the page.');
        }

        $dateValidation = Auth::user()->can('override') 
            ? 'required|date' 
            : 'required|date|after_or_equal:' . now()->subDay()->toDateString() . '|before_or_equal:' . now()->addDay()->toDateString();

        $validated = $request->validate([
            'user_id' => Auth::user()->can('override') ? 'required|exists:users,id' : 'nullable',
            'date' => $dateValidation,
            'check_in' => 'nullable',
            'check_out' => 'nullable',
            'status' => Auth::user()->can('override') ? 'required|in:present,absent,late,leave,sick' : 'nullable',
            'notes' => 'nullable|string|max:500',
        ]);

        if (!Auth::user()->can('override')) {
            unset($validated['status']);
            unset($validated['user_id']);
        } else {
            $validated['override_status'] = 'override';
        }

        $attendance->update($validated);

        return redirect()->route('attendances.index')
            ->with('success', 'Attendance updated successfully!');
    }

    public function destroy(Attendance $attendance)
    {
        if (!Auth::user()->can('override')) {
            return redirect()->route('home')->with('error', 'You cannot access the page.');
        }
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
            ['status' => 'present', 'override_status' => 'machine'] 
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

        $query = Attendance::whereBetween('date', [$startDate, $endDate])->where('status', '!=', 'pending');
        if (!Auth::user()->can('override')) {
            $query->where('user_id', Auth::id());
        }

        $stats = [
            'total_present' => (clone $query)->where('status', 'present')->count(),
            'total_absent' => (clone $query)->where('status', 'absent')->count(),
            'total_late' => (clone $query)->where('status', 'late')->count(),
            'total_leave' => (clone $query)->where('status', 'leave')->count(),
            'total_sick' => (clone $query)->where('status', 'sick')->count(),
        ];

        return response()->json($stats);
    }

    public function bulkDestroy(Request $request)
    {
        if (!Auth::user()->can('override')) {
            return redirect()->route('home')->with('error', 'You cannot access the page.');
        }
        $ids = $request->validate(['ids' => 'required|array'])['ids'];
        Attendance::whereIn('id', $ids)->delete();

        return redirect()->route('attendances.index')
            ->with('success', 'Selected attendances deleted successfully!');
    }

    public function requestOverride(Request $request, Attendance $attendance)
    {
        if ($attendance->user_id !== Auth::id() && !Auth::user()->can('override')) {
            return redirect()->route('home')->with('error', 'You cannot access the page.');
        }

        $validated = $request->validate([
            'requested_status' => 'required|in:present,absent,late,leave,sick',
            'requested_check_in' => 'nullable|date_format:H:i',
            'requested_check_out' => 'nullable|date_format:H:i',
            'override_reason' => 'required|string|max:500',
        ]);

        $attendance->update([
            'override_status' => 'pending',
            'requested_status' => $validated['requested_status'],
            'requested_check_in' => $validated['requested_check_in'],
            'requested_check_out' => $validated['requested_check_out'],
            'override_reason' => $validated['override_reason'],
        ]);

        return redirect()->route('attendances.index')
            ->with('success', 'Override request submitted successfully! Waiting for HR approval.');
    }

    public function approvals(Request $request)
    {
        if (!Auth::user()->can('override')) {
            return redirect()->route('home')->with('error', 'You cannot access the page.');
        }

        $pendingRequests = Attendance::with('user')
            ->where('override_status', 'pending')
            ->orderBy('updated_at', 'asc')
            ->paginate(15);

        return view('attendances.approvals', compact('pendingRequests'));
    }

    public function approveOverride(Request $request, Attendance $attendance)
    {
        if (!Auth::user()->can('override')) {
            return redirect()->route('home')->with('error', 'You cannot access the page.');
        }
        $attendance->update([
            'status' => $attendance->requested_status,
            'check_in' => $attendance->requested_check_in ?? $attendance->check_in,
            'check_out' => $attendance->requested_check_out ?? $attendance->check_out,
            'override_status' => 'override',
            'requested_status' => null,
            'requested_check_in' => null,
            'requested_check_out' => null,
            'override_reason' => null
        ]);

        return redirect()->back()->with('success', 'Request accepted! It has been added to the main attendance records.');
    }

    public function rejectOverride(Request $request, Attendance $attendance)
    {
        if (!Auth::user()->can('override')) {
            return redirect()->route('home')->with('error', 'You cannot access the page.');
        }

        if ($attendance->status === 'pending') {
            $attendance->delete();
            return redirect()->back()->with('success', 'Request declined and deleted from the system.');
        }

        $attendance->update([
            'override_status' => 'rejected',
            'requested_status' => null,
            'requested_check_in' => null,
            'requested_check_out' => null,
            'override_reason' => null
        ]);
        
        return redirect()->back()->with('success', 'Override request declined.');
    }

    public function bulkApprove(Request $request)
    {
        if (!Auth::user()->can('override')) {
            return redirect()->route('home')->with('error', 'You cannot access the page.');
        }

        $request->validate([
            'request_ids' => 'required|array',
            'request_ids.*' => 'exists:attendances,id'
        ]);

        $attendances = Attendance::whereIn('id', $request->request_ids)
            ->where('override_status', 'pending')
            ->get();

        foreach ($attendances as $attendance) {
            $attendance->update([
                'status' => $attendance->requested_status,
                'check_in' => $attendance->requested_check_in ?? $attendance->check_in,
                'check_out' => $attendance->requested_check_out ?? $attendance->check_out,
                'override_status' => 'override',
                'requested_status' => null,
                'requested_check_in' => null,
                'requested_check_out' => null,
                'override_reason' => null
            ]);
        }

        return redirect()->back()->with('success', count($attendances) . ' requests approved successfully.');
    }

    public function bulkReject(Request $request)
    {
        if (!Auth::user()->can('override')) {
            return redirect()->route('home')->with('error', 'You cannot access the page.');
        }

        $request->validate([
            'request_ids' => 'required|array',
            'request_ids.*' => 'exists:attendances,id'
        ]);

        $attendances = Attendance::whereIn('id', $request->request_ids)
            ->where('override_status', 'pending')
            ->get();

        foreach ($attendances as $attendance) {
            if ($attendance->status === 'pending') {
                $attendance->delete();
            } else {
                $attendance->update([
                    'override_status' => 'rejected',
                    'requested_status' => null,
                    'requested_check_in' => null,
                    'requested_check_out' => null,
                    'override_reason' => null
                ]);
            }
        }

        return redirect()->back()->with('success', count($attendances) . ' requests rejected successfully.');
    }
}