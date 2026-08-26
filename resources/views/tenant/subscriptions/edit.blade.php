@extends('layouts.app')
@section('title', 'Edit Subscription')

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
@media(max-width:640px) { .fg2 { grid-template-columns:1fr; } }
</style>
@endpush

@section('content')

@php
    $currentInvoices = \App\Models\Invoice::where('tenant_id', auth()->user()->tenant_id)
        ->where('contact_id', $subscription->contact_id)
        ->latest()->get(['id','number','total']);
@endphp

<div class="page-head">
    <div>
        <div style="font-size:12px;color:var(--text-300);margin-bottom:4px">
            <a href="{{ route('tenant.subscriptions.index') }}" style="color:var(--text-300);text-decoration:none">Subscriptions</a>
            › Edit
        </div>
        <div class="page-title">Edit Subscription</div>
    </div>
    <a href="{{ route('tenant.subscriptions.index') }}" class="btn btn-secondary">← Back</a>
</div>

@if($errors->any())
<div style="padding:10px 14px;background:var(--red-dim);border:1px solid rgba(255,82,87,.25);border-radius:var(--r-sm);margin-bottom:14px;font-size:13px;color:var(--red)">
    {{ $errors->first() }}
</div>
@endif

<form method="POST" action="{{ route('tenant.subscriptions.update', $subscription->id) }}" id="subForm">
@csrf
@method('PUT')
<div class="pf-card">
    <div class="pf-body">

        <div class="field">
            <label class="fl">Contact / Customer <span style="color:var(--red)">*</span></label>
            <select name="contact_id" id="contactSelect" class="fi" required onchange="onContactChange()">
                @foreach($contacts as $c)
                <option value="{{ $c->id }}" {{ old('contact_id', $subscription->contact_id) == $c->id ? 'selected' : '' }}>{{ $c->name }}{{ $c->company ? ' — ' . $c->company : '' }}</option>
                @endforeach
            </select>
            @error('contact_id') <span class="fe">{{ $message }}</span> @enderror
        </div>

        <div class="field">
            <label class="fl">Service <span style="color:var(--red)">*</span></label>
            <select name="service_id" id="serviceSelect" class="fi" required>
                @foreach($services as $s)
                <option value="{{ $s->id }}" {{ old('service_id', $subscription->service_id) == $s->id ? 'selected' : '' }}>{{ $s->name }} — ₹{{ number_format($s->rate, 2) }}</option>
                @endforeach
            </select>
            @error('service_id') <span class="fe">{{ $message }}</span> @enderror
        </div>

        <div class="fg2">
            <div class="field">
                <label class="fl">Start Date <span style="color:var(--red)">*</span></label>
                <input type="date" name="starts_at" id="startsAt" class="fi" value="{{ old('starts_at', $subscription->starts_at?->format('Y-m-d')) }}" required/>
                @error('starts_at') <span class="fe">{{ $message }}</span> @enderror
            </div>
            <div class="field">
                <label class="fl">Expiry Date</label>
                <input type="date" name="expires_at" id="expiresAt" class="fi" value="{{ old('expires_at', $subscription->expires_at?->format('Y-m-d')) }}"/>
                @error('expires_at') <span class="fe">{{ $message }}</span> @enderror
            </div>
        </div>

        <div class="fg2">
            <div class="field">
                <label class="fl">Duration <span style="font-weight:400;text-transform:none;color:var(--text-400)">(optional)</span></label>
                <div style="display:flex;gap:8px">
                    <input type="number" name="duration_value" id="durationValue" class="fi" min="1" value="{{ old('duration_value', $subscription->duration_value) }}" placeholder="e.g. 12" style="flex:1"/>
                    <select name="duration_unit" id="durationUnit" class="fi" style="flex:1">
                        <option value="">— Unit —</option>
                        <option value="days" {{ old('duration_unit', $subscription->duration_unit) === 'days' ? 'selected' : '' }}>Days</option>
                        <option value="months" {{ old('duration_unit', $subscription->duration_unit) === 'months' ? 'selected' : '' }}>Months</option>
                    </select>
                </div>
            </div>
            <div class="field">
                <label class="fl">Link Invoice <span style="font-weight:400;text-transform:none;color:var(--text-400)">(optional)</span></label>
                <select name="invoice_id" id="invoiceSelect" class="fi">
                    <option value="">— None —</option>
                    @foreach($currentInvoices as $inv)
                    <option value="{{ $inv->id }}" {{ old('invoice_id', $subscription->invoice_id) == $inv->id ? 'selected' : '' }}>{{ $inv->number }} — ₹{{ number_format($inv->total, 2) }}</option>
                    @endforeach
                </select>
                <span style="font-size:11.5px;color:var(--text-400)">Contact badalne par list refresh hogi</span>
            </div>
        </div>

        <div class="field">
            <label style="display:flex;align-items:center;gap:8px;font-size:13px;color:var(--text-200);cursor:pointer">
                <input type="checkbox" name="auto_renew" value="1" {{ old('auto_renew', $subscription->auto_renew) ? 'checked' : '' }} style="width:15px;height:15px;cursor:pointer"/>
                Auto-renew — extend the term and create a draft renewal invoice automatically on expiry
            </label>
        </div>

        <div class="field">
            <label class="fl">Notes</label>
            <textarea name="notes" class="fi" rows="3" style="resize:vertical" placeholder="Optional notes">{{ old('notes', $subscription->notes) }}</textarea>
        </div>

    </div>
    <div class="pf-foot">
        <a href="{{ route('tenant.subscriptions.index') }}" class="btn btn-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary">Update Subscription</button>
    </div>
</div>
</form>

<script>
function onContactChange(){
    const contactId = document.getElementById('contactSelect').value;
    const invoiceSelect = document.getElementById('invoiceSelect');
    const currentVal = invoiceSelect.value;
    invoiceSelect.innerHTML = '<option value="">— None —</option>';
    if(!contactId) return;

    const baseUrl = '{{ route("tenant.subscriptions.contact-invoices", 0) }}';
    fetch(baseUrl.replace(/\/0$/, '/' + contactId))
        .then(r => r.json())
        .then(invoices => {
            invoices.forEach(inv => {
                const opt = document.createElement('option');
                opt.value = inv.id;
                opt.textContent = `${inv.number} — ₹${parseFloat(inv.total).toLocaleString('en-IN',{minimumFractionDigits:2})}`;
                if (String(inv.id) === String(currentVal)) opt.selected = true;
                invoiceSelect.appendChild(opt);
            });
        })
        .catch(() => {});
}
</script>

@endsection
