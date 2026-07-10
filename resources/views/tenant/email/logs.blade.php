@extends('layouts.app')
@section('title', 'Email Logs')

@section('content')

<div class="page-head">
    <div>
        <div style="font-size:13px;color:var(--text-300);margin-bottom:4px">
            <a href="{{ route('tenant.email.index') }}" style="color:var(--text-300);text-decoration:none">Email</a>
            <span style="margin:0 6px">›</span> Logs
        </div>
        <div class="page-title">Email Logs</div>
    </div>
    <a href="{{ route('tenant.email.send') }}" class="btn btn-primary">Send Email</a>
</div>

{{-- Filters --}}
<form method="GET" action="{{ route('tenant.email.logs') }}">
    <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:20px">
        <select name="status" style="padding:8px 12px;height:36px;background:var(--bg-input);border:1.5px solid var(--border-default);border-radius:var(--r-sm);color:var(--text-100);font-family:var(--font);font-size:13px;outline:none;-webkit-appearance:none"
                onchange="this.form.submit()">
            <option value="">All Status</option>
            <option value="sent"   {{ request('status')==='sent'   ? 'selected':'' }}>Sent</option>
            <option value="failed" {{ request('status')==='failed' ? 'selected':'' }}>Failed</option>
            <option value="pending"{{ request('status')==='pending'? 'selected':'' }}>Pending</option>
        </select>
        <select name="is_bulk" style="padding:8px 12px;height:36px;background:var(--bg-input);border:1.5px solid var(--border-default);border-radius:var(--r-sm);color:var(--text-100);font-family:var(--font);font-size:13px;outline:none;-webkit-appearance:none"
                onchange="this.form.submit()">
            <option value="">All Types</option>
            <option value="0" {{ request('is_bulk')==='0' ? 'selected':'' }}>Single</option>
            <option value="1" {{ request('is_bulk')==='1' ? 'selected':'' }}>Bulk</option>
        </select>
        <input type="date" name="date" value="{{ request('date') }}"
               style="padding:8px 12px;height:36px;background:var(--bg-input);border:1.5px solid var(--border-default);border-radius:var(--r-sm);color:var(--text-100);font-family:var(--font);font-size:13px;outline:none"
               onchange="this.form.submit()"/>
        @if(request()->hasAny(['status','is_bulk','date']))
        <a href="{{ route('tenant.email.logs') }}" class="btn btn-secondary">Clear</a>
        @endif
    </div>
</form>

<div class="card">
    @if($logs->isEmpty())
    <div style="padding:60px 20px;text-align:center;color:var(--text-300);font-size:13px">No emails found</div>
    @else
    <div style="overflow-x:auto">
        <table class="data-table">
            <thead>
                <tr>
                    <th>To</th><th>Subject</th><th>Template</th>
                    <th>Type</th><th>Sent By</th><th>Time</th><th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($logs as $log)
                @php $sc = match($log->status) { 'sent'=>['green','Sent'], 'failed'=>['red','Failed'], default=>['amber','Pending'] }; @endphp
                <tr>
                    <td data-label="To">
                        <div style="font-weight:600">{{ $log->to_name ?? $log->to_email }}</div>
                        <div style="font-size:11.5px;color:var(--text-400)">{{ $log->to_email }}</div>
                    </td>
                    <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:13px" data-label="Subject">
                        {{ $log->subject }}
                    </td>
                    <td style="font-size:12px;color:var(--text-300)" data-label="Template">{{ $log->template?->name ?? '—' }}</td>
                    <td data-label="Type">
                        <span style="font-size:11px;padding:2px 8px;border-radius:20px;{{ $log->is_bulk ? 'background:var(--purple-dim);color:var(--purple)':'background:var(--bg-elevated);color:var(--text-300)' }}">
                            {{ $log->is_bulk ? 'Bulk':'Single' }}
                        </span>
                    </td>
                    <td style="font-size:12.5px;color:var(--text-200)" data-label="Sent By">{{ $log->sentBy?->name ?? '—' }}</td>
                    <td style="font-size:11.5px;color:var(--text-400);font-family:var(--mono)" data-label="Time">{{ $log->created_at->diffForHumans() }}</td>
                    <td data-label="Status">
                        <span style="background:var(--{{ $sc[0] }}-dim);color:var(--{{ $sc[0] }});font-size:11px;font-weight:600;padding:2px 8px;border-radius:20px">
                            {{ $sc[1] }}
                        </span>
                        @if($log->status === 'failed' && $log->error_message)
                        <div style="font-size:11px;color:var(--red);margin-top:2px" title="{{ $log->error_message }}">
                            {{ \Illuminate\Support\Str::limit($log->error_message, 30) }}
                        </div>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if($logs->hasPages())
    <div style="display:flex;align-items:center;justify-content:space-between;padding:14px 20px;border-top:1px solid var(--border-subtle);font-size:13px;color:var(--text-300)">
        <span>{{ $logs->firstItem() }}–{{ $logs->lastItem() }} of {{ $logs->total() }}</span>
        <div style="display:flex;gap:4px">
            <a href="{{ $logs->previousPageUrl() ?? '#' }}" style="padding:5px 10px;border:1px solid var(--border-default);border-radius:var(--r-sm);color:var(--text-200);text-decoration:none;font-size:13px;{{ !$logs->previousPageUrl() ? 'opacity:.4;pointer-events:none':'' }}">←</a>
            @foreach($logs->getUrlRange(max(1,$logs->currentPage()-2),min($logs->lastPage(),$logs->currentPage()+2)) as $page => $url)
            <a href="{{ $url }}" style="padding:5px 10px;border:1px solid var(--border-default);border-radius:var(--r-sm);text-decoration:none;font-size:13px;{{ $page==$logs->currentPage() ? 'background:var(--accent);border-color:var(--accent);color:#fff':'color:var(--text-200)' }}">{{ $page }}</a>
            @endforeach
            <a href="{{ $logs->nextPageUrl() ?? '#' }}" style="padding:5px 10px;border:1px solid var(--border-default);border-radius:var(--r-sm);color:var(--text-200);text-decoration:none;font-size:13px;{{ !$logs->nextPageUrl() ? 'opacity:.4;pointer-events:none':'' }}">→</a>
        </div>
    </div>
    @endif
    @endif
</div>

@endsection