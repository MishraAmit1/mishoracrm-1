@extends('layouts.app')
@section('title', $pageTitle . ' Permissions')

@push('styles')
<style>
.form-card { background:var(--bg-surface); border:1px solid var(--border-default); border-radius:var(--r-lg); overflow:hidden; }
.fc-sec { padding:22px 24px; }
.fc-title { font-size:13px; font-weight:700; color:var(--text-100); text-transform:uppercase; letter-spacing:.4px; margin-bottom:0; }
.fc-sub   { font-size:12.5px; color:var(--text-300); margin-top:2px; }

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
    padding:12px 14px;
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

.select-all-btn { font-size:11.5px; font-weight:600; color:var(--accent); background:none; border:none; cursor:pointer; padding:0; }
.select-all-btn:hover { text-decoration:underline; }

.form-footer { padding:16px 24px; background:var(--bg-elevated); border-top:1px solid var(--border-subtle); display:flex; justify-content:space-between; align-items:center; }
</style>
@endpush

@section('content')

@php
    $selPerms = old('permissions', $rolePermIds);
    $icons = [
        'leads' => '🎯', 'contacts' => '👤', 'deals' => '💼', 'followups' => '📅',
        'tasks' => '✅', 'quotations' => '📄', 'invoices' => '🧾', 'vendors' => '🏭',
        'purchase_requests' => '🛒', 'purchase_orders' => '📦', 'work_orders' => '🔧',
        'staff' => '👥', 'departments' => '🏢', 'whatsapp' => '💬', 'email' => '📧',
        'reports' => '📊', 'settings' => '⚙️', 'notifications' => '🔔', 'roles' => '🔐',
        'attendance' => '🕐', 'audit_logs' => '📜', 'subscriptions' => '📆',
        'appointments' => '🗓️', 'time_entries' => '⏱️', 'tickets' => '🎫',
    ];
@endphp

<div class="page-head">
    <div>
        <div style="font-size:12px;color:var(--text-300);margin-bottom:4px">
            <a href="{{ route('superadmin.permissions.index') }}" style="color:var(--text-300);text-decoration:none">Roles &amp; Permissions</a>
            <span style="margin:0 6px">›</span> {{ $pageTitle }}
        </div>
        <div class="page-title">{{ $pageTitle }} Permissions</div>
        <div class="page-sub">{{ $pageSub }} Only Super Admin can change this.</div>
    </div>
    <a href="{{ route('superadmin.permissions.index') }}" class="btn btn-secondary">← Back</a>
</div>

<div style="display:flex;gap:8px;margin-bottom:16px">
    <a href="{{ route('superadmin.roles.edit', 'tenant_admin') }}"
       class="btn btn-sm {{ $roleKey === 'tenant_admin' ? 'btn-primary' : 'btn-secondary' }}">Tenant Admin</a>
    <a href="{{ route('superadmin.roles.edit', 'staff') }}"
       class="btn btn-sm {{ $roleKey === 'staff' ? 'btn-primary' : 'btn-secondary' }}">Default Staff</a>
</div>

@if(session('success'))
<div style="padding:12px 16px;background:var(--green-dim);border:1px solid rgba(29,158,117,.2);border-radius:var(--r-sm);font-size:13px;color:var(--green);margin-bottom:16px">
    ✓ {{ session('success') }}
</div>
@endif

<form method="POST" action="{{ route('superadmin.roles.update', $roleKey) }}" id="roleForm">
    @csrf
    @method('PUT')

    <div class="form-card">
        <div class="fc-sec">
            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px">
                <div>
                    <div class="fc-title">Permissions</div>
                    <div class="fc-sub">
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
            <a href="{{ route('superadmin.permissions.index') }}" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary" id="submitBtn">Save Changes</button>
        </div>
    </div>
</form>

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
