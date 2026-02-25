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

    .alert-success-custom, .alert-error-custom {
        border-radius: 10px;
        padding: 16px 20px;
        margin-bottom: 24px;
        display: flex;
        align-items: center;
        gap: 12px;
        font-weight: 500;
        animation: slideIn 0.4s ease-out;
    }

    .alert-success-custom {
        background: linear-gradient(135deg, rgba(6, 168, 125, 0.1) 0%, rgba(6, 168, 125, 0.05) 100%);
        border-left: 4px solid #06a77d;
        color: #06a77d;
    }

    .alert-error-custom {
        background: linear-gradient(135deg, rgba(239, 68, 68, 0.1) 0%, rgba(239, 68, 68, 0.05) 100%);
        border-left: 4px solid #ef4444;
        color: #ef4444;
    }

    @keyframes slideIn {
        from { opacity: 0; transform: translateX(-20px); }
        to { opacity: 1; transform: translateX(0); }
    }

    .roles-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
        gap: 24px;
        margin-bottom: 32px;
    }

    .role-card {
        background: white;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        border: 1px solid rgba(0, 0, 0, 0.05);
        display: flex;
        flex-direction: column;
    }

    .role-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 15px 40px rgba(0, 0, 0, 0.12);
    }

    .role-header {
        padding: 24px 20px;
        background: linear-gradient(135deg, #0d3b66 0%, #1a5490 100%);
        color: white;
        text-align: center;
    }

    .role-icon {
        font-size: 48px;
        margin-bottom: 12px;
        opacity: 0.9;
    }

    .role-header h3 {
        font-size: 24px;
        font-weight: 700;
        margin: 0;
        color: white;
    }

    .role-body {
        padding: 20px;
        flex: 1;
        display: flex;
        flex-direction: column;
    }

    .permission-stat {
        text-align: center;
        padding: 16px;
        background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
        border-radius: 12px;
        margin-bottom: 16px;
        border: 1px solid #b3e5fc;
    }

    .stat-number {
        font-size: 32px;
        font-weight: 700;
        color: #0d3b66;
    }

    .stat-label {
        font-size: 13px;
        color: #6b7280;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }

    .permission-preview {
        flex: 1;
    }

    .preview-label {
        font-size: 12px;
        font-weight: 700;
        color: #6b7280;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        margin: 0 0 12px 0;
    }

    .permission-tags {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
    }

    .permission-tag {
        padding: 6px 12px;
        background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        color: white;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 600;
        text-transform: capitalize;
        word-break: break-word;
        max-width: 100%;
    }

    .permission-tag-more {
        background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    }

    .permission-empty {
        color: #9ca3af;
        font-size: 14px;
        text-align: center;
        padding: 16px;
    }

    .role-footer {
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

    .btn-edit { background: rgba(245, 158, 11, 0.1); color: #f59e0b; }
    .btn-edit:hover { background: #f59e0b; color: white; }
    .btn-delete { background: rgba(239, 68, 68, 0.1); color: #ef4444; }
    .btn-delete:hover { background: #ef4444; color: white; }

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

    .modal-content-custom {
        border: none;
        border-radius: 16px;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
    }

    .modal-header-custom {
        background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        border-radius: 16px 16px 0 0;
    }

    .modal-header-custom .modal-title {
        color: #0d3b66;
        font-weight: 700;
    }

    @media (max-width: 768px) {
        .page-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 16px;
        }

        .roles-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="page-wrapper">
    <div class="page-header mb-5">
        <div class="header-content">
            <h1 class="page-title">Roles & Permissions</h1>
            <p class="page-subtitle">Manage user roles and access control</p>
        </div>
        @can('role')
            <a href="{{ route('roles.create') }}" class="btn btn-create">
                <i class="fas fa-plus"></i> Add Role
            </a>
        @endcan
    </div>

    @if(session('success'))
        <div class="alert-success-custom">
            <i class="fas fa-check-circle"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    {{-- Catch ValidationException 'error' and generic session 'error' --}}
    @if($errors->has('error') || session('error'))
        <div class="alert-error-custom">
            <i class="fas fa-exclamation-circle"></i>
            <span>{{ $errors->first('error') ?: session('error') }}</span>
        </div>
    @endif

    <div class="roles-grid">
        @forelse($roles as $role)
            <div class="role-card">
                <div class="role-header">
                    <div class="role-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h3>{{ ucfirst($role->name) }}</h3>
                </div>
                <div class="role-body">
                    @php
                        $permissions = $role->permissions->pluck('name')->toArray();
                        $permissionCount = count($permissions);
                    @endphp
                    <div class="permission-stat">
                        <div class="stat-number">{{ $permissionCount }}</div>
                        <div class="stat-label">{{ $permissionCount === 1 ? 'Permission' : 'Permissions' }}</div>
                    </div>
                    @if($permissionCount > 0)
                        <div class="permission-preview">
                            <p class="preview-label">Key Permissions:</p>
                            <div class="permission-tags">
                                @foreach(array_slice($permissions, 0, 3) as $permission)
                                    <span class="permission-tag">{{ ucfirst($permission) }}</span>
                                @endforeach
                                @if($permissionCount > 3)
                                    <span class="permission-tag permission-tag-more">+{{ $permissionCount - 3 }}</span>
                                @endif
                            </div>
                        </div>
                    @else
                        <div class="permission-empty">
                            <p>No permissions assigned</p>
                        </div>
                    @endif
                </div>
                <div class="role-footer">
                    @can('role')
                        <a href="{{ route('roles.edit', $role->id) }}" class="btn-action btn-edit" title="Edit">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                        <button type="button" class="btn-action btn-delete delete-btn"
                            data-bs-toggle="modal" data-bs-target="#deleteModal"
                            data-action="{{ route('roles.destroy', $role->id) }}" title="Delete">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    @endcan
                </div>
            </div>
        @empty
            <div class="empty-state-full">
                <i class="fas fa-inbox"></i>
                <h3>No roles found</h3>
                <p>Create your first role to get started</p>
            </div>
        @endforelse
    </div>

    @if($roles->hasPages())
        <div class="pagination-wrapper">
            {{ $roles->links() }}
        </div>
    @endif
</div>

<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content modal-content-custom">
            <div class="modal-header modal-header-custom">
                <h5 class="modal-title">Delete Role</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <p>Are you sure you want to delete this role? This action cannot be undone.</p>
            </div>
            <div class="modal-footer border-0">
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

<script>
    document.addEventListener('DOMContentLoaded', function() {
        var deleteModal = document.getElementById('deleteModal');
        if(deleteModal) {
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