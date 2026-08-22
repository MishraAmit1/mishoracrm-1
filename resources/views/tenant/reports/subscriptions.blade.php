@extends('layouts.app')
@section('title', 'Subscriptions Report')

@push('styles')
<style>
.range-bar { display:flex; gap:8px; flex-wrap:wrap; margin-bottom:20px; }
.range-btn { padding:6px 14px; border-radius:20px; font-size:12.5px; font-weight:600; border:1.5px solid var(--border-default); background:none; color:var(--text-300); cursor:pointer; text-decoration:none; font-family:var(--font); transition:all .15s; }
.range-btn:hover { border-color:var(--border-strong); color:var(--text-100); }
.range-btn.active { border-color:var(--accent); background:var(--accent-dim); color:var(--accent); }

.kpi-grid { display:grid; grid-template-columns:repeat(5,1fr); gap:12px; margin-bottom:24px; }
@media(max-width:1100px) { .kpi-grid { grid-template-columns:repeat(3,1fr); } }
@media(max-width:640px)  { .kpi-grid { grid-template-columns:repeat(2,1fr); } }
.kpi-card { background:var(--bg-surface); border:1px solid var(--border-default); border-radius:var(--r-lg); padding:16px 18px; }
.kpi-label { font-size:11.5px; font-weight:600; color:var(--text-400); text-transform:uppercase; letter-spacing:.4px; margin-bottom:8px; }
.kpi-value { font-size:24px; font-weight:800; font-family:var(--mono); color:var(--text-100); letter-spacing:-.5px; }

.chart-card { background:var(--bg-surface); border:1px solid var(--border-default); border-radius:var(--r-lg); overflow:hidden; margin-bottom:16px; }
.chart-head { padding:16px 20px; border-bottom:1px solid var(--border-subtle); font-size:14px; font-weight:700; color:var(--text-100); }
.chart-body { padding:16px 20px; }
.two-col { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
@media(max-width:900px) { .two-col { grid-template-columns:1fr; } }

.row-item { display:flex; align-items:center; justify-content:space-between; padding:9px 0; border-bottom:1px solid var(--border-subtle); font-size:13px; }
.row-item:last-child { border-bottom:none; }
.row-name { color:var(--text-100); font-weight:500; }
.row-num  { font-family:var(--mono); color:var(--accent); font-weight:700; }

.mini-table { width:100%; border-collapse:collapse; }
.mini-table th { padding:8px 12px; text-align:left; font-size:11px; font-weight:700; color:var(--text-400); text-transform:uppercase; border-bottom:1px solid var(--border-subtle); }
.mini-table td { padding:9px 12px; font-size:13px; color:var(--text-100); border-bottom:1px solid var(--border-subtle); }
.mini-table tr:last-child td { border-bottom:none; }
</style>
@endpush

@section('content')

@php
    $ranges = ['today'=>'Today','this_week'=>'This Week','this_month'=>'This Month','last_month'=>'Last Month','this_quarter'=>'This Quarter','this_year'=>'This Year'];
    $curRange = $request->get('range','this_month');
@endphp

<div class="page-head">
    <div>
        <div style="font-size:13px;color:var(--text-300);margin-bottom:4px">
            <a href="{{ route('tenant.reports.overview') }}" style="color:var(--text-300);text-decoration:none">Reports</a>
            <span style="margin:0 6px">›</span> Subscriptions
        </div>
        <div class="page-title">Subscriptions Report</div>
        <div class="page-sub">{{ $from->format('d M Y') }} — {{ $to->format('d M Y') }}</div>
    </div>
</div>

<div class="range-bar">
    @foreach($ranges as $key => $label)
    <a href="{{ route('tenant.reports.subscriptions', ['range'=>$key]) }}" class="range-btn {{ $curRange===$key ? 'active':'' }}">{{ $label }}</a>
    @endforeach
</div>

<div class="kpi-grid">
    <div class="kpi-card"><div class="kpi-label">Active</div><div class="kpi-value">{{ $kpis['active'] }}</div></div>
    <div class="kpi-card"><div class="kpi-label">Expiring Soon</div><div class="kpi-value" style="color:#B36B00">{{ $kpis['expiring'] }}</div></div>
    <div class="kpi-card"><div class="kpi-label">Expired</div><div class="kpi-value" style="color:var(--red)">{{ $kpis['expired'] }}</div></div>
    <div class="kpi-card"><div class="kpi-label">Cancelled</div><div class="kpi-value">{{ $kpis['cancelled'] }}</div></div>
    <div class="kpi-card"><div class="kpi-label">Est. Monthly Recurring</div><div class="kpi-value">₹{{ number_format($mrr, 0) }}</div></div>
</div>

<div class="two-col">
    <div class="chart-card">
        <div class="chart-head">Top Services by Subscriptions</div>
        <div class="chart-body">
            @forelse($byService as $row)
            <div class="row-item"><span class="row-name">{{ $row->service?->name ?? '—' }}</span><span class="row-num">{{ $row->count }}</span></div>
            @empty
            <div style="color:var(--text-400);font-size:13px">No data yet.</div>
            @endforelse
        </div>
    </div>
    <div class="chart-card">
        <div class="chart-head">New Subscriptions — Last 12 Months</div>
        <div class="chart-body">
            @forelse($monthlyTrend as $row)
            <div class="row-item"><span class="row-name">{{ \Carbon\Carbon::parse($row->month.'-01')->format('M Y') }}</span><span class="row-num">{{ $row->count }}</span></div>
            @empty
            <div style="color:var(--text-400);font-size:13px">No data yet.</div>
            @endforelse
        </div>
    </div>
</div>

<div class="chart-card">
    <div class="chart-head">Expiring Soonest</div>
    <table class="mini-table">
        <thead><tr><th>Contact</th><th>Service</th><th>Expires</th></tr></thead>
        <tbody>
            @forelse($expiringList as $s)
            <tr>
                <td>{{ $s->contact?->name ?? '—' }}</td>
                <td>{{ $s->service?->name ?? '—' }}</td>
                <td class="mono">{{ $s->expires_at?->format('d M Y') ?? '—' }}</td>
            </tr>
            @empty
            <tr><td colspan="3" style="text-align:center;color:var(--text-400);padding:20px">Nothing expiring soon.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

@endsection
