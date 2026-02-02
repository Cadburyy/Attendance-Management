@extends('layouts.app')

@section('content')
<style>
    .table-striped-custom tbody tr:nth-of-type(odd) { background-color: #f9f9f9; }
    .table-hover-custom tbody tr:hover { background-color: #f1f1f1 !important; }
    .card-header-custom { background-color: #ffffff; border-bottom: 1px solid #e9ecef; }
    body, html { overflow-x: hidden; width: 100%; }
    .dropdown-menu { background-color: #fff !important; }
    .dropdown-menu .dropdown-item { transition: background-color 0.3s, color 0.3s; border-radius: 6px; }
    .dropdown-menu .dropdown-item:hover { background-color: #0d6efd; color: #fff; }
    .dropdown-menu .dropdown-item:hover i { color: #fff !important; }
    .table-responsive { overflow-x: auto !important; }
    .dropdown-menu { position: absolute !important; z-index: 1050; }
</style>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">Users Management</h2>
        @can('user')
            <a class="btn btn-primary" href="{{ route('users.create') }}">
                <i class="fa fa-plus me-2"></i> Create New User
            </a>
        @endcan
    </div>

    @session('success') <div class="alert alert-success rounded-3 shadow-sm" role="alert">{{ $value }}</div> @endsession
    @session('error') <div class="alert alert-danger rounded-3 shadow-sm" role="alert">{{ $value }}</div> @endsession

    <div class="card shadow-sm mb-4">
        <div class="card-header card-header-custom">
            <h5 class="mb-0">Users List</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped-custom table-hover-custom mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th width="100px" class="text-center">No.</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Roles</th>
                            <th width="100px" class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($data as $user)
                        <tr>
                            <td class="text-center">{{ ($data->currentPage() - 1) * $data->perPage() + $loop->iteration }}</td>
                            <td>{{ $user->name }}</td>
                            <td>{{ $user->email }}</td>
                            <td>
                                <div class="d-flex flex-wrap gap-2">
                                    @if(!empty($user->getRoleNames()))
                                        @foreach($user->getRoleNames() as $v)
                                            <span class="badge bg-primary rounded-pill">{{ $v }}</span>
                                        @endforeach
                                    @endif
                                </div>
                            </td>
                            <td class="dropdown-cell text-center">
                                <div class="dropdown">
                                    <button class="btn btn-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="fa-solid fa-ellipsis-h"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li>
                                            <a class="dropdown-item" href="{{ route('users.show',$user->id) }}">
                                                <i class="fa-solid fa-eye me-2 text-info"></i> View
                                            </a>
                                        </li>
                                        @can('user')
                                        <li>
                                            <a class="dropdown-item" href="{{ route('users.edit',$user->id) }}">
                                                <i class="fa-solid fa-pen-to-square me-2 text-primary"></i> Edit
                                            </a>
                                        </li>
                                        <li>
                                            <button class="dropdown-item text-danger" data-bs-toggle="modal" data-bs-target="#deleteModal" data-action="{{ route('users.destroy', $user->id) }}">
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
        {!! $data->links('pagination::bootstrap-5') !!}
    </div>
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <a class="btn btn-secondary" href="{{ route('settings.index') }}">
            <i class="fa fa-arrow-left me-2"></i> Back
        </a>
    </div>
</div>

<!-- Modal Logic (Keep as is) -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">Confirm Deletion</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">Are you sure you want to delete this user?</div>
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
            deleteModal.querySelector('#delete-form').action = action;
        });
    });
</script>
@endsection