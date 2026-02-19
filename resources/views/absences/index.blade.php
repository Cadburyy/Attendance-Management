@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="mb-0">Absence Requests</h1>
        </div>
        <div class="col-md-4 text-end">
            <a href="{{ route('absences.create') }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> New Request
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
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                        <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
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
                            <th>Employee</th>
                            <th>Date</th>
                            <th>Reason</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($absences as $absence)
                            <tr>
                                <td>{{ $absence->id }}</td>
                                <td>{{ $absence->employee_name }}</td>
                                <td>{{ $absence->date->format('M d, Y') }}</td>
                                <td>{{ ucfirst($absence->reason) }}</td>
                                <td>
                                    @if($absence->status == 'pending')
                                        <span class="badge bg-warning">Pending</span>
                                    @elseif($absence->status == 'approved')
                                        <span class="badge bg-success">Approved</span>
                                    @elseif($absence->status == 'rejected')
                                        <span class="badge bg-danger">Rejected</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="{{ route('absences.show', $absence) }}" class="btn btn-sm btn-info" title="View">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        @if($absence->status == 'pending')
                                            <form method="POST" action="{{ route('absences.approve', $absence) }}" style="display:inline;">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-success" title="Approve">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            </form>
                                            <form method="POST" action="{{ route('absences.reject', $absence) }}" style="display:inline;">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-danger" title="Reject">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </form>
                                        @endif
                                        <a href="{{ route('absences.edit', $absence) }}" class="btn btn-sm btn-warning" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form method="POST" action="{{ route('absences.destroy', $absence) }}" 
                                            style="display:inline;" onsubmit="return confirm('Delete this record?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-secondary" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">
                                    No absence records found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer">
            {{ $absences->links() }}
        </div>
    </div>
</div>
@endsection
