@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Create New User</h2>
        <a class="btn btn-secondary" href="{{ route('users.index') }}">
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

    <div class="card border-primary shadow-sm p-4">
        <form method="POST" action="{{ route('users.store') }}">
            @csrf
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="name" class="form-label"><strong>Name:</strong></label>
                    <input type="text" name="name" id="name" class="form-control" value="{{ old('name') }}" autocomplete="off">
                </div>
                <div class="col-md-6">
                    <label for="email" class="form-label"><strong>Email:</strong></label>
                    <input type="email" name="email" id="email" class="form-control" value="{{ old('email') }}" autocomplete="off">
                </div>
                <div class="col-md-6">
                    <label for="password" class="form-label"><strong>Password:</strong></label>
                    <input type="password" name="password" id="password" class="form-control">
                </div>
                <div class="col-md-6">
                    <label for="confirm-password" class="form-label"><strong>Confirm Password:</strong></label>
                    <input type="password" name="confirm-password" id="confirm-password" class="form-control">
                </div>
                <div class="col-12">
                    <label for="roles" class="form-label"><strong>Role:</strong></label>
                    <select name="roles[]" id="roles" class="form-select" multiple="multiple">
                        @foreach ($roles as $value => $label)
                            <option value="{{ $value }}" {{ in_array($value, old('roles', [])) ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 text-center mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="fa-solid fa-floppy-disk me-2"></i> Submit
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection