@extends('layouts.app')

@section('content')
<style>
    .form-wrapper { min-height: 80vh; display: flex; justify-content: center; align-items: center; padding: 40px 20px; animation: fadeInUp 0.5s ease-out; }
    @keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
    .form-container { width: 100%; max-width: 700px; background: white; border-radius: 16px; overflow: hidden; box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1); border: 1px solid rgba(0, 0, 0, 0.05); }
    .form-header { background: linear-gradient(135deg, #0d3b66 0%, #1a5490 100%); color: white; padding: 40px 32px; }
    .form-header h1 { font-size: 28px; font-weight: 700; margin: 0 0 8px 0; }
    .form-header p { font-size: 15px; opacity: 0.9; margin: 0; }
    .form-container > form { padding: 40px 32px; display: flex; flex-direction: column; gap: 24px; }
    .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
    .form-group { display: flex; flex-direction: column; gap: 8px; }
    .form-label { font-size: 14px; font-weight: 700; color: #1f2937; text-transform: uppercase; letter-spacing: 0.3px; }
    .required { color: #ef4444; }
    .form-input { width: 100%; padding: 12px 16px; border: 2px solid #e5e7eb; border-radius: 10px; font-size: 15px; background: #f9fafb; transition: all 0.3s ease; }
    .form-input:focus { outline: none; border-color: #0d3b66; background: white; }
    .form-input.is-invalid { border-color: #ef4444; background: #fef2f2; }
    .form-actions { display: flex; gap: 16px; justify-content: flex-end; margin-top: 10px; }
    .btn { padding: 10px 24px; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; gap: 8px; text-decoration: none; font-size: 15px; transition: all 0.2s ease; }
    .btn-submit { background: #0d3b66; color: white; }
    .btn-submit:hover { background: #0a2d52; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(13, 59, 102, 0.2); color: white; }
    .btn-cancel { background: #f3f4f6; color: #4b5563; border: 1px solid #e5e7eb; }
    .btn-cancel:hover { background: #e5e7eb; color: #374151; }
    .password-hint { font-size: 12px; color: #6b7280; margin-top: 4px; line-height: 1.4; }
    
    .custom-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(13, 59, 102, 0.6); backdrop-filter: blur(4px); z-index: 9999; display: flex; justify-content: center; align-items: center; opacity: 0; visibility: hidden; transition: all 0.3s ease; }
    .custom-overlay.active { opacity: 1; visibility: visible; }
    .custom-modal { background: white; border-radius: 24px; width: 90%; max-width: 420px; padding: 32px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); transform: translateY(30px) scale(0.95); transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); }
    .custom-overlay.active .custom-modal { transform: translateY(0) scale(1); }
    .custom-modal-header { display: flex; flex-direction: column; align-items: center; text-align: center; margin-bottom: 24px; }
    .custom-modal-header .icon-circle { width: 72px; height: 72px; border-radius: 50%; background: #fef2f2; color: #ef4444; display: flex; justify-content: center; align-items: center; font-size: 32px; margin-bottom: 16px; border: 4px solid #fee2e2; }
    .custom-modal-header h3 { margin: 0; color: #111827; font-size: 22px; font-weight: 800; letter-spacing: -0.5px; }
    .custom-modal-body p { color: #4b5563; margin: 0 0 16px 0; text-align: center; font-size: 15px; }
    .custom-modal-body ul { list-style: none; padding: 0; margin: 0; display: flex; flex-direction: column; gap: 8px; }
    .custom-modal-body li { background: #f9fafb; color: #b91c1c; padding: 12px 16px; border-radius: 10px; font-size: 14px; border-left: 4px solid #ef4444; display: flex; align-items: flex-start; gap: 12px; line-height: 1.4; }
    .custom-modal-body li::before { content: "\f06a"; font-family: "Font Awesome 5 Free"; font-weight: 900; margin-top: 2px; }
    .custom-modal-footer { margin-top: 28px; display: flex; justify-content: center; }
    .custom-modal-footer .btn { width: 100%; padding: 14px; font-size: 16px; border-radius: 12px; }

    .roles-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 16px; margin-top: 8px; }
    .role-card-wrapper { cursor: pointer; position: relative; display: block; margin: 0; }
    .role-checkbox { position: absolute; opacity: 0; width: 0; height: 0; }
    .role-card { display: flex; align-items: center; gap: 12px; padding: 14px 16px; border: 2px solid #e5e7eb; border-radius: 12px; background: #f9fafb; transition: all 0.2s ease; position: relative; }
    .role-card-icon { width: 40px; height: 40px; border-radius: 10px; background: #e5e7eb; color: #4b5563; display: flex; align-items: center; justify-content: center; font-size: 18px; transition: all 0.2s ease; }
    .role-card-info { display: flex; flex-direction: column; gap: 2px; }
    .role-card-title { font-size: 15px; font-weight: 700; color: #1f2937; margin: 0; line-height: 1.2; }
    .role-card-desc { font-size: 11px; color: #6b7280; margin: 0; }
    .role-card-check { position: absolute; top: 8px; right: 8px; font-size: 16px; color: #10b981; opacity: 0; transition: all 0.2s ease; }
    .role-checkbox:checked + .role-card { border-color: #0d3b66; background: rgba(13, 59, 102, 0.05); }
    .role-checkbox:checked + .role-card .role-card-icon { background: #0d3b66; color: white; }
    .role-checkbox:checked + .role-card .role-card-check { opacity: 1; }

    @media (max-width: 768px) { .form-row { grid-template-columns: 1fr; } .form-actions { flex-direction: column; } .btn { width: 100%; } }
</style>

<div class="form-wrapper">
    <div class="form-container">
        <div class="form-header">
            <h1>Create Employee Account</h1>
            <p>Add a new employee and register their AI face scan.</p>
        </div>

        <form id="createForm" method="POST" action="{{ route('users.store') }}" enctype="multipart/form-data">
            @csrf
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Name <span class="required">*</span></label>
                    <input type="text" name="name" class="form-input {{ $errors->has('name') ? 'is-invalid' : '' }}" value="{{ old('name') }}" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Email <span class="required">*</span></label>
                    <input type="email" name="email" id="email" class="form-input {{ $errors->has('email') ? 'is-invalid' : '' }}" value="{{ old('email') }}" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Password <span class="required">*</span></label>
                    <input type="password" name="password" id="password" class="form-input {{ $errors->has('password') ? 'is-invalid' : '' }}" minlength="12" maxlength="64" required>
                    <span class="password-hint"><i class="fas fa-info-circle"></i> Min 12 chars, incl. uppercase, number, and symbol.</span>
                </div>
                <div class="form-group">
                    <label class="form-label">Confirm Password <span class="required">*</span></label>
                    <input type="password" name="confirm-password" id="confirm-password" class="form-input {{ $errors->has('password') ? 'is-invalid' : '' }}" minlength="12" maxlength="64" required>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Assign Role(s) <span class="required">*</span></label>
                <div class="roles-grid">
                    @foreach ($roles as $value => $label)
                        @if(!(Auth::user()->hasRole('HR') && $value === 'AdminIT'))
                            <label class="role-card-wrapper">
                                <input type="radio" name="roles[]" value="{{ $value }}" class="role-checkbox" {{ is_array(old('roles')) && in_array($value, old('roles')) ? 'checked' : '' }}>
                                <div class="role-card">
                                    <div class="role-card-icon">
                                        @if($value === 'AdminIT')
                                            <i class="fas fa-laptop-code"></i>
                                        @elseif($value === 'HR')
                                            <i class="fas fa-users-cog"></i>
                                        @else
                                            <i class="fas fa-user-tie"></i>
                                        @endif
                                    </div>
                                    <div class="role-card-info">
                                        <span class="role-card-title">{{ $label }}</span>
                                        <span class="role-card-desc">Assign as {{ $label }}</span>
                                    </div>
                                    <div class="role-card-check">
                                        <i class="fas fa-check-circle"></i>
                                    </div>
                                </div>
                            </label>
                        @endif
                    @endforeach
                </div>
            </div>

            <div class="form-group" style="padding: 24px 16px; border: 2px dashed #e5e7eb; border-radius: 10px; background: #f8fafc; text-align: center;">
                <label class="form-label" style="display: flex; align-items: center; justify-content: center; gap: 8px; margin-bottom: 16px;">
                    <i class="fas fa-camera"></i> Face Scan Baseline <span class="required">*</span>
                </label>

                <div id="imagePreviewContainer" style="display: none; margin: 0 auto 20px auto;">
                    <img id="imagePreview" src="" alt="Face Scan Preview" style="width: 200px; height: 200px; object-fit: cover; border-radius: 12px; border: 3px solid #0d3b66; box-shadow: 0 8px 20px rgba(0,0,0,0.15);">
                </div>

                <input type="file" name="picture" id="pictureInput" class="form-input {{ $errors->has('picture') ? 'is-invalid' : '' }}" accept="image/*" style="background: white; max-width: 350px; margin: 0 auto;" required>
                <small style="color: #64748b; margin-top: 12px; display: block;">
                    <i class="fas fa-lock"></i> This image will be heavily encrypted via DEK/KEK for AI verification only.
                </small>
            </div>

            <div class="form-actions">
                <a href="{{ route('users.index') }}" class="btn btn-cancel">Cancel</a>
                <button type="submit" class="btn btn-submit">
                    <i class="fas fa-user-plus"></i> Create Employee
                </button>
            </div>
        </form>
    </div>
</div>

<div id="customErrorOverlay" class="custom-overlay {{ $errors->any() ? 'active' : '' }}">
    <div class="custom-modal">
        <div class="custom-modal-header">
            <div class="icon-circle"><i class="fas fa-shield-alt"></i></div>
            <h3>Requirements Not Met</h3>
        </div>
        <div class="custom-modal-body">
            <p>Please review and correct the following highlighted fields to proceed:</p>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        <div class="custom-modal-footer">
            <button type="button" class="btn btn-submit" id="closeCustomModal">Got it, let me fix it</button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const pictureInput = document.getElementById('pictureInput');
    const imagePreview = document.getElementById('imagePreview');
    const imagePreviewContainer = document.getElementById('imagePreviewContainer');

    if (pictureInput) {
        pictureInput.addEventListener('change', function(event) {
            const [file] = event.target.files;
            if (file) {
                imagePreview.src = URL.createObjectURL(file);
                imagePreviewContainer.style.display = 'block';
            } else {
                imagePreview.src = '';
                imagePreviewContainer.style.display = 'none';
            }
        });
    }

    const closeBtn = document.getElementById('closeCustomModal');
    if (closeBtn) {
        closeBtn.addEventListener('click', function() {
            document.getElementById('customErrorOverlay').classList.remove('active');
        });
    }
});
</script>
@endsection