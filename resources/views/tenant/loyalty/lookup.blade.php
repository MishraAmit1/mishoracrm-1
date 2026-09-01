@extends('layouts.app')
@section('title', 'Loyalty Lookup')

@push('styles')
<style>
.lk-form { display:flex; gap:8px; margin-bottom:20px; max-width:460px; }
.lk-form input { flex:1; padding:11px 14px; background:var(--bg-input); border:1.5px solid var(--border-default); border-radius:var(--r-sm); color:var(--text-100); font-size:15px; }
.lk-card { background:var(--bg-surface); border:1px solid var(--border-default); border-radius:var(--r-lg); padding:18px 20px; margin-bottom:12px; max-width:460px; }
.lk-name { font-size:16px; font-weight:700; color:var(--text-100); }
.lk-sub { font-size:12.5px; color:var(--text-400); margin-top:2px; }
.lk-pts { font-size:28px; font-weight:800; color:var(--text-100); margin-top:10px; }
.lk-tier { display:inline-block; padding:3px 11px; border-radius:20px; font-size:12px; font-weight:700; margin-top:6px; }
.lk-bronze { background:var(--amber-dim); color:var(--amber); }
.lk-silver { background:var(--accent-dim); color:var(--accent); }
.lk-gold   { background:var(--green-dim); color:var(--green); }
</style>
@endpush

@section('content')

<div class="page-head">
    <div>
        <div style="font-size:12px;color:var(--text-300);margin-bottom:4px">
            <a href="{{ route('tenant.loyalty.index') }}" style="color:var(--text-300);text-decoration:none">Loyalty</a> › Counter Lookup
        </div>
        <div class="page-title">Counter Lookup</div>
        <div class="page-sub">Type a phone number or name to check a customer's points at the billing counter.</div>
    </div>
</div>

<form method="GET" class="lk-form">
    <input type="text" name="q" value="{{ $q }}" autofocus placeholder="Phone number or name…"/>
    <button type="submit" class="btn btn-primary">Check</button>
</form>

@if($q !== '')
    @forelse($matches as $c)
    <div class="lk-card">
        <div class="lk-name">{{ $c->name }}</div>
        <div class="lk-sub">{{ $c->phone ?? 'no phone' }}{{ $c->email ? ' · ' . $c->email : '' }}</div>
        <div class="lk-pts">{{ number_format($c->loyalty_points) }} <span style="font-size:14px;font-weight:600;color:var(--text-400)">points</span></div>
        @if($c->loyalty_tier)
        <div><span class="lk-tier lk-{{ $c->loyalty_tier }}">{{ $c->loyaltyTierLabel() }}</span></div>
        @endif
        <div class="lk-sub" style="margin-top:10px">
            Lifetime {{ number_format($c->loyalty_lifetime_points) }} ·
            <a href="{{ route('tenant.contacts.show', $c->id) }}" style="color:var(--accent);text-decoration:none">Open contact →</a>
        </div>
    </div>
    @empty
    <div class="lk-card" style="color:var(--text-400)">No customer found for “{{ $q }}”.</div>
    @endforelse
@endif

@endsection
