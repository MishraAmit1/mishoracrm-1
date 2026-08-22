@extends('layouts.app')
@section('title', 'Time Tracking Report')

@push('styles')
<style>
.range-bar { display:flex; gap:8px; flex-wrap:wrap; margin-bottom:20px; }
.range-btn { padding:6px 14px; border-radius:20px; font-size:12.5px; font-weight:600; border:1.5px solid var(--border-default); background:none; color:var(--text-300); cursor:pointer; text-decoration:none; font-family:var(--font); transition:all .15s; }
.range-btn:hover { border-color:var(--border-strong); color:var(--text-100); }
.range-btn.active { border-color:var(--accent); background:var(--accent-dim); color:var(--accent); }

.kpi-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:12px; margin-bottom:24px; }
@media(max-width:900px) { .kpi-grid { grid-template-columns:repeat(2,1fr); } }
.kpi-card { background:var(--bg-surface); border:1px solid var(--border-default); border-radius:var(--r-lg); padding:16px 18px; }
.kpi-label { font-size:11.5px; font-weight:600; color:var(--text-400); text-transform:uppercase; letter-spacing:.4px; margin-bottom:8px; }
.kpi-value { font-size:24px; font-weight:800; font-family:var(--mono); color:var(--text-100); letter-spacing:-.5px; }

.chart-card { background:var(--bg-surface); border:1px solid var(--border-default); border-radius:var(--r-lg); overflow:hidden; margin-bottom:16px; }
.chart-head { padding:16px 20px; border-bottom:1px solid var(--border-subtle); font-size:14px; font-weight:700; color:var(--text-100); }

.mini-table { width:100%; border-collapse:collapse; }
.mini-table th { padding:10px 20px; text-align:left; font-size:11px; font-weight:700; color:var(--text-400); text-transform:uppercase; border-bottom:1px solid var(--border-subtle); }
.mini-table th.right, .mini-table td.right { text-align:right; }
.mini-table td { padding:10px 20px; font-size:13px; color:var(--text-100); border-bottom:1px solid var(--border-subtle); }
.mini-table tr:last-child td { border-bottom:none; }
.mono { font-family:var(--mono); }
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
            <span style="margin:0 6px">›</span> Time Tracking
        </div>
        <div class="page-title">Time Tracking Report</div>
        <div class="page-sub">{{ $from->format('d M Y') }} — {{ $to->format('d M Y') }}</div>
    </div>
</div>

<div class="range-bar">
    @foreach($ranges as $key => $label)
    <a href="{{ route('tenant.reports.time-tracking', ['range'=>$key]) }}" class="range-btn {{ $curRange===$key ? 'active':'' }}">{{ $label }}</a>
    @endforeach
</div>

<div class="kpi-grid">
    <div class="kpi-card"><div class="kpi-label">Total Hours Logged</div><div class="kpi-value">{{ $kpis['total_hours'] }}</div></div>
    <div class="kpi-card"><div class="kpi-label">Billable Hours</div><div class="kpi-value" style="color:var(--green)">{{ $kpis['billable_hours'] }}</div></div>
    <div class="kpi-card"><div class="kpi-label">Invoiced Hours</div><div class="kpi-value">{{ $kpis['invoiced_hours'] }}</div></div>
    <div class="kpi-card"><div class="kpi-label">Billable, Not Yet Invoiced</div><div class="kpi-value" style="color:#B36B00">{{ $kpis['uninvoiced_hours'] }}</div></div>
</div>

<div class="chart-card">
    <div class="chart-head">Hours by Staff</div>
    <table class="mini-table">
        <thead><tr><th>Staff</th><th class="right">Total Hours</th><th class="right">Billable Hours</th></tr></thead>
        <tbody>
            @forelse($byStaff as $row)
            <tr>
                <td>{{ $row['user']->name }}</td>
                <td class="right mono">{{ $row['total_hours'] }}</td>
                <td class="right mono">{{ $row['billable_hours'] }}</td>
            </tr>
            @empty
            <tr><td colspan="3" style="text-align:center;color:var(--text-400);padding:20px">No time logged in this range.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="chart-card">
    <div class="chart-head">Hours by Service</div>
    <table class="mini-table">
        <thead><tr><th>Service</th><th class="right">Hours</th></tr></thead>
        <tbody>
            @forelse($byService as $row)
            <tr>
                <td>{{ $row->service?->name ?? '—' }}</td>
                <td class="right mono">{{ round($row->minutes / 60, 1) }}</td>
            </tr>
            @empty
            <tr><td colspan="2" style="text-align:center;color:var(--text-400);padding:20px">No data yet.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>

@endsection
