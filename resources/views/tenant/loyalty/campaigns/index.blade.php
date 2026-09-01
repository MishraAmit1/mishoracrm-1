@extends('layouts.app')
@section('title', 'Loyalty Campaigns')

@push('styles')
<style>
.sub-table { width:100%; border-collapse:collapse; background:var(--bg-surface); border:1px solid var(--border-default); border-radius:var(--r-lg); overflow:hidden; }
.sub-table th { padding:10px 14px; text-align:left; font-size:11px; font-weight:700; color:var(--text-400); text-transform:uppercase; letter-spacing:.4px; background:var(--bg-elevated); border-bottom:1px solid var(--border-subtle); }
.sub-table td { padding:11px 14px; border-bottom:1px solid var(--border-subtle); font-size:13.5px; color:var(--text-100); }
.sub-table tr:last-child td { border-bottom:none; }
.sub-table tr:hover td { background:var(--bg-elevated); cursor:pointer; }
.st { display:inline-block; padding:2px 9px; border-radius:20px; font-size:11px; font-weight:700; }
.st.draft  { background:var(--amber-dim); color:var(--amber); }
.st.active { background:var(--green-dim); color:var(--green); }
.st.ended  { background:var(--bg-elevated); color:var(--text-400); }
</style>
@endpush

@section('content')

<div class="page-head">
    <div>
        <div style="font-size:12px;color:var(--text-300);margin-bottom:4px">
            <a href="{{ route('tenant.loyalty.index') }}" style="color:var(--text-300);text-decoration:none">Loyalty</a> › Campaigns
        </div>
        <div class="page-title">Campaigns</div>
        <div class="page-sub">Target a customer segment with an offer or redeem code.</div>
    </div>
    <a href="{{ route('tenant.loyalty.campaigns.create') }}" class="btn btn-primary">+ New Campaign</a>
</div>

@if(session('success'))<div style="padding:10px 14px;background:var(--green-dim);border:1px solid rgba(52,199,89,.25);border-radius:var(--r-sm);margin-bottom:14px;font-size:13px;color:var(--green)">{{ session('success') }}</div>@endif
@if(session('error'))<div style="padding:10px 14px;background:var(--red-dim);border:1px solid rgba(255,82,87,.25);border-radius:var(--r-sm);margin-bottom:14px;font-size:13px;color:var(--red)">{{ session('error') }}</div>@endif

<div class="sub-table-wrap">
<table class="sub-table">
    <thead>
        <tr><th>Name</th><th>Segment</th><th>Reward</th><th>Recipients</th><th>Redeemed</th><th>Status</th></tr>
    </thead>
    <tbody>
        @forelse($campaigns as $c)
        <tr onclick="window.location='{{ route('tenant.loyalty.campaigns.show', $c->id) }}'">
            <td style="font-weight:600">{{ $c->name }}</td>
            <td>{{ ucfirst($c->segment_type) }}</td>
            <td>{{ $c->rewardLabel() }}</td>
            <td>{{ $c->status === 'draft' ? '—' : number_format($c->recipients_count) }}</td>
            <td>{{ number_format($c->redeemed_count) }}{{ $c->total_redemption_cap ? ' / ' . number_format($c->total_redemption_cap) : '' }}</td>
            <td><span class="st {{ $c->status }}">{{ ucfirst($c->status) }}</span></td>
        </tr>
        @empty
        <tr><td colspan="6" style="text-align:center;padding:40px;color:var(--text-400)">No campaigns yet.</td></tr>
        @endforelse
    </tbody>
</table>
</div>

<div style="margin-top:14px">{{ $campaigns->links() }}</div>

@endsection
