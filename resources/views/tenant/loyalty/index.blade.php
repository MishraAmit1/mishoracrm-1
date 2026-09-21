@extends('layouts.app')
@section('title', 'Loyalty')

@push('styles')
<style>
.stat-row { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:12px; margin-bottom:18px; }
@media(max-width:800px){ .stat-row { grid-template-columns:repeat(2,minmax(0,1fr)); } }
.stat-card { background:var(--bg-surface); border:1px solid var(--border-default); border-radius:var(--r-md); padding:14px 16px; }
.ov-grid { display:grid; grid-template-columns:3fr 2fr; gap:14px; margin-bottom:18px; }
@media(max-width:900px){ .ov-grid { grid-template-columns:1fr; } }
.ov-card { background:var(--bg-surface); border:1px solid var(--border-default); border-radius:var(--r-md); padding:16px 18px; min-width:0; }
.ov-head { display:flex; justify-content:space-between; align-items:baseline; gap:10px; margin-bottom:12px; }
.ov-title { font-size:13px; font-weight:700; color:var(--text-100); }
.ov-sub { font-size:12px; color:var(--text-400); }
.ov-trend { display:flex; align-items:flex-end; gap:3px; height:110px; }
.ov-bar { flex:1; min-width:3px; background:var(--accent); border-radius:3px 3px 0 0; }
.ov-axis { display:flex; justify-content:space-between; font-size:11px; color:var(--text-400); margin-top:6px; }
.ov-row { display:flex; justify-content:space-between; align-items:center; gap:10px; padding:8px 0; border-bottom:1px solid var(--border-subtle); }
.ov-row:last-child { border-bottom:none; }
.ov-name { font-size:13px; font-weight:600; color:var(--text-100); text-decoration:none; }
.ov-desc { font-size:11.5px; color:var(--text-400); margin-top:1px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.ov-pts { font-size:13px; font-weight:700; white-space:nowrap; }
.ov-pts.pos { color:var(--green); }
.ov-pts.neg { color:var(--red); }
.ov-empty { font-size:13px; color:var(--text-400); padding:18px 0; text-align:center; }
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
        @if($tenant->hasModuleEnabled('customer_portal'))
        <a href="{{ route('tenant.loyalty.qr-kit.index') }}" class="btn btn-secondary">QR Kit</a>
        <a href="{{ route('tenant.loyalty.needs-review') }}" class="btn btn-secondary">Needs review</a>
        @endif
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

@if($overview['portal'])
<div class="stat-row">
    <div class="stat-card">
        <div class="stat-val">{{ number_format($overview['portal']['linked']) }}</div>
        <div class="stat-lbl">Wallet customers</div>
    </div>
    <div class="stat-card">
        <div class="stat-val">{{ number_format($overview['portal']['pending']) }}</div>
        <div class="stat-lbl">Awaiting confirmation</div>
    </div>
    @if($overview['portal']['stamps'])
    <div class="stat-card">
        <div class="stat-val">{{ number_format($overview['portal']['stamps']['active_cards']) }}</div>
        <div class="stat-lbl">Active stamp cards</div>
    </div>
    <div class="stat-card">
        <div class="stat-val">{{ number_format($overview['portal']['stamps']['unclaimed']) }}</div>
        <div class="stat-lbl">Rewards unclaimed · {{ number_format($overview['portal']['stamps']['claimed']) }} claimed</div>
    </div>
    @endif
</div>
@endif

<div class="ov-grid">
    <div class="ov-card">
        <div class="ov-head">
            <div class="ov-title">Visits — last 30 days</div>
            <div class="ov-sub">{{ number_format($overview['visits']) }} paid {{ \Illuminate\Support\Str::plural('visit', $overview['visits']) }}</div>
        </div>
        @php $peak = max(1, $overview['trend']->max('count')); @endphp
        <div class="ov-trend" role="img" aria-label="Paid visits per day for the last 30 days">
            @foreach($overview['trend'] as $day)
            <span class="ov-bar" title="{{ \Illuminate\Support\Carbon::parse($day['date'])->format('d M') }}: {{ $day['count'] }} {{ \Illuminate\Support\Str::plural('visit', $day['count']) }}"
                  style="height:{{ $day['count'] > 0 ? max(6, round($day['count'] / $peak * 100)) : 3 }}%;{{ $day['count'] === 0 ? 'opacity:.25' : '' }}"></span>
            @endforeach
        </div>
        <div class="ov-axis">
            <span>{{ \Illuminate\Support\Carbon::parse($overview['trend']->first()['date'])->format('d M') }}</span>
            <span>Peak {{ $peak === 1 && $overview['visits'] === 0 ? 0 : $peak }}/day</span>
            <span>Today</span>
        </div>
    </div>

    <div class="ov-card">
        <div class="ov-head"><div class="ov-title">Recent activity</div></div>
        @php $typeCfg = config('crm.loyalty.transaction_types'); @endphp
        @forelse($overview['activity'] as $tx)
        @php $unit = $tx->type === 'stamp' ? ' stamp' : ($tx->type === 'stamp_reward' ? ' reward' : ''); @endphp
        <div class="ov-row">
            <div style="min-width:0">
                <a href="{{ $tx->contact ? route('tenant.contacts.show', $tx->contact_id) : '#' }}" class="ov-name">{{ $tx->contact?->name ?? 'Deleted contact' }}</a>
                <div class="ov-desc">{{ $typeCfg[$tx->type]['label'] ?? ucfirst($tx->type) }}@if($tx->description) · {{ \Illuminate\Support\Str::limit($tx->description, 38) }}@endif · {{ $tx->created_at->diffForHumans(null, true) }}</div>
            </div>
            <div class="ov-pts {{ $tx->points >= 0 ? 'pos' : 'neg' }}">{{ $tx->points >= 0 ? '+' : '' }}{{ number_format($tx->points) }}{{ $unit }}{{ $unit && abs($tx->points) !== 1 ? 's' : '' }}</div>
        </div>
        @empty
        <div class="ov-empty">No activity yet.</div>
        @endforelse
    </div>
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
