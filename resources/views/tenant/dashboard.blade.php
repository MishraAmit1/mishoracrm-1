@extends('layouts.app')
@section('title', 'Dashboard')

@push('styles')
    <style>
        .grid-main {
            display: grid;
            grid-template-columns: 1fr 360px;
            gap: 16px;
            margin-bottom: 16px;
        }

        .grid-bottom {
            display: grid;
            grid-template-columns: 1fr 300px;
            gap: 16px;
            margin-bottom: 16px;
        }

        @media(max-width:1100px) {

            .grid-main,
            .grid-bottom {
                grid-template-columns: 1fr;
            }
        }

        .period-tabs {
            display: flex;
            gap: 2px;
            background: var(--bg-input);
            border-radius: var(--r-sm);
            padding: 2px;
        }

        .period-tab {
            padding: 4px 10px;
            font-size: 12px;
            font-weight: 600;
            border-radius: 4px;
            cursor: pointer;
            border: none;
            background: none;
            color: var(--text-300);
            font-family: var(--font);
            transition: all 0.15s var(--ease);
        }

        .period-tab.active {
            background: var(--bg-surface);
            color: var(--text-100);
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.2);
        }

        .quick-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
            margin-bottom: 24px;
        }

        @media(max-width:900px) {
            .quick-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        .quick-card {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 14px;
            background: var(--bg-surface);
            border: 1px solid var(--border-default);
            border-radius: var(--r-md);
            text-decoration: none;
            transition: border-color 0.15s var(--ease), transform 0.15s var(--ease);
        }

        .quick-card:hover {
            border-color: var(--accent);
            transform: translateY(-1px);
        }

        .quick-card-icon {
            width: 32px;
            height: 32px;
            border-radius: var(--r-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .quick-card-icon svg {
            width: 16px;
            height: 16px;
        }

        .quick-card-text {
            font-size: 12.5px;
            font-weight: 600;
            color: var(--text-200);
        }
    </style>
@endpush

@php
    $chartData = $chartData ?? [84000, 96000, 72000, 110000, 128000, 95000, 140000, 118000, 155000, 132000, 168000, 284500];
    $firstName = explode(' ', Auth::user()->name)[0] ?? '';
    $hour = now()->hour;
    $greeting = $hour < 12 ? 'morning' : ($hour < 17 ? 'afternoon' : 'evening');
@endphp

@section('content')

    {{-- Page header --}}
    <div class="page-head">
        <div>
            <div class="page-title">
                Good {{ $greeting }},
              {{ $firstName }}
            </div>
            <div class="page-sub">
               {{ now()->format('l, d M Y') }} — {{ Auth::user()->tenant?->name ?? 'No Tenant' }}
            </div>
        </div>
        <div class="page-actions">
            {{-- <a href="{{ route('leads.create') }}" --}} <a href="#" class="btn btn-primary">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Add Lead
            </a>
            <button class="btn btn-secondary" onclick="window.location.reload()">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
                </svg>
                Refresh
            </button>
        </div>
    </div>

    {{-- Quick actions --}}
    <div class="quick-grid">
        {{-- <a href="{{ route('leads.create') }}" --}} <a href="#" class="quick-card">
            <div class="quick-card-icon" style="background:var(--accent-dim);color:var(--accent)">
                <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M19 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zM4 19.235v-.11a6.375 6.375 0 0112.75 0v.109A12.318 12.318 0 0110.374 21c-2.331 0-4.512-.645-6.374-1.766z" />
                </svg>
            </div>
            <span class="quick-card-text">Add Lead</span>
        </a>
        {{-- <a href="{{ route('deals.create') }}" --}} <a href="#" class="quick-card">
            <div class="quick-card-icon" style="background:var(--green-dim);color:var(--green)">
                <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
            </div>
            <span class="quick-card-text">New Deal</span>
        </a>
        {{-- <a href="{{ route('tasks.create') }}" --}} <a href="#" class="quick-card">
            <div class="quick-card-icon" style="background:var(--amber-dim);color:var(--amber)">
                <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M11.35 3.836c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m8.9-4.414c.376.023.75.05 1.124.08 1.131.094 1.976 1.057 1.976 2.192V16.5A2.25 2.25 0 0118 18.75h-2.25m-7.5-10.5H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V18.75m-7.5-10.5h6.375c.621 0 1.125.504 1.125 1.125V18.75" />
                </svg>
            </div>
            <span class="quick-card-text">Create Task</span>
        </a>
        {{-- <a href="{{ route('quotations.create') }}" --}}  <a href="#"
         class="quick-card">
            <div class="quick-card-icon" style="background:var(--purple-dim);color:var(--purple)">
                <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                </svg>
            </div>
            <span class="quick-card-text">Quotation</span>
        </a>
    </div>

    {{-- Stat cards --}}
    <div class="stats-grid">
        <div class="stat-card s-blue">
            <div class="stat-top">
                <div class="stat-icon s-blue">
                    <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.75 3.75 0 11-6.75 0 3.75 3.75 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                    </svg>
                </div>
                <div class="stat-trend up">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M2.25 18L9 11.25l4.306 4.307a11.95 11.95 0 015.814-5.519l2.74-1.22m0 0l-5.94-2.28m5.94 2.28l-2.28 5.941" />
                    </svg>
                    +12%
                </div>
            </div>
            <div class="stat-num">{{ $stats['total_leads'] ?? 284 }}</div>
            <div class="stat-label">Total Leads</div>
            <div class="stat-sub">{{ $stats['new_leads_today'] ?? 8 }} new today</div>
        </div>

        <div class="stat-card s-green">
            <div class="stat-top">
                <div class="stat-icon s-green">
                    <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75" />
                    </svg>
                </div>
                <div class="stat-trend up">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M2.25 18L9 11.25l4.306 4.307a11.95 11.95 0 015.814-5.519l2.74-1.22m0 0l-5.94-2.28m5.94 2.28l-2.28 5.941" />
                    </svg>
                    +8%
                </div>
            </div>
            <div class="stat-num">₹{{ number_format(($stats['revenue_this_month'] ?? 284500) / 1000, 0) }}K</div>
            <div class="stat-label">Revenue This Month</div>
            <div class="stat-sub">{{ $stats['invoices_paid'] ?? 14 }} invoices paid</div>
        </div>

        <div class="stat-card s-amber">
            <div class="stat-top">
                <div class="stat-icon s-amber">
                    <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M3.75 3v11.25A2.25 2.25 0 006 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0118 16.5h-2.25m-7.5 0h7.5m-7.5 0l-1 3m8.5-3l1 3m0 0l.5 1.5m-.5-1.5h-9.5m0 0l-.5 1.5M9 11.25v1.5M12 9v3.75m3-6v6" />
                    </svg>
                </div>
                <div class="stat-trend down">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M2.25 6L9 12.75l4.286-4.286a11.948 11.948 0 014.306 6.43l.776 2.898m0 0l3.182-5.511m-3.182 5.51l-5.511-3.181" />
                    </svg>
                    -3%
                </div>
            </div>
            <div class="stat-num">{{ $stats['active_deals'] ?? 47 }}</div>
            <div class="stat-label">Active Deals</div>
            <div class="stat-sub">₹{{ number_format(($stats['pipeline_value'] ?? 1240000) / 100000, 1) }}L pipeline</div>
        </div>

        <div class="stat-card s-purple">
            <div class="stat-top">
                <div class="stat-icon s-purple">
                    <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="stat-trend up">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M2.25 18L9 11.25l4.306 4.307a11.95 11.95 0 015.814-5.519l2.74-1.22m0 0l-5.94-2.28m5.94 2.28l-2.28 5.941" />
                    </svg>
                    +5%
                </div>
            </div>
            <div class="stat-num">{{ $stats['tasks_completed'] ?? 31 }}</div>
            <div class="stat-label">Tasks Completed</div>
            <div class="stat-sub">{{ $stats['tasks_pending'] ?? 12 }} pending today</div>
        </div>
    </div>

    {{-- Revenue chart + Pipeline --}}
    <div class="grid-main">

        <div class="card">
            <div class="card-header">
                <div>
                    <div class="card-title">Revenue Overview</div>
                    <div class="card-subtitle">Monthly — {{ now()->year }}</div>
                </div>
                <div class="period-tabs">
                    <button class="period-tab" onclick="switchPeriod('3m',this)">3M</button>
                    <button class="period-tab" onclick="switchPeriod('6m',this)">6M</button>
                    <button class="period-tab active" onclick="switchPeriod('1y',this)">1Y</button>
                </div>
            </div>
            <div class="card-body" style="padding-bottom:12px">
                <canvas id="revenueChart" height="220" style="width:100%;display:block"></canvas>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <div>
                    <div class="card-title">Deal Pipeline</div>
                    <div class="card-subtitle">By stage this month</div>
                </div>
            </div>
            <div class="card-body">
                <div class="pipeline-list" style="margin-bottom:20px">
                    @php
                        $pipeline = $pipeline ?? [
                            ['label' => 'New', 'value' => 18, 'amount' => '₹3.2L', 'color' => 'var(--accent)', 'pct' => 80],
                            ['label' => 'Proposal', 'value' => 12, 'amount' => '₹5.8L', 'color' => 'var(--amber)', 'pct' => 55],
                            ['label' => 'Negotiation', 'value' => 9, 'amount' => '₹4.1L', 'color' => 'var(--purple)', 'pct' => 40],
                            ['label' => 'Won', 'value' => 6, 'amount' => '₹2.9L', 'color' => 'var(--green)', 'pct' => 25],
                            ['label' => 'Lost', 'value' => 4, 'amount' => '₹1.4L', 'color' => 'var(--red)', 'pct' => 18],
                        ];
                    @endphp
                    @foreach($pipeline as $p)
                        <div class="pipeline-item">
                            <div class="pipeline-label-row">
                                <span class="pipeline-name">{{ $p['label'] }}</span>
                                <span class="pipeline-val">{{ $p['value'] }} · {{ $p['amount'] }}</span>
                            </div>
                            <div class="pipeline-bar-bg">
                                <div class="pipeline-bar-fill" style="width:{{ $p['pct'] }}%;background:{{ $p['color'] }}">
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div style="padding-top:16px;border-top:1px solid var(--border-subtle)">
                    <div class="donut-wrap">
                        <svg width="80" height="80" viewBox="0 0 80 80" style="flex-shrink:0">
                            <circle cx="40" cy="40" r="30" fill="none" stroke="var(--border-subtle)" stroke-width="12" />
                            <circle cx="40" cy="40" r="30" fill="none" stroke="var(--accent)" stroke-width="12"
                                stroke-dasharray="75 189" stroke-dashoffset="0" transform="rotate(-90 40 40)" />
                            <circle cx="40" cy="40" r="30" fill="none" stroke="var(--amber)" stroke-width="12"
                                stroke-dasharray="52 189" stroke-dashoffset="-75" transform="rotate(-90 40 40)" />
                            <circle cx="40" cy="40" r="30" fill="none" stroke="var(--green)" stroke-width="12"
                                stroke-dasharray="38 189" stroke-dashoffset="-127" transform="rotate(-90 40 40)" />
                            <circle cx="40" cy="40" r="30" fill="none" stroke="var(--red)" stroke-width="12"
                                stroke-dasharray="24 189" stroke-dashoffset="-165" transform="rotate(-90 40 40)" />
                        </svg>
                        <div class="donut-legend">
                            <div class="legend-item">
                                <div class="legend-dot" style="background:var(--accent)"></div><span
                                    class="legend-label">New</span><span class="legend-val">40%</span>
                            </div>
                            <div class="legend-item">
                                <div class="legend-dot" style="background:var(--amber)"></div><span
                                    class="legend-label">Proposal</span><span class="legend-val">27%</span>
                            </div>
                            <div class="legend-item">
                                <div class="legend-dot" style="background:var(--green)"></div><span
                                    class="legend-label">Won</span><span class="legend-val">20%</span>
                            </div>
                            <div class="legend-item">
                                <div class="legend-dot" style="background:var(--red)"></div><span
                                    class="legend-label">Lost</span><span class="legend-val">13%</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    {{-- Recent leads + right col --}}
    <div class="grid-bottom">

        <div class="card">
            <div class="card-header">
                <div>
                    <div class="card-title">Recent Leads</div>
                    <div class="card-subtitle">Latest added to pipeline</div>
                </div>
                <a href="{{ route('tenant.leads.index') }}" class="btn btn-secondary btn-sm">View all →</a>
                     <a href="#" class="btn btn-secondary btn-sm">View all →</a>
            </div>
            <div style="overflow-x:auto">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Phone</th>
                            <th>Source</th>
                            <th>Status</th>
                            <th>Assigned</th>
                            <th>Added</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $recentLeads = $recentLeads ?? [
                                ['name' => 'Priya Mehta', 'phone' => '+91 98700 11234', 'source' => 'Facebook', 'status' => 'new', 'assigned' => 'Rahul S.', 'time' => '2h ago'],
                                ['name' => 'Amit Sharma', 'phone' => '+91 87654 32100', 'source' => 'Website', 'status' => 'contacted', 'assigned' => 'Neha K.', 'time' => '4h ago'],
                                ['name' => 'Sunita Patel', 'phone' => '+91 99887 76655', 'source' => 'WhatsApp', 'status' => 'qualified', 'assigned' => 'Rahul S.', 'time' => '6h ago'],
                                ['name' => 'Vikram Joshi', 'phone' => '+91 76543 21098', 'source' => 'Referral', 'status' => 'new', 'assigned' => '—', 'time' => '8h ago'],
                                ['name' => 'Deepa Gupta', 'phone' => '+91 90001 12345', 'source' => 'Instagram', 'status' => 'contacted', 'assigned' => 'Neha K.', 'time' => '1d ago'],
                                ['name' => 'Kiran Reddy', 'phone' => '+91 81234 56789', 'source' => 'Website', 'status' => 'lost', 'assigned' => 'Rahul S.', 'time' => '2d ago'],
                            ];
                        @endphp
                        @foreach($recentLeads as $lead)
                            <tr>
                                <td class="td-name">{{ $lead['name'] }}</td>
                                <td class="td-mono">{{ $lead['phone'] }}</td>
                                <td style="font-size:12px;color:var(--text-300)">{{ $lead['source'] }}</td>
                                <td><span class="badge badge-{{ $lead['status'] }}">{{ ucfirst($lead['status']) }}</span></td>
                                <td style="font-size:12.5px;color:var(--text-300)">{{ $lead['assigned'] }}</td>
                                <td class="td-mono" style="font-size:11.5px;color:var(--text-400)">{{ $lead['time'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div style="display:flex;flex-direction:column;gap:16px">

            {{-- Tasks --}}
            <div class="card">
                <div class="card-header">
                    <div>
                        <div class="card-title">Today's Tasks</div>
                        <div class="card-subtitle">{{ $stats['tasks_pending'] ?? 12 }} pending</div>
                    </div>
                    {{-- <a href="{{ route('tasks.index') }}"  --}}
                      <a href="#"
                    class="btn btn-secondary btn-sm btn-icon">
                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                        </svg>
                    </a>
                </div>
                <div class="task-list">
                    @php
                        $todayTasks = $todayTasks ?? [
                            ['text' => 'Follow up with Priya Mehta', 'due' => '10:00 AM', 'done' => false, 'priority' => 'high'],
                            ['text' => 'Send quotation to Amit Corp', 'due' => '12:00 PM', 'done' => true, 'priority' => 'medium'],
                            ['text' => 'Demo call — Sunita Patel', 'due' => '02:30 PM', 'done' => false, 'priority' => 'high'],
                            ['text' => 'Update deal stages', 'due' => '04:00 PM', 'done' => false, 'priority' => 'low'],
                            ['text' => 'Team standup meeting', 'due' => '05:00 PM', 'done' => false, 'priority' => 'medium'],
                        ];
                    @endphp
                    @foreach($todayTasks as $t)
                        <div class="task-item">
                            <div class="task-check {{ $t['done'] ? 'done' : '' }}"></div>
                            <div class="task-prio {{ $t['priority'] }}"></div>
                            <span class="task-text {{ $t['done'] ? 'done' : '' }}">{{ $t['text'] }}</span>
                            <span class="task-due">{{ $t['due'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Lead sources --}}
            <div class="card">
                <div class="card-header">
                    <div class="card-title">Lead Sources</div>
                </div>
                <div class="card-body" style="padding-top:12px">
                    <canvas id="sourceChart" height="180" style="width:100%;display:block"></canvas>
                </div>
            </div>

        </div>
    </div>

    {{-- Activity feed --}}
    <div class="card">
        <div class="card-header">
            <div>
                <div class="card-title">Recent Activity</div>
                <div class="card-subtitle">All team activity across CRM</div>
            </div>
            <button class="btn btn-secondary btn-sm">View all</button>
        </div>
        <div class="activity-list">
            @php
                $activities = $activities ?? [
                    ['type' => 'lead', 'text' => '<strong>Rahul Sharma</strong> added new lead <strong>Priya Mehta</strong> from Facebook Ads', 'time' => '2 min ago'],
                    ['type' => 'deal', 'text' => '<strong>Neha Kapoor</strong> moved deal <strong>Office Furniture 2024</strong> to Negotiation', 'time' => '18 min ago'],
                    ['type' => 'msg', 'text' => 'WhatsApp campaign sent to <strong>142 contacts</strong> — 89% delivered', 'time' => '45 min ago'],
                    ['type' => 'task', 'text' => '<strong>Amit Verma</strong> completed task <strong>Send quotation to Joshi Enterprises</strong>', 'time' => '1h ago'],
                    ['type' => 'deal', 'text' => '<strong>Rahul Sharma</strong> marked deal <strong>Laptop Procurement</strong> as Won — ₹1,24,000', 'time' => '2h ago'],
                    ['type' => 'time', 'text' => '<strong>Neha Kapoor</strong> logged a follow-up call with <strong>Sunita Patel</strong>', 'time' => '3h ago'],
                ];
                $actSvg = [
                    'lead' => '<path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.75 3.75 0 11-6.75 0 3.75 3.75 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/>',
                    'deal' => '<path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75"/>',
                    'task' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>',
                    'msg' => '<path stroke-linecap="round" stroke-linejoin="round" d="M8.625 9.75a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375m-13.5 3.01c0 1.6 1.123 2.994 2.707 3.227 1.087.16 2.185.283 3.293.369V21l4.184-4.183a1.14 1.14 0 01.778-.332 48.294 48.294 0 005.83-.498c1.585-.233 2.708-1.626 2.708-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0012 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018z"/>',
                    'time' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/>',
                ];
            @endphp
            @foreach($activities as $act)
                <div class="act-item">
                    <div class="act-dot {{ $act['type'] }}">
                        <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                            {!! $actSvg[$act['type']] !!}
                        </svg>
                    </div>
                    <div class="act-content">
                        <div class="act-text">{!! $act['text'] !!}</div>
                        <div class="act-time">{{ $act['time'] }}</div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

@endsection

@push('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
    <script>
        Chart.defaults.font.family = "'Outfit', sans-serif";
        Chart.defaults.color = '#5c6380';

        // Revenue chart
        const revCtx = document.getElementById('revenueChart').getContext('2d');
        const revData = @json($chartData);
        const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

        const grad = revCtx.createLinearGradient(0, 0, 0, 220);
        grad.addColorStop(0, 'rgba(99,120,255,0.18)');
        grad.addColorStop(1, 'rgba(99,120,255,0.00)');

        const revChart = new Chart(revCtx, {
            type: 'line',
            data: {
                labels: months,
                datasets: [{
                    label: 'Revenue',
                    data: revData,
                    borderColor: '#6378ff',
                    backgroundColor: grad,
                    borderWidth: 2,
                    pointBackgroundColor: '#6378ff',
                    pointRadius: 3,
                    pointHoverRadius: 6,
                    tension: 0.4,
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
                        backgroundColor: '#181c24',
                        borderColor: 'rgba(255,255,255,0.08)',
                        borderWidth: 1,
                        titleColor: '#f2f4ff',
                        bodyColor: '#9ca3c0',
                        padding: 12,
                        callbacks: { label: ctx => ' ₹' + ctx.raw.toLocaleString('en-IN') }
                    }
                },
                scales: {
                    x: {
                        grid: { color: 'rgba(255,255,255,0.04)', drawBorder: false },
                        ticks: { font: { family: "'DM Mono',monospace", size: 11 } }
                    },
                    y: {
                        grid: { color: 'rgba(255,255,255,0.04)', drawBorder: false },
                        ticks: {
                            font: { family: "'DM Mono',monospace", size: 11 },
                            callback: v => v >= 100000
                                ? '₹' + (v / 100000).toFixed(1) + 'L'
                                : '₹' + (v / 1000).toFixed(0) + 'K',
                        }
                    }
                }
            }
        });

        function switchPeriod(period, btn) {
            document.querySelectorAll('.period-tab').forEach(t => t.classList.remove('active'));
            btn.classList.add('active');
        }

        // Lead source doughnut
        new Chart(document.getElementById('sourceChart').getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: ['Facebook', 'Website', 'WhatsApp', 'Instagram', 'Referral'],
                datasets: [{
                    data: [35, 25, 20, 12, 8],
                    backgroundColor: ['#6378ff', '#2dd4a0', '#a78bfa', '#f8b84e', '#ff5257'],
                    borderWidth: 0,
                    hoverOffset: 4,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { color: '#9ca3c0', font: { family: "'Outfit',sans-serif", size: 11 }, padding: 10, boxWidth: 8, usePointStyle: true }
                    },
                    tooltip: {
                        backgroundColor: '#181c24', borderColor: 'rgba(255,255,255,0.08)', borderWidth: 1,
                        titleColor: '#f2f4ff', bodyColor: '#9ca3c0', padding: 10,
                        callbacks: { label: ctx => ' ' + ctx.label + ': ' + ctx.raw + '%' }
                    }
                },
                cutout: '68%',
            }
        });
    </script>
@endpush