@extends('layouts.app')

@section('content')
<style>
    .table-striped-custom tbody tr:nth-of-type(odd) {
        background-color: #f9f9f9;
    }
    .table-hover-custom tbody tr:hover {
        background-color: #f1f1f1 !important;
    }
    .card-header-custom {
        background-color: #ffffff;
        border-bottom: 1px solid #e9ecef;
    }
    body, html {
        overflow-x: hidden;
        width: 100%;
    }
    .dropdown-menu .dropdown-item {
        transition: background-color 0.3s, color 0.3s;
        border-radius: 6px;
    }
    .dropdown-menu .dropdown-item:hover {
        background-color: #0d6efd;
        color: #fff;
    }
    .dropdown-menu .dropdown-item:hover i {
        color: #fff !important;
    }
    .table-responsive {
    overflow: visible !important;
    }
    .dropdown-cell {
        position: relative;
        overflow: visible !important;
    }
    .dropdown-menu {
        position: absolute !important;
        z-index: 1050;
        background-color: #fff !important;
    }
</style>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Role Management</h2>
        @can('role')
            <a class="btn btn-primary" href="{{ route('roles.create') }}">
                <i class="fa fa-plus me-2"></i> Create New Role
            </a>
        @endcan
    </div>

    @if(session('success'))
        <div class="alert alert-success rounded-3 shadow-sm">
            {{ session('success') }}
        </div>
    @endif

    <div class="card shadow-sm mb-4">
        <div class="card-header card-header-custom">
            <h5 class="mb-0">Roles List</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped-custom table-hover-custom mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th width="100px">No</th>
                            <th>Name</th>
                            <th width="100px">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($roles as $key => $role)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $role->name }}</td>
                            <td>
                                <div class="dropdown">
                                    <button class="btn btn-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="fa-solid fa-ellipsis-h"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li>
                                            <a class="dropdown-item" href="{{ route('roles.show', $role->id) }}">
                                                <i class="fa-solid fa-eye me-2 text-info"></i> View
                                            </a>
                                        </li>
                                        @can('role')
                                        <li>
                                            <a class="dropdown-item" href="{{ route('roles.edit', $role->id) }}">
                                                <i class="fa-solid fa-pen-to-square me-2 text-primary"></i> Edit
                                            </a>
                                        </li>
                                        @endcan
                                        @can('role')
                                        <li>
                                            <button class="dropdown-item text-danger" data-bs-toggle="modal" data-bs-target="#deleteModal" data-action="{{ route('roles.destroy', $role->id) }}">
                                                <i class="fa-solid fa-trash me-2"></i> Delete
                                            </button>
                                        </li>
                                        @endcan
                                    </ul>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-4">
        {!! $roles->links('pagination::bootstrap-5') !!}
    </div>
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <a class="btn btn-secondary" href="{{ route('settings.index') }}">
            <i class="fa fa-arrow-left me-2"></i> Back
        </a>
    </div>
</div>

<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">Confirm Deletion</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to delete this role?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form id="delete-form" method="POST" action="">
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
        deleteModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            var action = button.getAttribute('data-action');
            var form = deleteModal.querySelector('#delete-form');
            form.action = action;
        });
    });
</script>
@endsection