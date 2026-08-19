@extends('layouts.app')
@section('title', 'Edit Permission')

@push('styles')
<style>
.form-wrap { max-width:560px; margin:0 auto; }
.form-card { background:var(--bg-card); border:1px solid var(--border-subtle); border-radius:var(--r-lg); padding:28px; }
.form-card h2 { font-size:18px; font-weight:700; color:var(--text-100); margin-bottom:24px; }
.form-group { margin-bottom:18px; }
.form-label { display:block; font-size:13px; font-weight:600; color:var(--text-300); margin-bottom:6px; }
.form-label span.req { color:#ef4444; margin-left:2px; }
.form-control {
    width:100%; padding:9px 12px;
    background:var(--bg-input); border:1px solid var(--border-subtle);
    border-radius:var(--r-md); color:var(--text-100); font-size:13.5px; outline:none;
    box-sizing:border-box;
}
.form-control:focus { border-color:var(--accent); }
.form-control.is-invalid { border-color:#ef4444; }
.invalid-feedback { font-size:12px; color:#ef4444; margin-top:4px; }
.form-hint { font-size:12px; color:var(--text-400); margin-top:4px; }
.form-footer { display:flex; gap:10px; margin-top:24px; }
.btn-primary { padding:10px 22px; background:var(--accent); color:#fff; border:none; border-radius:var(--r-md); font-size:14px; font-weight:700; cursor:pointer; text-decoration:none; }
.btn-secondary { padding:10px 18px; background:var(--bg-input); color:var(--text-200); border:1px solid var(--border-subtle); border-radius:var(--r-md); font-size:14px; font-weight:600; cursor:pointer; text-decoration:none; }
.back-link { display:flex; align-items:center; gap:6px; color:var(--text-400); font-size:13px; text-decoration:none; margin-bottom:18px; }
.back-link:hover { color:var(--text-100); }
.name-preview { font-family:monospace; font-size:14px; font-weight:700; color:var(--accent); background:var(--bg-input); border:1px dashed var(--border-subtle); border-radius:var(--r-md); padding:10px 12px; }
</style>
@endpush

@section('content')
<div class="page-content">
<div class="form-wrap">

    <a href="{{ route('superadmin.permissions.index') }}" class="back-link">
        <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
        Back to Roles & Permissions
    </a>

    <div class="form-card">
        <h2>Edit Permission</h2>

        <form action="{{ route('superadmin.permissions.update', $permission) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="form-group">
                <label class="form-label">Module <span class="req">*</span></label>
                <input type="text" name="module" id="module-input" value="{{ old('module', $module) }}"
                       list="module-list" class="form-control @error('module') is-invalid @enderror"
                       placeholder="e.g. leads, invoices, campaigns" required>
                <datalist id="module-list">
                    @foreach($modules as $m)
                        <option value="{{ $m }}">
                    @endforeach
                </datalist>
                @error('module')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="form-group">
                <label class="form-label">Action <span class="req">*</span></label>
                <input type="text" name="action" id="action-input" value="{{ old('action', $action) }}"
                       class="form-control @error('action') is-invalid @enderror"
                       placeholder="e.g. view_own, create, export" required>
                <div class="form-hint">Lowercase letters, numbers and underscores only.</div>
                @error('action')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="form-group">
                <label class="form-label">Permission Name Preview</label>
                <div class="name-preview" id="name-preview">{{ $permission->name }}</div>
                <div class="form-hint">Renaming keeps existing role assignments intact.</div>
            </div>

            <div class="form-footer">
                <button type="submit" class="btn-primary">Save Changes</button>
                <a href="{{ route('superadmin.permissions.index') }}" class="btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
</div>
@endsection

@push('scripts')
<script>
const moduleInput = document.getElementById('module-input');
const actionInput = document.getElementById('action-input');
const preview      = document.getElementById('name-preview');

function updatePreview() {
    const mod = (moduleInput.value || 'module').trim();
    const act = (actionInput.value || 'action').trim();
    preview.textContent = mod + '.' + act;
}

moduleInput.addEventListener('input', updatePreview);
actionInput.addEventListener('input', updatePreview);
</script>
@endpush
