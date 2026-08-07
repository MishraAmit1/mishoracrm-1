@extends('layouts.app')
@section('title', 'Reports Overview')

@push('styles')
<style>
/* ── Range bar ───────────────────────────────────────────────────── */
.range-bar { display:flex; align-items:center; gap:10px; flex-wrap:wrap; margin-bottom:24px; }
.range-btn { padding:6px 14px; border-radius:20px; font-size:12.5px; font-weight:600; border:1.5px solid var(--border-default); background:none; color:var(--text-300); cursor:pointer; text-decoration:none; font-family:var(--font); transition:all .15s; }
.range-btn:hover { border-color:var(--border-strong); color:var(--text-100); }
.range-btn.active { border-color:var(--accent); background:var(--accent-dim); color:var(--accent); }

/* ── KPI grid ────────────────────────────────────────────────────── */
.kpi-grid { display:grid; grid-template-columns:repeat(6,1fr); gap:12px; margin-bottom:24px; }
@media(max-width:1200px) { .kpi-grid { grid-template-columns:repeat(3,1fr); } }
@media(max-width:640px)  { .kpi-grid { grid-template-columns:repeat(2,1fr); } }

.kpi-card { background:var(--bg-surface); border:1px solid var(--border-default); border-radius:var(--r-lg); padding:16px 18px; border-top:3px solid transparent; transition:transform .15s; }
.kpi-card:hover { transform:translateY(-1px); }
.kpi-label { font-size:11.5px; font-weight:600; color:var(--text-400); text-transform:uppercase; letter-spacing:.4px; margin-bottom:8px; }
.kpi-value { font-size:24px; font-weight:800; font-family:var(--mono); color:var(--text-100); letter-spacing:-.5px; }
.kpi-sub   { font-size:11.5px; color:var(--text-400); margin-top:4px; }

/* ── Charts grid ─────────────────────────────────────────────────── */
.charts-grid { display:grid; grid-template-columns:2fr 1fr; gap:16px; margin-bottom:16px; }
@media(max-width:1024px) { .charts-grid { grid-template-columns:1fr; } }

.chart-card { background:var(--bg-surface); border:1px solid var(--border-default); border-radius:var(--r-lg); overflow:hidden; }
.chart-head { padding:16px 20px; border-bottom:1px solid var(--border-subtle); display:flex; align-items:center; justify-content:space-between; }
.chart-title { font-size:14px; font-weight:700; color:var(--text-100); }
.chart-sub   { font-size:12px; color:var(--text-300); margin-top:2px; }
.chart-body  { padding:20px; }

/* ── Source bars ─────────────────────────────────────────────────── */
.source-bar { margin-bottom:12px; }
.source-meta { display:flex; align-items:center; justify-content:space-between; margin-bottom:4px; font-size:13px; }
.source-name { color:var(--text-100); font-weight:500; }
.source-count { font-family:var(--mono); color:var(--text-300); font-size:12px; }
.source-track { height:6px; background:var(--border-subtle); border-radius:3px; overflow:hidden; }
.source-fill  { height:100%; border-radius:3px; transition:width .6s var(--ease); }

/* ── Stage pills ─────────────────────────────────────────────────── */
.stage-row { display:flex; align-items:center; justify-content:space-between; padding:10px 0; border-bottom:1px solid var(--border-subtle); }
.stage-row:last-child { border-bottom:none; }
.stage-dot { width:8px; height:8px; border-radius:50%; flex-shrink:0; }
.stage-name { font-size:13px; color:var(--text-100); font-weight:500; }
.stage-vals { display:flex; gap:16px; }
.stage-count { font-family:var(--mono); font-size:13px; color:var(--text-200); }
.stage-amt   { font-family:var(--mono); font-size:13px; font-weight:700; color:var(--accent); }

/* ── Top staff ───────────────────────────────────────────────────── */
.staff-row { display:flex; align-items:center; gap:12px; padding:10px 0; border-bottom:1px solid var(--border-subtle); }
.staff-row:last-child { border-bottom:none; }
.staff-av { width:34px; height:34px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:13px; font-weight:700; flex-shrink:0; }
.staff-name  { font-size:13.5px; font-weight:600; color:var(--text-100); }
.staff-count { font-size:12px; color:var(--text-300); margin-top:1px; }
.staff-num   { font-family:var(--mono); font-size:14px; font-weight:700; color:var(--accent); margin-left:auto; }

/* ── Conversion box ──────────────────────────────────────────────── */
.conv-box { text-align:center; padding:24px; }
.conv-num  { font-size:48px; font-weight:900; font-family:var(--mono); letter-spacing:-2px; color:var(--accent); }
.conv-label{ font-size:13px; color:var(--text-300); margin-top:4px; }

/* ── Bottom grid ─────────────────────────────────────────────────── */
.bottom-grid { display:grid; grid-template-columns:1fr 200px 1fr; gap:16px; margin-bottom:16px; }
@media(max-width:900px) { .bottom-grid { grid-template-columns:1fr; } }
</style>
@endpush

@section('content')

@php
    $ranges = [
        'today'        => 'Today',
        'this_week'    => 'This Week',
        'this_month'   => 'This Month',
        'last_month'   => 'Last Month',
        'this_quarter' => 'This Quarter',
        'this_year'    => 'This Year',
    ];
    $curRange = $request->get('range','this_month');
    $stageColors = ['new'=>'var(--accent)','proposal'=>'var(--amber)','negotiation'=>'var(--purple)','won'=>'var(--green)','lost'=>'var(--red)'];
    $sourceColors = ['facebook'=>'#1877f2','instagram'=>'#e1306c','google'=>'#ea4335','website'=>'#6378ff','whatsapp'=>'#25D366','referral'=>'#0f6e56','cold_call'=>'#854f0b','other'=>'#9ca3af'];
    $avColors = [['#E6F1FB','#185FA5'],['#E1F5EE','#0F6E56'],['#FAEEDA','#854F0B'],['#EEEDFE','#3C3489'],['#FEE2E2','#991B1B']];
@endphp

<div class="page-head">
    <div>
        <div class="page-title">Reports & Analytics</div>
        <div class="page-sub">{{ $from->format('d M Y') }} — {{ $to->format('d M Y') }}</div>
    </div>
    <div class="page-actions">
        {{-- <a href="{{ route('tenant.reports.leads') }}"    class="btn btn-secondary">Lead Report</a> --}}
        <a href="{{ route('tenant.reports.deals') }}"    class="btn btn-secondary">Deal Report</a>
        <a href="{{ route('tenant.reports.deal_quotations') }}" class="btn btn-secondary">Deal Quotations</a>
        <a href="{{ route('tenant.reports.revenue') }}"  class="btn btn-secondary">Revenue</a>
        <a href="{{ route('tenant.reports.staff') }}"    class="btn btn-secondary">Staff</a>
    </div>
</div>

{{-- Range selector --}}
<div class="range-bar">
    @foreach($ranges as $key => $label)
    <a href="{{ route('tenant.reports.overview', ['range'=>$key]) }}"
       class="range-btn {{ $curRange === $key ? 'active':'' }}">
        {{ $label }}
    </a>
    @endforeach
</div>

{{-- KPI cards --}}
<div class="kpi-grid">
    @php
        $kpiDefs = [
            ['key'=>'leads',      'label'=>'New Leads',       'prefix'=>'',  'color'=>'var(--accent)', 'icon'=>'👤'],
            ['key'=>'contacts',   'label'=>'New Contacts',    'prefix'=>'',  'color'=>'var(--purple)', 'icon'=>'📒'],
            ['key'=>'deals',      'label'=>'New Deals',       'prefix'=>'',  'color'=>'var(--amber)',  'icon'=>'💼'],
            ['key'=>'quotations', 'label'=>'Quotations',      'prefix'=>'',  'color'=>'var(--accent)', 'icon'=>'📄'],
            ['key'=>'revenue',    'label'=>'Revenue Collected','prefix'=>'₹', 'color'=>'var(--green)',  'icon'=>'💰'],
            ['key'=>'tasks_done', 'label'=>'Tasks Done',      'prefix'=>'',  'color'=>'var(--green)',  'icon'=>'✅'],
        ];
    @endphp
    @foreach($kpiDefs as $kpi)
    <div class="kpi-card" style="border-top-color:{{ $kpi['color'] }}">
        <div class="kpi-label">{{ $kpi['icon'] }} {{ $kpi['label'] }}</div>
        <div class="kpi-value" style="color:{{ $kpi['color'] }}">
            {{ $kpi['prefix'] }}{{ $kpi['prefix']==='₹' ? number_format($kpis[$kpi['key']],0) : $kpis[$kpi['key']] }}
        </div>
    </div>
    @endforeach
</div>

{{-- Revenue chart + deal stages --}}
<div class="charts-grid">

    {{-- Monthly Revenue chart --}}
    <div class="chart-card">
        <div class="chart-head">
            <div>
                <div class="chart-title">Monthly Revenue (Last 12 Months)</div>
                <div class="chart-sub">Paid invoices trend</div>
            </div>
        </div>
        <div class="chart-body" style="height:280px">
            <canvas id="revenueChart"></canvas>
        </div>
    </div>

    {{-- Deal stages --}}
    <div class="chart-card">
        <div class="chart-head">
            <div class="chart-title">Deal Pipeline</div>
        </div>
        <div class="chart-body">
            @forelse($dealStages as $ds)
            @php $stageConfig = config('crm.deal.stages.'.$ds->stage, []); @endphp
            <div class="stage-row">
                <div style="display:flex;align-items:center;gap:8px;flex:1">
                    <div class="stage-dot" style="background:{{ $stageColors[$ds->stage] ?? 'var(--accent)' }}"></div>
                    <span class="stage-name">{{ $stageConfig['label'] ?? ucfirst($ds->stage) }}</span>
                </div>
                <div class="stage-vals">
                    <span class="stage-count">{{ $ds->count }}</span>
                    <span class="stage-amt">₹{{ number_format($ds->total/1000,0) }}K</span>
                </div>
            </div>
            @empty
            <div style="padding:24px;text-align:center;color:var(--text-400);font-size:13px">No deal data</div>
            @endforelse
        </div>
    </div>

</div>

{{-- Lead sources + Conversion + Top Staff --}}
<div class="bottom-grid">

    {{-- Lead sources --}}
    <div class="chart-card">
        <div class="chart-head">
            <div class="chart-title">Lead Sources</div>
        </div>
        <div class="chart-body">
            @php $maxLeads = $leadSources->max('count') ?: 1; @endphp
            @forelse($leadSources as $ls)
            @php
                $srcConfig = config('crm.lead.sources.'.$ls->source, []);
                $srcColor  = $sourceColors[$ls->source] ?? 'var(--accent)';
                $pct       = round(($ls->count / $maxLeads) * 100);
            @endphp
            <div class="source-bar">
                <div class="source-meta">
                    <span class="source-name">
                        {{ $srcConfig['icon'] ?? '📌' }} {{ $srcConfig['label'] ?? ucfirst($ls->source) }}
                    </span>
                    <span class="source-count">{{ $ls->count }}</span>
                </div>
                <div class="source-track">
                    <div class="source-fill" style="width:{{ $pct }}%;background:{{ $srcColor }}"></div>
                </div>
            </div>
            @empty
            <div style="padding:24px;text-align:center;color:var(--text-400);font-size:13px">No lead data</div>
            @endforelse
        </div>
    </div>

    {{-- Conversion rate --}}
    <div class="chart-card">
        <div class="chart-head">
            <div class="chart-title" style="font-size:13px">Conversion</div>
        </div>
        <div class="conv-box">
            <div class="conv-num" style="color:{{ $conversionRate >= 50 ? 'var(--green)' : ($conversionRate >= 25 ? 'var(--amber)' : 'var(--red)') }}">
                {{ $conversionRate }}%
            </div>
            <div class="conv-label">Lead → Contact</div>
            <div style="margin-top:20px;padding-top:16px;border-top:1px solid var(--border-subtle);text-align:left">
                <div style="display:flex;justify-content:space-between;font-size:12px;color:var(--text-300);margin-bottom:6px">
                    <span>Total Leads</span><span class="stage-count">{{ $kpis['leads'] }}</span>
                </div>
                <div style="display:flex;justify-content:space-between;font-size:12px;color:var(--green)">
                    <span>Converted</span>
                    <span style="font-family:var(--mono);font-weight:600">{{ round($kpis['leads'] * $conversionRate/100) }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Top staff --}}
    <div class="chart-card">
        <div class="chart-head">
            <div class="chart-title">Top Staff by Leads</div>
        </div>
        <div class="chart-body">
            @forelse($topStaff as $i => $s)
            @php [$avBg,$avTx] = $avColors[$i % 5]; @endphp
            <div class="staff-row">
                <div class="staff-av" style="background:{{ $avBg }};color:{{ $avTx }}">
                    {{ strtoupper(substr($s->name,0,1)) }}
                </div>
                <div>
                    <div class="staff-name">{{ $s->name }}</div>
                    <div class="staff-count">{{ $s->leads_count }} leads</div>
                </div>
                <div class="staff-num">{{ $s->leads_count }}</div>
            </div>
            @empty
            <div style="padding:24px;text-align:center;color:var(--text-400);font-size:13px">No staff data</div>
            @endforelse
        </div>
    </div>

</div>

@endsection

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>
<script>
// ── Revenue Chart ─────────────────────────────────────────────────
const revenueData = @json($monthlyRevenue);
const months = revenueData.map(d => {
    const [y,m] = d.month.split('-');
    return new Date(y, m-1).toLocaleString('default',{month:'short',year:'2-digit'});
});
const revenues = revenueData.map(d => parseFloat(d.revenue || 0));

const ctx = document.getElementById('revenueChart').getContext('2d');

// Gradient fill
const grad = ctx.createLinearGradient(0,0,0,280);
grad.addColorStop(0,'rgba(99,120,255,0.25)');
grad.addColorStop(1,'rgba(99,120,255,0.0)');

new Chart(ctx, {
    type: 'line',
    data: {
        labels: months,
        datasets: [{
            label: 'Revenue (₹)',
            data: revenues,
            borderColor: '#6378ff',
            backgroundColor: grad,
            borderWidth: 2.5,
            pointRadius: 4,
            pointBackgroundColor: '#6378ff',
            pointBorderColor: '#fff',
            pointBorderWidth: 2,
            tension: 0.4,
            fill: true,
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: {
                callbacks: {
                    label: ctx => '₹' + ctx.parsed.y.toLocaleString('en-IN')
                }
            }
        },
        scales: {
            x: {
                grid: { color: 'rgba(255,255,255,0.05)' },
                ticks: { color: '#9ca3af', font: { size: 11 } }
            },
            y: {
                grid: { color: 'rgba(255,255,255,0.05)' },
                ticks: {
                    color: '#9ca3af',
                    font: { size: 11 },
                    callback: v => '₹' + (v >= 1000 ? (v/1000).toFixed(0)+'K' : v)
                }
            }
        }
    }
});
</script>
@endpush