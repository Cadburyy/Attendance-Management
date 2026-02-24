@extends('layouts.app')

@section('content')
<div class="page-wrapper">
    <div class="page-header mb-5">
        <div class="header-content">
            <h1 class="page-title">Employees</h1>
            <p class="page-subtitle">Manage employee accounts and roles</p>
        </div>
        @can('user')
            <a href="{{ route('users.create') }}" class="btn btn-create">
                <i class="fas fa-plus"></i> Add Employee
            </a>
        @endcan
    </div>

    @session('success')
        <div class="alert-success-custom">
            <i class="fas fa-check-circle"></i>
            <span>{{ $value }}</span>
        </div>
    @endsession

    @session('error')
        <div class="alert-error-custom">
            <i class="fas fa-exclamation-circle"></i>
            <span>{{ $value }}</span>
        </div>
    @endsession

    <div class="users-grid">
        @forelse($data as $user)
            <div class="user-card">
                <div class="user-avatar">
                    <div class="avatar-circle">
                        {{ substr($user->name, 0, 1) }}
                    </div>
                </div>
                <div class="user-info">
                    <h3>{{ $user->name }}</h3>
                    <p class="user-email">{{ $user->email }}</p>
                </div>
                <div class="user-roles">
                    @if(!empty($user->getRoleNames()))
                        @foreach($user->getRoleNames() as $v)
                            <span class="role-badge">{{ ucfirst($v) }}</span>
                        @endforeach
                    @else
                        <span class="role-badge role-badge-none">No Role</span>
                    @endif
                </div>
                <div class="user-actions">
                    <a href="{{ route('users.show', $user->id) }}" class="btn-action-icon" title="View">
                        <i class="fas fa-eye"></i>
                    </a>
                    @can('user')
                        <a href="{{ route('users.edit', $user->id) }}" class="btn-action-icon" title="Edit">
                            <i class="fas fa-edit"></i>
                        </a>
                        <button type="button" class="btn-action-icon delete-btn" 
                            data-bs-toggle="modal" data-bs-target="#deleteModal" 
                            data-action="{{ route('users.destroy', $user->id) }}" title="Delete">
                            <i class="fas fa-trash"></i>
                        </button>
                    @endcan
                </div>
            </div>
        @empty
            <div class="empty-state-full">
                <i class="fas fa-inbox"></i>
                <h3>No employees found</h3>
                <p>Add your first employee to get started</p>
            </div>
        @endforelse
    </div>

    @if($data->hasPages())
        <div class="pagination-wrapper">
            {{ $data->links() }}
        </div>
    @endif
</div>

<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content modal-content-custom">
            <div class="modal-header modal-header-custom">
                <h5 class="modal-title">Delete Employee</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body modal-body-custom">
                <p>Are you sure you want to delete this employee? This action cannot be undone.</p>
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

    .alert-error-custom {
        background: linear-gradient(135deg, rgba(239, 68, 68, 0.1) 0%, rgba(239, 68, 68, 0.05) 100%);
        border-left: 4px solid #ef4444;
        border-radius: 10px;
        padding: 16px 20px;
        margin-bottom: 24px;
        display: flex;
        align-items: center;
        gap: 12px;
        color: #ef4444;
        font-weight: 500;
        animation: slideIn 0.4s ease-out;
    }

    .alert-success-custom i, 
    .alert-error-custom i {
        font-size: 20px;
    }

    .users-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
        gap: 24px;
        margin-bottom: 32px;
    }

    .user-card {
        background: white;
        border-radius: 16px;
        padding: 24px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        border: 1px solid rgba(0, 0, 0, 0.05);
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
    }

    .user-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 15px 40px rgba(0, 0, 0, 0.12);
    }

    .user-avatar {
        margin-bottom: 16px;
    }

    .avatar-circle {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        background: linear-gradient(135deg, #0d3b66 0%, #1a5490 100%);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 32px;
        font-weight: 700;
        box-shadow: 0 4px 15px rgba(13, 59, 102, 0.3);
    }

    .user-info {
        margin-bottom: 16px;
        flex: 1;
    }

    .user-info h3 {
        font-size: 20px;
        font-weight: 700;
        color: #0d3b66;
        margin: 0 0 6px 0;
    }

    .user-email {
        font-size: 14px;
        color: #6b7280;
        margin: 0;
        word-break: break-word;
    }

    .user-roles {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        justify-content: center;
        margin-bottom: 20px;
        width: 100%;
    }

    .role-badge {
        padding: 6px 14px;
        background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        color: white;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }

    .role-badge-none {
        background: linear-gradient(135deg, #d1d5db 0%, #9ca3af 100%);
    }

    .user-actions {
        display: flex;
        gap: 12px;
        width: 100%;
        justify-content: center;
    }

    .btn-action-icon {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        border: 2px solid #e5e7eb;
        background: white;
        color: #0d3b66;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.3s ease;
        text-decoration: none;
    }

    .btn-action-icon:hover {
        transform: translateY(-4px);
        border-color: #0d3b66;
        background: #0d3b66;
        color: white;
        box-shadow: 0 8px 16px rgba(13, 59, 102, 0.2);
    }

    .btn-action-icon.delete-btn:hover {
        border-color: #ef4444;
        background: #ef4444;
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

        .users-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        var deleteModal = document.getElementById('deleteModal');
        deleteModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            var action = button.getAttribute('data-action');
            deleteModal.querySelector('#delete-form').action = action;
        });
    });
</script>
@endsection
