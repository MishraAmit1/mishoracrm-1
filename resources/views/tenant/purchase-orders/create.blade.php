@extends('layouts.app')
@section('title', 'New Purchase Order — ' . $number)

@push('styles')
<style>
@import url('https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=DM+Mono:wght@400;500&display=swap');
@import url('https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.19.0/dist/tabler-icons.min.css');

.qf { font-family: 'DM Sans', var(--font), sans-serif; }
.qf-layout { display:grid; grid-template-columns:minmax(0,1fr) 280px; gap:16px; margin-top:20px; }
@media(max-width:960px){ .qf-layout { grid-template-columns:1fr; } }
.qf-card { background:var(--bg-surface); border:1px solid var(--border-default); border-radius:14px; overflow:hidden; }
.qf-section { padding:20px 22px; border-bottom:1px solid var(--border-subtle); }
.qf-section:last-of-type { border-bottom:none; }
.qf-sec-head { display:flex; align-items:flex-start; gap:11px; margin-bottom:16px; }
.qf-sec-icon { width:30px; height:30px; border-radius:8px; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.qf-sec-title { font-size:13px; font-weight:600; color:var(--text-100); }
.qf-sec-sub { font-size:12px; color:var(--text-300); margin-top:1px; }
.qf-grid { display:grid; grid-template-columns:1fr 1fr; gap:14px; }
.qf-grid .span-full { grid-column:1/-1; }
@media(max-width:640px){ .qf-grid { grid-template-columns:1fr; } .qf-grid .span-full { grid-column:1; } }
.qf-field { display:flex; flex-direction:column; gap:5px; }
.qf-label { font-size:11.5px; font-weight:600; color:var(--text-200); text-transform:uppercase; letter-spacing:.5px; }
.qf-req { color:var(--red); margin-left:2px; }
.qf-hint { font-size:12px; color:var(--text-400); }
.qf-err { font-size:12px; color:var(--red); font-weight:500; }
.qf-input {
    width:100%; padding:9px 12px; background:var(--bg-input); border:1.5px solid var(--border-default);
    border-radius:8px; color:var(--text-100); font-family:'DM Sans',var(--font),sans-serif; font-size:13.5px; outline:none;
    transition:border-color .15s, box-shadow .15s;
}
.qf-input:focus { border-color:var(--accent); box-shadow:0 0 0 3px var(--accent-dim); }
.qf-sel { cursor:pointer; }
.qf-area { resize:vertical; min-height:80px; line-height:1.55; }

.items-table-wrap { overflow-x:auto; }
.items-table { width:100%; border-collapse:collapse; font-size:13px; min-width:620px; }
.items-table thead tr { background:var(--bg-elevated); }
.items-table th { padding:8px 10px; text-align:left; font-size:11px; font-weight:600; color:var(--text-300); text-transform:uppercase; letter-spacing:.5px; border-bottom:1px solid var(--border-subtle); }
.items-table td { padding:7px 6px; border-bottom:1px solid var(--border-subtle); vertical-align:top; }
.items-table tr:last-child td { border-bottom:none; }
.item-input { width:100%; padding:7px 9px; background:var(--bg-input); border:1.5px solid var(--border-default); border-radius:7px; color:var(--text-100); font-family:'DM Sans',var(--font),sans-serif; font-size:13px; outline:none; }
.item-input:focus { border-color:var(--accent); box-shadow:0 0 0 2px var(--accent-dim); }
.item-amount-input { font-family:'DM Mono',monospace; font-weight:600; background:var(--bg-elevated); color:var(--text-100); border-color:var(--border-subtle); cursor:default; }
.del-row-btn { width:28px; height:28px; border-radius:6px; background:transparent; border:1px solid var(--border-subtle); cursor:pointer; color:var(--text-400); display:flex; align-items:center; justify-content:center; margin:2px auto 0; }
.del-row-btn:hover { background:var(--red-dim); border-color:var(--red); color:var(--red); }
.add-item-btn { display:flex; align-items:center; gap:6px; padding:9px 16px; margin:12px 0 0; background:transparent; border:1.5px dashed var(--border-default); border-radius:8px; font-size:13px; color:var(--text-300); cursor:pointer; font-family:'DM Sans',var(--font),sans-serif; }
.add-item-btn:hover { border-color:var(--accent); color:var(--accent); }

.totals-wrap { display:flex; justify-content:flex-end; padding:16px 22px; border-top:1px solid var(--border-subtle); background:var(--bg-elevated); }
.totals-table { width:280px; }
.totals-table tr td { padding:5px 0; font-size:13px; color:var(--text-200); }
.totals-table tr td:last-child { text-align:right; font-family:'DM Mono',monospace; font-weight:500; color:var(--text-100); }
.totals-table .grand-total td { padding-top:10px; font-size:15px; font-weight:600; color:var(--text-100); border-top:1px solid var(--border-default); }
.totals-table .grand-total td:last-child { color:var(--accent); font-size:16px; }

.qf-footer { display:flex; align-items:center; justify-content:space-between; padding:15px 22px; background:var(--bg-elevated); border-top:1px solid var(--border-subtle); }
.qf-footer-note { font-size:12px; color:var(--text-300); }
.qf-sidebar { display:flex; flex-direction:column; gap:13px; }
.qf-sc { background:var(--bg-surface); border:1px solid var(--border-default); border-radius:14px; padding:17px; }
.qf-sc-title { font-size:11px; font-weight:600; color:var(--text-300); text-transform:uppercase; letter-spacing:.6px; margin-bottom:13px; }
.qp-number { font-size:18px; font-weight:600; color:var(--text-100); font-family:'DM Mono',monospace; }
.qp-total { font-size:28px; font-weight:600; color:var(--accent); font-family:'DM Mono',monospace; margin-top:8px; }
.tip-list { display:flex; flex-direction:column; gap:9px; }
.tip-item { display:flex; align-items:flex-start; gap:8px; font-size:12px; color:var(--text-300); line-height:1.45; }
.tip-dot { width:5px; height:5px; border-radius:50%; background:var(--accent); margin-top:5px; flex-shrink:0; }
</style>
@endpush

@section('content')
<div class="qf">

    <div class="page-head">
        <div>
            <div style="font-size:12px;color:var(--text-300);margin-bottom:4px;display:flex;align-items:center;gap:5px">
                <a href="{{ route('tenant.purchase-orders.index') }}" style="color:var(--text-300);text-decoration:none">Purchase Orders</a>
                <span style="opacity:.4">›</span>
                <span>New Purchase Order</span>
            </div>
            <div class="page-title">New Purchase Order</div>
        </div>
        <a href="{{ route('tenant.purchase-orders.index') }}" class="btn btn-secondary">
            <i class="ti ti-arrow-left" style="font-size:14px"></i> Back
        </a>
    </div>

    @if($purchaseRequest ?? null)
    <div style="display:flex;align-items:center;gap:9px;padding:10px 14px;background:var(--green-dim);border:1px solid var(--green);border-radius:8px;margin-bottom:14px;font-size:12.5px;color:var(--green);font-weight:500">
        <i class="ti ti-clipboard-list" style="font-size:15px"></i>
        Created from Purchase Request <strong>{{ $purchaseRequest->number }}</strong>
    </div>
    @endif

    <form method="POST" action="{{ route('tenant.purchase-orders.store') }}" novalidate id="poForm">
        @csrf
        <input type="hidden" name="status" id="statusHidden" value="draft">
        <input type="hidden" name="purchase_request_id" value="{{ old('purchase_request_id', $purchaseRequest->id ?? '') }}">

        <div class="qf-layout">
            <div>
                <div class="qf-card" style="margin-bottom:14px">
                    <div class="qf-section">
                        <div class="qf-sec-head">
                            <div class="qf-sec-icon" style="background:var(--accent-dim)">
                                <i class="ti ti-file-invoice" style="font-size:15px;color:var(--accent)"></i>
                            </div>
                            <div>
                                <div class="qf-sec-title">Purchase Order Details</div>
                                <div class="qf-sec-sub">Number, date and expected delivery</div>
                            </div>
                        </div>
                        <div class="qf-grid">
                            <div class="qf-field">
                                <label class="qf-label">PO Number</label>
                                <input type="text" class="qf-input" value="{{ $number }}" readonly
                                       style="background:var(--bg-elevated);color:var(--text-300);cursor:default;font-family:'DM Mono',monospace"/>
                            </div>
                            <div class="qf-field">
                                <label class="qf-label" for="po_date">Date <span class="qf-req">*</span></label>
                                <input type="date" name="date" id="po_date" class="qf-input {{ $errors->has('date')?'is-err':'' }}"
                                       value="{{ old('date', now()->format('Y-m-d')) }}" required/>
                                @error('date')<span class="qf-err">{{ $message }}</span>@enderror
                            </div>
                            <div class="qf-field">
                                <label class="qf-label" for="po_expected">Expected Delivery</label>
                                <input type="date" name="expected_delivery_date" id="po_expected"
                                       class="qf-input {{ $errors->has('expected_delivery_date')?'is-err':'' }}"
                                       value="{{ old('expected_delivery_date') }}"/>
                                @error('expected_delivery_date')<span class="qf-err">{{ $message }}</span>@enderror
                            </div>
                        </div>
                    </div>

                    <div class="qf-section">
                        <div class="qf-sec-head">
                            <div class="qf-sec-icon" style="background:var(--purple-dim)">
                                <i class="ti ti-building-warehouse" style="font-size:15px;color:var(--purple)"></i>
                            </div>
                            <div>
                                <div class="qf-sec-title">Vendor</div>
                                <div class="qf-sec-sub">Who is this order going to?</div>
                            </div>
                        </div>
                        <div class="qf-grid">
                            <div class="qf-field span-full">
                                <label class="qf-label" for="po_vendor">Vendor</label>
                                <select name="vendor_id" id="po_vendor" class="qf-input qf-sel {{ $errors->has('vendor_id')?'is-err':'' }}">
                                    <option value="">— Select Vendor (optional) —</option>
                                    @foreach($vendors as $vd)
                                    <option value="{{ $vd->id }}" {{ old('vendor_id') == $vd->id ? 'selected':'' }}>
                                        {{ $vd->name }}@if($vd->company) — {{ $vd->company }}@endif
                                    </option>
                                    @endforeach
                                </select>
                                <span class="qf-hint">A vendor is required before this PO can be marked as Sent</span>
                                @error('vendor_id')<span class="qf-err">{{ $message }}</span>@enderror
                            </div>
                        </div>
                    </div>
                </div>

                <div class="qf-card" style="margin-bottom:14px">
                    <div class="qf-section" style="border-bottom:none;padding-bottom:0">
                        <div class="qf-sec-head">
                            <div class="qf-sec-icon" style="background:var(--green-dim)">
                                <i class="ti ti-list-details" style="font-size:15px;color:var(--green)"></i>
                            </div>
                            <div>
                                <div class="qf-sec-title">Line Items <span class="qf-req">*</span></div>
                                <div class="qf-sec-sub">Products to procure</div>
                            </div>
                        </div>
                        <div class="items-table-wrap">
                            <table class="items-table" id="itemsTable">
                                <thead>
                                    <tr>
                                        <th style="width:22%">Item</th>
                                        <th style="width:20%">Description</th>
                                        <th style="width:10%">Qty</th>
                                        <th style="width:14%">Rate</th>
                                        <th style="width:10%">GST %</th>
                                        <th style="width:16%">Amount</th>
                                        <th style="width:8%"></th>
                                    </tr>
                                </thead>
                                <tbody id="itemsBody"></tbody>
                            </table>
                        </div>
                        <button type="button" class="add-item-btn" onclick="addItemRow()">
                            <i class="ti ti-plus" style="font-size:14px"></i> Add Item
                        </button>
                    </div>

                    <div class="totals-wrap">
                        <table class="totals-table">
                            <tr>
                                <td>Subtotal</td>
                                <td id="displaySubtotal">₹0.00</td>
                            </tr>
                            <tr>
                                <td>
                                    Discount
                                    <input type="number" name="discount" id="discountInput"
                                           class="qf-input" style="width:80px;display:inline-block;margin-left:6px;padding:4px 8px;height:28px;font-size:12.5px"
                                           placeholder="0" min="0" value="{{ old('discount', 0) }}" oninput="recalcTotals()"/>
                                </td>
                                <td id="displayDiscount" style="color:var(--red)">-₹0.00</td>
                            </tr>
                            <tr>
                                <td>
                                    Tax (<span id="dispTaxPct">0</span>%)
                                    <input type="hidden" name="tax_percent" id="taxPercentHidden" value="{{ old('tax_percent', 0) }}">
                                </td>
                                <td id="displayTax" style="color:var(--green)">+₹0.00</td>
                            </tr>
                            <tr class="grand-total">
                                <td><strong>Total</strong></td>
                                <td id="displayTotal"><strong>₹0.00</strong></td>
                            </tr>
                        </table>
                    </div>

                    <input type="hidden" name="subtotal" id="hiddenSubtotal">
                    <input type="hidden" name="tax_amount" id="hiddenTaxAmount">
                    <input type="hidden" name="total" id="hiddenTotal">
                </div>

                <div class="qf-card">
                    <div class="qf-section">
                        <div class="qf-sec-head">
                            <div class="qf-sec-icon" style="background:var(--amber-dim)">
                                <i class="ti ti-notes" style="font-size:15px;color:var(--amber)"></i>
                            </div>
                            <div>
                                <div class="qf-sec-title">Notes & Terms</div>
                                <div class="qf-sec-sub">Additional details for this order</div>
                            </div>
                        </div>
                        <div class="qf-grid">
                            <div class="qf-field span-full">
                                <label class="qf-label">Notes</label>
                                <textarea name="notes" class="qf-input qf-area" rows="3" placeholder="Internal notes...">{{ old('notes') }}</textarea>
                            </div>
                            <div class="qf-field span-full">
                                <label class="qf-label">Terms & Conditions</label>
                                <textarea name="terms" class="qf-input qf-area" rows="4" placeholder="Payment terms, delivery conditions...">{{ old('terms') }}</textarea>
                            </div>
                        </div>
                    </div>
                    <div class="qf-footer">
                        <div class="qf-footer-note">Fields marked <strong>*</strong> are required</div>
                        <div style="display:flex;gap:8px">
                            <a href="{{ route('tenant.purchase-orders.index') }}" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary" id="submitBtn">
                                <i class="ti ti-device-floppy" id="submitIcon" style="font-size:14px"></i>
                                <span id="submitText">Save Draft</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="qf-sidebar">
                <div class="qf-sc">
                    <div class="qf-sc-title">Purchase Order Preview</div>
                    <div class="qp-number">{{ $number }}</div>
                    <div class="qp-total" id="sidebarTotal">₹0.00</div>
                    <div style="font-size:12px;color:var(--text-300);margin-top:3px" id="sidebarItemsCount">0 items</div>
                </div>
                <div class="qf-sc">
                    <div class="qf-sc-title">Tips</div>
                    <div class="tip-list">
                        <div class="tip-item"><div class="tip-dot"></div><span>Selecting a product prefills its cost estimate as the rate</span></div>
                        <div class="tip-item"><div class="tip-dot"></div><span>A vendor is required before you can mark this as Sent</span></div>
                        <div class="tip-item"><div class="tip-dot"></div><span>Received quantities are tracked once the order is Sent</span></div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@include('tenant.partials.product-search-js')

@push('scripts')
<script>
(function(){

window.PRODUCTS = @json($products->keyBy('id'));
let rowIndex = 0;

window.fillRowFromProduct = function(i, p) {
    const row = document.getElementById('row_' + i);
    if (!row) return;
    row.querySelector(`[name="items[${i}][name]"]`).value        = p.name;
    row.querySelector(`[name="items[${i}][description]"]`).value = p.description || '';
    row.querySelector(`[name="items[${i}][rate]"]`).value        = (p.cost_price ?? p.rate);
    const productIdInput = row.querySelector(`[name="items[${i}][product_id]"]`);
    if (productIdInput) productIdInput.value = p.id;
    const taxInput = row.querySelector(`[name="items[${i}][tax_percent]"]`);
    if (taxInput) taxInput.value = p.tax_percent;
    calcRowAmount(i);
};

function esc(s){ return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }

function addItemRow(name='', desc='', qty=1, rate=0, taxPct='', productId=''){
    const i    = rowIndex++;
    const amt  = (parseFloat(qty)||0) * (parseFloat(rate)||0);
    const gst  = taxPct !== '' ? taxPct : 0;
    const tbody = document.getElementById('itemsBody');
    const tr   = document.createElement('tr');
    tr.id      = 'row_' + i;
    tr.innerHTML = `
        <td data-label="Item">
            <div id="ps_container_${i}"></div>
            <input type="hidden" name="items[${i}][product_id]" value="${productId}"/>
            <input type="text" name="items[${i}][name]" class="item-input" placeholder="Item name" value="${esc(name)}" required/>
        </td>
        <td data-label="Description">
            <input type="text" name="items[${i}][description]" class="item-input" placeholder="Optional description" value="${esc(desc)}"/>
        </td>
        <td data-label="Qty">
            <input type="number" name="items[${i}][quantity]" class="item-input" value="${qty}" min="0.01" step="0.01" required oninput="calcRowAmount(${i})"/>
        </td>
        <td data-label="Rate">
            <input type="number" name="items[${i}][rate]" class="item-input" value="${rate}" min="0" step="0.01" required oninput="calcRowAmount(${i})"/>
        </td>
        <td data-label="GST %">
            <input type="number" name="items[${i}][tax_percent]" class="item-input" value="${gst}" min="0" max="100" step="0.1" oninput="recalcTotals()"/>
        </td>
        <td data-label="Amount">
            <input type="number" name="items[${i}][amount]" class="item-input item-amount-input" id="amt_${i}" value="${amt.toFixed(2)}" readonly/>
        </td>
        <td>
            <button type="button" class="del-row-btn" onclick="delRow(${i})" title="Remove">
                <i class="ti ti-trash" style="font-size:13px"></i>
            </button>
        </td>
    `;
    tbody.appendChild(tr);
    buildProductSearch(i, document.getElementById('ps_container_' + i));
    recalcTotals();
}
window.addItemRow = addItemRow;

window.calcRowAmount = function(i){
    const qty  = parseFloat(document.querySelector(`[name="items[${i}][quantity]"]`)?.value) || 0;
    const rate = parseFloat(document.querySelector(`[name="items[${i}][rate]"]`)?.value)     || 0;
    const amt  = qty * rate;
    const el   = document.getElementById('amt_' + i);
    if(el){ el.value = amt.toFixed(2); document.querySelector(`[name="items[${i}][amount]"]`).value = amt.toFixed(2); }
    recalcTotals();
};

window.delRow = function(i){
    document.getElementById('row_' + i)?.remove();
    recalcTotals();
};

window.recalcTotals = function(){
    let subtotal = 0;
    let taxAmt   = 0;

    document.querySelectorAll('#itemsBody tr').forEach(tr => {
        const qty    = parseFloat(tr.querySelector('[name$="[quantity]"]')?.value)    || 0;
        const rate   = parseFloat(tr.querySelector('[name$="[rate]"]')?.value)        || 0;
        const taxPct = parseFloat(tr.querySelector('[name$="[tax_percent]"]')?.value) || 0;
        const rowAmt = qty * rate;
        subtotal += rowAmt;
        taxAmt   += rowAmt * taxPct / 100;
    });

    const discount  = parseFloat(document.getElementById('discountInput')?.value) || 0;
    const afterDisc = Math.max(0, subtotal - discount);
    const discRatio = subtotal > 0 ? afterDisc / subtotal : 1;
    taxAmt          = taxAmt * discRatio;
    const total     = afterDisc + taxAmt;
    const avgTaxPct = afterDisc > 0 ? (taxAmt / afterDisc * 100) : 0;

    const fmt = n => '₹' + n.toLocaleString('en-IN', {minimumFractionDigits:2, maximumFractionDigits:2});

    document.getElementById('displaySubtotal').textContent = fmt(subtotal);
    document.getElementById('displayDiscount').textContent = '-' + fmt(discount);
    document.getElementById('displayTax').textContent      = '+' + fmt(taxAmt);
    document.getElementById('dispTaxPct').textContent      = avgTaxPct.toFixed(1);
    document.getElementById('displayTotal').innerHTML      = '<strong>' + fmt(total) + '</strong>';

    document.getElementById('hiddenSubtotal').value   = subtotal.toFixed(2);
    document.getElementById('hiddenTaxAmount').value  = taxAmt.toFixed(2);
    document.getElementById('hiddenTotal').value      = total.toFixed(2);
    document.getElementById('taxPercentHidden').value = avgTaxPct.toFixed(2);

    document.getElementById('sidebarTotal').textContent = fmt(total);
    const rows = document.querySelectorAll('#itemsBody tr');
    document.getElementById('sidebarItemsCount').textContent = rows.length + (rows.length===1?' item':' items');
};

document.getElementById('poForm').addEventListener('submit', function(){
    const icon = document.getElementById('submitIcon');
    const text = document.getElementById('submitText');
    if(icon) icon.style.animation = 'qf-spin .7s linear infinite';
    if(text) text.textContent = 'Saving...';
    document.getElementById('submitBtn').disabled = true;
});

@if(old('items'))
const oldItems = @json(old('items'));
if(oldItems && oldItems.length){
    oldItems.forEach(item => addItemRow(item.name||'', item.description||'', item.quantity||1, item.rate||0, item.tax_percent||'', item.product_id||''));
} else {
    addItemRow();
}
@elseif($purchaseRequest ?? null)
const prItems = @json($purchaseRequest->items ?? []);
if(prItems.length){
    prItems.forEach(item => addItemRow(item.name||'', item.description||'', item.quantity||1, 0, '', item.product_id||''));
} else {
    addItemRow();
}
@else
addItemRow();
@endif

recalcTotals();

})();
</script>
@endpush
