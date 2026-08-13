@extends('layouts.app')
@section('title', 'Edit Template — ' . $template->name)

@push('styles')
<style>
.qtt-card { background:var(--bg-surface); border:1px solid var(--border-default); border-radius:14px; padding:20px; max-width:640px; margin-top:16px; }
.qtt-field { display:flex; flex-direction:column; gap:5px; margin-bottom:14px; }
.qtt-label { font-size:11.5px; font-weight:600; color:var(--text-200); text-transform:uppercase; letter-spacing:.5px; }
.qtt-input { width:100%; padding:9px 12px; background:var(--bg-input); border:1.5px solid var(--border-default); border-radius:8px; color:var(--text-100); font-family:var(--font); font-size:13.5px; outline:none; }
.qtt-input:focus { border-color:var(--accent); }
textarea.qtt-input { resize:vertical; min-height:100px; }
</style>
@endpush

@section('content')

<div class="page-head">
    <div class="page-title">Edit Template</div>
    <a href="{{ route('tenant.quotation-terms-templates.index') }}" class="btn btn-secondary">
        <i class="ti ti-arrow-left" style="font-size:14px"></i> Back
    </a>
</div>

<div class="qtt-card">
    <form method="POST" action="{{ route('tenant.quotation-terms-templates.update', $template->id) }}">
        @csrf @method('PUT')
        <div class="qtt-field">
            <label class="qtt-label">Name <span style="color:var(--red)">*</span></label>
            <input type="text" name="name" class="qtt-input" value="{{ old('name', $template->name) }}" required/>
            @error('name')<span style="font-size:12px;color:var(--red)">{{ $message }}</span>@enderror
        </div>
        <div class="qtt-field">
            <label class="qtt-label">Terms &amp; Conditions</label>
            <textarea name="terms" class="qtt-input" rows="8">{{ old('terms', $template->terms) }}</textarea>
        </div>
        <div class="qtt-field">
            <label class="qtt-label">Notes</label>
            <textarea name="notes" class="qtt-input" rows="4">{{ old('notes', $template->notes) }}</textarea>
        </div>
        <div style="display:flex;gap:8px">
            <a href="{{ route('tenant.quotation-terms-templates.index') }}" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary">Save Changes</button>
        </div>
    </form>
</div>

@endsection
