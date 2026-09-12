@extends('layouts.app')
@section('title', 'Deal Report')

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
.charts-2 { display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:16px; }
@media(max-width:900px) { .charts-2{grid-template-columns:1fr;} }

.stage-row  { display:flex; align-items:center; gap:12px; padding:12px 0; border-bottom:1px solid var(--border-subtle); }
.stage-row:last-child { border-bottom:none; }
.stage-dot  { width:10px; height:10px; border-radius:50%; flex-shrink:0; }

.deal-row { display:flex; align-items:center; justify-content:space-between; padding:12px 0; border-bottom:1px solid var(--border-subtle); gap:12px; }
.deal-row:last-child { border-bottom:none; }
.deal-rank { width:24px; height:24px; border-radius:50%; background:var(--bg-elevated); display:flex; align-items:center; justify-content:center; font-size:11px; font-weight:700; color:var(--text-300); flex-shrink:0; }

.badge { display:inline-flex; align-items:center; gap:4px; padding:3px 9px; border-radius:20px; font-size:11.5px; font-weight:600; }

.win-circle {
    width:120px; height:120px; border-radius:50%;
    display:flex; flex-direction:column; align-items:center; justify-content:center;
    background:conic-gradient(var(--green) 0% var(--pct), var(--border-subtle) var(--pct) 100%);
    margin:0 auto;
}
.win-inner {
    width:90px; height:90px; border-radius:50%;
    background:var(--bg-surface);
    display:flex; flex-direction:column; align-items:center; justify-content:center;
}
.win-num   { font-size:22px; font-weight:800; font-family:var(--mono); color:var(--green); }
.win-label { font-size:10px; color:var(--text-400); font-weight:600; }
</style>
@endpush

@section('content')

@php
    $ranges  = ['today'=>'Today','this_week'=>'This Week','this_month'=>'This Month','last_month'=>'Last Month','this_quarter'=>'This Quarter','this_year'=>'This Year'];
    $curRange = $request->get('range','this_month');
    $stagesCfg = config('crm.deal.stages');
    $stageColors = ['new'=>'var(--accent)','proposal'=>'var(--amber)','negotiation'=>'var(--purple)','won'=>'var(--green)','lost'=>'var(--red)'];
    $avColors = [['var(--accent-dim)','var(--accent)'],['var(--green-dim)','var(--green)'],['var(--amber-dim)','var(--amber)'],['var(--purple-dim)','var(--purple)'],['#FEE2E2','#991B1B']];
    $winPct   = $winRate . '%';
@endphp

<div class="page-head">
    <div>
        <div style="font-size:13px;color:var(--text-300);margin-bottom:4px">
            <a href="{{ route('tenant.reports.overview') }}" style="color:var(--text-300);text-decoration:none">Reports</a>
            <span style="margin:0 6px">›</span> Deal Report
        </div>
        <div class="page-title">Deal Analytics</div>
        <div class="page-sub">{{ $from->format('d M Y') }} — {{ $to->format('d M Y') }}</div>
    </div>
    <div class="page-actions">
        <a href="{{ route('tenant.reports.deal_quotations') }}" class="btn btn-secondary">Deal Quotations</a>
    </div>
</div>

<div class="range-bar">
    @foreach($ranges as $key => $label)
    <a href="{{ route('tenant.reports.deals', ['range'=>$key]) }}"
       class="range-btn {{ $curRange===$key ? 'active':'' }}">{{ $label }}</a>
    @endforeach
</div>

{{-- KPIs --}}
<div class="kpi-grid">
    @php $kpiItems = [
        ['label'=>'Deals Won',        'value'=>$wonCount,                      'color'=>'var(--green)'],
        ['label'=>'Won Value',         'value'=>'₹'.number_format($wonValue/1000,0).'K', 'color'=>'var(--green)'],
        ['label'=>'Deals Lost',        'value'=>$lostCount,                    'color'=>'var(--red)'],
        ['label'=>'Win Rate',          'value'=>$winRate.'%',                  'color'=>$winRate>=50?'var(--green)':'var(--amber)'],
        ['label'=>'Avg Deal Size',     'value'=>'₹'.number_format($avgDealSize/1000,1).'K', 'color'=>'var(--accent)'],
    ]; @endphp
    @foreach($kpiItems as $k)
    <div class="kpi-card" style="border-top-color:{{ $k['color'] }}">
        <div class="kpi-label">{{ $k['label'] }}</div>
        <div class="kpi-value" style="color:{{ $k['color'] }}">{{ $k['value'] }}</div>
    </div>
    @endforeach
</div>

{{-- Monthly chart + Win rate --}}
<div class="charts-2">

    {{-- Monthly deal trend --}}
    <div class="chart-card">
        <div class="chart-head"><div class="chart-title">Monthly Deal Trend</div></div>
        <div class="chart-body" style="height:260px">
            <canvas id="monthlyChart"></canvas>
        </div>
    </div>

    {{-- Win rate + pipeline --}}
    <div class="chart-card">
        <div class="chart-head"><div class="chart-title">Win Rate & Pipeline</div></div>
        <div class="chart-body">

            {{-- Win rate circle --}}
            <div style="text-align:center;margin-bottom:20px">
                <div style="position:relative;width:120px;height:120px;margin:0 auto">
                    <canvas id="winChart" width="120" height="120"></canvas>
                    <div style="position:absolute;inset:0;display:flex;flex-direction:column;align-items:center;justify-content:center">
                        <div style="font-size:22px;font-weight:800;font-family:var(--mono);color:var(--green)">{{ $winRate }}%</div>
                        <div style="font-size:10px;color:var(--text-400);font-weight:600">WIN RATE</div>
                    </div>
                </div>
                <div style="display:flex;justify-content:center;gap:16px;margin-top:12px;font-size:12px">
                    <span style="color:var(--green)">✓ Won: {{ $wonCount }}</span>
                    <span style="color:var(--red)">✗ Lost: {{ $lostCount }}</span>
                </div>
            </div>

            {{-- Pipeline value --}}
            <div style="background:var(--bg-elevated);border-radius:var(--r-sm);padding:14px;text-align:center;margin-top:8px">
                <div style="font-size:11px;color:var(--text-400);font-weight:600;text-transform:uppercase;letter-spacing:.4px;margin-bottom:4px">Active Pipeline</div>
                <div style="font-size:22px;font-weight:800;font-family:var(--mono);color:var(--accent)">
                    ₹{{ number_format($pipelineValue/100000, 2) }}L
                </div>
            </div>

        </div>
    </div>
</div>

{{-- Stage breakdown + Top deals --}}
<div class="charts-2">

    {{-- By stage --}}
    <div class="chart-card">
        <div class="chart-head"><div class="chart-title">By Stage</div></div>
        <div class="chart-body">
            @foreach($stagesCfg as $key => $sc)
            @php $sd = $byStage[$key] ?? null; @endphp
            <div class="stage-row">
                <div class="stage-dot" style="background:{{ $stageColors[$key] }}"></div>
                <div style="flex:1">
                    <div style="font-size:13.5px;font-weight:600;color:var(--text-100)">{{ $sc['label'] }}</div>
                    <div style="height:4px;background:var(--border-subtle);border-radius:2px;margin-top:5px;overflow:hidden">
                        <div style="height:100%;background:{{ $stageColors[$key] }};width:{{ $sd ? min(round(($sd->count/max($byStage->max('count'),1))*100),100) : 0 }}%;border-radius:2px"></div>
                    </div>
                </div>
                <div style="text-align:right">
                    <div style="font-size:13px;font-weight:700;font-family:var(--mono);color:{{ $stageColors[$key] }}">{{ $sd->count ?? 0 }}</div>
                    <div style="font-size:11px;color:var(--text-400);font-family:var(--mono)">₹{{ number_format(($sd->total ?? 0)/1000,0) }}K</div>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    {{-- By staff --}}
    <div class="chart-card">
        <div class="chart-head"><div class="chart-title">Top Staff by Deal Value</div></div>
        <div class="chart-body">
            @php $maxVal = $byStaff->max('total') ?: 1; @endphp
            @forelse($byStaff as $i => $bs)
            @php [$avBg,$avTx] = $avColors[$i % 5]; @endphp
            <div class="deal-row">
                <div style="width:28px;height:28px;border-radius:50%;background:{{ $avBg }};color:{{ $avTx }};display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;flex-shrink:0">
                    {{ strtoupper(substr($bs->assignedTo?->name ?? '?',0,1)) }}
                </div>
                <div style="flex:1;min-width:0">
                    <div style="font-size:13px;font-weight:600;color:var(--text-100)">{{ $bs->assignedTo?->name ?? 'Unassigned' }}</div>
                    <div style="height:3px;background:var(--border-subtle);border-radius:2px;margin-top:4px;overflow:hidden">
                        <div style="height:100%;background:var(--accent);width:{{ round(($bs->total/$maxVal)*100) }}%;border-radius:2px"></div>
                    </div>
                </div>
                <div style="text-align:right;flex-shrink:0">
                    <div style="font-size:13px;font-weight:700;font-family:var(--mono);color:var(--accent)">₹{{ number_format($bs->total/1000,0) }}K</div>
                    <div style="font-size:11px;color:var(--text-400)">{{ $bs->count }} deals</div>
                </div>
            </div>
            @empty
            <div style="text-align:center;padding:30px;color:var(--text-400)">No data</div>
            @endforelse
        </div>
    </div>

</div>

{{-- Top deals table --}}
<div class="chart-card">
    <div class="chart-head">
        <div class="chart-title">Top Deals by Value</div>
        <a href="{{ route('tenant.deals.index') }}" class="btn btn-secondary btn-sm">View All →</a>
    </div>
    <div style="overflow-x:auto">
        <table class="data-table" style="width:100%;border-collapse:collapse">
            <thead>
                <tr style="background:var(--bg-elevated)">
                    <th style="padding:10px 16px;text-align:left;font-size:11px;font-weight:600;color:var(--text-400);text-transform:uppercase;letter-spacing:.5px;border-bottom:1px solid var(--border-subtle)">#</th>
                    <th style="padding:10px 16px;text-align:left;font-size:11px;font-weight:600;color:var(--text-400);text-transform:uppercase;letter-spacing:.5px;border-bottom:1px solid var(--border-subtle)">Deal</th>
                    <th style="padding:10px 16px;text-align:left;font-size:11px;font-weight:600;color:var(--text-400);text-transform:uppercase;letter-spacing:.5px;border-bottom:1px solid var(--border-subtle)">Contact</th>
                    <th style="padding:10px 16px;text-align:left;font-size:11px;font-weight:600;color:var(--text-400);text-transform:uppercase;letter-spacing:.5px;border-bottom:1px solid var(--border-subtle)">Stage</th>
                    <th style="padding:10px 16px;text-align:right;font-size:11px;font-weight:600;color:var(--text-400);text-transform:uppercase;letter-spacing:.5px;border-bottom:1px solid var(--border-subtle)">Value</th>
                    <th style="padding:10px 16px;text-align:left;font-size:11px;font-weight:600;color:var(--text-400);text-transform:uppercase;letter-spacing:.5px;border-bottom:1px solid var(--border-subtle)">Assigned</th>
                </tr>
            </thead>
            <tbody>
                @forelse($topDeals as $i => $deal)
                @php $sc = $stagesCfg[$deal->stage] ?? []; @endphp
                <tr>
                    <td style="padding:12px 16px;border-bottom:1px solid var(--border-subtle)" data-label="#">
                        <span style="width:22px;height:22px;border-radius:50%;background:var(--bg-elevated);display:inline-flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;color:var(--text-300)">{{ $i+1 }}</span>
                    </td>
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
                    <td style="padding:12px 16px;border-bottom:1px solid var(--border-subtle);text-align:right;font-family:var(--mono);font-size:14px;font-weight:700;color:var(--accent)" data-label="Value">
                        ₹{{ number_format($deal->value) }}
                    </td>
                    <td style="padding:12px 16px;border-bottom:1px solid var(--border-subtle);font-size:13px;color:var(--text-200)" data-label="Assigned">
                        {{ $deal->assignedTo?->name ?? '—' }}
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" style="padding:30px;text-align:center;color:var(--text-400)">No deals</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>
<script>
// ── Monthly deal trend ────────────────────────────────────────────
const md = @json($monthlyDeals);
new Chart(document.getElementById('monthlyChart'), {
    type: 'bar',
    data: {
        labels: md.map(d => { const [y,m]=d.month.split('-'); return new Date(y,m-1).toLocaleString('default',{month:'short',year:'2-digit'}); }),
        datasets: [
            { label:'Deals', data:md.map(d=>d.count), backgroundColor:'rgba(255,122,89,0.7)', borderRadius:4, yAxisID:'y' },
            { label:'Value',  data:md.map(d=>parseFloat(d.total||0)), type:'line', borderColor:'var(--green)', backgroundColor:'rgba(29,158,117,0.1)', borderWidth:2.5, pointRadius:4, pointBackgroundColor:'var(--green)', tension:0.4, fill:true, yAxisID:'y1' }
        ]
    },
    options: {
        responsive:true, maintainAspectRatio:false,
        plugins:{ legend:{labels:{color:'#7C98B6',font:{size:11}}} },
        scales: {
            x: { grid:{color:'rgba(51,71,91,0.06)'}, ticks:{color:'#7C98B6',font:{size:10}} },
            y: { grid:{color:'rgba(51,71,91,0.06)'}, ticks:{color:'#7C98B6',font:{size:11}}, beginAtZero:true },
            y1:{ position:'right', grid:{display:false}, ticks:{color:'#7C98B6',font:{size:10}, callback:v=>'₹'+(v/1000).toFixed(0)+'K'}, beginAtZero:true }
        }
    }
});

// ── Win rate donut ────────────────────────────────────────────────
new Chart(document.getElementById('winChart'), {
    type:'doughnut',
    data:{
        datasets:[{
            data:[{{ $winRate }}, {{ 100 - $winRate }}],
            backgroundColor:['var(--green)','rgba(51,71,91,0.08)'],
            borderWidth:0, hoverOffset:0
        }]
    },
    options:{ responsive:false, plugins:{legend:{display:false}}, cutout:'75%' }
});
</script>
@endpush