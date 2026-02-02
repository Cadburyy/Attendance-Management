@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Edit Role</h2>
        <a class="btn btn-secondary" href="{{ route('roles.index') }}">
            <i class="fa fa-arrow-left me-2"></i> Back
        </a>
    </div>

    @if (count($errors) > 0)
        <div class="alert alert-danger rounded-3 shadow-sm">
            <strong>Whoops!</strong> There were some problems with your input.<br><br>
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card border-warning shadow-sm p-4">
        <form method="POST" action="{{ route('roles.update', $role->id) }}">
            @csrf
            @method('PUT')

            <div class="row g-3">
                <div class="col-12">
                    <label for="name" class="form-label"><strong>Name:</strong></label>
                    <input type="text" name="name" id="name" placeholder="Role Name" class="form-control" value="{{ old('name', $role->name) }}" autocomplete="off">
                </div>
                <div class="col-12">
                    <label class="form-label"><strong>Permissions:</strong></label>
                    <div class="row">
                        @foreach($permission as $value)
                            <div class="col-md-3 col-sm-6 mb-2">
                                <div class="form-check">
                                    <input type="checkbox" name="permission[]" value="{{$value->id}}" class="form-check-input" id="permission-{{ $value->id }}" {{ in_array($value->id, old('permission', $rolePermissions)) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="permission-{{ $value->id }}">
                                        {{ ucfirst($value->name) }}
                                    </label>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="col-12 text-center mt-4">
                    <button type="submit" class="btn btn-warning">
                        <i class="fa-solid fa-floppy-disk me-2"></i> Submit
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection