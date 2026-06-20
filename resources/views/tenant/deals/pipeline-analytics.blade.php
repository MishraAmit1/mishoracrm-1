@extends('layouts.app')
@section('title', 'Pipeline Analytics')

@push('styles')
<style>
@import url('https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=DM+Mono:wght@400;500&display=swap');
@import url('https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.19.0/dist/tabler-icons.min.css');

.pa { font-family: 'DM Sans', var(--font), sans-serif; }

/* ── KPI Grid ── */
.pa-kpis {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 14px;
    margin-bottom: 22px;
}
@media(max-width:900px){ .pa-kpis { grid-template-columns: repeat(2,1fr); } }
@media(max-width:500px){ .pa-kpis { grid-template-columns: 1fr; } }

.pa-kpi {
    background: var(--bg-surface);
    border: 1px solid var(--border-default);
    border-radius: 16px;
    padding: 20px;
    position: relative;
    overflow: hidden;
}
.pa-kpi-icon {
    width: 38px; height: 38px; border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 18px; margin-bottom: 12px;
}
.pa-kpi-val {
    font-size: 26px; font-weight: 700; font-family: 'DM Mono', monospace;
    letter-spacing: -1px; color: var(--text-100); line-height: 1;
    margin-bottom: 4px;
}
.pa-kpi-lbl {
    font-size: 12px; color: var(--text-300); font-weight: 500;
    text-transform: uppercase; letter-spacing: .05em;
}
.pa-kpi-sub {
    font-size: 11.5px; color: var(--text-400); margin-top: 6px;
}
.pa-kpi-bar {
    position: absolute; bottom: 0; left: 0; height: 3px; border-radius: 0 0 16px 16px;
}

/* ── Layout ── */
.pa-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
    margin-bottom: 16px;
}
.pa-grid-3 {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 16px;
    margin-bottom: 16px;
}
@media(max-width:900px){ .pa-grid, .pa-grid-3 { grid-template-columns: 1fr; } }

/* ── Card ── */
.pa-card {
    background: var(--bg-surface);
    border: 1px solid var(--border-default);
    border-radius: 14px;
    overflow: hidden;
}
.pa-card-hd {
    padding: 14px 18px;
    border-bottom: 1px solid var(--border-subtle);
    background: var(--bg-elevated);
    display: flex; align-items: center; justify-content: space-between;
}
.pa-card-title {
    font-size: 12px; font-weight: 600; color: var(--text-200);
    text-transform: uppercase; letter-spacing: .06em;
    display: flex; align-items: center; gap: 7px;
}
.pa-card-body { padding: 18px; }

/* ── Funnel ── */
.funnel-wrap {
    display: flex;
    flex-direction: column;
    gap: 10px;
    padding: 18px;
}
.funnel-row { position: relative; }
.funnel-bar-wrap {
    display: flex; align-items: center; gap: 12px;
    margin-bottom: 4px;
}
.funnel-label {
    font-size: 12px; font-weight: 600; color: var(--text-200);
    min-width: 90px; text-align: right;
}
.funnel-bar-outer {
    flex: 1; height: 36px; background: var(--bg-elevated);
    border-radius: 6px; overflow: hidden; position: relative;
}
.funnel-bar-inner {
    height: 100%; border-radius: 6px;
    display: flex; align-items: center; padding-left: 10px;
    transition: width .6s cubic-bezier(.34,1.56,.64,1);
    min-width: 40px;
}
.funnel-bar-text {
    font-size: 12px; font-weight: 600; color: #fff;
    white-space: nowrap;
}
.funnel-meta {
    font-size: 11px; color: var(--text-400);
    margin-left: 102px; font-family: 'DM Mono', monospace;
}
.funnel-conversion {
    display: flex; align-items: center; gap: 4px;
    font-size: 11px; color: var(--text-400);
    margin: 2px 0 2px 102px;
}
.funnel-arrow { font-size: 10px; color: var(--text-400); }

/* ── Chart ── */
.chart-wrap { padding: 18px 18px 10px; }
.bar-chart {
    display: flex; align-items: flex-end; gap: 8px;
    height: 160px; padding-bottom: 24px; position: relative;
    border-bottom: 1px solid var(--border-subtle);
}
.bar-chart-col {
    flex: 1; display: flex; flex-direction: column;
    align-items: center; justify-content: flex-end; gap: 3px;
    position: relative;
}
.bar-col-bar {
    width: 100%; border-radius: 5px 5px 0 0;
    min-height: 4px; transition: height .5s;
    position: relative; cursor: default;
}
.bar-col-bar:hover::after {
    content: attr(data-tip);
    position: absolute; bottom: calc(100% + 6px); left: 50%;
    transform: translateX(-50%);
    background: #1a1a2e; color: #fff;
    padding: 4px 8px; border-radius: 6px; font-size: 11px;
    white-space: nowrap; z-index: 10; pointer-events: none;
}
.bar-col-lbl {
    position: absolute; bottom: -20px; left: 50%;
    transform: translateX(-50%);
    font-size: 10px; color: var(--text-400); white-space: nowrap;
}
.bar-col-val {
    font-size: 10px; font-family: 'DM Mono', monospace;
    color: var(--text-300); white-space: nowrap;
}

/* Win/Loss chart */
.wl-chart {
    display: flex; align-items: flex-end; gap: 10px;
    height: 140px; padding-bottom: 24px;
    border-bottom: 1px solid var(--border-subtle);
}
.wl-col { flex: 1; display: flex; flex-direction: column; align-items: center; gap: 2px; justify-content: flex-end; position: relative; }
.wl-bars { display: flex; gap: 3px; align-items: flex-end; width: 100%; }
.wl-bar  { flex: 1; border-radius: 4px 4px 0 0; min-height: 3px; transition: height .5s; cursor: default; }
.wl-bar:hover::after {
    content: attr(data-tip);
    position: absolute; bottom: calc(100% + 6px); left: 50%;
    transform: translateX(-50%);
    background: #1a1a2e; color: #fff;
    padding: 3px 7px; border-radius: 5px; font-size: 11px;
    white-space: nowrap; z-index: 10; pointer-events: none;
}
.wl-col-lbl {
    position: absolute; bottom: -20px; left: 50%;
    transform: translateX(-50%);
    font-size: 10px; color: var(--text-400); white-space: nowrap;
}

/* ── Closing This Month ── */
.closing-list { display: flex; flex-direction: column; gap: 0; }
.closing-row {
    display: flex; align-items: center; gap: 12px;
    padding: 11px 18px; border-bottom: 1px solid var(--border-subtle);
    text-decoration: none; transition: background .12s;
}
.closing-row:last-child { border-bottom: none; }
.closing-row:hover { background: var(--bg-elevated); }
.closing-av {
    width: 32px; height: 32px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 11px; font-weight: 700; flex-shrink: 0;
}
.closing-info { flex: 1; min-width: 0; }
.closing-title { font-size: 13px; font-weight: 600; color: var(--text-100); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.closing-sub   { font-size: 11px; color: var(--text-400); margin-top: 1px; }
.closing-right { display: flex; flex-direction: column; align-items: flex-end; gap: 2px; }
.closing-val   { font-size: 13px; font-weight: 600; font-family: 'DM Mono', monospace; color: var(--text-100); }
.closing-date  { font-size: 11px; font-family: 'DM Mono', monospace; }

/* ── Top Deals ── */
.top-deal-row {
    display: flex; align-items: center; gap: 12px;
    padding: 11px 18px; border-bottom: 1px solid var(--border-subtle);
    text-decoration: none; transition: background .12s;
}
.top-deal-row:last-child { border-bottom: none; }
.top-deal-row:hover { background: var(--bg-elevated); }
.top-rank {
    width: 24px; height: 24px; border-radius: 6px;
    background: var(--bg-elevated); border: 1px solid var(--border-default);
    display: flex; align-items: center; justify-content: center;
    font-size: 11px; font-weight: 700; color: var(--text-300); flex-shrink: 0;
}
.top-deal-info { flex: 1; min-width: 0; }
.top-deal-title { font-size: 13px; font-weight: 600; color: var(--text-100); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.top-deal-sub   { font-size: 11px; color: var(--text-400); margin-top: 1px; }
.top-deal-val   { font-size: 14px; font-weight: 700; font-family: 'DM Mono', monospace; color: var(--text-100); }

/* ── Legend ── */
.chart-legend { display: flex; gap: 14px; margin-top: 10px; }
.legend-item  { display: flex; align-items: center; gap: 5px; font-size: 11.5px; color: var(--text-300); }
.legend-dot   { width: 10px; height: 10px; border-radius: 3px; flex-shrink: 0; }

/* Stage badge */
.s-badge {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 2px 8px; border-radius: 20px;
    font-size: 11px; font-weight: 600;
}
.s-badge::before { content:''; width:5px; height:5px; border-radius:50%; background:currentColor; display:inline-block; }

/* Empty state */
.pa-empty {
    text-align: center; padding: 32px 20px;
    font-size: 13px; color: var(--text-400);
}
</style>
@endpush

@section('content')
@php
use Illuminate\Support\Str;
$cfgStages   = config('deal_fields.stages');
$avColors    = [
    ['#E6F1FB','#185FA5'],['#E1F5EE','#0F6E56'],
    ['#FAEEDA','#854F0B'],['#EEEDFE','#3C3489'],
    ['#FCEBEB','#A32D2D'],
];
$initials = fn(string $n): string =>
    substr(collect(explode(' ',$n))->map(fn($p)=>strtoupper($p[0]??''))->join(''),0,2);

// Funnel max for width scaling
$funnelMax = $funnelData->max('count') ?: 1;

// Revenue chart max
$revenueMax = $monthlyRevenue->max('value') ?: 1;

// Win/loss chart max
$wlMax = $winLossData->max(fn($r) => max($r['won'], $r['lost'])) ?: 1;
@endphp

<div class="pa">

{{-- Page Head --}}
<div class="page-head">
    <div>
        <div class="page-title">Pipeline Analytics</div>
        <div style="font-size:12px;color:var(--text-300);margin-top:2px">
            Sales performance aur forecast overview
        </div>
    </div>
    <div style="display:flex;gap:8px;align-items:center">
        <a href="{{ route('tenant.deals.index') }}" class="btn btn-secondary">
            <i class="ti ti-layout-columns" style="font-size:13px"></i> Kanban
        </a>
        <a href="{{ route('tenant.deals.create') }}" class="btn btn-primary">
            <i class="ti ti-plus" style="font-size:13px"></i> Add Deal
        </a>
    </div>
</div>

{{-- KPI Cards --}}
<div class="pa-kpis">

    {{-- Total Pipeline --}}
    <div class="pa-kpi">
        <div class="pa-kpi-icon" style="background:#E6F1FB;color:#185FA5">
            <i class="ti ti-chart-bar"></i>
        </div>
        <div class="pa-kpi-val">₹{{ number_format($totalPipeline/100000,1) }}L</div>
        <div class="pa-kpi-lbl">Total Pipeline</div>
        <div class="pa-kpi-sub">{{ $funnelData->sum('count') }} open deals</div>
        <div class="pa-kpi-bar" style="width:100%;background:#378ADD"></div>
    </div>

    {{-- Weighted Forecast --}}
    <div class="pa-kpi">
        <div class="pa-kpi-icon" style="background:#E1F5EE;color:#0F6E56">
            <i class="ti ti-target"></i>
        </div>
        <div class="pa-kpi-val">₹{{ number_format($weightedForecast/100000,1) }}L</div>
        <div class="pa-kpi-lbl">Weighted Forecast</div>
        <div class="pa-kpi-sub">Probability se weighted</div>
        <div class="pa-kpi-bar" style="width:100%;background:#1D9E75"></div>
    </div>

    {{-- Win Rate --}}
    <div class="pa-kpi">
        <div class="pa-kpi-icon" style="background:#EEEDFE;color:#3C3489">
            <i class="ti ti-trophy"></i>
        </div>
        <div class="pa-kpi-val">{{ $winRate }}%</div>
        <div class="pa-kpi-lbl">Win Rate</div>
        <div class="pa-kpi-sub">{{ $wonCount }} won · {{ $lostCount }} lost (this year)</div>
        <div class="pa-kpi-bar" style="width:{{ $winRate }}%;background:#534AB7"></div>
    </div>

    {{-- Avg Deal Size --}}
    <div class="pa-kpi">
        <div class="pa-kpi-icon" style="background:#FAEEDA;color:#854F0B">
            <i class="ti ti-currency-rupee"></i>
        </div>
        <div class="pa-kpi-val">₹{{ number_format($avgDealSize/1000,0) }}K</div>
        <div class="pa-kpi-lbl">Avg Deal Size</div>
        <div class="pa-kpi-sub">
            @if($avgDaysToClose > 0)
            Avg close: {{ round($avgDaysToClose) }} days
            @else No closed deals yet @endif
        </div>
        <div class="pa-kpi-bar" style="width:100%;background:#EF9F27"></div>
    </div>

</div>

{{-- Row 1: Funnel + Monthly Revenue --}}
<div class="pa-grid">

    {{-- Pipeline Funnel --}}
    <div class="pa-card">
        <div class="pa-card-hd">
            <div class="pa-card-title">
                <i class="ti ti-filter" style="font-size:14px;color:#378ADD"></i>
                Pipeline Funnel
            </div>
            <span style="font-size:11.5px;color:var(--text-400)">Open stages</span>
        </div>
        <div class="funnel-wrap">
            @forelse($funnelData as $i => $row)
            @php
                $stage  = $cfgStages[$row['stage']] ?? [];
                $color  = $stage['color'] ?? '#378ADD';
                $label  = $stage['label'] ?? $row['stage'];
                $pct    = $funnelMax > 0 ? round(($row['count'] / $funnelMax) * 100) : 0;
                $nextRow = $funnelData[$i+1] ?? null;
                $conv   = ($nextRow && $row['count'] > 0)
                    ? round(($nextRow['count'] / $row['count']) * 100) . '%'
                    : null;
            @endphp
            <div class="funnel-row">
                <div class="funnel-bar-wrap">
                    <span class="funnel-label">{{ $label }}</span>
                    <div class="funnel-bar-outer">
                        <div class="funnel-bar-inner"
                             style="width:{{ max($pct,5) }}%;background:{{ $color }}">
                            <span class="funnel-bar-text">
                                {{ $row['count'] }} deal{{ $row['count'] != 1 ? 's' : '' }}
                            </span>
                        </div>
                    </div>
                </div>
                <div class="funnel-meta">₹{{ number_format($row['total']/100000,1) }}L pipeline value</div>
                @if($conv)
                <div class="funnel-conversion">
                    <i class="ti ti-arrow-down funnel-arrow"></i>
                    {{ $conv }} conversion to next stage
                </div>
                @endif
            </div>
            @empty
            <div class="pa-empty">
                <i class="ti ti-inbox" style="font-size:24px;display:block;margin-bottom:8px"></i>
                Koi open deals nahi hain
            </div>
            @endforelse

            {{-- Won + Lost summary --}}
            <div style="margin-top:8px;padding-top:12px;border-top:1px solid var(--border-subtle);display:flex;gap:16px">
                @php $wonRow=$cfgStages['won']??[]; $lostRow=$cfgStages['lost']??[]; @endphp
                <div style="display:flex;align-items:center;gap:7px">
                    <div style="width:10px;height:10px;border-radius:3px;background:{{ $wonRow['color']??'#1D9E75' }}"></div>
                    <span style="font-size:12px;color:var(--text-300)">
                        Won: <strong style="color:{{ $wonRow['text_color']??'#0F6E56' }}">{{ $stageData->get('won')?->count ?? 0 }}</strong>
                        · ₹{{ number_format(($stageData->get('won')?->total ?? 0)/100000,1) }}L
                    </span>
                </div>
                <div style="display:flex;align-items:center;gap:7px">
                    <div style="width:10px;height:10px;border-radius:3px;background:{{ $lostRow['color']??'#E24B4A' }}"></div>
                    <span style="font-size:12px;color:var(--text-300)">
                        Lost: <strong style="color:{{ $lostRow['text_color']??'#A32D2D' }}">{{ $stageData->get('lost')?->count ?? 0 }}</strong>
                    </span>
                </div>
            </div>
        </div>
    </div>

    {{-- Monthly Revenue Chart --}}
    <div class="pa-card">
        <div class="pa-card-hd">
            <div class="pa-card-title">
                <i class="ti ti-chart-area" style="font-size:14px;color:#1D9E75"></i>
                Monthly Closed Revenue
            </div>
            <span style="font-size:11.5px;color:var(--text-400)">Last 6 months</span>
        </div>
        <div class="chart-wrap">
            <div class="bar-chart">
                @foreach($monthlyRevenue as $m)
                @php
                    $h = $revenueMax > 0 ? round(($m['value'] / $revenueMax) * 130) : 4;
                    $h = max($h, 4);
                    $tip = '₹' . number_format($m['value']/1000, 0) . 'K — ' . $m['month'];
                @endphp
                <div class="bar-chart-col">
                    <div class="bar-col-val">
                        @if($m['value'] > 0)₹{{ number_format($m['value']/1000,0) }}K@else —@endif
                    </div>
                    <div class="bar-col-bar"
                         style="height:{{ $h }}px;background:{{ $m['value']>0?'#1D9E75':'var(--border-subtle)' }}"
                         data-tip="{{ $tip }}">
                    </div>
                    <span class="bar-col-lbl">{{ $m['short'] }}</span>
                </div>
                @endforeach
            </div>
            <div style="margin-top:14px;font-size:12px;color:var(--text-400)">
                Total closed:
                <strong style="color:var(--text-100);font-family:'DM Mono',monospace">
                    ₹{{ number_format($monthlyRevenue->sum('value')/100000,1) }}L
                </strong>
                over last 6 months
            </div>
        </div>
    </div>

</div>

{{-- Row 2: Closing This Month + Top Deals --}}
<div class="pa-grid-3">

    {{-- Deals Closing This Month --}}
    <div class="pa-card">
        <div class="pa-card-hd">
            <div class="pa-card-title">
                <i class="ti ti-calendar-due" style="font-size:14px;color:#EF9F27"></i>
                Closing This Month
            </div>
            <span style="font-size:11.5px;color:var(--text-400)">
                {{ $closingThisMonth->count() }} deal{{ $closingThisMonth->count()!=1?'s':'' }}
                · ₹{{ number_format($closingThisMonth->sum('value')/100000,1) }}L
            </span>
        </div>
        @if($closingThisMonth->isEmpty())
        <div class="pa-empty">
            <i class="ti ti-calendar-x" style="font-size:24px;display:block;margin-bottom:8px"></i>
            Is month koi deal close hone wali nahi
        </div>
        @else
        <div class="closing-list">
            @foreach($closingThisMonth as $i => $deal)
            @php
                [$avBg,$avTx] = $avColors[$i % 5];
                $stage  = $cfgStages[$deal->stage] ?? [];
                $cd     = \Carbon\Carbon::parse($deal->expected_close_date);
                $isLate = $cd->isPast();
                $daysLeft = $isLate
                    ? '-' . $cd->diffInDays(now()) . 'd'
                    : '+' . now()->diffInDays($cd) . 'd';
            @endphp
            <a href="{{ route('tenant.deals.show', $deal->id) }}" class="closing-row">
                <div class="closing-av" style="background:{{ $avBg }};color:{{ $avTx }}">
                    {{ $deal->contact ? $initials($deal->contact->name) : Str::upper(Str::substr($deal->title,0,2)) }}
                </div>
                <div class="closing-info">
                    <div class="closing-title">{{ $deal->title }}</div>
                    <div class="closing-sub">
                        @if($deal->contact){{ $deal->contact->name }} · @endif
                        <span class="s-badge" style="background:{{ $stage['bg']??'#E6F1FB' }};color:{{ $stage['text_color']??'#185FA5' }}">{{ $stage['label']??$deal->stage }}</span>
                    </div>
                </div>
                <div class="closing-right">
                    <div class="closing-val">₹{{ number_format($deal->value) }}</div>
                    <div class="closing-date" style="color:{{ $isLate?'#E24B4A':'var(--text-400)' }}">
                        {{ $cd->format('d M') }}
                        <span style="font-size:10px">({{ $daysLeft }})</span>
                    </div>
                </div>
            </a>
            @endforeach
        </div>
        @endif
    </div>

    {{-- Top Open Deals --}}
    <div class="pa-card">
        <div class="pa-card-hd">
            <div class="pa-card-title">
                <i class="ti ti-star" style="font-size:14px;color:#EF9F27"></i>
                Top Deals
            </div>
        </div>
        @if($topDeals->isEmpty())
        <div class="pa-empty">
            <i class="ti ti-currency-rupee" style="font-size:24px;display:block;margin-bottom:8px"></i>
            Koi open deals nahi
        </div>
        @else
        @foreach($topDeals as $i => $deal)
        @php $stage=$cfgStages[$deal->stage]??[]; @endphp
        <a href="{{ route('tenant.deals.show', $deal->id) }}" class="top-deal-row">
            <div class="top-rank" style="{{ $i===0?'background:#FAEEDA;color:#854F0B;border-color:#EF9F27':'' }}">
                {{ $i+1 }}
            </div>
            <div class="top-deal-info">
                <div class="top-deal-title">{{ Str::limit($deal->title, 24) }}</div>
                <div class="top-deal-sub">
                    <span class="s-badge" style="background:{{ $stage['bg']??'#E6F1FB' }};color:{{ $stage['text_color']??'#185FA5' }}">{{ $stage['label']??$deal->stage }}</span>
                    @if($deal->assignedTo)
                    · {{ Str::limit($deal->assignedTo->name,12) }}
                    @endif
                </div>
            </div>
            <div class="top-deal-val">₹{{ number_format($deal->value/1000,0) }}K</div>
        </a>
        @endforeach
        @endif
    </div>

</div>

{{-- Row 3: Win/Loss Chart + Stage Summary --}}
<div class="pa-grid">

    {{-- Win/Loss Chart --}}
    <div class="pa-card">
        <div class="pa-card-hd">
            <div class="pa-card-title">
                <i class="ti ti-chart-bar" style="font-size:14px;color:#534AB7"></i>
                Win vs Loss
            </div>
            <span style="font-size:11.5px;color:var(--text-400)">Last 6 months</span>
        </div>
        <div class="chart-wrap">
            <div class="wl-chart">
                @foreach($winLossData as $m)
                @php
                    $wh = $wlMax > 0 ? round(($m['won']  / $wlMax) * 110) : 3;
                    $lh = $wlMax > 0 ? round(($m['lost'] / $wlMax) * 110) : 3;
                @endphp
                <div class="wl-col">
                    <div class="wl-bars">
                        <div class="wl-bar" style="height:{{ max($wh,3) }}px;background:#1D9E75"
                             data-tip="{{ $m['won'] }} Won — {{ $m['month'] }}"></div>
                        <div class="wl-bar" style="height:{{ max($lh,3) }}px;background:#E24B4A"
                             data-tip="{{ $m['lost'] }} Lost — {{ $m['month'] }}"></div>
                    </div>
                    <span class="wl-col-lbl">{{ $m['month'] }}</span>
                </div>
                @endforeach
            </div>
            <div class="chart-legend">
                <div class="legend-item">
                    <div class="legend-dot" style="background:#1D9E75"></div> Won
                </div>
                <div class="legend-item">
                    <div class="legend-dot" style="background:#E24B4A"></div> Lost
                </div>
            </div>
        </div>
    </div>

    {{-- Stage Summary Table --}}
    <div class="pa-card">
        <div class="pa-card-hd">
            <div class="pa-card-title">
                <i class="ti ti-git-branch" style="font-size:14px;color:#378ADD"></i>
                Stage Breakdown
            </div>
        </div>
        <div style="overflow:auto">
            <table style="width:100%;border-collapse:collapse">
                <thead>
                    <tr style="background:var(--bg-elevated)">
                        <th style="padding:9px 16px;text-align:left;font-size:11px;font-weight:600;color:var(--text-300);text-transform:uppercase;letter-spacing:.05em;border-bottom:1px solid var(--border-subtle)">Stage</th>
                        <th style="padding:9px 14px;text-align:right;font-size:11px;font-weight:600;color:var(--text-300);text-transform:uppercase;letter-spacing:.05em;border-bottom:1px solid var(--border-subtle)">Deals</th>
                        <th style="padding:9px 16px;text-align:right;font-size:11px;font-weight:600;color:var(--text-300);text-transform:uppercase;letter-spacing:.05em;border-bottom:1px solid var(--border-subtle)">Value</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($cfgStages as $slug => $stage)
                    @php $row=$stageData->get($slug); $cnt=$row?->count??0; $tot=$row?->total??0; @endphp
                    <tr style="{{ !$loop->last?'border-bottom:1px solid var(--border-subtle)':'' }}">
                        <td style="padding:10px 16px">
                            <span class="s-badge"
                                  style="background:{{ $stage['bg'] }};color:{{ $stage['text_color'] }};border:1px solid {{ $stage['color'] }}30">
                                {{ $stage['label'] }}
                            </span>
                        </td>
                        <td style="padding:10px 14px;text-align:right;font-family:'DM Mono',monospace;font-size:13px;font-weight:600;color:var(--text-100)">
                            {{ $cnt }}
                        </td>
                        <td style="padding:10px 16px;text-align:right;font-family:'DM Mono',monospace;font-size:12px;color:var(--text-300)">
                            ₹{{ number_format($tot/1000,0) }}K
                        </td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr style="background:var(--bg-elevated);border-top:2px solid var(--border-default)">
                        <td style="padding:10px 16px;font-size:12px;font-weight:600;color:var(--text-200)">Total</td>
                        <td style="padding:10px 14px;text-align:right;font-family:'DM Mono',monospace;font-size:13px;font-weight:700;color:var(--text-100)">
                            {{ $stageData->sum('count') }}
                        </td>
                        <td style="padding:10px 16px;text-align:right;font-family:'DM Mono',monospace;font-size:12px;font-weight:600;color:var(--text-100)">
                            ₹{{ number_format($stageData->sum('total')/100000,1) }}L
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

</div>

</div>
@endsection
