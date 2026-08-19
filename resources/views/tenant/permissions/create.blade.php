@extends('layouts.app')
@section('title', 'Add Permission')

@push('styles')
<style>
.form-card {
    background: var(--bg-surface);
    border: 1px solid var(--border-default);
    border-radius: var(--r-lg);
    overflow: hidden;
    max-width: 560px;
}
.fc-sec { padding: 22px 24px; }
.field { display: flex; flex-direction: column; gap: 7px; margin-bottom: 18px; }
.fl    { font-size: 12px; font-weight: 600; color: var(--text-200); text-transform: uppercase; letter-spacing: .4px; }
.req   { color: var(--red); margin-left: 2px; }
.fi {
    padding: 9px 13px;
    background: var(--bg-input);
    border: 1.5px solid var(--border-default);
    border-radius: var(--r-sm);
    color: var(--text-100);
    font-family: var(--font);
    font-size: 13.5px;
    outline: none;
    transition: border-color .15s, box-shadow .15s;
    width: 100%;
    box-sizing: border-box;
}
.fi:focus { border-color: var(--accent); box-shadow: 0 0 0 3px var(--accent-dim); }
.fi.is-error { border-color: var(--red); }
.fe    { font-size: 12px; color: var(--red); font-weight: 500; }
.fhint { font-size: 12px; color: var(--text-400); }
.slug-preview {
    font-size: 13px; color: var(--accent); font-weight: 700;
    font-family: var(--mono);
    padding: 8px 12px;
    background: var(--bg-elevated);
    border-radius: var(--r-sm);
}
</style>
@endpush

@section('content')

<div class="page-head">
    <div>
        <div style="font-size:12px;color:var(--text-300);margin-bottom:4px;display:flex;align-items:center;gap:6px">
            <a href="{{ route('tenant.roles.index') }}" style="color:var(--text-300);text-decoration:none">Roles & Permissions</a>
            <span style="opacity:.4">›</span>
            <span>Add Permission</span>
        </div>
        <div class="page-title">Add Permission</div>
    </div>
    <a href="{{ route('tenant.roles.index') }}" class="btn btn-secondary">
        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M19 12H5M12 5l-7 7 7 7"/>
        </svg>
        Back to Roles
    </a>
</div>

@if($errors->any())
<div style="padding:12px 16px;background:var(--red-dim);border:1px solid rgba(224,82,82,.2);border-radius:var(--r-sm);margin-bottom:16px;font-size:13px;color:var(--red)">
    <strong>Please fix the following:</strong>
    <ul style="margin:6px 0 0 16px;padding:0">
        @foreach($errors->all() as $err)
        <li>{{ $err }}</li>
        @endforeach
    </ul>
</div>
@endif

<div style="padding:12px 16px;background:var(--bg-elevated);border:1px solid var(--border-subtle);border-radius:var(--r-sm);font-size:13px;color:var(--text-300);margin-bottom:20px;display:flex;gap:10px;align-items:flex-start">
    <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" style="width:16px;height:16px;flex-shrink:0;margin-top:1px;color:var(--accent)">
        <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z"/>
    </svg>
    <div>
        New permissions are added straight to your role builder — no waiting on the developer for new modules.
        <strong style="color:var(--text-200)">Note:</strong> renaming or deleting permissions isn't available here since permissions are shared across all tenants — reach out to support for that.
    </div>
</div>

<form method="POST" action="{{ route('tenant.permissions.store') }}" id="permForm" novalidate>
    @csrf

    <div class="form-card">
        <div class="fc-sec">

            <div class="field">
                <label class="fl">Module <span class="req">*</span></label>
                <input type="text" name="module" id="moduleInput"
                       class="fi {{ $errors->has('module') ? 'is-error' : '' }}"
                       list="moduleList"
                       placeholder="e.g. leads, invoices, campaigns"
                       value="{{ old('module') }}"
                       oninput="updatePreview()"
                       autocomplete="off" required />
                <datalist id="moduleList">
                    @foreach($modules as $m)
                        <option value="{{ $m }}">
                    @endforeach
                </datalist>
                <span class="fhint">Pick an existing module or type a new one</span>
                @error('module') <span class="fe">{{ $message }}</span> @enderror
            </div>

            <div class="field">
                <label class="fl">Action <span class="req">*</span></label>
                <input type="text" name="action" id="actionInput"
                       class="fi {{ $errors->has('action') ? 'is-error' : '' }}"
                       placeholder="e.g. view_own, create, export"
                       value="{{ old('action') }}"
                       oninput="updatePreview()"
                       autocomplete="off" required />
                <span class="fhint">Lowercase letters, numbers and underscores only</span>
                @error('action') <span class="fe">{{ $message }}</span> @enderror
            </div>

            <div class="field">
                <label class="fl">Permission Name</label>
                <div class="slug-preview" id="namePreview">module.action</div>
            </div>

        </div>
    </div>

    <div style="display:flex;gap:10px;margin-top:20px">
        <button type="submit" class="btn btn-primary">Add Permission</button>
        <a href="{{ route('tenant.roles.index') }}" class="btn btn-secondary">Cancel</a>
    </div>
</form>

@endsection

@push('scripts')
<script>
function updatePreview() {
    const mod = (document.getElementById('moduleInput').value || 'module').trim();
    const act = (document.getElementById('actionInput').value || 'action').trim();
    document.getElementById('namePreview').textContent = mod + '.' + act;
}
updatePreview();
</script>
@endpush
