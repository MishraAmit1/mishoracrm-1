@extends('layouts.app')
@section('title', 'Loyalty')

@push('styles')
<style>
.stat-row { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:12px; margin-bottom:18px; }
@media(max-width:800px){ .stat-row { grid-template-columns:repeat(2,minmax(0,1fr)); } }
.stat-card { background:var(--bg-surface); border:1px solid var(--border-default); border-radius:var(--r-md); padding:14px 16px; }
.stat-val { font-size:20px; font-weight:700; color:var(--text-100); }
.stat-lbl { font-size:11.5px; color:var(--text-400); text-transform:uppercase; letter-spacing:.4px; margin-top:2px; }
.sub-table { width:100%; border-collapse:collapse; background:var(--bg-surface); border:1px solid var(--border-default); border-radius:var(--r-lg); overflow:hidden; }
.sub-table th { padding:10px 14px; text-align:left; font-size:11px; font-weight:700; color:var(--text-400); text-transform:uppercase; letter-spacing:.4px; background:var(--bg-elevated); border-bottom:1px solid var(--border-subtle); }
.sub-table td { padding:11px 14px; border-bottom:1px solid var(--border-subtle); font-size:13.5px; color:var(--text-100); vertical-align:middle; }
.sub-table tr:last-child td { border-bottom:none; }
.sub-table tr:hover td { background:var(--bg-elevated); cursor:pointer; }
.tier-badge { display:inline-block; padding:2px 9px; border-radius:20px; font-size:11.5px; font-weight:600; }
.tier-bronze { background:var(--amber-dim); color:var(--amber); }
.tier-silver { background:var(--accent-dim); color:var(--accent); }
.tier-gold   { background:var(--green-dim); color:var(--green); }
.filters { display:flex; gap:8px; flex-wrap:wrap; margin-bottom:16px; }
.filters input, .filters select { padding:8px 12px; background:var(--bg-input); border:1.5px solid var(--border-default); border-radius:var(--r-sm); color:var(--text-100); font-size:13px; }
</style>
@endpush

@section('content')

<div class="page-head">
    <div>
        <div class="page-title">Loyalty</div>
        <div class="page-sub">Points, tiers and rewards for your repeat customers</div>
    </div>
    <div style="display:flex;gap:8px">
        <a href="{{ route('tenant.loyalty.lookup') }}" class="btn btn-secondary">Counter Lookup</a>
        <a href="{{ route('tenant.loyalty.top-customers') }}" class="btn btn-secondary">Top Customers</a>
        <a href="{{ route('tenant.loyalty.win-back') }}" class="btn btn-secondary">Win-back</a>
        @can('loyalty.manage')
        <a href="{{ route('tenant.loyalty.campaigns.index') }}" class="btn btn-secondary">Campaigns</a>
        <a href="{{ route('tenant.loyalty.settings') }}" class="btn btn-primary">Rules</a>
        @endcan
    </div>
</div>

@if(session('success'))
<div style="padding:10px 14px;background:var(--green-dim);border:1px solid rgba(52,199,89,.25);border-radius:var(--r-sm);margin-bottom:14px;font-size:13px;color:var(--green)">
    {{ session('success') }}
</div>
@endif
@if(session('error'))
<div style="padding:10px 14px;background:var(--red-dim);border:1px solid rgba(255,82,87,.25);border-radius:var(--r-sm);margin-bottom:14px;font-size:13px;color:var(--red)">
    {{ session('error') }}
</div>
@endif

@php $tierCfg = config('crm.loyalty.tiers'); @endphp

<div class="stat-row">
    <div class="stat-card">
        <div class="stat-val">{{ number_format($stats['members']) }}</div>
        <div class="stat-lbl">Members</div>
    </div>
    <div class="stat-card">
        <div class="stat-val">{{ number_format($stats['outstanding']) }}</div>
        <div class="stat-lbl">Points outstanding</div>
    </div>
    @foreach(['silver' => 'Silver members', 'gold' => 'Gold members'] as $t => $lbl)
    <div class="stat-card">
        <div class="stat-val">{{ number_format($stats['tiers'][$t] ?? 0) }}</div>
        <div class="stat-lbl">{{ $lbl }}</div>
    </div>
    @endforeach
</div>

<form method="GET" class="filters">
    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search name / phone / email"/>
    <select name="tier" onchange="this.form.submit()">
        <option value="">All tiers</option>
        @foreach($tierCfg as $key => $cfg)
        <option value="{{ $key }}" @selected(request('tier') === $key)>{{ $cfg['label'] }}</option>
        @endforeach
    </select>
    <button type="submit" class="btn btn-secondary btn-sm">Filter</button>
    @if(request('search') || request('tier'))
    <a href="{{ route('tenant.loyalty.index') }}" class="btn btn-secondary btn-sm">Clear</a>
    @endif
</form>

<div class="sub-table-wrap">
<table class="sub-table">
    <thead>
        <tr>
            <th>Customer</th>
            <th>Phone</th>
            <th>Tier</th>
            <th style="text-align:right">Points</th>
            <th style="text-align:right">Lifetime</th>
            <th>Last activity</th>
        </tr>
    </thead>
    <tbody>
        @forelse($members as $c)
        <tr onclick="window.location='{{ route('tenant.contacts.show', $c->id) }}'">
            <td style="font-weight:600">{{ $c->name }}</td>
            <td>{{ $c->phone ?? '—' }}</td>
            <td>
                @if($c->loyalty_tier)
                <span class="tier-badge tier-{{ $c->loyalty_tier }}">{{ $c->loyaltyTierLabel() }}</span>
                @else — @endif
            </td>
            <td style="text-align:right">{{ number_format($c->loyalty_points) }}</td>
            <td style="text-align:right">{{ number_format($c->loyalty_lifetime_points) }}</td>
            <td>{{ $c->loyalty_updated_at?->format('d M Y') ?? '—' }}</td>
        </tr>
        @empty
        <tr>
            <td colspan="6" style="text-align:center;padding:40px;color:var(--text-400)">
                No loyalty members yet. Points are earned automatically when a customer's invoice is fully paid.
            </td>
        </tr>
        @endforelse
    </tbody>
</table>
</div>

<div style="margin-top:14px">{{ $members->links() }}</div>

@endsection
