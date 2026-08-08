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

/* ── Mobile ───────────────────────────────────────────────────────── */
@media(max-width:768px) {
    .cal-head-actions { width:100%; }
    .cal-head-actions .input-group { width:100%; }
    .cal-head-actions .input-group .form-control { width:100%; min-width:0; }
    .cal-head-actions .cal-add-btn { display:none; }

    /* Instructional subtitle is onboarding copy, not something a
       returning mobile user needs — dropping it buys back a full
       line of vertical space above the fold. */
    .page-head .page-sub { display:none; }

    /* Single scrollable strip instead of wrapping onto a second line —
       the dot + label pairs are still all reachable, just via a swipe
       instead of eating extra vertical space. */
    .cal-legend {
        flex-wrap:nowrap; overflow-x:auto; -webkit-overflow-scrolling:touch;
        gap:8px; margin-bottom:12px; padding-bottom:2px;
        scrollbar-width:none;
    }
    .cal-legend::-webkit-scrollbar { display:none; }
    .cal-chip { flex:0 0 auto; }

    .cal-card { padding:12px; border-radius:var(--r-md,10px); }

    /* One row — prev/next/today on the left, title centered — instead
       of stacking nav and title on separate rows. Narrow screens fit
       this fine since there's no right-side chunk competing for room. */
    .fc .fc-toolbar.fc-header-toolbar {
        flex-wrap:wrap;
        gap:8px;
        margin-bottom:14px !important;
    }
    .fc .fc-toolbar-title { font-size:15px; }
    .fc .fc-button { padding:6px 10px; font-size:12px; }
    .fc .fc-button-group { flex-wrap:wrap; justify-content:center; }
    .fc .fc-today-button { text-transform:capitalize; }

    /* Sticky bottom bar (not a footer you have to scroll a long agenda
       list to reach) — reads like a native app's tab bar and stays
       reachable while browsing today's events. */
    .fc .fc-footer-toolbar {
        position:sticky; bottom:0;
        background:var(--bg-surface);
        border-top:1px solid var(--border-subtle);
        margin:14px -12px -12px !important;
        padding:10px 12px !important;
        z-index:40;
    }
    .fc .fc-footer-toolbar .fc-toolbar-chunk:first-child,
    .fc .fc-footer-toolbar .fc-toolbar-chunk:last-child { flex:0 0 0 !important; }
    .fc .fc-footer-toolbar .fc-toolbar-chunk:nth-child(2) { flex:1 1 auto !important; }
    .fc .fc-footer-toolbar .fc-button-group {
        display:flex !important; flex-direction:row !important; width:100% !important;
    }
    .fc .fc-footer-toolbar .fc-button-group .fc-button { flex:1 !important; }

    /* Blanked-out (not removed) all-day time cell — see eventDidMount.
       Keeping the cell preserves column alignment with timed events
       that still show a real time. */
    .fc-list-event-time:empty { padding:0; width:0; }

    .fc .fc-daygrid-day-number { font-size:11px; padding:4px; }
    .fc .fc-col-header-cell-cushion { font-size:11px; padding:6px 2px; }
    .fc-event { font-size:10.5px; padding:1px 2px; }
    .fc-daygrid-event-dot { margin:0 3px; }
    /* Long-press-to-drag is unreliable on touch inside a cramped month
       grid — the list view below is what mobile users actually get,
       so month-grid dragging is a desktop-only affordance anyway. */
    .fc-daygrid-day-frame { min-height:64px; }

    .fc-list-event-title, .fc-list-event-time { font-size:12.5px; }
    .fc-list-day-cushion { font-size:12px; padding:8px 10px !important; }

    .qc-backdrop { padding:16px; align-items:flex-end; }
    .qc-modal { max-width:100%; border-radius:16px 16px 0 0; padding-bottom:max(20px, env(safe-area-inset-bottom)); animation:qc-slide-up .2s var(--ease,ease-out); }
    @keyframes qc-slide-up { from{opacity:0;transform:translateY(24px)} to{opacity:1;transform:translateY(0)} }

    .cal-fab {
        display:flex; align-items:center; justify-content:center;
        position:fixed; right:20px; bottom:calc(80px + env(safe-area-inset-bottom));
        width:54px; height:54px; border-radius:50%;
        background:var(--accent); color:#fff; border:none;
        box-shadow:0 8px 24px rgba(0,0,0,.32), 0 0 0 1px var(--accent-hover) inset;
        cursor:pointer; z-index:60; font-size:22px;
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

<div class="cal-legend">
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

{{-- Mobile-only quick-add FAB: mobile defaults to the agenda/list view,
     which has no date cells to click, so this is the only way to add. --}}
<button type="button" class="cal-fab" id="calFab" onclick="qcOpen(new Date().toISOString().slice(0,10))" title="Quick add">
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
        calendarInstance.refetchEvents();
    } else {
        showToast('Could not add — please try again.', 'error');
    }
}

document.addEventListener('DOMContentLoaded', function () {
    const el = document.getElementById('calendar');
    // Month grid needs real screen width to be usable — on a phone a
    // native calendar app shows an agenda list by default, so mirror
    // that instead of cramming a 7-column grid into ~340px.
    const isMobile = window.matchMedia('(max-width:768px)').matches;

    calendarInstance = new FullCalendar.Calendar(el, {
        initialView: isMobile ? 'listWeek' : 'dayGridMonth',
        timeZone: el.dataset.tenantTz || 'local',
        editable: !isMobile,
        headerToolbar: isMobile
            ? { left: 'prev,next today', center: 'title', right: '' }
            : { left: 'prev,next today', center: 'title', right: 'dayGridMonth,timeGridWeek,listWeek' },
        footerToolbar: isMobile
            ? { left: '', center: 'dayGridMonth,timeGridWeek,listWeek', right: '' }
            : false,
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
});
</script>
@endpush
