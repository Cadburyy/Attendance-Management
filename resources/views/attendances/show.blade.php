@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <h1 class="mb-4">Attendance Details</h1>

            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <p class="text-muted mb-1">User</p>
                            <h6 class="fw-bold">{{ $attendance->user->name }}</h6>
                        </div>
                        <div class="col-md-6">
                            <p class="text-muted mb-1">Date</p>
                            <h6 class="fw-bold">{{ $attendance->date->format('M d, Y') }}</h6>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <p class="text-muted mb-1">Check-In Time</p>
                            <h6 class="fw-bold">{{ $attendance->check_in ?? 'Not recorded' }}</h6>
                        </div>
                        <div class="col-md-6">
                            <p class="text-muted mb-1">Check-Out Time</p>
                            <h6 class="fw-bold">{{ $attendance->check_out ?? 'Not recorded' }}</h6>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <p class="text-muted mb-1">Status</p>
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
                        </div>
                    </div>

                    @if($attendance->notes)
                        <div class="mb-3">
                            <p class="text-muted mb-1">Notes</p>
                            <p class="fw-normal">{{ $attendance->notes }}</p>
                        </div>
                    @endif

                    <div class="d-flex gap-2">
                        <a href="{{ route('attendances.edit', $attendance) }}" class="btn btn-warning">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                        <form method="POST" action="{{ route('attendances.destroy', $attendance) }}" style="display:inline;" onsubmit="return confirm('Delete this record?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger" title="Delete">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </form>
                        <a href="{{ route('attendances.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
