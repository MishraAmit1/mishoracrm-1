@extends('layouts.app')
@section('title', 'Win-back')

@push('styles')
<style>
.sub-table { width:100%; border-collapse:collapse; background:var(--bg-surface); border:1px solid var(--border-default); border-radius:var(--r-lg); overflow:hidden; }
.sub-table th { padding:10px 14px; text-align:left; font-size:11px; font-weight:700; color:var(--text-400); text-transform:uppercase; letter-spacing:.4px; background:var(--bg-elevated); border-bottom:1px solid var(--border-subtle); }
.sub-table td { padding:11px 14px; border-bottom:1px solid var(--border-subtle); font-size:13.5px; color:var(--text-100); }
.sub-table tr:last-child td { border-bottom:none; }
.tier-badge { display:inline-block; padding:2px 9px; border-radius:20px; font-size:11.5px; font-weight:600; }
.tier-bronze { background:var(--amber-dim); color:var(--amber); }
.tier-silver { background:var(--accent-dim); color:var(--accent); }
.tier-gold   { background:var(--green-dim); color:var(--green); }
</style>
@endpush

@section('content')

<div class="page-head">
    <div>
        <div style="font-size:12px;color:var(--text-300);margin-bottom:4px">
            <a href="{{ route('tenant.loyalty.index') }}" style="color:var(--text-300);text-decoration:none">Loyalty</a> › Win-back
        </div>
        <div class="page-title">Win-back</div>
        <div class="page-sub">Loyalty members with no paid invoice in the last {{ $days }} days. Reach out with an offer before they're gone for good.</div>
    </div>
</div>

<div class="sub-table-wrap">
<table class="sub-table">
    <thead>
        <tr>
            <th>Customer</th>
            <th>Phone</th>
            <th>Tier</th>
            <th style="text-align:right">Points</th>
            <th>Last visit</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
        @forelse($members as $c)
        <tr>
            <td style="font-weight:600"><a href="{{ route('tenant.contacts.show', $c->id) }}" style="color:var(--text-100);text-decoration:none">{{ $c->name }}</a></td>
            <td>{{ $c->phone ?? '—' }}</td>
            <td>
                @if($c->loyalty_tier)<span class="tier-badge tier-{{ $c->loyalty_tier }}">{{ $c->loyaltyTierLabel() }}</span>@else — @endif
            </td>
            <td style="text-align:right">{{ number_format($c->loyalty_points) }}</td>
            <td>{{ $c->last_paid_at ? \Illuminate\Support\Carbon::parse($c->last_paid_at)->format('d M Y') : '—' }}</td>
            <td style="text-align:right">
                @if($c->phone)
                <a href="https://wa.me/{{ preg_replace('/\D/', '', $c->phone) }}" target="_blank" class="btn btn-secondary btn-sm">WhatsApp</a>
                @endif
            </td>
        </tr>
        @empty
        <tr><td colspan="6" style="text-align:center;padding:40px;color:var(--text-400)">No lapsed members — everyone's been active. 🎉</td></tr>
        @endforelse
    </tbody>
</table>
</div>

<div style="margin-top:14px">{{ $members->links() }}</div>

@endsection
