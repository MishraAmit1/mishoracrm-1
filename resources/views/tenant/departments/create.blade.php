@extends('layouts.app')
@section('title', 'Add Department')

@section('content')

<div class="page-head">
    <div>
        <div style="font-size:13px;color:var(--text-300);margin-bottom:4px">
            <a href="{{ route('tenant.departments.index') }}" style="color:var(--text-300);text-decoration:none">Departments</a>
            <span style="margin:0 6px">›</span> Add
        </div>
        <div class="page-title">Add Department</div>
    </div>
    <a href="{{ route('tenant.departments.index') }}" class="btn btn-secondary">← Back</a>
</div>

<div style="max-width:560px">
    <div style="background:var(--bg-surface);border:1px solid var(--border-default);border-radius:var(--r-lg);overflow:hidden">
        <form method="POST" action="{{ route('tenant.departments.store') }}">
            @csrf
            <div style="padding:24px;display:flex;flex-direction:column;gap:18px">

                <div style="display:flex;flex-direction:column;gap:7px">
                    <label style="font-size:12.5px;font-weight:600;color:var(--text-200);text-transform:uppercase;letter-spacing:.3px">
                        Department Name <span style="color:var(--red)">*</span>
                    </label>
                    <input type="text" name="name"
                           style="padding:10px 13px;background:var(--bg-input);border:1.5px solid {{ $errors->has('name') ? 'var(--red)' : 'var(--border-default)' }};border-radius:var(--r-sm);color:var(--text-100);font-family:var(--font);font-size:14px;outline:none"
                           placeholder="e.g. Sales, Marketing, IT..."
                           value="{{ old('name') }}" required/>
                    @error('name')
                    <span style="font-size:12px;color:var(--red)">{{ $message }}</span>
                    @enderror
                </div>

                <div style="display:flex;flex-direction:column;gap:7px">
                    <label style="font-size:12.5px;font-weight:600;color:var(--text-200);text-transform:uppercase;letter-spacing:.3px">
                        Description
                    </label>
                    <textarea name="description" rows="3"
                              style="padding:10px 13px;background:var(--bg-input);border:1.5px solid var(--border-default);border-radius:var(--r-sm);color:var(--text-100);font-family:var(--font);font-size:14px;outline:none;resize:vertical"
                              placeholder="What does this department do...">{{ old('description') }}</textarea>
                </div>

            </div>

            <div style="display:flex;justify-content:flex-end;gap:10px;padding:16px 24px;background:var(--bg-elevated);border-top:1px solid var(--border-subtle)">
                <a href="{{ route('tenant.departments.index') }}" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="width:15px;height:15px">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                    </svg>
                    Create Department
                </button>
            </div>
        </form>
    </div>
</div>

@endsection