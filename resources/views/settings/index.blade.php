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
    }

    .input-custom:focus {
        outline: none;
        border-color: #0d3b66;
        box-shadow: 0 0 0 3px rgba(13, 59, 102, 0.1);
    }

    .asset-preview {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px;
        background: #f9fafb;
        border-radius: 10px;
        border: 1px solid #e5e7eb;
        margin-top: 10px;
    }

    .asset-preview img {
        object-fit: contain;
        background: white;
        padding: 4px;
        border-radius: 4px;
        border: 1px solid #eee;
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
            <h1 class="page-title">Edit Appearance</h1>
            <p class="page-subtitle">Manage branding, logos, and global typography</p>
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
        <form method="POST" action="{{ route('settings.update') }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="row g-4">
                <div class="col-md-6">
                    <label for="brand_name" class="form-label-custom">Brand Name</label>
                    <input type="text" name="brand_name" id="brand_name" class="input-custom"
                           value="{{ old('brand_name', $settings['brand_name'] ?? '') }}">
                    <small class="text-muted d-block mt-2">Visible next to the logo in the navigation bar.</small>
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
                    <small class="text-muted d-block mt-2">The system will automatically load this Google Font.</small>
                </div>

                <div class="col-md-6">
                    <label for="logo" class="form-label-custom">Main Logo (PNG)</label>
                    <input type="file" name="logo" id="logo" class="input-custom" accept="image/png">
                    @if(!empty($settings['logo_path']))
                        <div class="asset-preview">
                            <img src="{{ asset('storage/'.$settings['logo_path']) }}" alt="Logo" style="height:40px">
                            <span class="text-muted" style="font-size: 12px;">Current logo active</span>
                        </div>
                    @endif
                </div>

                <div class="col-md-6">
                    <label for="favicon" class="form-label-custom">Favicon / Browser Icon</label>
                    <input type="file" name="favicon" id="favicon" class="input-custom" accept="image/*">
                    @if(!empty($settings['favicon_path']))
                        <div class="asset-preview">
                            <img src="{{ asset('storage/'.$settings['favicon_path']) }}" alt="Favicon" style="height:24px">
                            <span class="text-muted" style="font-size: 12px;">Current favicon active</span>
                        </div>
                    @endif
                </div>

                <div class="col-12 text-center mt-5">
                    <button type="submit" class="btn-save">
                        <i class="fas fa-save me-2"></i> Save Appearance Changes
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection