@extends('layouts.app')
@section('title', 'Email')

@push('styles')
<style>
.email-stats { display:grid; grid-template-columns:repeat(5,1fr); gap:12px; margin-bottom:20px; }
@media(max-width:900px) { .email-stats { grid-template-columns:repeat(3,1fr); } }
@media(max-width:500px) { .email-stats { grid-template-columns:repeat(2,1fr); } }
.email-quick-actions { display:grid; grid-template-columns:repeat(3,1fr); gap:12px; margin-bottom:24px; }
@media(max-width:700px) { .email-quick-actions { grid-template-columns:1fr; } }

/* ── MOBILE EMAIL CARDS (list view, <768px) ──────────────────── */
.email-mobile-list{display:none}
@media(max-width:768px){
    .email-table-wrap{display:none}
    .email-mobile-list{display:flex;flex-direction:column;gap:10px;padding:14px}
}
.em-card{background:var(--bg-surface);border:1px solid var(--border-default);border-radius:var(--r-md);padding:14px}
.em-top{display:flex;align-items:flex-start;justify-content:space-between;gap:10px;margin-bottom:10px}
.em-to{font-size:14px;font-weight:700;color:var(--text-100);line-height:1.3;word-break:break-word}
.em-email{font-size:11.5px;color:var(--text-400);margin-top:2px;word-break:break-all}
.em-status{font-size:11px;font-weight:600;padding:2px 8px;border-radius:20px;white-space:nowrap;flex-shrink:0}
.em-subject{font-size:12.5px;color:var(--text-200);margin-bottom:10px;padding:9px 11px;background:var(--bg-elevated);border-radius:8px;word-break:break-word}
.em-meta{display:flex;align-items:center;justify-content:space-between;gap:8px;flex-wrap:wrap;font-size:11.5px;padding-top:10px;border-top:1px solid var(--border-subtle)}
.em-meta-lbl{color:var(--text-400)}
.em-meta-val{color:var(--text-200);font-weight:600}
</style>
@endpush

@section('content')

<div class="page-head">
    <div>
        <div class="page-title">✉️ Email</div>
        <div class="page-sub">Send emails & manage campaigns</div>
    </div>
    <div class="page-actions">
        @if(auth()->user()->isTenantAdmin())
        <a href="{{ route('tenant.email.settings') }}" class="btn btn-secondary">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10.343 3.94c.09-.542.56-.94 1.11-.94h1.093c.55 0 1.02.398 1.11.94l.149.894c.07.424.384.764.78.93.398.164.855.142 1.205-.108l.737-.527a1.125 1.125 0 011.45.12l.773.774c.39.389.44 1.002.12 1.45l-.527.737c-.25.35-.272.806-.107 1.204.165.397.505.71.93.78l.893.15c.543.09.94.56.94 1.109v1.094c0 .55-.397 1.02-.94 1.11l-.893.149c-.425.07-.765.383-.93.78-.165.398-.143.854.107 1.204l.527.738c.32.447.269 1.06-.12 1.45l-.774.773a1.125 1.125 0 01-1.449.12l-.738-.527c-.35-.25-.806-.272-1.203-.107-.397.165-.71.505-.781.929l-.149.894c-.09.542-.56.94-1.11.94h-1.094c-.55 0-1.019-.398-1.11-.94l-.148-.894c-.071-.424-.384-.764-.781-.93-.398-.164-.854-.142-1.204.108l-.738.527c-.447.32-1.06.269-1.45-.12l-.773-.774a1.125 1.125 0 01-.12-1.45l.527-.737c.25-.35.273-.806.108-1.204-.165-.397-.505-.71-.93-.78l-.894-.15c-.542-.09-.94-.56-.94-1.109v-1.094c0-.55.398-1.02.94-1.11l.894-.149c.424-.07.765-.383.93-.78.165-.398.143-.854-.107-1.204l-.527-.738a1.125 1.125 0 01.12-1.45l.773-.773a1.125 1.125 0 011.45-.12l.737.527c.35.25.807.272 1.204.107.397-.165.71-.505.78-.929l.15-.894z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            Email Settings
        </a>
        @endif
        <a href="{{ route('tenant.email.send') }}" class="btn btn-primary">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
            Send Email
        </a>
    </div>
</div>

{{-- Stats --}}
<div class="email-stats">
    @php
        $statItems = [
            ['num' => $stats['total_sent'],  'lbl' => 'Total Sent',  'color' => 'var(--accent)'],
            ['num' => $stats['today'],       'lbl' => 'Today',       'color' => 'var(--text-100)'],
            ['num' => $stats['this_month'],  'lbl' => 'This Month',  'color' => 'var(--text-100)'],
            ['num' => $stats['failed'],      'lbl' => 'Failed',      'color' => 'var(--red)'],
            ['num' => $stats['templates'],   'lbl' => 'Templates',   'color' => 'var(--green)'],
        ];
    @endphp
    @foreach($statItems as $s)
    <div style="background:var(--bg-surface);border:1px solid var(--border-default);border-radius:var(--r-md);padding:16px;text-align:center">
        <div style="font-size:24px;font-weight:800;color:{{ $s['color'] }};font-family:var(--mono)">{{ $s['num'] }}</div>
        <div style="font-size:11.5px;color:var(--text-300);margin-top:3px">{{ $s['lbl'] }}</div>
    </div>
    @endforeach
</div>

{{-- Quick actions --}}
<div class="email-quick-actions">
    @php
        $actions = [
            ['href'=>route('tenant.email.send'),      'icon'=>'✉️', 'label'=>'Send Email',     'sub'=>'Single recipient pe bhejo',    'bg'=>'var(--accent-dim)',  'color'=>'var(--accent)'],
            ['href'=>route('tenant.email.bulk'),      'icon'=>'📨', 'label'=>'Bulk Send',       'sub'=>'Multiple recipients ko bhejo', 'bg'=>'var(--purple-dim)', 'color'=>'var(--purple)'],
            ['href'=>route('tenant.email.templates'), 'icon'=>'📋', 'label'=>'Templates',       'sub'=>'Email templates manage karo',  'bg'=>'var(--amber-dim)',  'color'=>'var(--amber)'],
        ];
    @endphp
    @foreach($actions as $a)
    <a href="{{ $a['href'] }}"
       style="display:flex;align-items:center;gap:14px;padding:18px 20px;border-radius:var(--r-lg);border:1.5px solid var(--border-default);background:var(--bg-surface);text-decoration:none;transition:all .15s">
        <div style="width:44px;height:44px;border-radius:var(--r-md);background:{{ $a['bg'] }};display:flex;align-items:center;justify-content:center;font-size:20px;flex-shrink:0">
            {{ $a['icon'] }}
        </div>
        <div>
            <div style="font-size:14px;font-weight:700;color:var(--text-100);margin-bottom:2px">{{ $a['label'] }}</div>
            <div style="font-size:12px;color:var(--text-300)">{{ $a['sub'] }}</div>
        </div>
    </a>
    @endforeach
</div>

{{-- Recent logs --}}
<div class="card">
    <div class="card-header">
        <div class="card-title">Recent Emails</div>
        <a href="{{ route('tenant.email.logs') }}" class="btn btn-secondary btn-sm">View All →</a>
    </div>
    @if($recentLogs->isEmpty())
    <div style="padding:40px;text-align:center;color:var(--text-300);font-size:13px">No emails sent yet</div>
    @else
    <div class="email-table-wrap" style="overflow-x:auto">
        <table class="data-table">
            <thead>
                <tr><th>To</th><th>Subject</th><th>Template</th><th>Sent By</th><th>Time</th><th>Status</th></tr>
            </thead>
            <tbody>
                @foreach($recentLogs as $log)
                @php $sc = match($log->status) { 'sent'=>['green','Sent'], 'failed'=>['red','Failed'], default=>['amber','Pending'] }; @endphp
                <tr>
                    <td data-label="To">
                        <div style="font-weight:600">{{ $log->to_name ?? $log->to_email }}</div>
                        <div style="font-size:11.5px;color:var(--text-400)">{{ $log->to_email }}</div>
                    </td>
                    <td style="font-size:13px;max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" data-label="Subject">{{ $log->subject }}</td>
                    <td style="font-size:12px;color:var(--text-300)" data-label="Template">{{ $log->template?->name ?? '—' }}</td>
                    <td style="font-size:12.5px;color:var(--text-200)" data-label="Sent By">{{ $log->sentBy?->name ?? '—' }}</td>
                    <td style="font-size:11.5px;color:var(--text-400);font-family:var(--mono)" data-label="Time">{{ $log->created_at->diffForHumans() }}</td>
                    <td data-label="Status">
                        <span style="background:var(--{{ $sc[0] }}-dim);color:var(--{{ $sc[0] }});font-size:11px;font-weight:600;padding:2px 8px;border-radius:20px">{{ $sc[1] }}</span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Mobile card list (shown only <768px, table above hides itself) --}}
    <div class="email-mobile-list">
    @foreach($recentLogs as $log)
    @php $sc = match($log->status) { 'sent'=>['green','Sent'], 'failed'=>['red','Failed'], default=>['amber','Pending'] }; @endphp
    <div class="em-card">
        <div class="em-top">
            <div style="min-width:0">
                <div class="em-to">{{ $log->to_name ?? $log->to_email }}</div>
                <div class="em-email">{{ $log->to_email }}</div>
            </div>
            <span class="em-status" style="background:var(--{{ $sc[0] }}-dim);color:var(--{{ $sc[0] }})">{{ $sc[1] }}</span>
        </div>
        <div class="em-subject">{{ $log->subject }}</div>
        <div class="em-meta">
            <span><span class="em-meta-lbl">Template: </span><span class="em-meta-val">{{ $log->template?->name ?? '—' }}</span></span>
            <span><span class="em-meta-lbl">Sent by: </span><span class="em-meta-val">{{ $log->sentBy?->name ?? '—' }}</span></span>
            <span style="color:var(--text-400);font-family:var(--mono)">{{ $log->created_at->diffForHumans() }}</span>
        </div>
    </div>
    @endforeach
    </div>
    @endif
</div>

@endsection