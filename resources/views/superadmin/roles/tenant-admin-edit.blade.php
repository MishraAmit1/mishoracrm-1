@extends('layouts.app')
@section('title', 'Tenant Admin Role Permissions')

@push('styles')
<style>
.page-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:24px; }
.page-header h1 { font-size:22px; font-weight:700; color:var(--text-100); }
.page-header p { font-size:13px; color:var(--text-400); margin-top:4px; }

.form-card { background:var(--bg-card); border:1px solid var(--border-subtle); border-radius:var(--r-lg); overflow:hidden; }
.fc-sec { padding:22px 24px; }
.fc-title { font-size:13px; font-weight:700; color:var(--text-100); text-transform:uppercase; letter-spacing:.4px; margin-bottom:0; }
.fc-sub   { font-size:12.5px; color:var(--text-300); margin-top:2px; }

.perm-module { margin-bottom:20px; }
.pm-head {
    display:flex; align-items:center; gap:10px;
    padding:10px 14px;
    background:var(--bg-input); border:1px solid var(--border-subtle);
    border-radius:var(--r-md); cursor:pointer;
    margin-bottom:1px;
}
.pm-head.open { border-radius:var(--r-md) var(--r-md) 0 0; }
.pm-module-name { font-size:13px; font-weight:700; color:var(--text-100); text-transform:capitalize; flex:1; }
.pm-count { font-size:12px; font-weight:700; font-family:var(--mono); color:var(--accent); min-width:60px; text-align:right; }
.pm-chevron { transition:transform .2s; color:var(--text-300); }
.pm-head.open .pm-chevron { transform:rotate(180deg); }
.pm-perms {
    padding:12px 14px;
    border:1px solid var(--border-subtle); border-top:none;
    border-radius:0 0 var(--r-md) var(--r-md);
    background:var(--bg-card);
    display:grid; grid-template-columns:repeat(auto-fill, minmax(220px,1fr)); gap:8px;
}
.pm-perms.collapsed { display:none !important; }
.perm-item {
    display:flex; align-items:center; gap:9px;
    padding:8px 10px; border-radius:var(--r-md);
    border:1.5px solid var(--border-subtle);
    background:var(--bg-input); cursor:pointer;
    transition:border-color .12s, background .12s;
}
.perm-item:hover { border-color:var(--accent); }
.perm-item.checked { border-color:var(--accent); background:rgba(99,102,241,.1); }
.perm-item input { width:15px; height:15px; accent-color:var(--accent); cursor:pointer; flex-shrink:0; }
.perm-label { font-size:12.5px; color:var(--text-200); line-height:1.3; }
.perm-item.checked .perm-label { color:var(--accent); font-weight:600; }

.select-all-btn { font-size:11.5px; font-weight:600; color:var(--accent); background:none; border:none; cursor:pointer; padding:0; }
.select-all-btn:hover { text-decoration:underline; }

.form-footer { padding:16px 24px; background:var(--bg-input); border-top:1px solid var(--border-subtle); display:flex; justify-content:space-between; align-items:center; }
.btn-primary { padding:10px 22px; background:var(--accent); color:#fff; border:none; border-radius:var(--r-md); font-size:14px; font-weight:700; cursor:pointer; text-decoration:none; }
.btn-secondary { padding:10px 18px; background:var(--bg-input); color:var(--text-200); border:1px solid var(--border-subtle); border-radius:var(--r-md); font-size:14px; font-weight:600; cursor:pointer; text-decoration:none; }
</style>
@endpush

@section('content')
<div class="page-content">

@php
    $selPerms = old('permissions', $rolePermIds);
    $icons = [
        'leads' => '🎯', 'contacts' => '👤', 'deals' => '💼', 'followups' => '📅',
        'tasks' => '✅', 'quotations' => '📄', 'invoices' => '🧾', 'vendors' => '🏭',
        'purchase_requests' => '🛒', 'purchase_orders' => '📦', 'work_orders' => '🔧',
        'staff' => '👥', 'departments' => '🏢', 'whatsapp' => '💬', 'email' => '📧',
        'reports' => '📊', 'settings' => '⚙️', 'notifications' => '🔔', 'roles' => '🔐',
        'attendance' => '🕐', 'audit_logs' => '📜',
    ];
@endphp

<div class="page-header">
    <div>
        <h1>Tenant Admin Role Permissions</h1>
        <p>Controls what every Tenant Admin can access across all tenants. Only Super Admin can change this.</p>
    </div>
    <a href="{{ route('superadmin.permissions.index') }}" class="btn btn-secondary">← Back to Roles & Permissions</a>
</div>

@if(session('success'))
<div class="alert alert-success" style="background:rgba(22,163,74,.1);border:1px solid rgba(22,163,74,.25);color:#16a34a;padding:12px 16px;border-radius:var(--r-md);margin-bottom:16px;font-size:13.5px;">
    {{ session('success') }}
</div>
@endif

<form method="POST" action="{{ route('superadmin.roles.tenant-admin.update') }}" id="roleForm">
    @csrf
    @method('PUT')

    <div class="form-card">
        <div class="fc-sec">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px">
                <div>
                    <div class="fc-title">Permissions</div>
                    <div class="fc-sub">
                        Select what Tenant Admins can access
                        <span id="totalCount" style="font-weight:700;color:var(--accent);margin-left:4px">
                            ({{ count($selPerms) }} selected)
                        </span>
                    </div>
                </div>
                <div style="display:flex;gap:8px">
                    <button type="button" onclick="selectAll()" class="btn-secondary" style="padding:6px 14px;font-size:12.5px">Select All</button>
                    <button type="button" onclick="clearAll()"  class="btn-secondary" style="padding:6px 14px;font-size:12.5px">Clear All</button>
                </div>
            </div>

            @foreach($permissions as $module => $modulePerms)
            @php
                $moduleSelectedCount = collect($modulePerms)->filter(fn($p) => in_array($p->id, (array)$selPerms))->count();
            @endphp
            <div class="perm-module" id="mod-{{ $module }}">
                <div class="pm-head open" onclick="toggleModule('{{ $module }}')">
                    <span style="font-size:16px">{{ $icons[$module] ?? '🔧' }}</span>
                    <span class="pm-module-name">{{ str_replace('_', ' ', $module) }}</span>
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
                        $permLabel = ucwords(str_replace(['_', '.'], [' ', ': '], $perm->name));
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
            <a href="{{ route('superadmin.permissions.index') }}" class="btn-secondary">Cancel</a>
            <button type="submit" class="btn-primary" id="submitBtn">Save Changes</button>
        </div>
    </div>
</form>

</div>
@endsection

@push('scripts')
<script>
function toggleModule(mod) {
    const perms = document.getElementById('perms-' + mod);
    const head  = document.querySelector(`#mod-${mod} .pm-head`);
    perms.classList.toggle('collapsed');
    head.classList.toggle('open');
}

function toggleModulePerms(mod) {
    const checkboxes = document.querySelectorAll(`#perms-${mod} input[type=checkbox]`);
    const allChecked = [...checkboxes].every(c => c.checked);
    checkboxes.forEach(c => {
        c.checked = !allChecked;
        document.getElementById('pi-' + c.value)?.classList.toggle('checked', c.checked);
    });
    updateCounts(mod);
}

function updatePermItem(cb) {
    cb.closest('.perm-item').classList.toggle('checked', cb.checked);
    const mod = cb.closest('.perm-module').id.replace('mod-', '');
    updateCounts(mod);
}

function updateCounts(mod) {
    const total   = document.querySelectorAll(`#perms-${mod} input`).length;
    const checked = document.querySelectorAll(`#perms-${mod} input:checked`).length;
    const el = document.getElementById('cnt-' + mod);
    if (el) el.textContent = checked + '/' + total;
    const saBtn = document.getElementById('sa-' + mod);
    if (saBtn) saBtn.textContent = checked === total ? 'Deselect All' : 'Select All';
    document.getElementById('totalCount').textContent = '(' + document.querySelectorAll('.perm-item input:checked').length + ' selected)';
}

function selectAll() {
    document.querySelectorAll('.perm-item input').forEach(c => {
        c.checked = true;
        c.closest('.perm-item').classList.add('checked');
    });
    document.querySelectorAll('.perm-module').forEach(m => updateCounts(m.id.replace('mod-', '')));
}

function clearAll() {
    document.querySelectorAll('.perm-item input').forEach(c => {
        c.checked = false;
        c.closest('.perm-item').classList.remove('checked');
    });
    document.querySelectorAll('.perm-module').forEach(m => updateCounts(m.id.replace('mod-', '')));
}
</script>
@endpush
