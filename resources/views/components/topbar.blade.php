<header class="topbar">

    {{-- ── Sidebar toggle ───────────────────────────────────────── --}}
    <button class="topbar-toggle" onclick="toggleSidebar()" type="button" title="Toggle sidebar">
        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/>
        </svg>
    </button>

    {{-- ── Global search ────────────────────────────────────────── --}}
    <div class="topbar-search" onclick="document.getElementById('globalSearch').focus()">
        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/>
        </svg>
        <input
            type="text"
            id="globalSearch"
            placeholder="Search leads, contacts, deals..."
            autocomplete="off"
            onkeydown="handleSearch(event)"
        />
        <span class="search-kbd">
            <kbd>⌘K</kbd>
        </span>
    </div>

    {{-- ── Right section ────────────────────────────────────────── --}}
    <div class="topbar-right">

        {{-- Theme toggle --}}
        <button class="tb-btn" onclick="toggleTheme()" type="button" title="Toggle theme">
            <svg id="ico-moon" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.718 9.718 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 009.002-5.998z"/>
            </svg>
            <svg id="ico-sun" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" style="display:none">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z"/>
            </svg>
        </button>

        {{-- Notifications --}}
      {{--  <div class="dropdown">
            <button class="tb-btn" onclick="toggleDrop('notifDrop')" type="button" title="Notifications">
                <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0"/>
                </svg>
                @if(($unreadNotifs ?? 0) > 0)
                    <span class="tb-notif-dot"></span>
                @else
                    <span class="tb-notif-dot"></span> {{-- remove in prod if 0 
                @endif
            </button>

            <div class="drop-menu" id="notifDrop" style="min-width:300px">
                <div class="drop-menu-head" style="display:flex;align-items:center;justify-content:space-between">
                    <span>Notifications</span>
                    <a href="#" style="font-size:11px;color:var(--accent);text-decoration:none;font-weight:600;text-transform:none;letter-spacing:0">Mark all read</a>
                </div>

                {{-- Notif items
                @php
                    $notifs = $notifications ?? [
                        ['type'=>'lead',  'dot'=>'lead', 'text'=>'3 new leads assigned to you', 'time'=>'2 min ago',  'unread'=>true],
                        ['type'=>'task',  'dot'=>'task', 'text'=>'Follow-up with Rahul Sharma is due now', 'time'=>'15 min ago', 'unread'=>true],
                        ['type'=>'deal',  'dot'=>'deal', 'text'=>'Deal "Office Supplies" moved to Won', 'time'=>'1h ago',   'unread'=>false],
                        ['type'=>'msg',   'dot'=>'msg',  'text'=>'WhatsApp campaign delivered to 142 contacts', 'time'=>'2h ago',   'unread'=>false],
                    ];
                    $dotIcons = [
                        'lead' => '<path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.75 3.75 0 11-6.75 0 3.75 3.75 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/>',
                        'task' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>',
                        'deal' => '<path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75"/>',
                        'msg'  => '<path stroke-linecap="round" stroke-linejoin="round" d="M8.625 9.75a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375m-13.5 3.01c0 1.6 1.123 2.994 2.707 3.227 1.087.16 2.185.283 3.293.369V21l4.184-4.183a1.14 1.14 0 01.778-.332 48.294 48.294 0 005.83-.498c1.585-.233 2.708-1.626 2.708-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0012 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018z"/>',
                    ];
                @endphp

                @foreach($notifs as $n)
                <div class="drop-item notif-item {{ $n['unread'] ? 'unread' : '' }}">
                    <div class="act-dot {{ $n['dot'] }}" style="width:28px;height:28px;flex-shrink:0">
                        <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" style="width:13px;height:13px">
                            {!! $dotIcons[$n['dot']] !!}
                        </svg>
                    </div>
                    <div style="flex:1;min-width:0">
                        <div style="font-size:12.5px;color:var(--text-100);line-height:1.4">{{ $n['text'] }}</div>
                        <div style="font-size:11px;color:var(--text-400);margin-top:2px;font-family:var(--mono)">{{ $n['time'] }}</div>
                    </div>
                    @if($n['unread'])
                        <div style="width:6px;height:6px;border-radius:50%;background:var(--accent);flex-shrink:0"></div>
                    @endif
                </div>
                @endforeach

                <div style="padding:10px 16px;border-top:1px solid var(--border-subtle)">
                    <a href="#" style="font-size:12.5px;color:var(--accent);text-decoration:none;font-weight:500">
                        View all notifications →
                    </a>
                </div>
            </div>
        </div>  --}}

        {{-- Notification bell component --}}
        @include('components.layouts.bell')

        {{-- User avatar --}}
        <div class="dropdown">
            <div class="tb-avatar" onclick="toggleDrop('tbUserDrop')" title="{{ auth()->user()->name }}">
                @if(auth()->user()->avatar)
                    <img src="{{ Storage::url(auth()->user()->avatar) }}" alt="avatar"/>
                @else
                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                @endif
            </div>

            <div class="drop-menu" id="tbUserDrop">
                {{-- User info header --}}
                <div style="padding:14px 16px 12px;border-bottom:1px solid var(--border-subtle)">
                    <div style="font-size:13.5px;font-weight:700;color:var(--text-100)">{{ auth()->user()->name }}</div>
                    <div style="font-size:12px;color:var(--text-300);margin-top:2px">{{ auth()->user()->email }}</div>
                    <div style="margin-top:8px">
                        <span class="badge badge-new" style="font-size:10px">
                            {{ ucfirst(str_replace('_',' ', auth()->user()->user_type)) }}
                        </span>
                    </div>
                </div>

                {{-- <a href="{{ route('settings') }}" --}} <a href="#"
                 class="drop-item">
                    <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/>
                    </svg>
                    My Profile
                </a>
                {{-- <a href="{{ route('settings') }}" --}} <a href="#"
                 class="drop-item">
                    <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    Workspace Settings
                </a>

                {{-- Subscription info --}}
                @if(auth()->user()->tenant?->subscription)
                @php $sub = auth()->user()->tenant->subscription; @endphp
                <div style="margin:4px 10px;padding:10px 12px;background:var(--bg-input);border-radius:var(--r-sm);border:1px solid var(--border-subtle)">
                    <div style="font-size:11px;color:var(--text-400);text-transform:uppercase;letter-spacing:0.5px;font-weight:700;margin-bottom:4px">Current Plan</div>
                    <div style="display:flex;align-items:center;justify-content:space-between">
                        <span style="font-size:13px;font-weight:600;color:var(--text-100)">{{ $sub->plan?->name ?? 'Free' }}</span>
                        <a href="{{ route('tenant.subscription.plans') }}" style="font-size:11.5px;color:var(--accent);text-decoration:none;font-weight:600">Upgrade</a>
                    </div>
                    @if($sub->ends_at)
                    <div style="font-size:11px;color:var(--text-400);margin-top:2px;font-family:var(--mono)">
                        Expires {{ $sub->ends_at->format('d M Y') }}
                    </div>
                    @endif
                </div>
                @endif

                <div class="drop-sep"></div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="drop-item red">
                        <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75"/>
                        </svg>
                        Sign out
                    </button>
                </form>
            </div>
        </div>

    </div>{{-- /topbar-right --}}

</header>

{{-- Extra topbar CSS --}}
<style>
.search-kbd {
    display: flex; align-items: center;
    flex-shrink: 0;
}
.search-kbd kbd {
    font-size: 10.5px; color: var(--text-400);
    background: var(--bg-elevated);
    border: 1px solid var(--border-default);
    border-radius: 4px; padding: 1px 5px;
    font-family: var(--mono); letter-spacing: 0;
}
.notif-item.unread { background: rgba(99,120,255,0.04); }
.notif-item.unread:hover { background: var(--bg-hover); }
</style>