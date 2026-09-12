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

/* =========================================================
   MishoraCRM — Mobile Responsive UI Enhancement
   Keeps desktop layout intact; optimizes tablet + phone.
   ========================================================= */
@media (max-width: 1000px) {
    .sd-layout {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    .sd-page {
        width: 100%;
        max-width: 100%;
        min-width: 0;
        overflow-x: hidden;
    }

    /* Header */
    .sd-page .page-head {
        display: flex !important;
        flex-direction: column !important;
        align-items: stretch !important;
        gap: 12px !important;
        margin-bottom: 16px !important;
    }

    .sd-page .page-head > div:first-child {
        min-width: 0;
    }

    .sd-page .page-title {
        font-size: clamp(20px, 6vw, 25px) !important;
        line-height: 1.2 !important;
        word-break: break-word;
    }

    .sd-page .page-sub {
        font-size: 11px !important;
        line-height: 1.45;
    }

    .sd-page .page-head > div:last-child {
        width: 100%;
    }

    .sd-page .page-head .btn {
        width: 100% !important;
        min-height: 44px;
        justify-content: center;
    }

    /* KPI cards: 2 columns on normal phones */
    .sd-stats {
        grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
        gap: 9px !important;
        margin-bottom: 12px !important;
    }

    .sd-stat {
        min-width: 0;
        padding: 13px 12px !important;
        border-radius: 12px !important;
    }

    .sd-stat-icon {
        width: 32px !important;
        height: 32px !important;
        margin-bottom: 9px !important;
    }

    .sd-stat-icon svg {
        width: 16px;
        height: 16px;
    }

    .sd-stat-num {
        font-size: clamp(20px, 7vw, 25px) !important;
        letter-spacing: -0.8px;
        white-space: nowrap;
    }

    .sd-stat-label {
        font-size: 10.5px !important;
        line-height: 1.25;
        min-height: 26px;
    }

    .sd-stat-sub {
        font-size: 9.5px !important;
        line-height: 1.25;
        white-space: normal;
    }

    /* Main/sidebar become one clean mobile flow */
    .sd-layout {
        display: flex !important;
        flex-direction: column !important;
        gap: 12px !important;
        width: 100%;
    }

    .sd-main,
    .sd-sidebar {
        width: 100%;
        min-width: 0;
        gap: 12px !important;
    }

    .sd-card {
        width: 100%;
        min-width: 0;
        border-radius: 12px !important;
    }

    .sd-card-head {
        min-height: 52px;
        padding: 12px 13px !important;
        gap: 8px;
    }

    .sd-card-head > div:first-child {
        min-width: 0;
    }

    .sd-card-title {
        font-size: 13px !important;
    }

    .sd-card-sub {
        font-size: 10.5px !important;
        line-height: 1.35;
    }

    .sd-card-head .btn {
        flex: 0 0 auto;
        min-height: 38px;
        white-space: nowrap;
    }

    /* Leads: turn wide table into compact cards */
    .sd-table {
        display: block;
        width: 100%;
    }

    .sd-table thead {
        display: none;
    }

    .sd-table tbody,
    .sd-table tr {
        display: block;
        width: 100%;
    }

    .sd-table tr {
        position: relative;
        padding: 12px 13px 11px;
        border-bottom: 1px solid var(--border-subtle);
        background: var(--bg-surface);
    }

    .sd-table tr:last-child {
        border-bottom: none;
    }

    .sd-table td {
        display: flex !important;
        align-items: baseline;
        justify-content: space-between;
        gap: 12px;
        width: 100%;
        padding: 3px 0 !important;
        border: 0 !important;
        font-size: 11.5px !important;
        line-height: 1.45;
        text-align: right;
        min-width: 0;
    }

    .sd-table td[data-label]::before {
        content: attr(data-label);
        flex: 0 0 auto;
        color: var(--text-400);
        font-size: 9.5px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: .45px;
        text-align: left;
    }

    .sd-table td.sd-td-name {
        display: block !important;
        padding: 0 0 6px !important;
        padding-right: 52px !important;
        font-size: 14px !important;
        text-align: left !important;
        font-weight: 600 !important;
    }

    .sd-table td.sd-td-name::before {
        display: none;
    }

    .sd-table td:last-child {
        position: absolute;
        top: 12px;
        right: 13px;
        width: auto !important;
        padding: 0 !important;
    }

    .sd-table td:last-child::before {
        display: none;
    }

    .sd-table td:last-child a {
        display: inline-flex;
        align-items: center;
        min-height: 34px;
        padding: 6px 8px;
        border-radius: 8px;
        background: var(--accent-dim);
        color: var(--accent) !important;
        font-weight: 600;
    }

    .sd-badge {
        font-size: 10px !important;
        padding: 4px 8px !important;
    }

    /* Remove table wrapper horizontal scrolling on mobile */
    .sd-card > div[style*="overflow-x:auto"] {
        overflow-x: visible !important;
    }

    /* Agenda / task rows */
    .sd-task {
        min-height: 52px;
        padding: 10px 13px !important;
        gap: 8px !important;
        align-items: flex-start !important;
    }

    .sd-task-check {
        width: 20px !important;
        height: 20px !important;
        margin-top: 1px;
    }

    .sd-task-prio {
        margin-top: 8px;
    }

    .sd-task-text {
        min-width: 0;
        font-size: 12px !important;
        line-height: 1.4;
        overflow-wrap: anywhere;
    }

    .sd-task-due {
        font-size: 10px !important;
        white-space: nowrap;
        padding-top: 2px;
    }

    /* Quick actions: comfortable 2x2 grid */
    .sd-qa-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
        gap: 8px !important;
        padding: 11px !important;
    }

    .sd-qa-btn {
        min-height: 48px;
        padding: 9px !important;
        font-size: 11.5px !important;
        gap: 7px !important;
        min-width: 0;
    }

    .sd-qa-icon {
        width: 28px !important;
        height: 28px !important;
    }

    /* Pipeline rows */
    .sd-deal-item {
        min-height: 48px;
        padding: 10px 13px !important;
        gap: 8px !important;
    }

    .sd-deal-label {
        min-width: 0;
        font-size: 12px !important;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .sd-deal-count {
        font-size: 12px !important;
        white-space: nowrap;
    }

    .sd-deal-amount {
        font-size: 10px !important;
        white-space: nowrap;
    }

    /* Performance rows */
    .sd-perf-row {
        min-height: 44px;
        padding: 9px 13px !important;
        gap: 12px;
        font-size: 12px !important;
    }

    .sd-perf-key {
        min-width: 0;
    }

    .sd-perf-val {
        white-space: nowrap;
        font-size: 11.5px !important;
    }

    .sd-empty {
        padding: 24px 13px !important;
        font-size: 12px !important;
    }

    /* Make links/buttons touch-friendly */
    .sd-page a,
    .sd-page button {
        -webkit-tap-highlight-color: transparent;
    }

    /* Prevent long values from breaking the viewport */
    .sd-page img,
    .sd-page svg,
    .sd-page input,
    .sd-page select,
    .sd-page textarea {
        max-width: 100%;
    }
}

/* Very small Android phones */
@media (max-width: 380px) {
    .sd-stats {
        gap: 7px !important;
    }

    .sd-stat {
        padding: 11px 10px !important;
    }

    .sd-stat-num {
        font-size: 19px !important;
    }

    .sd-stat-label {
        font-size: 10px !important;
    }

    .sd-stat-sub {
        font-size: 9px !important;
    }

    .sd-card-head {
        padding: 10px 11px !important;
    }

    .sd-card-head .btn {
        font-size: 10px !important;
        padding-left: 8px !important;
        padding-right: 8px !important;
    }

    .sd-task-due {
        font-size: 9px !important;
    }

    .sd-deal-amount {
        display: none;
    }

    .sd-qa-btn {
        font-size: 10.5px !important;
    }
}

/* Extra narrow screens: single-column stats rather than cramped cards */
@media (max-width: 320px) {
    .sd-stats {
        grid-template-columns: 1fr !important;
    }

    .sd-stat-label {
        min-height: 0;
    }
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