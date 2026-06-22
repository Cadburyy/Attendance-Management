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

    .shift-tabs {
        display: flex;
        gap: 8px;
        margin-bottom: 24px;
        border-bottom: 2px solid #e5e7eb;
        padding-bottom: 8px;
    }

    .shift-tab {
        padding: 10px 20px;
        background: none;
        border: none;
        font-weight: 600;
        color: #6b7280;
        cursor: pointer;
        transition: all 0.3s ease;
        border-radius: 8px;
    }

    .shift-tab:hover {
        background: #f3f4f6;
        color: #0d3b66;
    }

    .shift-tab.active {
        background: #0d3b66;
        color: white;
    }

    .shift-panel {
        display: none;
        animation: fadeIn 0.3s ease-out;
    }

    .shift-panel.active {
        display: block;
    }

    /* Switch Style */
    .switch {
        position: relative;
        display: inline-block;
        width: 50px;
        height: 28px;
    }

    .switch input {
        opacity: 0;
        width: 0;
        height: 0;
    }

    .slider {
        position: absolute;
        cursor: pointer;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: #cbd5e1;
        transition: .4s;
        border-radius: 34px;
    }

    .slider:before {
        position: absolute;
        content: "";
        height: 20px;
        width: 20px;
        left: 4px;
        bottom: 4px;
        background-color: white;
        transition: .4s;
        border-radius: 50%;
    }

    input:checked + .slider {
        background-color: #0d3b66;
    }

    input:checked + .slider:before {
        transform: translateX(22px);
    }

    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }

    @media (max-width: 768px) {
        .page-header { flex-direction: column; align-items: flex-start; gap: 16px; }
        .btn-save { width: 100%; }
        .shift-tabs { flex-direction: column; }
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

    <!-- Section 2: Edit Absence Time (3 Shifts) -->
    <div class="settings-card">
        <h3 class="mb-4" style="font-weight: 700; color: #0d3b66;">Edit Absence Time (3 Shifts)</h3>
        
        <div class="shift-tabs">
            <button type="button" class="shift-tab active" id="shift-tab-1" onclick="showShift(1)">Shift 1 (Morning)</button>
            <button type="button" class="shift-tab" id="shift-tab-2" onclick="showShift(2)">Shift 2 (Afternoon)</button>
            <button type="button" class="shift-tab" id="shift-tab-3" onclick="showShift(3)">Shift 3 (Night)</button>
        </div>

        <form method="POST" action="{{ route('settings.update.absence') }}">
            @csrf
            @method('PUT')

            <!-- Shift 1 -->
            <div class="shift-panel active" id="shift-panel-1">
                <h5 class="mb-3 font-weight-bold" style="color: #475569;">Shift 1 Configuration</h5>
                <div class="row g-4">
                    <div class="col-md-3">
                        <label for="shift_1_in_start" class="form-label-custom">Check-in Start Time</label>
                        <input type="time" name="shift_1_in_start" id="shift_1_in_start" class="input-custom"
                               value="{{ old('shift_1_in_start', $settings['shift_1_in_start']) }}">
                    </div>
                    <div class="col-md-3">
                        <label for="shift_1_in_end" class="form-label-custom">Check-in Late Boundary</label>
                        <input type="time" name="shift_1_in_end" id="shift_1_in_end" class="input-custom"
                               value="{{ old('shift_1_in_end', $settings['shift_1_in_end']) }}">
                    </div>
                    <div class="col-md-3">
                        <label for="shift_1_out_start" class="form-label-custom">Check-out Start Time</label>
                        <input type="time" name="shift_1_out_start" id="shift_1_out_start" class="input-custom"
                               value="{{ old('shift_1_out_start', $settings['shift_1_out_start']) }}">
                    </div>
                    <div class="col-md-3">
                        <label for="shift_1_out_end" class="form-label-custom">Check-out End Time</label>
                        <input type="time" name="shift_1_out_end" id="shift_1_out_end" class="input-custom"
                               value="{{ old('shift_1_out_end', $settings['shift_1_out_end']) }}">
                    </div>
                </div>
            </div>

            <!-- Shift 2 -->
            <div class="shift-panel" id="shift-panel-2">
                <h5 class="mb-3 font-weight-bold" style="color: #475569;">Shift 2 Configuration</h5>
                <div class="row g-4">
                    <div class="col-md-3">
                        <label for="shift_2_in_start" class="form-label-custom">Check-in Start Time</label>
                        <input type="time" name="shift_2_in_start" id="shift_2_in_start" class="input-custom"
                               value="{{ old('shift_2_in_start', $settings['shift_2_in_start']) }}">
                    </div>
                    <div class="col-md-3">
                        <label for="shift_2_in_end" class="form-label-custom">Check-in Late Boundary</label>
                        <input type="time" name="shift_2_in_end" id="shift_2_in_end" class="input-custom"
                               value="{{ old('shift_2_in_end', $settings['shift_2_in_end']) }}">
                    </div>
                    <div class="col-md-3">
                        <label for="shift_2_out_start" class="form-label-custom">Check-out Start Time</label>
                        <input type="time" name="shift_2_out_start" id="shift_2_out_start" class="input-custom"
                               value="{{ old('shift_2_out_start', $settings['shift_2_out_start']) }}">
                    </div>
                    <div class="col-md-3">
                        <label for="shift_2_out_end" class="form-label-custom">Check-out End Time</label>
                        <input type="time" name="shift_2_out_end" id="shift_2_out_end" class="input-custom"
                               value="{{ old('shift_2_out_end', $settings['shift_2_out_end']) }}">
                    </div>
                </div>
            </div>

            <!-- Shift 3 -->
            <div class="shift-panel" id="shift-panel-3">
                <h5 class="mb-3 font-weight-bold" style="color: #475569;">Shift 3 Configuration</h5>
                <div class="row g-4">
                    <div class="col-md-3">
                        <label for="shift_3_in_start" class="form-label-custom">Check-in Start Time</label>
                        <input type="time" name="shift_3_in_start" id="shift_3_in_start" class="input-custom"
                               value="{{ old('shift_3_in_start', $settings['shift_3_in_start']) }}">
                    </div>
                    <div class="col-md-3">
                        <label for="shift_3_in_end" class="form-label-custom">Check-in Late Boundary</label>
                        <input type="time" name="shift_3_in_end" id="shift_3_in_end" class="input-custom"
                               value="{{ old('shift_3_in_end', $settings['shift_3_in_end']) }}">
                    </div>
                    <div class="col-md-3">
                        <label for="shift_3_out_start" class="form-label-custom">Check-out Start Time</label>
                        <input type="time" name="shift_3_out_start" id="shift_3_out_start" class="input-custom"
                               value="{{ old('shift_3_out_start', $settings['shift_3_out_start']) }}">
                    </div>
                    <div class="col-md-3">
                        <label for="shift_3_out_end" class="form-label-custom">Check-out End Time</label>
                        <input type="time" name="shift_3_out_end" id="shift_3_out_end" class="input-custom"
                               value="{{ old('shift_3_out_end', $settings['shift_3_out_end']) }}">
                    </div>
                </div>
            </div>

            <div class="col-12 text-end mt-4">
                <button type="submit" class="btn-save">
                    <i class="fas fa-clock me-2"></i> Save Shift Times
                </button>
            </div>
        </form>
    </div>

    <!-- Section 3: Geolocation Settings -->
    <div class="settings-card">
        <h3 class="mb-4" style="font-weight: 700; color: #0d3b66;">Geolocation Settings</h3>
        <form method="POST" action="{{ route('settings.update.geolocation') }}">
            @csrf
            @method('PUT')

            <div class="row g-4">
                <div class="col-12 d-flex align-items-center gap-3 mb-2">
                    <label class="switch">
                        <input type="hidden" name="geolocation_enabled" value="0">
                        <input type="checkbox" name="geolocation_enabled" value="1" id="geolocation_enabled" 
                               {{ old('geolocation_enabled', $settings['geolocation_enabled']) == '1' ? 'checked' : '' }}>
                        <span class="slider"></span>
                    </label>
                    <div>
                        <strong style="color: #0d3b66; font-size: 15px; display: block;">Enable Geolocation Verification</strong>
                        <span style="color: #64748b; font-size: 13px;">Require users to be at the office location to perform attendance scanning.</span>
                    </div>
                </div>

                <div class="col-md-4">
                    <label for="office_latitude" class="form-label-custom">Office Latitude</label>
                    <input type="number" step="0.000001" name="office_latitude" id="office_latitude" class="input-custom"
                           value="{{ old('office_latitude', $settings['office_latitude']) }}" placeholder="e.g. -6.200000">
                </div>

                <div class="col-md-4">
                    <label for="office_longitude" class="form-label-custom">Office Longitude</label>
                    <input type="number" step="0.000001" name="office_longitude" id="office_longitude" class="input-custom"
                           value="{{ old('office_longitude', $settings['office_longitude']) }}" placeholder="e.g. 106.816666">
                </div>

                <div class="col-md-4">
                    <label for="office_radius" class="form-label-custom">Allowed Radius (meters)</label>
                    <input type="number" name="office_radius" id="office_radius" class="input-custom"
                           value="{{ old('office_radius', $settings['office_radius']) }}" placeholder="e.g. 100">
                </div>

                <div class="col-12 mt-2">
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="getCurrentLocation()" style="border: 2px solid #cbd5e1; border-radius: 8px; padding: 8px 16px; font-weight: 600; cursor: pointer; background: white; transition: all 0.2s;">
                        <i class="fas fa-map-marker-alt me-1"></i> Detect My Coordinates
                    </button>
                </div>

                <div class="col-12 text-end mt-4">
                    <button type="submit" class="btn-save">
                        <i class="fas fa-map-marked-alt me-2"></i> Save Geolocation Settings
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Section 4: Liveness Settings -->
    <div class="settings-card mt-4">
        <h3 class="mb-4" style="font-weight: 700; color: #0d3b66;">Liveness Detection Settings</h3>
        <form method="POST" action="{{ route('settings.update.liveness') }}">
            @csrf
            @method('PUT')

            <div class="row g-4">
                <div class="col-12 d-flex align-items-center gap-3 mb-2">
                    <label class="switch">
                        <input type="hidden" name="liveness_enabled" value="0">
                        <input type="checkbox" name="liveness_enabled" value="1" id="liveness_enabled" 
                               {{ old('liveness_enabled', $settings['liveness_enabled']) == '1' ? 'checked' : '' }}>
                        <span class="slider"></span>
                    </label>
                    <div>
                        <strong style="color: #0d3b66; font-size: 15px; display: block;">Enable Liveness Verification</strong>
                        <span style="color: #64748b; font-size: 13px;">Require users to complete eye blink or head turn verification challenges before recording attendance.</span>
                    </div>
                </div>

                <div class="col-12 liveness-sub-settings" style="padding-left: 20px; transition: opacity 0.3s;">
                    <h5 class="mb-3" style="font-weight: 600; color: #0d3b66; font-size: 14px;">Active Liveness Challenges</h5>
                    
                    <div class="d-flex flex-column gap-3">
                        <div class="d-flex align-items-center gap-3">
                            <label class="switch">
                                <input type="hidden" name="liveness_blink" value="0">
                                <input type="checkbox" name="liveness_blink" value="1" class="liveness-challenge-toggle"
                                       {{ old('liveness_blink', $settings['liveness_blink']) == '1' ? 'checked' : '' }}>
                                <span class="slider"></span>
                            </label>
                            <div>
                                <strong style="color: #0d3b66; font-size: 14px; display: block;">Eye Blink Challenge</strong>
                                <span style="color: #64748b; font-size: 12px;">Detects natural blinking sequence of eyes.</span>
                            </div>
                        </div>

                        <div class="d-flex align-items-center gap-3">
                            <label class="switch">
                                <input type="hidden" name="liveness_turn_left" value="0">
                                <input type="checkbox" name="liveness_turn_left" value="1" class="liveness-challenge-toggle"
                                       {{ old('liveness_turn_left', $settings['liveness_turn_left']) == '1' ? 'checked' : '' }}>
                                <span class="slider"></span>
                            </label>
                            <div>
                                <strong style="color: #0d3b66; font-size: 14px; display: block;">Hadap Kiri (Turn Head Left) Challenge</strong>
                                <span style="color: #64748b; font-size: 12px;">Detects turning of head towards left side.</span>
                            </div>
                        </div>

                        <div class="d-flex align-items-center gap-3">
                            <label class="switch">
                                <input type="hidden" name="liveness_turn_right" value="0">
                                <input type="checkbox" name="liveness_turn_right" value="1" class="liveness-challenge-toggle"
                                       {{ old('liveness_turn_right', $settings['liveness_turn_right']) == '1' ? 'checked' : '' }}>
                                <span class="slider"></span>
                            </label>
                            <div>
                                <strong style="color: #0d3b66; font-size: 14px; display: block;">Hadap Kanan (Turn Head Right) Challenge</strong>
                                <span style="color: #64748b; font-size: 12px;">Detects turning of head towards right side.</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 text-end mt-4">
                    <button type="submit" class="btn-save">
                        <i class="fas fa-shield-alt me-2"></i> Save Liveness Settings
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    function toggleLivenessSubSettings() {
        const isEnabled = document.getElementById('liveness_enabled').checked;
        const subSettingsContainer = document.querySelector('.liveness-sub-settings');
        const toggles = subSettingsContainer.querySelectorAll('.liveness-challenge-toggle');
        
        if (isEnabled) {
            subSettingsContainer.style.opacity = '1';
            subSettingsContainer.style.pointerEvents = 'auto';
            toggles.forEach(t => t.disabled = false);
        } else {
            subSettingsContainer.style.opacity = '0.5';
            subSettingsContainer.style.pointerEvents = 'none';
            toggles.forEach(t => t.disabled = true);
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        document.getElementById('liveness_enabled').addEventListener('change', toggleLivenessSubSettings);
        toggleLivenessSubSettings();
    });

    function showShift(num) {
        document.querySelectorAll('.shift-tab').forEach(btn => btn.classList.remove('active'));
        document.querySelectorAll('.shift-panel').forEach(panel => panel.classList.remove('active'));
        
        document.getElementById('shift-tab-' + num).classList.add('active');
        document.getElementById('shift-panel-' + num).classList.add('active');
    }

    function getCurrentLocation() {
        if (!navigator.geolocation) {
            alert('Geolocation is not supported by your browser.');
            return;
        }
        alert('Requesting location permissions...');
        navigator.geolocation.getCurrentPosition(function(position) {
            document.getElementById('office_latitude').value = position.coords.latitude.toFixed(6);
            document.getElementById('office_longitude').value = position.coords.longitude.toFixed(6);
        }, function(error) {
            alert('Error getting location: ' + error.message);
        }, { enableHighAccuracy: true });
    }
</script>
@endsection