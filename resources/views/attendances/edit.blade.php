@extends('layouts.app')

@section('content')
<style>
    .form-wrapper {
        min-height: 100vh;
        animation: fadeInUp 0.5s ease-out;
    }

    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .form-container {
        max-width: 780px;
        background: white;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
        border: 1px solid rgba(0, 0, 0, 0.05);
    }

    .form-header {
        background: linear-gradient(135deg, #0d3b66 0%, #1a5490 100%);
        color: white;
        padding: 40px 32px;
    }

    .form-title { font-size: 28px; font-weight: 700; margin: 0 0 8px 0; }
    .form-subtitle { font-size: 15px; opacity: 0.9; margin: 0; }
    .form-container > form { padding: 40px 32px; }

    .alert-error {
        background: linear-gradient(135deg, rgba(239, 68, 68, 0.1) 0%, rgba(239, 68, 68, 0.05) 100%);
        border-left: 4px solid #ef4444;
        border-radius: 10px;
        padding: 16px 20px;
        display: flex;
        gap: 12px;
        margin-bottom: 24px;
        color: #991b1b;
    }

    .alert-error i { font-size: 20px; flex-shrink: 0; margin-top: 2px; }
    .alert-content strong { display: block; margin-bottom: 8px; }
    .error-list { list-style: none; padding: 0; margin: 0; }
    .error-list li { padding: 4px 0; font-size: 14px; }

    .modern-form { display: flex; flex-direction: column; gap: 24px; }
    .form-group { display: flex; flex-direction: column; gap: 8px; }
    .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
    .form-label { font-size: 14px; font-weight: 700; color: #1f2937; text-transform: uppercase; letter-spacing: 0.3px; }
    .required { color: #ef4444; }

    .form-input {
        width: 100%;
        padding: 12px 16px;
        border: 2px solid #e5e7eb;
        border-radius: 10px;
        font-size: 15px;
        font-family: inherit;
        transition: all 0.3s ease;
        background: #f9fafb;
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

    .error-text { font-size: 13px; color: #ef4444; font-weight: 500; }
    .form-actions { display: flex; gap: 12px; margin-top: 16px; }

    .btn {
        padding: 12px 24px;
        border: none;
        border-radius: 10px;
        font-weight: 700;
        font-size: 14px;
        cursor: pointer;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        text-decoration: none;
        text-align: center;
        flex: 1;
        justify-content: center;
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
        .form-header { padding: 30px 20px; }
        .form-container > form { padding: 30px 20px; }
        .form-title { font-size: 24px; }
        .form-row { grid-template-columns: 1fr; }
        .form-actions { flex-direction: column; }
        .btn { width: 100%; flex: none; }
    }
</style>

<div class="form-wrapper">
    <div class="form-container">
        <div class="form-header">
            <h1 class="form-title">Edit Attendance Record</h1>
            <p class="form-subtitle">Update attendance information</p>
        </div>

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

        <form method="POST" action="{{ route('attendances.update', $attendance) }}" class="modern-form">
            @csrf
            @method('PUT')

            <div class="form-group">
                <label for="user_id" class="form-label">Employee <span class="required">*</span></label>
                <select class="form-input @error('user_id') error @enderror" id="user_id" name="user_id" required>
                    <option value="">-- Select Employee --</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}" {{ $attendance->user_id == $user->id ? 'selected' : '' }}>
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
                        id="date" name="date" value="{{ $attendance->date->format('Y-m-d') }}" required>
                    @error('date')<span class="error-text">{{ $message }}</span>@enderror
                </div>

                <div class="form-group">
                    <label for="status" class="form-label">Status <span class="required">*</span></label>
                    <select class="form-input @error('status') error @enderror" id="status" name="status" required>
                        <option value="">-- Select Status --</option>
                        <option value="present" {{ $attendance->status == 'present' ? 'selected' : '' }}>Present</option>
                        <option value="absent" {{ $attendance->status == 'absent' ? 'selected' : '' }}>Absent</option>
                        <option value="late" {{ $attendance->status == 'late' ? 'selected' : '' }}>Late</option>
                        <option value="leave" {{ $attendance->status == 'leave' ? 'selected' : '' }}>Leave</option>
                        <option value="sick" {{ $attendance->status == 'sick' ? 'selected' : '' }}>Sick</option>
                    </select>
                    @error('status')<span class="error-text">{{ $message }}</span>@enderror
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="check_in" class="form-label">Check-In</label>
                    <input type="time" class="form-input @error('check_in') error @enderror" 
                        id="check_in" name="check_in" value="{{ $attendance->check_in ?? '' }}">
                    @error('check_in')<span class="error-text">{{ $message }}</span>@enderror
                </div>

                <div class="form-group">
                    <label for="check_out" class="form-label">Check-Out</label>
                    <input type="time" class="form-input @error('check_out') error @enderror" 
                        id="check_out" name="check_out" value="{{ $attendance->check_out ?? '' }}">
                    @error('check_out')<span class="error-text">{{ $message }}</span>@enderror
                </div>
            </div>

            <div class="form-group">
                <label for="notes" class="form-label">Notes</label>
                <textarea class="form-input @error('notes') error @enderror" 
                    id="notes" name="notes" rows="4" style="resize:vertical;">{{ $attendance->notes ?? '' }}</textarea>
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
