@extends('layouts.app')
@section('title', 'Recent Conversions Report')

@push('styles')
<style>
.range-bar { display:flex; gap:8px; flex-wrap:wrap; margin-bottom:20px; align-items:center; }
.range-btn { padding:6px 14px; border-radius:20px; font-size:12.5px; font-weight:600; border:1.5px solid var(--border-default); background:none; color:var(--text-300); cursor:pointer; text-decoration:none; font-family:var(--font); transition:all .15s; }
.range-btn:hover { border-color:var(--border-strong); color:var(--text-100); }
.range-btn.active { border-color:var(--accent); background:var(--accent-dim); color:var(--accent); }
.source-select { padding:6px 12px; border-radius:20px; font-size:12.5px; font-weight:600; border:1.5px solid var(--border-default); background:var(--bg-surface); color:var(--text-200); cursor:pointer; font-family:var(--font); }
.kpi-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:12px; margin-bottom:20px; }
@media(max-width:900px) { .kpi-grid{grid-template-columns:repeat(2,1fr);} }
.kpi-card { background:var(--bg-surface); border:1px solid var(--border-default); border-radius:var(--r-lg); padding:16px 18px; border-top:3px solid transparent; }
.kpi-label { font-size:11px; font-weight:600; color:var(--text-400); text-transform:uppercase; letter-spacing:.4px; margin-bottom:8px; }
.kpi-value { font-size:22px; font-weight:800; font-family:var(--mono); letter-spacing:-.5px; }
.chart-card { background:var(--bg-surface); border:1px solid var(--border-default); border-radius:var(--r-lg); overflow:hidden; margin-bottom:16px; }
.chart-head { padding:14px 20px; border-bottom:1px solid var(--border-subtle); display:flex; align-items:center; justify-content:space-between; }
.chart-title { font-size:14px; font-weight:700; color:var(--text-100); }
.chart-body  { padding:20px; }
.stage-row  { display:flex; align-items:center; gap:12px; padding:12px 0; border-bottom:1px solid var(--border-subtle); }
.stage-row:last-child { border-bottom:none; }
.stage-dot  { width:10px; height:10px; border-radius:50%; flex-shrink:0; background:var(--accent); }
.badge { display:inline-flex; align-items:center; gap:4px; padding:3px 9px; border-radius:20px; font-size:11.5px; font-weight:600; background:var(--green-dim); color:var(--green); }
</style>
@endpush

@section('content')

@php
    $ranges   = ['today'=>'Today','this_week'=>'This Week','this_month'=>'This Month','last_month'=>'Last Month','this_quarter'=>'This Quarter','this_year'=>'This Year'];
    $curRange = $request->get('range','this_month');
    $maxSourceCount = $bySource->max('count') ?: 1;
@endphp

<div class="page-head">
    <div>
        <div style="font-size:13px;color:var(--text-300);margin-bottom:4px">
            <a href="{{ route('tenant.reports.overview') }}" style="color:var(--text-300);text-decoration:none">Reports</a>
            <span style="margin:0 6px">›</span> Recent Conversions
        </div>
        <div class="page-title">Recent Conversions</div>
        <div class="page-sub">{{ $from->format('d M Y') }} — {{ $to->format('d M Y') }} · Leads that converted, with source breakdown</div>
    </div>
    <div class="page-actions">
        <a href="{{ route('tenant.reports.deals') }}" class="btn btn-secondary">Deal Report</a>
    </div>
</div>

<div class="range-bar">
    @foreach($ranges as $key => $label)
    <a href="{{ route('tenant.reports.conversions', array_filter(['range'=>$key,'source'=>$curSource!=='all'?$curSource:null])) }}"
       class="range-btn {{ $curRange===$key ? 'active':'' }}">{{ $label }}</a>
    @endforeach

    <form method="GET" style="margin-left:auto;">
        <input type="hidden" name="range" value="{{ $curRange }}">
        <select name="source" class="source-select" onchange="this.form.submit()">
            <option value="all" {{ $curSource==='all' ? 'selected':'' }}>All Sources</option>
            @foreach($sources as $key => $label)
            <option value="{{ $key }}" {{ $curSource===$key ? 'selected':'' }}>{{ $label }}</option>
            @endforeach
        </select>
    </form>
</div>

{{-- KPIs --}}
<div class="kpi-grid">
    @php $kpiItems = [
        ['label'=>'Total Converted',    'value'=>$totalConverted,                              'color'=>'var(--green)'],
        ['label'=>'Total Deal Value',   'value'=>'₹'.number_format($totalDealValue ?? 0),       'color'=>'var(--accent)'],
        ['label'=>'Avg Days to Convert','value'=>$avgDaysToConvert ? round($avgDaysToConvert,1) : '—', 'color'=>'var(--purple)'],
        ['label'=>'Sources Active',     'value'=>$bySource->count(),                            'color'=>'var(--amber)'],
    ]; @endphp
    @foreach($kpiItems as $k)
    <div class="kpi-card" style="border-top-color:{{ $k['color'] }}">
        <div class="kpi-label">{{ $k['label'] }}</div>
        <div class="kpi-value" style="color:{{ $k['color'] }}">{{ $k['value'] }}</div>
    </div>
    @endforeach
</div>

{{-- Source breakdown --}}
<div class="chart-card">
    <div class="chart-head"><div class="chart-title">Conversions by Source</div></div>
    <div class="chart-body">
        @forelse($bySource as $row)
        <div class="stage-row">
            <div class="stage-dot"></div>
            <div style="flex:1">
                <div style="font-size:13.5px;font-weight:600;color:var(--text-100)">{{ $sources[$row->source] ?? ucfirst($row->source ?? 'Unknown') }}</div>
                <div style="height:4px;background:var(--border-subtle);border-radius:2px;margin-top:5px;overflow:hidden">
                    <div style="height:100%;background:var(--accent);width:{{ min(round(($row->count/$maxSourceCount)*100),100) }}%;border-radius:2px"></div>
                </div>
            </div>
            <div style="text-align:right">
                <div style="font-size:13px;font-weight:700;font-family:var(--mono);color:var(--accent)">{{ $row->count }}</div>
            </div>
        </div>
        @empty
        <div style="text-align:center;padding:30px;color:var(--text-400)">No conversions in this range</div>
        @endforelse
    </div>
</div>

{{-- Converted leads table --}}
<div class="chart-card">
    <div class="chart-head">
        <div class="chart-title">Converted Leads</div>
        <a href="{{ route('tenant.leads.index', ['status'=>'converted']) }}" class="btn btn-secondary btn-sm">View All Leads →</a>
    </div>
    <div style="overflow-x:auto">
        <table class="data-table" style="width:100%;border-collapse:collapse">
            <thead>
                <tr style="background:var(--bg-elevated)">
                    <th style="padding:10px 16px;text-align:left;font-size:11px;font-weight:600;color:var(--text-400);text-transform:uppercase;letter-spacing:.5px;border-bottom:1px solid var(--border-subtle)">Lead</th>
                    <th style="padding:10px 16px;text-align:left;font-size:11px;font-weight:600;color:var(--text-400);text-transform:uppercase;letter-spacing:.5px;border-bottom:1px solid var(--border-subtle)">Source</th>
                    <th style="padding:10px 16px;text-align:left;font-size:11px;font-weight:600;color:var(--text-400);text-transform:uppercase;letter-spacing:.5px;border-bottom:1px solid var(--border-subtle)">Assigned To</th>
                    <th style="padding:10px 16px;text-align:left;font-size:11px;font-weight:600;color:var(--text-400);text-transform:uppercase;letter-spacing:.5px;border-bottom:1px solid var(--border-subtle)">Converted On</th>
                    <th style="padding:10px 16px;text-align:right;font-size:11px;font-weight:600;color:var(--text-400);text-transform:uppercase;letter-spacing:.5px;border-bottom:1px solid var(--border-subtle)">Deal Value</th>
                    <th style="padding:10px 16px;text-align:right;font-size:11px;font-weight:600;color:var(--text-400);text-transform:uppercase;letter-spacing:.5px;border-bottom:1px solid var(--border-subtle)">Days to Convert</th>
                </tr>
            </thead>
            <tbody>
                @forelse($leads as $lead)
                <tr>
                    <td style="padding:12px 16px;border-bottom:1px solid var(--border-subtle)" data-label="Lead">
                        <a href="{{ route('tenant.leads.show', $lead->id) }}"
                           style="font-weight:600;color:var(--text-100);text-decoration:none;font-size:13.5px">
                            {{ $lead->name }}
                        </a>
                    </td>
                    <td style="padding:12px 16px;border-bottom:1px solid var(--border-subtle)" data-label="Source">
                        <span class="badge">{{ $sources[$lead->source] ?? ucfirst($lead->source ?? '—') }}</span>
                    </td>
                    <td style="padding:12px 16px;border-bottom:1px solid var(--border-subtle);font-size:13px;color:var(--text-200)" data-label="Assigned To">
                        {{ $lead->assignedTo?->name ?? '—' }}
                    </td>
                    <td style="padding:12px 16px;border-bottom:1px solid var(--border-subtle);font-size:13px;color:var(--text-200)" data-label="Converted On">
                        {{ $lead->converted_at?->format('d M Y') ?? '—' }}
                    </td>
                    <td style="padding:12px 16px;border-bottom:1px solid var(--border-subtle);text-align:right;font-family:var(--mono);font-size:13px;color:var(--text-200)" data-label="Deal Value">
                        {{ $lead->deal ? '₹'.number_format($lead->deal->value) : '—' }}
                    </td>
                    <td style="padding:12px 16px;border-bottom:1px solid var(--border-subtle);text-align:right;font-family:var(--mono);font-size:13px;color:var(--text-200)" data-label="Days to Convert">
                        {{ $lead->converted_at ? $lead->created_at->diffInDays($lead->converted_at) : '—' }}
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" style="padding:30px;text-align:center;color:var(--text-400)">No converted leads in this range</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($leads->hasPages())
    <div style="padding:14px 20px;border-top:1px solid var(--border-subtle)">
        {{ $leads->links() }}
    </div>
    @endif
</div>

@endsection
