@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <h1 class="mb-4">Submit Absence Request</h1>

            @if($errors->any())
                <div class="alert alert-danger alert-dismissible fade show">
                    <strong>Validation Errors:</strong>
                    <ul class="mb-0">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="card shadow-sm">
                <div class="card-body">
                    <form method="POST" action="{{ route('absences.store') }}">
                        @csrf

                        <div class="mb-3">
                            <label for="employee_name" class="form-label">Employee Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('employee_name') is-invalid @enderror" 
                                id="employee_name" name="employee_name" value="{{ old('employee_name') }}" required>
                            @error('employee_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="date" class="form-label">Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control @error('date') is-invalid @enderror" 
                                id="date" name="date" value="{{ old('date') }}" required>
                            @error('date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="reason" class="form-label">Reason <span class="text-danger">*</span></label>
                            <select class="form-select @error('reason') is-invalid @enderror" 
                                id="reason" name="reason" required>
                                <option value="">-- Select Reason --</option>
                                <option value="sick" {{ old('reason') == 'sick' ? 'selected' : '' }}>Sick</option>
                                <option value="leave" {{ old('reason') == 'leave' ? 'selected' : '' }}>Casual Leave</option>
                                <option value="emergency" {{ old('reason') == 'emergency' ? 'selected' : '' }}>Emergency</option>
                                <option value="other" {{ old('reason') == 'other' ? 'selected' : '' }}>Other</option>
                            </select>
                            @error('reason')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="details" class="form-label">Details</label>
                            <textarea class="form-control @error('details') is-invalid @enderror" 
                                id="details" name="details" rows="4">{{ old('details') }}</textarea>
                            @error('details')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Submit Request
                            </button>
                            <a href="{{ route('absences.index') }}" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
