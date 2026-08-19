@extends('layouts.app')
@section('title', 'Roles & Permissions')

@push('styles')
<style>
.page-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:24px; }
.page-header h1 { font-size:22px; font-weight:700; color:var(--text-100); }
.page-header p { font-size:13px; color:var(--text-400); margin-top:4px; }
.badge { display:inline-flex; align-items:center; padding:3px 10px; border-radius:100px; font-size:11px; font-weight:700; }
.badge-blue  { background:rgba(99,102,241,.1); color:var(--accent); }
.badge-gray  { background:var(--bg-input); color:var(--text-400); }
.action-btns { display:flex; gap:6px; }
.btn-sm { padding:5px 12px; border-radius:var(--r-md); font-size:12px; font-weight:600; cursor:pointer; border:none; text-decoration:none; display:inline-block; }
.btn-edit { background:var(--bg-input); color:var(--text-200); }
.btn-del  { background:rgba(239,68,68,.1); color:#ef4444; }
.empty-state { text-align:center; padding:56px 16px; color:var(--text-400); font-size:14px; }

.perm-module { background:var(--bg-card); border:1px solid var(--border-subtle); border-radius:var(--r-lg); margin-bottom:14px; overflow:hidden; }
.pm-head { display:flex; align-items:center; gap:10px; padding:14px 18px; background:var(--bg-input); cursor:pointer; user-select:none; }
.pm-emoji { font-size:16px; }
.pm-module-name { font-size:14px; font-weight:700; color:var(--text-100); flex:1; }
.pm-count { font-size:12px; color:var(--text-400); }
.pm-chevron { width:15px; height:15px; color:var(--text-400); transition:transform .15s; }
.pm-head.open .pm-chevron { transform:rotate(180deg); }
.pm-perms { display:none; }
.pm-head.open + .pm-perms { display:block; }
.perm-row { display:flex; align-items:center; gap:12px; padding:11px 18px; border-top:1px solid var(--border-subtle); }
.perm-name { font-family:monospace; font-size:13px; font-weight:600; color:var(--text-100); flex:1; }
</style>
@endpush

@section('content')
<div class="page-content">

    <div class="page-header">
        <div>
            <h1>Roles & Permissions</h1>
            <p>Master permission list — add a permission here to make it instantly available for Tenant Admin and the tenant-side role builder. No seeder/code changes needed.</p>
        </div>
        <a href="{{ route('superadmin.permissions.create') }}" class="btn btn-primary" style="padding:9px 18px;border-radius:var(--r-md);background:var(--accent);color:#fff;font-size:13.5px;font-weight:700;text-decoration:none;">
            + Add Permission
        </a>
    </div>

    @if(session('success'))
    <div class="alert alert-success" style="background:rgba(22,163,74,.1);border:1px solid rgba(22,163,74,.25);color:#16a34a;padding:12px 16px;border-radius:var(--r-md);margin-bottom:16px;font-size:13.5px;">
        {{ session('success') }}
    </div>
    @endif

    @if(session('error'))
    <div class="alert alert-error" style="background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.25);color:#ef4444;padding:12px 16px;border-radius:var(--r-md);margin-bottom:16px;font-size:13.5px;">
        {{ session('error') }}
    </div>
    @endif

    @php
    $icons = [
        'leads'             => '🎯',
        'contacts'          => '👤',
        'deals'             => '💼',
        'followups'         => '📅',
        'tasks'             => '✅',
        'quotations'        => '📄',
        'invoices'          => '🧾',
        'vendors'           => '🏭',
        'purchase_requests' => '🛒',
        'purchase_orders'   => '📦',
        'work_orders'       => '🔧',
        'staff'             => '👥',
        'departments'       => '🏢',
        'whatsapp'          => '💬',
        'email'             => '📧',
        'reports'           => '📊',
        'settings'          => '⚙️',
        'notifications'     => '🔔',
        'roles'             => '🔐',
        'attendance'        => '🕐',
        'audit_logs'        => '📜',
    ];
    @endphp

    @forelse($permissions as $module => $modulePerms)
    <div class="perm-module">
        <div class="pm-head open" onclick="this.classList.toggle('open')">
            <span class="pm-emoji">{{ $icons[$module] ?? '🔧' }}</span>
            <span class="pm-module-name">{{ ucfirst(str_replace('_', ' ', $module)) }}</span>
            <span class="pm-count">{{ count($modulePerms) }} permission(s)</span>
            <svg class="pm-chevron" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/>
            </svg>
        </div>
        <div class="pm-perms">
            @foreach($modulePerms as $perm)
            <div class="perm-row">
                <span class="perm-name">{{ $perm->name }}</span>
                @if($perm->roles_count > 0)
                    <span class="badge badge-blue">used by {{ $perm->roles_count }} role(s)</span>
                @else
                    <span class="badge badge-gray">unused</span>
                @endif
                <div class="action-btns">
                    <a href="{{ route('superadmin.permissions.edit', $perm) }}" class="btn-sm btn-edit">Edit</a>
                    <form action="{{ route('superadmin.permissions.destroy', $perm) }}" method="POST" style="display:inline"
                          onsubmit="return confirm('Delete permission {{ $perm->name }}?{{ $perm->roles_count > 0 ? ' It is used by ' . $perm->roles_count . ' role(s) and will be removed from them.' : '' }}')">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn-sm btn-del">Delete</button>
                    </form>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @empty
    <div class="empty-state">
        No permissions yet. <a href="{{ route('superadmin.permissions.create') }}" style="color:var(--accent)">Add your first permission →</a>
    </div>
    @endforelse

</div>
@endsection
