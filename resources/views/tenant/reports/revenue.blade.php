@extends('layouts.app')
@section('title', 'Revenue Report')

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
.kpi-sub   { font-size:11.5px; color:var(--text-400); margin-top:4px; }
.chart-card { background:var(--bg-surface); border:1px solid var(--border-default); border-radius:var(--r-lg); overflow:hidden; margin-bottom:16px; }
.chart-head { padding:14px 20px; border-bottom:1px solid var(--border-subtle); display:flex; align-items:center; justify-content:space-between; }
.chart-title { font-size:14px; font-weight:700; color:var(--text-100); }
.chart-body  { padding:20px; }
.charts-2 { display:grid; grid-template-columns:2fr 1fr; gap:16px; margin-bottom:16px; }
@media(max-width:900px) { .charts-2{grid-template-columns:1fr;} }

.status-row { display:flex; align-items:center; gap:12px; padding:12px 0; border-bottom:1px solid var(--border-subtle); }
.status-row:last-child { border-bottom:none; }
.contact-row { display:flex; align-items:center; gap:10px; padding:10px 0; border-bottom:1px solid var(--border-subtle); }
.contact-row:last-child { border-bottom:none; }
.badge { display:inline-flex; align-items:center; gap:4px; padding:3px 9px; border-radius:20px; font-size:11.5px; font-weight:600; }
</style>
@endpush

@section('content')

@php
    $ranges  = ['today'=>'Today','this_week'=>'This Week','this_month'=>'This Month','last_month'=>'Last Month','this_quarter'=>'This Quarter','this_year'=>'This Year'];
    $curRange = $request->get('range','this_month');
    $invoiceStatusCfg = config('crm.invoice.statuses');
    $statusColors = ['draft'=>'var(--amber)','sent'=>'var(--accent)','paid'=>'var(--green)','partial'=>'var(--purple)','overdue'=>'var(--red)'];
    $avColors = [['#E6F1FB','#185FA5'],['#E1F5EE','#0F6E56'],['#FAEEDA','#854F0B'],['#EEEDFE','#3C3489'],['#FEE2E2','#991B1B']];
@endphp

<div class="page-head">
    <div>
        <div style="font-size:13px;color:var(--text-300);margin-bottom:4px">
            <a href="{{ route('tenant.reports.overview') }}" style="color:var(--text-300);text-decoration:none">Reports</a>
            <span style="margin:0 6px">›</span> Revenue Report
        </div>
        <div class="page-title">Revenue Analytics</div>
        <div class="page-sub">{{ $from->format('d M Y') }} — {{ $to->format('d M Y') }}</div>
    </div>
</div>

<div class="range-bar">
    @foreach($ranges as $key => $label)
    <a href="{{ route('tenant.reports.revenue', ['range'=>$key]) }}"
       class="range-btn {{ $curRange===$key ? 'active':'' }}">{{ $label }}</a>
    @endforeach
</div>

{{-- KPIs --}}
<div class="kpi-grid">
    @php $kpiItems = [
        ['label'=>'Revenue Collected',  'value'=>'₹'.number_format($totalRevenue,0),    'color'=>'var(--green)',  'sub'=>$paidInvoices.' invoices paid'],
        ['label'=>'Pending Revenue',    'value'=>'₹'.number_format($pendingRevenue,0),  'color'=>'var(--amber)',  'sub'=>'Awaiting payment'],
        ['label'=>'Overdue Revenue',    'value'=>'₹'.number_format($overdueRevenue,0),  'color'=>'var(--red)',    'sub'=>'Past due date'],
        ['label'=>'Total Invoices',     'value'=>$totalInvoices,                         'color'=>'var(--accent)', 'sub'=>'In period'],
        ['label'=>'Quotation Conversion','value'=>$quotationRate.'%',                   'color'=>'var(--purple)', 'sub'=>$quotationsAccepted.'/'.$quotationsTotal.' accepted'],
    ]; @endphp
    @foreach($kpiItems as $k)
    <div class="kpi-card" style="border-top-color:{{ $k['color'] }}">
        <div class="kpi-label">{{ $k['label'] }}</div>
        <div class="kpi-value" style="color:{{ $k['color'] }}">{{ $k['value'] }}</div>
        <div class="kpi-sub">{{ $k['sub'] }}</div>
    </div>
    @endforeach
</div>

{{-- Monthly revenue chart + Invoice status --}}
<div class="charts-2">

    <div class="chart-card">
        <div class="chart-head"><div class="chart-title">Monthly Revenue Trend (Last 12 Months)</div></div>
        <div class="chart-body" style="height:280px">
            <canvas id="revenueChart"></canvas>
        </div>
    </div>

    <div class="chart-card">
        <div class="chart-head"><div class="chart-title">Invoice Status Breakdown</div></div>
        <div class="chart-body">
            <canvas id="statusChart" style="max-height:180px;margin-bottom:16px"></canvas>
            @foreach($invoiceStatusCfg as $key => $sc)
            @php $sd = $invoiceStatuses[$key] ?? null; @endphp
            <div class="status-row">
                <div style="width:8px;height:8px;border-radius:50%;background:{{ $statusColors[$key] ?? 'var(--accent)' }};flex-shrink:0"></div>
                <span style="font-size:13px;color:var(--text-100);flex:1">{{ $sc['label'] }}</span>
                <span style="font-family:var(--mono);font-size:12px;color:var(--text-300)">{{ $sd->count ?? 0 }}</span>
                <span style="font-family:var(--mono);font-size:12px;font-weight:700;color:{{ $statusColors[$key] ?? 'var(--accent)' }}">₹{{ number_format(($sd->total ?? 0)/1000,0) }}K</span>
            </div>
            @endforeach
        </div>
    </div>

</div>

{{-- Top contacts by revenue + Recent paid --}}
<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px">
    @media(max-width:900px) { grid-template-columns:1fr; }

    {{-- Top contacts --}}
    <div class="chart-card">
        <div class="chart-head"><div class="chart-title">Top Contacts by Revenue</div></div>
        <div class="chart-body">
            @php $maxRev = $byContact->max('revenue') ?: 1; @endphp
            @forelse($byContact as $i => $bc)
            @php [$avBg,$avTx] = $avColors[$i % 5]; @endphp
            <div class="contact-row">
                <div style="width:32px;height:32px;border-radius:50%;background:{{ $avBg }};color:{{ $avTx }};display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;flex-shrink:0">
                    {{ strtoupper(substr($bc->contact?->name ?? '?',0,1)) }}
                </div>
                <div style="flex:1;min-width:0">
                    <div style="font-size:13px;font-weight:600;color:var(--text-100);white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                        {{ $bc->contact?->name ?? 'Unknown' }}
                    </div>
                    <div style="height:3px;background:var(--border-subtle);border-radius:2px;margin-top:4px;overflow:hidden">
                        <div style="height:100%;background:var(--green);width:{{ round(($bc->revenue/$maxRev)*100) }}%;border-radius:2px"></div>
                    </div>
                </div>
                <div style="text-align:right;flex-shrink:0;margin-left:8px">
                    <div style="font-size:13px;font-weight:700;font-family:var(--mono);color:var(--green)">₹{{ number_format($bc->revenue/1000,0) }}K</div>
                    <div style="font-size:11px;color:var(--text-400)">{{ $bc->count }} inv.</div>
                </div>
            </div>
            @empty
            <div style="text-align:center;padding:30px;color:var(--text-400)">No data</div>
            @endforelse
        </div>
    </div>

    {{-- Recent paid --}}
    <div class="chart-card">
        <div class="chart-head">
            <div class="chart-title">Recent Payments</div>
            <a href="{{ route('tenant.invoices.index') }}" class="btn btn-secondary btn-sm">All →</a>
        </div>
        <div class="chart-body">
            @forelse($recentPaid as $inv)
            <div style="display:flex;align-items:center;justify-content:space-between;padding:10px 0;border-bottom:1px solid var(--border-subtle)">
                <div>
                    <div style="font-size:13px;font-weight:600;color:var(--text-100)">{{ $inv->number }}</div>
                    <div style="font-size:12px;color:var(--text-300)">{{ $inv->contact?->name ?? '—' }}</div>
                </div>
                <div style="text-align:right">
                    <div style="font-size:14px;font-weight:700;font-family:var(--mono);color:var(--green)">₹{{ number_format($inv->total) }}</div>
                    <div style="font-size:11px;color:var(--text-400);font-family:var(--mono)">{{ $inv->paid_at?->format('d M Y') }}</div>
                </div>
            </div>
            @empty
            <div style="text-align:center;padding:30px;color:var(--text-400)">No paid invoices</div>
            @endforelse
        </div>
    </div>

</div>

@endsection

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>
<script>
// ── Revenue trend ─────────────────────────────────────────────────
const rv = @json($monthlyRevenue);
const ctx = document.getElementById('revenueChart').getContext('2d');
const grad = ctx.createLinearGradient(0,0,0,280);
grad.addColorStop(0,'rgba(29,158,117,0.3)');
grad.addColorStop(1,'rgba(29,158,117,0.0)');

new Chart(ctx, {
    type:'line',
    data:{
        labels: rv.map(d => { const [y,m]=d.month.split('-'); return new Date(y,m-1).toLocaleString('default',{month:'short',year:'2-digit'}); }),
        datasets:[{
            label:'Revenue',
            data: rv.map(d => parseFloat(d.revenue||0)),
            borderColor:'#1D9E75', backgroundColor:grad,
            borderWidth:2.5, pointRadius:4,
            pointBackgroundColor:'#1D9E75', pointBorderColor:'#fff', pointBorderWidth:2,
            tension:0.4, fill:true
        }]
    },
    options:{
        responsive:true, maintainAspectRatio:false,
        plugins:{ legend:{display:false}, tooltip:{ callbacks:{ label: c=>'₹'+c.parsed.y.toLocaleString('en-IN') } } },
        scales:{
            x:{grid:{color:'rgba(255,255,255,0.04)'},ticks:{color:'#9ca3af',font:{size:10}}},
            y:{grid:{color:'rgba(255,255,255,0.04)'},ticks:{color:'#9ca3af',font:{size:11},callback:v=>'₹'+(v>=100000?(v/100000).toFixed(1)+'L':v>=1000?(v/1000).toFixed(0)+'K':v)},beginAtZero:true}
        }
    }
});

// ── Invoice status donut ──────────────────────────────────────────
const statuses = @json($invoiceStatuses);
const statusColors = {draft:'#EF9F27',sent:'#6378ff',paid:'#1D9E75',partial:'#a78bfa',overdue:'#E05252'};
const keys = Object.keys(statusColors);
new Chart(document.getElementById('statusChart'), {
    type:'doughnut',
    data:{
        labels: keys,
        datasets:[{
            data: keys.map(k => statuses[k]?.count || 0),
            backgroundColor: keys.map(k => statusColors[k]),
            borderWidth:0, hoverOffset:4
        }]
    },
    options:{ responsive:true, plugins:{legend:{labels:{color:'#9ca3af',font:{size:11}}}}, cutout:'65%' }
});
</script>
@endpush