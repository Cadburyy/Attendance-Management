@extends('layouts.app')

@section('content')
<style>
    .page-wrapper { animation: fadeInUp 0.5s ease-out; }
    @keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
    .page-header { display: flex; justify-content: space-between; align-items: center; padding-bottom: 20px; border-bottom: 1px solid rgba(0,0,0,0.05); margin-bottom: 30px; }
    .page-title { font-size: 32px; font-weight: 700; color: #0d3b66; margin: 0 0 8px 0; display: flex; align-items: center; gap: 12px; }
    .page-subtitle { color: #6b7280; margin: 0; font-size: 15px; }
    .btn-back { padding: 10px 20px; background: #f3f4f6; color: #4b5563; border: none; border-radius: 10px; font-weight: 600; cursor: pointer; transition: all 0.3s ease; text-decoration: none; }
    .btn-back:hover { background: #e5e7eb; color: #1f2937; }
    
    .approval-card { background: white; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08); border: 1px solid rgba(0, 0, 0, 0.05); margin-bottom: 20px; display: flex; align-items: center; padding: 20px; transition: transform 0.3s ease; }
    .approval-card:hover { transform: translateY(-3px); box-shadow: 0 8px 30px rgba(0, 0, 0, 0.1); }
    .user-info { flex: 1; display: flex; align-items: center; gap: 20px; border-right: 1px solid #e5e7eb; padding-right: 20px; }
    .user-avatar { width: 50px; height: 50px; background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 20px; color: #0ea5e9; }
    .user-details h4 { margin: 0 0 4px 0; font-size: 18px; font-weight: 700; color: #1f2937; }
    .user-details p { margin: 0; font-size: 13px; color: #6b7280; }
    
    .request-info { flex: 2; padding: 0 20px; display: flex; flex-direction: column; gap: 10px; border-right: 1px solid #e5e7eb; }
    .status-change { display: flex; align-items: center; gap: 12px; font-size: 14px; font-weight: 600; }
    .old-status { background: #f3f4f6; color: #6b7280; padding: 4px 10px; border-radius: 6px; text-transform: uppercase; font-size: 11px; }
    .new-status { background: #0d3b66; color: white; padding: 4px 10px; border-radius: 6px; text-transform: uppercase; font-size: 11px; }
    .reason-text { font-size: 14px; color: #4b5563; background: #f9fafb; padding: 12px; border-radius: 8px; border: 1px solid #e5e7eb; margin: 0; }
    
    .actions { flex: 1; display: flex; gap: 10px; padding-left: 20px; justify-content: center; align-items: center; }
    .btn-action { padding: 10px 16px; border: none; border-radius: 8px; font-weight: 600; font-size: 13px; cursor: pointer; transition: all 0.2s ease; display: inline-flex; align-items: center; gap: 6px; }
    .btn-approve { background: #10b981; color: white; }
    .btn-approve:hover { background: #059669; transform: translateY(-2px); box-shadow: 0 4px 10px rgba(16, 185, 129, 0.3); }
    .btn-reject { background: #ef4444; color: white; }
    .btn-reject:hover { background: #dc2626; transform: translateY(-2px); box-shadow: 0 4px 10px rgba(239, 68, 68, 0.3); }

    .alert-success-custom { background: linear-gradient(135deg, rgba(6, 168, 125, 0.1) 0%, rgba(6, 168, 125, 0.05) 100%); border-left: 4px solid #06a77d; border-radius: 10px; padding: 16px 20px; margin-bottom: 24px; display: flex; align-items: center; gap: 12px; color: #06a77d; font-weight: 500; animation: slideIn 0.4s ease-out; }
    @keyframes slideIn { from { opacity: 0; transform: translateX(-20px); } to { opacity: 1; transform: translateX(0); } }

    @media (max-width: 992px) {
        .approval-card { flex-direction: column; align-items: stretch; gap: 20px; }
        .user-info, .request-info { border-right: none; border-bottom: 1px solid #e5e7eb; padding-right: 0; padding-bottom: 20px; }
        .request-info { padding-left: 0; }
        .actions { padding-left: 0; justify-content: flex-start; }
    }
</style>

<div class="page-wrapper">
    <div class="page-header">
        <div>
            <h1 class="page-title"><i class="fas fa-clipboard-list"></i> Pending Approvals</h1>
            <p class="page-subtitle">Review attendance override requests from employees</p>
        </div>
        <a href="{{ route('attendances.index') }}" class="btn-back">
            <i class="fas fa-arrow-left"></i> Back to Attendances
        </a>
    </div>

    @if(session('success'))
        <div class="alert-success-custom">
            <i class="fas fa-check-circle"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    <div class="approvals-list">
        @forelse($pendingRequests as $request)
            <div class="approval-card">
                <div class="user-info">
                    <div class="user-avatar"><i class="fas fa-user"></i></div>
                    <div class="user-details">
                        <h4>{{ $request->user->name }}</h4>
                        <p><i class="far fa-calendar-alt"></i> {{ \Carbon\Carbon::parse($request->date)->format('M d, Y') }}</p>
                    </div>
                </div>

                <div class="request-info">
                    <div class="status-change">
                        Change from 
                        <span class="old-status">{{ $request->status === 'pending' ? 'New Request' : $request->status }}</span> 
                        <i class="fas fa-arrow-right text-muted"></i> 
                        <span class="new-status">{{ $request->requested_status }}</span>
                    </div>
                    @if($request->requested_check_in || $request->requested_check_out)
                    <div style="font-size: 13px; color: #4b5563; margin-top: 8px;">
                        <i class="fas fa-clock" style="color: #6b7280; width: 14px;"></i> Time Request: 
                        <strong>{{ $request->requested_check_in ? \Carbon\Carbon::parse($request->requested_check_in)->format('H:i') : '--:--' }}</strong> 
                        to 
                        <strong>{{ $request->requested_check_out ? \Carbon\Carbon::parse($request->requested_check_out)->format('H:i') : '--:--' }}</strong>
                    </div>
                    @endif
                    <p class="reason-text mt-2"><strong>Reason:</strong> {{ $request->override_reason }}</p>
                </div>

                <div class="actions">
                    <form action="{{ route('attendances.approve', $request) }}" method="POST" class="m-0">
                        @csrf
                        <button type="submit" class="btn-action btn-approve" onclick="return confirm('Accept this request? It will be officially added to the main attendance list.')">
                            <i class="fas fa-check"></i> Accept
                        </button>
                    </form>
                    
                    <form action="{{ route('attendances.reject', $request) }}" method="POST" class="m-0">
                        @csrf
                        <button type="submit" class="btn-action btn-reject" onclick="return confirm('Decline this request? If this is a completely new request, it will be deleted permanently.')">
                            <i class="fas fa-times"></i> Decline
                        </button>
                    </form>
                </div>
            </div>
        @empty
            <div class="empty-state-full" style="text-align: center; padding: 60px; background: white; border-radius: 16px; border: 1px dashed #d1d5db;">
                <i class="fas fa-check-circle" style="font-size: 64px; color: #10b981; margin-bottom: 20px; display: block; opacity: 0.5;"></i>
                <h3 style="color: #4b5563;">All Caught Up!</h3>
                <p style="color: #6b7280;">There are no pending override requests at the moment.</p>
            </div>
        @endforelse
    </div>

    @if($pendingRequests->hasPages())
        <div class="pagination-wrapper d-flex justify-content-center mt-4">
            {{ $pendingRequests->links() }}
        </div>
    @endif
</div>
@endsection