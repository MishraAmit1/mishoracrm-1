@extends('layouts.app')
@section('title', $tenant->name . ' — Tenant Detail')

@push('styles')
<style>
/* Layout */
.show-grid { display:grid; grid-template-columns:340px 1fr; gap:16px; align-items:start; margin-bottom:16px; }
@media(max-width:1100px){ .show-grid{ grid-template-columns:1fr; } }
.stack-card { margin-bottom:16px; }

/* Card polish */
.card { transition:box-shadow 0.2s ease, border-color 0.2s ease; }
.card:hover { box-shadow:var(--shadow-md); border-color:var(--border-strong); }
.card-icon {
    width:30px; height:30px; border-radius:9px; flex-shrink:0;
    display:flex; align-items:center; justify-content:center;
}
.card-icon svg { width:15px; height:15px; }
.card-header-flex { display:flex; align-items:center; gap:10px; }

/* Plan feature chips */
.feat-chip {
    display:inline-flex; align-items:center; gap:5px; font-size:11px; font-weight:600;
    padding:3px 10px; border-radius:20px; border:1px solid var(--border-default);
}
.feat-chip.on  { background:var(--green-dim); color:var(--green); border-color:transparent; }
.feat-chip.off { background:var(--bg-input); color:var(--text-300); }
.feat-chip.num { background:var(--accent-dim); color:var(--accent); border-color:transparent; }
.feat-chip .fc-label { color:inherit; font-weight:500; }

/* Legibility overrides for this page's smaller/muted text */
.card-subtitle { font-size:12px; color:var(--text-300); }
.stat-sub { color:var(--text-300); }
.data-table th { color:var(--text-300); }

/* Hero */
.hero-card {
    position:relative; overflow:hidden;
    background:var(--bg-surface); border:1px solid var(--border-default); border-radius:var(--r-lg);
    padding:22px 24px; margin-bottom:16px;
    display:flex; align-items:center; justify-content:space-between; gap:20px; flex-wrap:wrap;
}
.hero-card::before {
    content:''; position:absolute; top:0; left:0; right:0; height:3px;
    background:linear-gradient(90deg, var(--accent), var(--purple));
}
.hero-left { display:flex; align-items:center; gap:16px; min-width:0; }
.hero-avatar {
    width:56px; height:56px; border-radius:14px; flex-shrink:0; overflow:hidden;
    background:linear-gradient(135deg, var(--accent), var(--purple)); color:#fff;
    display:flex; align-items:center; justify-content:center; font-size:20px; font-weight:800;
}
.hero-avatar img { width:100%; height:100%; object-fit:cover; }
.back-link { font-size:12px; color:var(--text-400); display:inline-flex; align-items:center; gap:4px; margin-bottom:8px; text-decoration:none; }
.back-link:hover { color:var(--text-200); }
.hero-name { font-size:20px; font-weight:800; letter-spacing:-0.4px; color:var(--text-100); line-height:1.2; }
.hero-sub  { font-size:13px; color:var(--text-300); margin-top:2px; }
.hero-badges { display:flex; gap:6px; flex-wrap:wrap; margin-top:10px; }
.hero-badge {
    display:inline-flex; align-items:center; gap:5px; font-size:11.5px; font-weight:600;
    padding:3px 10px; border-radius:20px; background:var(--bg-elevated); border:1px solid var(--border-subtle); color:var(--text-300);
}
.hero-actions { display:flex; align-items:center; gap:8px; flex-wrap:wrap; }

/* Info card */
.info-row   { display:flex; justify-content:space-between; align-items:flex-start; padding:10px 0; border-bottom:1px solid var(--border-subtle); font-size:13px; }
.info-row:last-child { border-bottom:none; }
.info-label { color:var(--text-200); flex-shrink:0; margin-right:12px; }
.info-val   { color:var(--text-100); font-weight:500; text-align:right; word-break:break-word; }

/* Status badges */
.t-status { display:inline-flex; align-items:center; gap:5px; font-size:11px; font-weight:600; padding:2px 8px; border-radius:20px; }
.t-status::before { content:''; width:5px; height:5px; border-radius:50%; }
.t-active    { background:var(--green-dim); color:var(--green); }
.t-active::before { background:var(--green); }
.t-inactive  { background:var(--amber-dim); color:var(--amber); }
.t-inactive::before { background:var(--amber); }
.t-suspended { background:var(--red-dim); color:var(--red); }
.t-suspended::before { background:var(--red); }

/* User quota */
.quota-wrap  { margin:16px 0; }
.quota-bar   { height:8px; border-radius:4px; background:var(--border-subtle); overflow:hidden; }
.quota-fill  { height:100%; border-radius:4px; background:var(--green); transition:width 0.3s; }
.quota-fill.warn { background:var(--amber); }
.quota-fill.full { background:var(--red); }
.quota-nums  { display:flex; justify-content:space-between; font-size:12px; color:var(--text-300); font-family:var(--mono); margin-top:4px; }

/* Sub status */
.sub-pill { font-size:11px; font-weight:600; padding:2px 8px; border-radius:20px; }
.sub-active    { background:var(--green-dim); color:var(--green); }
.sub-trial     { background:rgba(99,120,255,0.1); color:#6378ff; }
.sub-expired   { background:var(--red-dim); color:var(--red); }
.sub-cancelled { background:var(--border-subtle); color:var(--text-400); }
.sub-pending_payment { background:var(--amber-dim); color:var(--amber); }

/* Payment history */
.pay-row { display:flex; align-items:flex-start; gap:12px; padding:12px 20px; border-bottom:1px solid var(--border-subtle); transition:background 0.15s; }
.pay-row:last-child { border-bottom:none; }
.pay-row:hover { background:var(--bg-hover); }
.pay-icon { width:32px; height:32px; border-radius:50%; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.pay-title { font-size:13px; font-weight:600; color:var(--text-100); }
.pay-sub   { font-size:11.5px; color:var(--text-300); margin-top:2px; font-family:var(--mono); }
.pay-amount{ font-size:14px; font-weight:700; color:var(--green); font-family:var(--mono); margin-left:auto; text-align:right; flex-shrink:0; }

/* Users table */
.user-type-badge { font-size:10px; font-weight:700; padding:2px 7px; border-radius:12px; text-transform:uppercase; letter-spacing:0.5px; }
.ut-admin { background:rgba(99,120,255,0.1); color:#6378ff; }
.ut-staff { background:var(--border-subtle); color:var(--text-300); }

/* Toggle form */
.status-select { font-size:12px; padding:4px 8px; background:var(--bg-input); border:1px solid var(--border-default); border-radius:var(--r-sm); color:var(--text-100); cursor:pointer; }

/* Module access grid */
.module-grid { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:12px; min-width:0; }
@media(max-width:1000px){ .module-grid{ grid-template-columns:repeat(2,minmax(0,1fr)); } }
@media(max-width:560px){ .module-grid{ grid-template-columns:minmax(0,1fr); } }
.module-card {
    background:var(--bg-elevated); border:1px solid var(--border-subtle); border-radius:12px;
    padding:16px; display:flex; flex-direction:column; gap:10px;
    transition:border-color 0.18s ease, box-shadow 0.18s ease;
    min-width:0;
}
.module-card:hover { border-color:var(--border-default); box-shadow:var(--shadow-sm); }
.module-top { display:flex; align-items:flex-start; justify-content:space-between; gap:10px; min-width:0; }
.module-heading { display:flex; align-items:flex-start; gap:10px; min-width:0; flex:1; }
.module-icon { width:34px; height:34px; border-radius:9px; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.module-icon svg { width:17px; height:17px; }
.module-name { font-size:13px; font-weight:700; color:var(--text-100); min-width:0; }
.module-desc { font-size:11px; color:var(--text-300); margin-top:2px; line-height:1.4; min-width:0; }
.module-note { font-size:11px; color:var(--text-300); line-height:1.5; }
.module-note strong { color:var(--text-100); }
.module-actions { display:flex; gap:6px; flex-wrap:wrap; margin-top:auto; padding-top:2px; }
</style>
@endpush

@section('content')

@php
    $icons = [
        'users'      => 'M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.75 3.75 0 11-6.75 0 3.75 3.75 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z',
        'card'       => 'M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z',
        'shield'     => 'M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z',
        'cash'       => 'M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75',
        'cube'       => 'M21 7.5l-9-5.25L3 7.5m18 0l-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9',
        'wrench'     => 'M21.75 6.75a4.5 4.5 0 01-4.884 4.484c-1.076-.091-2.264.071-2.95.904l-7.152 8.684a2.548 2.548 0 11-3.586-3.586l8.684-7.152c.833-.686.995-1.874.904-2.95a4.5 4.5 0 016.336-4.486l-3.276 3.276a3.004 3.004 0 002.25 2.25l3.276-3.276c.256.565.398 1.192.398 1.852z',
        'renew'      => 'M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99',
        'calendar'   => 'M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5',
        'clock'      => 'M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z',
        'chat'       => 'M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a5.969 5.969 0 01-.474-.065 4.48 4.48 0 00.978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z',
        'building'   => 'M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21',
    ];

    $initials = strtoupper(substr($tenant->name, 0, 2));

    $sub  = $tenant->subscription;
    $plan = $sub?->plan;

    $maxUsers = $plan ? (int)($plan->features['users'] ?? 0) : 0;
    $userPct  = $maxUsers > 0 ? min(100, round($userCount / $maxUsers * 100)) : 0;

    $lifetimeRevenue = $paymentHistory->reduce(function ($carry, $payment) {
        if (empty($payment->razorpay_payment_id)) return $carry;
        $amount = $payment->original_amount ?? ($payment->billing_cycle === 'yearly'
            ? $payment->plan?->yearly_price
            : $payment->plan?->monthly_price);
        return $carry + max(0, ($amount ?? 0) - ($payment->discount_amount ?? 0));
    }, 0);
    $paidCount = $paymentHistory->filter(fn($p) => !empty($p->razorpay_payment_id))->count();

    $subSubtext = 'No active subscription';
    $subCardClass = 's-red';
    if ($sub) {
        $subCardClass = match($sub->status) {
            'active'  => 's-green',
            'trial'   => 's-blue',
            default   => 's-red',
        };
        if ($sub->status === 'trial' && $sub->trial_ends_at) {
            $subSubtext = 'Trial ends ' . $sub->trial_ends_at->format('d M Y');
        } elseif ($sub->ends_at) {
            $subSubtext = $sub->ends_at->isPast()
                ? 'Expired ' . $sub->ends_at->diffForHumans()
                : 'Renews ' . $sub->ends_at->diffForHumans();
        }
    }

    $moduleConfigs = [
        'manufacturing' => [
            'label' => 'Manufacturing',
            'desc'  => 'Work Orders + Product Batches (production tracking)',
            'icon'  => 'cube',
            'toggle_route' => 'superadmin.tenants.toggle-manufacturing',
            'clear_route'  => 'superadmin.tenants.clear-manufacturing-override',
            'params' => [$tenant],
        ],
        'service' => [
            'label' => 'Service Catalog',
            'desc'  => 'Service line items in Quotations/Invoices',
            'icon'  => 'wrench',
            'toggle_route' => 'superadmin.tenants.toggle-service',
            'clear_route'  => 'superadmin.tenants.clear-service-override',
            'params' => [$tenant],
        ],
        'subscriptions' => [
            'label' => 'Service Subscriptions',
            'desc'  => 'Customer-level subscription tracking, expiry reminders, renewals',
            'icon'  => 'renew',
            'toggle_route' => 'superadmin.tenants.toggle-module',
            'clear_route'  => 'superadmin.tenants.clear-module-override',
            'params' => [$tenant, 'subscriptions'],
        ],
        'appointments' => [
            'label' => 'Appointments / Booking',
            'desc'  => 'Public online booking link + staff appointment management',
            'icon'  => 'calendar',
            'toggle_route' => 'superadmin.tenants.toggle-module',
            'clear_route'  => 'superadmin.tenants.clear-module-override',
            'params' => [$tenant, 'appointments'],
        ],
        'time_tracking' => [
            'label' => 'Time Tracking',
            'desc'  => 'Task timers, billable hours, convert time to invoices',
            'icon'  => 'clock',
            'toggle_route' => 'superadmin.tenants.toggle-module',
            'clear_route'  => 'superadmin.tenants.clear-module-override',
            'params' => [$tenant, 'time_tracking'],
        ],
        'tickets' => [
            'label' => 'Tickets / Helpdesk',
            'desc'  => 'Customer support tickets, public submission form, reply thread',
            'icon'  => 'chat',
            'toggle_route' => 'superadmin.tenants.toggle-module',
            'clear_route'  => 'superadmin.tenants.clear-module-override',
            'params' => [$tenant, 'tickets'],
        ],
    ];
@endphp

<a href="{{ route('superadmin.tenants.index') }}" class="back-link">
    <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/></svg>
    All Tenants
</a>

@if(session('success'))
<div class="alert alert-success" style="margin-bottom:16px;">{{ session('success') }}</div>
@endif

{{-- Hero --}}
<div class="hero-card">
    <div class="hero-left">
        <div class="hero-avatar">
            @if($tenant->logo)
                <img src="{{ asset('storage/' . $tenant->logo) }}" alt="{{ $tenant->name }}">
            @else
                {{ $initials }}
            @endif
        </div>
        <div style="min-width:0;">
            <div class="hero-name">{{ $tenant->name }}</div>
            <div class="hero-sub">{{ $tenant->subdomain }} &middot; {{ $tenant->email }}</div>
            <div class="hero-badges">
                <span class="t-status t-{{ $tenant->status }}">{{ ucfirst($tenant->status) }}</span>
                <span class="hero-badge">
                    <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icons['card'] }}"/></svg>
                    {{ $plan->name ?? 'No Plan' }}
                </span>
                <span class="hero-badge">
                    <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icons['calendar'] }}"/></svg>
                    Joined {{ $tenant->created_at->format('d M Y') }}
                </span>
            </div>
        </div>
    </div>
    <div class="hero-actions">
        <form method="POST" action="{{ route('superadmin.tenants.toggle-status', $tenant) }}" style="display:flex;align-items:center;gap:8px;">
            @csrf
            <select name="status" class="status-select" onchange="this.form.submit()">
                <option value="active"    {{ $tenant->status === 'active'    ? 'selected' : '' }}>Active</option>
                <option value="inactive"  {{ $tenant->status === 'inactive'  ? 'selected' : '' }}>Inactive</option>
                <option value="suspended" {{ $tenant->status === 'suspended' ? 'selected' : '' }}>Suspended</option>
            </select>
            <button type="submit" class="btn btn-secondary btn-sm">Update Status</button>
        </form>
        <a href="{{ route('superadmin.tenant-webhooks.index', $tenant) }}" class="btn btn-secondary btn-sm">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="width:14px;height:14px;">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m13.35-.622l1.757-1.757a4.5 4.5 0 00-6.364-6.364l-4.5 4.5a4.5 4.5 0 001.242 7.244"/>
            </svg>
            Webhooks
        </a>
    </div>
</div>

{{-- Stats --}}
<div class="stats-grid" style="grid-template-columns:repeat(4,1fr);">
    <div class="stat-card s-blue">
        <div class="stat-top">
            <div class="stat-icon s-blue">
                <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icons['users'] }}"/></svg>
            </div>
        </div>
        <div class="stat-num">{{ $userCount }}{{ $maxUsers > 0 ? ' / '.$maxUsers : '' }}</div>
        <div class="stat-label">Team Members</div>
        <div class="stat-sub">{{ $maxUsers > 0 ? $userPct.'% of plan seats used' : 'Unlimited seats' }}</div>
    </div>

    <div class="stat-card s-purple">
        <div class="stat-top">
            <div class="stat-icon s-purple">
                <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icons['card'] }}"/></svg>
            </div>
        </div>
        <div class="stat-num">{{ $plan->name ?? '—' }}</div>
        <div class="stat-label">Current Plan</div>
        <div class="stat-sub">
            @if($sub && $plan)
                {{ ucfirst($sub->billing_cycle) }} &middot; ₹{{ number_format($sub->billing_cycle === 'yearly' ? $plan->yearly_price : $plan->monthly_price) }}
            @else
                No billing on file
            @endif
        </div>
    </div>

    <div class="stat-card {{ $subCardClass }}">
        <div class="stat-top">
            <div class="stat-icon {{ $subCardClass }}">
                <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icons['shield'] }}"/></svg>
            </div>
        </div>
        <div class="stat-num">{{ $sub ? ucfirst($sub->status) : 'None' }}</div>
        <div class="stat-label">Subscription Status</div>
        <div class="stat-sub">{{ $subSubtext }}</div>
    </div>

    <div class="stat-card s-amber">
        <div class="stat-top">
            <div class="stat-icon s-amber">
                <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icons['cash'] }}"/></svg>
            </div>
        </div>
        <div class="stat-num">₹{{ number_format($lifetimeRevenue) }}</div>
        <div class="stat-label">Lifetime Revenue</div>
        <div class="stat-sub">{{ $paidCount }} paid invoice{{ $paidCount !== 1 ? 's' : '' }}</div>
    </div>
</div>

<div class="show-grid">

    {{-- Tenant info card --}}
    <div class="card">
            <div class="card-header">
                <div class="card-header-flex">
                    <div class="card-icon" style="background:var(--accent-dim);color:var(--accent);">
                        <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icons['building'] }}"/></svg>
                    </div>
                    <div class="card-title">Tenant Info</div>
                </div>
            </div>
            <div class="card-body">
                <div class="info-row">
                    <span class="info-label">Company</span>
                    <span class="info-val">{{ $tenant->name }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Email</span>
                    <span class="info-val">{{ $tenant->email }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Phone</span>
                    <span class="info-val">{{ $tenant->phone ?? '—' }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Subdomain</span>
                    <span class="info-val td-mono">{{ $tenant->subdomain }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Timezone</span>
                    <span class="info-val">{{ $tenant->timezone }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Currency</span>
                    <span class="info-val">{{ $tenant->currency }}</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Joined</span>
                    <span class="info-val">{{ $tenant->created_at->format('d M Y, h:i A') }}</span>
                </div>
            </div>
        </div>

        {{-- Current subscription card --}}
        <div class="card">
            <div class="card-header">
                <div class="card-header-flex">
                    <div class="card-icon" style="background:var(--purple-dim);color:var(--purple);">
                        <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icons['card'] }}"/></svg>
                    </div>
                    <div class="card-title">Current Subscription</div>
                </div>
                @if($sub)
                    <span class="sub-pill sub-{{ $sub->status }}">{{ ucfirst($sub->status) }}</span>
                @endif
            </div>
            <div class="card-body">
                @if($sub && $plan)
                    <div class="info-row">
                        <span class="info-label">Plan</span>
                        <span class="info-val" style="font-weight:700;color:var(--text-100)">{{ $plan->name }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Billing</span>
                        <span class="info-val">{{ ucfirst($sub->billing_cycle) }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Price</span>
                        <span class="info-val td-mono">
                            ₹{{ number_format($sub->billing_cycle === 'yearly' ? $plan->yearly_price : $plan->monthly_price) }}
                            /{{ $sub->billing_cycle === 'yearly' ? 'yr' : 'mo' }}
                        </span>
                    </div>
                    @if($sub->started_at)
                    <div class="info-row">
                        <span class="info-label">Started</span>
                        <span class="info-val">{{ $sub->started_at->format('d M Y') }}</span>
                    </div>
                    @endif
                    @if($sub->ends_at)
                    <div class="info-row">
                        <span class="info-label">Expires</span>
                        <span class="info-val" style="{{ $sub->ends_at->isPast() ? 'color:var(--red)' : '' }}">
                            {{ $sub->ends_at->format('d M Y') }}
                            @if(!$sub->ends_at->isPast())
                                ({{ $sub->ends_at->diffForHumans() }})
                            @else
                                (Expired)
                            @endif
                        </span>
                    </div>
                    @endif
                    @if($sub->trial_ends_at && $sub->status === 'trial')
                    <div class="info-row">
                        <span class="info-label">Trial ends</span>
                        <span class="info-val">{{ $sub->trial_ends_at->format('d M Y') }}</span>
                    </div>
                    @endif

                    {{-- User quota from plan features --}}
                    @php
                        $pct     = $userPct;
                        $fillCls = $pct >= 100 ? 'full' : ($pct >= 80 ? 'warn' : '');
                    @endphp
                    <div class="quota-wrap">
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;">
                            <span style="font-size:12.5px;font-weight:600;color:var(--text-200)">Users</span>
                            <span style="font-size:11px;color:var(--text-200);font-family:var(--mono)">
                                {{ $userCount }} / {{ $maxUsers > 0 ? $maxUsers : '∞' }} seats used
                            </span>
                        </div>
                        @if($maxUsers > 0)
                        <div class="quota-bar">
                            <div class="quota-fill {{ $fillCls }}" style="width:{{ $pct }}%"></div>
                        </div>
                        <div class="quota-nums">
                            <span>0</span>
                            @if($pct >= 80)
                                <span style="color:{{ $pct >= 100 ? 'var(--red)' : 'var(--amber)' }}">
                                    {{ $pct }}% used{{ $pct >= 100 ? ' — FULL' : '' }}
                                </span>
                            @endif
                            <span>{{ $maxUsers }}</span>
                        </div>
                        @endif
                    </div>

                    {{-- Plan features summary --}}
                    <div style="margin-top:8px;padding-top:12px;border-top:1px solid var(--border-subtle);">
                        <div style="font-size:11px;font-weight:700;color:var(--text-200);text-transform:uppercase;letter-spacing:0.6px;margin-bottom:8px;">Plan Features</div>
                        <div style="display:flex;flex-wrap:wrap;gap:6px;">
                            @foreach($plan->features as $feat => $val)
                            <span class="feat-chip {{ is_bool($val) ? ($val ? 'on' : 'off') : 'num' }}">
                                <span class="fc-label">{{ ucfirst(str_replace('_',' ',$feat)) }}</span>
                                @if(is_bool($val))
                                    {{ $val ? '✓' : '✗' }}
                                @else
                                    {{ $val }}
                                @endif
                            </span>
                            @endforeach
                        </div>
                    </div>

                @else
                    <div style="text-align:center;padding:24px;color:var(--text-400);font-size:13px;">No active subscription.</div>
                @endif
            </div>
        </div>

</div>

{{-- Users list --}}
<div class="card stack-card">
            <div class="card-header">
                <div class="card-header-flex">
                    <div class="card-icon" style="background:var(--accent-dim);color:var(--accent);">
                        <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icons['users'] }}"/></svg>
                    </div>
                    <div>
                        <div class="card-title">Users</div>
                        <div class="card-subtitle">{{ $userCount }} user{{ $userCount !== 1 ? 's' : '' }}{{ $planUserLimit ? ' of '.$planUserLimit.' allowed' : '' }}</div>
                    </div>
                </div>
            </div>
            <div style="overflow-x:auto;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Type</th>
                            <th>Role</th>
                            <th>Last Login</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $user)
                        <tr>
                            <td data-label="Name">
                                <div class="td-name">{{ $user->name }}</div>
                                <div style="font-size:11.5px;color:var(--text-300)">{{ $user->email }}</div>
                            </td>
                            <td data-label="Type">
                                <span class="user-type-badge {{ $user->user_type === 'tenant_admin' ? 'ut-admin' : 'ut-staff' }}">
                                    {{ $user->user_type === 'tenant_admin' ? 'Admin' : 'Staff' }}
                                </span>
                            </td>
                            <td style="font-size:12px;color:var(--text-300)" data-label="Role">
                                {{ $user->roles->pluck('name')->implode(', ') ?: '—' }}
                            </td>
                            <td class="td-mono" style="font-size:11.5px;" data-label="Last Login">
                                @if($user->last_login_at)
                                    <div style="color:var(--text-200)">{{ $user->last_login_at->format('d M Y') }}</div>
                                    <div style="color:var(--text-400)">{{ $user->last_login_at->diffForHumans() }}</div>
                                @else
                                    <span style="color:var(--text-400)">Never</span>
                                @endif
                            </td>
                            <td data-label="Status">
                                @if($user->is_active)
                                    <span class="t-status t-active">Active</span>
                                @else
                                    <span class="t-status t-inactive">Inactive</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="5" style="text-align:center;padding:32px;color:var(--text-400);">No users found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
</div>

{{-- Payment history --}}
<div class="card stack-card">
            <div class="card-header">
                <div class="card-header-flex">
                    <div class="card-icon" style="background:var(--amber-dim);color:var(--amber);">
                        <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icons['cash'] }}"/></svg>
                    </div>
                    <div>
                        <div class="card-title">Payment History</div>
                        <div class="card-subtitle">{{ $paymentHistory->count() }} subscription record{{ $paymentHistory->count() !== 1 ? 's' : '' }}</div>
                    </div>
                </div>
            </div>

            @forelse($paymentHistory as $payment)
            @php
                $isPaid = !empty($payment->razorpay_payment_id);
                $amount = $payment->original_amount ?? ($payment->billing_cycle === 'yearly'
                    ? $payment->plan?->yearly_price
                    : $payment->plan?->monthly_price);
                $finalAmount = $amount - ($payment->discount_amount ?? 0);
            @endphp
            <div class="pay-row">
                <div class="pay-icon" style="background:{{ $isPaid ? 'var(--green-dim)' : 'var(--border-subtle)' }};">
                    @if($isPaid)
                        <svg width="14" height="14" fill="none" stroke="var(--green)" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                        </svg>
                    @else
                        <svg width="14" height="14" fill="none" stroke="var(--text-400)" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9.303 3.376c.866 1.5-.217 3.374-1.948 3.374H4.645c-1.73 0-2.813-1.874-1.948-3.374l7.028-12.124c.866-1.5 3.032-1.5 3.898 0l7.027 12.124z"/>
                        </svg>
                    @endif
                </div>
                <div style="flex:1;min-width:0;">
                    <div class="pay-title">
                        {{ $payment->plan?->name ?? 'Unknown Plan' }}
                        &mdash; {{ ucfirst($payment->billing_cycle) }}
                        <span class="sub-pill sub-{{ $payment->status }}" style="margin-left:6px;">{{ ucfirst($payment->status) }}</span>
                    </div>
                    <div class="pay-sub">
                        @if($payment->started_at)
                            {{ $payment->started_at->format('d M Y') }}
                            @if($payment->ends_at) &rarr; {{ $payment->ends_at->format('d M Y') }} @endif
                        @elseif($payment->created_at)
                            Created {{ $payment->created_at->format('d M Y') }}
                        @endif
                        @if($payment->razorpay_payment_id)
                            &middot; ID: {{ $payment->razorpay_payment_id }}
                        @endif
                        @if($payment->coupon)
                            &middot; Coupon: {{ $payment->coupon->code }}
                        @endif
                    </div>
                </div>
                <div class="pay-amount">
                    @if($finalAmount)
                        ₹{{ number_format($finalAmount) }}
                        @if($payment->discount_amount > 0)
                            <div style="font-size:10px;font-weight:400;color:var(--green);">-₹{{ number_format($payment->discount_amount) }} off</div>
                        @endif
                    @else
                        <span style="color:var(--text-400);font-size:12px;">Free / Trial</span>
                    @endif
                </div>
            </div>
            @empty
            <div style="text-align:center;padding:32px;color:var(--text-400);font-size:13px;">No payment records found.</div>
            @endforelse
</div>

{{-- Module access — superadmin per-tenant feature toggle --}}
<div class="card" style="margin-top:16px;">
    <div class="card-header">
        <div>
            <div class="card-title">Module Access</div>
            <div class="card-subtitle">Per-tenant feature overrides, independent of their plan</div>
        </div>
    </div>
    <div class="card-body">
        <div class="module-grid">
            @foreach($moduleConfigs as $modKey => $cfg)
            @php
                $modInPlan   = $tenant->moduleIncludedInPlan($modKey);
                $modOverride = $tenant->moduleOverride($modKey);
                $modOn       = $tenant->hasModuleEnabled($modKey);
            @endphp
            <div class="module-card">
                <div class="module-top">
                    <div class="module-heading">
                        <div class="module-icon" style="background:{{ $modOn ? 'var(--green-dim)' : 'var(--bg-hover)' }};color:{{ $modOn ? 'var(--green)' : 'var(--text-400)' }};">
                            <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icons[$cfg['icon']] }}"/></svg>
                        </div>
                        <div style="min-width:0;">
                            <div class="module-name">{{ $cfg['label'] }}</div>
                            <div class="module-desc">{{ $cfg['desc'] }}</div>
                        </div>
                    </div>
                    <span class="t-status {{ $modOn ? 't-active' : 't-suspended' }}">{{ $modOn ? 'On' : 'Off' }}</span>
                </div>

                <div class="module-note">
                    @if($modOverride === true)
                        Manually <strong>force-enabled</strong>{{ $modInPlan ? ' (plan already includes it too)' : ", overriding their {$plan?->name} plan" }}.
                    @elseif($modOverride === false)
                        Manually <strong>force-disabled</strong>, overriding their {{ $plan?->name ?? 'current' }} plan.
                    @elseif($modInPlan)
                        Included via the <strong>{{ $plan->name }}</strong> plan.
                    @else
                        Not in the {{ $plan?->name ?? 'current' }} plan, no override set.
                    @endif
                </div>

                <div class="module-actions">
                    <form method="POST" action="{{ route($cfg['toggle_route'], $cfg['params']) }}">
                        @csrf
                        <input type="hidden" name="enabled" value="1">
                        <button type="submit" class="btn btn-sm {{ $modOn ? 'btn-secondary' : 'btn-primary' }}" {{ $modOverride === true ? 'disabled' : '' }}>
                            Force Enable
                        </button>
                    </form>
                    <form method="POST" action="{{ route($cfg['toggle_route'], $cfg['params']) }}">
                        @csrf
                        <input type="hidden" name="enabled" value="0">
                        <button type="submit" class="btn btn-sm {{ !$modOn ? 'btn-secondary' : 'btn-primary' }}" {{ $modOverride === false ? 'disabled' : '' }}>
                            Force Disable
                        </button>
                    </form>
                    @if(!is_null($modOverride))
                    <form method="POST" action="{{ route($cfg['clear_route'], $cfg['params']) }}">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-secondary">Reset</button>
                    </form>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
    </div>
</div>

@endsection
