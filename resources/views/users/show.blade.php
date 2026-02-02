@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Show User</h2>
        <a class="btn btn-secondary" href="{{ route('users.index') }}">
            <i class="fa fa-arrow-left me-2"></i> Back
        </a>
    </div>

    <div class="card shadow-sm p-4">
        <div class="row">
            <div class="col-12 mb-3">
                <strong>Name:</strong>
                <p class="lead mb-0">{{ $user->name }}</p>
            </div>
            <div class="col-12 mb-3">
                <strong>Email:</strong>
                <p class="lead mb-0">{{ $user->email }}</p>
            </div>
            <div class="col-12">
                <strong>Roles:</strong><br>
                <div class="d-flex flex-wrap gap-2 mt-2">
                    @if($user->getRoleNames()->isNotEmpty())
                        @foreach ($user->getRoleNames() as $role)
                            <span class="badge bg-primary rounded-pill">{{ $role }}</span>
                        @endforeach
                    @else
                        <p class="text-muted">No Roles Assigned</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection