@extends('layouts.app')
@section('title', 'Add Staff')

@push('styles')
<style>
.form-card { background:var(--bg-surface); border:1px solid var(--border-default); border-radius:var(--r-lg); overflow:hidden; max-width:780px; }
.form-section { padding:24px; border-bottom:1px solid var(--border-subtle); }
.form-section:last-child { border-bottom:none; }
.fs-title { font-size:13px; font-weight:700; color:var(--text-100); text-transform:uppercase; letter-spacing:.4px; margin-bottom:4px; }
.fs-sub   { font-size:12.5px; color:var(--text-300); margin-bottom:20px; }
.form-grid { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
.span-2 { grid-column:1/-1; }
@media(max-width:640px) { .form-grid { grid-template-columns:1fr; } .span-2 { grid-column:1; } }

.field { display:flex; flex-direction:column; gap:7px; }
.field-label { font-size:12.5px; font-weight:600; color:var(--text-200); text-transform:uppercase; letter-spacing:.3px; }
.req { color:var(--red); margin-left:2px; }
.field-input {
    padding:10px 13px; background:var(--bg-input);
    border:1.5px solid var(--border-default); border-radius:var(--r-sm);
    color:var(--text-100); font-family:var(--font); font-size:14px; outline:none;
    transition:border-color .15s var(--ease), box-shadow .15s var(--ease);
}
.field-input:focus { border-color:var(--accent); box-shadow:0 0 0 3px var(--accent-dim); }
.field-input::placeholder { color:var(--text-400); }
.field-input.is-error { border-color:var(--red); }
.field-select { -webkit-appearance:none; cursor:pointer; }
.field-error { font-size:12px; color:var(--red); font-weight:500; }
.field-hint  { font-size:12px; color:var(--text-400); }

.pw-wrap { position:relative; }
.pw-wrap .field-input { padding-right:40px; }
.pw-toggle { position:absolute; right:12px; top:50%; transform:translateY(-50%); background:none; border:none; cursor:pointer; color:var(--text-300); }
.pw-toggle svg { width:15px; height:15px; }

.role-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:8px; }
@media(max-width:500px) { .role-grid { grid-template-columns:1fr 1fr; } }
.role-card {
    padding:14px 10px; border-radius:var(--r-md);
    border:1.5px solid var(--border-default);
    cursor:pointer; transition:all .15s var(--ease);
    text-align:center; background:none;
}
.role-card input { display:none; }
.role-card-name { font-size:12.5px; font-weight:700; margin-bottom:3px; color:var(--text-200); }
.role-card-desc { font-size:11px; color:var(--text-400); }

.form-actions {
    display:flex; align-items:center; justify-content:flex-end; gap:10px;
    padding:20px 24px; background:var(--bg-elevated);
    border-top:1px solid var(--border-subtle);
}
</style>
@endpush

@section('content')

@php
    $cfg      = config('staff');
    $types    = $cfg['employment_types'];
    $sections = $cfg['form_fields'];

    // Roles come from the DB now (system + custom), not a static config list.
    $roleKind = fn($k) => match($k) {
        'admin' => ['color' => 'red',   'bg' => 'red-dim'],
        'staff' => ['color' => 'green', 'bg' => 'green-dim'],
        default => ['color' => 'accent','bg' => 'accent-dim'],
    };
    $defaultRole = $assignableRoles->firstWhere('kind', 'staff')['name']
        ?? ($assignableRoles->first()['name'] ?? '');
    $selRole = old('role', $defaultRole);
@endphp

<div class="page-head">
    <div>
        <div style="font-size:13px;color:var(--text-300);margin-bottom:4px">
            <a href="{{ route('tenant.staffs.index') }}" style="color:var(--text-300);text-decoration:none">Staff</a>
            <span style="margin:0 6px">›</span> Add Staff
        </div>
        <div class="page-title">Add New Staff</div>
    </div>
    <a href="{{ route('tenant.staffs.index') }}" class="btn btn-secondary">← Back</a>
</div>

<div class="form-card">
    <form method="POST" action="{{ route('tenant.staffs.store') }}" novalidate>
        @csrf

        {{-- ── Account section (from config) ──────────────────── --}}
        @php $sec = $sections['account']; @endphp
        <div class="form-section">
            <div class="fs-title">{{ $sec['title'] }}</div>
            <div class="fs-sub">{{ $sec['sub'] }}</div>
            <div class="form-grid">
                @foreach($sec['fields'] as $f)
                <div class="field {{ ($f['span'] ?? 1) > 1 ? 'span-2' : '' }}">
                    <label class="field-label">
                        {{ $f['label'] }}
                        @if($f['required'])<span class="req">*</span>@endif
                    </label>

                    @if($f['type'] === 'password')
                    <div class="pw-wrap">
                        <input type="password" name="{{ $f['name'] }}" id="{{ $f['name'] }}"
                               class="field-input {{ $errors->has($f['name']) ? 'is-error':'' }}"
                               placeholder="{{ $f['placeholder'] }}"
                               {{ $f['required'] ? 'required':'' }}/>
                        <button type="button" class="pw-toggle" onclick="togglePw('{{ $f['name'] }}',this)">
                            <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </button>
                    </div>
                    @else
                    <input type="{{ $f['type'] }}" name="{{ $f['name'] }}"
                           class="field-input {{ $errors->has($f['name']) ? 'is-error':'' }}"
                           placeholder="{{ $f['placeholder'] }}"
                           value="{{ old($f['name']) }}"
                           {{ $f['required'] ? 'required':'' }}/>
                    @endif

                    @error($f['name'])<span class="field-error">{{ $message }}</span>@enderror
                </div>
                @endforeach
            </div>
        </div>

        {{-- ── Role section (from config) ───────────────────────── --}}
        <div class="form-section">
            <div class="fs-title">Role & Permissions <span class="req">*</span></div>
            <div class="fs-sub">What access level should this staff member have</div>
            <input type="hidden" name="role" id="roleInput" value="{{ $selRole }}"/>
            <div class="role-grid">
                @foreach($assignableRoles as $r)
                @php $rc = $roleKind($r['kind']); $active = $selRole === $r['name']; @endphp
                <div class="role-card" data-role="{{ $r['name'] }}"
                     data-color="{{ $rc['color'] }}" data-bg="{{ $rc['bg'] }}"
                     onclick="selectRole('{{ $r['name'] }}')"
                     style="{{ $active ? 'border-color:var(--'.$rc['color'].');background:var(--'.$rc['bg'].')' : '' }}">
                    <input type="radio" value="{{ $r['name'] }}" {{ $active ? 'checked':'' }}/>
                    <div class="role-card-name" style="{{ $active ? 'color:var(--'.$rc['color'].')' : '' }}">
                        {{ $r['label'] }}
                    </div>
                    <div class="role-card-desc">{{ $r['description'] ?: 'Custom role' }}</div>
                </div>
                @endforeach
            </div>
            <p style="font-size:12px;color:var(--text-400);margin-top:10px">
                Need a different role? <a href="{{ route('tenant.roles.index') }}" style="color:var(--accent)">Create one in Roles &amp; Permissions</a> — it will appear here.
            </p>
            @error('role')<p style="font-size:12px;color:var(--red);margin-top:8px">{{ $message }}</p>@enderror
        </div>

        {{-- ── Job section (from config) ────────────────────────── --}}
        @php $sec = $sections['job']; @endphp
        <div class="form-section">
            <div class="fs-title">{{ $sec['title'] }}</div>
            <div class="fs-sub">{{ $sec['sub'] }}</div>
            <div class="form-grid">

                {{-- Department — dynamic --}}
                <div class="field">
                    <label class="field-label">Department</label>
                    <select name="department_id" class="field-input field-select">
                        <option value="">No Department</option>
                        @foreach($departments as $dept)
                        <option value="{{ $dept->id }}" {{ old('department_id') == $dept->id ? 'selected':'' }}>
                            {{ $dept->name }}
                        </option>
                        @endforeach
                    </select>
                </div>

                {{-- Employment Type — from config --}}
                <div class="field">
                    <label class="field-label">Employment Type</label>
                    <select name="employment_type" class="field-input field-select">
                        @foreach($types as $key => $et)
                        <option value="{{ $key }}"
                            {{ old('employment_type','full_time') === $key ? 'selected':'' }}>
                            {{ $et['label'] }}
                        </option>
                        @endforeach
                    </select>
                </div>

                {{-- Config-driven job fields --}}
                @foreach($sec['fields'] as $f)
                <div class="field {{ ($f['span'] ?? 1) > 1 ? 'span-2' : '' }}">
                    <label class="field-label">
                        {{ $f['label'] }}
                        @if($f['required'])<span class="req">*</span>@endif
                    </label>
                    <input type="{{ $f['type'] }}" name="{{ $f['name'] }}"
                           class="field-input {{ $errors->has($f['name']) ? 'is-error':'' }}"
                           placeholder="{{ $f['placeholder'] }}"
                           value="{{ old($f['name'], $f['name'] === 'joining_date' ? now()->format('Y-m-d') : '') }}"
                           {{ $f['type'] === 'number' ? 'min=0 step=100':'' }}
                           {{ $f['required'] ? 'required':'' }}/>
                    @if(!empty($f['hint']))<span class="field-hint">{{ $f['hint'] }}</span>@endif
                    @error($f['name'])<span class="field-error">{{ $message }}</span>@enderror
                </div>
                @endforeach

            </div>
        </div>

        <div class="form-actions">
            <a href="{{ route('tenant.staffs.index') }}" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary" id="submitBtn">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                </svg>
                Add Staff Member
            </button>
        </div>
    </form>
</div>

@endsection

@push('scripts')
<script>
function selectRole(val) {
    document.querySelectorAll('.role-card').forEach(c => {
        c.style.borderColor = '';
        c.style.background  = '';
        c.querySelector('.role-card-name').style.color = 'var(--text-200)';
    });
    const card = document.querySelector(`.role-card[data-role="${CSS.escape(val)}"]`);
    if (card) {
        const color = card.dataset.color, bg = card.dataset.bg;
        card.style.borderColor = `var(--${color})`;
        card.style.background  = `var(--${bg})`;
        card.querySelector('.role-card-name').style.color = `var(--${color})`;
    }
    document.getElementById('roleInput').value = val;
}

function togglePw(id, btn) {
    const inp = document.getElementById(id);
    inp.type  = inp.type === 'password' ? 'text' : 'password';
    btn.style.opacity = inp.type === 'text' ? '.5' : '1';
}

document.querySelector('form').addEventListener('submit', function() {
    const btn = document.getElementById('submitBtn');
    btn.innerHTML = '⏳ Adding...';
    btn.disabled  = true;
});
</script>
@endpush