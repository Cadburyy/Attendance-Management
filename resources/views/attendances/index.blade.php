@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="mb-0">Attendance Records</h1>
        </div>
        <div class="col-md-4 text-end">
            <a href="{{ route('attendances.create') }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> New Attendance
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card shadow-sm">
        <div class="card-header bg-light">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <input type="date" name="date" class="form-control form-control-sm" 
                        value="{{ request('date') }}" placeholder="Filter by date">
                </div>
                <div class="col-md-3">
                    <select name="user_id" class="form-select form-select-sm">
                        <option value="">All Users</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>
                                {{ $user->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-select form-select-sm">
                        <option value="">All Status</option>
                        <option value="present" {{ request('status') == 'present' ? 'selected' : '' }}>Present</option>
                        <option value="absent" {{ request('status') == 'absent' ? 'selected' : '' }}>Absent</option>
                        <option value="late" {{ request('status') == 'late' ? 'selected' : '' }}>Late</option>
                        <option value="leave" {{ request('status') == 'leave' ? 'selected' : '' }}>Leave</option>
                        <option value="sick" {{ request('status') == 'sick' ? 'selected' : '' }}>Sick</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary btn-sm w-100">Filter</button>
                </div>
            </form>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>User</th>
                            <th>Date</th>
                            <th>Check-In</th>
                            <th>Check-Out</th>
                            <th>Status</th>
                            <th>Notes</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($attendances as $attendance)
                            <tr>
                                <td>{{ $attendance->id }}</td>
                                <td>{{ $attendance->user->name ?? 'N/A' }}</td>
                                <td>{{ $attendance->date->format('M d, Y') }}</td>
                                <td>{{ $attendance->check_in ?? '-' }}</td>
                                <td>{{ $attendance->check_out ?? '-' }}</td>
                                <td>
                                    @if($attendance->status == 'present')
                                        <span class="badge bg-success">Present</span>
                                    @elseif($attendance->status == 'absent')
                                        <span class="badge bg-danger">Absent</span>
                                    @elseif($attendance->status == 'late')
                                        <span class="badge bg-warning">Late</span>
                                    @elseif($attendance->status == 'leave')
                                        <span class="badge bg-info">Leave</span>
                                    @elseif($attendance->status == 'sick')
                                        <span class="badge bg-secondary">Sick</span>
                                    @endif
                                </td>
                                <td>{{ Str::limit($attendance->notes, 30) }}</td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('attendances.show', $attendance) }}" class="btn btn-sm btn-info" title="View">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ route('attendances.edit', $attendance) }}" class="btn btn-sm btn-warning" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form method="POST" action="{{ route('attendances.destroy', $attendance) }}" 
                                            style="display:inline;" onsubmit="return confirm('Delete this record?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">
                                    No attendance records found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer">
            {{ $attendances->links() }}
        </div>
    </div>
</div>
@endsection
