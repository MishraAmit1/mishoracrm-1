@extends('layouts.app')
@section('title', 'Instagram Activity Logs')

@push('styles')
<style>
.log-filters { display:flex; gap:10px; flex-wrap:wrap; margin-bottom:18px; }
.log-filters select,.log-filters input { height:36px; border-radius:var(--r-sm); border:1px solid var(--border-default); background:var(--bg-surface); padding:0 12px; font-size:13px; color:var(--text-100); }
.log-table { width:100%; border-collapse:collapse; }
.log-table th,.log-table td { padding:10px 12px; text-align:left; border-bottom:1px solid var(--border-subtle); font-size:13px; }
.log-table th { font-weight:600; color:var(--text-300); font-size:11.5px; text-transform:uppercase; letter-spacing:.04em; background:var(--bg-subtle); }
.log-table tr:hover td { background:var(--bg-hover); }
.ev-badge { display:inline-flex; align-items:center; padding:2px 8px; border-radius:99px; font-size:11px; font-weight:600; }
.ev-comment { background:#fef3c7; color:#92400e; }
.ev-dm_received { background:#dbeafe; color:#1e40af; }
.ev-dm_sent { background:#d1fae5; color:#065f46; }
.ev-automation_triggered { background:#ede9fe; color:#5b21b6; }
.ev-chatbot_triggered { background:#fce7f3; color:#831843; }
.st-success { color:#16a34a; font-weight:600; }
.st-failed { color:#dc2626; font-weight:600; }
.st-skipped { color:#9ca3af; font-weight:600; }
.msg-cell { max-width:200px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
</style>
@endpush

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Activity Logs</h1>
        <p class="page-sub">All Instagram events processed by your automations</p>
    </div>
    <a href="{{ route('tenant.instagram.index') }}" class="btn btn-ghost">Back</a>
</div>

<form method="GET" class="log-filters">
    <select name="event_type" onchange="this.form.submit()">
        <option value="">All Events</option>
        <option value="comment" {{ request('event_type')==='comment'?'selected':'' }}>Comment</option>
        <option value="dm_received" {{ request('event_type')==='dm_received'?'selected':'' }}>DM Received</option>
        <option value="dm_sent" {{ request('event_type')==='dm_sent'?'selected':'' }}>DM Sent</option>
        <option value="automation_triggered" {{ request('event_type')==='automation_triggered'?'selected':'' }}>Automation Triggered</option>
        <option value="chatbot_triggered" {{ request('event_type')==='chatbot_triggered'?'selected':'' }}>Chatbot Triggered</option>
    </select>
    <select name="status" onchange="this.form.submit()">
        <option value="">All Statuses</option>
        <option value="success" {{ request('status')==='success'?'selected':'' }}>Success</option>
        <option value="failed" {{ request('status')==='failed'?'selected':'' }}>Failed</option>
        <option value="skipped" {{ request('status')==='skipped'?'selected':'' }}>Skipped</option>
    </select>
    <input type="date" name="date" value="{{ request('date') }}" onchange="this.form.submit()">
    @if(request()->hasAny(['event_type','status','date']))
        <a href="{{ route('tenant.instagram.logs') }}" class="btn btn-ghost btn-sm">Clear</a>
    @endif
</form>

<div class="card">
    @if($logs->isEmpty())
        <div style="padding:60px;text-align:center;color:var(--text-300);">No logs found for the selected filters.</div>
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
                    <th>Error</th>
                    <th>Time</th>
                </tr>
            </thead>
            <tbody>
                @foreach($logs as $log)
                <tr>
                    <td data-label="Event"><span class="ev-badge ev-{{ $log->event_type }}">{{ str_replace('_',' ',ucfirst($log->event_type)) }}</span></td>
                    <td data-label="User">
                        @if($log->instagram_username)
                            <span title="{{ $log->instagram_user_id }}">@{{ $log->instagram_username }}</span>
                        @else
                            <span style="color:var(--text-300);">{{ $log->instagram_user_id ?? '—' }}</span>
                        @endif
                    </td>
                    <td class="msg-cell" title="{{ $log->incoming_text }}" data-label="Incoming">{{ $log->incoming_text ?? '—' }}</td>
                    <td class="msg-cell" title="{{ $log->outgoing_text }}" data-label="Outgoing">{{ $log->outgoing_text ?? '—' }}</td>
                    <td data-label="Status"><span class="st-{{ $log->status }}">{{ ucfirst($log->status) }}</span></td>
                    <td class="msg-cell" style="color:var(--danger);" title="{{ $log->error_message }}" data-label="Error">{{ $log->error_message ? Str::limit($log->error_message, 40) : '—' }}</td>
                    <td style="white-space:nowrap;color:var(--text-300);font-size:12px;" data-label="Time">{{ $log->created_at->format('d M H:i') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div style="padding:16px;">
        {{ $logs->links() }}
    </div>
    @endif
</div>
@endsection
