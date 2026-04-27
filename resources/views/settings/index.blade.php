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

    input[type="file"].input-custom {
        padding: 10px 16px;
        line-height: 1.6;
    }

    select.input-custom {
        appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%236b7280' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 16px center;
        padding-right: 40px;
    }

    .input-custom:focus {
        outline: none;
        border-color: #0d3b66;
        box-shadow: 0 0 0 3px rgba(13, 59, 102, 0.1);
    }

    .current-asset-box {
        background: #f8fafc;
        border: 1px dashed #cbd5e1;
        border-radius: 8px;
        display: flex;
        align-items: center;
        gap: 16px;
        padding: 16px;
        margin-bottom: 12px;
    }

    .current-asset-preview {
        width: 64px;
        height: 64px;
        background: white;
        border-radius: 8px;
        display: flex;
        justify-content: center;
        align-items: center;
        border: 1px solid #e2e8f0;
        overflow: hidden;
        padding: 4px;
    }

    .current-asset-preview img {
        max-width: 100%;
        max-height: 100%;
        object-fit: contain;
    }

    .current-asset-info strong {
        display: block;
        font-size: 14px;
        color: #334155;
        margin-bottom: 2px;
    }

    .current-asset-info span {
        font-size: 12px;
        color: #64748b;
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
        animation: slideIn 0.4s ease-out;
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

    @keyframes slideIn {
        from { opacity: 0; transform: translateX(-20px); }
        to { opacity: 1; transform: translateX(0); }
    }

    @media (max-width: 768px) {
        .page-header { flex-direction: column; align-items: flex-start; gap: 16px; }
        .btn-save { width: 100%; }
    }
</style>

<div class="page-wrapper">
    <div class="page-header mb-5">
        <div class="header-content">
            <h1 class="page-title">General Settings</h1>
            <p class="page-subtitle">Manage company identity and attendance schedules</p>
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

    <!-- Section 1: Edit Appearance -->
    <div class="settings-card">
        <h3 class="mb-4" style="font-weight: 700; color: #0d3b66;">Edit Appearance</h3>
        <form method="POST" action="{{ route('settings.update.appearance') }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="row g-4">
                <div class="col-md-6">
                    <label for="brand_name" class="form-label-custom">Company Name</label>
                    <input type="text" name="brand_name" id="brand_name" class="input-custom"
                           value="{{ old('brand_name', $settings['brand_name'] ?? '') }}">
                </div>

                <div class="col-md-6">
                    <label for="font" class="form-label-custom">Global Font Family</label>
                    <select name="font" id="font" class="input-custom">
                        @foreach (['Nunito','Inter','Roboto','Poppins','Open Sans'] as $font)
                            <option value="{{ $font }}" {{ old('font', $settings['font'] ?? '') === $font ? 'selected' : '' }}>
                                {{ $font }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label-custom">Main Logo (PNG)</label>
                    
                    <div class="current-asset-box">
                        <div class="current-asset-preview">
                            @if(!empty($settings['logo_path']))
                                <img src="{{ asset('storage/'.$settings['logo_path']) }}" alt="Current Logo">
                            @else
                                <i class="fas fa-image text-muted" style="font-size: 24px;"></i>
                            @endif
                        </div>
                        <div class="current-asset-info">
                            <strong>Current Logo</strong>
                            <span>{{ !empty($settings['logo_path']) ? 'Active logo installed' : 'No logo yet' }}</span>
                        </div>
                    </div>

                    <input type="file" name="logo" id="logo" class="input-custom" accept="image/png">
                    <small class="text-muted d-block mt-2">Upload to replace the current logo.</small>
                </div>

                <div class="col-md-6">
                    <label class="form-label-custom">Favicon / Browser Icon</label>
                    
                    <div class="current-asset-box">
                        <div class="current-asset-preview">
                            @if(!empty($settings['favicon_path']))
                                <img src="{{ asset('storage/'.$settings['favicon_path']) }}" alt="Current Favicon">
                            @else
                                <i class="fas fa-globe text-muted" style="font-size: 24px;"></i>
                            @endif
                        </div>
                        <div class="current-asset-info">
                            <strong>Current Favicon</strong>
                            <span>{{ !empty($settings['favicon_path']) ? 'Active favicon installed' : 'No favicon yet' }}</span>
                        </div>
                    </div>

                    <input type="file" name="favicon" id="favicon" class="input-custom" accept="image/*">
                    <small class="text-muted d-block mt-2">Upload to replace the current favicon.</small>
                </div>

                <div class="col-12 text-end mt-4">
                    <button type="submit" class="btn-save">
                        <i class="fas fa-save me-2"></i> Save Appearance
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Section 2: Edit Absence Time -->
    <div class="settings-card">
        <h3 class="mb-4" style="font-weight: 700; color: #0d3b66;">Edit Absence Time</h3>
        <form method="POST" action="{{ route('settings.update.absence') }}">
            @csrf
            @method('PUT')

            <div class="row g-4">
                <div class="col-md-6">
                    <label for="attendance_in_start" class="form-label-custom">Check-in Start Time</label>
                    <input type="time" name="attendance_in_start" id="attendance_in_start" class="input-custom"
                           value="{{ old('attendance_in_start', $settings['attendance_in_start']) }}">
                </div>

                <div class="col-md-6">
                    <label for="attendance_in_end" class="form-label-custom">Check-in Late Boundary</label>
                    <input type="time" name="attendance_in_end" id="attendance_in_end" class="input-custom"
                           value="{{ old('attendance_in_end', $settings['attendance_in_end']) }}">
                </div>

                <div class="col-md-6">
                    <label for="attendance_out_start" class="form-label-custom">Check-out Start Time</label>
                    <input type="time" name="attendance_out_start" id="attendance_out_start" class="input-custom"
                           value="{{ old('attendance_out_start', $settings['attendance_out_start']) }}">
                </div>

                <div class="col-md-6">
                    <label for="attendance_out_end" class="form-label-custom">Check-out End Time</label>
                    <input type="time" name="attendance_out_end" id="attendance_out_end" class="input-custom"
                           value="{{ old('attendance_out_end', $settings['attendance_out_end']) }}">
                </div>

                <div class="col-12 text-end mt-4">
                    <button type="submit" class="btn-save">
                        <i class="fas fa-clock me-2"></i> Save Absence Times
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection