@extends('layouts.app')
@section('title', 'Instagram Automation')

@push('styles')
<style>
.ig-stats { display:grid; grid-template-columns:repeat(5,1fr); gap:12px; margin-bottom:20px; }
@media(max-width:900px){ .ig-stats { grid-template-columns:repeat(3,1fr); } }
@media(max-width:500px){ .ig-stats { grid-template-columns:repeat(2,1fr); } }
.ig-stat { background:var(--bg-surface); border:1px solid var(--border-default); border-radius:var(--r-md); padding:16px; text-align:center; }
.ig-stat-num { font-size:24px; font-weight:800; color:var(--text-100); font-family:var(--mono); }
.ig-stat-lbl { font-size:11.5px; color:var(--text-300); margin-top:3px; }
.ig-gradient { background:linear-gradient(135deg,#f09433,#e6683c,#dc2743,#cc2366,#bc1888); }
.quick-actions { display:grid; grid-template-columns:repeat(4,1fr); gap:12px; margin-bottom:24px; }
@media(max-width:800px){ .quick-actions { grid-template-columns:repeat(2,1fr); } }
@media(max-width:450px){ .quick-actions { grid-template-columns:1fr; } }
.qa-card { display:flex; align-items:center; gap:14px; padding:18px 20px; border-radius:var(--r-lg); border:1.5px solid var(--border-default); background:var(--bg-surface); text-decoration:none; transition:all .15s var(--ease); }
.qa-card:hover { border-color:#dc2743; transform:translateY(-1px); background:var(--bg-hover); }
.qa-icon { width:44px; height:44px; border-radius:var(--r-md); display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.qa-icon svg { width:20px; height:20px; }
.qa-label { font-size:14px; font-weight:700; color:var(--text-100); margin-bottom:2px; }
.qa-sub { font-size:12px; color:var(--text-300); }
.conn-badge { display:inline-flex; align-items:center; gap:6px; padding:4px 12px; border-radius:99px; font-size:12px; font-weight:600; }
.conn-badge.connected { background:#dcfce7; color:#16a34a; }
.conn-badge.disconnected { background:#fee2e2; color:#dc2626; }
.log-table { width:100%; border-collapse:collapse; }
.log-table th,.log-table td { padding:10px 12px; text-align:left; border-bottom:1px solid var(--border-subtle); font-size:13px; }
.log-table th { font-weight:600; color:var(--text-300); font-size:11.5px; text-transform:uppercase; letter-spacing:.04em; }
.ev-badge { display:inline-flex; align-items:center; padding:2px 8px; border-radius:99px; font-size:11px; font-weight:600; }
.ev-comment { background:#fef3c7; color:#92400e; }
.ev-dm_received { background:#dbeafe; color:#1e40af; }
.ev-dm_sent { background:#d1fae5; color:#065f46; }
.ev-automation_triggered { background:#ede9fe; color:#5b21b6; }
.ev-chatbot_triggered { background:#fce7f3; color:#831843; }
.ev-n8n_triggered { background:#f0fdf4; color:#166534; }
.st-success { color:#16a34a; }
.st-failed { color:#dc2626; }
.st-skipped { color:#9ca3af; }
</style>
@endpush

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Instagram Automation</h1>
        <p class="page-sub">Manage comment replies, DM chatbot, and n8n workflows</p>
    </div>
    <div style="display:flex;gap:8px;align-items:center;">
        @if($settings->is_connected)
            <span class="conn-badge connected">
                <span style="width:7px;height:7px;background:#16a34a;border-radius:50%;"></span> Connected
            </span>
        @else
            <span class="conn-badge disconnected">
                <span style="width:7px;height:7px;background:#dc2626;border-radius:50%;"></span> Not connected
            </span>
        @endif
        <a href="{{ route('tenant.instagram.guide') }}" class="btn btn-ghost" title="How it works">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="width:15px;height:15px;">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9 5.25h.008v.008H12v-.008z"/>
            </svg>
            Guide
        </a>
        <a href="{{ route('tenant.instagram.settings') }}" class="btn btn-primary">Settings</a>
    </div>
</div>

{{-- Stats --}}
<div class="ig-stats">
    <div class="ig-stat">
        <div class="ig-stat-num">{{ $stats['automations'] }}</div>
        <div class="ig-stat-lbl">Automations</div>
    </div>
    <div class="ig-stat">
        <div class="ig-stat-num">{{ $stats['active_auto'] }}</div>
        <div class="ig-stat-lbl">Active</div>
    </div>
    <div class="ig-stat">
        <div class="ig-stat-num">{{ $stats['chatbot_flows'] }}</div>
        <div class="ig-stat-lbl">Chatbot Flows</div>
    </div>
    <div class="ig-stat">
        <div class="ig-stat-num">{{ $stats['total_logs'] }}</div>
        <div class="ig-stat-lbl">Total Events</div>
    </div>
    <div class="ig-stat">
        <div class="ig-stat-num">{{ $stats['today_logs'] }}</div>
        <div class="ig-stat-lbl">Today</div>
    </div>
</div>

{{-- Quick actions --}}
<div class="quick-actions">
    <a href="{{ route('tenant.instagram.automations') }}" class="qa-card">
        <div class="qa-icon" style="background:#fce7f3;">
            <svg fill="none" stroke="#db2777" stroke-width="1.75" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 01.865-.501 48.172 48.172 0 003.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0012 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018z"/>
            </svg>
        </div>
        <div>
            <div class="qa-label">Automations</div>
            <div class="qa-sub">Comment → DM rules</div>
        </div>
    </a>
    <a href="{{ route('tenant.instagram.chatbot') }}" class="qa-card">
        <div class="qa-icon" style="background:#ede9fe;">
            <svg fill="none" stroke="#7c3aed" stroke-width="1.75" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 9.75a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375m-13.5 3.01c0 1.6 1.123 2.994 2.707 3.227 1.087.16 2.185.283 3.293.369V21l4.184-4.183a1.14 1.14 0 01.778-.332 48.294 48.294 0 005.83-.498c1.585-.233 2.708-1.626 2.708-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0012 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018z"/>
            </svg>
        </div>
        <div>
            <div class="qa-label">Chatbot</div>
            <div class="qa-sub">Keyword-based DM flows</div>
        </div>
    </a>
    <a href="{{ route('tenant.instagram.settings') }}" class="qa-card">
        <div class="qa-icon" style="background:#fff7ed;">
            <svg fill="none" stroke="#ea580c" stroke-width="1.75" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z"/>
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
        </div>
        <div>
            <div class="qa-label">Settings</div>
            <div class="qa-sub">API credentials & n8n</div>
        </div>
    </a>
    <a href="{{ route('tenant.instagram.logs') }}" class="qa-card">
        <div class="qa-icon" style="background:#f0f9ff;">
            <svg fill="none" stroke="#0284c7" stroke-width="1.75" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9.776c.112-.017.227-.026.344-.026h15.812c.117 0 .232.009.344.026m-16.5 0a2.25 2.25 0 00-1.883 2.542l.857 6a2.25 2.25 0 002.227 1.932H19.05a2.25 2.25 0 002.227-1.932l.857-6a2.25 2.25 0 00-1.883-2.542m-16.5 0V6A2.25 2.25 0 016 3.75h3.879a1.5 1.5 0 011.06.44l2.122 2.12a1.5 1.5 0 001.06.44H18A2.25 2.25 0 0120.25 9v.776"/>
            </svg>
        </div>
        <div>
            <div class="qa-label">Activity Logs</div>
            <div class="qa-sub">Events & errors</div>
        </div>
    </a>
</div>

{{-- Recent activity --}}
<div class="card">
    <div class="card-header" style="display:flex;justify-content:space-between;align-items:center;">
        <h3 class="card-title">Recent Activity</h3>
        <a href="{{ route('tenant.instagram.logs') }}" class="btn btn-ghost btn-sm">View all</a>
    </div>
    @if($recentLogs->isEmpty())
        <div class="empty-state" style="padding:40px;text-align:center;color:var(--text-300);">
            No activity yet. Connect your Instagram account and set up automations to get started.
        </div>
    @else
    <div style="overflow-x:auto;">
        <table class="log-table data-table">
            <thead>
                <tr>
                    <th>Event</th>
                    <th>User</th>
                    <th>Incoming</th>
                    <th>Outgoing</th>
                    <th>Status</th>
                    <th>Time</th>
                </tr>
            </thead>
            <tbody>
                @foreach($recentLogs as $log)
                <tr>
                    <td data-label="Event"><span class="ev-badge ev-{{ $log->event_type }}">{{ str_replace('_',' ',ucfirst($log->event_type)) }}</span></td>
                    <td data-label="User">{{ $log->instagram_username ?? $log->instagram_user_id ?? '—' }}</td>
                    <td style="max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" data-label="Incoming">{{ $log->incoming_text ?? '—' }}</td>
                    <td style="max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" data-label="Outgoing">{{ $log->outgoing_text ?? '—' }}</td>
                    <td data-label="Status"><span class="st-{{ $log->status }}">{{ ucfirst($log->status) }}</span></td>
                    <td style="white-space:nowrap;color:var(--text-300);font-size:12px;" data-label="Time">{{ $log->created_at->diffForHumans() }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>
@endsection
