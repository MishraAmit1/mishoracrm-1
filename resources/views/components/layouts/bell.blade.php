<div style="position:relative" id="notifBell">

    {{-- Bell button --}}
    <button onclick="toggleBell(event)"
            style="position:relative;width:36px;height:36px;border-radius:var(--r-sm);background:none;border:1.5px solid var(--border-default);display:flex;align-items:center;justify-content:center;cursor:pointer;color:var(--text-200);transition:all .15s">
        <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24" style="width:18px;height:18px">
            <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0"/>
        </svg>
        {{-- Unread badge --}}
        <span id="bellBadge"
              style="position:absolute;top:-4px;right:-4px;min-width:17px;height:17px;border-radius:10px;background:var(--red);color:#fff;font-size:9px;font-weight:700;display:none;align-items:center;justify-content:center;padding:0 4px;font-family:var(--mono)">
            0
        </span>
    </button>

    {{-- Dropdown --}}
    <div id="bellDropdown"
         style="display:none;position:absolute;top:calc(100% + 10px);right:0;width:360px;background:var(--bg-surface);border:1px solid var(--border-default);border-radius:var(--r-lg);box-shadow:0 8px 32px rgba(0,0,0,.2);z-index:500;overflow:hidden">

        {{-- Header --}}
        <div style="padding:14px 16px;border-bottom:1px solid var(--border-subtle);display:flex;align-items:center;justify-content:space-between">
            <div style="font-size:14px;font-weight:700;color:var(--text-100)">
                Notifications
                <span id="dropBadge" style="font-size:11px;font-family:var(--mono);background:var(--accent-dim);color:var(--accent);padding:1px 7px;border-radius:10px;margin-left:6px"></span>
            </div>
            <div style="display:flex;gap:6px">
                <button onclick="markAllRead()" style="font-size:11.5px;color:var(--accent);background:none;border:none;cursor:pointer;padding:0">Mark all read</button>
                <a href="{{ route('tenant.notifications.index') }}" style="font-size:11.5px;color:var(--text-300);text-decoration:none">View all →</a>
            </div>
        </div>

        {{-- List --}}
        <div id="bellList" style="max-height:380px;overflow-y:auto">
            <div style="padding:40px 20px;text-align:center;color:var(--text-400);font-size:13px" id="bellEmpty">
                Loading...
            </div>
        </div>

        {{-- Footer --}}
        <div style="padding:10px 16px;border-top:1px solid var(--border-subtle);text-align:center">
            <a href="{{ route('tenant.notifications.preferences') }}"
               style="font-size:12px;color:var(--text-300);text-decoration:none">
                ⚙️ Notification Settings
            </a>
        </div>
    </div>
</div>

<style>
.bell-item {
    display:flex; gap:12px; padding:12px 16px;
    border-bottom:1px solid var(--border-subtle);
    transition:background .15s; cursor:pointer;
    text-decoration:none;
}
.bell-item:last-child { border-bottom:none; }
.bell-item:hover { background:var(--bg-elevated); }
.bell-item.unread { background:rgba(255,122,89,.04); }
#bellDropdown.bell-open { animation: fadeUp 0.15s var(--ease-out) both; }
.bell-icon { width:34px; height:34px; border-radius:50%; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.bell-icon svg { width:15px; height:15px; }
.bell-title { font-size:13px; font-weight:600; color:var(--text-100); margin-bottom:2px; line-height:1.3; }
.bell-msg   { font-size:12px; color:var(--text-300); line-height:1.4; }
.bell-time  { font-size:11px; color:var(--text-400); margin-top:3px; font-family:var(--mono); }
.bell-dot   { width:7px; height:7px; border-radius:50%; background:var(--accent); flex-shrink:0; margin-top:5px; }
</style>

<script>
const CSRF = document.querySelector('meta[name="csrf-token"]')?.content;
let bellOpen = false;

function toggleBell(e) {
    e.stopPropagation();
    bellOpen = !bellOpen;
    const dropdown = document.getElementById('bellDropdown');
    dropdown.style.display = bellOpen ? 'block' : 'none';
    dropdown.classList.toggle('bell-open', bellOpen);
    if (bellOpen) loadNotifications();
}

document.addEventListener('click', function(e) {
    if (!document.getElementById('notifBell').contains(e.target)) {
        bellOpen = false;
        const dropdown = document.getElementById('bellDropdown');
        dropdown.style.display = 'none';
        dropdown.classList.remove('bell-open');
    }
});

function loadNotifications() {
    fetch('/notifications/latest', { headers: { 'Accept': 'application/json' } })
        .then(r => r.json())
        .then(data => {
            updateBadge(data.unread_count);
            renderList(data.notifications);
        })
        .catch(() => {
            document.getElementById('bellList').innerHTML =
                '<div style="padding:20px;text-align:center;color:var(--text-400);font-size:13px">Failed to load</div>';
        });
}

function updateBadge(count) {
    const badge    = document.getElementById('bellBadge');
    const dropBadge = document.getElementById('dropBadge');
    if (count > 0) {
        badge.style.display = 'flex';
        badge.textContent   = count > 99 ? '99+' : count;
    } else {
        badge.style.display = 'none';
    }
    dropBadge.textContent = count > 0 ? count + ' unread' : '';
    dropBadge.style.display = count > 0 ? 'inline' : 'none';
}

function renderList(notifications) {
    const list = document.getElementById('bellList');

    if (!notifications.length) {
        list.innerHTML = '<div style="padding:40px 20px;text-align:center;color:var(--text-400);font-size:13px">🎉 You\'re all caught up!</div>';
        return;
    }

    const colorMap = {
        accent: ['var(--accent-dim)',  'var(--accent)'],
        green:  ['var(--green-dim)',   'var(--green)'],
        red:    ['var(--red-dim)',     'var(--red)'],
        amber:  ['var(--amber-dim)',   'var(--amber)'],
        purple: ['var(--purple-dim)',  'var(--purple)'],
    };

    list.innerHTML = notifications.map(n => {
        const [bg, color] = colorMap[n.color] || colorMap.accent;
        const href = n.url || '/notifications';
        return `
            <a href="${href}" class="bell-item ${n.is_read ? '' : 'unread'}"
               onclick="markRead(${n.id})">
                <div class="bell-icon" style="background:${bg};color:${color}">
                    <svg fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24">
                        ${n.icon_svg}
                    </svg>
                </div>
                <div style="flex:1;min-width:0">
                    <div class="bell-title">${n.title}</div>
                    <div class="bell-msg">${n.message}</div>
                    <div class="bell-time">${n.time}</div>
                </div>
                ${!n.is_read ? '<div class="bell-dot"></div>' : ''}
            </a>`;
    }).join('');
}

function markRead(id) {
    fetch(`/notifications/${id}/read`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': CSRF, 'Content-Type': 'application/json' }
    }).then(() => loadNotifications());
}

function markAllRead() {
    fetch('/notifications/read-all', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': CSRF }
    }).then(() => loadNotifications());
}

// Poll every 30 seconds for new notifications
setInterval(() => {
    fetch('/notifications/latest', { headers: { 'Accept': 'application/json' } })
        .then(r => r.json())
        .then(data => updateBadge(data.unread_count));
}, 30000);

// Initial badge load
fetch('/notifications/latest', { headers: { 'Accept': 'application/json' } })
    .then(r => r.json())
    .then(data => updateBadge(data.unread_count));
</script>