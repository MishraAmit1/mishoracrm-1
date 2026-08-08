@extends('layouts.app')
@section('title', 'Calendar')

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.19.0/dist/tabler-icons.min.css">
<style>
/* ── Header ───────────────────────────────────────────────────────── */
.cal-head-actions { display:flex; align-items:center; gap:10px; flex-wrap:wrap; }
.cal-head-actions .input-group { width:auto; }
.cal-head-actions .input-group .form-control { min-width:190px; }

/* ── Legend chips ─────────────────────────────────────────────────── */
.cal-legend { display:flex; gap:8px; margin-bottom:16px; flex-wrap:wrap; }
.cal-chip {
    display:inline-flex; align-items:center; gap:6px;
    padding:5px 12px 5px 10px; border-radius:20px;
    font-size:11.5px; font-weight:600; white-space:nowrap;
    border:1px solid transparent;
}
.cal-chip i { font-size:13px; }
.cal-legend-toggle { display:none; }

/* ── Calendar card ────────────────────────────────────────────────── */
.cal-card {
    background:var(--bg-surface); border:1px solid var(--border-default);
    border-radius:var(--r-lg,16px); padding:20px;
    box-shadow:var(--shadow-sm);
}
#calendar { font-family:var(--font); }

.fc {
    --fc-border-color: var(--border-subtle);
    --fc-page-bg-color: var(--bg-surface);
    --fc-neutral-bg-color: var(--bg-elevated);
    --fc-list-event-hover-bg-color: var(--bg-elevated);
    --fc-today-bg-color: var(--accent-dim);
    --fc-event-text-color: #fff;
}
.fc a { color: inherit; }

/* Toolbar */
.fc .fc-toolbar.fc-header-toolbar { margin-bottom:18px !important; gap:10px; }
.fc .fc-toolbar-title { color:var(--text-100); font-size:17px; font-weight:600; letter-spacing:-.2px; }
.fc .fc-button {
    background:var(--bg-elevated); border:1px solid var(--border-default); color:var(--text-200);
    box-shadow:none; font-weight:500; text-transform:capitalize;
    padding:7px 13px; border-radius:8px; transition:background .15s,border-color .15s,color .15s;
}
.fc .fc-button:hover { background:var(--bg-hover); color:var(--text-100); border-color:var(--border-strong); }
.fc .fc-button:focus { box-shadow:0 0 0 3px var(--accent-dim); }
.fc .fc-button-primary:not(:disabled).fc-button-active,
.fc .fc-button-primary:not(:disabled):active {
    background:var(--accent); border-color:var(--accent); color:#fff;
}
.fc .fc-today-button:disabled { opacity:.4; }
.fc .fc-button-group { gap:2px; }
.fc .fc-icon { font-size:15px; }

/* Grid */
.fc-daygrid-day-number, .fc-col-header-cell-cushion { color:var(--text-200); font-weight:500; }
.fc-col-header-cell-cushion { padding:9px 4px; font-size:12px; text-transform:uppercase; letter-spacing:.4px; }
.fc-daygrid-day-frame { min-height:92px; }
.fc-day-today .fc-daygrid-day-number {
    background:var(--accent); color:#fff; border-radius:50%;
    width:22px; height:22px; display:inline-flex; align-items:center; justify-content:center;
    font-weight:600; margin:3px;
}

/* Events — rounded pills instead of default flat bars */
.fc-event { cursor:pointer; border:none; border-radius:6px; padding:1px 5px; font-weight:500; }
.fc-daygrid-event { margin-top:2px; }
.fc-daygrid-event-dot { border-width:4px; }
.fc-list-event:hover td { background:var(--bg-elevated); }
.fc-list-day-cushion { background:var(--bg-elevated) !important; font-weight:600; }
.fc-list-event-dot { border-width:5px; }
.fc-list-table td { border-color:var(--border-subtle); }

/* ── Quick-create modal ── */
.qc-backdrop {
    display:none; position:fixed; inset:0; background:rgba(10,12,20,.55); backdrop-filter:blur(2px);
    z-index:1000; align-items:center; justify-content:center; padding:20px; box-sizing:border-box;
}
.qc-backdrop.open { display:flex; }
.qc-modal {
    background:var(--bg-surface); border:1px solid var(--border-default);
    border-radius:var(--r-lg,16px); width:100%; max-width:400px; padding:22px;
    box-shadow:var(--shadow-lg); animation:qc-pop .18s var(--ease,ease-out);
}
@keyframes qc-pop { from{opacity:0;transform:translateY(6px) scale(.98)} to{opacity:1;transform:translateY(0) scale(1)} }
.qc-head { display:flex; align-items:center; gap:10px; margin-bottom:4px; }
.qc-head-icon {
    width:32px; height:32px; border-radius:9px; background:var(--accent-dim); color:var(--accent);
    display:flex; align-items:center; justify-content:center; font-size:16px; flex-shrink:0;
}
.qc-title { font-size:14.5px; font-weight:600; color:var(--text-100); }
.qc-sub { font-size:12px; color:var(--text-300); margin:2px 0 16px 42px; }
.qc-tabs {
    display:flex; gap:3px; margin-bottom:16px; padding:3px;
    background:var(--bg-elevated); border-radius:10px; border:1px solid var(--border-subtle);
}
.qc-tab {
    flex:1; padding:7px; border-radius:8px; border:none; background:transparent;
    color:var(--text-300); font-size:12.5px; font-weight:600; cursor:pointer; text-align:center;
    font-family:var(--font); transition:background .15s,color .15s;
}
.qc-tab.active { background:var(--bg-surface); color:var(--text-100); box-shadow:var(--shadow-sm); }
.qc-field { margin-bottom:13px; }
.qc-label {
    font-size:11px; font-weight:600; color:var(--text-300); text-transform:uppercase;
    letter-spacing:.5px; display:block; margin-bottom:6px;
}
.qc-input {
    width:100%; padding:9px 11px; background:var(--bg-input); border:1.5px solid var(--border-default);
    border-radius:8px; color:var(--text-100); font-family:var(--font); font-size:13px; outline:none;
    transition:border-color .15s,box-shadow .15s;
}
.qc-input:focus { border-color:var(--accent); box-shadow:0 0 0 3px var(--accent-dim); }
.qc-actions { display:flex; justify-content:flex-end; gap:8px; margin-top:18px; }

/* ── FAB ──────────────────────────────────────────────────────────── */
.cal-fab { display:none; }

/* ── Mobile agenda (hidden on desktop — FullCalendar's .cal-card is used there) ── */
#mobileAgenda { display:none; }

/* ── Mobile ───────────────────────────────────────────────────────── */
@media(max-width:768px) {
    .cal-head-actions { width:100%; }
    .cal-head-actions .input-group { width:100%; }
    .cal-head-actions .input-group .form-control { width:100%; min-width:0; height:42px; }
    .cal-head-actions .cal-add-btn { display:none; }

    /* Instructional subtitle is onboarding copy, not something a
       returning mobile user needs — dropping it buys back a full
       line of vertical space above the fold. */
    .page-head .page-sub { display:none; }
    .page-head { margin-bottom:14px; }

    /* Legend collapses behind a small toggle pill instead of a
       permanent strip — that one row of chrome was the single biggest
       contributor to the "everything is squeezed" feeling, so it only
       costs vertical space when someone actually wants it. */
    .cal-legend-toggle {
        display:flex; align-items:center; gap:7px;
        width:100%; padding:10px 14px; margin-bottom:12px;
        background:var(--bg-surface); border:1px solid var(--border-default);
        border-radius:var(--r-md,10px); color:var(--text-200);
        font-family:var(--font); font-size:13px; font-weight:600;
        cursor:pointer;
    }
    .cal-legend-toggle span { flex:1; text-align:left; }
    .cal-legend-toggle i:first-child { font-size:15px; color:var(--accent); }
    .cal-legend-chevron { font-size:15px; transition:transform .15s; }
    .cal-legend-toggle.open .cal-legend-chevron { transform:rotate(180deg); }

    .cal-legend {
        display:none; grid-template-columns:1fr 1fr; gap:8px;
        margin:-4px 0 14px; padding:12px; flex-wrap:unset;
        background:var(--bg-elevated); border:1px solid var(--border-subtle); border-radius:var(--r-md,10px);
    }
    .cal-legend.open { display:grid; }
    .cal-chip { justify-content:flex-start; padding:6px 10px; font-size:12px; white-space:normal; }

    /* Desktop's FullCalendar grid is swapped out entirely for a hand-built
       native-style agenda screen on mobile (see #mobileAgenda below) —
       reskinning FullCalendar's own DOM only goes so far, so mobile gets
       its own purpose-built markup instead of a shrunk desktop widget. */
    .cal-card { display:none; }
    #mobileAgenda { display:block; }

    /* ── Mobile agenda ── */
    .magenda-monthnav { display:flex; align-items:center; gap:10px; margin-bottom:16px; }
    .magenda-navbtn {
        width:36px; height:36px; border-radius:50%; flex-shrink:0;
        border:1px solid var(--border-default); background:var(--bg-elevated); color:var(--text-200);
        display:flex; align-items:center; justify-content:center; font-size:16px;
    }
    .magenda-navbtn:active { background:var(--bg-hover); }
    .magenda-monthlabel { flex:1; text-align:center; font-size:16.5px; font-weight:700; color:var(--text-100); letter-spacing:-.2px; }
    .magenda-todaybtn {
        padding:7px 13px; border-radius:18px; flex-shrink:0;
        border:1px solid var(--border-default); background:var(--bg-elevated); color:var(--text-200);
        font-size:11.5px; font-weight:600; font-family:var(--font);
    }
    .magenda-todaybtn.is-hidden { visibility:hidden; }

    .magenda-strip {
        display:flex; gap:8px; overflow-x:auto; -webkit-overflow-scrolling:touch;
        padding:2px 2px 8px; margin-bottom:18px; scrollbar-width:none;
        scroll-snap-type:x proximity;
    }
    .magenda-strip::-webkit-scrollbar { display:none; }
    .magenda-day {
        flex:0 0 auto; width:48px; padding:9px 0 10px; border-radius:16px;
        background:var(--bg-surface); border:1.5px solid var(--border-subtle);
        display:flex; flex-direction:column; align-items:center; gap:4px;
        position:relative; scroll-snap-align:center;
    }
    .magenda-day:active { background:var(--bg-hover); }
    .magenda-day-dow { font-size:10px; font-weight:700; color:var(--text-400); text-transform:uppercase; letter-spacing:.3px; }
    .magenda-day-num { font-size:16px; font-weight:700; color:var(--text-100); font-family:var(--mono); }
    .magenda-day.is-today { border-color:var(--accent); }
    .magenda-day.is-today .magenda-day-num { color:var(--accent); }
    .magenda-day.is-selected { background:var(--accent); border-color:var(--accent); }
    .magenda-day.is-selected .magenda-day-dow,
    .magenda-day.is-selected .magenda-day-num { color:#fff; }
    .magenda-day-dot { width:4px; height:4px; border-radius:50%; background:var(--accent); position:absolute; bottom:5px; }
    .magenda-day.is-selected .magenda-day-dot { background:#fff; }

    .magenda-list-head {
        display:flex; align-items:center; gap:8px; margin-bottom:12px;
        font-size:14px; font-weight:700; color:var(--text-100);
    }
    .magenda-list-count {
        font-size:11px; font-weight:700; color:var(--text-300);
        background:var(--bg-elevated); padding:2px 9px; border-radius:10px;
    }

    .magenda-event {
        display:flex; align-items:center; gap:12px; padding:13px 14px 13px 16px;
        background:var(--bg-surface); border:1px solid var(--border-subtle); border-radius:14px;
        margin-bottom:10px; position:relative; overflow:hidden;
    }
    .magenda-event:active { background:var(--bg-hover); }
    .magenda-event::before {
        content:''; position:absolute; left:0; top:0; bottom:0; width:4px;
        background:var(--ev-color, var(--accent));
    }
    .magenda-event-icon {
        width:38px; height:38px; border-radius:12px; flex-shrink:0;
        display:flex; align-items:center; justify-content:center; font-size:17px;
        background:var(--ev-bg, var(--accent-dim)); color:var(--ev-color, var(--accent));
    }
    .magenda-event-body { flex:1; min-width:0; }
    .magenda-event-title {
        font-size:14px; font-weight:600; color:var(--text-100);
        overflow:hidden; text-overflow:ellipsis; white-space:nowrap;
    }
    .magenda-event-meta { font-size:12px; color:var(--text-300); margin-top:2px; }
    .magenda-event-chevron { color:var(--text-400); font-size:16px; flex-shrink:0; }

    .magenda-empty { text-align:center; padding:48px 20px; }
    .magenda-empty i { font-size:32px; color:var(--text-400); margin-bottom:12px; display:block; }
    .magenda-empty-title { font-size:14px; font-weight:600; color:var(--text-200); margin-bottom:4px; }
    .magenda-empty-sub { font-size:12.5px; color:var(--text-300); }
    .magenda-empty-btn {
        margin-top:18px; display:inline-flex; align-items:center; gap:6px;
        padding:9px 18px; border-radius:20px; background:var(--accent); color:#fff;
        font-size:12.5px; font-weight:600; border:none; font-family:var(--font);
    }

    .magenda-skel {
        height:64px; border-radius:14px; margin-bottom:10px;
        background:var(--bg-elevated); opacity:.5; animation:magenda-pulse 1.1s ease-in-out infinite;
    }
    @keyframes magenda-pulse { 0%,100%{opacity:.35} 50%{opacity:.7} }

    .qc-backdrop { padding:16px; align-items:flex-end; }
    .qc-modal { max-width:100%; border-radius:16px 16px 0 0; padding-bottom:max(20px, env(safe-area-inset-bottom)); animation:qc-slide-up .2s var(--ease,ease-out); }
    @keyframes qc-slide-up { from{opacity:0;transform:translateY(24px)} to{opacity:1;transform:translateY(0)} }

    .cal-fab {
        display:flex; align-items:center; justify-content:center;
        position:fixed; right:18px; bottom:calc(96px + env(safe-area-inset-bottom));
        width:56px; height:56px; border-radius:50%;
        background:var(--accent); color:#fff; border:none;
        box-shadow:0 8px 24px rgba(0,0,0,.32), 0 0 0 1px var(--accent-hover) inset;
        cursor:pointer; z-index:60; font-size:24px;
        transition:transform .15s;
    }
    .cal-fab:active { transform:scale(.92); }
}
</style>
@endpush

@section('content')
<div class="page-head">
    <div>
        <div class="page-title">Calendar</div>
        <div class="page-sub">Follow-ups, tasks, reminders and deal close dates — drag to reschedule, click a date to add.</div>
    </div>
    <div class="cal-head-actions">
        @if($staffList->count())
        <div class="input-group">
            <span class="input-group-icon"><i class="ti ti-users" aria-hidden="true"></i></span>
            <select class="form-control" id="staffFilter">
                <option value="">All Staff (Team View)</option>
                @foreach($staffList as $staff)
                <option value="{{ $staff->id }}">{{ $staff->name }}</option>
                @endforeach
            </select>
        </div>
        @endif
        <button type="button" class="btn btn-primary cal-add-btn" onclick="qcOpen(new Date().toISOString().slice(0,10))">
            <i class="ti ti-plus" style="font-size:14px" aria-hidden="true"></i>
            Quick Add
        </button>
    </div>
</div>

{{-- Mobile: collapsed behind a toggle to free up vertical space. Desktop:
     always-visible inline row (toggle button is hidden via CSS there). --}}
<button type="button" class="cal-legend-toggle" id="legendToggle">
    <i class="ti ti-palette" aria-hidden="true"></i>
    <span>Legend</span>
    <i class="ti ti-chevron-down cal-legend-chevron" aria-hidden="true"></i>
</button>
<div class="cal-legend" id="calLegend">
    <span class="cal-chip" style="background:rgba(55,138,221,.12);color:#378ADD">
        <i class="ti ti-phone-outgoing" aria-hidden="true"></i> Follow-up · Scheduled
    </span>
    <span class="cal-chip" style="background:rgba(29,158,117,.12);color:#1D9E75">
        <i class="ti ti-circle-check" aria-hidden="true"></i> Follow-up · Done
    </span>
    <span class="cal-chip" style="background:rgba(224,82,82,.12);color:#E05252">
        <i class="ti ti-circle-x" aria-hidden="true"></i> Follow-up · Missed
    </span>
    <span class="cal-chip" style="background:rgba(239,159,39,.12);color:#BA7517">
        <i class="ti ti-clipboard-list" aria-hidden="true"></i> Task / Reminder
    </span>
    <span class="cal-chip" style="background:rgba(83,74,183,.12);color:#534AB7">
        <i class="ti ti-target-arrow" aria-hidden="true"></i> Deal · Expected Close
    </span>
</div>

<div class="cal-card">
    <div id="calendar" data-tenant-tz="{{ auth()->user()->tenant->timezone ?? 'Asia/Kolkata' }}"></div>
</div>

{{-- Mobile-only: hand-built agenda screen (date strip + day event cards)
     that replaces FullCalendar entirely on small screens. --}}
<div id="mobileAgenda" data-tenant-tz="{{ auth()->user()->tenant->timezone ?? 'Asia/Kolkata' }}">
    <div class="magenda-monthnav">
        <button type="button" class="magenda-navbtn" id="magendaPrev" title="Previous month">
            <i class="ti ti-chevron-left" aria-hidden="true"></i>
        </button>
        <div class="magenda-monthlabel" id="magendaMonthLabel"></div>
        <button type="button" class="magenda-navbtn" id="magendaNext" title="Next month">
            <i class="ti ti-chevron-right" aria-hidden="true"></i>
        </button>
        <button type="button" class="magenda-todaybtn" id="magendaToday">Today</button>
    </div>

    <div class="magenda-strip" id="magendaStrip"></div>

    <div class="magenda-list" id="magendaList"></div>
</div>

{{-- Mobile-only quick-add FAB: mobile defaults to the agenda/list view,
     which has no date cells to click, so this is the only way to add. --}}
<button type="button" class="cal-fab" id="calFab" title="Quick add">
    <i class="ti ti-plus" aria-hidden="true"></i>
</button>

{{-- Quick-create modal --}}
<div class="qc-backdrop" id="qcBackdrop">
    <div class="qc-modal">
        <div class="qc-head">
            <div class="qc-head-icon"><i class="ti ti-calendar-plus" aria-hidden="true"></i></div>
            <div class="qc-title">Quick Add</div>
        </div>
        <div class="qc-sub" id="qcDateLabel"></div>

        <div class="qc-tabs">
            <button type="button" class="qc-tab active" id="qcTabFollowup" onclick="qcSetKind('followup')">Follow-up</button>
            <button type="button" class="qc-tab" id="qcTabTask" onclick="qcSetKind('task')">Task</button>
        </div>

        <div class="qc-field" id="qcTypeField">
            <label class="qc-label">Type</label>
            <select class="qc-input" id="qcType">
                <option value="call">Phone Call</option>
                <option value="email">Email</option>
                <option value="whatsapp">WhatsApp</option>
                <option value="meeting">Meeting</option>
                <option value="other">Other</option>
            </select>
        </div>

        <div class="qc-field" id="qcTitleField" style="display:none">
            <label class="qc-label">Title</label>
            <input type="text" class="qc-input" id="qcTitle" placeholder="Task title">
        </div>

        <div class="qc-field">
            <label class="qc-label">Notes</label>
            <textarea class="qc-input" id="qcNotes" rows="2" placeholder="Optional"></textarea>
        </div>

        <div class="qc-actions">
            <button type="button" class="btn btn-secondary" onclick="qcClose()">Cancel</button>
            <button type="button" class="btn btn-primary" id="qcSaveBtn" onclick="qcSave()">
                <i class="ti ti-check" style="font-size:14px" aria-hidden="true"></i>
                Add
            </button>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
<script>
let qcKind = 'followup';
let qcDate = null;
let calendarInstance = null;

function qcSetKind(kind) {
    qcKind = kind;
    document.getElementById('qcTabFollowup').classList.toggle('active', kind === 'followup');
    document.getElementById('qcTabTask').classList.toggle('active', kind === 'task');
    document.getElementById('qcTypeField').style.display = kind === 'followup' ? 'block' : 'none';
    document.getElementById('qcTitleField').style.display = kind === 'task' ? 'block' : 'none';
}

function qcOpen(dateStr) {
    qcDate = dateStr;
    qcSetKind('followup');
    document.getElementById('qcDateLabel').textContent = new Date(dateStr).toLocaleDateString('en-IN', { day: 'numeric', month: 'long', year: 'numeric' });
    document.getElementById('qcTitle').value = '';
    document.getElementById('qcNotes').value = '';
    document.getElementById('qcBackdrop').classList.add('open');
}
function qcClose() {
    document.getElementById('qcBackdrop').classList.remove('open');
}

let isMobileView = false;

async function qcSave() {
    if (qcKind === 'task' && !document.getElementById('qcTitle').value.trim()) {
        showToast('Task title is required.', 'error');
        return;
    }
    const payload = {
        kind: qcKind,
        date: qcDate,
        notes: document.getElementById('qcNotes').value,
        type: document.getElementById('qcType').value,
        title: document.getElementById('qcTitle').value,
    };
    const res = await crmPost("{{ route('tenant.calendar.quick-create') }}", payload);
    if (res.ok) {
        showToast('Added to calendar.', 'success');
        qcClose();
        isMobileView ? fetchMobileMonth() : calendarInstance.refetchEvents();
    } else {
        showToast('Could not add — please try again.', 'error');
    }
}

/* ══════════════════════════════════════════════════════════════════
   Mobile agenda — date strip + day-grouped event cards.
   Fully custom (no FullCalendar) — hits the same events endpoint as
   the desktop calendar, but renders as a native-feeling list instead
   of a shrunk-down grid.
   ══════════════════════════════════════════════════════════════════ */
let mobileFocusDate = new Date();
let mobileSelectedDate = null;
let mobileEvents = [];

function pad2(n) { return String(n).padStart(2, '0'); }
function toDateStr(d) { return `${d.getFullYear()}-${pad2(d.getMonth() + 1)}-${pad2(d.getDate())}`; }
function eventDateStr(ev) { return (ev.start || '').slice(0, 10); }

function hexToRgba(hex, alpha) {
    hex = (hex || '#6378ff').replace('#', '');
    if (hex.length === 3) hex = hex.split('').map(c => c + c).join('');
    const r = parseInt(hex.substring(0, 2), 16) || 0;
    const g = parseInt(hex.substring(2, 4), 16) || 0;
    const b = parseInt(hex.substring(4, 6), 16) || 0;
    return `rgba(${r},${g},${b},${alpha})`;
}

function eventIcon(ev) {
    const title = ev.title || '';
    if (/^call/i.test(title)) return 'ti-phone';
    if (/^email/i.test(title)) return 'ti-mail';
    if (/^whatsapp/i.test(title)) return 'ti-brand-whatsapp';
    if (/^meeting/i.test(title)) return 'ti-users';
    const map = { task: 'ti-clipboard-list', reminder: 'ti-bell', deal: 'ti-target-arrow', followup: 'ti-calendar-event' };
    return map[ev.extendedProps?.type] || 'ti-calendar-event';
}

function eventMeta(ev) {
    if (ev.allDay) {
        const map = { task: 'Task', reminder: 'Reminder', deal: 'Expected close' };
        return map[ev.extendedProps?.type] || 'All day';
    }
    const d = ev.start ? new Date(ev.start) : null;
    if (!d || isNaN(d)) return '';
    return d.toLocaleTimeString('en-IN', { hour: 'numeric', minute: '2-digit', hour12: true });
}

async function fetchMobileMonth() {
    const first = new Date(mobileFocusDate.getFullYear(), mobileFocusDate.getMonth(), 1);
    const last  = new Date(mobileFocusDate.getFullYear(), mobileFocusDate.getMonth() + 1, 0);
    const staffId = document.getElementById('staffFilter')?.value || '';

    document.getElementById('magendaList').innerHTML =
        '<div class="magenda-skel"></div><div class="magenda-skel"></div><div class="magenda-skel"></div>';

    try {
        const res = await fetch(`{{ route('tenant.calendar.events') }}?start=${toDateStr(first)}&end=${toDateStr(last)}&staff_id=${staffId}`);
        mobileEvents = await res.json();
    } catch (e) {
        mobileEvents = [];
    }

    renderMobileStrip();
    renderMobileList();
}

function renderMobileStrip() {
    const strip = document.getElementById('magendaStrip');
    const daysInMonth = new Date(mobileFocusDate.getFullYear(), mobileFocusDate.getMonth() + 1, 0).getDate();
    const todayStr = toDateStr(new Date());

    document.getElementById('magendaMonthLabel').textContent =
        mobileFocusDate.toLocaleDateString('en-IN', { month: 'long', year: 'numeric' });
    document.getElementById('magendaToday')?.classList.toggle('is-hidden', mobileSelectedDate === todayStr);

    let html = '';
    for (let d = 1; d <= daysInMonth; d++) {
        const date    = new Date(mobileFocusDate.getFullYear(), mobileFocusDate.getMonth(), d);
        const dateStr = toDateStr(date);
        const hasEvents = mobileEvents.some(ev => eventDateStr(ev) === dateStr);
        html += `
            <div class="magenda-day ${dateStr === todayStr ? 'is-today' : ''} ${dateStr === mobileSelectedDate ? 'is-selected' : ''}" data-date="${dateStr}">
                <div class="magenda-day-dow">${date.toLocaleDateString('en-IN', { weekday: 'short' })}</div>
                <div class="magenda-day-num">${d}</div>
                ${hasEvents ? '<span class="magenda-day-dot"></span>' : ''}
            </div>`;
    }
    strip.innerHTML = html;
    strip.querySelector('.is-selected')?.scrollIntoView({ block: 'nearest', inline: 'center' });
}

function renderMobileList() {
    const list = document.getElementById('magendaList');
    const dayEvents = mobileEvents
        .filter(ev => eventDateStr(ev) === mobileSelectedDate)
        .sort((a, b) => (a.start || '').localeCompare(b.start || ''));

    const label = new Date(mobileSelectedDate + 'T00:00:00')
        .toLocaleDateString('en-IN', { weekday: 'long', day: 'numeric', month: 'long' });

    if (!dayEvents.length) {
        list.innerHTML = `
            <div class="magenda-list-head">${label}</div>
            <div class="magenda-empty">
                <i class="ti ti-calendar-off" aria-hidden="true"></i>
                <div class="magenda-empty-title">Nothing scheduled</div>
                <div class="magenda-empty-sub">No follow-ups, tasks or reminders on this day.</div>
                <button type="button" class="magenda-empty-btn" onclick="qcOpen('${mobileSelectedDate}')">
                    <i class="ti ti-plus" aria-hidden="true"></i> Quick Add
                </button>
            </div>`;
        return;
    }

    let html = `<div class="magenda-list-head">${label} <span class="magenda-list-count">${dayEvents.length}</span></div>`;
    dayEvents.forEach(ev => {
        const color = ev.color || '#6378ff';
        const title = (ev.title || '').replace(/"/g, '&quot;');
        html += `
            <div class="magenda-event" style="--ev-color:${color};--ev-bg:${hexToRgba(color, .14)}" data-url="${ev.url || ''}" data-title="${title}">
                <div class="magenda-event-icon"><i class="ti ${eventIcon(ev)}" aria-hidden="true"></i></div>
                <div class="magenda-event-body">
                    <div class="magenda-event-title">${ev.title || ''}</div>
                    <div class="magenda-event-meta">${eventMeta(ev)}</div>
                </div>
                <i class="ti ti-chevron-right magenda-event-chevron" aria-hidden="true"></i>
            </div>`;
    });
    list.innerHTML = html;
}

function initMobileAgenda() {
    isMobileView = true;
    mobileSelectedDate = toDateStr(new Date());

    document.getElementById('magendaPrev')?.addEventListener('click', () => {
        mobileFocusDate = new Date(mobileFocusDate.getFullYear(), mobileFocusDate.getMonth() - 1, 1);
        fetchMobileMonth();
    });
    document.getElementById('magendaNext')?.addEventListener('click', () => {
        mobileFocusDate = new Date(mobileFocusDate.getFullYear(), mobileFocusDate.getMonth() + 1, 1);
        fetchMobileMonth();
    });
    document.getElementById('magendaToday')?.addEventListener('click', () => {
        mobileFocusDate = new Date();
        mobileSelectedDate = toDateStr(new Date());
        fetchMobileMonth();
    });
    document.getElementById('magendaStrip')?.addEventListener('click', (e) => {
        const day = e.target.closest('.magenda-day');
        if (!day) return;
        mobileSelectedDate = day.dataset.date;
        renderMobileStrip();
        renderMobileList();
    });
    document.getElementById('magendaList')?.addEventListener('click', (e) => {
        const card = e.target.closest('.magenda-event');
        if (!card) return;
        if (card.dataset.url) {
            window.location.href = card.dataset.url;
        } else {
            showToast(card.dataset.title, 'info');
        }
    });
    document.getElementById('calFab')?.addEventListener('click', () => qcOpen(mobileSelectedDate));
    document.getElementById('staffFilter')?.addEventListener('change', fetchMobileMonth);

    fetchMobileMonth();
}

/* ══════════════════════════════════════════════════════════════════
   Desktop — FullCalendar month/week/agenda grid.
   ══════════════════════════════════════════════════════════════════ */
function initDesktopCalendar() {
    const el = document.getElementById('calendar');

    calendarInstance = new FullCalendar.Calendar(el, {
        initialView: 'dayGridMonth',
        timeZone: el.dataset.tenantTz || 'local',
        editable: true,
        headerToolbar: { left: 'prev,next today', center: 'title', right: 'dayGridMonth,timeGridWeek,listWeek' },
        footerToolbar: false,
        buttonText: { today: 'Today', month: 'Month', week: 'Week', list: 'Agenda' },
        height: 'auto',
        dayMaxEvents: 3,
        events: function (info, successCallback, failureCallback) {
            const staffId = document.getElementById('staffFilter')?.value || '';
            fetch(`{{ route('tenant.calendar.events') }}?start=${info.startStr}&end=${info.endStr}&staff_id=${staffId}`)
                .then(res => res.json())
                .then(successCallback)
                .catch(failureCallback);
        },
        // List view repeats "all-day" on every row when most events are
        // all-day (tasks, deals) — blank it so only real times (e.g. a
        // follow-up's actual hour) stand out. Cell stays in the DOM so
        // the table's column alignment doesn't shift row to row.
        eventDidMount: function (info) {
            if (info.event.allDay) {
                const timeEl = info.el.querySelector('.fc-list-event-time');
                if (timeEl) timeEl.textContent = '';
            }
        },
        eventClick: function (info) {
            info.jsEvent.preventDefault();
            if (info.event.url) {
                window.location.href = info.event.url;
            } else {
                showToast(info.event.title, 'info');
            }
        },
        dateClick: function (info) {
            qcOpen(info.dateStr);
        },
        eventDrop: async function (info) {
            const type = info.event.extendedProps.type;
            const recordId = info.event.extendedProps.recordId;
            if (!type || !recordId) { info.revert(); return; }

            const res = await crmPost("{{ route('tenant.calendar.reschedule') }}", {
                type, id: recordId, start: info.event.startStr,
            });
            if (res.ok) {
                showToast('Rescheduled.', 'success');
            } else {
                showToast(res.message || 'Could not reschedule.', 'error');
                info.revert();
            }
        },
    });
    calendarInstance.render();

    document.getElementById('staffFilter')?.addEventListener('change', function () {
        calendarInstance.refetchEvents();
    });
}

document.addEventListener('DOMContentLoaded', function () {
    document.getElementById('legendToggle')?.addEventListener('click', function () {
        document.getElementById('calLegend')?.classList.toggle('open');
        this.classList.toggle('open');
    });

    // Month grid needs real screen width to be usable — mobile gets a
    // purpose-built agenda screen instead of a shrunk desktop widget.
    const isMobile = window.matchMedia('(max-width:768px)').matches;

    if (isMobile) {
        initMobileAgenda();
    } else {
        initDesktopCalendar();
    }
});
</script>
@endpush
