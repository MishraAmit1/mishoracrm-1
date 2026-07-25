@extends('layouts.app')
@section('title', 'Notifications')

@push('styles')
<style>
.notif-layout { display:grid; grid-template-columns:240px 1fr; gap:16px; align-items:start; }
@media(max-width:900px) { .notif-layout { grid-template-columns:1fr; } }

/* Sidebar */
.notif-sidebar { background:var(--bg-surface); border:1px solid var(--border-default); border-radius:var(--r-lg); overflow:hidden; position:sticky; top:80px; }
.sidebar-head  { padding:14px 16px; border-bottom:1px solid var(--border-subtle); font-size:13px; font-weight:700; color:var(--text-100); }
.sidebar-item  {
    display:flex; align-items:center; justify-content:space-between;
    padding:10px 16px; font-size:13px; color:var(--text-200);
    text-decoration:none; border-bottom:1px solid var(--border-subtle);
    transition:background .15s; cursor:pointer;
}
.sidebar-item:last-child { border-bottom:none; }
.sidebar-item:hover { background:var(--bg-elevated); }
.sidebar-item.active { background:var(--accent-dim); color:var(--accent); font-weight:600; }
.sidebar-count { font-size:11px; font-family:var(--mono); padding:1px 6px; border-radius:10px; background:var(--bg-elevated); color:var(--text-300); }
.sidebar-item.active .sidebar-count { background:var(--accent); color:#fff; }

/* Notification items */
.notif-item {
    display:flex; gap:14px; padding:16px 20px;
    border-bottom:1px solid var(--border-subtle);
    transition:background .15s; position:relative;
}
.notif-item:last-child { border-bottom:none; }
.notif-item:hover { background:var(--bg-elevated); }
.notif-item.unread { background:rgba(99,120,255,.04); }
.notif-item.unread::before { content:''; position:absolute; left:0; top:0; bottom:0; width:3px; background:var(--accent); border-radius:0 2px 2px 0; }

.notif-icon { width:40px; height:40px; border-radius:50%; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.notif-icon svg { width:18px; height:18px; }

.notif-content { flex:1; min-width:0; }
.notif-title   { font-size:13.5px; font-weight:600; color:var(--text-100); margin-bottom:3px; }
.notif-message { font-size:12.5px; color:var(--text-300); line-height:1.5; margin-bottom:6px; }
.notif-meta    { display:flex; align-items:center; gap:8px; font-size:11.5px; color:var(--text-400); font-family:var(--mono); }

.notif-actions { display:flex; gap:6px; flex-shrink:0; align-items:flex-start; opacity:0; transition:opacity .15s; }
.notif-item:hover .notif-actions { opacity:1; }
.notif-btn { padding:4px 8px; border-radius:var(--r-sm); border:1px solid var(--border-default); background:none; font-size:11px; color:var(--text-300); cursor:pointer; transition:all .15s; }
.notif-btn:hover { border-color:var(--accent); color:var(--accent); }
.notif-btn.del:hover { border-color:var(--red); color:var(--red); }

/* Filter bar */
.filter-bar { display:flex; align-items:center; gap:10px; padding:12px 20px; border-bottom:1px solid var(--border-subtle); flex-wrap:wrap; }
.filter-btn { padding:5px 12px; border-radius:20px; font-size:12px; font-weight:600; border:1.5px solid var(--border-default); background:none; color:var(--text-300); cursor:pointer; text-decoration:none; transition:all .15s; font-family:var(--font); }
.filter-btn:hover { border-color:var(--border-strong); color:var(--text-100); }
.filter-btn.active { border-color:var(--accent); color:var(--accent); background:var(--accent-dim); }

.empty-state { padding:60px 20px; text-align:center; }
.empty-icon  { font-size:40px; margin-bottom:12px; }
.empty-title { font-size:15px; font-weight:700; color:var(--text-100); margin-bottom:6px; }
.empty-sub   { font-size:13px; color:var(--text-300); }

.pag-wrap { display:flex; align-items:center; justify-content:space-between; padding:14px 20px; border-top:1px solid var(--border-subtle); font-size:13px; color:var(--text-300); }
.pag-links { display:flex; gap:4px; }
.pg-btn { padding:5px 10px; border-radius:var(--r-sm); border:1px solid var(--border-default); color:var(--text-200); text-decoration:none; font-size:13px; transition:all .15s; }
.pg-btn:hover { border-color:var(--accent); color:var(--accent); }
.pg-btn.active { background:var(--accent); border-color:var(--accent); color:#fff; }
.pg-btn.disabled { opacity:.4; pointer-events:none; }
</style>
@endpush

@section('content')

@php
    $colorMap = [
        'accent' => ['bg'=>'var(--accent-dim)',  'color'=>'var(--accent)'],
        'green'  => ['bg'=>'var(--green-dim)',   'color'=>'var(--green)'],
        'red'    => ['bg'=>'var(--red-dim)',     'color'=>'var(--red)'],
        'amber'  => ['bg'=>'var(--amber-dim)',   'color'=>'var(--amber)'],
        'purple' => ['bg'=>'var(--purple-dim)',  'color'=>'var(--purple)'],
    ];
    $curStatus = request('status', '');
    $curType   = request('type', '');
    $groups    = collect($types)->groupBy(fn($t) => $t['group'] ?? 'Other');
@endphp

<div class="page-head">
    <div>
        <div class="page-title">Notifications</div>
        <div class="page-sub">{{ $unreadCount }} unread</div>
    </div>
    <div class="page-actions">
        @if($unreadCount > 0)
        <button class="btn btn-secondary" onclick="markAllRead()">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="width:15px;height:15px"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
            Mark All Read
        </button>
        @endif
        <a href="{{ route('tenant.notifications.preferences') }}" class="btn btn-secondary">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="width:15px;height:15px"><path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            Preferences
        </a>
        @if(auth()->user()->isTenantAdmin())
        <a href="{{ route('tenant.slack.index') }}" class="btn btn-secondary">
            <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" style="width:15px;height:15px"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 01.865-.501 48.172 48.172 0 003.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0012 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018z"/></svg>
            Slack Setup
        </a>
        @endif
        <form method="POST" action="{{ route('tenant.notifications.clear') }}">
            @csrf
            <button type="submit" class="btn btn-secondary" style="color:var(--red)">Clear Read</button>
        </form>
    </div>
</div>

<div class="notif-layout">

    {{-- Sidebar --}}
    <div class="notif-sidebar">
        <div class="sidebar-head">Filter by Type</div>
        <a href="{{ route('tenant.notifications.index') }}"
           class="sidebar-item {{ !$curType ? 'active':'' }}">
            All
            <span class="sidebar-count">{{ $notifications->total() }}</span>
        </a>
        @foreach($groups as $group => $groupTypes)
        <div style="padding:8px 16px 4px;font-size:10px;font-weight:700;color:var(--text-400);text-transform:uppercase;letter-spacing:.5px;background:var(--bg-elevated)">
            {{ $group }}
        </div>
        @foreach($groupTypes as $typeKey => $typeCfg)
        <a href="{{ route('tenant.notifications.index', ['type'=>$typeKey]) }}"
           class="sidebar-item {{ $curType === $typeKey ? 'active':'' }}">
            <span style="font-size:12.5px">{{ $typeCfg['label'] }}</span>
        </a>
        @endforeach
        @endforeach
    </div>

    {{-- Main --}}
    <div class="card" style="padding:0">

        {{-- Filter bar --}}
        <div class="filter-bar">
            <a href="{{ route('tenant.notifications.index', array_merge(request()->except('status','page'), [])) }}"
               class="filter-btn {{ !$curStatus ? 'active':'' }}">All</a>
            <a href="{{ route('tenant.notifications.index', array_merge(request()->except('status','page'), ['status'=>'unread'])) }}"
               class="filter-btn {{ $curStatus==='unread' ? 'active':'' }}">
                Unread
                @if($unreadCount > 0)
                <span style="background:var(--accent);color:#fff;font-size:10px;padding:0 5px;border-radius:10px;margin-left:4px">{{ $unreadCount }}</span>
                @endif
            </a>
            <a href="{{ route('tenant.notifications.index', array_merge(request()->except('status','page'), ['status'=>'read'])) }}"
               class="filter-btn {{ $curStatus==='read' ? 'active':'' }}">Read</a>
        </div>

        @if($notifications->isEmpty())
        <div class="empty-state">
            <div class="empty-icon">🔔</div>
            <div class="empty-title">No notifications</div>
            <div class="empty-sub">You're all caught up!</div>
        </div>
        @else

        @foreach($notifications as $n)
        @php
            $c = $colorMap[$n->color] ?? $colorMap['accent'];
        @endphp
        <div class="notif-item {{ !$n->is_read ? 'unread':'' }}" id="notif_{{ $n->id }}">

            {{-- Icon --}}
            <div class="notif-icon" style="background:{{ $c['bg'] }};color:{{ $c['color'] }}">
                <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                    {!! $n->icon_svg !!}
                </svg>
            </div>

            {{-- Content --}}
            <div class="notif-content">
                @if($n->url)
                <a href="{{ $n->url }}" class="notif-title" style="text-decoration:none;color:inherit"
                   onclick="markRead({{ $n->id }})">{{ $n->title }}</a>
                @else
                <div class="notif-title">{{ $n->title }}</div>
                @endif
                <div class="notif-message">{{ $n->message }}</div>
                <div class="notif-meta">
                    <span>{{ $n->created_at->diffForHumans() }}</span>
                    @if(!$n->is_read)
                    <span style="width:6px;height:6px;border-radius:50%;background:var(--accent);display:inline-block"></span>
                    <span style="color:var(--accent)">Unread</span>
                    @endif
                    @if($n->triggered_by)
                    <span>by {{ $n->triggeredBy?->name }}</span>
                    @endif
                </div>
            </div>

            {{-- Actions --}}
            <div class="notif-actions">
                @if(!$n->is_read)
                <button class="notif-btn" onclick="markRead({{ $n->id }})">✓ Read</button>
                @endif
                <button class="notif-btn del" onclick="deleteNotif({{ $n->id }})">✕</button>
            </div>

        </div>
        @endforeach

        {{-- Pagination --}}
        @if($notifications->hasPages())
        <div class="pag-wrap">
            <span>{{ $notifications->firstItem() }}–{{ $notifications->lastItem() }} of {{ $notifications->total() }}</span>
            <div class="pag-links">
                <a href="{{ $notifications->previousPageUrl() ?? '#' }}" class="pg-btn {{ !$notifications->previousPageUrl() ? 'disabled':'' }}">←</a>
                @foreach($notifications->getUrlRange(max(1,$notifications->currentPage()-2),min($notifications->lastPage(),$notifications->currentPage()+2)) as $page => $url)
                <a href="{{ $url }}" class="pg-btn {{ $page==$notifications->currentPage() ? 'active':'' }}">{{ $page }}</a>
                @endforeach
                <a href="{{ $notifications->nextPageUrl() ?? '#' }}" class="pg-btn {{ !$notifications->nextPageUrl() ? 'disabled':'' }}">→</a>
            </div>
        </div>
        @endif

        @endif
    </div>
</div>

@endsection

@push('scripts')
<script>
function markRead(id) {
    fetch(`/notifications/${id}/read`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Content-Type':'application/json' }
    }).then(() => {
        const el = document.getElementById('notif_' + id);
        if (el) el.classList.remove('unread');
    });
}

function markAllRead() {
    fetch('/notifications/read-all', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
    }).then(() => window.location.reload());
}

function deleteNotif(id) {
    fetch(`/notifications/${id}`, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
    }).then(() => {
        const el = document.getElementById('notif_' + id);
        if (el) el.remove();
    });
}
</script>
@endpush