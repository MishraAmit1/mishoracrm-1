@extends('layouts.app')
@section('title', 'Edit Task Template')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.19.0/dist/tabler-icons.min.css"/>
<style>
.tt-form-wrap { font-family: var(--font), sans-serif; max-width:640px; }
.tt-card { background:var(--bg-surface); border:1px solid var(--border-default); border-radius:14px; padding:22px; }
.tt-field { display:flex; flex-direction:column; gap:6px; margin-bottom:16px; }
.tt-label { font-size:11.5px; font-weight:600; color:var(--text-200); text-transform:uppercase; letter-spacing:.5px; }
.tt-input { width:100%; padding:10px 12px; border-radius:8px; border:1.5px solid var(--border-default); background:var(--bg-input); color:var(--text-100); font-size:13.5px; outline:none; transition:.15s; font-family:inherit; }
.tt-input:focus { border-color:var(--accent); box-shadow:0 0 0 3px var(--accent-dim); }
.tt-hint { font-size:12px; color:var(--text-400); }
.tt-footer { display:flex; justify-content:flex-end; gap:8px; margin-top:20px; }
</style>
@endpush

@section('content')
<div class="tt-form-wrap">

    <div class="page-head">
        <div>
            <div style="font-size:12px;color:var(--text-300);margin-bottom:4px">
                <a href="{{ route('tenant.task-templates.index') }}" style="text-decoration:none;color:inherit">Task Templates</a>
                › Edit
            </div>
            <div class="page-title">Edit Task Template</div>
        </div>
    </div>

    <form method="POST" action="{{ route('tenant.task-templates.update', $template->id) }}">
        @csrf
        @method('PUT')
        <div class="tt-card">

            <div class="tt-field">
                <label class="tt-label">Template Name *</label>
                <input type="text" name="name" class="tt-input" value="{{ old('name', $template->name) }}" required maxlength="255">
                @error('name') <span class="tt-hint" style="color:var(--red)">{{ $message }}</span> @enderror
            </div>

            <div class="tt-field">
                <label class="tt-label">Default Task Title / Description</label>
                <textarea name="description" class="tt-input" rows="3">{{ old('description', $template->description) }}</textarea>
            </div>

            <div class="tt-field">
                <label class="tt-label">Default Priority</label>
                <select name="default_priority" class="tt-input">
                    @foreach(config('task_fields.priorities') as $key => $p)
                    <option value="{{ $key }}" {{ old('default_priority', $template->default_priority) === $key ? 'selected' : '' }}>{{ $p['label'] }}</option>
                    @endforeach
                </select>
            </div>

            <div class="tt-field">
                <label class="tt-label">Default Tags</label>
                <input type="text" name="default_tags" class="tt-input" value="{{ old('default_tags', implode(', ', $template->default_tags ?? [])) }}" placeholder="e.g. onboarding, urgent (comma separated)">
            </div>

            <div class="tt-field">
                <label class="tt-label">Checklist Items</label>
                <textarea name="checklist_items" class="tt-input" rows="6">{{ old('checklist_items', implode("\n", $template->checklist_items ?? [])) }}</textarea>
                <span class="tt-hint">Each line becomes a checklist item on tasks created from this template.</span>
            </div>

        </div>

        <div class="tt-footer">
            <a href="{{ route('tenant.task-templates.index') }}" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary"><i class="ti ti-device-floppy"></i> Save Changes</button>
        </div>
    </form>

</div>
@endsection
