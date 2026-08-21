@extends('layouts.app')
@section('title', 'Add Subscription')

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

<div class="page-head">
    <div>
        <div style="font-size:12px;color:var(--text-300);margin-bottom:4px">
            <a href="{{ route('tenant.subscriptions.index') }}" style="color:var(--text-300);text-decoration:none">Subscriptions</a>
            › Add
        </div>
        <div class="page-title">Add Subscription</div>
    </div>
    <a href="{{ route('tenant.subscriptions.index') }}" class="btn btn-secondary">← Back</a>
</div>

@if($errors->any())
<div style="padding:10px 14px;background:var(--red-dim);border:1px solid rgba(255,82,87,.25);border-radius:var(--r-sm);margin-bottom:14px;font-size:13px;color:var(--red)">
    {{ $errors->first() }}
</div>
@endif

<form method="POST" action="{{ route('tenant.subscriptions.store') }}" id="subForm">
@csrf
<div class="pf-card">
    <div class="pf-body">

        <div class="field">
            <label class="fl">Contact / Customer <span style="color:var(--red)">*</span></label>
            <select name="contact_id" id="contactSelect" class="fi" required onchange="onContactChange()">
                <option value="">— Select contact —</option>
                @foreach($contacts as $c)
                <option value="{{ $c->id }}" {{ old('contact_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}{{ $c->company ? ' — ' . $c->company : '' }}</option>
                @endforeach
            </select>
            @error('contact_id') <span class="fe">{{ $message }}</span> @enderror
        </div>

        <div class="field">
            <label class="fl">Service <span style="color:var(--red)">*</span></label>
            <select name="service_id" id="serviceSelect" class="fi" required onchange="onServiceChange()">
                <option value="">— Select service —</option>
                @foreach($services as $s)
                <option value="{{ $s->id }}" {{ old('service_id') == $s->id ? 'selected' : '' }}>{{ $s->name }} — ₹{{ number_format($s->rate, 2) }}</option>
                @endforeach
            </select>
            @error('service_id') <span class="fe">{{ $message }}</span> @enderror
        </div>

        <div class="fg2">
            <div class="field">
                <label class="fl">Start Date <span style="color:var(--red)">*</span></label>
                <input type="date" name="starts_at" id="startsAt" class="fi" value="{{ old('starts_at', now()->format('Y-m-d')) }}" required onchange="recalcExpiry()"/>
                @error('starts_at') <span class="fe">{{ $message }}</span> @enderror
            </div>
            <div class="field">
                <label class="fl">Expiry Date</label>
                <input type="date" name="expires_at" id="expiresAt" class="fi" value="{{ old('expires_at') }}"/>
                <span style="font-size:11.5px;color:var(--text-400)">Service select karne par auto-calculate hoga — chaho to edit kar do</span>
                @error('expires_at') <span class="fe">{{ $message }}</span> @enderror
            </div>
        </div>

        <div class="fg2">
            <div class="field">
                <label class="fl">Duration <span style="font-weight:400;text-transform:none;color:var(--text-400)">(optional)</span></label>
                <div style="display:flex;gap:8px">
                    <input type="number" name="duration_value" id="durationValue" class="fi" min="1" value="{{ old('duration_value') }}" placeholder="e.g. 12" style="flex:1"/>
                    <select name="duration_unit" id="durationUnit" class="fi" style="flex:1">
                        <option value="">— Unit —</option>
                        <option value="days" {{ old('duration_unit') === 'days' ? 'selected' : '' }}>Days</option>
                        <option value="months" {{ old('duration_unit') === 'months' ? 'selected' : '' }}>Months</option>
                    </select>
                </div>
            </div>
            <div class="field">
                <label class="fl">Link Invoice <span style="font-weight:400;text-transform:none;color:var(--text-400)">(optional)</span></label>
                <select name="invoice_id" id="invoiceSelect" class="fi">
                    <option value="">— None —</option>
                </select>
                <span style="font-size:11.5px;color:var(--text-400)">Pehle contact select karo — uske invoices yahan dikhenge</span>
            </div>
        </div>

        <div class="field">
            <label class="fl">Notes</label>
            <textarea name="notes" class="fi" rows="3" style="resize:vertical" placeholder="Optional notes">{{ old('notes') }}</textarea>
        </div>

    </div>
    <div class="pf-foot">
        <a href="{{ route('tenant.subscriptions.index') }}" class="btn btn-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary">Save Subscription</button>
    </div>
</div>
</form>

<script>
const SERVICES = @json($services->keyBy('id'));

function onServiceChange(){
    const id = document.getElementById('serviceSelect').value;
    const svc = SERVICES[id];
    if(!svc) return;
    document.getElementById('durationValue').value = svc.duration_value || '';
    document.getElementById('durationUnit').value  = svc.duration_unit || '';
    recalcExpiry();
}

function recalcExpiry(){
    const start = document.getElementById('startsAt').value;
    if(!start) return;
    const durVal  = parseInt(document.getElementById('durationValue').value) || null;
    const durUnit = document.getElementById('durationUnit').value || null;
    const svcId   = document.getElementById('serviceSelect').value;
    const svc     = SERVICES[svcId];
    const cycle   = svc ? svc.billing_cycle : null;

    const d = new Date(start + 'T00:00:00');
    let result = null;

    if(durVal && durUnit){
        if(durUnit === 'days'){
            result = new Date(d); result.setDate(result.getDate() + durVal);
        } else {
            result = new Date(d); result.setMonth(result.getMonth() + durVal);
        }
    } else if(cycle && cycle !== 'one_time'){
        result = new Date(d);
        if(cycle === 'monthly')       result.setMonth(result.getMonth() + 1);
        else if(cycle === 'quarterly') result.setMonth(result.getMonth() + 3);
        else if(cycle === 'yearly')    result.setFullYear(result.getFullYear() + 1);
    }

    if(result){
        document.getElementById('expiresAt').value = result.toISOString().slice(0,10);
    }
}

document.getElementById('durationValue').addEventListener('input', recalcExpiry);
document.getElementById('durationUnit').addEventListener('change', recalcExpiry);

function onContactChange(){
    const contactId = document.getElementById('contactSelect').value;
    const invoiceSelect = document.getElementById('invoiceSelect');
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
                invoiceSelect.appendChild(opt);
            });
        })
        .catch(() => {});
}

@if(old('contact_id'))
document.addEventListener('DOMContentLoaded', onContactChange);
@endif
</script>

@endsection
