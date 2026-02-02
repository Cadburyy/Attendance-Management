@extends('layouts.app')

@section('content')

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Edit Appearance</h2>
        <a class="btn btn-secondary" href="{{ route('settings.index') }}">
            <i class="fa fa-arrow-left me-2"></i> Back
        </a>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger rounded-3 shadow-sm mt-2">
            <strong>Whoops!</strong> There were some problems with your input.<br><br>
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if(session('success'))
        <div class="alert alert-success rounded-3 shadow-sm mt-2">
            {{ session('success') }}
        </div>
    @endif

    <div class="card border-success shadow-sm p-4">
        <form method="POST" action="{{ route('settings.appearance.update') }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="row g-3">
                <div class="col-md-6">
                    <label for="brand_name" class="form-label"><strong>Brand Name</strong></label>
                    <input type="text" name="brand_name" id="brand_name" class="form-control"
                           value="{{ old('brand_name', $settings['brand_name'] ?? '') }}">
                    <small class="text-muted">This is the text label shown next to the logo in the navbar.</small>
                </div>

                <div class="col-md-6">
                    <label for="font" class="form-label"><strong>Font</strong></label>
                    <select name="font" id="font" class="form-select">
                        @foreach (['Nunito','Inter','Roboto','Poppins','Open Sans'] as $font)
                            <option value="{{ $font }}" {{ old('font', $settings['font']) === $font ? 'selected' : '' }}>
                                {{ $font }}
                            </option>
                        @endforeach
                    </select>
                    <small class="text-muted">We will auto-load the selected font for you.</small>
                </div>

                <div class="col-md-6">
                    <label for="bg_color" class="form-label"><strong>Background Color</strong></label>
                    <div class="input-group">
                        <input type="color" name="bg_color" id="bg_color" class="form-control form-control-color"
                               value="{{ old('bg_color', $settings['bg_color'] ?? '#f8f9fa') }}">
                        <input type="text" name="bg_color_hex" id="bg_color_hex" class="form-control" placeholder="#f8f9fa"
                               value="{{ old('bg_color', $settings['bg_color'] ?? '#f8f9fa') }}">
                    </div>
                    <small class="text-muted">Choose the main page background color.</small>
                </div>

                <div class="col-md-6">
                    <label for="nav_bg_color" class="form-label"><strong>Navbar Background Color</strong></label>
                    <div class="input-group">
                        <input type="color" name="nav_bg_color" id="nav_bg_color" class="form-control form-control-color"
                               value="{{ old('nav_bg_color', $settings['nav_bg_color'] ?? '#ffffff') }}">
                        <input type="text" name="nav_bg_color_hex" id="nav_bg_color_hex" class="form-control" placeholder="#ffffff"
                               value="{{ old('nav_bg_color', $settings['nav_bg_color'] ?? '#ffffff') }}">
                    </div>
                    <small class="text-muted">Choose the color for the main navigation bar.</small>
                </div>

                <div class="col-md-6">
                    <label for="logo" class="form-label"><strong>Logo (PNG)</strong></label>
                    <input type="file" name="logo" id="logo" class="form-control" accept="image/png">
                    @if(!empty($settings['logo_path']))
                        <div class="mt-2">
                            <img src="{{ asset('storage/'.$settings['logo_path']) }}" alt="Current Logo" style="height:50px">
                            <div><small class="text-muted">Current logo</small></div>
                        </div>
                    @endif
                    <small class="text-muted d-block">Maximum size 2MB. PNG only.</small>
                </div>

                <div class="col-md-6">
                    <label for="favicon" class="form-label"><strong>Icon (ico, png, jpg, gif)</strong></label>
                    <input type="file" name="favicon" id="favicon" class="form-control" accept="image/*">
                    @if(!empty($settings['favicon_path']))
                        <div class="mt-2">
                            <img src="{{ asset('storage/'.$settings['favicon_path']) }}" alt="Current Favicon" style="height:30px">
                            <div><small class="text-muted">Current favicon</small></div>
                        </div>
                    @endif
                    <small class="text-muted d-block">Maximum size 2MB. Accepts .ico, .png, .jpg, .gif.</small>
                </div>

                <div class="col-12 text-center mt-3">
                    <button type="submit" class="btn btn-success">
                        <i class="fa-solid fa-floppy-disk me-2"></i> Save Changes
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const bgColorInput = document.getElementById('bg_color');
        const bgColorHexInput = document.getElementById('bg_color_hex');
        const navBgColorInput = document.getElementById('nav_bg_color');
        const navBgColorHexInput = document.getElementById('nav_bg_color_hex');

        bgColorInput.addEventListener('input', (event) => {
            bgColorHexInput.value = event.target.value;
        });

        navBgColorInput.addEventListener('input', (event) => {
            navBgColorHexInput.value = event.target.value;
        });

        bgColorHexInput.addEventListener('input', (event) => {
            bgColorInput.value = event.target.value;
        });

        navBgColorHexInput.addEventListener('input', (event) => {
            navBgColorInput.value = event.target.value;
        });
    });
</script>
@endsection
