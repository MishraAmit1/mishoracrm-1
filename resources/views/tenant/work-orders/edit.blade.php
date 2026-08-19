@extends('layouts.app')
@section('title', 'Edit Work Order — ' . $workOrder->number)

@push('styles')
<style>
@import url('https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=DM+Mono:wght@400;500&display=swap');
@import url('https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.19.0/dist/tabler-icons.min.css');

.pf { font-family: 'DM Sans', var(--font), sans-serif; }
.pf-card { background:var(--bg-surface); border:1px solid var(--border-default); border-radius:14px; overflow:hidden; margin-top:20px; }
.pf-section { padding:20px 22px; border-bottom:1px solid var(--border-subtle); }
.pf-section:last-of-type { border-bottom:none; }
.pf-grid { display:grid; grid-template-columns:1fr 1fr; gap:14px; }
.pf-grid .span-full { grid-column:1/-1; }
@media(max-width:640px){ .pf-grid { grid-template-columns:1fr; } .pf-grid .span-full { grid-column:1; } }
.pf-field { display:flex; flex-direction:column; gap:5px; }
.pf-label { font-size:11.5px; font-weight:600; color:var(--text-200); text-transform:uppercase; letter-spacing:.5px; }
.pf-req { color:var(--red,#E24B4A); margin-left:2px; }
.pf-input {
    width:100%; padding:9px 12px; background:var(--bg-input); border:1.5px solid var(--border-default);
    border-radius:8px; color:var(--text-100); font-family:'DM Sans',var(--font),sans-serif; font-size:13.5px; outline:none;
}
.pf-input:focus { border-color:var(--accent); box-shadow:0 0 0 3px var(--accent-dim); }
.pf-area { resize:vertical; min-height:80px; line-height:1.55; }
.pf-err { font-size:12px; color:var(--red,#E24B4A); font-weight:500; }
.pf-footer { display:flex; align-items:center; justify-content:flex-end; padding:15px 22px; background:var(--bg-elevated); border-top:1px solid var(--border-subtle); gap:8px; }
</style>
@endpush

@section('content')
<div class="pf">
    <div class="page-head">
        <div>
            <div style="font-size:12px;color:var(--text-300);margin-bottom:4px;display:flex;align-items:center;gap:5px">
                <a href="{{ route('tenant.work-orders.index') }}" style="color:var(--text-300);text-decoration:none">Work Orders</a>
                <span style="opacity:.4">›</span>
                <span>Edit {{ $workOrder->number }}</span>
            </div>
            <div class="page-title">Edit Work Order</div>
        </div>
        <a href="{{ route('tenant.work-orders.show', $workOrder->id) }}" class="btn btn-secondary">
            <i class="ti ti-arrow-left" style="font-size:14px"></i> Back
        </a>
    </div>

    <form method="POST" action="{{ route('tenant.work-orders.update', $workOrder->id) }}" novalidate>
        @csrf @method('PUT')
        <div class="pf-card">
            <div class="pf-section">
                <div class="pf-grid">
                    <div class="pf-field">
                        <label class="pf-label">Number</label>
                        <input type="text" class="pf-input" value="{{ $workOrder->number }}" readonly
                               style="background:var(--bg-elevated);color:var(--text-300);cursor:default;font-family:'DM Mono',monospace"/>
                    </div>
                    <div class="pf-field">
                        <label class="pf-label" for="wo_quantity">Quantity to Produce <span class="pf-req">*</span></label>
                        <input type="number" name="quantity" id="wo_quantity" class="pf-input" min="0.01" step="0.01"
                               value="{{ old('quantity', $workOrder->quantity) }}" required/>
                        @error('quantity')<span class="pf-err">{{ $message }}</span>@enderror
                    </div>
                    <div class="pf-field span-full">
                        <label class="pf-label" for="wo_product">Finished Good <span class="pf-req">*</span></label>
                        <select name="product_id" id="wo_product" class="pf-input" required>
                            @foreach($products as $p)
                            <option value="{{ $p->id }}" {{ old('product_id', $workOrder->product_id) == $p->id ? 'selected':'' }}>{{ $p->name }}{{ $p->product_code ? ' ['.$p->product_code.']' : '' }}</option>
                            @endforeach
                        </select>
                        @error('product_id')<span class="pf-err">{{ $message }}</span>@enderror
                    </div>
                    <div class="pf-field span-full">
                        <label class="pf-label" for="wo_notes">Notes</label>
                        <textarea name="notes" id="wo_notes" class="pf-input pf-area" rows="3">{{ old('notes', $workOrder->notes) }}</textarea>
                        @error('notes')<span class="pf-err">{{ $message }}</span>@enderror
                    </div>
                </div>
            </div>
            <div class="pf-footer">
                <a href="{{ route('tenant.work-orders.show', $workOrder->id) }}" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </div>
    </form>
</div>
@endsection
