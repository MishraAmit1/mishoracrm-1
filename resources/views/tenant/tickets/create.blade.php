@extends('layouts.app')
@section('title', 'New Ticket')

@push('styles')
<style>
.pf-card  { background:var(--bg-surface); border:1px solid var(--border-default); border-radius:var(--r-lg); overflow:hidden; max-width:680px; }
.pf-body  { padding:24px; display:flex; flex-direction:column; gap:16px; }
.pf-foot  { padding:14px 24px; background:var(--bg-elevated); border-top:1px solid var(--border-subtle); display:flex; justify-content:space-between; align-items:center; }
.field    { display:flex; flex-direction:column; gap:6px; }
.fl       { font-size:12px; font-weight:600; color:var(--text-200); text-transform:uppercase; letter-spacing:.4px; }
.fi       { padding:9px 13px; background:var(--bg-input); border:1.5px solid var(--border-default); border-radius:var(--r-sm); color:var(--text-100); font-family:var(--font); font-size:14px; outline:none; width:100%; }
.fg2 { display:grid; grid-template-columns:1fr 1fr; gap:14px; }
@media(max-width:640px) { .fg2 { grid-template-columns:1fr; } }
</style>
@endpush

@section('content')

<div class="page-head">
    <div>
        <div style="font-size:12px;color:var(--text-300);margin-bottom:4px">
            <a href="{{ route('tenant.tickets.index') }}" style="color:var(--text-300);text-decoration:none">Tickets</a>
            › New
        </div>
        <div class="page-title">New Ticket</div>
    </div>
    <a href="{{ route('tenant.tickets.index') }}" class="btn btn-secondary">← Back</a>
</div>

@if($errors->any())
<div style="padding:10px 14px;background:var(--red-dim);border:1px solid rgba(255,82,87,.25);border-radius:var(--r-sm);margin-bottom:14px;font-size:13px;color:var(--red)">
    {{ $errors->first() }}
</div>
@endif

<form method="POST" action="{{ route('tenant.tickets.store') }}">
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
            <label class="fl">Subject <span style="color:var(--red)">*</span></label>
            <input type="text" name="subject" class="fi" required/>
        </div>

        <div class="fg2">
            <div class="field">
                <label class="fl">Related Service <span style="font-weight:400;text-transform:none;color:var(--text-400)">(optional)</span></label>
                <select name="service_id" class="fi">
                    <option value="">— None —</option>
                    @foreach($services as $s)
                    <option value="{{ $s->id }}">{{ $s->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label class="fl">Priority <span style="color:var(--red)">*</span></label>
                <select name="priority" class="fi" required>
                    @foreach(\App\Models\Ticket::priorities() as $val => $label)
                    <option value="{{ $val }}" {{ $val === 'medium' ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="field">
            <label class="fl">Description</label>
            <textarea name="description" class="fi" rows="5"></textarea>
        </div>

        <div class="field">
            <label class="fl">Notify Customer Via</label>
            <select name="notify_via" class="fi">
                <option value="default" selected>Default (tenant settings)</option>
                <option value="both">Email &amp; WhatsApp</option>
                <option value="email">Email only</option>
                <option value="whatsapp">WhatsApp only</option>
                <option value="none">Don't notify</option>
            </select>
        </div>

    </div>
    <div class="pf-foot">
        <a href="{{ route('tenant.tickets.index') }}" class="btn btn-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary">Create Ticket</button>
    </div>
</div>
</form>

@endsection
