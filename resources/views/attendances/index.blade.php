@extends('layouts.app')

@section('content')
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
                    <option value="present" {{ request('status') == 'present' ? 'selected' : '' }}>Present</option>
                    <option value="absent" {{ request('status') == 'absent' ? 'selected' : '' }}>Absent</option>
                    <option value="late" {{ request('status') == 'late' ? 'selected' : '' }}>Late</option>
                    <option value="leave" {{ request('status') == 'leave' ? 'selected' : '' }}>Leave</option>
                    <option value="sick" {{ request('status') == 'sick' ? 'selected' : '' }}>Sick</option>
                </select>
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
                    <div class="record-title">
                        <h3>{{ $attendance->user->name ?? 'N/A' }}</h3>
                        <p>{{ $attendance->date->format('M d, Y') }}</p>
                    </div>
                    <span class="status-badge status-{{ $attendance->status }}">
                        {{ ucfirst($attendance->status) }}
                    </span>
                </div>
                <div class="record-body">
                    <div class="time-grid">
                        <div class="time-item">
                            <div class="time-label">
                                <i class="fas fa-sign-in-alt"></i>
                                Check-In
                            </div>
                            <div class="time-value">{{ $attendance->check_in ?? '-' }}</div>
                        </div>
                        <div class="time-item">
                            <div class="time-label">
                                <i class="fas fa-sign-out-alt"></i>
                                Check-Out
                            </div>
                            <div class="time-value">{{ $attendance->check_out ?? '-' }}</div>
                        </div>
                    </div>
                    @if($attendance->notes)
                        <div class="notes-section">
                            <label>Notes:</label>
                            <p>{{ $attendance->notes }}</p>
                        </div>
                    @endif
                </div>
                <div class="record-footer">
                    <a href="{{ route('attendances.show', $attendance) }}" class="btn-action btn-view" title="View">
                        <i class="fas fa-eye"></i> View
                    </a>
                    <a href="{{ route('attendances.edit', $attendance) }}" class="btn-action btn-edit" title="Edit">
                        <i class="fas fa-edit"></i> Edit
                    </a>
                    <form method="POST" action="{{ route('attendances.destroy', $attendance) }}" 
                        style="display:inline;" onsubmit="return confirm('Delete this record?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn-action btn-delete" title="Delete">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    </form>
                </div>
            </div>
        @empty
            <div class="empty-state-full">
                <i class="fas fa-inbox"></i>
                <h3>No attendance records found</h3>
                <p>Create your first attendance record today</p>
            </div>
        @endforelse
    </div>

    @if($attendances->hasPages())
        <div class="pagination-wrapper">
            {{ $attendances->links() }}
        </div>
    @endif
</div>

<style>
    .page-wrapper {
        animation: fadeInUp 0.5s ease-out;
    }

    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
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
        grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
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
        padding: 20px;
        background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 16px;
        border-bottom: 1px solid rgba(0, 0, 0, 0.05);
    }

    .record-title h3 {
        font-size: 18px;
        font-weight: 700;
        color: #0d3b66;
        margin: 0 0 6px 0;
    }

    .record-title p {
        font-size: 13px;
        color: #6b7280;
        margin: 0;
    }

    .status-badge {
        padding: 6px 14px;
        border-radius: 8px;
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        white-space: nowrap;
    }

    .status-present {
        background: linear-gradient(135deg, #34d399 0%, #10b981 100%);
        color: white;
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
    }

    .status-absent {
        background: linear-gradient(135deg, #f87171 0%, #ef4444 100%);
        color: white;
        box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
    }

    .status-late {
        background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
        color: white;
        box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);
    }

    .status-leave {
        background: linear-gradient(135deg, #60a5fa 0%, #3b82f6 100%);
        color: white;
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
    }

    .status-sick {
        background: linear-gradient(135deg, #a78bfa 0%, #8b5cf6 100%);
        color: white;
        box-shadow: 0 4px 12px rgba(139, 92, 246, 0.3);
    }

    .record-body {
        padding: 20px;
        flex: 1;
    }

    .time-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px;
        margin-bottom: 16px;
    }

    .time-item {
        background: linear-gradient(135deg, #f9fafb 0%, #f3f4f6 100%);
        border-radius: 12px;
        padding: 16px;
        text-align: center;
        border: 1px solid #e5e7eb;
    }

    .time-label {
        font-size: 12px;
        color: #6b7280;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        margin-bottom: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
    }

    .time-label i {
        color: #0d3b66;
    }

    .time-value {
        font-size: 18px;
        font-weight: 700;
        color: #0d3b66;
    }

    .notes-section {
        background: linear-gradient(135deg, #fef3c7 0%, #fef1e8 100%);
        border-left: 4px solid #f59e0b;
        border-radius: 8px;
        padding: 12px 16px;
        margin-top: 12px;
    }

    .notes-section label {
        font-size: 12px;
        font-weight: 700;
        color: #92400e;
        display: block;
        margin-bottom: 6px;
    }

    .notes-section p {
        font-size: 14px;
        color: #b45309;
        margin: 0;
        word-break: break-word;
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

    .btn-view {
        background: rgba(59, 130, 246, 0.1);
        color: #3b82f6;
    }

    .btn-view:hover {
        background: #3b82f6;
        color: white;
    }

    .btn-edit {
        background: rgba(245, 158, 11, 0.1);
        color: #f59e0b;
    }

    .btn-edit:hover {
        background: #f59e0b;
        color: white;
    }

    .btn-delete {
        background: rgba(107, 114, 128, 0.1);
        color: #6b7280;
    }

    .btn-delete:hover {
        background: #6b7280;
        color: white;
    }

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

    .empty-state-full p {
        margin: 0;
        font-size: 15px;
    }

    .pagination-wrapper {
        display: flex;
        justify-content: center;
        padding: 20px 0;
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

        .time-grid {
            grid-template-columns: 1fr;
        }
    }
</style>
