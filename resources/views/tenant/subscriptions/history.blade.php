@extends('layouts.app')
@section('title', 'Reminder History')

@push('styles')
<style>
.sub-table { width:100%; border-collapse:collapse; background:var(--bg-surface); border:1px solid var(--border-default); border-radius:var(--r-lg); overflow:hidden; }
.sub-table th { padding:10px 14px; text-align:left; font-size:11px; font-weight:700; color:var(--text-400); text-transform:uppercase; letter-spacing:.4px; background:var(--bg-elevated); border-bottom:1px solid var(--border-subtle); }
.sub-table td { padding:11px 14px; border-bottom:1px solid var(--border-subtle); font-size:13.5px; color:var(--text-100); vertical-align:top; }
.sub-table tr:last-child td { border-bottom:none; }
.sub-table tr:hover td { background:var(--bg-elevated); }
.mono { font-family:var(--mono); }
.badge-status { display:inline-block; padding:2px 9px; border-radius:20px; font-size:11.5px; font-weight:600; }
.badge-sent   { background:var(--green-dim); color:var(--green); }
.badge-failed { background:var(--red-dim); color:var(--red); }
.badge-channel{ display:inline-block; padding:2px 9px; border-radius:20px; font-size:11.5px; font-weight:600; background:var(--accent-dim); color:var(--accent); text-transform:capitalize; }
.status-tabs { display:flex; gap:4px; flex-wrap:wrap; margin-bottom:16px; }
.s-tab { padding:7px 14px; border-radius:var(--r-sm); font-size:12.5px; font-weight:600; text-decoration:none; color:var(--text-300); border:1.5px solid transparent; transition:all .15s; }
.s-tab:hover { color:var(--text-100); background:var(--bg-elevated); }
.s-tab.active { background:var(--accent-dim); color:var(--accent); border-color:rgba(var(--accent-rgb),.25); }
.msg-snippet { font-size:12px; color:var(--text-300); max-width:320px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
</style>
@endpush

@section('content')

<div class="page-head">
    <div>
        <div style="font-size:12px;color:var(--text-300);margin-bottom:4px">
            <a href="{{ route('tenant.subscriptions.index') }}" style="color:var(--text-300);text-decoration:none">Subscriptions</a>
            › Reminder History
        </div>
        <div class="page-title">Reminder History</div>
        <div class="page-sub">Every Email/WhatsApp reminder sent to customers — manual or automatic</div>
    </div>
    <a href="{{ route('tenant.subscriptions.index') }}" class="btn btn-secondary">← Back</a>
</div>

<div class="status-tabs">
    <a href="{{ route('tenant.subscriptions.history') }}" class="s-tab {{ !request('channel') ? 'active' : '' }}">All</a>
    <a href="{{ route('tenant.subscriptions.history', ['channel'=>'email']) }}" class="s-tab {{ request('channel')==='email' ? 'active' : '' }}">Email</a>
    <a href="{{ route('tenant.subscriptions.history', ['channel'=>'whatsapp']) }}" class="s-tab {{ request('channel')==='whatsapp' ? 'active' : '' }}">WhatsApp</a>
</div>

<div class="sub-table-wrap">
<table class="sub-table">
    <thead>
        <tr>
            <th>When</th>
            <th>Contact</th>
            <th>Service</th>
            <th>Channel</th>
            <th>Trigger</th>
            <th>Status</th>
            <th>Message</th>
        </tr>
    </thead>
    <tbody>
        @forelse($logs as $log)
        <tr>
            <td class="mono" data-label="When">{{ $log->sent_at?->format('d M Y, h:i A') ?? $log->created_at->format('d M Y, h:i A') }}</td>
            <td style="font-weight:600" data-label="Contact">{{ $log->contact?->name ?? '—' }}</td>
            <td data-label="Service">{{ $log->subscription?->service?->name ?? '—' }}</td>
            <td data-label="Channel"><span class="badge-channel">{{ $log->channel }}</span></td>
            <td data-label="Trigger">{{ $log->is_manual ? 'Manual' : 'Automatic' }}</td>
            <td data-label="Status">
                @if($log->status === 'sent')
                    <span class="badge-status badge-sent">Sent</span>
                @else
                    <span class="badge-status badge-failed">Failed</span>
                @endif
                @if($log->error_message)
                    <div style="font-size:11px;color:var(--red);margin-top:3px">{{ $log->error_message }}</div>
                @endif
            </td>
            <td data-label="Message">
                <div class="msg-snippet" title="{{ strip_tags($log->message) }}">{{ strip_tags($log->message) }}</div>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="7" style="text-align:center;padding:40px;color:var(--text-400)">
                No reminders sent yet.
            </td>
        </tr>
        @endforelse
    </tbody>
</table>
</div>

<div style="margin-top:14px">{{ $logs->links() }}</div>

@endsection
