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
    .alert-error { background: linear-gradient(135deg, rgba(239, 68, 68, 0.1) 0%, rgba(239, 68, 68, 0.05) 100%); border-left: 4px solid #ef4444; border-radius: 10px; padding: 16px 20px; display: flex; gap: 12px; margin-bottom: 24px; color: #991b1b; }
    .alert-error i { font-size: 20px; flex-shrink: 0; }
    .error-list { list-style: none; padding: 0; margin: 0; }
    .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
    .form-group { display: flex; flex-direction: column; gap: 8px; }
    .form-label { font-size: 14px; font-weight: 700; color: #1f2937; text-transform: uppercase; letter-spacing: 0.3px; }
    .required { color: #ef4444; }
    .form-input { width: 100%; padding: 12px 16px; border: 2px solid #e5e7eb; border-radius: 10px; font-size: 15px; font-family: inherit; transition: all 0.3s ease; background: #f9fafb; }
    .form-input:focus { outline: none; border-color: #0d3b66; background: white; box-shadow: 0 0 0 4px rgba(13, 59, 102, 0.1); }
    .form-input.error { border-color: #ef4444; background: rgba(239, 68, 68, 0.05); }
    .error-text { font-size: 13px; color: #ef4444; font-weight: 500; }
    .password-hint { font-size: 12px; color: #6b7280; margin-top: -4px; }
    .form-actions { display: flex; gap: 12px; margin-top: 16px; }
    .btn { padding: 12px 24px; border: none; border-radius: 10px; font-weight: 700; font-size: 14px; cursor: pointer; transition: all 0.3s ease; display: flex; align-items: center; justify-content: center; gap: 8px; text-decoration: none; flex: 1; }
    .btn-submit { background: linear-gradient(135deg, #0d3b66 0%, #1a5490 100%); color: white; box-shadow: 0 4px 15px rgba(13, 59, 102, 0.3); }
    .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(13, 59, 102, 0.4); }
    .btn-cancel { background: #f3f4f6; color: #6b7280; border: 1px solid #e5e7eb; }
    .btn-cancel:hover { background: #e5e7eb; }
    @media (max-width: 768px) { .form-row { grid-template-columns: 1fr; } .form-actions { flex-direction: column; } }
</style>

<div class="form-wrapper">
    <div class="form-container">
        <div class="form-header">
            <h1>Create Employee Account</h1>
            <p>Add a new employee to the system</p>
        </div>

        @if ($errors->any())
            <div class="alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <div>
                    <strong>Validation Errors:</strong>
                    <ul class="error-list">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        <form method="POST" action="{{ route('users.store') }}">
            @csrf

            <div class="form-row">
                <div class="form-group">
                    <label for="name" class="form-label">Name <span class="required">*</span></label>
                    <input type="text" name="name" id="name" class="form-input @error('name') error @enderror" value="{{ old('name') }}" required>
                    @error('name')<span class="error-text">{{ $message }}</span>@enderror
                </div>

                <div class="form-group">
                    <label for="email" class="form-label">Email <span class="required">*</span></label>
                    <input type="email" name="email" id="email" class="form-input @error('email') error @enderror" value="{{ old('email') }}" required>
                    @error('email')<span class="error-text">{{ $message }}</span>@enderror
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="password" class="form-label">Password <span class="required">*</span></label>
                    <input type="password" name="password" id="password" 
                           class="form-input @error('password') error @enderror" 
                           minlength="6" maxlength="18" required>
                    <span class="password-hint">Between 6 and 18 characters</span>
                    @error('password')<span class="error-text">{{ $message }}</span>@enderror
                </div>

                <div class="form-group">
                    <label for="confirm-password" class="form-label">Confirm Password <span class="required">*</span></label>
                    <input type="password" name="confirm-password" id="confirm-password" 
                           class="form-input @error('confirm-password') error @enderror" 
                           minlength="6" maxlength="18" required>
                    @error('confirm-password')<span class="error-text">{{ $message }}</span>@enderror
                </div>
            </div>

            <div class="form-group">
                <label for="roles" class="form-label">Assign Role(s) <span class="required">*</span></label>
                <select name="roles[]" id="roles" class="form-input @error('roles') error @enderror" multiple required>
                    @foreach ($roles as $value => $label)
                        <option value="{{ $value }}" {{ in_array($value, old('roles', [])) ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
                @error('roles')<span class="error-text">{{ $message }}</span>@enderror
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-submit">
                    <i class="fas fa-user-plus"></i> Create Employee
                </button>
                <a href="{{ route('users.index') }}" class="btn btn-cancel">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </div>
        </form>
    </div>
</div>
@endsection