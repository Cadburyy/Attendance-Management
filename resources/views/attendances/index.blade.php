@extends('layouts.app')

@section('content')
<style>
    .page-wrapper { animation: fadeInUp 0.5s ease-out; }
    @keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
    .page-header { display: flex; justify-content: space-between; align-items: center; padding-bottom: 20px; }
    .page-title { font-size: 32px; font-weight: 700; color: #0d3b66; margin: 0 0 8px 0; }
    .page-subtitle { color: #6b7280; margin: 0; font-size: 15px; }
    
    .btn-create { display: inline-flex; align-items: center; gap: 8px; padding: 12px 24px; background: linear-gradient(135deg, #0d3b66 0%, #1a5490 100%); color: white; border: none; border-radius: 10px; font-weight: 600; cursor: pointer; transition: all 0.3s ease; box-shadow: 0 4px 15px rgba(13, 59, 102, 0.3); text-decoration: none; }
    .btn-create:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(13, 59, 102, 0.4); color: white; }
    
    .btn-approvals { display: inline-flex; align-items: center; gap: 8px; padding: 12px 24px; background: linear-gradient(135deg, #0d3b66 0%, #1a5490 100%); color: white; border: none; border-radius: 10px; font-weight: 600; cursor: pointer; transition: all 0.3s ease; box-shadow: 0 4px 15px rgba(13, 59, 102, 0.3); text-decoration: none; margin-right: 12px; }
    .btn-approvals:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(13, 59, 102, 0.4); color: white; }
    
    .header-actions { display: flex; align-items: center; }
    .filter-card { background: white; border-radius: 16px; padding: 24px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08); margin-bottom: 32px; border: 1px solid rgba(0, 0, 0, 0.05); }
    
    .filter-form { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; align-items: end; }
    .filter-input { width: 100%; height: 48px; padding: 10px 16px; border: 2px solid #e5e7eb; border-radius: 8px; font-size: 14px; transition: all 0.3s ease; font-family: inherit; box-sizing: border-box; background-color: white; }
    .filter-input:focus { outline: none; border-color: #0d3b66; box-shadow: 0 0 0 3px rgba(13, 59, 102, 0.1); }
    .btn-filter { height: 48px; padding: 0 24px; background: #0d3b66; color: white; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; transition: all 0.3s ease; display: flex; align-items: center; justify-content: center; gap: 8px; box-sizing: border-box; }
    .btn-filter:hover { background: #0a2d52; transform: translateY(-2px); }
    
    .table-container { background: white; border-radius: 16px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08); border: 1px solid rgba(0, 0, 0, 0.05); overflow: hidden; margin-bottom: 32px; overflow-x: auto; }
    .modern-table { width: 100%; border-collapse: separate; border-spacing: 0; text-align: left; }
    .modern-table th { background: #f8fafc; padding: 18px 20px; font-weight: 700; color: #475569; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid #e2e8f0; white-space: nowrap; }
    .modern-table td { padding: 16px 20px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; font-size: 14px; color: #334155; }
    .modern-table tbody tr:hover td { background-color: #f8fafc; transition: background-color 0.2s ease; }
    .modern-table tbody tr:last-child td { border-bottom: none; }
    
    .user-cell { display: flex; align-items: center; gap: 12px; font-weight: 600; color: #0f172a; }
    .user-avatar-small { width: 36px; height: 36px; border-radius: 50%; background: #e0f2fe; color: #0284c7; display: flex; align-items: center; justify-content: center; font-size: 14px; font-weight: 700; flex-shrink: 0; }
    
    .time-badge { display: inline-flex; align-items: center; gap: 6px; background: #f1f5f9; padding: 6px 12px; border-radius: 8px; font-size: 13px; font-weight: 600; color: #475569; }
    .time-badge i { font-size: 12px; }
    
    .status-badge { padding: 6px 16px; border-radius: 50px; font-size: 12px; font-weight: 600; text-transform: capitalize; display: inline-flex; align-items: center; justify-content: center; min-width: 80px; }
    .status-present { background: #dcfce7; color: #166534; }
    .status-absent { background: #fee2e2; color: #991b1b; }
    .status-late { background: #fef3c7; color: #92400e; }
    .status-leave { background: #e0f2fe; color: #075985; }
    .status-sick { background: #f3e8ff; color: #6b21a8; }
    .status-pending { background: #fef3c7; color: #d97706; }
    
    .tag-pending { background: #fef3c7; color: #d97706; padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 600; display: inline-flex; align-items: center; gap: 4px; white-space: nowrap; }
    .tag-approved { background: #dcfce7; color: #059669; padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 600; display: inline-flex; align-items: center; gap: 4px; white-space: nowrap; }
    .tag-rejected { background: #fee2e2; color: #dc2626; padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 600; display: inline-flex; align-items: center; gap: 4px; white-space: nowrap; }
    
    .action-group { display: flex; gap: 8px; align-items: center; }
    .btn-icon { width: 34px; height: 34px; border-radius: 8px; border: none; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.2s ease; text-decoration: none; font-size: 13px; }
    .btn-icon-view { background: #eff6ff; color: #3b82f6; }
    .btn-icon-view:hover { background: #3b82f6; color: white; transform: translateY(-2px); box-shadow: 0 4px 10px rgba(59, 130, 246, 0.2); }
    .btn-icon-edit { background: #fffbeb; color: #f59e0b; }
    .btn-icon-edit:hover { background: #f59e0b; color: white; transform: translateY(-2px); box-shadow: 0 4px 10px rgba(245, 158, 11, 0.2); }
    .btn-icon-delete { background: #fef2f2; color: #ef4444; }
    .btn-icon-delete:hover { background: #ef4444; color: white; transform: translateY(-2px); box-shadow: 0 4px 10px rgba(239, 68, 68, 0.2); }
    .btn-icon-override { background: #f3e8ff; color: #8b5cf6; width: auto; padding: 0 14px; font-weight: 600; gap: 6px; }
    .btn-icon-override:hover { background: #8b5cf6; color: white; transform: translateY(-2px); box-shadow: 0 4px 10px rgba(139, 92, 246, 0.2); }

    .alert-success-custom { background: linear-gradient(135deg, rgba(6, 168, 125, 0.1) 0%, rgba(6, 168, 125, 0.05) 100%); border-left: 4px solid #06a77d; border-radius: 10px; padding: 16px 20px; margin-bottom: 24px; display: flex; align-items: center; gap: 12px; color: #06a77d; font-weight: 500; animation: slideIn 0.4s ease-out; }
    @keyframes slideIn { from { opacity: 0; transform: translateX(-20px); } to { opacity: 1; transform: translateX(0); } }
    .modal-content-custom { border: 1px solid rgba(0, 0, 0, 0.08); box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15); }
    .modal-header-custom { background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%); border-bottom: 1px solid rgba(0, 0, 0, 0.05); }
    
    @media (max-width: 768px) {
        .page-header { flex-direction: column; align-items: flex-start; gap: 16px; }
        
        .header-actions {
            width: 100%;
            display: flex;
            flex-direction: row; 
            gap: 10px;
        }
        
        .btn-approvals, .btn-create {
            flex: 1; 
            margin: 0;
            padding: 10px 8px; 
            font-size: 13px; 
            white-space: nowrap; 
            justify-content: center;
        }

        .filter-form {
            grid-template-columns: repeat(2, 1fr); /* Forces 2 columns for inputs */
            gap: 12px;
        }

        .filter-form .btn-filter {
            grid-column: 1 / -1; /* Forces the filter button to stretch fully across */
        }
        
        /* Adjust padding for small inputs */
        .filter-input {
            padding: 10px;
            font-size: 13px;
        }
    }
</style>

<div class="page-wrapper">
    <div class="page-header mb-5">
        <div class="header-content">
            <h1 class="page-title">{{ auth()->user()->can('override') ? 'Attendance Records' : 'Attendance' }}</h1>
            <p class="page-subtitle">Track and manage attendance data</p>
        </div>
        <div class="header-actions">
            @can('override')
                <a href="{{ route('attendances.approvals') }}" class="btn-approvals">
                    <i class="fas fa-clipboard-list"></i> Pending Requests
                </a>
            @endcan
            @can('attendance')
                <a href="{{ route('attendances.create') }}" class="btn btn-create">
                    <i class="fas fa-plus"></i> Add Request
                </a>
            @endcan
        </div>
    </div>

    @if(session('success'))
        <div class="alert-success-custom">
            <i class="fas fa-check-circle"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    <div class="filter-card">
        <form method="GET" class="filter-form">
            @can('override')
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
            @endcan

            <div class="filter-group">
                <select name="status" class="filter-input">
                    <option value="">All Status</option>
                    @foreach(['present', 'absent', 'late', 'leave', 'sick', 'pending'] as $status)
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

    <div class="table-container">
        <table class="modern-table">
            <thead>
                <tr>
                    <th>Employee</th>
                    <th>Date</th>
                    <th>Check In</th>
                    <th>Check Out</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($attendances as $attendance)
                    <tr>
                        <td>
                            <div class="user-cell">
                                <div class="user-avatar-small">{{ substr($attendance->user->name ?? 'N', 0, 1) }}</div>
                                <span>{{ $attendance->user->name ?? 'N/A' }}</span>
                            </div>
                        </td>
                        <td>
                            <span style="font-weight: 600; color: #475569;">{{ \Carbon\Carbon::parse($attendance->date)->format('M d, Y') }}</span>
                        </td>
                        <td>
                            <span class="time-badge">
                                <i class="fas fa-sign-in-alt text-success"></i> 
                                {{ $attendance->check_in ? \Carbon\Carbon::parse($attendance->check_in)->format('H:i') : '--:--' }}
                            </span>
                        </td>
                        <td>
                            <span class="time-badge">
                                <i class="fas fa-sign-out-alt text-danger"></i> 
                                {{ $attendance->check_out ? \Carbon\Carbon::parse($attendance->check_out)->format('H:i') : '--:--' }}
                            </span>
                        </td>
                        <td>
                            <span class="status-badge status-{{ $attendance->status }}">{{ ucfirst($attendance->status) }}</span>
                        </td>
                        <td>
                            <div class="action-group">
                                <a href="{{ route('attendances.show', $attendance) }}" class="btn-icon btn-icon-view" title="View">
                                    <i class="fas fa-eye"></i>
                                </a>
                                
                                @can('override')
                                    <a href="{{ route('attendances.edit', $attendance) }}" class="btn-icon btn-icon-edit" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button type="button" class="btn-icon btn-icon-delete" 
                                            data-bs-toggle="modal" data-bs-target="#deleteModal" 
                                            data-action="{{ route('attendances.destroy', $attendance) }}" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                @else
                                    <a href="{{ route('attendances.edit', $attendance) }}" class="btn-icon btn-icon-edit" title="Edit Time">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    @if($attendance->override_status !== 'pending' && $attendance->override_status !== 'approved')
                                        <button type="button" class="btn-icon btn-icon-override" 
                                                data-bs-toggle="modal" data-bs-target="#requestOverrideModal" 
                                                data-action="{{ route('attendances.requestOverride', $attendance) }}"
                                                data-status="{{ $attendance->status }}"
                                                data-checkin="{{ $attendance->check_in ? \Carbon\Carbon::parse($attendance->check_in)->format('H:i') : '' }}"
                                                data-checkout="{{ $attendance->check_out ? \Carbon\Carbon::parse($attendance->check_out)->format('H:i') : '' }}">
                                            <i class="fas fa-paper-plane"></i> Request
                                        </button>
                                    @endif
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center" style="padding: 60px 20px; text-align: center;">
                            <i class="fas fa-calendar-times" style="font-size: 48px; color: #cbd5e1; margin-bottom: 16px; display: block;"></i>
                            <h4 style="color: #475569; margin-bottom: 8px;">No records found</h4>
                            <p style="color: #94a3b8; margin: 0;">Try adjusting your filters or add a new record.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($attendances->hasPages())
        <div class="pagination-wrapper d-flex justify-content-center">
            {{ $attendances->links() }}
        </div>
    @endif
</div>

@can('override')
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
@endcan

@if(auth()->user()->cannot('override'))
<div class="modal fade" id="requestOverrideModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content modal-content-custom">
            <div class="modal-header modal-header-custom">
                <h5 class="modal-title font-weight-bold" style="color: #8b5cf6;"><i class="fas fa-paper-plane"></i> Request Status Change</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="request-override-form" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <p class="text-muted mb-3" style="font-size: 14px;">Submit a request to HR to change your attendance status.</p>
                    
                    <div class="form-group mb-3">
                        <label class="form-label" style="font-size: 13px; font-weight: 700; color: #1f2937; margin-bottom: 6px;">Requested Status <span style="color: #ef4444;">*</span></label>
                        <select class="form-control" style="border-radius: 8px; padding: 10px; border: 2px solid #e5e7eb;" id="request-status" name="requested_status" required>
                            <option value="present">Present</option>
                            <option value="absent">Absent</option>
                            <option value="late">Late</option>
                            <option value="leave">Leave</option>
                            <option value="sick">Sick</option>
                        </select>
                    </div>

                    <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 16px;">
                        <div class="form-group">
                            <label class="form-label" style="font-size: 13px; font-weight: 700; color: #1f2937; margin-bottom: 6px;">Requested Check-In</label>
                            <input type="time" class="form-control" style="border-radius: 8px; padding: 10px; border: 2px solid #e5e7eb;" id="request-check-in" name="requested_check_in">
                        </div>
                        <div class="form-group">
                            <label class="form-label" style="font-size: 13px; font-weight: 700; color: #1f2937; margin-bottom: 6px;">Requested Check-Out</label>
                            <input type="time" class="form-control" style="border-radius: 8px; padding: 10px; border: 2px solid #e5e7eb;" id="request-check-out" name="requested_check_out">
                        </div>
                    </div>

                    <div class="form-group mb-0">
                        <label class="form-label" style="font-size: 13px; font-weight: 700; color: #1f2937; margin-bottom: 6px;">Reason / Details <span style="color: #ef4444;">*</span></label>
                        <textarea class="form-control" style="border-radius: 8px; padding: 10px; border: 2px solid #e5e7eb; resize: vertical;" name="override_reason" rows="3" placeholder="Explain why you need this change..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary" style="border-radius: 8px; padding: 8px 16px;" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn text-white" style="background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%); border-radius: 8px; padding: 8px 16px; border: none;">Submit Request</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

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

        var requestOverrideModal = document.getElementById('requestOverrideModal');
        if (requestOverrideModal) {
            requestOverrideModal.addEventListener('show.bs.modal', function (event) {
                var button = event.relatedTarget;
                var action = button.getAttribute('data-action');
                var currentStatus = button.getAttribute('data-status');
                var currentCheckIn = button.getAttribute('data-checkin');
                var currentCheckOut = button.getAttribute('data-checkout');
                
                var form = requestOverrideModal.querySelector('#request-override-form');
                var statusSelect = requestOverrideModal.querySelector('#request-status');
                var checkInInput = requestOverrideModal.querySelector('#request-check-in');
                var checkOutInput = requestOverrideModal.querySelector('#request-check-out');
                
                form.action = action;
                statusSelect.value = currentStatus;
                checkInInput.value = currentCheckIn;
                checkOutInput.value = currentCheckOut;
            });
        }
    });
</script>
@endsection