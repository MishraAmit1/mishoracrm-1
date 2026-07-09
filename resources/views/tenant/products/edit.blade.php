@extends('layouts.app')
@section('title', 'Edit Product')

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
            <a href="{{ route('tenant.products.index') }}" style="color:var(--text-300);text-decoration:none">Products</a>
            › Edit
        </div>
        <div class="page-title">Edit: {{ $product->name }}</div>
    </div>
    <a href="{{ route('tenant.products.index') }}" class="btn btn-secondary">← Back</a>
</div>

@if(session('success'))
<div style="padding:10px 14px;background:var(--green-dim);border:1px solid rgba(52,199,89,.25);border-radius:var(--r-sm);margin-bottom:14px;font-size:13px;color:var(--green)">
    {{ session('success') }}
</div>
@endif

@if($errors->any())
<div style="padding:10px 14px;background:var(--red-dim);border:1px solid rgba(255,82,87,.25);border-radius:var(--r-sm);margin-bottom:14px;font-size:13px;color:var(--red)">
    {{ $errors->first() }}
</div>
@endif

<form method="POST" action="{{ route('tenant.products.update', $product->id) }}">
@csrf @method('PUT')
<div class="pf-card">
    <div class="pf-body">

        <div class="field">
            <label class="fl">Name <span style="color:var(--red)">*</span></label>
            <input type="text" name="name" class="fi"
                   value="{{ old('name', $product->name) }}" required autofocus/>
            @error('name') <span class="fe">{{ $message }}</span> @enderror
        </div>

        <div class="field">
            <label class="fl">Product Code / SKU</label>
            <input type="text" name="product_code" class="fi"
                   value="{{ old('product_code', $product->product_code) }}" placeholder="e.g. SRV-WD001"
                   style="font-family:var(--mono,monospace);letter-spacing:.5px"/>
            <span style="font-size:11.5px;color:var(--text-400)">Search mein code se bhi dhundh sakte hain</span>
        </div>

        <div class="field">
            <label class="fl">Description</label>
            <textarea name="description" class="fi" rows="3"
                      style="resize:vertical">{{ old('description', $product->description) }}</textarea>
        </div>

        <div class="fg2">
            <div class="field">
                <label class="fl">Rate (₹) <span style="color:var(--red)">*</span></label>
                <input type="number" name="rate" class="fi" min="0" step="0.01"
                       value="{{ old('rate', $product->rate) }}" required/>
                @error('rate') <span class="fe">{{ $message }}</span> @enderror
            </div>
            <div class="field">
                <label class="fl">GST / Tax % <span style="color:var(--red)">*</span></label>
                <select name="tax_percent" class="fi" required>
                    @foreach([0,5,12,18,28] as $rate)
                    <option value="{{ $rate }}" {{ old('tax_percent', $product->tax_percent) == $rate ? 'selected' : '' }}>
                        {{ $rate }}%{{ $rate === 18 ? ' (Default)' : '' }}
                    </option>
                    @endforeach
                </select>
                @error('tax_percent') <span class="fe">{{ $message }}</span> @enderror
            </div>
        </div>

        <div class="fg2">
            <div class="field">
                <label class="fl">HSN / SAC Code</label>
                <input type="text" name="hsn" class="fi"
                       value="{{ old('hsn', $product->hsn) }}" placeholder="e.g. 998314"/>
            </div>
            <div class="field">
                <label class="fl">Unit</label>
                <input type="text" name="unit" class="fi"
                       value="{{ old('unit', $product->unit) }}" placeholder="e.g. pcs, hrs, kg"/>
            </div>
        </div>

        <div class="field" style="flex-direction:row;align-items:center;gap:10px">
            <input type="checkbox" name="is_active" value="1" id="is_active"
                   {{ old('is_active', $product->is_active) ? 'checked' : '' }}
                   style="width:16px;height:16px;cursor:pointer"/>
            <label for="is_active" style="font-size:13.5px;color:var(--text-200);cursor:pointer">
                Active (visible in invoice/quotation selectors)
            </label>
        </div>

    </div>
    <div class="pf-foot">
        <form method="POST" action="{{ route('tenant.products.destroy', $product->id) }}"
              onsubmit="return confirm('Delete this product?')" style="display:inline">
            @csrf @method('DELETE')
            <button type="submit"
                    style="padding:8px 14px;border-radius:var(--r-sm);border:1.5px solid rgba(255,82,87,.3);background:var(--red-dim);color:var(--red);font-size:13px;font-weight:600;cursor:pointer;font-family:var(--font)">
                Delete
            </button>
        </form>
        <div style="display:flex;gap:8px">
            <a href="{{ route('tenant.products.index') }}" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary">Save Changes</button>
        </div>
    </div>
</div>
</form>

@endsection
