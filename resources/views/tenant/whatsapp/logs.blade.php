@extends('layouts.app')
@section('title', 'WhatsApp Logs')

@push('styles')
<style>
.filter-bar { display:flex; align-items:center; gap:10px; flex-wrap:wrap; margin-bottom:20px; }
.filter-input { padding:8px 12px; height:36px; background:var(--bg-input); border:1.5px solid var(--border-default); border-radius:var(--r-sm); color:var(--text-100); font-family:var(--font); font-size:13px; outline:none; }
.filter-input:focus { border-color:var(--accent); }
.pag-wrap { display:flex; align-items:center; justify-content:space-between; padding:14px 20px; border-top:1px solid var(--border-subtle); font-size:13px; color:var(--text-300); }
.pag-links { display:flex; gap:4px; }
.pg-btn { padding:5px 10px; border-radius:var(--r-sm); border:1px solid var(--border-default); color:var(--text-200); text-decoration:none; font-size:13px; transition:all .15s; }
.pg-btn:hover { border-color:var(--accent); color:var(--accent); }
.pg-btn.active { background:var(--accent); border-color:var(--accent); color:#fff; }
.pg-btn.disabled { opacity:.4; pointer-events:none; }
.err-view-btn { background:none; border:none; color:var(--accent); font-size:11px; text-decoration:underline; padding:0; margin-left:6px; cursor:pointer; }
.log-modal-backdrop { display:none; position:fixed; inset:0; background:rgba(0,0,0,.5); z-index:1000; align-items:center; justify-content:center; padding:20px; }
.log-modal-backdrop.open { display:flex; }
.log-modal { background:var(--bg-surface); border-radius:12px; max-width:560px; width:100%; max-height:80vh; display:flex; flex-direction:column; }
.log-modal-header { display:flex; align-items:center; justify-content:space-between; padding:14px 18px; border-bottom:1px solid var(--border-subtle); }
.log-modal-header h3 { font-size:15px; margin:0; color:var(--red); }
.log-modal-body { padding:16px 18px; overflow:auto; font-size:13.5px; color:var(--text-100); line-height:1.5; white-space:pre-wrap; word-break:break-word; }
</style>
@endpush

@section('content')

<div class="page-head">
    <div>
        <div style="font-size:13px;color:var(--text-300);margin-bottom:4px">
            <a href="{{ route('tenant.whatsapp.index') }}" style="color:var(--text-300);text-decoration:none">WhatsApp</a>
            <span style="margin:0 6px">›</span> Logs
        </div>
        <div class="page-title">Message Logs</div>
    </div>
    <a href="{{ route('tenant.whatsapp.send') }}" class="btn btn-primary">Send Message</a>
</div>

{{-- Filters --}}
<form method="GET" action="{{ route('tenant.whatsapp.logs') }}">
    <div class="filter-bar">
        <select name="status" class="filter-input" onchange="this.form.submit()">
            <option value="">All Status</option>
            <option value="sent"    {{ request('status')==='sent'    ? 'selected':'' }}>Sent</option>
            <option value="failed"  {{ request('status')==='failed'  ? 'selected':'' }}>Failed</option>
            <option value="pending" {{ request('status')==='pending' ? 'selected':'' }}>Pending</option>
        </select>
        <select name="is_bulk" class="filter-input" onchange="this.form.submit()">
            <option value="">All Types</option>
            <option value="0" {{ request('is_bulk')==='0' ? 'selected':'' }}>Single</option>
            <option value="1" {{ request('is_bulk')==='1' ? 'selected':'' }}>Bulk</option>
        </select>
        <input type="date" name="date" class="filter-input"
               value="{{ request('date') }}" onchange="this.form.submit()"/>
        @if(request()->hasAny(['status','is_bulk','date']))
        <a href="{{ route('tenant.whatsapp.logs') }}" class="btn btn-secondary">Clear</a>
        @endif
    </div>
</form>

<div class="card">
    @if($logs->isEmpty())
    <div style="padding:60px 20px;text-align:center;color:var(--text-300);font-size:13px">
        No messages found
    </div>
    @else
    <div style="overflow-x:auto">
        <table class="data-table">
            <thead>
                <tr>
                    <th>To</th>
                    <th>Message</th>
                    <th>Template</th>
                    <th>Type</th>
                    <th>Sent By</th>
                    <th>Time</th>
                    <th>Status</th>
                    <th>Error</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($logs as $log)
                @php
                    $sc = match($log->status) {
                        'sent'    => ['green', 'Sent'],
                        'failed'  => ['red',   'Failed'],
                        'pending' => ['amber',  'Pending'],
                        default   => ['text-300', ucfirst($log->status)],
                    };
                @endphp
                <tr>
                    <td data-label="To">
                        <div style="font-weight:600;font-size:13.5px">{{ $log->to_name ?? '—' }}</div>
                        <div style="font-size:11.5px;color:var(--text-400);font-family:var(--mono)">{{ $log->to_phone }}</div>
                    </td>
                    <td style="max-width:200px" data-label="Message">
                        <div style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:12.5px;color:var(--text-200)">
                            {{ $log->message }}
                            @if($log->attachment_name)
                            <span title="{{ $log->attachment_name }} ({{ $log->media_type }})" style="margin-left:4px">📎</span>
                            @endif
                        </div>
                    </td>
                    <td style="font-size:12px;color:var(--text-300)" data-label="Template">{{ $log->template?->name ?? '—' }}</td>
                    <td data-label="Type">
                        <span style="font-size:11px;padding:2px 8px;border-radius:20px;{{ $log->is_bulk ? 'background:var(--purple-dim);color:var(--purple)' : 'background:var(--bg-elevated);color:var(--text-300)' }}">
                            {{ $log->is_bulk ? 'Bulk' : 'Single' }}
                        </span>
                    </td>
                    <td style="font-size:12.5px;color:var(--text-200)" data-label="Sent By">{{ $log->sentBy?->name ?? '—' }}</td>
                    <td style="font-size:11.5px;color:var(--text-400);font-family:var(--mono)" data-label="Time">{{ $log->created_at->diffForHumans() }}</td>
                    <td data-label="Status">
                        <span style="background:var(--{{ $sc[0] }}-dim);color:var(--{{ $sc[0] }});font-size:11px;font-weight:600;padding:2px 8px;border-radius:20px">
                            {{ $sc[1] }}
                        </span>
                    </td>
                    <td style="max-width:220px" data-label="Error">
                        @if($log->status === 'failed' && $log->error_message)
                        <span style="font-size:11.5px;color:var(--red);display:inline-flex;align-items:center;max-width:100%;">
                            <span style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">⚠️ {{ Str::limit($log->error_message, 30) }}</span>
                            <button type="button" class="err-view-btn" onclick='showErrorDetail(@json($log->error_message), @json($log->to_phone))'>view</button>
                        </span>
                        @elseif($log->status === 'failed')
                        <span style="font-size:11.5px;color:var(--text-400);">No error detail captured</span>
                        @else
                        <span style="color:var(--text-400);">—</span>
                        @endif
                    </td>
                    <td>
                        @if($log->to_phone)
                        <a href="{{ $log->wa_url }}" target="_blank"
                           class="btn btn-secondary btn-sm"
                           style="font-size:11.5px;color:#25D366;border-color:rgba(37,211,102,.3)">
                            Re-send
                        </a>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if($logs->hasPages())
    <div class="pag-wrap">
        <span>{{ $logs->firstItem() }}–{{ $logs->lastItem() }} of {{ $logs->total() }}</span>
        <div class="pag-links">
            <a href="{{ $logs->previousPageUrl() ?? '#' }}" class="pg-btn {{ !$logs->previousPageUrl() ? 'disabled':'' }}">←</a>
            @foreach($logs->getUrlRange(max(1,$logs->currentPage()-2), min($logs->lastPage(),$logs->currentPage()+2)) as $page => $url)
            <a href="{{ $url }}" class="pg-btn {{ $page==$logs->currentPage() ? 'active':'' }}">{{ $page }}</a>
            @endforeach
            <a href="{{ $logs->nextPageUrl() ?? '#' }}" class="pg-btn {{ !$logs->nextPageUrl() ? 'disabled':'' }}">→</a>
        </div>
    </div>
    @endif
    @endif
</div>

<div class="log-modal-backdrop" id="errModalBackdrop" onclick="if(event.target===this) closeErrorDetail()">
    <div class="log-modal">
        <div class="log-modal-header">
            <h3>⚠️ Send failed — <span id="errModalPhone" style="color:var(--text-300);font-weight:400;"></span></h3>
            <button type="button" class="btn btn-ghost btn-sm" onclick="closeErrorDetail()">Close</button>
        </div>
        <div class="log-modal-body" id="errModalBody"></div>
    </div>
</div>

<script>
function showErrorDetail(message, phone) {
    document.getElementById('errModalPhone').textContent = phone || '';
    document.getElementById('errModalBody').textContent = message || 'No further detail available.';
    document.getElementById('errModalBackdrop').classList.add('open');
}
function closeErrorDetail() {
    document.getElementById('errModalBackdrop').classList.remove('open');
}
</script>

@endsection