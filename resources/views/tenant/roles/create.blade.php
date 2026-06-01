@extends('layouts.app')
@section('title', 'Create Role')

@push('styles')
<style>
/* ── Layout ── */
.role-create-layout {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 300px;
    gap: 16px;
    align-items: start;
    margin-top: 4px;
}
@media (max-width: 900px) {
    .role-create-layout { grid-template-columns: 1fr; }
}

/* ── Form Card ── */
.form-card {
    background: var(--bg-surface);
    border: 1px solid var(--border-default);
    border-radius: var(--r-lg);
    overflow: hidden;
}

.fc-sec {
    padding: 22px 24px;
    border-bottom: 1px solid var(--border-subtle);
}
.fc-sec:last-child { border-bottom: none; }

.fc-sec-header {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    margin-bottom: 20px;
}
.fc-sec-icon {
    width: 32px; height: 32px;
    border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
}
.fc-title { font-size: 13px; font-weight: 700; color: var(--text-100); letter-spacing: .3px; }
.fc-sub   { font-size: 12.5px; color: var(--text-300); margin-top: 2px; }

/* ── Fields ── */
.field { display: flex; flex-direction: column; gap: 7px; }
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
}
.fi:focus { border-color: var(--accent); box-shadow: 0 0 0 3px var(--accent-dim); }
.fi.is-error { border-color: var(--red); }
.fe    { font-size: 12px; color: var(--red); font-weight: 500; }
.fhint { font-size: 12px; color: var(--text-400); }

.slug-preview {
    font-size: 12px; color: var(--text-400);
    font-family: var(--mono);
    padding: 6px 10px;
    background: var(--bg-elevated);
    border-radius: var(--r-sm);
}

/* ── Copy-from row ── */
.copy-from-box {
    margin-top: 16px;
    padding: 12px 14px;
    background: var(--bg-elevated);
    border: 1px solid var(--border-subtle);
    border-radius: var(--r-sm);
}
.copy-from-label { font-size: 12.5px; font-weight: 600; color: var(--text-200); margin-bottom: 8px; }
.copy-from-btns  { display: flex; gap: 8px; flex-wrap: wrap; }

/* ── Permission matrix ── */
.perm-module { margin-bottom: 14px; }

.pm-head {
    display: flex; align-items: center; gap: 10px;
    padding: 10px 14px;
    background: var(--bg-elevated);
    border: 1px solid var(--border-subtle);
    border-radius: var(--r-sm);
    cursor: pointer;
    user-select: none;
}
.pm-head.open { border-radius: var(--r-sm) var(--r-sm) 0 0; }

.pm-emoji { font-size: 16px; line-height: 1; }
.pm-module-name { font-size: 13px; font-weight: 700; color: var(--text-100); text-transform: capitalize; flex: 1; }
.pm-count  { font-size: 12px; font-weight: 700; font-family: var(--mono); color: var(--accent); min-width: 52px; text-align: right; }
.pm-chevron { transition: transform .2s; color: var(--text-300); flex-shrink: 0; }
.pm-head.open .pm-chevron { transform: rotate(180deg); }

.select-all-btn {
    font-size: 11.5px; font-weight: 600; color: var(--accent);
    background: none; border: none; cursor: pointer;
    font-family: var(--font); padding: 0;
}
.select-all-btn:hover { text-decoration: underline; }

.pm-perms {
    padding: 12px 14px;
    border: 1px solid var(--border-subtle); border-top: none;
    border-radius: 0 0 var(--r-sm) var(--r-sm);
    background: var(--bg-surface);
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(210px, 1fr));
    gap: 8px;
}
.pm-perms.collapsed { display: none; }

.perm-item {
    display: flex; align-items: center; gap: 9px;
    padding: 8px 10px;
    border-radius: var(--r-sm);
    border: 1.5px solid var(--border-subtle);
    background: var(--bg-elevated);
    cursor: pointer;
    transition: border-color .12s, background .12s;
}
.perm-item:hover { border-color: var(--accent); background: var(--accent-dim); }
.perm-item.checked { border-color: var(--accent); background: var(--accent-dim); }
.perm-item input { width: 15px; height: 15px; accent-color: var(--accent); cursor: pointer; flex-shrink: 0; }
.perm-label { font-size: 12.5px; color: var(--text-200); line-height: 1.3; }
.perm-item.checked .perm-label { color: var(--accent); font-weight: 600; }

/* ── Footer ── */
.form-footer {
    display: flex; align-items: center; justify-content: space-between;
    padding: 16px 24px;
    background: var(--bg-elevated);
    border-top: 1px solid var(--border-subtle);
}

/* ── Sidebar ── */
.rc-sidebar { display: flex; flex-direction: column; gap: 14px; position: sticky; top: 80px; }

.sidebar-card {
    background: var(--bg-surface);
    border: 1px solid var(--border-default);
    border-radius: var(--r-lg);
    overflow: hidden;
}
.sidebar-card-head {
    padding: 13px 18px;
    border-bottom: 1px solid var(--border-subtle);
    font-size: 13px; font-weight: 700; color: var(--text-100);
}
.sidebar-card-body { padding: 16px 18px; }

/* ── Progress bar ── */
.role-progress-wrap { margin-bottom: 16px; }
.rp-label { display: flex; justify-content: space-between; font-size: 12px; color: var(--text-300); margin-bottom: 6px; }
.rp-bar-bg { height: 5px; background: var(--bg-elevated); border-radius: 100px; overflow: hidden; }
.rp-bar    { height: 100%; background: var(--accent); border-radius: 100px; transition: width .4s cubic-bezier(.4,0,.2,1); width: 0%; }

/* ── Tips ── */
.tip-item {
    display: flex; align-items: flex-start; gap: 8px;
    font-size: 12px; color: var(--text-300); line-height: 1.45;
    margin-bottom: 10px;
}
.tip-item:last-child { margin-bottom: 0; }
.tip-dot { width: 5px; height: 5px; border-radius: 50%; background: var(--accent); margin-top: 5px; flex-shrink: 0; }
</style>
@endpush

@section('content')

{{-- Page Header --}}
<div class="page-head">
    <div>
        <div style="font-size:12px;color:var(--text-300);margin-bottom:4px;display:flex;align-items:center;gap:6px">
            <a href="{{ route('tenant.roles.index') }}" style="color:var(--text-300);text-decoration:none">Roles & Permissions</a>
            <span style="opacity:.4">›</span>
            <span>Create New Role</span>
        </div>
        <div class="page-title">Create Role</div>
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

<form method="POST" action="{{ route('tenant.roles.store') }}" id="roleForm" novalidate>
    @csrf

    <div class="role-create-layout">

        {{-- ── Main Form ── --}}
        <div class="form-card">

            {{-- Role Details Section --}}
            <div class="fc-sec">
                <div class="fc-sec-header">
                    <div class="fc-sec-icon" style="background:#E6F1FB">
                        <svg width="16" height="16" fill="none" stroke="#378ADD" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </div>
                    <div>
                        <div class="fc-title">Role Details</div>
                        <div class="fc-sub">Give this role a name and optional description</div>
                    </div>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
                    <div class="field">
                        <label class="fl">Role Name <span class="req">*</span></label>
                        <input type="text" name="name" id="roleName"
                               class="fi {{ $errors->has('name') ? 'is-error' : '' }}"
                               placeholder="e.g. sales_manager"
                               value="{{ old('name') }}"
                               oninput="updateSlug(this.value)"
                               autocomplete="off"
                               required />
                        <div class="slug-preview" id="roleSlug">
                            tenant_{{ auth()->user()->tenant_id }}_<span id="slugPart">your_role_name</span>
                        </div>
                        <span class="fhint">Lowercase letters, numbers, underscores only</span>
                        @error('name') <span class="fe">{{ $message }}</span> @enderror
                    </div>

                    <div class="field">
                        <label class="fl">Description</label>
                        <textarea name="description" class="fi" rows="3"
                                  placeholder="What does this role do? (optional)"
                                  style="resize:none">{{ old('description') }}</textarea>
                    </div>
                </div>

                {{-- Copy from existing role --}}
                @if(isset($copyFrom) && $copyFrom->isNotEmpty())
                <div class="copy-from-box">
                    <div class="copy-from-label">
                        Copy permissions from an existing role:
                    </div>
                    <div class="copy-from-btns">
                        @foreach($copyFrom as $cr)
                        @php
                            $crDisplay = ucwords(str_replace('_', ' ', preg_replace('/^tenant_\d+_/', '', $cr->name)));
                        @endphp
                        <button type="button" class="btn btn-secondary btn-sm"
                                onclick="copyFromRole({{ $cr->id }}, '{{ addslashes($crDisplay) }}')">
                            Copy from {{ $crDisplay }}
                        </button>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>

            {{-- Permission Matrix Section --}}
            <div class="fc-sec">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px">
                    <div>
                        <div class="fc-title">Permissions</div>
                        <div class="fc-sub" style="margin-top:2px">
                            Select what this role can access —
                            <span id="totalCount" style="font-weight:700;color:var(--accent)">0 selected</span>
                        </div>
                    </div>
                    <div style="display:flex;gap:8px">
                        <button type="button" onclick="selectAll()" class="btn btn-secondary btn-sm">Select All</button>
                        <button type="button" onclick="clearAll()"  class="btn btn-secondary btn-sm">Clear All</button>
                    </div>
                </div>

                @php
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
                    'roles'         => '🔐',
                    'attendance'    => '🕐',
                ];
                @endphp

                @forelse($permissions as $module => $modulePerms)
                @php
                    $oldSelected = old('permissions', []);
                    $moduleCheckedCount = collect($modulePerms)->filter(fn($p) => in_array($p->id, $oldSelected))->count();
                @endphp
                <div class="perm-module" id="mod-{{ $module }}">
                    <div class="pm-head open" onclick="toggleModule('{{ $module }}')">
                        <span class="pm-emoji">{{ $icons[$module] ?? '🔧' }}</span>
                        <span class="pm-module-name">{{ ucfirst($module) }}</span>
                        <button type="button" class="select-all-btn"
                                onclick="event.stopPropagation(); toggleModulePerms('{{ $module }}')"
                                id="sa-{{ $module }}">
                            {{ $moduleCheckedCount === count($modulePerms) && count($modulePerms) > 0 ? 'Deselect All' : 'Select All' }}
                        </button>
                        <span class="pm-count" id="cnt-{{ $module }}">{{ $moduleCheckedCount }}/{{ count($modulePerms) }}</span>
                        <svg class="pm-chevron" fill="none" stroke="currentColor" stroke-width="2"
                             viewBox="0 0 24 24" style="width:15px;height:15px">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/>
                        </svg>
                    </div>

                    <div class="pm-perms" id="perms-{{ $module }}">
                        @foreach($modulePerms as $perm)
                        @php
                            $checked = in_array($perm->id, $oldSelected);
                            $permLabel = ucwords(str_replace(['_', '.'], [' ', ': '], $perm->name));
                        @endphp
                        <label class="perm-item {{ $checked ? 'checked' : '' }}" id="pi-{{ $perm->id }}">
                            <input type="checkbox"
                                   name="permissions[]"
                                   value="{{ $perm->id }}"
                                   {{ $checked ? 'checked' : '' }}
                                   onchange="updatePermItem(this)" />
                            <span class="perm-label">{{ $permLabel }}</span>
                        </label>
                        @endforeach
                    </div>
                </div>
                @empty
                <div style="padding:32px;text-align:center;color:var(--text-400);font-size:13px">
                    No permissions defined yet.
                </div>
                @endforelse
            </div>

            {{-- Footer --}}
            <div class="form-footer">
                <span style="font-size:12px;color:var(--text-400)">Fields marked <strong style="color:var(--text-200)">*</strong> are required</span>
                <div style="display:flex;gap:8px">
                    <a href="{{ route('tenant.roles.index') }}" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary" id="submitBtn">
                        <svg id="submitIcon" fill="none" stroke="currentColor" stroke-width="2.5"
                             viewBox="0 0 24 24" style="width:14px;height:14px">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                        </svg>
                        <span id="submitText">Create Role</span>
                    </button>
                </div>
            </div>

        </div>{{-- /form-card --}}

        {{-- ── Sidebar ── --}}
        <div class="rc-sidebar">

            {{-- Permission Summary --}}
            <div class="sidebar-card">
                <div class="sidebar-card-head">Permission Summary</div>
                <div class="sidebar-card-body">
                    <div class="role-progress-wrap">
                        @php $totalPerms = collect($permissions)->flatten()->count(); @endphp
                        <div class="rp-label">
                            <span>Selected</span>
                            <span id="progressLabel">0 / {{ $totalPerms }}</span>
                        </div>
                        <div class="rp-bar-bg">
                            <div class="rp-bar" id="progressBar"></div>
                        </div>
                    </div>
                    <div id="summaryList">
                        <div style="font-size:12.5px;color:var(--text-400);text-align:center;padding:8px 0">
                            No permissions selected
                        </div>
                    </div>
                </div>
            </div>

            {{-- Tips --}}
            <div class="sidebar-card">
                <div class="sidebar-card-head">Tips</div>
                <div class="sidebar-card-body">
                    <div class="tip-item">
                        <div class="tip-dot"></div>
                        <span>Role names must be unique — use underscore format like <code>sales_manager</code></span>
                    </div>
                    <div class="tip-item">
                        <div class="tip-dot"></div>
                        <span>You can copy permissions from an existing role and then adjust as needed</span>
                    </div>
                    <div class="tip-item">
                        <div class="tip-dot"></div>
                        <span>Click a module header to expand or collapse its permissions</span>
                    </div>
                    <div class="tip-item">
                        <div class="tip-dot"></div>
                        <span>System roles (Tenant Admin, Staff) cannot be deleted or modified</span>
                    </div>
                </div>
            </div>

            {{-- Role Name Preview --}}
            <div class="sidebar-card">
                <div class="sidebar-card-head">Role Preview</div>
                <div class="sidebar-card-body">
                    <div style="font-size:12px;color:var(--text-300);margin-bottom:6px">Display name</div>
                    <div id="previewName" style="font-size:16px;font-weight:700;color:var(--text-100);margin-bottom:12px">—</div>
                    <div style="font-size:12px;color:var(--text-300);margin-bottom:6px">Internal slug</div>
                    <div id="previewSlug"
                         style="font-size:11.5px;font-family:var(--mono);color:var(--text-400);word-break:break-all;background:var(--bg-elevated);padding:6px 8px;border-radius:var(--r-sm)">
                        tenant_{{ auth()->user()->tenant_id }}_your_role_name
                    </div>
                </div>
            </div>

        </div>{{-- /rc-sidebar --}}

    </div>{{-- /role-create-layout --}}
</form>

@endsection

@push('scripts')
<script>
(function () {
    const TOTAL_PERMS = {{ collect($permissions)->flatten()->count() }};
    const SELECTED    = new Set({{ json_encode(array_map('intval', old('permissions', []))) }});

    // ── Slug / preview update ─────────────────────────────────────
    window.updateSlug = function (val) {
        const clean = val.toLowerCase()
            .replace(/[^a-z0-9_]/g, '_')
            .replace(/__+/g, '_')
            .replace(/^_|_$/g, '');

        const slugEl = document.getElementById('slugPart');
        if (slugEl) slugEl.textContent = clean || 'your_role_name';

        const previewSlug = document.getElementById('previewSlug');
        const tenantId    = '{{ auth()->user()->tenant_id }}';
        if (previewSlug) previewSlug.textContent = `tenant_${tenantId}_${clean || 'your_role_name'}`;

        const previewName = document.getElementById('previewName');
        if (previewName) {
            previewName.textContent = clean
                ? clean.split('_').map(w => w.charAt(0).toUpperCase() + w.slice(1)).join(' ')
                : '—';
        }
    };

    // ── Toggle module collapse ────────────────────────────────────
    window.toggleModule = function (mod) {
        const perms = document.getElementById('perms-' + mod);
        const head  = document.querySelector('#mod-' + mod + ' .pm-head');
        const collapsed = perms.classList.contains('collapsed');
        perms.classList.toggle('collapsed', !collapsed);
        head.classList.toggle('open', !collapsed);
    };

    // ── Toggle all perms in a module ──────────────────────────────
    window.toggleModulePerms = function (mod) {
        const checkboxes = document.querySelectorAll('#perms-' + mod + ' input[type=checkbox]');
        const allChecked = [...checkboxes].every(c => c.checked);
        checkboxes.forEach(c => {
            c.checked = !allChecked;
            document.getElementById('pi-' + c.value)?.classList.toggle('checked', c.checked);
            if (c.checked) SELECTED.add(parseInt(c.value));
            else           SELECTED.delete(parseInt(c.value));
        });
        refreshModule(mod);
        refreshSummary();
    };

    // ── Per-item toggle ───────────────────────────────────────────
    window.updatePermItem = function (cb) {
        const item = cb.closest('.perm-item');
        item.classList.toggle('checked', cb.checked);
        const mod = item.closest('.perm-module').id.replace('mod-', '');
        if (cb.checked) SELECTED.add(parseInt(cb.value));
        else            SELECTED.delete(parseInt(cb.value));
        refreshModule(mod);
        refreshSummary();
    };

    // ── Select / clear all ────────────────────────────────────────
    window.selectAll = function () {
        document.querySelectorAll('.perm-item input').forEach(c => {
            c.checked = true;
            c.closest('.perm-item').classList.add('checked');
            SELECTED.add(parseInt(c.value));
        });
        refreshAllModules();
        refreshSummary();
    };

    window.clearAll = function () {
        document.querySelectorAll('.perm-item input').forEach(c => {
            c.checked = false;
            c.closest('.perm-item').classList.remove('checked');
            SELECTED.delete(parseInt(c.value));
        });
        refreshAllModules();
        refreshSummary();
    };

    // ── Refresh a module's count badge ────────────────────────────
    function refreshModule(mod) {
        const total   = document.querySelectorAll('#perms-' + mod + ' input').length;
        const checked = document.querySelectorAll('#perms-' + mod + ' input:checked').length;
        const cntEl   = document.getElementById('cnt-' + mod);
        const saBtn   = document.getElementById('sa-' + mod);
        if (cntEl)  cntEl.textContent  = checked + '/' + total;
        if (saBtn)  saBtn.textContent  = (checked === total && total > 0) ? 'Deselect All' : 'Select All';
    }

    function refreshAllModules() {
        document.querySelectorAll('.perm-module').forEach(m => {
            refreshModule(m.id.replace('mod-', ''));
        });
    }

    // ── Summary sidebar ───────────────────────────────────────────
    function refreshSummary() {
        const count       = SELECTED.size;
        const totalCount  = document.getElementById('totalCount');
        const progressBar = document.getElementById('progressBar');
        const progressLbl = document.getElementById('progressLabel');
        const summaryList = document.getElementById('summaryList');

        if (totalCount)  totalCount.textContent  = count + ' selected';
        if (progressLbl) progressLbl.textContent  = count + ' / ' + TOTAL_PERMS;
        if (progressBar) progressBar.style.width  = (TOTAL_PERMS ? (count / TOTAL_PERMS) * 100 : 0) + '%';

        if (!summaryList) return;

        const modules = {};
        document.querySelectorAll('.perm-item input:checked').forEach(c => {
            const mod = c.closest('.perm-module').id.replace('mod-', '');
            modules[mod] = (modules[mod] || 0) + 1;
        });

        if (Object.keys(modules).length === 0) {
            summaryList.innerHTML = '<div style="font-size:12.5px;color:var(--text-400);text-align:center;padding:8px 0">No permissions selected</div>';
            return;
        }

        summaryList.innerHTML = Object.entries(modules).map(([mod, cnt]) => `
            <div style="display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px solid var(--border-subtle);font-size:12.5px">
                <span style="text-transform:capitalize;color:var(--text-200)">${mod}</span>
                <span style="font-family:var(--mono);font-weight:700;color:var(--accent)">${cnt}</span>
            </div>
        `).join('') + `
            <div style="display:flex;justify-content:space-between;padding:8px 0 0;font-size:12.5px;font-weight:700">
                <span style="color:var(--text-200)">Total</span>
                <span style="font-family:var(--mono);color:var(--accent)">${count}</span>
            </div>
        `;
    }

    // ── Copy permissions from another role (AJAX) ─────────────────
    window.copyFromRole = async function (roleId, roleName) {
        try {
            const r    = await fetch('/roles/' + roleId + '/permissions', {
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || ''
                }
            });
            const data = await r.json();
            if (!data.permission_ids) return;

            clearAll();
            data.permission_ids.forEach(id => {
                const cb = document.querySelector('input[name="permissions[]"][value="' + id + '"]');
                if (cb) {
                    cb.checked = true;
                    cb.closest('.perm-item')?.classList.add('checked');
                    SELECTED.add(parseInt(id));
                }
            });
            refreshAllModules();
            refreshSummary();
        } catch (e) {
            console.error('Could not fetch permissions for copy:', e);
        }
    };

    // ── Submit loading state ──────────────────────────────────────
    document.getElementById('roleForm').addEventListener('submit', function () {
        const btn  = document.getElementById('submitBtn');
        const icon = document.getElementById('submitIcon');
        const text = document.getElementById('submitText');
        if (btn && !btn.disabled) {
            btn.disabled       = true;
            icon.style.animation = 'spin .7s linear infinite';
            text.textContent   = 'Creating...';
        }
    });

    // ── Init ─────────────────────────────────────────────────────
    refreshAllModules();
    refreshSummary();

    // Re-init slug if old() value is present
    const nameInput = document.getElementById('roleName');
    if (nameInput && nameInput.value) updateSlug(nameInput.value);

})();
</script>
<style>
@keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
</style>
@endpush
