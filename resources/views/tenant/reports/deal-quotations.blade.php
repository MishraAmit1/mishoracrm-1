@extends('layouts.app')
@section('title', 'Deal Quotations Report')

@push('styles')
<style>
.range-bar { display:flex; gap:8px; flex-wrap:wrap; margin-bottom:20px; }
.range-btn { padding:6px 14px; border-radius:20px; font-size:12.5px; font-weight:600; border:1.5px solid var(--border-default); background:none; color:var(--text-300); cursor:pointer; text-decoration:none; font-family:var(--font); transition:all .15s; }
.range-btn:hover { border-color:var(--border-strong); color:var(--text-100); }
.range-btn.active { border-color:var(--accent); background:var(--accent-dim); color:var(--accent); }
.kpi-grid { display:grid; grid-template-columns:repeat(5,1fr); gap:12px; margin-bottom:20px; }
@media(max-width:1100px) { .kpi-grid{grid-template-columns:repeat(3,1fr);} }
@media(max-width:600px)  { .kpi-grid{grid-template-columns:repeat(2,1fr);} }
.kpi-card { background:var(--bg-surface); border:1px solid var(--border-default); border-radius:var(--r-lg); padding:16px 18px; border-top:3px solid transparent; }
.kpi-label { font-size:11px; font-weight:600; color:var(--text-400); text-transform:uppercase; letter-spacing:.4px; margin-bottom:8px; }
.kpi-value { font-size:22px; font-weight:800; font-family:var(--mono); letter-spacing:-.5px; }
.chart-card { background:var(--bg-surface); border:1px solid var(--border-default); border-radius:var(--r-lg); overflow:hidden; margin-bottom:16px; }
.chart-head { padding:14px 20px; border-bottom:1px solid var(--border-subtle); display:flex; align-items:center; justify-content:space-between; }
.chart-title { font-size:14px; font-weight:700; color:var(--text-100); }
.chart-body  { padding:20px; }
.stage-row  { display:flex; align-items:center; gap:12px; padding:12px 0; border-bottom:1px solid var(--border-subtle); }
.stage-row:last-child { border-bottom:none; }
.stage-dot  { width:10px; height:10px; border-radius:50%; flex-shrink:0; }
.badge { display:inline-flex; align-items:center; gap:4px; padding:3px 9px; border-radius:20px; font-size:11.5px; font-weight:600; }
</style>
@endpush

@section('content')

@php
    $ranges   = ['today'=>'Today','this_week'=>'This Week','this_month'=>'This Month','last_month'=>'Last Month','this_quarter'=>'This Quarter','this_year'=>'This Year'];
    $curRange = $request->get('range','this_month');
    $stagesCfg = config('crm.deal.stages');
    $qStatusColors = [
        'draft'    => ['label'=>'accent',  'bg'=>'accent-dim'],
        'sent'     => ['label'=>'accent',  'bg'=>'accent-dim'],
        'accepted' => ['label'=>'green',   'bg'=>'green-dim'],
        'rejected' => ['label'=>'red',     'bg'=>'red-dim'],
    ];
    $maxStatusCount = $statusBreakdown->max('count') ?: 1;
@endphp

<div class="page-head">
    <div>
        <div style="font-size:13px;color:var(--text-300);margin-bottom:4px">
            <a href="{{ route('tenant.reports.overview') }}" style="color:var(--text-300);text-decoration:none">Reports</a>
            <span style="margin:0 6px">›</span> Deal Quotations
        </div>
        <div class="page-title">Deal ↔ Quotation Report</div>
        <div class="page-sub">{{ $from->format('d M Y') }} — {{ $to->format('d M Y') }}</div>
    </div>
    <div class="page-actions">
        <a href="{{ route('tenant.reports.deals') }}"   class="btn btn-secondary">Deal Report</a>
        <a href="{{ route('tenant.reports.revenue') }}" class="btn btn-secondary">Revenue</a>
    </div>
</div>

<div class="range-bar">
    @foreach($ranges as $key => $label)
    <a href="{{ route('tenant.reports.deal_quotations', ['range'=>$key]) }}"
       class="range-btn {{ $curRange===$key ? 'active':'' }}">{{ $label }}</a>
    @endforeach
</div>

{{-- KPIs --}}
<div class="kpi-grid">
    @php $kpiItems = [
        ['label'=>'Total Deals',           'value'=>$totalDeals,             'color'=>'var(--accent)'],
        ['label'=>'Deals With Quotations', 'value'=>$dealsWithQuotations,    'color'=>'var(--green)'],
        ['label'=>'Deals Without',         'value'=>$dealsWithoutQuotations, 'color'=>'var(--amber)'],
        ['label'=>'Total Quotations',      'value'=>$totalQuotations,        'color'=>'var(--purple)'],
        ['label'=>'Avg / Deal',            'value'=>$avgPerDeal,             'color'=>'var(--accent)'],
    ]; @endphp
    @foreach($kpiItems as $k)
    <div class="kpi-card" style="border-top-color:{{ $k['color'] }}">
        <div class="kpi-label">{{ $k['label'] }}</div>
        <div class="kpi-value" style="color:{{ $k['color'] }}">{{ $k['value'] }}</div>
    </div>
    @endforeach
</div>

{{-- Quotation status breakdown --}}
<div class="chart-card">
    <div class="chart-head"><div class="chart-title">Quotation Status Breakdown</div></div>
    <div class="chart-body">
        @forelse($statuses as $key => $label)
        @php $sd = $statusBreakdown[$key] ?? null; $c = $qStatusColors[$key] ?? ['label'=>'accent','bg'=>'accent-dim']; @endphp
        <div class="stage-row">
            <div class="stage-dot" style="background:var(--{{ $c['label'] }})"></div>
            <div style="flex:1">
                <div style="font-size:13.5px;font-weight:600;color:var(--text-100)">{{ $label }}</div>
                <div style="height:4px;background:var(--border-subtle);border-radius:2px;margin-top:5px;overflow:hidden">
                    <div style="height:100%;background:var(--{{ $c['label'] }});width:{{ $sd ? min(round(($sd->count/$maxStatusCount)*100),100) : 0 }}%;border-radius:2px"></div>
                </div>
            </div>
            <div style="text-align:right">
                <div style="font-size:13px;font-weight:700;font-family:var(--mono);color:var(--{{ $c['label'] }})">{{ $sd->count ?? 0 }}</div>
            </div>
        </div>
        @empty
        <div style="text-align:center;padding:30px;color:var(--text-400)">No quotations in this range</div>
        @endforelse
    </div>
</div>

{{-- Deals table --}}
<div class="chart-card">
    <div class="chart-head">
        <div class="chart-title">Deals & Their Quotations</div>
        <a href="{{ route('tenant.deals.index') }}" class="btn btn-secondary btn-sm">View All Deals →</a>
    </div>
    <div style="overflow-x:auto">
        <table class="data-table" style="width:100%;border-collapse:collapse">
            <thead>
                <tr style="background:var(--bg-elevated)">
                    <th style="padding:10px 16px;text-align:left;font-size:11px;font-weight:600;color:var(--text-400);text-transform:uppercase;letter-spacing:.5px;border-bottom:1px solid var(--border-subtle)">Deal</th>
                    <th style="padding:10px 16px;text-align:left;font-size:11px;font-weight:600;color:var(--text-400);text-transform:uppercase;letter-spacing:.5px;border-bottom:1px solid var(--border-subtle)">Contact</th>
                    <th style="padding:10px 16px;text-align:left;font-size:11px;font-weight:600;color:var(--text-400);text-transform:uppercase;letter-spacing:.5px;border-bottom:1px solid var(--border-subtle)">Stage</th>
                    <th style="padding:10px 16px;text-align:center;font-size:11px;font-weight:600;color:var(--text-400);text-transform:uppercase;letter-spacing:.5px;border-bottom:1px solid var(--border-subtle)">Quotations</th>
                    <th style="padding:10px 16px;text-align:left;font-size:11px;font-weight:600;color:var(--text-400);text-transform:uppercase;letter-spacing:.5px;border-bottom:1px solid var(--border-subtle)">Latest Quotation</th>
                </tr>
            </thead>
            <tbody>
                @forelse($deals as $deal)
                @php
                    $sc = $stagesCfg[$deal->stage] ?? [];
                    $latest = $deal->quotations->first();
                    $lc = $latest ? ($qStatusColors[$latest->status] ?? ['label'=>'accent','bg'=>'accent-dim']) : null;
                @endphp
                <tr>
                    <td style="padding:12px 16px;border-bottom:1px solid var(--border-subtle)" data-label="Deal">
                        <a href="{{ route('tenant.deals.show', $deal->id) }}"
                           style="font-weight:600;color:var(--text-100);text-decoration:none;font-size:13.5px">
                            {{ $deal->title }}
                        </a>
                    </td>
                    <td style="padding:12px 16px;border-bottom:1px solid var(--border-subtle);font-size:13px;color:var(--text-200)" data-label="Contact">
                        {{ $deal->contact?->name ?? '—' }}
                    </td>
                    <td style="padding:12px 16px;border-bottom:1px solid var(--border-subtle)" data-label="Stage">
                        <span class="badge" style="background:var(--{{ $sc['bg'] ?? 'accent-dim' }});color:var(--{{ $sc['color'] ?? 'accent' }})">
                            {{ $sc['label'] ?? ucfirst($deal->stage) }}
                        </span>
                    </td>
                    <td style="padding:12px 16px;border-bottom:1px solid var(--border-subtle);text-align:center;font-family:var(--mono);font-size:14px;font-weight:700;color:var(--accent)" data-label="Quotations">
                        {{ $deal->quotations_count }}
                    </td>
                    <td style="padding:12px 16px;border-bottom:1px solid var(--border-subtle);font-size:13px" data-label="Latest Quotation">
                        @if($latest)
                        <a href="{{ route('tenant.quotations.show', $latest->id) }}" style="color:var(--text-200);text-decoration:none;margin-right:8px">{{ $latest->number }}</a>
                        <span class="badge" style="background:var(--{{ $lc['bg'] }});color:var(--{{ $lc['label'] }})">
                            {{ $statuses[$latest->status] ?? ucfirst($latest->status) }}
                        </span>
                        @else
                        <span style="color:var(--text-400)">No quotation yet</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" style="padding:30px;text-align:center;color:var(--text-400)">No deals in this range</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($deals->hasPages())
    <div style="padding:14px 20px;border-top:1px solid var(--border-subtle)">
        {{ $deals->links() }}
    </div>
    @endif
</div>

@endsection
