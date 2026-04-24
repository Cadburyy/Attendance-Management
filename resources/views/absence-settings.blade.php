@extends('layouts.app')

@section('content')
<style>
    .page-wrapper {
        animation: fadeInUp 0.5s ease-out;
    }

    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding-bottom: 20px;
    }

    .page-title {
        font-size: 32px;
        font-weight: 700;
        color: #0d3b66;
        margin: 0 0 8px 0;
    }

    .page-subtitle {
        color: #6b7280;
        margin: 0;
        font-size: 15px;
    }

    .settings-card {
        background: white;
        border-radius: 16px;
        padding: 32px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        border: 1px solid rgba(0, 0, 0, 0.05);
        margin-bottom: 32px;
    }

    .form-label-custom {
        font-size: 12px;
        font-weight: 700;
        color: #6b7280;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        margin-bottom: 8px;
        display: block;
    }

    .input-custom {
        width: 100%;
        padding: 12px 16px;
        border: 2px solid #e5e7eb;
        border-radius: 8px;
        font-size: 14px;
        transition: all 0.3s ease;
        height: 50px;
        background-color: white;
        box-sizing: border-box;
    }

    .input-custom:focus {
        outline: none;
        border-color: #0d3b66;
        box-shadow: 0 0 0 3px rgba(13, 59, 102, 0.1);
    }

    .btn-save {
        padding: 12px 32px;
        background: linear-gradient(135deg, #0d3b66 0%, #1a5490 100%);
        color: white;
        border: none;
        border-radius: 10px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(13, 59, 102, 0.3);
    }

    .btn-save:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(13, 59, 102, 0.4);
        color: white;
    }

    .alert-custom {
        border-radius: 10px;
        padding: 16px 20px;
        margin-bottom: 24px;
        display: flex;
        align-items: center;
        gap: 12px;
        font-weight: 500;
    }

    .alert-success-custom {
        background: rgba(6, 168, 125, 0.1);
        border-left: 4px solid #06a77d;
        color: #06a77d;
    }

    .alert-danger-custom {
        background: rgba(239, 68, 68, 0.1);
        border-left: 4px solid #ef4444;
        color: #ef4444;
    }

    .info-box {
        background: #f1f5f9;
        border-radius: 12px;
        padding: 20px;
        margin-top: 20px;
        border: 1px solid #e2e8f0;
    }

    .info-box i {
        color: #3b82f6;
        margin-right: 10px;
    }
</style>

<div class="page-wrapper">
    <div class="page-header mb-5">
        <div class="header-content">
            <h1 class="page-title">AI Absence Settings</h1>
            <p class="page-subtitle">Configure operational hours for the AI recognition system</p>
        </div>
    </div>

    @if ($errors->any())
        <div class="alert-custom alert-danger-custom">
            <i class="fas fa-exclamation-triangle"></i>
            <div>
                <strong>Validation Error</strong>
                <ul class="mb-0 ps-3 mt-1" style="font-size: 0.9em;">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    @if(session('success'))
        <div class="alert-custom alert-success-custom">
            <i class="fas fa-check-circle"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    <div class="settings-card">
        <form method="POST" action="{{ route('ai-absence.update') }}">
            @csrf
            @method('PUT')

            <div class="row g-4">
                <div class="col-12">
                    <h5 class="fw-bold text-primary mb-3"><i class="fas fa-sign-in-alt me-2"></i> Check-in Window</h5>
                    <p class="text-muted small mb-4">Define when employees can start and end their check-in for the day.</p>
                </div>

                <div class="col-md-6">
                    <label for="attendance_in_start" class="form-label-custom">Check-in Start Time</label>
                    <input type="time" name="attendance_in_start" id="attendance_in_start" class="input-custom"
                           value="{{ old('attendance_in_start', $settings['attendance_in_start'] ?? '07:00') }}">
                </div>

                <div class="col-md-6">
                    <label for="attendance_in_end" class="form-label-custom">Check-in End Time</label>
                    <input type="time" name="attendance_in_end" id="attendance_in_end" class="input-custom"
                           value="{{ old('attendance_in_end', $settings['attendance_in_end'] ?? '09:00') }}">
                </div>

                <div class="col-12 mt-5">
                    <h5 class="fw-bold text-primary mb-3"><i class="fas fa-sign-out-alt me-2"></i> Check-out Window</h5>
                    <p class="text-muted small mb-4">Define when employees can start and end their check-out for the day.</p>
                </div>

                <div class="col-md-6">
                    <label for="attendance_out_start" class="form-label-custom">Check-out Start Time</label>
                    <input type="time" name="attendance_out_start" id="attendance_out_start" class="input-custom"
                           value="{{ old('attendance_out_start', $settings['attendance_out_start'] ?? '16:00') }}">
                </div>

                <div class="col-md-6">
                    <label for="attendance_out_end" class="form-label-custom">Check-out End Time</label>
                    <input type="time" name="attendance_out_end" id="attendance_out_end" class="input-custom"
                           value="{{ old('attendance_out_end', $settings['attendance_out_end'] ?? '18:00') }}">
                </div>

                <div class="col-12 mt-4">
                    <div class="info-box">
                        <i class="fas fa-info-circle"></i>
                        <strong>Automatic Absence:</strong> Any employee who has not checked out by midnight (12:00 AM) will be automatically marked as <strong>Absent</strong> for the day.
                    </div>
                </div>

                <div class="col-12 text-center mt-5">
                    <button type="submit" class="btn-save">
                        <i class="fas fa-save me-2"></i> Save AI Absence Configuration
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
