@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <h1 class="mb-4">Absence Request Details</h1>

            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <p class="text-muted mb-1">Employee Name</p>
                            <h6 class="fw-bold">{{ $absence->employee_name }}</h6>
                        </div>
                        <div class="col-md-6">
                            <p class="text-muted mb-1">Date</p>
                            <h6 class="fw-bold">{{ $absence->date->format('M d, Y') }}</h6>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <p class="text-muted mb-1">Reason</p>
                            <h6 class="fw-bold">{{ ucfirst($absence->reason) }}</h6>
                        </div>
                        <div class="col-md-6">
                            <p class="text-muted mb-1">Status</p>
                            @if($absence->status == 'pending')
                                <span class="badge bg-warning">Pending</span>
                            @elseif($absence->status == 'approved')
                                <span class="badge bg-success">Approved</span>
                            @elseif($absence->status == 'rejected')
                                <span class="badge bg-danger">Rejected</span>
                            @endif
                        </div>
                    </div>

                    @if($absence->details)
                        <div class="mb-3">
                            <p class="text-muted mb-1">Details</p>
                            <p class="fw-normal">{{ $absence->details }}</p>
                        </div>
                    @endif

                    <div class="d-flex gap-2">
                        <a href="{{ route('absences.edit', $absence) }}" class="btn btn-warning">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                        @if($absence->status == 'pending')
                            <form method="POST" action="{{ route('absences.approve', $absence) }}" style="display:inline;">
                                @csrf
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-check"></i> Approve
                                </button>
                            </form>
                            <form method="POST" action="{{ route('absences.reject', $absence) }}" style="display:inline;">
                                @csrf
                                <button type="submit" class="btn btn-danger">
                                    <i class="fas fa-times"></i> Reject
                                </button>
                            </form>
                        @endif
                        <form method="POST" action="{{ route('absences.destroy', $absence) }}" style="display:inline;" onsubmit="return confirm('Delete this record?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-secondary">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </form>
                        <a href="{{ route('absences.index') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
