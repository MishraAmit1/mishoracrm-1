@extends('layouts.app')
@section('title', 'SuperAdmin Dashboard')

@push('styles')
<style>
.grid-main   { display: grid; grid-template-columns: 1fr 360px; gap: 16px; margin-bottom: 16px; }
.grid-bottom { display: grid; grid-template-columns: 1fr 1fr;   gap: 16px; margin-bottom: 16px; }
@media(max-width:1100px) { .grid-main,.grid-bottom { grid-template-columns: 1fr; } }

/* SuperAdmin badge */
.sa-badge {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 4px 10px; border-radius: 20px;
    background: rgba(255,82,87,0.1);
    border: 1px solid rgba(255,82,87,0.25);
    color: var(--red); font-size: 11px; font-weight: 700;
    text-transform: uppercase; letter-spacing: 0.6px;
    margin-bottom: 6px;
}
.sa-badge-dot {
    width: 5px; height: 5px; border-radius: 50%;
    background: var(--red);
    animation: blink 1.5s ease-in-out infinite;
}
@keyframes blink { 0%,100%{opacity:1} 50%{opacity:0.3} }

/* Plan cards */
.plan-cards { display: grid; grid-template-columns: repeat(3,1fr); gap: 10px; }
@media(max-width:700px) { .plan-cards { grid-template-columns: 1fr; } }
.plan-card {
    background: var(--bg-elevated);
    border: 1px solid var(--border-default);
    border-radius: var(--r-md); padding: 16px;
    transition: border-color 0.15s var(--ease);
}
.plan-card:hover { border-color: var(--border-strong); }
.plan-card-name  { font-size: 13px; font-weight: 700; color: var(--text-100); margin-bottom: 4px; }
.plan-card-price { font-size: 11.5px; color: var(--text-300); font-family: var(--mono); margin-bottom: 12px; }
.plan-card-row   { display: flex; justify-content: space-between; font-size: 12px; margin-bottom: 4px; }
.plan-card-label { color: var(--text-300); }
.plan-card-val   { color: var(--text-100); font-weight: 600; font-family: var(--mono); }
.plan-card-mrr   { margin-top: 10px; padding-top: 10px; border-top: 1px solid var(--border-subtle); font-size: 12.5px; color: var(--green); font-weight: 600; font-family: var(--mono); }

/* Alert row */
.alert-row {
    display: flex; align-items: flex-start; gap: 12px;
    padding: 12px 20px; border-bottom: 1px solid var(--border-subtle);
    transition: background 0.15s var(--ease);
}
.alert-row:last-child { border-bottom: none; }
.alert-row:hover { background: var(--bg-hover); }
.alert-dot-wrap { width: 32px; height: 32px; border-radius: 50%; background: var(--red-dim); display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.alert-dot-wrap svg { width: 14px; height: 14px; color: var(--red); }
.alert-title { font-size: 13px; font-weight: 600; color: var(--text-100); }
.alert-sub   { font-size: 11.5px; color: var(--text-300); margin-top: 1px; font-family: var(--mono); }

/* Tenant table status */
.t-status { display: inline-flex; align-items: center; gap: 5px; font-size: 11px; font-weight: 600; padding: 2px 8px; border-radius: 20px; }
.t-status::before { content:''; width:5px; height:5px; border-radius:50%; }
.t-active    { background:var(--green-dim); color:var(--green); }
.t-active::before { background:var(--green); }
.t-inactive  { background:var(--amber-dim); color:var(--amber); }
.t-inactive::before { background:var(--amber); }
.t-suspended { background:var(--red-dim); color:var(--red); }
.t-suspended::before { background:var(--red); }

/* Period tabs */
.period-tabs { display:flex; gap:2px; background:var(--bg-input); border-radius:var(--r-sm); padding:2px; }
.period-tab  { padding:4px 10px; font-size:12px; font-weight:600; border-radius:4px; cursor:pointer; border:none; background:none; color:var(--text-300); font-family:var(--font); transition:all 0.15s var(--ease); }
.period-tab.active { background:var(--bg-surface); color:var(--text-100); box-shadow:0 1px 4px rgba(0,0,0,0.2); }
</style>
@endpush

@section('content')

{{-- Page header --}}
<div class="page-head">
    <div>
        <div class="sa-badge">
            <div class="sa-badge-dot"></div>
            Super Admin
        </div>
        <div class="page-title">Platform Overview</div>
        <div class="page-sub">
            {{ now()->format('l, d M Y') }} &mdash; All tenants across CrmPro
        </div>
    </div>
    <div class="page-actions">
        {{-- <a href="{{ route('superadmin.tenants.create') }}" --}}  <a href="#"
         class="btn btn-primary">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
            </svg>
            Add Tenant
        </a>
        <a href="{{ route('superadmin.tenants.index') }}"
        class="btn btn-secondary">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 010 3.75H5.625a1.875 1.875 0 010-3.75z"/>
            </svg>
            All Tenants
        </a>
    </div>
</div>

{{-- Stat cards --}}
<div class="stats-grid">

    <div class="stat-card s-blue">
        <div class="stat-top">
            <div class="stat-icon s-blue">
                <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21"/>
                </svg>
            </div>
            <div class="stat-trend up">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18L9 11.25l4.306 4.307a11.95 11.95 0 015.814-5.519l2.74-1.22m0 0l-5.94-2.28m5.94 2.28l-2.28 5.941"/></svg>
                +{{ $stats['new_this_month'] }}
            </div>
        </div>
        <div class="stat-num">{{ $stats['total_tenants'] }}</div>
        <div class="stat-label">Total Tenants</div>
        <div class="stat-sub">{{ $stats['active_tenants'] }} active &middot; {{ $stats['new_today'] }} today</div>
    </div>

    <div class="stat-card s-green">
        <div class="stat-top">
            <div class="stat-icon s-green">
                <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75"/>
                </svg>
            </div>
            <div class="stat-trend up">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18L9 11.25l4.306 4.307a11.95 11.95 0 015.814-5.519l2.74-1.22m0 0l-5.94-2.28m5.94 2.28l-2.28 5.941"/></svg>
                MRR
            </div>
        </div>
        <div class="stat-num">₹{{ number_format($stats['total_mrr'] / 1000, 1) }}K</div>
        <div class="stat-label">Monthly Recurring Revenue</div>
        <div class="stat-sub">{{ $stats['active_subs'] }} paid subscriptions</div>
    </div>

    <div class="stat-card s-amber">
        <div class="stat-top">
            <div class="stat-icon s-amber">
                <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.75 3.75 0 11-6.75 0 3.75 3.75 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/>
                </svg>
            </div>
            <div class="stat-trend up">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18L9 11.25l4.306 4.307a11.95 11.95 0 015.814-5.519l2.74-1.22m0 0l-5.94-2.28m5.94 2.28l-2.28 5.941"/></svg>
                +{{ $stats['new_this_month'] }}
            </div>
        </div>
        <div class="stat-num">{{ $stats['total_users'] }}</div>
        <div class="stat-label">Total Users</div>
        <div class="stat-sub">{{ $stats['active_users'] }} active users</div>
    </div>

    <div class="stat-card s-purple">
        <div class="stat-top">
            <div class="stat-icon s-purple">
                <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
                </svg>
            </div>
            @if($stats['expiring_soon'] > 0)
            <div class="stat-trend down">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9.303 3.376c.866 1.5-.217 3.374-1.948 3.374H4.645c-1.73 0-2.813-1.874-1.948-3.374l7.028-12.124c.866-1.5 3.032-1.5 3.898 0l7.027 12.124z"/></svg>
                Alert
            </div>
            @endif
        </div>
        <div class="stat-num">{{ $stats['trial_subs'] }}</div>
        <div class="stat-label">On Trial</div>
        <div class="stat-sub">{{ $stats['expiring_soon'] }} expiring in 7 days</div>
    </div>

</div>

{{-- Charts row --}}
<div class="grid-main">

    {{-- Signups + Revenue chart --}}
    <div class="card">
        <div class="card-header">
            <div>
                <div class="card-title">Growth Overview</div>
                <div class="card-subtitle">Signups & revenue — {{ now()->year }}</div>
            </div>
            <div class="period-tabs">
                <button class="period-tab active" onclick="switchChart('signups',this)">Signups</button>
                <button class="period-tab" onclick="switchChart('revenue',this)">Revenue</button>
            </div>
        </div>
        <div class="card-body" style="padding-bottom:12px">
            <canvas id="growthChart" height="220" style="width:100%;display:block"></canvas>
        </div>
    </div>

    {{-- Plan breakdown --}}
    <div class="card">
        <div class="card-header">
            <div>
                <div class="card-title">Plan Breakdown</div>
                <div class="card-subtitle">Active subscriptions by plan</div>
            </div>
        </div>
        <div class="card-body">
            <div class="plan-cards">
                @foreach($planBreakdown as $plan)
                <div class="plan-card">
                    <div class="plan-card-name">{{ $plan['name'] }}</div>
                    <div class="plan-card-price">₹{{ number_format($plan['price']) }}/mo</div>
                    <div class="plan-card-row">
                        <span class="plan-card-label">Active</span>
                        <span class="plan-card-val">{{ $plan['active_count'] }}</span>
                    </div>
                    <div class="plan-card-row">
                        <span class="plan-card-label">Trial</span>
                        <span class="plan-card-val">{{ $plan['trial_count'] }}</span>
                    </div>
                    <div class="plan-card-mrr">MRR ₹{{ number_format($plan['mrr']) }}</div>
                </div>
                @endforeach
            </div>

            {{-- Tenant status donut --}}
            <div style="margin-top:20px;padding-top:16px;border-top:1px solid var(--border-subtle)">
                <div class="card-title" style="margin-bottom:14px">Tenant Status</div>
                <div class="donut-wrap">
                    <svg width="80" height="80" viewBox="0 0 80 80" style="flex-shrink:0">
                        @php
                            $total    = max($stats['total_tenants'], 1);
                            $actPct   = round(($stats['active_tenants']   / $total) * 188);
                            $inactPct = round(($stats['inactive_tenants'] / $total) * 188);
                            $suspPct  = round(($stats['suspended']        / $total) * 188);
                            $off1 = 0;
                            $off2 = -$actPct;
                            $off3 = -$actPct - $inactPct;
                        @endphp
                        <circle cx="40" cy="40" r="30" fill="none" stroke="var(--border-subtle)" stroke-width="12"/>
                        <circle cx="40" cy="40" r="30" fill="none" stroke="var(--green)"  stroke-width="12" stroke-dasharray="{{ $actPct }} 188"  stroke-dashoffset="{{ $off1 }}" transform="rotate(-90 40 40)"/>
                        <circle cx="40" cy="40" r="30" fill="none" stroke="var(--amber)"  stroke-width="12" stroke-dasharray="{{ $inactPct }} 188" stroke-dashoffset="{{ $off2 }}" transform="rotate(-90 40 40)"/>
                        <circle cx="40" cy="40" r="30" fill="none" stroke="var(--red)"    stroke-width="12" stroke-dasharray="{{ $suspPct }} 188"  stroke-dashoffset="{{ $off3 }}" transform="rotate(-90 40 40)"/>
                    </svg>
                    <div class="donut-legend">
                        <div class="legend-item">
                            <div class="legend-dot" style="background:var(--green)"></div>
                            <span class="legend-label">Active</span>
                            <span class="legend-val">{{ $stats['active_tenants'] }}</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-dot" style="background:var(--amber)"></div>
                            <span class="legend-label">Inactive</span>
                            <span class="legend-val">{{ $stats['inactive_tenants'] }}</span>
                        </div>
                        <div class="legend-item">
                            <div class="legend-dot" style="background:var(--red)"></div>
                            <span class="legend-label">Suspended</span>
                            <span class="legend-val">{{ $stats['suspended'] }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

{{-- Recent tenants + expiring alerts --}}
<div class="grid-bottom">

    {{-- Recent tenants table --}}
    <div class="card">
        <div class="card-header">
            <div>
                <div class="card-title">Recent Tenants</div>
                <div class="card-subtitle">Latest signups</div>
            </div>
            <a href="{{ route('superadmin.tenants.index') }}"
            class="btn btn-secondary btn-sm">View all →</a>
        </div>
        <div style="overflow-x:auto">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Company</th>
                        <th>Subdomain</th>
                        <th>Plan</th>
                        <th>Status</th>
                        <th>Joined</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($recentTenants as $tenant)
                    <tr>
                        <td data-label="Company">
                            <div class="td-name">{{ $tenant['name'] }}</div>
                            <div style="font-size:11.5px;color:var(--text-400)">{{ $tenant['email'] }}</div>
                        </td>
                        <td class="td-mono" style="font-size:12px" data-label="Subdomain">{{ $tenant['subdomain'] }}.crmPro.in</td>
                        <td style="font-size:12.5px;color:var(--text-200)" data-label="Plan">{{ $tenant['plan'] }}</td>
                        <td data-label="Status">
                            <span class="t-status t-{{ $tenant['status'] }}">
                                {{ ucfirst($tenant['status']) }}
                            </span>
                        </td>
                        <td class="td-mono" style="font-size:11.5px;color:var(--text-400)" data-label="Joined">{{ $tenant['joined_ago'] }}</td>
                        <td>
                            <a href="{{ route('superadmin.tenants.show', $tenant['id']) }}"
                            class="btn btn-secondary btn-sm btn-icon">
                                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/>
                                </svg>
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Expiring subscriptions --}}
    <div class="card">
        <div class="card-header">
            <div>
                <div class="card-title">Expiring Soon</div>
                <div class="card-subtitle">Subscriptions in next 7 days</div>
            </div>
            @if($stats['expiring_soon'] > 0)
            <span class="badge badge-lost">{{ $stats['expiring_soon'] }} alerts</span>
            @endif
        </div>

        @if(count($expiringSoon) > 0)
        @foreach($expiringSoon as $exp)
        <div class="alert-row">
            <div class="alert-dot-wrap">
                <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
                </svg>
            </div>
            <div style="flex:1">
                <div class="alert-title">{{ $exp['tenant_name'] }}</div>
                <div class="alert-sub">
                    {{ $exp['plan'] }} &middot; Expires {{ $exp['ends_at'] }}
                    &middot; {{ $exp['days_left'] }}d left
                </div>
            </div>
            <span class="badge badge-{{ $exp['days_left'] <= 2 ? 'lost' : 'contacted' }}">
                {{ $exp['days_left'] }}d
            </span>
        </div>
        @endforeach
        @else
        <div style="padding:32px 20px;text-align:center">
            <div style="font-size:28px;margin-bottom:8px">✅</div>
            <div style="font-size:13px;color:var(--text-300)">No subscriptions expiring in next 7 days</div>
        </div>
        @endif
    </div>

</div>

@endsection

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script>
Chart.defaults.font.family = "'Outfit', sans-serif";
Chart.defaults.color       = '#5c6380';

const months       = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
const signupsData  = @json($signupsChart);
const revenueData  = @json($revenueChart);
let   currentChart = 'signups';

const ctx  = document.getElementById('growthChart').getContext('2d');
const grad = ctx.createLinearGradient(0, 0, 0, 220);
grad.addColorStop(0, 'rgba(99,120,255,0.18)');
grad.addColorStop(1, 'rgba(99,120,255,0.00)');

const growthChart = new Chart(ctx, {
    type: 'bar',
    data: {
        labels: months,
        datasets: [{
            label: 'New Tenants',
            data: signupsData,
            backgroundColor: 'rgba(99,120,255,0.7)',
            borderRadius: 4,
            borderSkipped: false,
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: { intersect: false, mode: 'index' },
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: '#181c24',
                borderColor: 'rgba(255,255,255,0.08)',
                borderWidth: 1,
                titleColor: '#f2f4ff',
                bodyColor: '#9ca3c0',
                padding: 12,
            }
        },
        scales: {
            x: { grid: { color: 'rgba(255,255,255,0.04)', drawBorder: false }, ticks: { font: { family: "'DM Mono',monospace", size: 11 } } },
            y: { grid: { color: 'rgba(255,255,255,0.04)', drawBorder: false }, ticks: { font: { family: "'DM Mono',monospace", size: 11 }, stepSize: 1 } }
        }
    }
});

function switchChart(type, btn) {
    document.querySelectorAll('.period-tab').forEach(t => t.classList.remove('active'));
    btn.classList.add('active');
    currentChart = type;

    if (type === 'signups') {
        growthChart.data.datasets[0].label   = 'New Tenants';
        growthChart.data.datasets[0].data    = signupsData;
        growthChart.data.datasets[0].backgroundColor = 'rgba(99,120,255,0.7)';
        growthChart.options.scales.y.ticks.callback = v => v;
    } else {
        growthChart.data.datasets[0].label   = 'Revenue (₹)';
        growthChart.data.datasets[0].data    = revenueData;
        growthChart.data.datasets[0].backgroundColor = 'rgba(45,212,160,0.7)';
        growthChart.options.scales.y.ticks.callback = v =>
            v >= 100000 ? '₹' + (v/100000).toFixed(1) + 'L' : '₹' + (v/1000).toFixed(0) + 'K';
    }
    growthChart.update();
}
</script>
@endpush