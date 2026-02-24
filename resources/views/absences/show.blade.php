@extends('layouts.app')

@section('content')
<div class="detail-wrapper">
    <div class="detail-container">
        <div class="detail-header">
            <div>
                <h1 class="detail-title">Absence Request Details</h1>
                <p class="detail-subtitle">View absence request information</p>
            </div>
            <span class="status-badge status-{{ $absence->status }}">{{ ucfirst($absence->status) }}</span>
        </div>

        <div class="detail-card">
            <div class="detail-row">
                <div class="detail-item">
                    <label class="detail-label">Employee Name</label>
                    <p class="detail-value">{{ $absence->employee_name }}</p>
                </div>
                <div class="detail-item">
                    <label class="detail-label">Date</label>
                    <p class="detail-value">{{ $absence->date->format('M d, Y') }}</p>
                </div>
            </div>

            <div class="detail-row">
                <div class="detail-item">
                    <label class="detail-label">Reason</label>
                    <p class="detail-value">{{ ucfirst($absence->reason) }}</p>
                </div>
                <div class="detail-item">
                    <label class="detail-label">Status</label>
                    <div>
                        <span class="status-badge-inline status-{{ $absence->status }}">{{ ucfirst($absence->status) }}</span>
                    </div>
                </div>
            </div>

            @if($absence->details)
                <div class="detail-full">
                    <label class="detail-label">Details</label>
                    <p class="detail-value detail-text">{{ $absence->details }}</p>
                </div>
            @endif
        </div>

        <div class="detail-actions">
            <a href="{{ route('absences.edit', $absence) }}" class="btn btn-edit">
                <i class="fas fa-edit"></i> Edit
            </a>
            @if($absence->status == 'pending')
                <form method="POST" action="{{ route('absences.approve', $absence) }}" style="display:inline;">
                    @csrf
                    <button type="submit" class="btn btn-approve">
                        <i class="fas fa-check"></i> Approve
                    </button>
                </form>
                <form method="POST" action="{{ route('absences.reject', $absence) }}" style="display:inline;">
                    @csrf
                    <button type="submit" class="btn btn-reject">
                        <i class="fas fa-times"></i> Reject
                    </button>
                </form>
            @endif
            <form method="POST" action="{{ route('absences.destroy', $absence) }}" style="display:inline;" onsubmit="return confirm('Delete this record?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-delete">
                    <i class="fas fa-trash"></i> Delete
                </button>
            </form>
            <a href="{{ route('absences.index') }}" class="btn btn-back">
                <i class="fas fa-arrow-left"></i> Back
            </a>
        </div>
    </div>
</div>

<style>
    .detail-wrapper {
        min-height: 100vh;
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

    .detail-container {
        max-width: 700px;
        background: white;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
        border: 1px solid rgba(0, 0, 0, 0.05);
    }

    .detail-header {
        background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        padding: 32px;
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        border-bottom: 1px solid rgba(0, 0, 0, 0.05);
    }

    .detail-title {
        font-size: 28px;
        font-weight: 700;
        color: #0d3b66;
        margin: 0 0 8px 0;
    }

    .detail-subtitle {
        font-size: 15px;
        color: #6b7280;
        margin: 0;
    }

    .status-badge {
        padding: 8px 16px;
        border-radius: 10px;
        font-size: 13px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        white-space: nowrap;
    }

    .status-pending {
        background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
        color: white;
    }

    .status-approved {
        background: linear-gradient(135deg, #34d399 0%, #10b981 100%);
        color: white;
    }

    .status-rejected {
        background: linear-gradient(135deg, #f87171 0%, #ef4444 100%);
        color: white;
    }

    .detail-card {
        padding: 40px 32px;
    }

    .detail-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 32px;
        margin-bottom: 28px;
    }

    .detail-full {
        margin-bottom: 28px;
    }

    .detail-item {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .detail-label {
        font-size: 13px;
        font-weight: 700;
        color: #6b7280;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }

    .detail-value {
        font-size: 16px;
        color: #1f2937;
        margin: 0;
        font-weight: 500;
    }

    .detail-text {
        line-height: 1.6;
        white-space: pre-wrap;
        word-break: break-word;
    }

    .status-badge-inline {
        padding: 6px 12px;
        border-radius: 8px;
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        display: inline-block;
    }

    .detail-actions {
        padding: 16px 32px 32px 32px;
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
        border-top: 1px solid rgba(0, 0, 0, 0.05);
    }

    .btn {
        padding: 10px 18px;
        border: none;
        border-radius: 8px;
        font-weight: 700;
        font-size: 13px;
        cursor: pointer;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        text-decoration: none;
    }

    .btn-edit {
        background: rgba(245, 158, 11, 0.1);
        color: #f59e0b;
    }

    .btn-edit:hover {
        background: #f59e0b;
        color: white;
    }

    .btn-approve {
        background: rgba(16, 185, 129, 0.1);
        color: #10b981;
    }

    .btn-approve:hover {
        background: #10b981;
        color: white;
    }

    .btn-reject {
        background: rgba(239, 68, 68, 0.1);
        color: #ef4444;
    }

    .btn-reject:hover {
        background: #ef4444;
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

    .btn-back {
        background: linear-gradient(135deg, #0d3b66 0%, #1a5490 100%);
        color: white;
        margin-left: auto;
    }

    .btn-back:hover {
        transform: translateY(-2px);
    }

    @media (max-width: 768px) {
        .detail-header {
            flex-direction: column;
            gap: 16px;
            padding: 24px;
        }

        .detail-card {
            padding: 24px;
        }

        .detail-row {
            grid-template-columns: 1fr;
            gap: 20px;
        }

        .detail-actions {
            flex-direction: column;
            padding: 16px 24px 24px 24px;
        }

        .btn {
            width: 100%;
            justify-content: center;
            flex: none;
        }

        .btn-back {
            margin-left: 0;
        }
    }
</style>
@endsection

