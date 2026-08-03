@extends('layouts.app')
@section('title', 'Calendar')

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.css" rel="stylesheet">
<style>
.cal-card{background:var(--bg-surface);border:1px solid var(--border-default);border-radius:14px;padding:18px}
.cal-legend{display:flex;gap:16px;margin-bottom:14px;flex-wrap:wrap}
.cal-legend-item{display:flex;align-items:center;gap:6px;font-size:12px;color:var(--text-300)}
.cal-legend-dot{width:9px;height:9px;border-radius:50%}
#calendar{font-family:var(--font)}
.fc{--fc-border-color:var(--border-subtle);--fc-page-bg-color:var(--bg-surface);--fc-neutral-bg-color:var(--bg-elevated);--fc-list-event-hover-bg-color:var(--bg-elevated);--fc-today-bg-color:var(--accent-dim);}
.fc .fc-button{background:var(--bg-elevated);border-color:var(--border-default);color:var(--text-100);box-shadow:none}
.fc .fc-button:hover{background:var(--bg-input)}
.fc .fc-button-primary:not(:disabled).fc-button-active{background:var(--accent);border-color:var(--accent);color:#fff}
.fc .fc-toolbar-title{color:var(--text-100);font-size:16px}
.fc-daygrid-day-number, .fc-col-header-cell-cushion{color:var(--text-200)}
.fc-event{cursor:pointer;border:none}

/* ── Quick-create modal ── */
.qc-backdrop{display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:1000;align-items:center;justify-content:center;padding:20px;box-sizing:border-box}
.qc-backdrop.open{display:flex}
.qc-modal{background:var(--bg-surface);border:1px solid var(--border-default);border-radius:14px;width:100%;max-width:380px;padding:20px}
.qc-title{font-size:14px;font-weight:700;color:var(--text-100);margin-bottom:4px}
.qc-sub{font-size:12px;color:var(--text-300);margin-bottom:14px}
.qc-tabs{display:flex;gap:6px;margin-bottom:14px}
.qc-tab{flex:1;padding:7px;border-radius:8px;border:1.5px solid var(--border-default);background:var(--bg-elevated);color:var(--text-200);font-size:12.5px;font-weight:600;cursor:pointer;text-align:center}
.qc-tab.active{background:var(--accent);border-color:var(--accent);color:#fff}
.qc-field{margin-bottom:12px}
.qc-label{font-size:11.5px;font-weight:600;color:var(--text-200);text-transform:uppercase;letter-spacing:.4px;display:block;margin-bottom:5px}
.qc-input{width:100%;padding:8px 10px;background:var(--bg-input);border:1.5px solid var(--border-default);border-radius:7px;color:var(--text-100);font-family:var(--font);font-size:13px}
.qc-actions{display:flex;justify-content:flex-end;gap:8px;margin-top:16px}
.cal-fab{display:none}

/* ── Mobile ───────────────────────────────────────────────────────── */
@media(max-width:768px) {
    .page-head select#staffFilter { width:100%; min-width:0; }

    .cal-legend { gap:10px; margin-bottom:10px; }
    .cal-legend-item { font-size:11px; gap:5px; }

    .cal-card { padding:10px; border-radius:12px; }

    /* Stack the toolbar into three centered rows — title, then nav,
       then view switcher — instead of squeezing all three into one
       row where the buttons wrap mid-word or spill off screen. */
    .fc .fc-toolbar.fc-header-toolbar {
        flex-direction:column;
        align-items:stretch;
        gap:10px;
        margin-bottom:14px !important;
    }
    .fc .fc-toolbar-chunk { display:flex; justify-content:center; }
    .fc .fc-toolbar-title { font-size:15px; text-align:center; }
    .fc .fc-button { padding:6px 10px; font-size:12px; }
    .fc .fc-button-group { flex-wrap:wrap; justify-content:center; }
    .fc .fc-today-button { text-transform:capitalize; }

    /* Footer view-switcher reads like a native app's segmented tab bar.
       footerToolbar only populates the center slot, but FullCalendar
       still renders empty left/right chunks and flexes all three
       equally — leaving the (empty) outer two eating 2/3 of the width.
       Only the center chunk, which actually holds the buttons, should
       grow; the empty ones must collapse to 0. */
    .fc .fc-footer-toolbar { margin-top:14px !important; }
    .fc .fc-footer-toolbar .fc-toolbar-chunk:first-child,
    .fc .fc-footer-toolbar .fc-toolbar-chunk:last-child { flex:0 0 0 !important; }
    .fc .fc-footer-toolbar .fc-toolbar-chunk:nth-child(2) { flex:1 1 auto !important; }
    .fc .fc-footer-toolbar .fc-button-group {
        display:flex !important; flex-direction:row !important; width:100% !important;
    }
    .fc .fc-footer-toolbar .fc-button-group .fc-button { flex:1 !important; }

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
    .qc-modal { max-width:100%; border-radius:16px 16px 0 0; padding-bottom:max(20px, env(safe-area-inset-bottom)); }

    .cal-fab {
        display:flex; align-items:center; justify-content:center;
        position:fixed; right:20px; bottom:calc(24px + env(safe-area-inset-bottom));
        width:52px; height:52px; border-radius:50%;
        background:var(--accent); color:#fff; border:none;
        box-shadow:0 8px 24px rgba(0,0,0,.32);
        cursor:pointer; z-index:60;
    }
    .cal-fab svg { width:24px; height:24px; }
}
</style>
@endpush

@section('content')
<div class="page-head">
    <div>
        <div class="page-title">Calendar</div>
        <div class="page-sub">Follow-ups, tasks, reminders and deal close dates — drag to reschedule, click a date to add.</div>
    </div>
    @if($staffList->count())
    <div>
        <select class="fi" id="staffFilter" style="min-width:180px">
            <option value="">All Staff (Team View)</option>
            @foreach($staffList as $staff)
            <option value="{{ $staff->id }}">{{ $staff->name }}</option>
            @endforeach
        </select>
    </div>
    @endif
</div>

<div class="cal-legend">
    <div class="cal-legend-item"><span class="cal-legend-dot" style="background:#378ADD"></span> Follow-up (scheduled)</div>
    <div class="cal-legend-item"><span class="cal-legend-dot" style="background:#1D9E75"></span> Follow-up (done)</div>
    <div class="cal-legend-item"><span class="cal-legend-dot" style="background:#E05252"></span> Follow-up (missed)</div>
    <div class="cal-legend-item"><span class="cal-legend-dot" style="background:#EF9F27"></span> Task / Reminder</div>
    <div class="cal-legend-item"><span class="cal-legend-dot" style="background:#534AB7"></span> Deal expected close</div>
</div>

<div class="cal-card">
    <div id="calendar" data-tenant-tz="{{ auth()->user()->tenant->timezone ?? 'Asia/Kolkata' }}"></div>
</div>

{{-- Mobile-only quick-add FAB: mobile defaults to the agenda/list view,
     which has no date cells to click, so this is the only way to add. --}}
<button type="button" class="cal-fab" id="calFab" onclick="qcOpen(new Date().toISOString().slice(0,10))" title="Quick add">
    <svg fill="none" stroke="currentColor" stroke-width="2.25" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
</button>

{{-- Quick-create modal --}}
<div class="qc-backdrop" id="qcBackdrop">
    <div class="qc-modal">
        <div class="qc-title">Quick Add</div>
        <div class="qc-sub" id="qcDateLabel"></div>

        <div class="qc-tabs">
            <div class="qc-tab active" id="qcTabFollowup" onclick="qcSetKind('followup')">Follow-up</div>
            <div class="qc-tab" id="qcTabTask" onclick="qcSetKind('task')">Task</div>
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
            <button type="button" class="btn btn-primary" id="qcSaveBtn" onclick="qcSave()">Add</button>
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
            ? { left: 'prev,next', center: 'title', right: 'today' }
            : { left: 'prev,next today', center: 'title', right: 'dayGridMonth,timeGridWeek,listWeek' },
        footerToolbar: isMobile
            ? { left: '', center: 'dayGridMonth,timeGridWeek,listWeek', right: '' }
            : false,
        buttonText: { today: 'Today', month: 'Month', week: 'Week', list: 'Agenda' },
        height: 'auto',
        events: function (info, successCallback, failureCallback) {
            const staffId = document.getElementById('staffFilter')?.value || '';
            fetch(`{{ route('tenant.calendar.events') }}?start=${info.startStr}&end=${info.endStr}&staff_id=${staffId}`)
                .then(res => res.json())
                .then(successCallback)
                .catch(failureCallback);
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
