@extends('layouts.app')

@section('content')
<div class="page-wrapper">
    <div class="page-header mb-5">
        <div class="header-content">
            <h1 class="page-title">Absence Requests</h1>
            <p class="page-subtitle">Manage and track absence requests</p>
        </div>
        <a href="{{ route('absences.create') }}" class="btn btn-create">
            <i class="fas fa-plus"></i> Add New Request
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
                <input type="date" name="date" class="filter-input" 
                    value="{{ request('date') }}" placeholder="Filter by date">
            </div>
            <div class="filter-group">
                <select name="user_id" class="filter-input">
                    <option value="">All Users</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>
                            {{ $user->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="filter-group">
                <select name="status" class="filter-input">
                    <option value="">All Status</option>
                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                    <option value="rejected" {{ request('status') == 'rejected' ? 'selected' : '' }}>Rejected</option>
                </select>
            </div>
            <button type="submit" class="btn-filter">
                <i class="fas fa-search"></i> Filter
            </button>
        </form>
    </div>

    <div class="records-grid">
        @forelse($absences as $absence)
            <div class="record-card">
                <div class="record-header">
                    <div class="record-icon">
                        <i class="fas fa-user-clock"></i>
                    </div>
                    <h3>{{ $absence->employee_name }}</h3>
                </div>
                
                <div class="record-body">
                    <div class="stat-grid">
                        <div class="stat-box">
                            <div class="stat-value">{{ $absence->date->format('M d, Y') }}</div>
                            <div class="stat-label">Date</div>
                        </div>
                        <div class="stat-box">
                            <div class="stat-value">
                                <span class="status-badge status-{{ $absence->status }}">{{ ucfirst($absence->status) }}</span>
                            </div>
                            <div class="stat-label">Status</div>
                        </div>
                    </div>
                    
                    <div class="record-details">
                        <div class="detail-group">
                            <p class="detail-label">Reason:</p>
                            <div class="detail-text">{{ ucfirst($absence->reason) }}</div>
                        </div>
                        <div class="detail-group" style="margin-top: 12px;">
                            <p class="detail-label">Details:</p>
                            <div class="detail-text">{{ $absence->details ?? 'No details provided' }}</div>
                        </div>
                    </div>
                </div>
                
                <div class="record-footer">
                    <a href="{{ route('absences.show', $absence) }}" class="btn-action btn-view" title="View">
                        <i class="fas fa-eye"></i> View
                    </a>
                    
                    @if($absence->status == 'pending')
                        <form method="POST" action="{{ route('absences.approve', $absence) }}" style="display:inline; flex: 1;">
                            @csrf
                            <button type="submit" class="btn-action btn-approve w-100" title="Approve">
                                <i class="fas fa-check"></i> Approve
                            </button>
                        </form>
                        <form method="POST" action="{{ route('absences.reject', $absence) }}" style="display:inline; flex: 1;">
                            @csrf
                            <button type="submit" class="btn-action btn-reject w-100" title="Reject">
                                <i class="fas fa-times"></i> Reject
                            </button>
                        </form>
                    @endif
                    
                    <a href="{{ route('absences.edit', $absence) }}" class="btn-action btn-edit" title="Edit">
                        <i class="fas fa-edit"></i> Edit
                    </a>
                    
                    <button type="button" class="btn-action btn-delete delete-btn"
                        data-bs-toggle="modal" data-bs-target="#deleteModal"
                        data-action="{{ route('absences.destroy', $absence) }}" title="Delete">
                        <i class="fas fa-trash"></i> Delete
                    </button>
                </div>
            </div>
        @empty
            <div class="empty-state-full">
                <i class="fas fa-inbox"></i>
                <h3>No absence records found</h3>
                <p>Start by creating a new absence request</p>
            </div>
        @endforelse
    </div>

    @if($absences->hasPages())
        <div class="pagination-wrapper">
            {{ $absences->links() }}
        </div>
    @endif
</div>

<!-- Delete Confirmation Modal (Aligned with Roles design) -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content modal-content-custom">
            <div class="modal-header modal-header-custom">
                <h5 class="modal-title">Delete Absence Record</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body modal-body-custom">
                <p>Are you sure you want to delete this record? This action cannot be undone.</p>
            </div>
            <div class="modal-footer modal-footer-custom">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form id="delete-form" method="POST" style="display:inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>

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
    }

    .btn-create:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(13, 59, 102, 0.4);
    }

    .alert-success-custom {
        background: linear-gradient(135deg, rgba(6, 168, 125, 0.1) 0%, rgba(6, 168, 125, 0.05) 100%);
        border-left: 4px solid #06a77d;
        border-radius: 10px;
        padding: 16px 20px;
        margin-bottom: 24px;
        display: flex;
        align-items: center;
        gap: 12px;
        color: #06a77d;
        font-weight: 500;
        animation: slideIn 0.4s ease-out;
    }

    .alert-success-custom i {
        font-size: 20px;
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

    /* Updated Card Header to match Roles */
    .record-header {
        padding: 24px 20px;
        background: linear-gradient(135deg, #0d3b66 0%, #1a5490 100%);
        color: white;
        text-align: center;
    }

    .record-icon {
        font-size: 48px;
        margin-bottom: 12px;
        opacity: 0.9;
    }

    .record-header h3 {
        font-size: 24px;
        font-weight: 700;
        margin: 0;
        color: white;
    }

    .record-body {
        padding: 20px;
        flex: 1;
        display: flex;
        flex-direction: column;
    }

    /* Updated Stat Grid to match Roles permissions stat */
    .stat-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 12px;
        margin-bottom: 16px;
    }

    .stat-box {
        text-align: center;
        padding: 12px;
        background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
        border-radius: 12px;
        border: 1px solid #b3e5fc;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
    }

    .stat-value {
        font-size: 15px;
        font-weight: 700;
        color: #0d3b66;
        margin-bottom: 4px;
    }

    .stat-label {
        font-size: 11px;
        color: #6b7280;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }

    .status-badge {
        padding: 4px 12px;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        white-space: nowrap;
        display: inline-block;
    }

    .status-pending {
        background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
        color: white;
        box-shadow: 0 2px 8px rgba(245, 158, 11, 0.3);
    }

    .status-approved {
        background: linear-gradient(135deg, #34d399 0%, #10b981 100%);
        color: white;
        box-shadow: 0 2px 8px rgba(16, 185, 129, 0.3);
    }

    .status-rejected {
        background: linear-gradient(135deg, #f87171 0%, #ef4444 100%);
        color: white;
        box-shadow: 0 2px 8px rgba(239, 68, 68, 0.3);
    }

    /* Matching details section to Roles preview */
    .record-details {
        flex: 1;
        background: #f9fafb;
        border-radius: 10px;
        padding: 16px;
        border: 1px solid #e5e7eb;
    }

    .detail-label {
        font-size: 12px;
        font-weight: 700;
        color: #6b7280;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        margin: 0 0 4px 0;
    }

    .detail-text {
        font-size: 14px;
        color: #1f2937;
        font-weight: 500;
    }

    .record-footer {
        padding: 16px 20px;
        border-top: 1px solid rgba(0, 0, 0, 0.05);
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }

    .btn-action {
        padding: 8px 12px;
        border: none;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s ease;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        flex: 1;
        justify-content: center;
        text-decoration: none;
        min-height: 36px;
    }

    .w-100 {
        width: 100%;
    }

    .btn-view { background: rgba(59, 130, 246, 0.1); color: #3b82f6; }
    .btn-view:hover { background: #3b82f6; color: white; }

    .btn-approve { background: rgba(16, 185, 129, 0.1); color: #10b981; }
    .btn-approve:hover { background: #10b981; color: white; }

    .btn-reject { background: rgba(239, 68, 68, 0.1); color: #ef4444; }
    .btn-reject:hover { background: #ef4444; color: white; }

    .btn-edit { background: rgba(245, 158, 11, 0.1); color: #f59e0b; }
    .btn-edit:hover { background: #f59e0b; color: white; }

    .btn-delete { background: rgba(107, 114, 128, 0.1); color: #6b7280; }
    .btn-delete:hover { background: #6b7280; color: white; }

    .empty-state-full {
        text-align: center;
        padding: 60px 40px;
        color: #9ca3af;
        grid-column: 1 / -1;
    }

    .empty-state-full i {
        font-size: 64px;
        color: #d1d5db;
        margin-bottom: 20px;
        display: block;
    }

    .empty-state-full h3 {
        font-size: 22px;
        font-weight: 700;
        color: #1f2937;
        margin: 0 0 8px 0;
    }

    .empty-state-full p { margin: 0; font-size: 15px; }

    .pagination-wrapper {
        display: flex;
        justify-content: center;
        padding: 20px 0;
    }

    /* Modal Styling */
    .modal-content-custom {
        border: 1px solid rgba(0, 0, 0, 0.08);
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
    }
    .modal-header-custom {
        background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        border-bottom: 1px solid rgba(0, 0, 0, 0.05);
    }
    .modal-header-custom .modal-title {
        color: #0d3b66;
        font-weight: 700;
    }
    .modal-body-custom {
        color: #4b5563;
        font-size: 15px;
    }
    .modal-footer-custom {
        border-top: 1px solid rgba(0, 0, 0, 0.05);
    }

    @media (max-width: 768px) {
        .page-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 16px;
        }
        .records-grid {
            grid-template-columns: 1fr;
        }
        .filter-form {
            grid-template-columns: 1fr;
        }
    }
</style>

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