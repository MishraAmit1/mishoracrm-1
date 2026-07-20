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
.ev-oauth_connect { background:#e0e7ff; color:#3730a3; }
.st-success { color:#16a34a; font-weight:600; }
.st-failed { color:#dc2626; font-weight:600; }
.st-skipped { color:#9ca3af; font-weight:600; }
.msg-cell { max-width:200px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.detail-btn { cursor:pointer; background:none; border:none; color:var(--accent,#6366f1); font-size:11px; text-decoration:underline; padding:0; margin-left:6px; }
.log-modal-backdrop { display:none; position:fixed; inset:0; background:rgba(0,0,0,.5); z-index:1000; align-items:center; justify-content:center; padding:20px; }
.log-modal-backdrop.open { display:flex; }
.log-modal { background:var(--bg-surface,#fff); border-radius:12px; max-width:640px; width:100%; max-height:80vh; display:flex; flex-direction:column; }
.log-modal-header { display:flex; align-items:center; justify-content:space-between; padding:14px 18px; border-bottom:1px solid var(--border-subtle); }
.log-modal-header h3 { font-size:15px; margin:0; }
.log-modal-body { padding:16px 18px; overflow:auto; }
.log-modal-body pre { white-space:pre-wrap; word-break:break-all; font-size:12px; background:var(--bg-subtle); padding:12px; border-radius:8px; }
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
        <option value="oauth_connect" {{ request('event_type')==='oauth_connect'?'selected':'' }}>Connect Attempt</option>
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
                    <td class="msg-cell" style="color:var(--danger);" title="{{ $log->error_message }}" data-label="Error">
                        {{ $log->error_message ? Str::limit($log->error_message, 40) : '—' }}
                        @if($log->raw_payload)
                            <button type="button" class="detail-btn" onclick='showLogDetail(@json($log->error_message), @json($log->raw_payload))'>view</button>
                        @endif
                    </td>
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

<div class="log-modal-backdrop" id="logModalBackdrop" onclick="if(event.target===this) closeLogDetail()">
    <div class="log-modal">
        <div class="log-modal-header">
            <h3 id="logModalTitle">Details</h3>
            <button type="button" class="btn btn-ghost btn-sm" onclick="closeLogDetail()">Close</button>
        </div>
        <div class="log-modal-body">
            <pre id="logModalBody"></pre>
        </div>
    </div>
</div>

<script>
function showLogDetail(message, payload) {
    document.getElementById('logModalTitle').textContent = message || 'Details';
    document.getElementById('logModalBody').textContent = JSON.stringify(payload, null, 2);
    document.getElementById('logModalBackdrop').classList.add('open');
}
function closeLogDetail() {
    document.getElementById('logModalBackdrop').classList.remove('open');
}
</script>
@endsection
