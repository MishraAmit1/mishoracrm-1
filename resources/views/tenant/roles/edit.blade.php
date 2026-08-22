@extends('layouts.app')
@section('title', isset($role) ? 'Edit Role' : 'Create Role')

@push('styles')
<style>
.form-card { background:var(--bg-surface); border:1px solid var(--border-default); border-radius:var(--r-lg); overflow:hidden; }
.fc-sec { padding:22px 24px; border-bottom:1px solid var(--border-subtle); }
.fc-sec:last-child { border-bottom:none; }
.fc-title { font-size:13px; font-weight:700; color:var(--text-100); text-transform:uppercase; letter-spacing:.4px; margin-bottom:4px; }
.fc-sub   { font-size:12.5px; color:var(--text-300); margin-bottom:18px; }
.field    { display:flex; flex-direction:column; gap:7px; }
.fl       { font-size:12.5px; font-weight:600; color:var(--text-200); text-transform:uppercase; letter-spacing:.3px; }
.req      { color:var(--red); margin-left:2px; }
.fi       { padding:10px 13px; background:var(--bg-input); border:1.5px solid var(--border-default); border-radius:var(--r-sm); color:var(--text-100); font-family:var(--font); font-size:14px; outline:none; transition:border-color .15s; }
.fi:focus { border-color:var(--accent); box-shadow:0 0 0 3px var(--accent-dim); }
.fe       { font-size:12px; color:var(--red); }
.slug-preview { font-size:12px; color:var(--text-400); font-family:var(--mono); padding:6px 10px; background:var(--bg-elevated); border-radius:var(--r-sm); margin-top:4px; }

/* Permission matrix */
.perm-module { margin-bottom:20px; }
.pm-head {
    display:flex; align-items:center; gap:10px;
    padding:10px 14px;
    background:var(--bg-elevated); border:1px solid var(--border-subtle);
    border-radius:var(--r-sm); cursor:pointer;
    margin-bottom:1px;
}
.pm-head.open { border-radius:var(--r-sm) var(--r-sm) 0 0; }
.pm-module-name { font-size:13px; font-weight:700; color:var(--text-100); text-transform:capitalize; flex:1; }
.pm-count { font-size:12px; font-weight:700; font-family:var(--mono); color:var(--accent); min-width:60px; text-align:right; }
.pm-chevron { transition:transform .2s; color:var(--text-300); }
.pm-head.open .pm-chevron { transform:rotate(180deg); }
.pm-perms {
    display:none; padding:12px 14px;
    border:1px solid var(--border-subtle); border-top:none;
    border-radius:0 0 var(--r-sm) var(--r-sm);
    background:var(--bg-surface);
    display:grid; grid-template-columns:repeat(auto-fill, minmax(220px,1fr)); gap:8px;
}
.pm-perms.collapsed { display:none !important; }
.perm-item {
    display:flex; align-items:center; gap:9px;
    padding:8px 10px; border-radius:var(--r-sm);
    border:1.5px solid var(--border-subtle);
    background:var(--bg-elevated); cursor:pointer;
    transition:border-color .12s, background .12s;
}
.perm-item:hover { border-color:var(--accent); background:var(--accent-dim); }
.perm-item.checked { border-color:var(--accent); background:var(--accent-dim); }
.perm-item input { width:15px; height:15px; accent-color:var(--accent); cursor:pointer; flex-shrink:0; }
.perm-label { font-size:12.5px; color:var(--text-200); line-height:1.3; }
.perm-item.checked .perm-label { color:var(--accent); font-weight:600; }

/* Select all per module */
.select-all-btn { font-size:11.5px; font-weight:600; color:var(--accent); background:none; border:none; cursor:pointer; font-family:var(--font); padding:0; }
.select-all-btn:hover { text-decoration:underline; }

/* Copy from preset */
.copy-from-row { display:flex; align-items:center; gap:10px; flex-wrap:wrap; }

.form-footer { padding:16px 24px; background:var(--bg-elevated); border-top:1px solid var(--border-subtle); display:flex; justify-content:space-between; align-items:center; }
</style>
@endpush

@section('content')
@php
    $isEdit   = isset($role);
    $selPerms = $isEdit ? ($rolePermIds ?? []) : old('permissions', []);

    // Display name from prefix
    $dispName = '';
    if ($isEdit) {
        $dispName = preg_replace('/^tenant_\d+_/', '', $role->name);
        $dispName = str_replace('_', ' ', $dispName);
    }

    // Module icon map
    $icons = [
        'leads'         => '🎯',
        'contacts'      => '👤',
        'deals'         => '💼',
        'followups'     => '📅',
        'tasks'         => '✅',
        'quotations'    => '📄',
        'invoices'      => '🧾',
        'staff'         => '👥',
        'departments'   => '🏢',
        'whatsapp'      => '💬',
        'email'         => '📧',
        'reports'       => '📊',
        'settings'      => '⚙️',
        'notifications' => '🔔',
        'subscriptions' => '📆',
        'appointments'  => '🗓️',
        'time_entries'  => '⏱️',
        'tickets'       => '🎫',
    ];
@endphp

<div class="page-head">
    <div>
        <div style="font-size:13px;color:var(--text-300);margin-bottom:4px">
            <a href="{{ route('tenant.roles.index') }}" style="color:var(--text-300);text-decoration:none">Roles</a>
            <span style="margin:0 6px">›</span>
            {{ $isEdit ? 'Edit: '.$roleDisplayName : 'Create New Role' }}
        </div>
        <div class="page-title">{{ $isEdit ? 'Edit Role' : 'Create Role' }}</div>
    </div>
    <a href="{{ route('tenant.roles.index') }}" class="btn btn-secondary">← Back</a>
</div>

@if($errors->any())
<div style="padding:12px 16px;background:var(--red-dim);border:1px solid rgba(224,82,82,.2);border-radius:var(--r-sm);margin-bottom:16px;font-size:13px;color:var(--red)">
    {{ $errors->first() }}
</div>
@endif

<form method="POST"
      action="{{ $isEdit ? route('tenant.roles.update', $role->id) : route('roles.store') }}"
      id="roleForm">
@csrf
@if($isEdit) @method('PUT') @endif

<div style="display:grid;grid-template-columns:1fr 300px;gap:16px;align-items:start">

{{-- Main form --}}
<div class="form-card">

    {{-- Basic info --}}
    <div class="fc-sec">
        <div class="fc-title">Role Details</div>
        <div class="fc-sub">Name this role and add a description for your team</div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">

            <div class="field">
                <label class="fl">Role Name <span class="req">*</span></label>
                @if($isEdit)
                    {{-- Can't rename existing role --}}
                    <input type="text" class="fi" value="{{ $roleDisplayName }}" readonly
                           style="background:var(--bg-elevated);color:var(--text-300);cursor:default"/>
                    <div class="slug-preview">{{ $role->name }}</div>
                @else
                    <input type="text" name="name" id="roleName"
                           class="fi {{ $errors->has('name')?'is-error':'' }}"
                           placeholder="e.g. sales_manager"
                           value="{{ old('name') }}"
                           oninput="updatePreview(this.value)"
                           required/>
                    <div class="slug-preview" id="roleSlug">
                        tenant_{{ auth()->user()->tenant_id }}_<span id="slugPart">your_role_name</span>
                    </div>
                    <span style="font-size:11.5px;color:var(--text-400)">
                        Use lowercase letters, numbers, underscores only
                    </span>
                    @error('name') <span class="fe">{{ $message }}</span> @enderror
                @endif
            </div>

            <div class="field">
                <label class="fl">Description</label>
                <textarea name="description" class="fi" rows="2"
                          placeholder="What is this role for? (optional)"
                          style="resize:none">{{ old('description', $role->description ?? '') }}</textarea>
            </div>

        </div>

        {{-- Copy from existing role --}}
        @if(!$isEdit)
        <div style="margin-top:16px;padding:12px 14px;background:var(--bg-elevated);border:1px solid var(--border-subtle);border-radius:var(--r-sm)">
            <div style="font-size:12.5px;font-weight:600;color:var(--text-200);margin-bottom:8px">
                Copy permissions from an existing role:
            </div>
            <div class="copy-from-row">
                @foreach($copyFrom as $cr)
                @php
                    $crDisplay = ucwords(str_replace('_',' ', preg_replace('/^tenant_\d+_/','',$cr->name)));
                @endphp
                <button type="button" class="btn btn-secondary btn-sm"
                        onclick="copyFromRole({{ $cr->id }})">
                    Copy from {{ $crDisplay }}
                </button>
                @endforeach
            </div>
        </div>
        @endif
    </div>

    {{-- Permission matrix --}}
    <div class="fc-sec">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px">
            <div>
                <div class="fc-title" style="margin-bottom:0">Permissions</div>
                <div class="fc-sub" style="margin-bottom:0;margin-top:2px">
                    Select what this role can access
                    <span id="totalCount" style="font-weight:700;color:var(--accent);margin-left:4px">
                        ({{ count($selPerms) }} selected)
                    </span>
                </div>
            </div>
            <div style="display:flex;gap:8px">
                <button type="button" onclick="selectAll()" class="btn btn-secondary btn-sm">Select All</button>
                <button type="button" onclick="clearAll()"  class="btn btn-secondary btn-sm">Clear All</button>
            </div>
        </div>

        @foreach($permissions as $module => $modulePerms)
        @php
            $moduleSelectedCount = collect($modulePerms)->filter(fn($p) => in_array($p->id, (array)$selPerms))->count();
        @endphp
        <div class="perm-module" id="mod-{{ $module }}">
            <div class="pm-head open" onclick="toggleModule('{{ $module }}')">
                <span style="font-size:16px">{{ $icons[$module] ?? '🔧' }}</span>
                <span class="pm-module-name">{{ ucwords(str_replace('_', ' ', $module)) }}</span>
                <button type="button" class="select-all-btn"
                        onclick="event.stopPropagation();toggleModulePerms('{{ $module }}')"
                        id="sa-{{ $module }}">
                    {{ $moduleSelectedCount === count($modulePerms) ? 'Deselect All' : 'Select All' }}
                </button>
                <span class="pm-count" id="cnt-{{ $module }}">
                    {{ $moduleSelectedCount }}/{{ count($modulePerms) }}
                </span>
                <svg class="pm-chevron" fill="none" stroke="currentColor" stroke-width="2"
                     viewBox="0 0 24 24" style="width:15px;height:15px">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/>
                </svg>
            </div>

            <div class="pm-perms" id="perms-{{ $module }}">
                @foreach($modulePerms as $perm)
                @php
                    $checked   = in_array($perm->id, (array)$selPerms);
                    $permLabel = ucwords(str_replace(['_','.'], [' ',': '], $perm->name));
                @endphp
                <label class="perm-item {{ $checked ? 'checked' : '' }}" id="pi-{{ $perm->id }}">
                    <input type="checkbox"
                           name="permissions[]"
                           value="{{ $perm->id }}"
                           {{ $checked ? 'checked' : '' }}
                           onchange="updatePermItem(this)"/>
                    <span class="perm-label">{{ $permLabel }}</span>
                </label>
                @endforeach
            </div>
        </div>
        @endforeach
    </div>

    <div class="form-footer">
        <a href="{{ route('tenant.roles.index') }}" class="btn btn-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary" id="submitBtn">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="width:15px;height:15px">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
            </svg>
            {{ $isEdit ? 'Save Changes' : 'Create Role' }}
        </button>
    </div>

</div>

{{-- Sidebar summary --}}
<div style="background:var(--bg-surface);border:1px solid var(--border-default);border-radius:var(--r-lg);overflow:hidden;position:sticky;top:80px">
    <div style="padding:14px 18px;border-bottom:1px solid var(--border-subtle);font-size:13.5px;font-weight:700;color:var(--text-100)">
        Permission Summary
    </div>
    <div style="padding:16px 18px" id="summaryList">
        <div style="font-size:12.5px;color:var(--text-400);text-align:center;padding:12px 0">
            No permissions selected
        </div>
    </div>
</div>

</div>
</form>

@endsection

@push('scripts')
<script>
const SELECTED = new Set(@json(array_map('intval', (array)$selPerms)));
const ROLES_DATA = {};  // Will be filled if copy-from is used

// ── Update preview slug ───────────────────────────────────────────
function updatePreview(val) {
    const clean = val.toLowerCase().replace(/[^a-z0-9_]/g,'_').replace(/__+/g,'_');
    const el = document.getElementById('slugPart');
    if(el) el.textContent = clean || 'your_role_name';
}

// ── Toggle module section ─────────────────────────────────────────
function toggleModule(mod) {
    const perms = document.getElementById('perms-' + mod);
    const head  = document.querySelector(`#mod-${mod} .pm-head`);
    if(perms.classList.contains('collapsed')) {
        perms.classList.remove('collapsed');
        head.classList.add('open');
    } else {
        perms.classList.add('collapsed');
        head.classList.remove('open');
    }
}

// ── Select/deselect all in module ────────────────────────────────
function toggleModulePerms(mod) {
    const checkboxes = document.querySelectorAll(`#perms-${mod} input[type=checkbox]`);
    const allChecked = [...checkboxes].every(c => c.checked);
    checkboxes.forEach(c => {
        c.checked = !allChecked;
        const item = document.getElementById('pi-'+c.value);
        if(item) item.classList.toggle('checked', c.checked);
        if(c.checked) SELECTED.add(parseInt(c.value));
        else SELECTED.delete(parseInt(c.value));
    });
    updateCounts(mod);
    updateSummary();
}

// ── Update perm item style ────────────────────────────────────────
function updatePermItem(cb) {
    const item = cb.closest('.perm-item');
    item.classList.toggle('checked', cb.checked);
    const mod = item.closest('.perm-module').id.replace('mod-','');
    if(cb.checked) SELECTED.add(parseInt(cb.value));
    else SELECTED.delete(parseInt(cb.value));
    updateCounts(mod);
    updateSummary();
}

// ── Update count badges ───────────────────────────────────────────
function updateCounts(mod) {
    const total   = document.querySelectorAll(`#perms-${mod} input`).length;
    const checked = document.querySelectorAll(`#perms-${mod} input:checked`).length;
    const el = document.getElementById('cnt-'+mod);
    if(el) el.textContent = checked + '/' + total;
    const saBtn = document.getElementById('sa-'+mod);
    if(saBtn) saBtn.textContent = checked === total ? 'Deselect All' : 'Select All';
    document.getElementById('totalCount').textContent = '(' + SELECTED.size + ' selected)';
}

// ── Select/clear all ──────────────────────────────────────────────
function selectAll() {
    document.querySelectorAll('.perm-item input').forEach(c => {
        c.checked = true;
        c.closest('.perm-item').classList.add('checked');
        SELECTED.add(parseInt(c.value));
    });
    document.querySelectorAll('.perm-module').forEach(m => {
        updateCounts(m.id.replace('mod-',''));
    });
    updateSummary();
}

function clearAll() {
    document.querySelectorAll('.perm-item input').forEach(c => {
        c.checked = false;
        c.closest('.perm-item').classList.remove('checked');
        SELECTED.delete(parseInt(c.value));
    });
    document.querySelectorAll('.perm-module').forEach(m => {
        updateCounts(m.id.replace('mod-',''));
    });
    updateSummary();
}

// ── Summary sidebar ───────────────────────────────────────────────
function updateSummary() {
    const list = document.getElementById('summaryList');
    const modules = {};

    document.querySelectorAll('.perm-item input:checked').forEach(c => {
        const mod = c.closest('.perm-module').id.replace('mod-','');
        if(!modules[mod]) modules[mod] = 0;
        modules[mod]++;
    });

    if(Object.keys(modules).length === 0) {
        list.innerHTML = '<div style="font-size:12.5px;color:var(--text-400);text-align:center;padding:12px 0">No permissions selected</div>';
        return;
    }

    list.innerHTML = Object.entries(modules).map(([mod, cnt]) => `
        <div style="display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px solid var(--border-subtle);font-size:12.5px">
            <span style="text-transform:capitalize;color:var(--text-200)">${mod}</span>
            <span style="font-family:var(--mono);font-weight:700;color:var(--accent)">${cnt}</span>
        </div>
    `).join('');
}

// ── Copy from role (AJAX) ─────────────────────────────────────────
async function copyFromRole(roleId) {
    try {
        const r = await fetch(`/roles/${roleId}/permissions`, {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || '' }
        });
        const data = await r.json();
        if(data.permission_ids) {
            clearAll();
            data.permission_ids.forEach(id => {
                const cb = document.querySelector(`input[name="permissions[]"][value="${id}"]`);
                if(cb) {
                    cb.checked = true;
                    cb.closest('.perm-item')?.classList.add('checked');
                    SELECTED.add(id);
                }
            });
            document.querySelectorAll('.perm-module').forEach(m => {
                updateCounts(m.id.replace('mod-',''));
            });
            updateSummary();
        }
    } catch(e) {
        console.error('Could not copy permissions:', e);
    }
}

// ── Form submit loader ────────────────────────────────────────────
document.getElementById('roleForm').addEventListener('submit', function() {
    const btn = document.getElementById('submitBtn');
    if(btn) { btn.innerHTML = '⏳ Saving...'; btn.disabled = true; }
});

// ── Init ──────────────────────────────────────────────────────────
document.querySelectorAll('.perm-module').forEach(m => {
    updateCounts(m.id.replace('mod-',''));
});
updateSummary();
</script>
@endpush