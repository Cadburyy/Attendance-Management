@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <h1 class="mb-4">@isset($attendance) Edit @else Create @endisset Attendance</h1>

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
                    <form method="POST" 
                        action="@isset($attendance){{ route('attendances.update', $attendance) }}@else{{ route('attendances.store') }}@endisset">
                        @csrf
                        @isset($attendance)
                            @method('PUT')
                        @endisset

                        <div class="mb-3">
                            <label for="user_id" class="form-label">User <span class="text-danger">*</span></label>
                            <select class="form-select @error('user_id') is-invalid @enderror" 
                                id="user_id" name="user_id" required>
                                <option value="">-- Select User --</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}" 
                                        {{ (isset($attendance) && $attendance->user_id == $user->id) || old('user_id') == $user->id ? 'selected' : '' }}>
                                        {{ $user->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('user_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="date" class="form-label">Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control @error('date') is-invalid @enderror" 
                                id="date" name="date" 
                                value="{{ isset($attendance) ? $attendance->date->format('Y-m-d') : old('date') }}" required>
                            @error('date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="check_in" class="form-label">Check-In Time</label>
                                <input type="time" class="form-control @error('check_in') is-invalid @enderror" 
                                    id="check_in" name="check_in" 
                                    value="{{ isset($attendance) && $attendance->check_in ? $attendance->check_in : old('check_in') }}">
                                @error('check_in')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="check_out" class="form-label">Check-Out Time</label>
                                <input type="time" class="form-control @error('check_out') is-invalid @enderror" 
                                    id="check_out" name="check_out" 
                                    value="{{ isset($attendance) && $attendance->check_out ? $attendance->check_out : old('check_out') }}">
                                @error('check_out')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                            <select class="form-select @error('status') is-invalid @enderror" 
                                id="status" name="status" required>
                                <option value="">-- Select Status --</option>
                                <option value="present" {{ (isset($attendance) && $attendance->status == 'present') || old('status') == 'present' ? 'selected' : '' }}>Present</option>
                                <option value="absent" {{ (isset($attendance) && $attendance->status == 'absent') || old('status') == 'absent' ? 'selected' : '' }}>Absent</option>
                                <option value="late" {{ (isset($attendance) && $attendance->status == 'late') || old('status') == 'late' ? 'selected' : '' }}>Late</option>
                                <option value="leave" {{ (isset($attendance) && $attendance->status == 'leave') || old('status') == 'leave' ? 'selected' : '' }}>Leave</option>
                                <option value="sick" {{ (isset($attendance) && $attendance->status == 'sick') || old('status') == 'sick' ? 'selected' : '' }}>Sick</option>
                            </select>
                            @error('status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="notes" class="form-label">Notes</label>
                            <textarea class="form-control @error('notes') is-invalid @enderror" 
                                id="notes" name="notes" rows="3">{{ isset($attendance) ? $attendance->notes : old('notes') }}</textarea>
                            @error('notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> @isset($attendance) Update @else Create @endisset
                            </button>
                            <a href="{{ route('attendances.index') }}" class="btn btn-secondary">
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
