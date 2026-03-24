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
    .form-input { width: 100%; padding: 12px 16px; border: 2px solid #e5e7eb; border-radius: 10px; font-size: 15px; background: #f9fafb; }
    .form-input:focus { outline: none; border-color: #0d3b66; background: white; }
    .form-actions { display: flex; gap: 16px; justify-content: flex-end; margin-top: 10px; }
    .btn { padding: 10px 24px; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; gap: 8px; text-decoration: none; font-size: 15px; transition: all 0.2s ease; }
    .btn-submit { background: #0d3b66; color: white; }
    .btn-submit:hover { background: #0a2d52; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(13, 59, 102, 0.2); color: white; }
    .btn-cancel { background: #f3f4f6; color: #4b5563; border: 1px solid #e5e7eb; }
    .btn-cancel:hover { background: #e5e7eb; color: #374151; }
    
    @media (max-width: 768px) { .form-row { grid-template-columns: 1fr; } .form-actions { flex-direction: column; } .btn { width: 100%; } }
</style>

<div class="form-wrapper">
    <div class="form-container">
        <div class="form-header">
            <h1>Create Employee Account</h1>
            <p>Add a new employee and register their AI face scan.</p>
        </div>

        <form method="POST" action="{{ route('users.store') }}" enctype="multipart/form-data">
            @csrf
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Name <span class="required">*</span></label>
                    <input type="text" name="name" class="form-input" value="{{ old('name') }}" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Email <span class="required">*</span></label>
                    <input type="email" name="email" class="form-input" value="{{ old('email') }}" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Password <span class="required">*</span></label>
                    <input type="password" name="password" class="form-input" minlength="6" maxlength="18" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Confirm Password <span class="required">*</span></label>
                    <input type="password" name="confirm-password" class="form-input" minlength="6" maxlength="18" required>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Assign Role(s) <span class="required">*</span></label>
                <select name="roles[]" class="form-input" multiple required>
                    @foreach ($roles as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group" style="padding: 24px 16px; border: 2px dashed #e5e7eb; border-radius: 10px; background: #f8fafc; text-align: center;">
                <label class="form-label" style="display: flex; align-items: center; justify-content: center; gap: 8px; margin-bottom: 16px;">
                    <i class="fas fa-camera"></i> Face Scan Baseline <span class="required">*</span>
                </label>

                <div id="imagePreviewContainer" style="display: none; margin: 0 auto 20px auto;">
                    <img id="imagePreview" src="" alt="Face Scan Preview" style="width: 200px; height: 200px; object-fit: cover; border-radius: 12px; border: 3px solid #0d3b66; box-shadow: 0 8px 20px rgba(0,0,0,0.15);">
                </div>

                <input type="file" name="picture" id="pictureInput" class="form-input" accept="image/*" style="background: white; max-width: 350px; margin: 0 auto;" required>
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

<div class="modal fade" id="errorModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 16px;">
            <div class="modal-header">
                <h5 class="modal-title" style="color: #ef4444;"><i class="fas fa-exclamation-circle"></i> Validation Errors</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <ul style="color: #4b5563; padding-left: 20px;">
                    @foreach ($errors->all() as $error)
                        <li style="margin-bottom: 8px;">{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

@if ($errors->any())
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var errorModal = new bootstrap.Modal(document.getElementById('errorModal'));
        errorModal.show();
    });
</script>
@endif

<script>
document.addEventListener('DOMContentLoaded', function() {
    const pictureInput = document.getElementById('pictureInput');
    const imagePreview = document.getElementById('imagePreview');
    const imagePreviewContainer = document.getElementById('imagePreviewContainer');

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
});
</script>
@endsection