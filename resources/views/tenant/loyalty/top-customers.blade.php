@extends('layouts.app')
@section('title', 'Top Customers')

@push('styles')
<style>
.s-tab { padding:7px 14px; border-radius:var(--r-sm); font-size:12.5px; font-weight:600; text-decoration:none; color:var(--text-300); border:1.5px solid transparent; }
.s-tab.active { background:var(--accent-dim); color:var(--accent); border-color:rgba(var(--accent-rgb),.25); }
.sub-table { width:100%; border-collapse:collapse; background:var(--bg-surface); border:1px solid var(--border-default); border-radius:var(--r-lg); overflow:hidden; }
.sub-table th { padding:10px 14px; text-align:left; font-size:11px; font-weight:700; color:var(--text-400); text-transform:uppercase; letter-spacing:.4px; background:var(--bg-elevated); border-bottom:1px solid var(--border-subtle); }
.sub-table td { padding:11px 14px; border-bottom:1px solid var(--border-subtle); font-size:13.5px; color:var(--text-100); }
.sub-table tr:last-child td { border-bottom:none; }
.sub-table tr:hover td { background:var(--bg-elevated); cursor:pointer; }
.rank { font-weight:800; color:var(--text-400); width:36px; }
.tier-badge { display:inline-block; padding:2px 9px; border-radius:20px; font-size:11.5px; font-weight:600; }
.tier-bronze { background:var(--amber-dim); color:var(--amber); } .tier-silver { background:var(--accent-dim); color:var(--accent); } .tier-gold { background:var(--green-dim); color:var(--green); }
</style>
@endpush

@section('content')

<div class="page-head">
    <div>
        <div style="font-size:12px;color:var(--text-300);margin-bottom:4px">
            <a href="{{ route('tenant.loyalty.index') }}" style="color:var(--text-300);text-decoration:none">Loyalty</a> › Top Customers
        </div>
        <div class="page-title">Top Customers</div>
        <div class="page-sub">Your biggest spenders — recognise them, treat them well.</div>
    </div>
</div>

<div style="display:flex;gap:4px;flex-wrap:wrap;margin-bottom:16px">
    @foreach(['month' => 'This month', '30d' => 'Last 30 days', 'year' => 'This year', 'all' => 'All time'] as $k => $label)
    <a href="{{ route('tenant.loyalty.top-customers', ['period' => $k]) }}" class="s-tab {{ $period === $k ? 'active' : '' }}">{{ $label }}</a>
    @endforeach
</div>

<div class="sub-table-wrap">
<table class="sub-table">
    <thead>
        <tr><th>#</th><th>Customer</th><th>Phone</th><th>Tier</th><th style="text-align:right">Spend</th><th style="text-align:right">Points</th></tr>
    </thead>
    <tbody>
        @forelse($customers as $i => $c)
        <tr onclick="window.location='{{ route('tenant.contacts.show', $c->id) }}'">
            <td class="rank">{{ $i + 1 }}</td>
            <td style="font-weight:600">{{ $c->name }}</td>
            <td>{{ $c->phone ?? '—' }}</td>
            <td>@if($c->loyalty_tier)<span class="tier-badge tier-{{ $c->loyalty_tier }}">{{ $c->loyaltyTierLabel() }}</span>@else — @endif</td>
            <td style="text-align:right;font-weight:700">₹{{ number_format((float) $c->_spend, 0) }}</td>
            <td style="text-align:right">{{ number_format($c->loyalty_points) }}</td>
        </tr>
        @empty
        <tr><td colspan="6" style="text-align:center;padding:40px;color:var(--text-400)">No paid invoices in this period yet.</td></tr>
        @endforelse
    </tbody>
</table>
</div>

@endsection
