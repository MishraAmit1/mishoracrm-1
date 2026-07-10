@extends('layouts.app')
@section('title', $tenant->name . ' — Tenant Detail')

@push('styles')
<style>
/* Layout */
.show-grid { display:grid; grid-template-columns:340px 1fr; gap:16px; align-items:start; margin-bottom:16px; }
@media(max-width:1100px){ .show-grid{ grid-template-columns:1fr; } }

/* Info card */
.info-row   { display:flex; justify-content:space-between; align-items:flex-start; padding:10px 0; border-bottom:1px solid var(--border-subtle); font-size:13px; }
.info-row:last-child { border-bottom:none; }
.info-label { color:var(--text-300); flex-shrink:0; margin-right:12px; }
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
</style>
@endpush

@section('content')

<div class="page-head">
    <div>
        <a href="{{ route('superadmin.tenants.index') }}" style="font-size:12px;color:var(--text-400);display:flex;align-items:center;gap:4px;margin-bottom:6px;">
            <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/></svg>
            All Tenants
        </a>
        <div class="page-title">{{ $tenant->name }}</div>
        <div class="page-sub">{{ $tenant->subdomain }} &middot; {{ $tenant->email }}</div>
    </div>
    <div class="page-actions">
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

@if(session('success'))
<div class="alert alert-success" style="margin-bottom:16px;">{{ session('success') }}</div>
@endif

<div class="show-grid">

    {{-- Left: Info + Subscription --}}
    <div>
        {{-- Tenant info card --}}
        <div class="card" style="margin-bottom:16px;">
            <div class="card-header">
                <div class="card-title">Tenant Info</div>
                <span class="t-status t-{{ $tenant->status }}">{{ ucfirst($tenant->status) }}</span>
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
        @php
            $sub  = $tenant->subscription;
            $plan = $sub?->plan;
        @endphp
        <div class="card">
            <div class="card-header">
                <div class="card-title">Current Subscription</div>
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
                        $maxUsers = (int)($plan->features['users'] ?? 0);
                        $pct      = $maxUsers > 0 ? min(100, round($userCount / $maxUsers * 100)) : 0;
                        $fillCls  = $pct >= 100 ? 'full' : ($pct >= 80 ? 'warn' : '');
                    @endphp
                    <div class="quota-wrap">
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;">
                            <span style="font-size:12.5px;font-weight:600;color:var(--text-200)">Users</span>
                            <span style="font-size:11px;color:var(--text-300);font-family:var(--mono)">
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
                        <div style="font-size:11px;font-weight:700;color:var(--text-300);text-transform:uppercase;letter-spacing:0.6px;margin-bottom:8px;">Plan Features</div>
                        <div style="display:flex;flex-wrap:wrap;gap:6px;">
                            @foreach($plan->features as $feat => $val)
                            <span style="font-size:11px;padding:2px 8px;background:var(--bg-input);border:1px solid var(--border-default);border-radius:12px;color:var(--text-200);">
                                {{ ucfirst(str_replace('_',' ',$feat)) }}:
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

    {{-- Right: Users + Payment history --}}
    <div>

        {{-- Users list --}}
        <div class="card" style="margin-bottom:16px;">
            <div class="card-header">
                <div>
                    <div class="card-title">Users</div>
                    <div class="card-subtitle">{{ $userCount }} user{{ $userCount !== 1 ? 's' : '' }}{{ $planUserLimit ? ' of '.$planUserLimit.' allowed' : '' }}</div>
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
                                <div style="font-size:11.5px;color:var(--text-400)">{{ $user->email }}</div>
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
        <div class="card">
            <div class="card-header">
                <div>
                    <div class="card-title">Payment History</div>
                    <div class="card-subtitle">{{ $paymentHistory->count() }} subscription record{{ $paymentHistory->count() !== 1 ? 's' : '' }}</div>
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

    </div>
</div>

@endsection
