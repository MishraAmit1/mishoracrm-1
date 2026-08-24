@extends('layouts.app')
@section('title', 'Edit Appointment')

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
@media(max-width:640px) { .fg2 { grid-template-columns:1fr; } }
</style>
@endpush

@section('content')

<div class="page-head">
    <div>
        <div style="font-size:12px;color:var(--text-300);margin-bottom:4px">
            <a href="{{ route('tenant.appointments.show', $appointment->id) }}" style="color:var(--text-300);text-decoration:none">Appointment</a>
            › Edit
        </div>
        <div class="page-title">Edit Appointment</div>
    </div>
    <a href="{{ route('tenant.appointments.show', $appointment->id) }}" class="btn btn-secondary">← Back</a>
</div>

@if($errors->any())
<div style="padding:10px 14px;background:var(--red-dim);border:1px solid rgba(255,82,87,.25);border-radius:var(--r-sm);margin-bottom:14px;font-size:13px;color:var(--red)">
    {{ $errors->first() }}
</div>
@endif

<form method="POST" action="{{ route('tenant.appointments.update', $appointment->id) }}">
@csrf
@method('PUT')
<div class="pf-card">
    <div class="pf-body">

        <div class="field">
            <label class="fl">Contact <span style="color:var(--red)">*</span></label>
            <select name="contact_id" class="fi" required>
                @foreach($contacts as $c)
                <option value="{{ $c->id }}" @selected($appointment->contact_id == $c->id)>{{ $c->name }}{{ $c->phone ? ' — ' . $c->phone : '' }}</option>
                @endforeach
            </select>
        </div>

        <div class="field">
            <label class="fl">Service <span style="font-weight:400;text-transform:none;color:var(--text-400)">(optional)</span></label>
            <select name="service_id" class="fi">
                <option value="">— None —</option>
                @foreach($services as $s)
                <option value="{{ $s->id }}" @selected($appointment->service_id == $s->id)>{{ $s->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="fg2">
            <div class="field">
                <label class="fl">Technician <span style="font-weight:400;text-transform:none;color:var(--text-400)">(optional)</span></label>
                <select name="assigned_to" class="fi">
                    <option value="">— Unassigned —</option>
                    @foreach($staffList as $u)
                    <option value="{{ $u->id }}" @selected($appointment->assigned_to == $u->id)>{{ $u->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label class="fl">Service Address <span style="font-weight:400;text-transform:none;color:var(--text-400)">(optional)</span></label>
                <input type="text" name="service_address" class="fi" value="{{ $appointment->service_address }}" placeholder="Job site address"/>
            </div>
        </div>

        <div class="fg2">
            <div class="field">
                <label class="fl">Date <span style="color:var(--red)">*</span></label>
                <input type="date" name="date" class="fi" value="{{ $appointment->starts_at->toDateString() }}" required/>
            </div>
            <div class="field">
                <label class="fl">Duration (minutes) <span style="color:var(--red)">*</span></label>
                <input type="number" name="duration_minutes" class="fi" min="5" value="{{ $appointment->starts_at->diffInMinutes($appointment->ends_at) }}" required/>
            </div>
        </div>

        <div class="field">
            <label class="fl">Time <span style="color:var(--red)">*</span></label>
            <input type="time" name="time" class="fi" value="{{ $appointment->starts_at->format('H:i') }}" required/>
        </div>

        <div class="field">
            <label class="fl">Notes</label>
            <textarea name="notes" class="fi" rows="3" style="resize:vertical">{{ $appointment->notes }}</textarea>
        </div>

    </div>
    <div class="pf-foot">
        <a href="{{ route('tenant.appointments.show', $appointment->id) }}" class="btn btn-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary">Save Changes</button>
    </div>
</div>
</form>

@endsection
