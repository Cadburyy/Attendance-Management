@extends('layouts.app')

@section('content')
<style>
    .page-wrapper {
        animation: fadeInUp 0.5s ease-out;
    }

    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding-bottom: 20px;
    }

    .page-title {
        font-size: 32px;
        font-weight: 700;
        color: #0d3b66;
        margin: 0 0 8px 0;
    }

    .page-subtitle {
        color: #6b7280;
        margin: 0;
        font-size: 15px;
    }

    .btn-create {
        padding: 12px 24px;
        background: linear-gradient(135deg, #0d3b66 0%, #1a5490 100%);
        color: white;
        border: none;
        border-radius: 10px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(13, 59, 102, 0.3);
        text-decoration: none;
    }

    .btn-create:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(13, 59, 102, 0.4);
        color: white;
    }

    .filter-card {
        background: white;
        border-radius: 16px;
        padding: 24px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        margin-bottom: 32px;
        border: 1px solid rgba(0, 0, 0, 0.05);
    }

    .filter-form {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 16px;
    }

    .filter-input {
        width: 100%;
        padding: 12px 16px;
        border: 2px solid #e5e7eb;
        border-radius: 8px;
        font-size: 14px;
        transition: all 0.3s ease;
        font-family: inherit;
    }

    .filter-input:focus {
        outline: none;
        border-color: #0d3b66;
        box-shadow: 0 0 0 3px rgba(13, 59, 102, 0.1);
    }

    .btn-filter {
        padding: 12px 24px;
        background: #0d3b66;
        color: white;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        align-self: flex-end;
    }

    .btn-filter:hover {
        background: #0a2d52;
        transform: translateY(-2px);
    }

    .records-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
        gap: 24px;
        margin-bottom: 32px;
    }

    .record-card {
        background: white;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        border: 1px solid rgba(0, 0, 0, 0.05);
        display: flex;
        flex-direction: column;
    }

    .record-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 15px 40px rgba(0, 0, 0, 0.12);
    }

    .record-header {
        padding: 24px 20px;
        background: linear-gradient(135deg, #0d3b66 0%, #1a5490 100%);
        color: white;
        text-align: center;
    }

    .record-icon { font-size: 48px; margin-bottom: 12px; opacity: 0.9; }

    .record-header h3 { font-size: 24px; font-weight: 700; margin: 0; color: white; }

    .record-body { padding: 20px; flex: 1; display: flex; flex-direction: column; }

    .stat-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 16px; }

    .stat-box {
        text-align: center; padding: 12px; background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
        border-radius: 12px; border: 1px solid #b3e5fc; display: flex; flex-direction: column; justify-content: center; align-items: center;
    }

    .stat-value { font-size: 15px; font-weight: 700; color: #0d3b66; margin-bottom: 4px; }
    .stat-label { font-size: 11px; color: #6b7280; font-weight: 600; text-transform: uppercase; letter-spacing: 0.3px; }

    .status-badge { padding: 4px 12px; border-radius: 6px; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.3px; white-space: nowrap; display: inline-block; }
    .status-present { background: linear-gradient(135deg, #34d399 0%, #10b981 100%); color: white; }
    .status-absent { background: linear-gradient(135deg, #f87171 0%, #ef4444 100%); color: white; }
    .status-late { background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%); color: white; }
    .status-leave { background: linear-gradient(135deg, #60a5fa 0%, #3b82f6 100%); color: white; }
    .status-sick { background: linear-gradient(135deg, #a78bfa 0%, #8b5cf6 100%); color: white; }

    .record-details { flex: 1; background: #f9fafb; border-radius: 10px; padding: 16px; border: 1px solid #e5e7eb; }
    .detail-label { font-size: 12px; font-weight: 700; color: #6b7280; text-transform: uppercase; letter-spacing: 0.3px; margin: 0 0 4px 0; }
    .detail-text { font-size: 14px; color: #1f2937; font-weight: 500; }

    .record-footer { padding: 16px 20px; border-top: 1px solid rgba(0, 0, 0, 0.05); display: flex; gap: 8px; flex-wrap: wrap; }

    .btn-action {
        padding: 8px 12px; border: none; border-radius: 6px; font-size: 12px; font-weight: 600; cursor: pointer; transition: all 0.2s ease;
        display: inline-flex; align-items: center; gap: 6px; flex: 1; justify-content: center; text-decoration: none; min-height: 36px;
    }

    .btn-view { background: rgba(59, 130, 246, 0.1); color: #3b82f6; }
    .btn-view:hover { background: #3b82f6; color: white; }
    .btn-edit { background: rgba(245, 158, 11, 0.1); color: #f59e0b; }
    .btn-edit:hover { background: #f59e0b; color: white; }
    .btn-delete { background: rgba(107, 114, 128, 0.1); color: #6b7280; }
    .btn-delete:hover { background: #6b7280; color: white; }

    .alert-success-custom {
        background: linear-gradient(135deg, rgba(6, 168, 125, 0.1) 0%, rgba(6, 168, 125, 0.05) 100%);
        border-left: 4px solid #06a77d; border-radius: 10px; padding: 16px 20px; margin-bottom: 24px;
        display: flex; align-items: center; gap: 12px; color: #06a77d; font-weight: 500; animation: slideIn 0.4s ease-out;
    }

    @keyframes slideIn { from { opacity: 0; transform: translateX(-20px); } to { opacity: 1; transform: translateX(0); } }

    .modal-content-custom { border: 1px solid rgba(0, 0, 0, 0.08); box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15); }
    .modal-header-custom { background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%); border-bottom: 1px solid rgba(0, 0, 0, 0.05); }

    @media (max-width: 768px) {
        .page-header { flex-direction: column; align-items: flex-start; gap: 16px; }
        .records-grid { grid-template-columns: 1fr; }
        .filter-form { grid-template-columns: 1fr; }
    }
</style>

<div class="page-wrapper">
    <div class="page-header mb-5">
        <div class="header-content">
            <h1 class="page-title">Attendance Records</h1>
            <p class="page-subtitle">Track and manage attendance data</p>
        </div>
        <a href="{{ route('attendances.create') }}" class="btn btn-create">
            <i class="fas fa-plus"></i> Add Record
        </a>
    </div>

    @if(session('success'))
        <div class="alert-success-custom">
            <i class="fas fa-check-circle"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    <div class="filter-card">
        <form method="GET" class="filter-form">
            <div class="filter-group">
                <input type="text" name="name" class="filter-input" placeholder="Search by name..." value="{{ request('name') }}">
            </div>
            
            <div class="filter-group">
                <select name="role" class="filter-input">
                    <option value="">All Roles</option>
                    @foreach($roles as $role)
                        <option value="{{ $role }}" {{ request('role') == $role ? 'selected' : '' }}>{{ ucfirst($role) }}</option>
                    @endforeach
                </select>
            </div>

            <div class="filter-group">
                <select name="status" class="filter-input">
                    <option value="">All Status</option>
                    @foreach(['present', 'absent', 'late', 'leave', 'sick'] as $status)
                        <option value="{{ $status }}" {{ request('status') == $status ? 'selected' : '' }}>{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
            </div>

            <div class="filter-group">
                <input type="date" name="date" class="filter-input" value="{{ request('date') }}">
            </div>

            <button type="submit" class="btn-filter">
                <i class="fas fa-search"></i> Filter
            </button>
        </form>
    </div>

    <div class="records-grid">
        @forelse($attendances as $attendance)
            <div class="record-card">
                <div class="record-header">
                    <div class="record-icon"><i class="fas fa-user-check"></i></div>
                    <h3>{{ $attendance->user->name ?? 'N/A' }}</h3>
                </div>

                <div class="record-body">
                    <div class="stat-grid">
                        <div class="stat-box">
                            <div class="stat-value">{{ \Carbon\Carbon::parse($attendance->date)->format('M d, Y') }}</div>
                            <div class="stat-label">Date</div>
                        </div>
                        <div class="stat-box">
                            <div class="stat-value">
                                <span class="status-badge status-{{ $attendance->status }}">{{ ucfirst($attendance->status) }}</span>
                            </div>
                            <div class="stat-label">Status</div>
                        </div>
                    </div>

                    <div class="record-details">
                        <div class="detail-group">
                            <p class="detail-label">Time Info:</p>
                            <div class="detail-text">
                                <i class="fas fa-sign-in-alt" style="color: #0d3b66;"></i> {{ $attendance->check_in ?? '--:--' }}
                                &nbsp;|&nbsp;
                                <i class="fas fa-sign-out-alt" style="color: #0d3b66;"></i> {{ $attendance->check_out ?? '--:--' }}
                            </div>
                        </div>
                        <div class="detail-group" style="margin-top: 12px;">
                            <p class="detail-label">Notes:</p>
                            <div class="detail-text">{{ $attendance->notes ?? 'No details provided' }}</div>
                        </div>
                    </div>
                </div>

                <div class="record-footer">
                    <a href="{{ route('attendances.show', $attendance) }}" class="btn-action btn-view">View</a>
                    <a href="{{ route('attendances.edit', $attendance) }}" class="btn-action btn-edit">Edit</a>
                    <button type="button" class="btn-action btn-delete" 
                            data-bs-toggle="modal" data-bs-target="#deleteModal" 
                            data-action="{{ route('attendances.destroy', $attendance) }}">Delete</button>
                </div>
            </div>
        @empty
            <div class="empty-state-full" style="grid-column: 1/-1; text-align: center; padding: 60px;">
                <i class="fas fa-calendar-times" style="font-size: 64px; color: #d1d5db; margin-bottom: 20px; display: block;"></i>
                <h3>No records found</h3>
                <p>Try adjusting your filters or add a new record.</p>
            </div>
        @endforelse
    </div>

    @if($attendances->hasPages())
        <div class="pagination-wrapper d-flex justify-content-center">
            {{ $attendances->links() }}
        </div>
    @endif
</div>

<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content modal-content-custom">
            <div class="modal-header modal-header-custom">
                <h5 class="modal-title font-weight-bold" style="color: #0d3b66;">Delete Record</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <p>Are you sure you want to delete this record? This action cannot be undone.</p>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form id="delete-form" method="POST" style="display:inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger px-4">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        var deleteModal = document.getElementById('deleteModal');
        if (deleteModal) {
            deleteModal.addEventListener('show.bs.modal', function (event) {
                var button = event.relatedTarget;
                var action = button.getAttribute('data-action');
                var form = deleteModal.querySelector('#delete-form');
                form.action = action;
            });
        }
    });
</script>
@endsection