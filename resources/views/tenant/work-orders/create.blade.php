@extends('layouts.app')
@section('title', 'New Work Order — ' . $number)

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
.pf-req { color:var(--red,#E24B4A); margin-left:2px; }
.pf-input {
    width:100%; padding:9px 12px; background:var(--bg-input); border:1.5px solid var(--border-default);
    border-radius:8px; color:var(--text-100); font-family:'DM Sans',var(--font),sans-serif; font-size:13.5px; outline:none;
    transition:border-color .15s, box-shadow .15s;
}
.pf-input:focus { border-color:var(--accent); box-shadow:0 0 0 3px var(--accent-dim); }
.pf-area { resize:vertical; min-height:80px; line-height:1.55; }
.pf-err { font-size:12px; color:var(--red,#E24B4A); font-weight:500; }
.pf-footer { display:flex; align-items:center; justify-content:space-between; padding:15px 22px; background:var(--bg-elevated); border-top:1px solid var(--border-subtle); }
.pf-footer-note { font-size:12px; color:var(--text-300); }
.pf-sidebar { display:flex; flex-direction:column; gap:13px; }
.pf-sc { background:var(--bg-surface); border:1px solid var(--border-default); border-radius:14px; padding:17px; }
.pf-sc-title { font-size:11px; font-weight:600; color:var(--text-300); text-transform:uppercase; letter-spacing:.6px; margin-bottom:13px; }
.pr-number { font-size:18px; font-weight:600; color:var(--text-100); font-family:'DM Mono',monospace; }
.tip-list { display:flex; flex-direction:column; gap:9px; }
.tip-item { display:flex; align-items:flex-start; gap:8px; font-size:12px; color:var(--text-300); line-height:1.45; }
.tip-dot { width:5px; height:5px; border-radius:50%; background:var(--accent,#378ADD); margin-top:5px; flex-shrink:0; }

.bom-table { width:100%; border-collapse:collapse; font-size:13px; margin-top:6px; }
.bom-table thead tr { background:var(--bg-elevated); }
.bom-table th { padding:8px 10px; text-align:left; font-size:11px; font-weight:600; color:var(--text-300); text-transform:uppercase; letter-spacing:.5px; border-bottom:1px solid var(--border-subtle); }
.bom-table td { padding:8px 10px; border-bottom:1px solid var(--border-subtle); }
.bom-table tr:last-child td { border-bottom:none; }
.bom-ok { color:#0F6E56; font-weight:600; }
.bom-short { color:#A32D2D; font-weight:600; }
.bom-empty { padding:14px; text-align:center; color:var(--text-300); font-size:12.5px; }
</style>
@endpush

@section('content')
<div class="pf">

    <div class="page-head">
        <div>
            <div style="font-size:12px;color:var(--text-300);margin-bottom:4px;display:flex;align-items:center;gap:5px">
                <a href="{{ route('tenant.work-orders.index') }}" style="color:var(--text-300);text-decoration:none">Work Orders</a>
                <span style="opacity:.4">›</span>
                <span>New Work Order</span>
            </div>
            <div class="page-title">New Work Order</div>
        </div>
        <a href="{{ route('tenant.work-orders.index') }}" class="btn btn-secondary">
            <i class="ti ti-arrow-left" style="font-size:14px"></i> Back
        </a>
    </div>

    <form method="POST" action="{{ route('tenant.work-orders.store') }}" novalidate id="woForm">
        @csrf
        <div class="pf-layout">
            <div>
                <div class="pf-card" style="margin-bottom:14px">
                    <div class="pf-section">
                        <div class="pf-sec-head">
                            <div class="pf-sec-icon" style="background:#E6F1FB">
                                <i class="ti ti-clipboard-list" style="font-size:15px;color:#185FA5"></i>
                            </div>
                            <div>
                                <div class="pf-sec-title">Work Order Details</div>
                                <div class="pf-sec-sub">What are you producing?</div>
                            </div>
                        </div>
                        <div class="pf-grid">
                            <div class="pf-field">
                                <label class="pf-label">Work Order Number</label>
                                <input type="text" class="pf-input" value="{{ $number }}" readonly
                                       style="background:var(--bg-elevated);color:var(--text-300);cursor:default;font-family:'DM Mono',monospace"/>
                            </div>
                            <div class="pf-field">
                                <label class="pf-label" for="wo_quantity">Quantity to Produce <span class="pf-req">*</span></label>
                                <input type="number" name="quantity" id="wo_quantity" class="pf-input" min="0.01" step="0.01"
                                       value="{{ old('quantity', 1) }}" required oninput="renderBom()"/>
                                @error('quantity')<span class="pf-err">{{ $message }}</span>@enderror
                            </div>
                            <div class="pf-field span-full">
                                <label class="pf-label" for="wo_product">Finished Good <span class="pf-req">*</span></label>
                                <select name="product_id" id="wo_product" class="pf-input" required onchange="renderBom()">
                                    <option value="">— Select finished good —</option>
                                    @foreach($products as $p)
                                    <option value="{{ $p->id }}" {{ old('product_id') == $p->id ? 'selected':'' }}>{{ $p->name }}{{ $p->product_code ? ' ['.$p->product_code.']' : '' }}</option>
                                    @endforeach
                                </select>
                                @error('product_id')<span class="pf-err">{{ $message }}</span>@enderror
                            </div>
                        </div>
                    </div>
                </div>

                <div class="pf-card" style="margin-bottom:14px">
                    <div class="pf-section" style="border-bottom:none">
                        <div class="pf-sec-head">
                            <div class="pf-sec-icon" style="background:#E1F5EE">
                                <i class="ti ti-list-details" style="font-size:15px;color:#0F6E56"></i>
                            </div>
                            <div>
                                <div class="pf-sec-title">Raw Materials Needed (BOM)</div>
                                <div class="pf-sec-sub">Auto-computed from the product's Bill of Materials</div>
                            </div>
                        </div>
                        <div id="bomPreview"><div class="bom-empty">Select a finished good to preview material needs.</div></div>
                    </div>
                </div>

                <div class="pf-card">
                    <div class="pf-section">
                        <div class="pf-sec-head">
                            <div class="pf-sec-icon" style="background:#FAEEDA">
                                <i class="ti ti-notes" style="font-size:15px;color:#BA7517"></i>
                            </div>
                            <div>
                                <div class="pf-sec-title">Notes</div>
                                <div class="pf-sec-sub">Optional production notes</div>
                            </div>
                        </div>
                        <div class="pf-grid">
                            <div class="pf-field span-full">
                                <textarea name="notes" class="pf-input pf-area" rows="3" placeholder="Any special instructions...">{{ old('notes') }}</textarea>
                                @error('notes')<span class="pf-err">{{ $message }}</span>@enderror
                            </div>
                        </div>
                    </div>
                    <div class="pf-footer">
                        <div class="pf-footer-note">Fields marked <strong>*</strong> are required</div>
                        <div style="display:flex;gap:8px">
                            <a href="{{ route('tenant.work-orders.index') }}" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="ti ti-send" style="font-size:14px"></i> Create Work Order
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="pf-sidebar">
                <div class="pf-sc">
                    <div class="pf-sc-title">Work Order Preview</div>
                    <div class="pr-number">{{ $number }}</div>
                </div>
                <div class="pf-sc">
                    <div class="pf-sc-title">Tips</div>
                    <div class="tip-list">
                        <div class="tip-item"><div class="tip-dot"></div><span>Only finished goods with stock/BOM setup appear in the picker</span></div>
                        <div class="tip-item"><div class="tip-dot"></div><span>Raw materials are only consumed when you mark the order Completed</span></div>
                        <div class="tip-item"><div class="tip-dot"></div><span>Completion is blocked if any material is short — top up stock first</span></div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
(function(){
const PRODUCTS = @json($products);

function esc(s){ return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }

window.renderBom = function(){
    const pid = document.getElementById('wo_product').value;
    const qty = parseFloat(document.getElementById('wo_quantity').value) || 0;
    const el = document.getElementById('bomPreview');
    const product = PRODUCTS.find(p => String(p.id) === String(pid));

    if(!product){
        el.innerHTML = '<div class="bom-empty">Select a finished good to preview material needs.</div>';
        return;
    }

    const bom = product.bill_of_materials || [];
    if(!bom.length){
        el.innerHTML = '<div class="bom-empty">No BOM configured for this product — nothing will be consumed on completion.</div>';
        return;
    }

    let rows = '';
    bom.forEach(line => {
        const material = line.material;
        if(!material) return;
        const needed = (parseFloat(line.quantity_per_unit) || 0) * qty;
        const available = parseFloat(material.current_stock) || 0;
        const ok = available >= needed;
        rows += `<tr>
            <td>${esc(material.name)}</td>
            <td style="text-align:right;font-family:'DM Mono',monospace">${needed.toFixed(2)}</td>
            <td style="text-align:right;font-family:'DM Mono',monospace">${available.toFixed(2)}</td>
            <td style="text-align:right" class="${ok ? 'bom-ok' : 'bom-short'}">${ok ? 'OK' : 'Short by ' + (needed-available).toFixed(2)}</td>
        </tr>`;
    });

    el.innerHTML = `<table class="bom-table"><thead><tr>
        <th>Material</th><th style="text-align:right">Needed</th><th style="text-align:right">In Stock</th><th style="text-align:right">Status</th>
    </tr></thead><tbody>${rows}</tbody></table>`;
};

renderBom();
})();
</script>
@endpush
