@extends('layouts.app')

@section('content')
<style>
    .detail-wrapper { min-height: 100vh; animation: fadeInUp 0.5s ease-out; }
    .detail-container { max-width: 700px; background: white; border-radius: 16px; overflow: hidden; box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1); border: 1px solid rgba(0, 0, 0, 0.05); }
    .detail-header { background: linear-gradient(135deg, #0d3b66 0%, #1a5490 100%); color: white; padding: 40px 32px; display: flex; justify-content: space-between; align-items: center; }
    .header-info { flex: 1; }
    .detail-header h1 { font-size: 28px; font-weight: 700; margin: 0 0 8px 0; }
    .detail-header p { font-size: 15px; opacity: 0.9; margin: 0; }
    .user-avatar { width: 80px; height: 80px; border-radius: 50%; background: rgba(255, 255, 255, 0.2); border: 3px solid white; display: flex; align-items: center; justify-content: center; font-size: 32px; font-weight: 700; flex-shrink: 0; margin-left: 20px; }
    .detail-content { padding: 40px 32px; }
    .detail-row { margin-bottom: 32px; }
    .detail-row:last-child { margin-bottom: 0; }
    .detail-label { font-size: 12px; font-weight: 700; color: #6b7280; text-transform: uppercase; letter-spacing: 0.4px; margin-bottom: 8px; }
    .detail-value { font-size: 16px; color: #1f2937; font-weight: 500; margin: 0; }
    .role-badges { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 12px; }
    .role-badge { background: linear-gradient(135deg, #0d3b66 0%, #1a5490 100%); color: white; padding: 6px 14px; border-radius: 20px; font-size: 13px; font-weight: 600; }
    .no-roles { color: #9ca3af; font-style: italic; }
    .action-buttons { display: flex; gap: 12px; margin-top: 40px; padding-top: 32px; border-top: 1px solid #e5e7eb; }
    .btn { padding: 12px 24px; border: none; border-radius: 10px; font-weight: 700; font-size: 14px; cursor: pointer; transition: all 0.3s ease; display: flex; align-items: center; justify-content: center; gap: 8px; text-decoration: none; flex: 1; }
    .btn-edit { background: linear-gradient(135deg, #0d3b66 0%, #1a5490 100%); color: white; box-shadow: 0 4px 15px rgba(13, 59, 102, 0.3); }
    .btn-edit:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(13, 59, 102, 0.4); }
    .btn-delete { background: #fecaca; color: #991b1b; border: 2px solid #ef4444; }
    .btn-delete:hover { background: #fca5a5; }
    .btn-back { background: #f3f4f6; color: #6b7280; border: 1px solid #e5e7eb; }
    .btn-back:hover { background: #e5e7eb; }
    @media (max-width: 768px) { .detail-header { flex-direction: column; text-align: center; } .user-avatar { margin-left: 0; margin-top: 20px; } .detail-content { padding: 30px 20px; } .action-buttons { flex-direction: column; } .btn { width: 100%; } }
</style>

<div class="detail-wrapper">
    <div class="detail-container">
        <div class="detail-header">
            <div class="header-info">
                <h1>{{ $user->name }}</h1>
                <p>Employee Profile</p>
            </div>
            <div class="user-avatar">
                {{ strtoupper(substr($user->name, 0, 1)) }}
            </div>
        </div>

        <div class="detail-content">
            <div class="detail-row">
                <div class="detail-label">Email Address</div>
                <p class="detail-value">{{ $user->email }}</p>
            </div>

            <div class="detail-row">
                <div class="detail-label">Assigned Role(s)</div>
                @if($user->getRoleNames()->isNotEmpty())
                    <div class="role-badges">
                        @foreach ($user->getRoleNames() as $role)
                            <span class="role-badge">{{ $role }}</span>
                        @endforeach
                    </div>
                @else
                    <p class="detail-value no-roles">No roles assigned</p>
                @endif
            </div>

            <div class="action-buttons">
                <a href="{{ route('users.edit', $user->id) }}" class="btn btn-edit">
                    <i class="fas fa-edit"></i> Edit
                </a>
                <form method="POST" action="{{ route('users.destroy', $user->id) }}" style="flex: 1;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-delete" onclick="return confirm('Are you sure you want to delete this user?');">
                        <i class="fas fa-trash"></i> Delete
                    </button>
                </form>
                <a href="{{ route('users.index') }}" class="btn btn-back">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
            </div>
        </div>
    </div>
</div>
@endsection