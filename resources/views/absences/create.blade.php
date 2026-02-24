@extends('layouts.app')

@section('content')
<div class="form-wrapper">
    <div class="form-container">
        <div class="form-header">
            <div>
                <h1 class="form-title">Submit Absence Request</h1>
                <p class="form-subtitle">Fill in the details to submit a new absence request</p>
            </div>
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

        <form method="POST" action="{{ route('absences.store') }}" class="modern-form">
            @csrf

            <div class="form-group">
                <label for="employee_name" class="form-label">Employee Name <span class="required">*</span></label>
                <input type="text" class="form-input @error('employee_name') error @enderror" 
                    id="employee_name" name="employee_name" value="{{ old('employee_name') }}" 
                    placeholder="Enter employee name" required>
                @error('employee_name')
                    <span class="error-text">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="date" class="form-label">Date <span class="required">*</span></label>
                    <input type="date" class="form-input @error('date') error @enderror" 
                        id="date" name="date" value="{{ old('date') }}" required>
                    @error('date')
                        <span class="error-text">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="reason" class="form-label">Reason <span class="required">*</span></label>
                    <select class="form-input @error('reason') error @enderror" id="reason" name="reason" required>
                        <option value="">-- Select Reason --</option>
                        <option value="sick" {{ old('reason') == 'sick' ? 'selected' : '' }}>Sick</option>
                        <option value="leave" {{ old('reason') == 'leave' ? 'selected' : '' }}>Casual Leave</option>
                        <option value="emergency" {{ old('reason') == 'emergency' ? 'selected' : '' }}>Emergency</option>
                        <option value="other" {{ old('reason') == 'other' ? 'selected' : '' }}>Other</option>
                    </select>
                    @error('reason')
                        <span class="error-text">{{ $message }}</span>
                    @enderror
                </div>
            </div>

            <div class="form-group">
                <label for="details" class="form-label">Details</label>
                <textarea class="form-input textarea @error('details') error @enderror" 
                    id="details" name="details" rows="5" placeholder="Additional details (optional)">{{ old('details') }}</textarea>
                @error('details')
                    <span class="error-text">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-submit">
                    <i class="fas fa-paper-plane"></i> Submit Request
                </button>
                <a href="{{ route('absences.index') }}" class="btn btn-cancel">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </div>
        </form>
    </div>
</div>

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
        max-width: 700px;
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

    .form-title {
        font-size: 28px;
        font-weight: 700;
        margin: 0 0 8px 0;
    }

    .form-subtitle {
        font-size: 15px;
        opacity: 0.9;
        margin: 0;
    }

    .form-container > form {
        padding: 40px 32px;
    }

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

    .alert-error i {
        font-size: 20px;
        flex-shrink: 0;
        margin-top: 2px;
    }

    .alert-content strong {
        display: block;
        margin-bottom: 8px;
    }

    .error-list {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .error-list li {
        padding: 4px 0;
        font-size: 14px;
    }

    .modern-form {
        display: flex;
        flex-direction: column;
        gap: 24px;
    }

    .form-group {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
    }

    .form-label {
        font-size: 14px;
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

    .form-input.textarea {
        resize: vertical;
        padding: 14px 16px;
    }

    .error-text {
        font-size: 13px;
        color: #ef4444;
        font-weight: 500;
    }

    .form-actions {
        display: flex;
        gap: 12px;
        margin-top: 16px;
    }

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
        .form-header {
            padding: 30px 20px;
        }

        .form-container > form {
            padding: 30px 20px;
        }

        .form-title {
            font-size: 24px;
        }

        .form-row {
            grid-template-columns: 1fr;
        }

        .form-actions {
            flex-direction: column;
        }

        .btn {
            flex: unset;
            width: 100%;
        }
    }
</style>
@endsection
