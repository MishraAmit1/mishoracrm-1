@php
    /** @var \App\Models\VendorBill|null $bill */
    $isEdit    = isset($bill) && $bill->exists;
    $formItems = $isEdit
        ? $bill->items
        : ($prefill['items'] ?? old('items', []));
    $selVendor = $isEdit ? $bill->vendor_id : old('vendor_id', $prefill['vendor_id'] ?? '');
    $selPo     = $isEdit ? $bill->purchase_order_id : old('purchase_order_id', $prefill['purchase_order_id'] ?? '');
@endphp

@push('styles')
<style>
@import url('https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.19.0/dist/tabler-icons.min.css');
.vbf-card { background:var(--bg-surface); border:1px solid var(--border-default); border-radius:14px; overflow:hidden; margin-bottom:14px; max-width:900px; }
.vbf-section { padding:20px 22px; border-bottom:1px solid var(--border-subtle); }
.vbf-section:last-child { border-bottom:none; }
.vbf-grid { display:grid; grid-template-columns:1fr 1fr; gap:14px; }
.vbf-grid .span-full { grid-column:1/-1; }
@media(max-width:640px){ .vbf-grid { grid-template-columns:1fr; } .vbf-grid .span-full { grid-column:1; } }
.vbf-field { display:flex; flex-direction:column; gap:5px; }
.vbf-label { font-size:11.5px; font-weight:600; color:var(--text-200); text-transform:uppercase; letter-spacing:.5px; }
.vbf-req { color:var(--red); }
.vbf-hint { font-size:12px; color:var(--text-400); }
.vbf-err { font-size:12px; color:var(--red); font-weight:500; }
.vbf-input { width:100%; padding:9px 12px; background:var(--bg-input); border:1.5px solid var(--border-default); border-radius:8px; color:var(--text-100); font-family:var(--font); font-size:13.5px; outline:none; }
.vbf-input:focus { border-color:var(--accent); box-shadow:0 0 0 3px var(--accent-dim); }
.vbf-items { width:100%; border-collapse:collapse; font-size:13px; min-width:620px; }
.vbf-items thead tr { background:var(--bg-elevated); }
.vbf-items th { padding:8px 10px; text-align:left; font-size:11px; font-weight:600; color:var(--text-300); text-transform:uppercase; letter-spacing:.5px; border-bottom:1px solid var(--border-subtle); }
.vbf-items td { padding:7px 6px; border-bottom:1px solid var(--border-subtle); vertical-align:top; }
.item-input { width:100%; padding:7px 9px; background:var(--bg-input); border:1.5px solid var(--border-default); border-radius:7px; color:var(--text-100); font-family:var(--font); font-size:13px; outline:none; }
.item-amount-input { font-family:var(--mono); font-weight:600; background:var(--bg-elevated); border-color:var(--border-subtle); cursor:default; }
.del-row-btn { width:28px; height:28px; border-radius:6px; background:transparent; border:1px solid var(--border-subtle); cursor:pointer; color:var(--text-400); }
.del-row-btn:hover { background:var(--red-dim); border-color:var(--red); color:var(--red); }
.add-item-btn { display:flex; align-items:center; gap:6px; padding:9px 16px; margin:12px 0 0; background:transparent; border:1.5px dashed var(--border-default); border-radius:8px; font-size:13px; color:var(--text-300); cursor:pointer; font-family:var(--font); }
.add-item-btn:hover { border-color:var(--accent); color:var(--accent); }
.vbf-totals { display:flex; justify-content:flex-end; padding:16px 22px; border-top:1px solid var(--border-subtle); background:var(--bg-elevated); }
.vbf-totals table { width:280px; }
.vbf-totals td { padding:5px 0; font-size:13px; color:var(--text-200); }
.vbf-totals td:last-child { text-align:right; font-family:var(--mono); font-weight:500; color:var(--text-100); }
.vbf-totals .grand td { padding-top:10px; font-size:15px; font-weight:600; color:var(--text-100); border-top:1px solid var(--border-default); }
.vbf-totals .grand td:last-child { color:var(--accent); font-size:16px; }
</style>
@endpush

@if($errors->any())
<div style="padding:10px 14px;background:var(--red-dim);border:1px solid var(--red);border-radius:8px;margin-bottom:14px;font-size:13px;color:var(--red);max-width:900px">
    {{ $errors->first() }}
</div>
@endif

<form method="POST" action="{{ $action }}" id="vbForm">
    @csrf
    @if($isEdit) @method('PUT') @endif
    <input type="hidden" name="purchase_order_id" value="{{ $selPo }}">

    <div class="vbf-card">
        <div class="vbf-section">
            <div class="vbf-grid">
                <div class="vbf-field">
                    <label class="vbf-label">Bill Number</label>
                    <input type="text" class="vbf-input" value="{{ $isEdit ? $bill->number : $number }}" readonly
                           style="background:var(--bg-elevated);color:var(--text-300);font-family:var(--mono)"/>
                </div>
                <div class="vbf-field">
                    <label class="vbf-label" for="vb_vendor">Vendor <span class="vbf-req">*</span></label>
                    <select name="vendor_id" id="vb_vendor" class="vbf-input" required>
                        <option value="">— Select Vendor —</option>
                        @foreach($vendors as $vd)
                        <option value="{{ $vd->id }}" data-terms="{{ $vd->payment_terms_days }}" {{ (string) $selVendor === (string) $vd->id ? 'selected' : '' }}>
                            {{ $vd->name }}@if($vd->company) — {{ $vd->company }}@endif
                        </option>
                        @endforeach
                    </select>
                    @error('vendor_id')<span class="vbf-err">{{ $message }}</span>@enderror
                </div>
                <div class="vbf-field">
                    <label class="vbf-label" for="vb_vinv">Vendor's Invoice No.</label>
                    <input type="text" name="vendor_invoice_number" id="vb_vinv" class="vbf-input"
                           value="{{ old('vendor_invoice_number', $isEdit ? $bill->vendor_invoice_number : '') }}"
                           placeholder="Their reference"/>
                </div>
                <div class="vbf-field">
                    <label class="vbf-label" for="vb_date">Bill Date <span class="vbf-req">*</span></label>
                    <input type="date" name="date" id="vb_date" class="vbf-input"
                           value="{{ old('date', $isEdit ? $bill->date->format('Y-m-d') : now()->format('Y-m-d')) }}" required/>
                    @error('date')<span class="vbf-err">{{ $message }}</span>@enderror
                </div>
                <div class="vbf-field">
                    <label class="vbf-label" for="vb_due">Due Date</label>
                    <input type="date" name="due_date" id="vb_due" class="vbf-input"
                           value="{{ old('due_date', $isEdit && $bill->due_date ? $bill->due_date->format('Y-m-d') : '') }}"/>
                    <span class="vbf-hint">Auto-filled from the vendor's payment terms</span>
                    @error('due_date')<span class="vbf-err">{{ $message }}</span>@enderror
                </div>
            </div>
        </div>

        <div class="vbf-section">
            <div style="font-size:13px;font-weight:600;color:var(--text-100);margin-bottom:12px">Line Items <span class="vbf-req">*</span></div>
            <div style="overflow-x:auto">
                <table class="vbf-items" id="vbItems">
                    <thead>
                        <tr>
                            <th style="width:24%">Item</th>
                            <th style="width:22%">Description</th>
                            <th style="width:10%">Qty</th>
                            <th style="width:15%">Rate</th>
                            <th style="width:10%">GST %</th>
                            <th style="width:15%">Amount</th>
                            <th style="width:4%"></th>
                        </tr>
                    </thead>
                    <tbody id="vbItemsBody"></tbody>
                </table>
            </div>
            <button type="button" class="add-item-btn" onclick="vbAddRow()">
                <i class="ti ti-plus" style="font-size:14px"></i> Add Item
            </button>
        </div>

        <div class="vbf-totals">
            <table>
                <tr><td>Subtotal</td><td id="vbSub">₹0.00</td></tr>
                <tr>
                    <td>Discount
                        <input type="number" name="discount" id="vbDiscount" class="vbf-input"
                               style="width:90px;display:inline-block;margin-left:6px;padding:4px 8px;height:28px;font-size:12.5px"
                               min="0" value="{{ old('discount', $isEdit ? $bill->discount : 0) }}" oninput="vbRecalc()"/>
                    </td>
                    <td style="color:var(--red)" id="vbDisc">-₹0.00</td>
                </tr>
                <tr>
                    <td>Tax (<span id="vbTaxPct">0</span>%)
                        <input type="hidden" name="tax_percent" id="vbTaxHidden" value="{{ old('tax_percent', 0) }}">
                    </td>
                    <td style="color:var(--green)" id="vbTax">+₹0.00</td>
                </tr>
                <tr class="grand"><td>Total</td><td id="vbTotal">₹0.00</td></tr>
            </table>
        </div>

        <div class="vbf-section">
            <div class="vbf-field span-full">
                <label class="vbf-label">Notes</label>
                <textarea name="notes" class="vbf-input" rows="3" style="resize:vertical">{{ old('notes', $isEdit ? $bill->notes : '') }}</textarea>
            </div>
        </div>

        <div style="display:flex;justify-content:flex-end;gap:8px;padding:15px 22px;background:var(--bg-elevated);border-top:1px solid var(--border-subtle)">
            <a href="{{ $isEdit ? route('tenant.vendor-bills.show', $bill->id) : route('tenant.vendor-bills.index') }}" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary">
                <i class="ti ti-device-floppy" style="font-size:14px"></i> {{ $isEdit ? 'Save Changes' : 'Create Bill' }}
            </button>
        </div>
    </div>
</form>

@include('tenant.partials.product-search-js')

@push('scripts')
<script>
(function(){
window.PRODUCTS = @json($products->keyBy('id'));
let vbIndex = 0;

window.fillRowFromProduct = function(i, p){
    const row = document.getElementById('vbrow_' + i);
    if (!row) return;
    row.querySelector(`[name="items[${i}][name]"]`).value        = p.name;
    row.querySelector(`[name="items[${i}][description]"]`).value  = p.description || '';
    row.querySelector(`[name="items[${i}][rate]"]`).value         = (p.cost_price ?? p.rate);
    const pid = row.querySelector(`[name="items[${i}][product_id]"]`);
    if (pid) pid.value = p.id;
    const tax = row.querySelector(`[name="items[${i}][tax_percent]"]`);
    if (tax) tax.value = p.tax_percent;
    vbCalcRow(i);
};

function esc(s){ return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }

window.vbAddRow = function(name='', desc='', qty=1, rate=0, taxPct='', productId=''){
    const i = vbIndex++;
    const amt = (parseFloat(qty)||0) * (parseFloat(rate)||0);
    const tbody = document.getElementById('vbItemsBody');
    const tr = document.createElement('tr');
    tr.id = 'vbrow_' + i;
    tr.innerHTML = `
        <td>
            <div id="vbps_${i}"></div>
            <input type="hidden" name="items[${i}][product_id]" value="${productId}"/>
            <input type="text" name="items[${i}][name]" class="item-input" placeholder="Item name" value="${esc(name)}" required/>
        </td>
        <td><input type="text" name="items[${i}][description]" class="item-input" value="${esc(desc)}"/></td>
        <td><input type="number" name="items[${i}][quantity]" class="item-input" value="${qty}" min="0.01" step="0.01" required oninput="vbCalcRow(${i})"/></td>
        <td><input type="number" name="items[${i}][rate]" class="item-input" value="${rate}" min="0" step="0.01" required oninput="vbCalcRow(${i})"/></td>
        <td><input type="number" name="items[${i}][tax_percent]" class="item-input" value="${taxPct!==''?taxPct:0}" min="0" max="100" step="0.1" oninput="vbRecalc()"/></td>
        <td><input type="number" name="items[${i}][amount]" class="item-input item-amount-input" id="vbamt_${i}" value="${amt.toFixed(2)}" readonly/></td>
        <td><button type="button" class="del-row-btn" onclick="document.getElementById('vbrow_${i}').remove(); vbRecalc();"><i class="ti ti-trash" style="font-size:13px"></i></button></td>
    `;
    tbody.appendChild(tr);
    if (window.buildProductSearch) buildProductSearch(i, document.getElementById('vbps_' + i));
    vbRecalc();
};

window.vbCalcRow = function(i){
    const qty  = parseFloat(document.querySelector(`[name="items[${i}][quantity]"]`)?.value) || 0;
    const rate = parseFloat(document.querySelector(`[name="items[${i}][rate]"]`)?.value)     || 0;
    const amt  = qty * rate;
    const el = document.getElementById('vbamt_' + i);
    if (el) el.value = amt.toFixed(2);
    vbRecalc();
};

window.vbRecalc = function(){
    let sub = 0, tax = 0;
    document.querySelectorAll('#vbItemsBody tr').forEach(tr => {
        const qty  = parseFloat(tr.querySelector('[name$="[quantity]"]')?.value) || 0;
        const rate = parseFloat(tr.querySelector('[name$="[rate]"]')?.value)     || 0;
        const tpc  = parseFloat(tr.querySelector('[name$="[tax_percent]"]')?.value) || 0;
        const row  = qty * rate;
        sub += row;
        tax += row * tpc / 100;
    });
    const disc = parseFloat(document.getElementById('vbDiscount')?.value) || 0;
    const afterDisc = Math.max(0, sub - disc);
    const ratio = sub > 0 ? afterDisc / sub : 1;
    tax = tax * ratio;
    const total = afterDisc + tax;
    const avgPct = afterDisc > 0 ? (tax / afterDisc * 100) : 0;
    const fmt = n => '₹' + n.toLocaleString('en-IN', {minimumFractionDigits:2, maximumFractionDigits:2});
    document.getElementById('vbSub').textContent   = fmt(sub);
    document.getElementById('vbDisc').textContent  = '-' + fmt(disc);
    document.getElementById('vbTax').textContent   = '+' + fmt(tax);
    document.getElementById('vbTaxPct').textContent = avgPct.toFixed(1);
    document.getElementById('vbTotal').textContent = fmt(total);
    document.getElementById('vbTaxHidden').value   = avgPct.toFixed(2);
};

// Auto-fill due date from the selected vendor's payment terms (only when
// the user hasn't already set one).
const vendorSel = document.getElementById('vb_vendor');
const dateInput = document.getElementById('vb_date');
const dueInput  = document.getElementById('vb_due');
function syncDueDate(){
    const opt = vendorSel.options[vendorSel.selectedIndex];
    const terms = parseInt(opt?.dataset.terms || '', 10);
    if (!isNaN(terms) && dateInput.value) {
        const d = new Date(dateInput.value);
        d.setDate(d.getDate() + terms);
        dueInput.value = d.toISOString().slice(0, 10);
    }
}
vendorSel.addEventListener('change', syncDueDate);
dateInput.addEventListener('change', function(){ if (!dueInput.value) syncDueDate(); });

const seed = @json(collect($formItems)->values());
if (seed && seed.length) {
    seed.forEach(it => vbAddRow(it.name||'', it.description||'', it.quantity||1, it.rate||0,
        (it.tax_percent!==undefined && it.tax_percent!==null) ? it.tax_percent : '', it.product_id||''));
} else {
    vbAddRow();
}
vbRecalc();
})();
</script>
@endpush
