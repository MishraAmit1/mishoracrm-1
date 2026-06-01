@extends('layouts.app')
@section('title', 'My Profile')

@push('styles')
<style>
.profile-layout { display:grid; grid-template-columns:320px 1fr; gap:16px; align-items:start; }
@media(max-width:1024px) { .profile-layout { grid-template-columns:1fr; } }

/* ── Profile card ────────────────────────────────────────────────── */
.profile-card { background:var(--bg-surface); border:1px solid var(--border-default); border-radius:var(--r-lg); overflow:hidden; }
.profile-cover { height:90px; background:linear-gradient(135deg,var(--accent) 0%,#a78bfa 100%); }
.profile-body  { padding:0 20px 20px; }

.profile-av-wrap { margin-top:-38px; margin-bottom:14px; }
.profile-av {
    width:76px; height:76px; border-radius:50%;
    border:4px solid var(--bg-surface);
    display:flex; align-items:center; justify-content:center;
    font-size:26px; font-weight:800;
    object-fit:cover;
}
.profile-name  { font-size:18px; font-weight:800; color:var(--text-100); margin-bottom:3px; }
.profile-role  { font-size:13px; color:var(--text-300); margin-bottom:12px; }
.profile-tags  { display:flex; flex-wrap:wrap; gap:6px; margin-bottom:16px; }
.profile-badge { font-size:11.5px; font-weight:600; padding:3px 10px; border-radius:20px; }

.profile-info-row {
    display:flex; align-items:center; gap:12px;
    padding:10px 0; border-bottom:1px solid var(--border-subtle);
    font-size:13px; color:var(--text-200);
}
.profile-info-row:last-child { border-bottom:none; }
.profile-info-icon { width:32px; height:32px; border-radius:var(--r-sm); background:var(--bg-elevated); display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.profile-info-icon svg { width:15px; height:15px; color:var(--text-300); }
.profile-info-label { font-size:11px; color:var(--text-400); font-weight:600; margin-bottom:2px; }
.profile-info-val   { font-size:13.5px; color:var(--text-100); font-weight:500; }

/* ── Stats ───────────────────────────────────────────────────────── */
.stat-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:12px; margin-bottom:16px; }
@media(max-width:640px) { .stat-grid { grid-template-columns:repeat(2,1fr); } }
.stat-card { background:var(--bg-surface); border:1px solid var(--border-default); border-radius:var(--r-md); padding:16px; text-align:center; }
.stat-num { font-size:22px; font-weight:800; color:var(--text-100); font-family:var(--mono); margin-bottom:4px; }
.stat-lbl { font-size:11.5px; color:var(--text-300); }

/* ── Activity ────────────────────────────────────────────────────── */
.detail-card { background:var(--bg-surface); border:1px solid var(--border-default); border-radius:var(--r-lg); overflow:hidden; margin-bottom:16px; }
.detail-head { padding:14px 20px; border-bottom:1px solid var(--border-subtle); font-size:13.5px; font-weight:700; color:var(--text-100); display:flex; align-items:center; justify-content:space-between; }

/* ── Permission list ─────────────────────────────────────────────── */
.perm-grid { display:grid; grid-template-columns:1fr 1fr; gap:8px; padding:16px 20px; }
.perm-item { display:flex; align-items:center; gap:8px; font-size:13px; }
.perm-dot  { width:7px; height:7px; border-radius:50%; flex-shrink:0; }
</style>
@endpush

@section('content')

@php
    $user         = auth()->user()->load(['roles','tenant','staff.department']);
    $userRole     = $user->roles->first()?->name ?? 'staff';
    $staff        = $user->staff;
    $roleConfig   = config('staff.roles.'.$userRole, ['label'=>ucfirst($userRole),'color'=>'accent','bg'=>'accent-dim']);
    $typeConfig   = config('staff.employment_types.'.$staff?->employment_type, ['label'=>'Full Time','color'=>'accent','bg'=>'accent-dim']);

    $avatarColors = config('staff.avatar_colors');
    $avColor = $avatarColors[abs(crc32($user->name ?? '')) % count($avatarColors)];
    $avBg = $avColor['bg'];
    $avTx = $avColor['text'];

    // Stats
    $leadsCount    = \App\Models\Lead::where('created_by', $user->id)->count();
    $dealsCount    = \App\Models\Deal::where('assigned_to', $user->id)->count();
    $tasksCount    = \App\Models\Task::where('assigned_to', $user->id)->count();
    $followupCount = \App\Models\Followup::where('created_by', $user->id)->count();

    // Permissions map
    $permMap = [
        'Leads'      => in_array($userRole, ['tenant_admin','manager','staff']),
        'Contacts'   => in_array($userRole, ['tenant_admin','manager','staff']),
        'Deals'      => in_array($userRole, ['tenant_admin','manager']),
        'Quotations' => in_array($userRole, ['tenant_admin','manager']),
        'Invoices'   => in_array($userRole, ['tenant_admin']),
        'Staff'      => in_array($userRole, ['tenant_admin']),
        'Reports'    => in_array($userRole, ['tenant_admin','manager']),
        'Settings'   => in_array($userRole, ['tenant_admin']),
        'Tasks'      => in_array($userRole, ['tenant_admin','manager','staff']),
        'Follow-ups' => in_array($userRole, ['tenant_admin','manager','staff']),
    ];
@endphp

<div class="page-head">
    <div>
        <div class="page-title">My Profile</div>
        <div class="page-sub">Your account overview</div>
    </div>
    <a href="{{ route('tenant.settings.index') }}" class="btn btn-primary">
        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="width:15px;height:15px">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z"/>
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
        </svg>
        Edit Profile
    </a>
</div>

<div class="profile-layout">

    {{-- ── Left: Profile card ──────────────────────────────────── --}}
    <div>
        <div class="profile-card">
            <div class="profile-cover"></div>
            <div class="profile-body">
                {{-- Avatar --}}
                <div class="profile-av-wrap">
                    @if($user->avatar)
                    <img src="{{ Storage::url($user->avatar) }}"
                         alt="{{ $user->name }}" class="profile-av"/>
                    @else
                    <div class="profile-av" style="background:{{ $avBg }};color:{{ $avTx }}">
                        {{ strtoupper(substr($user->name, 0, 1)) }}
                    </div>
                    @endif
                </div>

                <div class="profile-name">{{ $user->name }}</div>
                <div class="profile-role">
                    {{ $staff?->designation ?? $roleConfig['label'] }}
                    @if($user->tenant) · {{ $user->tenant->name }} @endif
                </div>

                <div class="profile-tags">
                    <span class="profile-badge"
                          style="background:var(--{{ $roleConfig['bg'] }});color:var(--{{ $roleConfig['color'] }})">
                        {{ $roleConfig['label'] }}
                    </span>
                    @if($staff)
                    <span class="profile-badge"
                          style="background:var(--{{ $typeConfig['bg'] }});color:var(--{{ $typeConfig['color'] }})">
                        {{ $typeConfig['label'] }}
                    </span>
                    @endif
                    <span class="profile-badge"
                          style="background:{{ $user->is_active ? 'var(--green-dim)':'var(--red-dim)' }};color:{{ $user->is_active ? 'var(--green)':'var(--red)' }}">
                        {{ $user->is_active ? 'Active':'Inactive' }}
                    </span>
                </div>

                {{-- Contact info --}}
                <div class="profile-info-row">
                    <div class="profile-info-icon">
                        <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/></svg>
                    </div>
                    <div>
                        <div class="profile-info-label">Email</div>
                        <div class="profile-info-val">
                            <a href="mailto:{{ $user->email }}" style="color:var(--accent);text-decoration:none">{{ $user->email }}</a>
                        </div>
                    </div>
                </div>

                @if($user->phone)
                <div class="profile-info-row">
                    <div class="profile-info-icon">
                        <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z"/></svg>
                    </div>
                    <div>
                        <div class="profile-info-label">Phone</div>
                        <div class="profile-info-val">{{ $user->phone }}</div>
                    </div>
                </div>
                @endif

                @if($staff?->department)
                <div class="profile-info-row">
                    <div class="profile-info-icon">
                        <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21"/></svg>
                    </div>
                    <div>
                        <div class="profile-info-label">Department</div>
                        <div class="profile-info-val">{{ $staff->department->name }}</div>
                    </div>
                </div>
                @endif

                @if($staff?->joining_date)
                <div class="profile-info-row">
                    <div class="profile-info-icon">
                        <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/></svg>
                    </div>
                    <div>
                        <div class="profile-info-label">Joined</div>
                        <div class="profile-info-val">
                            {{ $staff->joining_date->format('d M Y') }}
                            <span style="font-size:12px;color:var(--text-400)">
                                ({{ $staff->joining_date->diffForHumans() }})
                            </span>
                        </div>
                    </div>
                </div>
                @endif

                <div class="profile-info-row">
                    <div class="profile-info-icon">
                        <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                    <div>
                        <div class="profile-info-label">Last Login</div>
                        <div class="profile-info-val">
                            {{ $user->last_login_at ? $user->last_login_at->diffForHumans() : 'N/A' }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Right: Stats + Permissions ──────────────────────────── --}}
    <div>

        {{-- Stats --}}
        <div class="stat-grid">
            @php
                $statsData = [
                    ['num'=>$leadsCount,    'lbl'=>'Leads Created',  'color'=>'var(--accent)'],
                    ['num'=>$dealsCount,    'lbl'=>'Deals Assigned', 'color'=>'var(--amber)'],
                    ['num'=>$tasksCount,    'lbl'=>'Tasks Assigned', 'color'=>'var(--green)'],
                    ['num'=>$followupCount, 'lbl'=>'Follow-ups',     'color'=>'var(--purple)'],
                ];
            @endphp
            @foreach($statsData as $s)
            <div class="stat-card">
                <div class="stat-num" style="color:{{ $s['color'] }}">{{ $s['num'] }}</div>
                <div class="stat-lbl">{{ $s['lbl'] }}</div>
            </div>
            @endforeach
        </div>

        {{-- Account details --}}
        <div class="detail-card">
            <div class="detail-head">
                Account Details
                <a href="{{ route('tenant.settings.index') }}"
                   style="font-size:12px;color:var(--accent);text-decoration:none">Edit →</a>
            </div>
            <div style="padding:16px 20px;display:grid;grid-template-columns:1fr 1fr;gap:14px">
                @foreach([
                    ['lbl'=>'User ID',         'val'=>'#'.$user->id],
                    ['lbl'=>'Employee Code',    'val'=>$staff?->employee_code ?? '—'],
                    ['lbl'=>'Member Since',     'val'=>$user->created_at->format('d M Y')],
                    ['lbl'=>'Last Updated',     'val'=>$user->updated_at->diffForHumans()],
                ] as $row)
                <div>
                    <div style="font-size:11px;color:var(--text-400);font-weight:600;text-transform:uppercase;letter-spacing:.3px;margin-bottom:3px">
                        {{ $row['lbl'] }}
                    </div>
                    <div style="font-size:13.5px;font-weight:600;color:var(--text-100);font-family:var(--mono)">
                        {{ $row['val'] }}
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        {{-- Module access --}}
        <div class="detail-card">
            <div class="detail-head">
                Module Access
                <span style="font-size:11.5px;font-weight:600;padding:2px 8px;border-radius:20px;background:var(--{{ $roleConfig['bg'] }});color:var(--{{ $roleConfig['color'] }})">
                    {{ $roleConfig['label'] }}
                </span>
            </div>
            <div class="perm-grid">
                @foreach($permMap as $module => $hasAccess)
                <div class="perm-item">
                    <div class="perm-dot"
                         style="background:{{ $hasAccess ? 'var(--green)':'var(--border-default)' }}">
                    </div>
                    <span style="color:{{ $hasAccess ? 'var(--text-100)':'var(--text-400)' }}">
                        {{ $module }}
                    </span>
                    @if($hasAccess)
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"
                         style="width:12px;height:12px;color:var(--green);margin-left:auto">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                    </svg>
                    @else
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"
                         style="width:12px;height:12px;color:var(--text-400);margin-left:auto">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                    </svg>
                    @endif
                </div>
                @endforeach
            </div>
        </div>

        {{-- Quick links --}}
        <div class="detail-card">
            <div class="detail-head">Quick Links</div>
            <div style="padding:12px 20px;display:flex;flex-wrap:wrap;gap:8px">
                @foreach([
                    ['href'=>route('tenant.settings.index'),              'label'=>'⚙️ Settings'],
                    ['href'=>route('tenant.notifications.index'),         'label'=>'🔔 Notifications'],
                    ['href'=>route('tenant.notifications.preferences'),   'label'=>'🎛️ Notification Prefs'],
                    ['href'=>route('tenant.leads.index'),                 'label'=>'👤 My Leads'],
                    ['href'=>route('tenant.tasks.index'),                 'label'=>'✅ My Tasks'],
                ] as $link)
                <a href="{{ $link['href'] }}"
                   style="padding:7px 14px;border-radius:var(--r-sm);border:1.5px solid var(--border-default);font-size:13px;font-weight:500;color:var(--text-200);text-decoration:none;transition:all .15s"
                   onmouseover="this.style.borderColor='var(--accent)';this.style.color='var(--accent)'"
                   onmouseout="this.style.borderColor='var(--border-default)';this.style.color='var(--text-200)'">
                    {{ $link['label'] }}
                </a>
                @endforeach
            </div>
        </div>

    </div>
</div>

@endsection