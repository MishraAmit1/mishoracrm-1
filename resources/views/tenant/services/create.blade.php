@extends('layouts.app')
@section('title', 'Add Service')

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
    $units = ['Hour','Day','Job','Visit','Month','Session','Piece'];
    $oldUnit = old('unit');
@endphp

<div class="page-head">
    <div>
        <div style="font-size:12px;color:var(--text-300);margin-bottom:4px">
            <a href="{{ route('tenant.services.index') }}" style="color:var(--text-300);text-decoration:none">Services</a>
            › Add
        </div>
        <div class="page-title">Add Service</div>
    </div>
    <a href="{{ route('tenant.services.index') }}" class="btn btn-secondary">← Back</a>
</div>

@if($errors->any())
<div style="padding:10px 14px;background:var(--red-dim);border:1px solid rgba(255,82,87,.25);border-radius:var(--r-sm);margin-bottom:14px;font-size:13px;color:var(--red)">
    {{ $errors->first() }}
</div>
@endif

<form method="POST" action="{{ route('tenant.services.store') }}">
@csrf
<div class="pf-card">
    <div class="pf-body">

        <div class="field" style="flex-direction:row;align-items:center;gap:10px;padding:10px 12px;background:var(--bg-elevated);border-radius:var(--r-sm)">
            <input type="checkbox" name="is_package" value="1" id="isPackage"
                   {{ old('is_package') ? 'checked' : '' }} onchange="onPackageToggle()"
                   style="width:16px;height:16px;cursor:pointer"/>
            <label for="isPackage" style="font-size:13.5px;color:var(--text-200);cursor:pointer">
                This is a Package (bundle of other services at a combined price)
            </label>
        </div>

        <div class="field">
            <label class="fl">Name <span style="color:var(--red)">*</span></label>
            <input type="text" name="name" class="fi {{ $errors->has('name')?'border-red':'' }}"
                   value="{{ old('name') }}" placeholder="e.g. Website Maintenance" required autofocus/>
            @error('name') <span class="fe">{{ $message }}</span> @enderror
        </div>

        <div class="field" id="packageComponentsField" style="{{ old('is_package') ? '' : 'display:none' }}">
            <label class="fl">Included Services</label>
            <div style="display:flex;flex-direction:column;gap:6px;max-height:200px;overflow-y:auto;padding:10px 12px;background:var(--bg-input);border:1.5px solid var(--border-default);border-radius:var(--r-sm)">
                @forelse($availableComponents as $c)
                @php $oldSelected = old('component_service_ids', []); @endphp
                <label style="display:flex;align-items:center;gap:8px;font-size:13px;color:var(--text-200);cursor:pointer">
                    <input type="checkbox" name="component_service_ids[]" value="{{ $c->id }}"
                           {{ in_array($c->id, $oldSelected) ? 'checked' : '' }}
                           style="width:14px;height:14px;cursor:pointer"/>
                    {{ $c->name }} <span style="color:var(--text-400)">(₹{{ number_format($c->rate, 2) }})</span>
                </label>
                @empty
                <span style="font-size:12.5px;color:var(--text-400)">No standalone services yet — add some services first, then bundle them into this package.</span>
                @endforelse
            </div>
            <span style="font-size:11.5px;color:var(--text-400)">This is for reference only (to show the customer what's included) — set the Rate/Tax/Billing Cycle below yourself; they are not auto-calculated from the component rates.</span>
        </div>

        <div class="fg2">
            <div class="field">
                <label class="fl">Service Code / SKU</label>
                <input type="text" name="service_code" class="fi"
                       value="{{ old('service_code') }}" placeholder="e.g. SRV-WD001"
                       style="font-family:var(--mono,monospace);letter-spacing:.5px"/>
                <span style="font-size:11.5px;color:var(--text-400)">Search mein code se bhi dhundh sakte hain</span>
            </div>
            <div class="field">
                <label class="fl">HSN / SAC Code</label>
                <input type="text" name="hsn" class="fi"
                       value="{{ old('hsn') }}" placeholder="e.g. 998314"/>
            </div>
        </div>

        <div class="field">
            <label class="fl">Description</label>
            <textarea name="description" class="fi" rows="3"
                      style="resize:vertical" placeholder="Optional — shown in invoice/quotation item description">{{ old('description') }}</textarea>
        </div>

        <div class="fg2">
            <div class="field">
                <label class="fl">Rate (₹) <span style="color:var(--red)">*</span></label>
                <input type="number" name="rate" class="fi" min="0" step="0.01"
                       value="{{ old('rate', 0) }}" placeholder="0.00" required/>
                @error('rate') <span class="fe">{{ $message }}</span> @enderror
            </div>
            <div class="field">
                <label class="fl">GST / Tax % <span style="color:var(--red)">*</span></label>
                <select name="tax_percent" class="fi" required>
                    @foreach([0,5,12,18,28] as $rate)
                    <option value="{{ $rate }}" {{ old('tax_percent', 18) == $rate ? 'selected' : '' }}>
                        {{ $rate }}%{{ $rate === 18 ? ' (Default)' : '' }}
                    </option>
                    @endforeach
                </select>
                @error('tax_percent') <span class="fe">{{ $message }}</span> @enderror
            </div>
        </div>

        <div class="field">
            <label class="fl">Unit</label>
            <select class="fi" id="unitSelect" onchange="onUnitChange()">
                <option value="">— Select unit —</option>
                @foreach($units as $u)
                <option value="{{ $u }}" {{ $oldUnit === $u ? 'selected' : '' }}>{{ $u }}</option>
                @endforeach
                <option value="__other__" {{ ($oldUnit && !in_array($oldUnit, $units)) ? 'selected' : '' }}>Other (custom)</option>
            </select>
            <input type="text" id="unitOther" class="fi" placeholder="Enter custom unit"
                   value="{{ ($oldUnit && !in_array($oldUnit, $units)) ? $oldUnit : '' }}"
                   style="{{ ($oldUnit && !in_array($oldUnit, $units)) ? '' : 'display:none' }};margin-top:6px"/>
            <input type="hidden" name="unit" id="unitHidden" value="{{ $oldUnit }}">
            <span style="font-size:11.5px;color:var(--text-400)">What the rate is based on — per Hour, per Session, per Month, per Project, etc.</span>
        </div>

        <div class="field">
            <label class="fl">Billing Cycle</label>
            <select name="billing_cycle" class="fi" id="billingCycleSelect" onchange="onBillingCycleChange()">
                @foreach(\App\Models\Service::billingCycles() as $val => $label)
                <option value="{{ $val }}" {{ old('billing_cycle', 'one_time') === $val ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            <span style="font-size:11.5px;color:var(--text-400)">How often the customer is billed — once (One-time), or on a recurring basis (Monthly/Quarterly/Yearly).</span>
        </div>

        <div class="field" id="durationField" style="{{ old('billing_cycle', 'one_time') === 'one_time' ? 'display:none' : '' }}">
            <label class="fl">Contract Length <span style="font-weight:400;text-transform:none;color:var(--text-400)">(optional)</span></label>
            <div style="display:flex;gap:8px">
                <input type="number" name="duration_value" class="fi" min="1"
                       value="{{ old('duration_value') }}" placeholder="e.g. 6" style="flex:1"/>
                <select name="duration_unit" class="fi" style="flex:1">
                    <option value="">— Unit —</option>
                    <option value="days" {{ old('duration_unit') === 'days' ? 'selected' : '' }}>Days</option>
                    <option value="months" {{ old('duration_unit') === 'months' ? 'selected' : '' }}>Months</option>
                </select>
            </div>
            <span style="font-size:11.5px;color:var(--text-400)">The full length of the commitment/agreement — can differ from the billing frequency. Fill this in only when the customer is locked into a fixed period. E.g. AMC: quarterly billing on a 12-month contract &middot; Coaching course: monthly fees on a 6-month course. Leave blank for a simple ongoing monthly/yearly service.</span>
        </div>

        <div class="field">
            <label class="fl">Total Quantity <span style="font-weight:400;text-transform:none;color:var(--text-400)">(optional)</span></label>
            <input type="number" name="total_quantity" class="fi" min="1"
                   value="{{ old('total_quantity') }}" placeholder="e.g. 10"/>
            <span style="font-size:11.5px;color:var(--text-400)">If the service has a fixed count (e.g. a "10 sessions" package), enter 10 here — the customer's subscription will track "X of 10 used". Leave blank for unlimited services like a membership.</span>
        </div>

        <div class="field" style="flex-direction:row;align-items:center;gap:10px">
            <input type="checkbox" name="is_active" value="1" id="is_active"
                   {{ old('is_active', true) ? 'checked' : '' }}
                   style="width:16px;height:16px;cursor:pointer"/>
            <label for="is_active" style="font-size:13.5px;color:var(--text-200);cursor:pointer">
                Active (visible in invoice/quotation selectors)
            </label>
        </div>

    </div>
    <div class="pf-foot">
        <a href="{{ route('tenant.services.index') }}" class="btn btn-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary">Save Service</button>
    </div>
</div>
</form>

<script>
function onUnitChange(){
    const sel   = document.getElementById('unitSelect').value;
    const other = document.getElementById('unitOther');
    const hidden = document.getElementById('unitHidden');
    if(sel === '__other__'){
        other.style.display = 'block';
        hidden.value = other.value;
    } else {
        other.style.display = 'none';
        hidden.value = sel;
    }
}
document.getElementById('unitOther')?.addEventListener('input', function(){
    document.getElementById('unitHidden').value = this.value;
});

function onBillingCycleChange(){
    const cycle = document.getElementById('billingCycleSelect').value;
    const field = document.getElementById('durationField');
    field.style.display = cycle === 'one_time' ? 'none' : '';
}

function onPackageToggle(){
    const checked = document.getElementById('isPackage').checked;
    document.getElementById('packageComponentsField').style.display = checked ? '' : 'none';
}
</script>

@endsection
