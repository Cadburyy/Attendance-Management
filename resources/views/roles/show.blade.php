@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Show Role</h2>
        <a class="btn btn-secondary" href="{{ route('roles.index') }}">
            <i class="fa fa-arrow-left me-2"></i> Back
        </a>
    </div>

    <div class="card shadow-sm p-4">
        <div class="row">
            <div class="col-md-12 mb-3">
                <div class="form-group">
                    <strong>Name:</strong>
                    <p class="lead mb-0">{{ $role->name }}</p>
                </div>
            </div>

            <div class="col-md-12 mt-3">
                <div class="form-group">
                    <strong>Permissions:</strong>
                    <div class="d-flex flex-wrap gap-2 mt-2">
                        @if (!empty($rolePermissions))
                            @foreach ($rolePermissions as $permission)
                                <span class="badge bg-primary rounded-pill">{{ $permission->name }}</span>
                            @endforeach
                        @else
                            <span class="text-muted">No permissions assigned.</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
