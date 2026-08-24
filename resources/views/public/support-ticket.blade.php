<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<meta name="csrf-token" content="{{ csrf_token() }}"/>
<title>Ticket — {{ $ticket->subject }}</title>
<link rel="preconnect" href="https://fonts.googleapis.com"/>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="{{ asset('css/app.css') }}"/>
<style>
body { background: var(--bg-app); min-height:100vh; padding:24px 16px; }
.bk-wrap { max-width:620px; margin:0 auto; display:flex; flex-direction:column; gap:16px; }
.bk-card { background:var(--bg-surface); border:1px solid var(--border-default); border-radius:14px; padding:22px; }
.bk-flash { padding:12px 16px; border-radius:10px; font-size:13.5px; font-weight:500; }
.bk-flash.success { background:var(--green-dim); color:var(--green); border:1px solid var(--green); }
.bk-flash.error   { background:var(--red-dim); color:var(--red); border:1px solid var(--red); }
.bk-flash.info    { background:var(--accent-dim); color:var(--accent); border:1px solid var(--accent); }
.bk-badge { display:inline-flex; padding:5px 14px; border-radius:20px; font-size:12.5px; font-weight:600; }
.bk-badge.open, .bk-badge.in_progress { background:var(--accent-dim); color:var(--accent); }
.bk-badge.resolved { background:var(--green-dim); color:var(--green); }
.bk-badge.closed { background:var(--bg-elevated); color:var(--text-400); }
.msg { padding:12px 14px; border-radius:10px; margin-bottom:10px; font-size:13.5px; line-height:1.5; }
.msg.customer { background:var(--accent-dim); }
.msg.staff { background:var(--bg-elevated); }
.msg-meta { font-size:11px; color:var(--text-400); margin-bottom:4px; font-weight:600; }
.bk-input { width:100%; padding:10px 12px; background:var(--bg-input); border:1.5px solid var(--border-default); border-radius:8px; color:var(--text-100); font-family:var(--font); font-size:13.5px; outline:none; }
.bk-btn { padding:10px 20px; border-radius:10px; border:none; background:var(--accent); color:#fff; font-size:13.5px; font-weight:600; cursor:pointer; margin-top:10px; }
</style>
</head>
<body>

<div class="bk-wrap">
    @foreach(['success','error','info'] as $type)
    @if(session($type))
    <div class="bk-flash {{ $type }}">{{ session($type) }}</div>
    @endif
    @endforeach

    <div class="bk-card">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:12px;margin-bottom:6px">
            <div>
                <div style="font-size:17px;font-weight:700;color:var(--text-100)">{{ $ticket->subject }}</div>
                @if($ticket->ticket_number)
                <div style="font-family:var(--mono);font-size:12.5px;color:var(--accent);margin-top:2px">{{ $ticket->ticket_number }}</div>
                @endif
            </div>
            <span class="bk-badge {{ $ticket->status }}">{{ \App\Models\Ticket::statuses()[$ticket->status] ?? ucfirst($ticket->status) }}</span>
        </div>
        <div style="font-size:12.5px;color:var(--text-400);margin-bottom:16px">with {{ $ticket->tenant->name ?? '' }} — opened {{ $ticket->created_at->format('d M Y') }}</div>

        @if($ticket->description)
        <div class="msg customer">
            <div class="msg-meta">{{ $ticket->contact?->name ?? 'You' }} · {{ $ticket->created_at->format('d M Y, h:i A') }}</div>
            {{ $ticket->description }}
        </div>
        @endif

        @foreach($replies as $r)
        <div class="msg {{ $r->is_customer_reply ? 'customer' : 'staff' }}">
            <div class="msg-meta">{{ $r->authorLabel() }} · {{ $r->created_at->format('d M Y, h:i A') }}</div>
            {{ $r->body }}
        </div>
        @endforeach

        @if($ticket->attachments->isNotEmpty())
        <div style="margin-top:10px;font-size:12px;color:var(--text-400)">
            Attachments:
            @foreach($ticket->attachments as $a)
            <a href="{{ $a->url }}" target="_blank" style="color:var(--accent);margin-right:8px">{{ $a->original_name }}</a>
            @endforeach
        </div>
        @endif

        @if($ticket->status !== 'closed')
        <form method="POST" action="{{ route('public.support.reply', $ticket->public_token) }}" style="margin-top:16px">
            @csrf
            <textarea name="body" class="bk-input" rows="3" placeholder="Type your reply..." required></textarea>
            <button type="submit" class="bk-btn">Send Reply</button>
        </form>
        @else
        <div style="margin-top:16px;font-size:12.5px;color:var(--text-400)">This ticket is closed. Submit a new one if you need further help.</div>
        @endif
    </div>
</div>

</body>
</html>
