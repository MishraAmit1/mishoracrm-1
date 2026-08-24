@extends('layouts.app')
@section('title', 'My Dashboard')

@push('styles')
<style>
@import url('https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=DM+Mono:wght@400;500&display=swap');

.sd-page { font-family: 'DM Sans', var(--font), sans-serif; }

/* ── Stats Grid ── */
.sd-stats {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 12px;
    margin-bottom: 16px;
}
@media(max-width:900px) { .sd-stats { grid-template-columns: repeat(2,1fr); } }
@media(max-width:500px) { .sd-stats { grid-template-columns: 1fr; } }

.sd-stat {
    background: var(--bg-surface);
    border: 1px solid var(--border-default);
    border-radius: 14px;
    padding: 16px 18px;
    position: relative;
    overflow: hidden;
}
.sd-stat-icon {
    width: 36px; height: 36px; border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    margin-bottom: 12px;
}
.sd-stat-num {
    font-size: 26px; font-weight: 600; color: var(--text-100);
    font-family: 'DM Mono', monospace; letter-spacing: -1px;
    line-height: 1;
}
.sd-stat-label { font-size: 11.5px; color: var(--text-300); margin-top: 4px; font-weight: 500; }
.sd-stat-sub   { font-size: 11px; color: var(--text-400); margin-top: 3px; }

.sd-stat-glow {
    position: absolute; right: -10px; bottom: -10px;
    width: 60px; height: 60px; border-radius: 50%; opacity: 0.08;
}

/* ── Layout ── */
.sd-layout {
    display: grid;
    grid-template-columns: minmax(0,1fr) 280px;
    gap: 14px;
}
@media(max-width:1000px) { .sd-layout { grid-template-columns: 1fr; } }

.sd-main    { display: flex; flex-direction: column; gap: 14px; }
.sd-sidebar { display: flex; flex-direction: column; gap: 14px; }

/* ── Cards ── */
.sd-card {
    background: var(--bg-surface);
    border: 1px solid var(--border-default);
    border-radius: 14px;
    overflow: hidden;
}
.sd-card-head {
    display: flex; align-items: center; justify-content: space-between;
    padding: 14px 18px;
    border-bottom: 1px solid var(--border-subtle);
}
.sd-card-title  { font-size: 13px; font-weight: 600; color: var(--text-100); }
.sd-card-sub    { font-size: 11.5px; color: var(--text-300); margin-top: 2px; }

/* ── Leads Table ── */
.sd-table { width: 100%; border-collapse: collapse; }
.sd-table th {
    font-size: 10.5px; font-weight: 600; color: var(--text-300);
    text-transform: uppercase; letter-spacing: 0.5px;
    padding: 10px 16px; text-align: left;
    border-bottom: 1px solid var(--border-subtle);
    background: var(--bg-elevated);
}
.sd-table td {
    padding: 10px 16px; font-size: 13px; color: var(--text-200);
    border-bottom: 1px solid var(--border-subtle);
    vertical-align: middle;
}
.sd-table tr:last-child td { border-bottom: none; }
.sd-table tr:hover td { background: var(--bg-elevated); }
.sd-td-name { font-weight: 500; color: var(--text-100); }
.sd-td-mono { font-family: 'DM Mono', monospace; font-size: 12px; }

/* ── Badges ── */
.sd-badge {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 3px 9px; border-radius: 20px;
    font-size: 11px; font-weight: 600;
}
.sd-badge-new       { background:var(--accent-dim); color:var(--accent); }
.sd-badge-contacted { background:var(--green-dim); color:var(--green); }
.sd-badge-qualified { background:var(--green-dim); color:var(--green); }
.sd-badge-converted { background:var(--green-dim); color:var(--green); }
.sd-badge-lost      { background:var(--red-dim); color:var(--red); }

/* ── Tasks ── */
.sd-task-list { display: flex; flex-direction: column; }
.sd-task {
    display: flex; align-items: center; gap: 10px;
    padding: 10px 18px;
    border-bottom: 1px solid var(--border-subtle);
}
.sd-task:last-child { border-bottom: none; }
.sd-task-check {
    width: 16px; height: 16px; border-radius: 50%;
    border: 1.5px solid var(--border-default);
    flex-shrink: 0; cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    transition: all .15s;
}
.sd-task-check.done {
    background: var(--green); border-color: var(--green);
}
.sd-task-prio { width: 4px; height: 4px; border-radius: 50%; flex-shrink: 0; }
.sd-task-prio.high   { background: var(--red); }
.sd-task-prio.medium { background: var(--amber); }
.sd-task-prio.low    { background: #97C459; }
.sd-task-text { flex: 1; font-size: 13px; color: var(--text-100); }
.sd-task-text.done { text-decoration: line-through; color: var(--text-400); }
.sd-task-due  { font-size: 11.5px; color: var(--text-300); font-family: 'DM Mono', monospace; flex-shrink: 0; }

/* ── Deal Stages ── */
.sd-deal-item {
    display: flex; align-items: center; gap: 10px;
    padding: 10px 18px;
    border-bottom: 1px solid var(--border-subtle);
}
.sd-deal-item:last-child { border-bottom: none; }
.sd-deal-dot {
    width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0;
}
.sd-deal-label { flex: 1; font-size: 13px; color: var(--text-100); font-weight: 500; }
.sd-deal-count { font-size: 13px; font-weight: 600; color: var(--text-100); font-family: 'DM Mono', monospace; }
.sd-deal-amount{ font-size: 11.5px; color: var(--text-300); margin-left: 4px; }

/* ── Quick Actions ── */
.sd-qa-grid {
    display: grid; grid-template-columns: 1fr 1fr;
    gap: 8px; padding: 14px;
}
.sd-qa-btn {
    display: flex; align-items: center; gap: 8px;
    padding: 10px 12px; border-radius: 10px;
    background: var(--bg-elevated); border: 1px solid var(--border-subtle);
    font-size: 12.5px; font-weight: 500; color: var(--text-100);
    text-decoration: none; cursor: pointer;
    font-family: 'DM Sans', var(--font), sans-serif;
    transition: all .15s; width: 100%;
}
.sd-qa-btn:hover { background: var(--bg-surface); border-color: var(--accent); color: var(--accent); }
.sd-qa-icon {
    width: 26px; height: 26px; border-radius: 7px;
    display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}

/* ── Performance Card ── */
.sd-perf-row {
    display: flex; justify-content: space-between; align-items: center;
    padding: 9px 18px; border-bottom: 1px solid var(--border-subtle);
    font-size: 13px;
}
.sd-perf-row:last-child { border-bottom: none; }
.sd-perf-key { color: var(--text-300); }
.sd-perf-val { font-weight: 600; color: var(--text-100); font-family: 'DM Mono', monospace; }

/* ── Empty State ── */
.sd-empty {
    padding: 28px 18px; text-align: center;
    color: var(--text-400); font-size: 13px; font-style: italic;
}
</style>
@endpush

@php
    $hour      = now()->hour;
    $greeting  = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
    $firstName = collect(explode(' ', $user->name))->filter()->first();

    $dealDotColors = [
        'new'         => 'var(--accent)',
        'proposal'    => 'var(--amber)',
        'negotiation' => 'var(--purple)',
        'won'         => 'var(--green)',
        'lost'        => 'var(--red)',
    ];
@endphp

@section('content')
<div class="sd-page">

    {{-- Header --}}
    <div class="page-head">
        <div>
            <div class="page-title">{{ $greeting }}, {{ $firstName }}</div>
            <div class="page-sub">{{ now()->format('l, d M Y') }} · My Dashboard</div>
        </div>
        <div style="display:flex;gap:8px">
            <a href="{{ route('tenant.leads.create') }}" class="btn btn-primary">
                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                Add Lead
            </a>
        </div>
    </div>

    {{-- Stats --}}
    <div class="sd-stats">

        {{-- My Leads --}}
        <div class="sd-stat">
            <div class="sd-stat-glow" style="background:var(--accent)"></div>
            <div class="sd-stat-icon" style="background:var(--accent-dim)">
                <svg width="18" height="18" fill="none" stroke="var(--accent)" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.75 3.75 0 11-6.75 0 3.75 3.75 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/></svg>
            </div>
            <div class="sd-stat-num">{{ $stats['my_leads_total'] }}</div>
            <div class="sd-stat-label">My Total Leads</div>
            <div class="sd-stat-sub">{{ $stats['my_leads_new'] }} new · {{ $stats['my_leads_today'] }} today</div>
        </div>

        {{-- My Active Deals --}}
        <div class="sd-stat">
            <div class="sd-stat-glow" style="background:var(--green)"></div>
            <div class="sd-stat-icon" style="background:var(--green-dim)">
                <svg width="18" height="18" fill="none" stroke="var(--green)" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75"/></svg>
            </div>
            <div class="sd-stat-num">{{ $stats['my_deals_active'] }}</div>
            <div class="sd-stat-label">Active Deals</div>
            <div class="sd-stat-sub">₹{{ number_format($stats['my_pipeline_value'] / 1000, 0) }}K pipeline</div>
        </div>

        {{-- Tasks Pending --}}
        <div class="sd-stat">
            <div class="sd-stat-glow" style="background:var(--amber)"></div>
            <div class="sd-stat-icon" style="background:var(--amber-dim)">
                <svg width="18" height="18" fill="none" stroke="var(--amber)" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <div class="sd-stat-num">{{ $stats['tasks_pending'] }}</div>
            <div class="sd-stat-label">Pending Tasks</div>
            <div class="sd-stat-sub" style="{{ $stats['tasks_overdue'] > 0 ? 'color:var(--red)' : '' }}">
                {{ $stats['tasks_overdue'] > 0 ? $stats['tasks_overdue'] . ' overdue' : 'All on track' }}
            </div>
        </div>

        {{-- Won This Month --}}
        <div class="sd-stat">
            <div class="sd-stat-glow" style="background:var(--purple)"></div>
            <div class="sd-stat-icon" style="background:var(--purple-dim)">
                <svg width="18" height="18" fill="none" stroke="var(--purple)" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 18.75h-9m9 0a3 3 0 013 3h-15a3 3 0 013-3m9 0v-3.375c0-.621-.503-1.125-1.125-1.125h-.871M7.5 18.75v-3.375c0-.621.504-1.125 1.125-1.125h.872m5.007 0H9.497m5.007 0a7.454 7.454 0 01-.982-3.172M9.497 14.25a7.454 7.454 0 00.981-3.172M5.25 4.236c-.982.143-1.954.317-2.916.52A6.003 6.003 0 007.73 9.728M5.25 4.236V4.5c0 2.108.966 3.99 2.48 5.228M5.25 4.236V2.721C7.456 2.41 9.71 2.25 12 2.25c2.291 0 4.545.16 6.75.47v1.516M7.73 9.728a6.726 6.726 0 002.748 1.35m8.272-6.842V4.5c0 2.108-.966 3.99-2.48 5.228m2.48-5.492a46.32 46.32 0 012.916.52 6.003 6.003 0 01-5.395 4.972m0 0a6.726 6.726 0 01-2.749 1.35m0 0a6.772 6.772 0 01-3.044 0"/></svg>
            </div>
            <div class="sd-stat-num">{{ $stats['my_deals_won_month'] }}</div>
            <div class="sd-stat-label">Deals Won This Month</div>
            <div class="sd-stat-sub">₹{{ number_format($stats['my_won_value_month'] / 1000, 0) }}K closed</div>
        </div>

    </div>

    <div class="sd-layout">

        {{-- ── Main Column ── --}}
        <div class="sd-main">

            {{-- My Leads --}}
            <div class="sd-card">
                <div class="sd-card-head">
                    <div>
                        <div class="sd-card-title">My Leads</div>
                        <div class="sd-card-sub">Leads assigned to you</div>
                    </div>
                    <a href="{{ route('tenant.leads.index') }}" class="btn btn-secondary btn-sm">View all →</a>
                </div>
                @if(count($myRecentLeads))
                <div style="overflow-x:auto">
                    <table class="sd-table data-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Phone</th>
                                <th>Source</th>
                                <th>Status</th>
                                <th>Added</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($myRecentLeads as $lead)
                            <tr>
                                <td class="sd-td-name" data-label="Name">{{ $lead['name'] }}</td>
                                <td class="sd-td-mono" data-label="Phone">{{ $lead['phone'] }}</td>
                                <td style="font-size:12px;color:var(--text-300)" data-label="Source">{{ ucwords(str_replace('_',' ',$lead['source'])) }}</td>
                                <td data-label="Status">
                                    <span class="sd-badge sd-badge-{{ $lead['status'] }}">
                                        {{ ucfirst($lead['status']) }}
                                    </span>
                                </td>
                                <td class="sd-td-mono" style="font-size:11.5px;color:var(--text-400)" data-label="Added">{{ $lead['time'] }}</td>
                                <td>
                                    <a href="{{ route('tenant.leads.show', $lead['id']) }}"
                                       style="font-size:12px;color:var(--accent);text-decoration:none">View →</a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="sd-empty">No leads assigned to you yet.</div>
                @endif
            </div>

            {{-- My Agenda (Tasks + Follow-ups) --}}
            <div class="sd-card">
                <div class="sd-card-head">
                    <div>
                        <div class="sd-card-title">My Agenda</div>
                        <div class="sd-card-sub">Pending tasks & today's follow-ups</div>
                    </div>
                    <a href="{{ route('tenant.calendar.index') }}" class="btn btn-secondary btn-sm">Open Calendar →</a>
                </div>
                @if(count($myTodayTasks))
                <div class="sd-task-list">
                    @foreach($myTodayTasks as $task)
                    <div class="sd-task" @if($task['url'] ?? null) onclick="window.location='{{ $task['url'] }}'" style="cursor:pointer" @endif>
                        <div class="sd-task-check {{ $task['done'] ? 'done' : '' }}">
                            @if($task['done'])
                            <svg width="9" height="9" fill="none" stroke="#fff" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            @endif
                        </div>
                        <div class="sd-task-prio {{ $task['priority'] }}"></div>
                        <span class="sd-task-text {{ $task['done'] ? 'done' : '' }}">
                            @if(($task['kind'] ?? 'task') === 'followup')<span style="font-size:9.5px;font-weight:700;color:var(--accent);border:1px solid var(--accent);border-radius:4px;padding:0 4px;margin-right:5px;text-transform:uppercase;letter-spacing:.3px">FU</span>@endif
                            {{ $task['text'] }}
                        </span>
                        <span class="sd-task-due">{{ $task['due'] }}</span>
                    </div>
                    @endforeach
                </div>
                @else
                <div class="sd-empty">No tasks for today.</div>
                @endif
            </div>

        </div>{{-- /sd-main --}}

        {{-- ── Sidebar ── --}}
        <div class="sd-sidebar">

            {{-- Quick Actions --}}
            <div class="sd-card">
                <div class="sd-card-head">
                    <div class="sd-card-title">Quick Actions</div>
                </div>
                <div class="sd-qa-grid">
                    <a href="{{ route('tenant.leads.create') }}" class="sd-qa-btn">
                        <div class="sd-qa-icon" style="background:var(--accent-dim)">
                            <svg width="13" height="13" fill="none" stroke="var(--accent)" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                        </div>
                        Add Lead
                    </a>
                    <a href="{{ route('tenant.deals.create') }}" class="sd-qa-btn">
                        <div class="sd-qa-icon" style="background:var(--green-dim)">
                            <svg width="13" height="13" fill="none" stroke="var(--green)" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                        </div>
                        New Deal
                    </a>
                    <a href="{{ route('tenant.tasks.create') }}" class="sd-qa-btn">
                        <div class="sd-qa-icon" style="background:var(--amber-dim)">
                            <svg width="13" height="13" fill="none" stroke="var(--amber)" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        Add Task
                    </a>
                    <a href="{{ route('tenant.leads.index') }}" class="sd-qa-btn">
                        <div class="sd-qa-icon" style="background:var(--purple-dim)">
                            <svg width="13" height="13" fill="none" stroke="var(--purple)" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 010 3.75H5.625a1.875 1.875 0 010-3.75z"/></svg>
                        </div>
                        All Leads
                    </a>
                </div>
            </div>

            {{-- My Deals by Stage --}}
            <div class="sd-card">
                <div class="sd-card-head">
                    <div>
                        <div class="sd-card-title">My Deal Pipeline</div>
                        <div class="sd-card-sub">By stage</div>
                    </div>
                    <a href="{{ route('tenant.deals.index') }}" class="btn btn-secondary btn-sm">View →</a>
                </div>
                @if(count($myDealsByStage))
                <div>
                    @foreach($myDealsByStage as $deal)
                    <div class="sd-deal-item">
                        <div class="sd-deal-dot" style="background:{{ $dealDotColors[$deal['stage']] ?? '#9CA3AF' }}"></div>
                        <span class="sd-deal-label">{{ $deal['label'] }}</span>
                        <span class="sd-deal-count">{{ $deal['value'] }}</span>
                        <span class="sd-deal-amount">{{ $deal['amount'] }}</span>
                    </div>
                    @endforeach
                </div>
                @else
                <div class="sd-empty">No deals yet.</div>
                @endif
            </div>

            {{-- My Performance This Month --}}
            <div class="sd-card">
                <div class="sd-card-head">
                    <div class="sd-card-title">This Month</div>
                </div>
                <div>
                    <div class="sd-perf-row">
                        <span class="sd-perf-key">Leads Added</span>
                        <span class="sd-perf-val">{{ $stats['my_leads_today'] }}</span>
                    </div>
                    <div class="sd-perf-row">
                        <span class="sd-perf-key">Leads Converted</span>
                        <span class="sd-perf-val" style="color:var(--green)">{{ $stats['my_leads_converted'] }}</span>
                    </div>
                    <div class="sd-perf-row">
                        <span class="sd-perf-key">Deals Won</span>
                        <span class="sd-perf-val" style="color:var(--purple)">{{ $stats['my_deals_won_month'] }}</span>
                    </div>
                    <div class="sd-perf-row">
                        <span class="sd-perf-key">Revenue Closed</span>
                        <span class="sd-perf-val">₹{{ number_format($stats['my_won_value_month'] / 1000, 0) }}K</span>
                    </div>
                    <div class="sd-perf-row">
                        <span class="sd-perf-key">Tasks Pending</span>
                        <span class="sd-perf-val" style="{{ $stats['tasks_overdue'] > 0 ? 'color:var(--red)' : '' }}">
                            {{ $stats['tasks_pending'] }}
                        </span>
                    </div>
                </div>
            </div>

        </div>{{-- /sd-sidebar --}}

    </div>{{-- /sd-layout --}}

</div>
@endsection
