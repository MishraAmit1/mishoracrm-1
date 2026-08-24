@extends('layouts.app')
@section('title', 'Book Appointment')

@push('styles')
<style>
.pf-card  { background:var(--bg-surface); border:1px solid var(--border-default); border-radius:var(--r-lg); overflow:hidden; max-width:680px; }
.pf-body  { padding:24px; display:flex; flex-direction:column; gap:16px; }
.pf-foot  { padding:14px 24px; background:var(--bg-elevated); border-top:1px solid var(--border-subtle); display:flex; justify-content:space-between; align-items:center; }
.field    { display:flex; flex-direction:column; gap:6px; }
.fl       { font-size:12px; font-weight:600; color:var(--text-200); text-transform:uppercase; letter-spacing:.4px; }
.fi       { padding:9px 13px; background:var(--bg-input); border:1.5px solid var(--border-default); border-radius:var(--r-sm); color:var(--text-100); font-family:var(--font); font-size:14px; outline:none; transition:border-color .15s; width:100%; }
.fi:focus { border-color:var(--accent); box-shadow:0 0 0 3px var(--accent-dim); }
.fg2 { display:grid; grid-template-columns:1fr 1fr; gap:14px; }
.fe  { font-size:12px; color:var(--red); }
.slot-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:8px; }
.slot-btn { padding:8px 4px; text-align:center; border:1.5px solid var(--border-default); border-radius:6px; font-size:12px; font-family:var(--mono,monospace); color:var(--text-200); cursor:pointer; background:var(--bg-input); }
.slot-btn:hover, .slot-btn.sel { border-color:var(--accent); background:var(--accent-dim); color:var(--accent); }
@media(max-width:640px) { .fg2 { grid-template-columns:1fr; } }
</style>
@endpush

@section('content')

<div class="page-head">
    <div>
        <div style="font-size:12px;color:var(--text-300);margin-bottom:4px">
            <a href="{{ route('tenant.appointments.index') }}" style="color:var(--text-300);text-decoration:none">Appointments</a>
            › Book
        </div>
        <div class="page-title">Book Appointment</div>
    </div>
    <a href="{{ route('tenant.appointments.index') }}" class="btn btn-secondary">← Back</a>
</div>

@if($errors->any())
<div style="padding:10px 14px;background:var(--red-dim);border:1px solid rgba(255,82,87,.25);border-radius:var(--r-sm);margin-bottom:14px;font-size:13px;color:var(--red)">
    {{ $errors->first() }}
</div>
@endif

<form method="POST" action="{{ route('tenant.appointments.store') }}" id="apptForm">
@csrf
<div class="pf-card">
    <div class="pf-body">

        <div class="field">
            <label class="fl">Contact <span style="color:var(--red)">*</span></label>
            <select name="contact_id" class="fi" required>
                <option value="">— Select contact —</option>
                @foreach($contacts as $c)
                <option value="{{ $c->id }}">{{ $c->name }}{{ $c->phone ? ' — ' . $c->phone : '' }}</option>
                @endforeach
            </select>
        </div>

        <div class="field">
            <label class="fl">Service <span style="font-weight:400;text-transform:none;color:var(--text-400)">(optional)</span></label>
            <select name="service_id" class="fi">
                <option value="">— None —</option>
                @foreach($services as $s)
                <option value="{{ $s->id }}">{{ $s->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="fg2">
            <div class="field">
                <label class="fl">Technician <span style="font-weight:400;text-transform:none;color:var(--text-400)">(optional)</span></label>
                <select name="assigned_to" class="fi">
                    <option value="">— Unassigned —</option>
                    @foreach($staffList as $u)
                    <option value="{{ $u->id }}">{{ $u->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label class="fl">Service Address <span style="font-weight:400;text-transform:none;color:var(--text-400)">(optional)</span></label>
                <input type="text" name="service_address" class="fi" placeholder="Job site address"/>
            </div>
        </div>

        <div class="fg2">
            <div class="field">
                <label class="fl">Date <span style="color:var(--red)">*</span></label>
                <input type="date" name="date" id="dateInput" class="fi" min="{{ now()->toDateString() }}" value="{{ now()->toDateString() }}" required onchange="loadSlots()"/>
            </div>
            <div class="field">
                <label class="fl">Duration (minutes) <span style="color:var(--red)">*</span></label>
                <input type="number" name="duration_minutes" id="durationInput" class="fi" min="5" value="{{ $tenant->bookingSettings()['slot_duration_minutes'] }}" required onchange="loadSlots()"/>
            </div>
        </div>

        <div class="field">
            <label class="fl">Time <span style="color:var(--red)">*</span></label>
            <input type="hidden" name="time" id="timeInput" required/>
            <div class="slot-grid" id="slotsContainer">
                <span style="font-size:12.5px;color:var(--text-400)">Select date/duration to see available slots, or the booking module isn't configured yet — you can still type a time manually below.</span>
            </div>
            <input type="time" id="manualTimeInput" class="fi" style="margin-top:8px" onchange="document.getElementById('timeInput').value = this.value"/>
        </div>

        <div class="field">
            <label class="fl">Notes</label>
            <textarea name="notes" class="fi" rows="3" style="resize:vertical"></textarea>
        </div>

    </div>
    <div class="pf-foot">
        <a href="{{ route('tenant.appointments.index') }}" class="btn btn-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary">Book Appointment</button>
    </div>
</div>
</form>

<script>
function loadSlots(){
    const date = document.getElementById('dateInput').value;
    const duration = document.getElementById('durationInput').value;
    const container = document.getElementById('slotsContainer');
    if(!date || !duration) return;

    fetch(`{{ route('tenant.appointments.slots') }}?date=${date}&duration_minutes=${duration}`)
        .then(r => r.json())
        .then(data => {
            container.innerHTML = '';
            if(!data.slots || !data.slots.length){
                container.innerHTML = '<span style="font-size:12.5px;color:var(--text-400)">No configured slots for this date — type a time manually below.</span>';
                return;
            }
            data.slots.forEach(t => {
                const btn = document.createElement('div');
                btn.className = 'slot-btn';
                btn.textContent = t;
                btn.onclick = () => {
                    document.querySelectorAll('.slot-btn').forEach(b => b.classList.remove('sel'));
                    btn.classList.add('sel');
                    document.getElementById('timeInput').value = t;
                    document.getElementById('manualTimeInput').value = t;
                };
                container.appendChild(btn);
            });
        });
}
loadSlots();
</script>

@endsection
