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

@endsection