@extends('layouts.app')

@section('content')
<style>
    .form-wrapper { 
        min-height: 80vh; 
        display: flex; 
        justify-content: center; 
        align-items: center; 
        padding: 40px 20px;
        animation: fadeInUp 0.5s ease-out; 
    }

    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .form-container { 
        width: 100%;
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

    .form-header h1 { font-size: 28px; font-weight: 700; margin: 0 0 8px 0; }
    .form-header p { font-size: 15px; opacity: 0.9; margin: 0; }

    .form-container > form { padding: 40px 32px; display: flex; flex-direction: column; gap: 24px; }

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

    .alert-error i { font-size: 20px; flex-shrink: 0; }
    .error-list { list-style: none; padding: 0; margin: 0; }

    .form-group { display: flex; flex-direction: column; gap: 8px; }
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

    .form-input.error { border-color: #ef4444; background: rgba(239, 68, 68, 0.05); }
    .error-text { font-size: 13px; color: #ef4444; font-weight: 500; }

    .permissions-grid { 
        display: grid; 
        grid-template-columns: repeat(2, 1fr); 
        gap: 12px; 
    }

    .permission-check { 
        display: flex; 
        align-items: center; 
        padding: 12px 16px; 
        background: #f9fafb; 
        border: 2px solid #e5e7eb; 
        border-radius: 10px; 
        cursor: pointer; 
        transition: all 0.3s ease; 
    }

    .permission-check:hover { border-color: #0d3b66; background: rgba(13, 59, 102, 0.05); }

    .permission-check input[type="checkbox"] { 
        width: 20px; 
        height: 20px; 
        margin-right: 12px; 
        cursor: pointer; 
        accent-color: #0d3b66; 
    }

    .permission-label { cursor: pointer; font-size: 14px; font-weight: 500; color: #374151; flex: 1; }

    .form-actions { display: flex; gap: 12px; margin-top: 16px; }

    .btn { 
        padding: 12px 24px; 
        border: none; 
        border-radius: 10px; 
        font-weight: 700; 
        font-size: 14px; 
        cursor: pointer; 
        transition: all 0.3s ease; 
        display: flex; 
        align-items: center; 
        justify-content: center; 
        gap: 8px; 
        text-decoration: none; 
        flex: 1; 
    }

    .btn-submit { background: linear-gradient(135deg, #0d3b66 0%, #1a5490 100%); color: white; box-shadow: 0 4px 15px rgba(13, 59, 102, 0.3); }
    .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(13, 59, 102, 0.4); }
    .btn-cancel { background: #f3f4f6; color: #6b7280; border: 1px solid #e5e7eb; }
    .btn-cancel:hover { background: #e5e7eb; }

    @media (max-width: 768px) { 
        .form-header { padding: 30px 20px; } 
        .form-container > form { padding: 30px 20px; } 
        .permissions-grid { grid-template-columns: 1fr; } 
        .form-actions { flex-direction: column; } 
        .btn { width: 100%; flex: none; } 
    }
</style>

<div class="form-wrapper">
    <div class="form-container">
        <div class="form-header">
            <h1>Create New Role</h1>
            <p>Define a new role with permissions</p>
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

        <form method="POST" action="{{ route('roles.store') }}">
            @csrf

            <div class="form-group">
                <label for="name" class="form-label">Role Name <span class="required">*</span></label>
                <input type="text" name="name" id="name" class="form-input @error('name') error @enderror" value="{{ old('name') }}" placeholder="Enter role name">
                @error('name')<span class="error-text">{{ $message }}</span>@enderror
            </div>

            <div class="form-group">
                <label class="form-label">Permissions</label>
                <div class="permissions-grid">
                    @foreach($permission as $perm)
                        <label class="permission-check">
                            <input type="checkbox" name="permission[]" value="{{ $perm->id }}" {{ (is_array(old('permission')) && in_array($perm->id, old('permission'))) ? 'checked' : '' }}>
                            <span class="permission-label">{{ ucfirst($perm->name) }}</span>
                        </label>
                    @endforeach
                </div>
                @error('permission')<span class="error-text">{{ $message }}</span>@enderror
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-submit">
                    <i class="fas fa-check"></i> Create Role
                </button>
                <a href="{{ route('roles.index') }}" class="btn btn-cancel">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </div>
        </form>
    </div>
</div>
@endsection