<!DOCTYPE html>
<html lang="en" data-theme="{{ Auth::user()?->tenant?->settings['theme'] ?? 'dark' }}">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<meta name="csrf-token" content="{{ csrf_token() }}"/>
<title>@yield('title', 'Dashboard') — {{ Auth::user()?->tenant?->name ?? 'CrmPro' }}</title>

<link rel="preconnect" href="https://fonts.googleapis.com"/>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="{{ asset('css/app.css') }}"/>

@stack('styles')
</head>
<body>

<div class="app-shell">

    {{-- ── Sidebar component ────────────────────────────────────── --}}
    @include('components.sidebar')

    {{-- ── Main area ────────────────────────────────────────────── --}}
    <div class="main-area" id="mainArea">

        {{-- Topbar component --}}
        @include('components.topbar')

        {{-- Flash messages --}}
        @if(session('success'))
        <div class="flash flash-success" id="flashMsg">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            {{ session('success') }}
            <button onclick="this.parentElement.remove()" class="flash-close">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        @endif

        @if(session('error'))
        <div class="flash flash-error" id="flashMsg">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
            </svg>
            {{ session('error') }}
            <button onclick="this.parentElement.remove()" class="flash-close">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        @endif

        {{-- Page content --}}
        <main class="page-body">
            @yield('content')
        </main>

    </div>{{-- /main-area --}}

</div>{{-- /app-shell --}}

{{-- ── Global styles ─────────────────────────────────────────────── --}}
<style>
/* Flash messages */
.flash {
    display: flex; align-items: center; gap: 10px;
    padding: 12px 20px;
    font-size: 13.5px; font-weight: 500;
    border-bottom: 1px solid transparent;
    animation: slideDown 0.3s var(--ease) both;
}
@keyframes slideDown {
    from { opacity: 0; transform: translateY(-8px); }
    to   { opacity: 1; transform: translateY(0); }
}
.flash svg { width: 16px; height: 16px; flex-shrink: 0; }
.flash-success {
    background: rgba(45,212,160,0.08);
    border-color: rgba(45,212,160,0.2);
    color: var(--green);
}
.flash-error {
    background: rgba(255,82,87,0.08);
    border-color: rgba(255,82,87,0.2);
    color: var(--red);
}
.flash-close {
    margin-left: auto; background: none; border: none;
    cursor: pointer; color: inherit; opacity: 0.6;
    padding: 0; display: flex;
    transition: opacity 0.15s;
}
.flash-close:hover { opacity: 1; }
.flash-close svg { width: 14px; height: 14px; }
</style>

{{-- ── Global scripts ────────────────────────────────────────────── --}}
<script>
// ── Theme ─────────────────────────────────────────────────────────
function toggleTheme() {
    const html   = document.documentElement;
    const isDark = html.dataset.theme === 'dark';
    html.dataset.theme = isDark ? 'light' : 'dark';
    document.getElementById('ico-moon').style.display = isDark ? 'none' : '';
    document.getElementById('ico-sun').style.display  = isDark ? ''     : 'none';
    localStorage.setItem('crm_theme', isDark ? 'light' : 'dark');
}

// Apply saved theme on load
(function () {
    const saved = localStorage.getItem('crm_theme');
    if (!saved) return;
    document.documentElement.dataset.theme = saved;
    if (saved === 'light') {
        const moon = document.getElementById('ico-moon');
        const sun  = document.getElementById('ico-sun');
        if (moon) moon.style.display = 'none';
        if (sun)  sun.style.display  = '';
    }
})();

// ── Sidebar ───────────────────────────────────────────────────────
let sbCollapsed = localStorage.getItem('crm_sb') === '1';
const sidebar   = document.getElementById('sidebar');
const mainArea  = document.getElementById('mainArea');
const overlay   = document.getElementById('sbOverlay');

function applyCollapse() {
    sidebar.classList.toggle('collapsed', sbCollapsed);
    mainArea.classList.toggle('collapsed', sbCollapsed);
}
applyCollapse();

function toggleSidebar() {
    if (window.innerWidth <= 768) {
        const isOpen = sidebar.classList.toggle('mobile-open');
        overlay.classList.toggle('show', isOpen);
    } else {
        sbCollapsed = !sbCollapsed;
        localStorage.setItem('crm_sb', sbCollapsed ? '1' : '0');
        applyCollapse();
    }
}

function closeMobile() {
    sidebar.classList.remove('mobile-open');
    overlay.classList.remove('show');
}

window.addEventListener('resize', function () {
    if (window.innerWidth > 768) closeMobile();
});

// ── Submenus ──────────────────────────────────────────────────────
function toggleSub(id, btn) {
    const sub    = document.getElementById(id);
    const isOpen = sub.classList.toggle('open');
    btn.classList.toggle('sub-open', isOpen);
}

// ── Dropdowns ─────────────────────────────────────────────────────
function toggleDrop(id) {
    document.querySelectorAll('.drop-menu').forEach(m => {
        if (m.id !== id) m.classList.remove('open');
    });
    document.getElementById(id)?.classList.toggle('open');
}

document.addEventListener('click', function (e) {
    const insideDrop    = e.target.closest('.drop-menu');
    const insideTrigger = e.target.closest('[onclick*="toggleDrop"]');
    if (!insideDrop && !insideTrigger) {
        document.querySelectorAll('.drop-menu').forEach(m => m.classList.remove('open'));
    }
});

// ── Global search ─────────────────────────────────────────────────
function handleSearch(e) {
    if (e.key === 'Enter') {
        const q = e.target.value.trim();
        if (q) window.location.href = '/search?q=' + encodeURIComponent(q);
    }
    if (e.key === 'Escape') e.target.blur();
}

// ⌘K / Ctrl+K shortcut
document.addEventListener('keydown', function (e) {
    if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
        e.preventDefault();
        document.getElementById('globalSearch')?.focus();
    }
});

// ── Auto dismiss flash ────────────────────────────────────────────
const flash = document.getElementById('flashMsg');
if (flash) {
    setTimeout(() => {
        flash.style.transition = 'opacity 0.4s';
        flash.style.opacity    = '0';
        setTimeout(() => flash.remove(), 400);
    }, 4000);
}

// ── Global CSRF helper ────────────────────────────────────────────
window.CrmCsrf = document.querySelector('meta[name="csrf-token"]')?.content;

window.crmPost = async function (url, data = {}) {
    const res = await fetch(url, {
        method:  'POST',
        headers: {
            'Content-Type':  'application/json',
            'X-CSRF-TOKEN':  window.CrmCsrf,
            'Accept':        'application/json',
        },
        body: JSON.stringify(data),
    });
    return res.json();
};
</script>

@stack('scripts')

</body>
</html>