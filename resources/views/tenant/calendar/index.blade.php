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
</style>
@endpush

@section('content')
<div class="page-head">
    <div>
        <div class="page-title">Calendar</div>
        <div class="page-sub">Follow-ups, tasks and reminders in one view.</div>
    </div>
</div>

<div class="cal-legend">
    <div class="cal-legend-item"><span class="cal-legend-dot" style="background:#378ADD"></span> Follow-up (scheduled)</div>
    <div class="cal-legend-item"><span class="cal-legend-dot" style="background:#1D9E75"></span> Follow-up (done)</div>
    <div class="cal-legend-item"><span class="cal-legend-dot" style="background:#E05252"></span> Follow-up (missed)</div>
    <div class="cal-legend-item"><span class="cal-legend-dot" style="background:#EF9F27"></span> Task / Reminder</div>
</div>

<div class="cal-card">
    <div id="calendar" data-tenant-tz="{{ auth()->user()->tenant->timezone ?? 'Asia/Kolkata' }}"></div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const el = document.getElementById('calendar');
    const calendar = new FullCalendar.Calendar(el, {
        initialView: 'dayGridMonth',
        timeZone: el.dataset.tenantTz || 'local',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,listWeek',
        },
        height: 'auto',
        events: function (info, successCallback, failureCallback) {
            fetch(`{{ route('tenant.calendar.events') }}?start=${info.startStr}&end=${info.endStr}`)
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
    });
    calendar.render();
});
</script>
@endpush
