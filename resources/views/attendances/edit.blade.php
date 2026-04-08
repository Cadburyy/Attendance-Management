@extends('layouts.app')

@section('content')
<div class="form-wrapper">
    <div class="form-container">
        <div class="form-header">
            <h1 class="form-title">Edit Attendance Record</h1>
            <p class="form-subtitle">Update attendance information</p>
        </div>

        <form method="POST" action="{{ route('attendances.update', $attendance) }}" class="modern-form">
            @csrf
            @method('PUT')

            @if($errors->any())
                <div class="alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <div class="alert-content">
                        <strong>Validation Errors:</strong>
                        <ul class="error-list">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

            <div class="form-group">
                <label for="user_id" class="form-label">Employee <span class="required">*</span></label>
                <select class="form-input @error('user_id') error @enderror" id="user_id" name="user_id" required>
                    <option value="">-- Select Employee --</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}" {{ old('user_id', $attendance->user_id) == $user->id ? 'selected' : '' }}>
                            {{ $user->name }}
                        </option>
                    @endforeach
                </select>
                @error('user_id')<span class="error-text">{{ $message }}</span>@enderror
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="date" class="form-label">Date <span class="required">*</span></label>
                    <input type="date" class="form-input @error('date') error @enderror" 
                        id="date" name="date" value="{{ old('date', $attendance->date->format('Y-m-d')) }}" 
                        @if(!auth()->user()->can('override'))
                        min="{{ now()->subDay()->toDateString() }}" 
                        max="{{ now()->addDay()->toDateString() }}"
                        @endif
                        onclick="this.showPicker && this.showPicker()"
                        required>
                    @error('date')<span class="error-text">{{ $message }}</span>@enderror
                </div>

                <div class="form-group">
                    <label for="status" class="form-label">Status <span class="required">*</span></label>
                    <select class="form-input @error('status') error @enderror" id="status" name="status" required>
                        <option value="">-- Select Status --</option>
                        <option value="present" {{ old('status', $attendance->status) == 'present' ? 'selected' : '' }}>Present</option>
                        <option value="absent" {{ old('status', $attendance->status) == 'absent' ? 'selected' : '' }}>Absent</option>
                        <option value="late" {{ old('status', $attendance->status) == 'late' ? 'selected' : '' }}>Late</option>
                        <option value="leave" {{ old('status', $attendance->status) == 'leave' ? 'selected' : '' }}>Leave</option>
                        <option value="sick" {{ old('status', $attendance->status) == 'sick' ? 'selected' : '' }}>Sick</option>
                    </select>
                    @error('status')<span class="error-text">{{ $message }}</span>@enderror
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="check_in" class="form-label">Check-In</label>
                    <input type="time" class="form-input @error('check_in') error @enderror" 
                        id="check_in" name="check_in" 
                        value="{{ old('check_in', $attendance->check_in ? \Carbon\Carbon::parse($attendance->check_in)->format('H:i') : '') }}" 
                        onclick="this.showPicker && this.showPicker()">
                    @error('check_in')<span class="error-text">{{ $message }}</span>@enderror
                </div>

                <div class="form-group">
                    <label for="check_out" class="form-label">Check-Out</label>
                    <input type="time" class="form-input @error('check_out') error @enderror" 
                        id="check_out" name="check_out" 
                        value="{{ old('check_out', $attendance->check_out ? \Carbon\Carbon::parse($attendance->check_out)->format('H:i') : '') }}" 
                        onclick="this.showPicker && this.showPicker()">
                    @error('check_out')<span class="error-text">{{ $message }}</span>@enderror
                </div>
            </div>

            <div class="form-group">
                <label for="notes" class="form-label">Notes</label>
                <textarea class="form-input textarea @error('notes') error @enderror" 
                    id="notes" name="notes" rows="2" placeholder="Additional notes (optional)">{{ old('notes', $attendance->notes) }}</textarea>
                @error('notes')<span class="error-text">{{ $message }}</span>@enderror
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-submit">
                    <i class="fas fa-check"></i> Update
                </button>
                <a href="{{ route('attendances.index') }}" class="btn btn-cancel">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<style>
    .form-wrapper {
        min-height: calc(100vh - 100px);
        display: flex;
        align-items: center;
        justify-content: center;
        animation: fadeInUp 0.5s ease-out;
    }

    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .form-container {
        max-width: 680px;
        width: 100%;
        background: white;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
        border: 1px solid rgba(0, 0, 0, 0.05);
    }

    .form-header {
        background: linear-gradient(135deg, #0d3b66 0%, #1a5490 100%);
        color: white;
        padding: 24px 32px;
    }

    .form-title {
        font-size: 24px;
        font-weight: 700;
        margin: 0 0 4px 0;
    }

    .form-subtitle {
        font-size: 14px;
        opacity: 0.9;
        margin: 0;
    }

    .form-container > form {
        padding: 24px 32px;
    }

    .alert-error {
        background: linear-gradient(135deg, rgba(239, 68, 68, 0.1) 0%, rgba(239, 68, 68, 0.05) 100%);
        border-left: 4px solid #ef4444;
        border-radius: 10px;
        padding: 14px 20px;
        display: flex;
        gap: 12px;
        color: #991b1b;
        margin-bottom: 16px;
    }

    .alert-error i {
        font-size: 18px;
        flex-shrink: 0;
        margin-top: 2px;
    }

    .alert-content strong {
        display: block;
        margin-bottom: 4px;
        font-size: 14px;
    }

    .error-list {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .error-list li {
        padding: 2px 0;
        font-size: 13px;
    }

    .modern-form {
        display: flex;
        flex-direction: column;
        gap: 16px;
    }

    .form-group {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }

    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px;
    }

    .form-label {
        font-size: 13px;
        font-weight: 700;
        color: #1f2937;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        display: flex;
        align-items: center;
        gap: 4px;
    }

    .required {
        color: #ef4444;
    }

    .form-input {
        width: 100%;
        height: 46px;
        padding: 10px 16px;
        border: 2px solid #e5e7eb;
        border-radius: 10px;
        font-size: 14px;
        font-family: inherit;
        transition: all 0.3s ease;
        background: #f9fafb;
        box-sizing: border-box;
    }

    .form-input:focus {
        outline: none;
        border-color: #0d3b66;
        background: white;
        box-shadow: 0 0 0 4px rgba(13, 59, 102, 0.1);
    }

    .form-input.error {
        border-color: #ef4444;
        background: rgba(239, 68, 68, 0.05);
    }

    .form-input.error:focus {
        box-shadow: 0 0 0 4px rgba(239, 68, 68, 0.1);
    }

    .form-input.textarea {
        height: auto;
        min-height: 80px;
        resize: vertical;
        padding: 12px 16px;
    }

    .error-text {
        font-size: 12px;
        color: #ef4444;
        font-weight: 500;
    }

    .form-actions {
        display: flex;
        gap: 12px;
        margin-top: 8px;
    }

    .btn {
        height: 46px;
        padding: 0 24px;
        border: none;
        border-radius: 10px;
        font-weight: 700;
        font-size: 14px;
        cursor: pointer;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        text-decoration: none;
        text-align: center;
        flex: 1;
        box-sizing: border-box;
    }

    .btn-submit {
        background: linear-gradient(135deg, #0d3b66 0%, #1a5490 100%);
        color: white;
        box-shadow: 0 4px 15px rgba(13, 59, 102, 0.3);
    }

    .btn-submit:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(13, 59, 102, 0.4);
    }

    .btn-cancel {
        background: #f3f4f6;
        color: #6b7280;
        border: 1px solid #e5e7eb;
    }

    .btn-cancel:hover {
        background: #e5e7eb;
        color: #1f2937;
    }

    @media (max-width: 768px) {
        .form-header, .form-container > form {
            padding: 24px 20px;
        }

        .form-row {
            grid-template-columns: 1fr;
        }

        .form-actions {
            flex-direction: column;
        }
    }
</style>
@endsection