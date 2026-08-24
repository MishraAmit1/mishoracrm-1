@extends('layouts.app')
@section('title', 'Edit Work Order — ' . $workOrder->number)

@push('styles')
<style>
@import url('https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=DM+Mono:wght@400;500&display=swap');
@import url('https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.19.0/dist/tabler-icons.min.css');

.pf { font-family: 'DM Sans', var(--font), sans-serif; }
.pf-layout { display:grid; grid-template-columns:minmax(0,1fr) 280px; gap:16px; margin-top:20px; }
@media(max-width:960px){ .pf-layout { grid-template-columns:1fr; } }
.pf-card { background:var(--bg-surface); border:1px solid var(--border-default); border-radius:14px; overflow:hidden; }
.pf-section { padding:20px 22px; border-bottom:1px solid var(--border-subtle); }
.pf-section:last-of-type { border-bottom:none; }
.pf-sec-head { display:flex; align-items:flex-start; gap:11px; margin-bottom:16px; }
.pf-sec-icon { width:30px; height:30px; border-radius:8px; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.pf-sec-title { font-size:13px; font-weight:600; color:var(--text-100); }
.pf-sec-sub { font-size:12px; color:var(--text-300); margin-top:1px; }
.pf-grid { display:grid; grid-template-columns:1fr 1fr; gap:14px; }
.pf-grid .span-full { grid-column:1/-1; }
@media(max-width:640px){ .pf-grid { grid-template-columns:1fr; } .pf-grid .span-full { grid-column:1; } }
.pf-field { display:flex; flex-direction:column; gap:5px; }
.pf-label { font-size:11.5px; font-weight:600; color:var(--text-200); text-transform:uppercase; letter-spacing:.5px; }
.pf-req { color:var(--red); margin-left:2px; }
.pf-input {
    width:100%; padding:9px 12px; background:var(--bg-input); border:1.5px solid var(--border-default);
    border-radius:8px; color:var(--text-100); font-family:'DM Sans',var(--font),sans-serif; font-size:13.5px; outline:none;
    transition:border-color .15s, box-shadow .15s;
}
.pf-input:focus { border-color:var(--accent); box-shadow:0 0 0 3px var(--accent-dim); }
.pf-area { resize:vertical; min-height:80px; line-height:1.55; }
.pf-err { font-size:12px; color:var(--red); font-weight:500; }
.pf-footer { display:flex; align-items:center; justify-content:space-between; padding:15px 22px; background:var(--bg-elevated); border-top:1px solid var(--border-subtle); }
.pf-footer-note { font-size:12px; color:var(--text-300); }
.pf-sidebar { display:flex; flex-direction:column; gap:13px; }
.pf-sc { background:var(--bg-surface); border:1px solid var(--border-default); border-radius:14px; padding:17px; }
.pf-sc-title { font-size:11px; font-weight:600; color:var(--text-300); text-transform:uppercase; letter-spacing:.6px; margin-bottom:13px; }
.pr-number { font-size:18px; font-weight:600; color:var(--text-100); font-family:'DM Mono',monospace; }
.tip-list { display:flex; flex-direction:column; gap:9px; }
.tip-item { display:flex; align-items:flex-start; gap:8px; font-size:12px; color:var(--text-300); line-height:1.45; }
.tip-dot { width:5px; height:5px; border-radius:50%; background:var(--accent); margin-top:5px; flex-shrink:0; }
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
        <div class="pf-layout">
            <div class="pf-card">
                <div class="pf-section">
                    <div class="pf-sec-head">
                        <div class="pf-sec-icon" style="background:var(--accent-dim)">
                            <i class="ti ti-clipboard-list" style="font-size:15px;color:var(--accent)"></i>
                        </div>
                        <div>
                            <div class="pf-sec-title">Work Order Details</div>
                            <div class="pf-sec-sub">What you're producing</div>
                        </div>
                    </div>
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
                    </div>
                </div>
                <div class="pf-section">
                    <div class="pf-sec-head">
                        <div class="pf-sec-icon" style="background:var(--amber-dim)">
                            <i class="ti ti-notes" style="font-size:15px;color:var(--amber)"></i>
                        </div>
                        <div>
                            <div class="pf-sec-title">Notes</div>
                            <div class="pf-sec-sub">Optional production notes</div>
                        </div>
                    </div>
                    <div class="pf-grid">
                        <div class="pf-field span-full">
                            <textarea name="notes" id="wo_notes" class="pf-input pf-area" rows="3">{{ old('notes', $workOrder->notes) }}</textarea>
                            @error('notes')<span class="pf-err">{{ $message }}</span>@enderror
                        </div>
                    </div>
                </div>
                <div class="pf-footer">
                    <div class="pf-footer-note">Fields marked <strong>*</strong> are required</div>
                    <div style="display:flex;gap:8px">
                        <a href="{{ route('tenant.work-orders.show', $workOrder->id) }}" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">
                            <i class="ti ti-device-floppy" style="font-size:14px"></i> Save Changes
                        </button>
                    </div>
                </div>
            </div>

            <div class="pf-sidebar">
                <div class="pf-sc">
                    <div class="pf-sc-title">Work Order</div>
                    <div class="pr-number">{{ $workOrder->number }}</div>
                </div>
                <div class="pf-sc">
                    <div class="pf-sc-title">Tips</div>
                    <div class="tip-list">
                        <div class="tip-item"><div class="tip-dot"></div><span>Changing the finished good or quantity recalculates the BOM material requirement on save</span></div>
                        <div class="tip-item"><div class="tip-dot"></div><span>Raw materials are only consumed when the order is marked Completed</span></div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection
