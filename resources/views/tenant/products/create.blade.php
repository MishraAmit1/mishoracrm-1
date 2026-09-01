@extends('layouts.app')
@section('title', 'Add Product')

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
    $units = ['Kg','Gram','Quintal','Litre','Millilitre','Metre','Piece','Box','Bag','Dozen','Ton','Set','Roll'];
    $oldUnit = old('unit');
@endphp

<div class="page-head">
    <div>
        <div style="font-size:12px;color:var(--text-300);margin-bottom:4px">
            <a href="{{ route('tenant.products.index') }}" style="color:var(--text-300);text-decoration:none">Products</a>
            › Add
        </div>
        <div class="page-title">Add Product / Service</div>
    </div>
    <a href="{{ route('tenant.products.index') }}" class="btn btn-secondary">← Back</a>
</div>

@if($errors->any())
<div style="padding:10px 14px;background:var(--red-dim);border:1px solid rgba(255,82,87,.25);border-radius:var(--r-sm);margin-bottom:14px;font-size:13px;color:var(--red)">
    {{ $errors->first() }}
</div>
@endif

<form method="POST" action="{{ route('tenant.products.store') }}">
@csrf
<div class="pf-card">
    <div class="pf-body">

        <div class="field">
            <label class="fl">Name <span style="color:var(--red)">*</span></label>
            <input type="text" name="name" class="fi {{ $errors->has('name')?'border-red':'' }}"
                   value="{{ old('name') }}" placeholder="e.g. Web Design Service" required autofocus/>
            @error('name') <span class="fe">{{ $message }}</span> @enderror
        </div>

        <div class="field">
            <label class="fl">Product Code / SKU</label>
            <input type="text" name="product_code" class="fi"
                   value="{{ old('product_code') }}" placeholder="e.g. SRV-WD001"
                   style="font-family:var(--mono,monospace);letter-spacing:.5px"/>
            <span style="font-size:11.5px;color:var(--text-400)">Search mein code se bhi dhundh sakte hain</span>
        </div>

        <div class="field">
            <label class="fl">Description</label>
            <textarea name="description" class="fi" rows="3"
                      style="resize:vertical" placeholder="Optional — shown in invoice item description">{{ old('description') }}</textarea>
        </div>

        <div class="field">
            <label class="fl">Category</label>
            <input type="text" name="category" class="fi" value="{{ old('category') }}" placeholder="e.g. Beverages, Electronics"/>
            <span style="font-size:11.5px;color:var(--text-400)">Used for targeted loyalty campaigns</span>
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
            <label class="fl">Purchase Cost / Unit (₹)</label>
            <input type="number" name="cost_price" class="fi" min="0" step="0.01"
                   value="{{ old('cost_price') }}" placeholder="Defaults to Rate if left blank"/>
            <span style="font-size:11.5px;color:var(--text-400)">Cost basis for production &amp; inventory valuation. Auto-updates (weighted average) each time stock is received via a Purchase Order.</span>
            @error('cost_price') <span class="fe">{{ $message }}</span> @enderror
        </div>

        <div class="fg2">
            <div class="field">
                <label class="fl">HSN / SAC Code</label>
                <input type="text" name="hsn" class="fi"
                       value="{{ old('hsn') }}" placeholder="e.g. 998314"/>
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
            </div>
        </div>

        <div class="fg2">
            <div class="field">
                <label class="fl">Type</label>
                <select name="type" class="fi" id="typeSelect" onchange="onTypeChange()">
                    <option value="finished_good" {{ old('type', 'finished_good') === 'finished_good' ? 'selected' : '' }}>Finished Good</option>
                    <option value="raw_material" {{ old('type') === 'raw_material' ? 'selected' : '' }}>Raw Material</option>
                </select>
                <span style="font-size:11.5px;color:var(--text-400)">Finished goods are sold on invoices; raw materials are used in a Bill of Materials</span>
            </div>
            <div class="field">
                <label class="fl">Current Stock</label>
                <input type="number" name="current_stock" class="fi" min="0" step="0.01"
                       value="{{ old('current_stock', 0) }}" placeholder="0"/>
            </div>
        </div>

        <div class="fg2" id="reorderFields">
            <div class="field">
                <label class="fl">Reorder Level</label>
                <input type="number" name="reorder_level" class="fi" min="0" step="0.01"
                       value="{{ old('reorder_level') }}" placeholder="Alert when stock falls to/below this"/>
            </div>
            <div class="field">
                <label class="fl">Reorder Quantity</label>
                <input type="number" name="reorder_quantity" class="fi" min="0" step="0.01"
                       value="{{ old('reorder_quantity') }}" placeholder="How many units to replenish"/>
            </div>
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
        <a href="{{ route('tenant.products.index') }}" class="btn btn-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary">Save Product</button>
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
function onTypeChange(){
    // Reorder fields are meaningful for both types, so no hide/show here —
    // kept as a hook in case future rules need it.
}
</script>

@endsection
