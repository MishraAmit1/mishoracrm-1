@extends('layouts.app')
@section('title', 'Platform Overview')

@push('styles')
    @include('partials.panel-ui')
    <style>
        /* Super-admin surfaces run on a red identity accent. */
        .dsh--sa { --e: var(--red); --ew: var(--red-dim); }

        .sa-tabs { display: flex; gap: 3px; background: var(--bg-elevated); border: 1px solid var(--border-subtle); border-radius: 9px; padding: 3px; }
        .sa-tab { padding: 5px 13px; font-size: 11.5px; font-weight: 600; border-radius: 6px; cursor: pointer; border: none; background: none; color: var(--text-300); font-family: var(--font); transition: all 0.15s var(--ease); }
        .sa-tab.on { background: var(--bg-surface); color: var(--text-100); box-shadow: var(--shadow-sm); }

        /* Compact stat pair under the donut. */
        .sa-mini { display: flex; gap: 10px; margin-top: 16px; padding-top: 14px; border-top: 1px solid var(--border-subtle); }
        .sa-mini > div { flex: 1; }
        .sa-mini-n { font-size: 19px; font-weight: 700; color: var(--text-100); letter-spacing: -0.5px; font-variant-numeric: tabular-nums; }
        .sa-mini-l { font-size: 11px; color: var(--text-300); margin-top: 2px; }

        .sa-chart { height: 232px; position: relative; }
        .sa-chart canvas { width: 100% !important; height: 100% !important; }
    </style>
@endpush

@php
    $icons = [
        'building' => 'M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21',
        'cash'     => 'M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75',
        'users'    => 'M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.75 3.75 0 11-6.75 0 3.75 3.75 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z',
        'clock'    => 'M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z',
        'warn'     => 'M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z',
        'arrow'    => 'M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3',
        'list'     => 'M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 010 3.75H5.625a1.875 1.875 0 010-3.75z',
        'tag'      => 'M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3z M6 6h.008v.008H6V6z',
        'dots'     => 'M12 13.5a1.5 1.5 0 100-3 1.5 1.5 0 000 3zM6 13.5a1.5 1.5 0 100-3 1.5 1.5 0 000 3zM18 13.5a1.5 1.5 0 100-3 1.5 1.5 0 000 3z',
    ];

    $statusPill = fn($s) => match ($s) {
        'active'    => 'on',
        'inactive'  => 'warn',
        'suspended' => 'off',
        default     => 'muted',
    };

    $sTotal = max($stats['total_tenants'], 1);

    $planActiveTotal = collect($planBreakdown)->sum('active_count');
    $planTrialTotal  = collect($planBreakdown)->sum('trial_count');
    $planMrrTotal    = collect($planBreakdown)->sum('mrr');
@endphp

@section('content')

<div class="dsh dsh--sa">

    {{-- ── Header ─────────────────────────────────────────────────── --}}
    <div class="dsh-head">
        <div>
            <div class="dsh-eyebrow">Super Admin · {{ now()->format('l, d M Y') }}</div>
            <div class="dsh-title">Platform Overview</div>
            <div class="dsh-sub">Tenants, subscriptions and revenue across Mishora CRM.</div>
        </div>
        <div class="dsh-acts">
            <a href="{{ route('superadmin.plans.index') }}" class="dbtn">
                <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icons['tag'] }}"/></svg>
                Manage Plans
            </a>
            <a href="{{ route('superadmin.tenants.index') }}" class="dbtn dbtn-accent">
                <svg fill="none" stroke="currentColor" stroke-width="1.9" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icons['list'] }}"/></svg>
                All Tenants
            </a>
        </div>
    </div>

    {{-- ── KPI row ────────────────────────────────────────────────── --}}
    <div class="kgrid">

        <div class="kcard" style="--k:#6378ff;--kw:rgba(99,120,255,.12);--kb:rgba(99,120,255,.4)">
            <div class="khead">
                <span class="kico"><svg fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icons['building'] }}"/></svg></span>
                <span class="klabel">Tenants</span>
                <a href="{{ route('superadmin.tenants.index') }}" class="kdots"><svg fill="currentColor" viewBox="0 0 24 24"><path d="{{ $icons['dots'] }}"/></svg></a>
            </div>
            <div class="kmid">
                <span class="knum">{{ number_format($stats['total_tenants']) }}</span>
                <div class="kspark"><canvas id="spTenants"></canvas></div>
            </div>
            <div class="kfoot">
                @if($stats['new_this_month'] > 0)
                    <span class="kdelta up">+{{ $stats['new_this_month'] }}</span>
                @endif
                <span class="knote">{{ $stats['active_tenants'] }} active · {{ $stats['new_this_month'] }} new this month</span>
            </div>
        </div>

        <div class="kcard" style="--k:#2dd4a0;--kw:rgba(45,212,160,.12);--kb:rgba(45,212,160,.4)">
            <div class="khead">
                <span class="kico"><svg fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icons['cash'] }}"/></svg></span>
                <span class="klabel">Monthly Revenue</span>
            </div>
            <div class="kmid">
                <span class="knum">₹{{ $stats['total_mrr'] >= 100000 ? number_format($stats['total_mrr'] / 100000, 2) . 'L' : number_format($stats['total_mrr']) }}</span>
                <div class="kspark"><canvas id="spRevenue"></canvas></div>
            </div>
            <div class="kfoot">
                <span class="knote">{{ $stats['active_subs'] }} paid · ₹{{ number_format($stats['total_mrr'] * 12) }} ARR</span>
            </div>
        </div>

        <div class="kcard" style="--k:#a78bfa;--kw:rgba(167,139,250,.12);--kb:rgba(167,139,250,.4)">
            <div class="khead">
                <span class="kico"><svg fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icons['users'] }}"/></svg></span>
                <span class="klabel">Users</span>
            </div>
            <div class="kmid">
                <span class="knum">{{ number_format($stats['total_users']) }}</span>
            </div>
            <div class="kfoot">
                <span class="knote">{{ $stats['active_users'] }} active across all tenants</span>
            </div>
        </div>

        <div class="kcard" style="--k:#f8b84e;--kw:rgba(248,184,78,.14);--kb:rgba(248,184,78,.4)">
            <div class="khead">
                <span class="kico"><svg fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icons['clock'] }}"/></svg></span>
                <span class="klabel">Trials</span>
            </div>
            <div class="kmid">
                <span class="knum">{{ number_format($stats['trial_subs']) }}</span>
            </div>
            <div class="kfoot">
                @if($stats['expiring_soon'] > 0)
                    <span class="kdelta down">
                        <svg fill="none" stroke="currentColor" stroke-width="2.25" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icons['warn'] }}"/></svg>
                        {{ $stats['expiring_soon'] }}
                    </span>
                    <span class="knote">subscriptions expiring in ≤ 7 days</span>
                @else
                    <span class="knote">no subscriptions expiring soon</span>
                @endif
            </div>
        </div>

    </div>

    {{-- ── Detail grid ─────────────────────────── --}}
    <div class="dgrid-side" style="grid-template-columns:minmax(0,1fr) 340px">

        <div class="dstack">

            <div class="dcard">
                <div class="dcard-h">
                    <div>
                        <div class="dcard-t">Growth</div>
                        <div class="dcard-s">Monthly signups &amp; revenue — {{ now()->year }}</div>
                    </div>
                    <div class="sa-tabs">
                        <button class="sa-tab on" onclick="switchChart('signups', this)">Signups</button>
                        <button class="sa-tab" onclick="switchChart('revenue', this)">Revenue</button>
                    </div>
                </div>
                <div class="dcard-b">
                    <div class="sa-chart"><canvas id="growthChart"></canvas></div>
                </div>
            </div>

            <div class="dcard">
                <div class="dcard-h">
                    <div>
                        <div class="dcard-t">Recent Tenants</div>
                        <div class="dcard-s">Newest {{ count($recentTenants) }} signup{{ count($recentTenants) !== 1 ? 's' : '' }}</div>
                    </div>
                    <a href="{{ route('superadmin.tenants.index') }}" class="dbtn dbtn-sm">View all →</a>
                </div>
                <div style="overflow-x:auto">
                    <table class="rtable">
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
                            @forelse($recentTenants as $t)
                            <tr>
                                <td data-label="Company">
                                    <div class="rname">
                                        <span class="rav rav-sq" style="background:#6378ff">{{ strtoupper(substr($t['name'], 0, 2)) }}</span>
                                        <span>
                                            <span class="rn">{{ $t['name'] }}</span>
                                            <div class="rsub">{{ $t['email'] }}</div>
                                        </span>
                                    </div>
                                </td>
                                <td data-label="Subdomain" class="rmono">{{ $t['subdomain'] }}</td>
                                <td data-label="Plan" style="color:var(--text-100);font-weight:600">{{ $t['plan'] }}</td>
                                <td data-label="Status"><span class="dpill {{ $statusPill($t['status']) }}">{{ ucfirst($t['status']) }}</span></td>
                                <td data-label="Joined" class="rmono">{{ $t['joined_ago'] }}</td>
                                <td>
                                    <a href="{{ route('superadmin.tenants.show', $t['id']) }}" class="icobtn">
                                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icons['arrow'] }}"/></svg>
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="6" class="dempty">No tenants yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

        <div class="dstack">

            <div class="dcard">
                <div class="dcard-h">
                    <div>
                        <div class="dcard-t">Tenant Status</div>
                        <div class="dcard-s">{{ $stats['total_tenants'] }} tenant{{ $stats['total_tenants'] !== 1 ? 's' : '' }} total</div>
                    </div>
                </div>
                <div class="dcard-b">
                    <div class="ddonut">
                        @php
                            $circ = 201.06; // 2πr, r = 32
                            $segments = [
                                ['val' => $stats['active_tenants'],   'color' => 'var(--green)'],
                                ['val' => $stats['inactive_tenants'], 'color' => 'var(--amber)'],
                                ['val' => $stats['suspended'],        'color' => 'var(--red)'],
                            ];
                            $acc = 0;
                        @endphp
                        <svg width="86" height="86" viewBox="0 0 88 88" style="flex-shrink:0">
                            <circle cx="44" cy="44" r="32" fill="none" stroke="var(--border-subtle)" stroke-width="11"/>
                            @foreach($segments as $seg)
                                @php $len = $seg['val'] / $sTotal * $circ; @endphp
                                @if($len > 0)
                                    <circle cx="44" cy="44" r="32" fill="none" stroke="{{ $seg['color'] }}" stroke-width="11"
                                        stroke-dasharray="{{ $len }} {{ $circ }}" stroke-dashoffset="{{ -$acc }}"
                                        transform="rotate(-90 44 44)"/>
                                @endif
                                @php $acc += $len; @endphp
                            @endforeach
                        </svg>
                        <div class="dlegend">
                            <div class="dlegend-item"><span class="dlegend-dot" style="background:var(--green)"></span><span class="dlegend-l">Active</span><span class="dlegend-v">{{ $stats['active_tenants'] }}</span></div>
                            <div class="dlegend-item"><span class="dlegend-dot" style="background:var(--amber)"></span><span class="dlegend-l">Inactive</span><span class="dlegend-v">{{ $stats['inactive_tenants'] }}</span></div>
                            <div class="dlegend-item"><span class="dlegend-dot" style="background:var(--red)"></span><span class="dlegend-l">Suspended</span><span class="dlegend-v">{{ $stats['suspended'] }}</span></div>
                        </div>
                    </div>
                    <div class="sa-mini">
                        <div>
                            <div class="sa-mini-n">{{ $stats['new_this_month'] }}</div>
                            <div class="sa-mini-l">New this month</div>
                        </div>
                        <div>
                            <div class="sa-mini-n">{{ $stats['new_today'] }}</div>
                            <div class="sa-mini-l">New today</div>
                        </div>
                        <div>
                            <div class="sa-mini-n">{{ $stats['expired_subs'] }}</div>
                            <div class="sa-mini-l">Expired subs</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="dcard">
                <div class="dcard-h">
                    <div>
                        <div class="dcard-t">Plans</div>
                        <div class="dcard-s">Active subscriptions by plan</div>
                    </div>
                    <a href="{{ route('superadmin.plans.index') }}" class="icobtn">
                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icons['arrow'] }}"/></svg>
                    </a>
                </div>
                <div style="overflow-x:auto">
                    <table class="rtable compact">
                        <thead>
                            <tr>
                                <th>Plan</th>
                                <th class="num">Active</th>
                                <th class="num">Trial</th>
                                <th class="num">MRR</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($planBreakdown as $plan)
                            <tr>
                                <td data-label="Plan">
                                    <span class="rn">{{ $plan['name'] }}</span>
                                    <div class="rsub">₹{{ number_format($plan['price']) }}/mo</div>
                                </td>
                                <td data-label="Active" class="num" style="color:var(--text-100);font-weight:600">{{ $plan['active_count'] }}</td>
                                <td data-label="Trial" class="num">{{ $plan['trial_count'] }}</td>
                                <td data-label="MRR" class="num" style="color:{{ $plan['mrr'] > 0 ? 'var(--green)' : 'var(--text-400)' }};font-weight:600">{{ $plan['mrr'] > 0 ? '₹' . number_format($plan['mrr']) : '—' }}</td>
                            </tr>
                            @empty
                            <tr><td colspan="4" class="dempty">No plans configured.</td></tr>
                            @endforelse
                        </tbody>
                        @if(count($planBreakdown) > 0)
                        <tfoot>
                            <tr>
                                <td>Total</td>
                                <td class="num">{{ $planActiveTotal }}</td>
                                <td class="num">{{ $planTrialTotal }}</td>
                                <td class="num" style="color:var(--green)">₹{{ number_format($planMrrTotal) }}</td>
                            </tr>
                        </tfoot>
                        @endif
                    </table>
                </div>
            </div>

        </div>

    </div>

    {{-- ── Expiring soon (only when there is something) ──────────── --}}
    @if(count($expiringSoon) > 0)
    <div class="dcard" style="margin-top:12px">
        <div class="dcard-h">
            <div class="dcard-ht">
                <span class="dcard-ico" style="--c:var(--amber);--cw:var(--amber-dim)"><svg fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icons['warn'] }}"/></svg></span>
                <div>
                    <div class="dcard-t">Expiring Soon</div>
                    <div class="dcard-s">Active subscriptions ending in the next 7 days</div>
                </div>
            </div>
            <span class="dpill off">{{ $stats['expiring_soon'] }}</span>
        </div>
        <div class="dcard-b tight">
            @foreach($expiringSoon as $exp)
            <div class="dlist-row" style="--li:var(--{{ $exp['days_left'] <= 2 ? 'red' : 'amber' }});--liw:var(--{{ $exp['days_left'] <= 2 ? 'red' : 'amber' }}-dim)">
                <span class="dlist-ico"><svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icons['warn'] }}"/></svg></span>
                <div class="dlist-main">
                    <div class="dlist-t">{{ $exp['tenant_name'] }}</div>
                    <div class="dlist-s">{{ $exp['plan'] }} · expires {{ $exp['ends_at'] }}</div>
                </div>
                <span class="dpill {{ $exp['days_left'] <= 2 ? 'off' : 'warn' }}">{{ $exp['days_left'] }}d left</span>
            </div>
            @endforeach
        </div>
    </div>
    @endif

</div>

@endsection

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script>
    Chart.defaults.font.family = "'Outfit', sans-serif";

    function resolveColor(c) {
        const p = document.createElement('span');
        p.style.color = c; document.body.appendChild(p);
        const v = getComputedStyle(p).color; p.remove(); return v || c;
    }
    function cssVar(n, f) { return getComputedStyle(document.documentElement).getPropertyValue(n).trim() || f; }
    function toRgba(c, a) { const m = resolveColor(c).match(/\d+(\.\d+)?/g); return m ? `rgba(${m[0]},${m[1]},${m[2]},${a})` : c; }

    const C_TEXT  = resolveColor(cssVar('--text-100', '#0d0f1a'));
    const C_MUTE  = resolveColor(cssVar('--text-300', '#7b84a8'));
    const C_SURF  = resolveColor(cssVar('--bg-surface', '#fff'));
    const C_LINE  = toRgba(cssVar('--text-400', '#b0b8d4'), 0.28);
    const C_ACC   = resolveColor(cssVar('--accent', '#6378ff'));
    const C_GREEN = resolveColor(cssVar('--green', '#2dd4a0'));
    Chart.defaults.color = C_MUTE;

    const months      = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    const signupsData = @json($signupsChart);
    const revenueData = @json($revenueChart);

    /* ── KPI mini sparklines (faint track behind the real series) ── */
    function miniSpark(id, series, color) {
        const el = document.getElementById(id);
        if (!el) return;
        const s = series.map(Number);
        const peak = Math.max.apply(null, s.concat([0]));
        new Chart(el.getContext('2d'), {
            type: 'bar',
            data: {
                labels: s.map((_, i) => i),
                datasets: [
                    { data: s.map(() => (peak > 0 ? peak : 1)), backgroundColor: toRgba(color, 0.12), borderRadius: 3, barPercentage: 0.72, categoryPercentage: 0.9, grouped: false },
                    { data: s, backgroundColor: toRgba(color, 0.5), borderRadius: 3, barPercentage: 0.72, categoryPercentage: 0.9, grouped: false },
                ]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { display: false }, tooltip: { enabled: false } },
                scales: { x: { display: false }, y: { display: false, beginAtZero: true } },
                animation: { duration: 450 },
            }
        });
    }
    miniSpark('spTenants', signupsData, C_ACC);
    miniSpark('spRevenue', revenueData, C_GREEN);

    /* ── Growth chart — soft area line ────────────────────────────── */
    const gctx = document.getElementById('growthChart').getContext('2d');
    const gFill = gctx.createLinearGradient(0, 0, 0, 232);
    gFill.addColorStop(0, toRgba(C_ACC, 0.2));
    gFill.addColorStop(1, toRgba(C_ACC, 0));

    const growthChart = new Chart(gctx, {
        type: 'line',
        data: {
            labels: months,
            datasets: [{
                data: signupsData,
                borderColor: C_ACC,
                backgroundColor: gFill,
                borderWidth: 2.5,
                pointRadius: 0,
                pointHoverRadius: 5,
                pointHoverBackgroundColor: C_ACC,
                tension: 0.3,
                fill: true,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { intersect: false, mode: 'index' },
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: C_TEXT, titleColor: C_SURF, bodyColor: C_SURF,
                    padding: 10, displayColors: false, cornerRadius: 8,
                },
            },
            scales: {
                x: { grid: { display: false }, border: { display: false }, ticks: { font: { size: 11 } } },
                y: {
                    grid: { color: C_LINE, drawTicks: false },
                    border: { display: false },
                    ticks: { font: { size: 10.5 }, precision: 0, maxTicksLimit: 5, padding: 8 },
                    beginAtZero: true,
                },
            }
        }
    });

    function switchChart(type, btn) {
        document.querySelectorAll('.sa-tab').forEach(t => t.classList.remove('on'));
        btn.classList.add('on');
        const isRevenue = type === 'revenue';
        const color = isRevenue ? C_GREEN : C_ACC;
        const fill = gctx.createLinearGradient(0, 0, 0, 232);
        fill.addColorStop(0, toRgba(color, 0.22));
        fill.addColorStop(1, toRgba(color, 0));

        growthChart.data.datasets[0].data = isRevenue ? revenueData : signupsData;
        growthChart.data.datasets[0].borderColor = color;
        growthChart.data.datasets[0].backgroundColor = fill;
        growthChart.data.datasets[0].pointHoverBackgroundColor = color;
        growthChart.options.scales.y.ticks.callback = isRevenue
            ? (v => v >= 100000 ? '₹' + (v / 100000).toFixed(1) + 'L' : (v >= 1000 ? '₹' + (v / 1000).toFixed(0) + 'K' : '₹' + v))
            : (v => v);
        growthChart.options.plugins.tooltip.callbacks = {
            label: ctx => isRevenue ? '₹' + Number(ctx.raw).toLocaleString('en-IN') : ctx.raw + ' signup' + (ctx.raw === 1 ? '' : 's'),
        };
        growthChart.update();
    }
</script>
@endpush
