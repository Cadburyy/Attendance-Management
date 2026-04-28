@extends('layouts.app')

@section('content')
<style>
    .form-wrapper { min-height: calc(100vh - 100px); display: flex; align-items: center; justify-content: center; padding: 20px 0; animation: fadeInUp 0.5s ease-out; }
    @keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
    
    .form-container { max-width: 680px; width: 100%; background: white; border-radius: 16px; overflow: hidden; box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1); border: 1px solid rgba(0, 0, 0, 0.05); }
    
    .form-header { background: linear-gradient(135deg, #0d3b66 0%, #1a5490 100%); color: white; padding: 24px 32px; display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; }
    .form-title { font-size: 24px; font-weight: 700; margin: 0 0 4px 0; }
    .form-subtitle { font-size: 14px; opacity: 0.9; margin: 0; }
    
    .status-badge { padding: 8px 16px; border-radius: 10px; font-size: 13px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.3px; white-space: nowrap; background: rgba(255, 255, 255, 0.15); border: 1px solid rgba(255, 255, 255, 0.3); backdrop-filter: blur(4px); }

    .modern-form { display: flex; flex-direction: column; gap: 16px; padding: 24px 32px; }
    
    .form-group { display: flex; flex-direction: column; gap: 6px; }
    .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
    
    .form-label { font-size: 13px; font-weight: 700; color: #1f2937; text-transform: uppercase; letter-spacing: 0.3px; display: flex; align-items: center; gap: 4px; }
    
    /* Input Styling to mimic Create but locked as Read-Only */
    .form-input { width: 100%; height: 46px; padding: 10px 16px; border: 2px solid #e5e7eb; border-radius: 10px; font-size: 14px; font-family: inherit; background: #f9fafb; box-sizing: border-box; color: #1f2937; cursor: default; transition: all 0.3s ease; }
    .form-input:focus { outline: none; border-color: #e5e7eb; } 
    .form-input.textarea { height: auto; min-height: 80px; resize: none; padding: 12px 16px; line-height: 1.5; }
    
    .override-badge { padding: 6px 14px; border-radius: 8px; font-size: 12px; font-weight: 700; text-transform: uppercase; margin-bottom: 8px; display: inline-flex; align-items: center; gap: 6px; color: white; width: fit-content; }
    .override-approved { background: linear-gradient(135deg, #10b981 0%, #059669 100%); box-shadow: 0 4px 10px rgba(16, 185, 129, 0.2); }
    .override-pending { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); box-shadow: 0 4px 10px rgba(245, 158, 11, 0.2); }
    .override-rejected { background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); box-shadow: 0 4px 10px rgba(239, 68, 68, 0.2); }

    .form-actions { display: flex; gap: 12px; flex-wrap: wrap; border-top: 1px solid rgba(0, 0, 0, 0.05); padding-top: 24px; margin-top: 8px; }
    .btn { padding: 10px 20px; border: none; border-radius: 8px; font-weight: 700; font-size: 14px; cursor: pointer; transition: all 0.3s ease; display: inline-flex; align-items: center; justify-content: center; gap: 8px; text-decoration: none; box-sizing: border-box; }
    .btn-edit { background: rgba(245, 158, 11, 0.1); color: #f59e0b; }
    .btn-edit:hover { background: #f59e0b; color: white; transform: translateY(-2px); }
    .btn-delete { background: rgba(239, 68, 68, 0.1); color: #ef4444; }
    .btn-delete:hover { background: #ef4444; color: white; transform: translateY(-2px); }
    .btn-back { background: linear-gradient(135deg, #0d3b66 0%, #1a5490 100%); color: white; margin-left: auto; box-shadow: 0 4px 15px rgba(13, 59, 102, 0.2); }
    .btn-back:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(13, 59, 102, 0.3); }

    @media (max-width: 768px) {
        .form-header { flex-direction: column; gap: 16px; padding: 24px 20px; }
        .modern-form { padding: 24px 20px; }
        .form-row { grid-template-columns: 1fr; gap: 16px; }
        .form-actions { flex-direction: column; }
        .btn { width: 100%; }
        .btn-back { margin-left: 0; }
    }
</style>

<div class="form-wrapper">
    <div class="form-container">
        <div class="form-header">
            <div>
                <h1 class="form-title">Attendance Details</h1>
                <p class="form-subtitle">View attendance information</p>
            </div>
            <span class="status-badge">
                <i class="fas fa-circle" style="font-size: 10px; margin-right: 4px;"></i> {{ ucfirst($attendance->status) }}
            </span>
        </div>

        <div class="modern-form">
            @if($attendance->override_status == 'approved')
                <div class="override-badge override-approved"><i class="fas fa-check-circle"></i> Status Overridden</div>
            @elseif($attendance->override_status == 'pending')
                <div class="override-badge override-pending"><i class="fas fa-clock"></i> Request Pending Approval</div>
            @elseif($attendance->override_status == 'rejected')
                <div class="override-badge override-rejected"><i class="fas fa-times-circle"></i> Request Denied</div>
            @endif

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Employee</label>
                    <input type="text" class="form-input" value="{{ $attendance->user->name }}" readonly>
                </div>
                <div class="form-group">
                    <label class="form-label">Date</label>
                    <input type="text" class="form-input" value="{{ \Carbon\Carbon::parse($attendance->date)->format('M d, Y') }}" readonly>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Check-In</label>
                    <input type="text" class="form-input" value="{{ $attendance->check_in ?? 'Not recorded' }}" readonly>
                </div>
                <div class="form-group">
                    <label class="form-label">Check-Out</label>
                    <input type="text" class="form-input" value="{{ $attendance->check_out ?? 'Not recorded' }}" readonly>
                </div>
            </div>

            @if($attendance->override_status)
                <div class="form-group" style="margin-top: 12px;">
                    <label class="form-label" style="color: #8b5cf6;"><i class="fas fa-clipboard-list"></i> Override Request Details</label>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Requested Status</label>
                        <input type="text" class="form-input" value="{{ ucfirst($attendance->requested_status) }}" readonly>
                    </div>
                </div>

                @if($attendance->requested_check_in || $attendance->requested_check_out)
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Requested Check-In</label>
                            <input type="text" class="form-input" value="{{ $attendance->requested_check_in ? \Carbon\Carbon::parse($attendance->requested_check_in)->format('H:i') : '--:--' }}" readonly>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Requested Check-Out</label>
                            <input type="text" class="form-input" value="{{ $attendance->requested_check_out ? \Carbon\Carbon::parse($attendance->requested_check_out)->format('H:i') : '--:--' }}" readonly>
                        </div>
                    </div>
                @endif
                
                <div class="form-group">
                    <label class="form-label">Reason</label>
                    <textarea class="form-input textarea" readonly>{{ $attendance->override_reason }}</textarea>
                </div>
            @endif

            @if($attendance->notes)
                <div class="form-group">
                    <label class="form-label">Notes</label>
                    <textarea class="form-input textarea" readonly>{{ $attendance->notes }}</textarea>
                </div>
            @endif

            @if($attendance->image)
                <div class="form-group mt-3">
                    <label class="form-label"><i class="fas fa-camera"></i> Captured Photo (AI Verification)</label>
                    <div class="photo-preview-container" style="margin-top: 10px;">
                        <img src="{{ asset('storage/' . $attendance->image) }}" alt="Attendance Photo" 
                             style="width: 100%; max-width: 400px; border-radius: 12px; border: 3px solid #0d3b66; box-shadow: 0 4px 15px rgba(0,0,0,0.1);">
                    </div>
                </div>
            @endif

            <div class="form-actions">
                @can('override')
                    <a href="{{ route('attendances.edit', $attendance) }}" class="btn btn-edit">
                        <i class="fas fa-edit"></i> Edit
                    </a>
                    <form method="POST" action="{{ route('attendances.destroy', $attendance) }}" style="display:inline;" onsubmit="return confirm('Delete this record?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-delete">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    </form>
                @endcan
                <a href="{{ route('attendances.index') }}" class="btn btn-back">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
            </div>
        </div>
    </div>
</div>
@endsection