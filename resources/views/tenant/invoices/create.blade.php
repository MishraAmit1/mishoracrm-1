@extends('layouts.app')
@section('title', 'Create Invoice')

@push('styles')
<style>
.inv-layout { display:grid; grid-template-columns:1fr 320px; gap:16px; align-items:start; }
@media(max-width:1100px) { .inv-layout { grid-template-columns:1fr; } }

.form-card  { background:var(--bg-surface); border:1px solid var(--border-default); border-radius:var(--r-lg); overflow:hidden; }
.fc-section { padding:22px 24px; border-bottom:1px solid var(--border-subtle); }
.fc-title   { font-size:12.5px; font-weight:700; color:var(--text-300); text-transform:uppercase; letter-spacing:.4px; margin-bottom:16px; }

.field       { display:flex; flex-direction:column; gap:7px; }
.field-label { font-size:12.5px; font-weight:600; color:var(--text-200); text-transform:uppercase; letter-spacing:.3px; }
.req         { color:var(--red); margin-left:2px; }
.field-input {
    padding:10px 13px; background:var(--bg-input);
    border:1.5px solid var(--border-default); border-radius:var(--r-sm);
    color:var(--text-100); font-family:var(--font); font-size:14px; outline:none;
    transition:border-color .15s, box-shadow .15s;
    width:100%;
}
.field-input:focus { border-color:var(--accent); box-shadow:0 0 0 3px var(--accent-dim); }
.field-input::placeholder { color:var(--text-400); }
.field-select { -webkit-appearance:none; cursor:pointer; }
.field-error  { font-size:12px; color:var(--red); }
.form-grid    { display:grid; grid-template-columns:1fr 1fr; gap:14px; }
.span-2       { grid-column:1/-1; }
@media(max-width:640px) { .form-grid { grid-template-columns:1fr; } .span-2 { grid-column:1; } }

/* Items table */
.items-table  { width:100%; border-collapse:collapse; }
.items-table th { padding:8px 10px; text-align:left; font-size:11px; font-weight:700; color:var(--text-400); text-transform:uppercase; letter-spacing:.4px; border-bottom:1px solid var(--border-subtle); white-space:nowrap; }
.items-table th.right { text-align:right; }
.items-table td { padding:6px 6px; vertical-align:middle; }
.item-input { padding:8px 10px; background:var(--bg-input); border:1.5px solid var(--border-default); border-radius:var(--r-sm); color:var(--text-100); font-family:var(--font); font-size:13.5px; outline:none; transition:border-color .15s; width:100%; }
.item-input:focus { border-color:var(--accent); }
.item-input.right { text-align:right; }
.item-amount { font-size:13.5px; font-weight:600; color:var(--text-100); text-align:right; padding-right:4px; font-family:var(--mono); white-space:nowrap; }
.del-row { padding:6px 8px; border:none; background:var(--red-dim); color:var(--red); border-radius:var(--r-sm); cursor:pointer; font-size:13px; transition:background .15s; flex-shrink:0; }
.del-row:hover { background:var(--red); color:#fff; }
.add-row-btn { display:flex; align-items:center; gap:6px; padding:9px 14px; border:1.5px dashed var(--accent); background:none; color:var(--accent); border-radius:var(--r-sm); font-size:13px; font-weight:600; cursor:pointer; font-family:var(--font); transition:background .15s; margin-top:10px; }
.add-row-btn:hover { background:var(--accent-dim); }

@media(max-width:768px) {
    .items-table thead { display:none; }
    .items-table, .items-table tbody { display:block; width:100%; }
    .items-table tr {
        display:block; margin-bottom:12px; padding:12px;
        background:var(--bg-elevated); border:1px solid var(--border-default); border-radius:var(--r-md);
    }
    .items-table td {
        display:flex; align-items:center; justify-content:space-between;
        gap:10px; padding:6px 0;
    }
    .items-table td::before {
        content:attr(data-label);
        font-size:11px; font-weight:700; text-transform:uppercase;
        letter-spacing:0.4px; color:var(--text-400); flex-shrink:0;
    }
    .items-table td[style*="text-align:center"] { justify-content:flex-end; }
    .items-table td[style*="text-align:center"]::before { content:''; }
}

/* Totals */
.totals-box { background:var(--bg-elevated); border-radius:var(--r-sm); padding:16px; }
.total-row  { display:flex; justify-content:space-between; align-items:center; padding:6px 0; border-bottom:1px solid var(--border-subtle); font-size:13.5px; }
.total-row:last-child { border-bottom:none; padding-top:10px; margin-top:4px; }
.total-row.grand { font-size:16px; font-weight:800; color:var(--text-100); }
.total-label { color:var(--text-200); }
.total-value { font-family:var(--mono); font-weight:600; color:var(--text-100); }

/* Summary sidebar */
.summary-card { background:var(--bg-surface); border:1px solid var(--border-default); border-radius:var(--r-lg); overflow:hidden; position:sticky; top:80px; }
.sc-head { padding:14px 18px; border-bottom:1px solid var(--border-subtle); font-size:13.5px; font-weight:700; color:var(--text-100); }
.sc-body { padding:18px; }
.sc-row  { display:flex; justify-content:space-between; font-size:13px; padding:7px 0; border-bottom:1px solid var(--border-subtle); }
.sc-row:last-child { border-bottom:none; }
.sc-total { font-size:18px; font-weight:800; color:var(--accent); font-family:var(--mono); margin-top:12px; text-align:right; }

/* Contact preview */
.contact-preview { background:var(--bg-elevated); border-radius:var(--r-sm); padding:12px 14px; margin-top:10px; display:none; font-size:12.5px; line-height:1.7; color:var(--text-200); }

.form-footer { padding:16px 24px; background:var(--bg-elevated); border-top:1px solid var(--border-subtle); display:flex; justify-content:space-between; align-items:center; }
</style>
@endpush

@section('content')

@php
    $defaultTerms = config('crm.quotation.terms_default', "1. Payment due within 30 days.\n2. Prices are inclusive of GST unless stated otherwise.");
    $loyaltyOn = $tenant->hasModuleEnabled('loyalty');
    $contactsJson = $contacts->mapWithKeys(fn($c) => [
        $c->id => [
            'name'    => $c->name,
            'company' => $c->company,
            'phone'   => $c->phone,
            'email'   => $c->email,
            'address' => trim(collect([$c->address, $c->city, $c->state])->filter()->implode(', ')),
            'gst'     => $c->gst_number,
            'loyalty' => $loyaltyOn && $c->loyalty_points > 0
                ? number_format($c->loyalty_points) . ' pts' . ($c->loyalty_tier ? ' · ' . ucfirst($c->loyalty_tier) : '')
                : null,
        ]
    ]);

    // Pre-fill from quotation
    $prefillItems = $quotation ? $quotation->items : [['description'=>'','quantity'=>1,'rate'=>'','amount'=>0]];
    $prefillDiscount = $quotation ? $quotation->discount : 0;
    $prefillTax = $quotation ? $quotation->tax_percent : 18;
    $prefillNotes = $quotation ? $quotation->notes : '';
    $prefillTerms = $quotation ? $quotation->terms : $defaultTerms;
    $prefillContactId = $contact ? $contact->id : ($quotation?->contact_id ?? '');
@endphp

<div class="page-head">
    <div>
        <div style="font-size:13px;color:var(--text-300);margin-bottom:4px">
            <a href="{{ route('tenant.invoices.index') }}" style="color:var(--text-300);text-decoration:none">Invoices</a>
            <span style="margin:0 6px">›</span> Create
        </div>
        <div class="page-title">New Invoice</div>
        @if($quotation)
        <div class="page-sub">
            From Quotation: <strong>{{ $quotation->number }}</strong>
        </div>
        @endif
    </div>
    <a href="{{ route('tenant.invoices.index') }}" class="btn btn-secondary">← Back</a>
</div>

@if($errors->any())
<div style="padding:12px 16px;background:var(--red-dim);border:1px solid rgba(255,82,87,.25);border-radius:var(--r-sm);margin-bottom:16px;font-size:13px;color:var(--red)">
    {{ $errors->first() }}
</div>
@endif

<form method="POST" action="{{ route('tenant.invoices.store') }}" id="invForm">
@csrf
@if($quotation)
<input type="hidden" name="quotation_id" value="{{ $quotation->id }}"/>
@endif

<div class="inv-layout">

    {{-- ── Left: Main form ─────────────────────────────────────── --}}
    <div style="display:flex;flex-direction:column;gap:0">
        <div class="form-card">

            {{-- Header info --}}
            <div class="fc-section">
                <div class="fc-title">Invoice Details</div>
                <div class="form-grid">

                    <div class="field span-2">
                        <label class="field-label">Bill To <span class="req">*</span></label>
                        <select name="contact_id" id="contactSelect"
                                class="field-input field-select {{ $errors->has('contact_id') ? 'is-error':'' }}"
                                onchange="loadContact(this.value)" required>
                            <option value="">— Select Contact —</option>
                            @foreach($contacts as $c)
                            <option value="{{ $c->id }}"
                                {{ (old('contact_id', $prefillContactId) == $c->id) ? 'selected':'' }}>
                                {{ $c->name }}{{ $c->company ? ' ('.$c->company.')' : '' }}
                            </option>
                            @endforeach
                        </select>
                        <div id="contactPreview" class="contact-preview"></div>
                    </div>

                    <div class="field">
                        <label class="field-label">Invoice Number</label>
                        <input type="text" class="field-input" value="{{ $number }}" readonly
                               style="background:var(--bg-elevated);color:var(--text-300);cursor:default"/>
                    </div>

                    <div class="field">
                        <label class="field-label">Invoice Date <span class="req">*</span></label>
                        <input type="date" name="date"
                               class="field-input {{ $errors->has('date') ? 'is-error':'' }}"
                               value="{{ old('date', now()->toDateString()) }}" required/>
                        @error('date') <span class="field-error">{{ $message }}</span> @enderror
                    </div>

                    <div class="field">
                        <label class="field-label">Due Date <span class="req">*</span></label>
                        <input type="date" name="due_date"
                               class="field-input {{ $errors->has('due_date') ? 'is-error':'' }}"
                               value="{{ old('due_date', now()->addDays(30)->toDateString()) }}" required/>
                        @error('due_date') <span class="field-error">{{ $message }}</span> @enderror
                    </div>

                </div>
            </div>

            {{-- Items table --}}
            <div class="fc-section">
                <div class="fc-title">Line Items</div>
                <div style="overflow-x:auto">
                    <table class="items-table">
                        <thead>
                            <tr>
                                <th style="min-width:260px">Description</th>
                                <th style="width:90px">Qty</th>
                                <th style="width:120px">Rate (₹)</th>
                                <th style="width:80px">GST %</th>
                                <th class="right" style="width:120px">Amount</th>
                                <th style="width:40px"></th>
                            </tr>
                        </thead>
                        <tbody id="itemsBody">
                            {{-- Rows injected by JS --}}
                        </tbody>
                    </table>
                </div>
                <button type="button" class="add-row-btn" onclick="addRow()">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="width:15px;height:15px">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                    </svg>
                    Add Line Item
                </button>
            </div>

            {{-- Discount, Tax, Totals --}}
            <div class="fc-section">
                <div class="form-grid">
                    <div class="field">
                        <label class="field-label">Discount (₹)</label>
                        <input type="number" name="discount" id="discount"
                               class="field-input" min="0" step="0.01"
                               value="{{ old('discount', $prefillDiscount) }}"
                               oninput="calcTotals()" placeholder="0"/>
                    </div>
                    <div class="field">
                        <label class="field-label">GST / Tax (%)</label>
                        <input type="text" class="field-input"
                               style="background:var(--bg-elevated);color:var(--text-300);cursor:default"
                               readonly value="Per-item (set per row below)"
                               id="taxPercent"/>
                        <input type="hidden" name="tax_percent" value="{{ old('tax_percent', $prefillTax) }}"/>
                    </div>
                </div>

                <div class="totals-box" style="margin-top:16px">
                    <div class="total-row">
                        <span class="total-label">Subtotal</span>
                        <span class="total-value" id="dispSubtotal">₹0.00</span>
                    </div>
                    <div class="total-row">
                        <span class="total-label">Discount</span>
                        <span class="total-value" id="dispDiscount" style="color:var(--red)">- ₹0.00</span>
                    </div>
                    <div class="total-row">
                        <span class="total-label">GST (<span id="dispTaxPct">18</span>%)</span>
                        <span class="total-value" id="dispTax">₹0.00</span>
                    </div>
                    <div class="total-row grand">
                        <span>Total</span>
                        <span id="dispTotal" style="color:var(--accent)">₹0.00</span>
                    </div>
                </div>
            </div>

            {{-- Notes & Terms --}}
            <div class="fc-section">
                <div class="form-grid">
                    <div class="field">
                        <label class="field-label">Notes</label>
                        <textarea name="notes" class="field-input" rows="3"
                                  style="resize:vertical" placeholder="Internal notes or message to client...">{{ old('notes', $prefillNotes) }}</textarea>
                    </div>
                    <div class="field">
                        <label class="field-label">Terms & Conditions</label>
                        <textarea name="terms" class="field-input" rows="3"
                                  style="resize:vertical" placeholder="Payment terms...">{{ old('terms', $prefillTerms) }}</textarea>
                    </div>
                </div>
            </div>

            <div class="form-footer">
                <a href="{{ route('tenant.invoices.index') }}" class="btn btn-secondary">Cancel</a>
                <div style="display:flex;gap:10px">
                    <button type="submit" class="btn btn-primary">
                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="width:15px;height:15px">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                        </svg>
                        Create Invoice
                    </button>
                </div>
            </div>

        </div>
    </div>

    {{-- ── Right: Summary ──────────────────────────────────────── --}}
    <div class="summary-card">
        <div class="sc-head">Invoice Summary</div>
        <div class="sc-body">
            <div class="sc-row">
                <span style="color:var(--text-300)">Company</span>
                <span style="font-weight:600;color:var(--text-100)">{{ $tenant->name }}</span>
            </div>
            <div class="sc-row">
                <span style="color:var(--text-300)">Invoice #</span>
                <span style="font-family:var(--mono);font-size:12px;color:var(--accent)">{{ $number }}</span>
            </div>
            <div class="sc-row">
                <span style="color:var(--text-300)">Items</span>
                <span id="sumItems" style="font-weight:600">0</span>
            </div>
            <div class="sc-row">
                <span style="color:var(--text-300)">Subtotal</span>
                <span id="sumSubtotal" style="font-family:var(--mono)">₹0.00</span>
            </div>
            <div class="sc-row">
                <span style="color:var(--text-300)">GST</span>
                <span id="sumTax" style="font-family:var(--mono)">₹0.00</span>
            </div>
            <div style="padding-top:12px;border-top:1px solid var(--border-subtle);margin-top:4px">
                <div style="font-size:11px;color:var(--text-400);margin-bottom:4px">Total Amount</div>
                <div class="sc-total" id="sumTotal">₹0.00</div>
            </div>

            {{-- Due date hint --}}
            <div style="margin-top:16px;padding:12px;background:var(--amber-dim);border-radius:var(--r-sm)">
                <div style="font-size:11px;font-weight:700;color:var(--amber);margin-bottom:4px">DUE DATE</div>
                <div id="sumDueDate" style="font-size:13px;font-weight:600;color:var(--text-100)">—</div>
            </div>

            @if($quotation)
            <div style="margin-top:12px;padding:12px;background:var(--green-dim);border-radius:var(--r-sm)">
                <div style="font-size:11px;font-weight:700;color:var(--green);margin-bottom:4px">FROM QUOTATION</div>
                <div style="font-size:13px;font-weight:600;color:var(--text-100)">{{ $quotation->number }}</div>
            </div>
            @endif
        </div>
    </div>

</div>
</form>

@endsection

@include('tenant.partials.product-search-js')

@push('scripts')
<script>
// ── Data ──────────────────────────────────────────────────────────
const CONTACTS      = @json($contactsJson);
const PREFILL_ITEMS = @json($prefillItems);
window.PRODUCTS = @json($products->keyBy('id'));
window.SERVICES = @json($services->keyBy('id'));
let rowCount = 0;

// ── Contact load ──────────────────────────────────────────────────
function loadContact(id) {
    const c   = CONTACTS[id];
    const box = document.getElementById('contactPreview');
    if (!c || !id) { box.style.display = 'none'; return; }

    box.innerHTML = `
        <strong>${c.name}</strong>${c.company ? ' · ' + c.company : ''}<br/>
        ${c.phone || ''} ${c.email ? '· ' + c.email : ''}<br/>
        ${c.address || ''}
        ${c.gst ? '<br/>GST: ' + c.gst : ''}
        ${c.loyalty ? '<br/><span style="color:var(--green);font-weight:600">★ Loyalty: ' + c.loyalty + '</span>' : ''}
    `;
    box.style.display = 'block';
}

// ── Autofill row from selected product (called by shared partial) ─
window.fillRowFromProduct = function(i, p) {
    const row = document.querySelector(`[data-row="${i}"]`);
    if (!row) return;
    row.querySelector(`[name="items[${i}][description]"]`).value = p.description || p.name;
    row.querySelector(`[name="items[${i}][rate]"]`).value        = p.rate;
    row.querySelector(`[name="items[${i}][tax_percent]"]`).value = p.tax_percent;
    const pidInput = row.querySelector(`[name="items[${i}][product_id]"]`);
    const sidInput = row.querySelector(`[name="items[${i}][service_id]"]`);
    if (p._kind === 'service') {
        if (sidInput) sidInput.value = p.id;
        if (pidInput) pidInput.value = '';
    } else {
        if (pidInput) pidInput.value = p.id;
        if (sidInput) sidInput.value = '';
    }
    renderSubscriptionToggle(i, p);
    calcRow(i);
    calcTotals();
};

// Recurring/duration-bearing service → show a "Track as subscription"
// checkbox on that row so saving the invoice can auto-create a
// ServiceSubscription without a separate manual step.
function renderSubscriptionToggle(i, p){
    const box = document.getElementById('sub_track_' + i);
    if (!box) return;

    const isRecurring = p._kind === 'service' && p.billing_cycle && p.billing_cycle !== 'one_time';
    if (!isRecurring) { box.innerHTML = ''; return; }

    const cycleLabel = p.billing_cycle.charAt(0).toUpperCase() + p.billing_cycle.slice(1);
    box.innerHTML = `
        <label style="display:flex;align-items:center;gap:6px;font-size:11.5px;color:var(--text-300);margin-top:5px;cursor:pointer">
            <input type="checkbox" name="items[${i}][track_subscription]" value="1" checked style="width:13px;height:13px;cursor:pointer"/>
            Track as subscription (${cycleLabel}${p.duration_value && p.duration_unit ? ' · ' + p.duration_value + ' ' + p.duration_unit : ''})
        </label>
    `;
}

// ── Add row ───────────────────────────────────────────────────────
function addRow(desc = '', qty = 1, rate = '', taxPct = '', productId = '', serviceId = '') {
    const tbody = document.getElementById('itemsBody');
    const i     = rowCount++;
    const tr    = document.createElement('tr');
    tr.dataset.row = i;

    const amount = (parseFloat(qty)||0) * (parseFloat(rate)||0);
    const gst    = taxPct !== '' ? taxPct : 18;

    tr.innerHTML = `
        <td data-label="Description">
            <div id="ps_container_${i}"></div>
            <input type="hidden" name="items[${i}][product_id]" value="${productId}"/>
            <input type="hidden" name="items[${i}][service_id]" value="${serviceId}"/>
            <input type="text"
                   name="items[${i}][description]"
                   class="item-input"
                   placeholder="Item description"
                   value="${escHtml(desc)}"
                   required/>
            <div id="sub_track_${i}"></div>
        </td>
        <td data-label="Qty">
            <input type="number"
                   name="items[${i}][quantity]"
                   class="item-input right"
                   min="0.01" step="0.01"
                   value="${qty}"
                   oninput="calcRow(${i}); calcTotals();"
                   required/>
        </td>
        <td data-label="Rate (₹)">
            <input type="number"
                   name="items[${i}][rate]"
                   class="item-input right"
                   min="0" step="0.01"
                   placeholder="0.00"
                   value="${rate}"
                   oninput="calcRow(${i}); calcTotals();"
                   required/>
        </td>
        <td data-label="GST %">
            <input type="number"
                   name="items[${i}][tax_percent]"
                   class="item-input right"
                   min="0" max="100" step="0.1"
                   placeholder="18"
                   value="${gst}"
                   oninput="calcTotals();"/>
        </td>
        <td data-label="Amount">
            <div class="item-amount" id="rowAmt_${i}">
                ₹${fmt(amount)}
            </div>
            <input type="hidden" name="items[${i}][amount]" id="rowAmtHidden_${i}" value="${amount}"/>
        </td>
        <td style="text-align:center">
            <button type="button" class="del-row" onclick="delRow(this)">✕</button>
        </td>`;

    tbody.appendChild(tr);
    buildProductSearch(i, document.getElementById('ps_container_' + i));
    if (serviceId && window.SERVICES && window.SERVICES[serviceId]) {
        renderSubscriptionToggle(i, {...window.SERVICES[serviceId], _kind: 'service'}, false);
    }
    calcTotals();
}

// ── Delete row ────────────────────────────────────────────────────
function delRow(btn) {
    const tbody = document.getElementById('itemsBody');
    if (tbody.rows.length <= 1) return;
    btn.closest('tr').remove();
    calcTotals();
}

// ── Calc row amount ───────────────────────────────────────────────
function calcRow(i) {
    const row  = document.querySelector(`[data-row="${i}"]`);
    if (!row) return;
    const qty  = parseFloat(row.querySelector('[name$="[quantity]"]')?.value) || 0;
    const rate = parseFloat(row.querySelector('[name$="[rate]"]')?.value)     || 0;
    const amt  = qty * rate;
    const disp = document.getElementById(`rowAmt_${i}`);
    const hid  = document.getElementById(`rowAmtHidden_${i}`);
    if (disp) disp.textContent = '₹' + fmt(amt);
    if (hid)  hid.value = amt;
}

// ── Calc totals ───────────────────────────────────────────────────
function calcTotals() {
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

    const discount   = parseFloat(document.getElementById('discount')?.value) || 0;
    const afterDisc  = Math.max(0, subtotal - discount);
    const discRatio  = subtotal > 0 ? afterDisc / subtotal : 1;
    taxAmt           = taxAmt * discRatio;
    const total      = afterDisc + taxAmt;
    const avgTaxPct  = subtotal > 0 ? (taxAmt / afterDisc * 100) : 0;

    // Update display
    set('dispSubtotal', '₹' + fmt(subtotal));
    set('dispDiscount', '- ₹' + fmt(discount));
    set('dispTax',      '₹' + fmt(taxAmt));
    set('dispTaxPct',   avgTaxPct.toFixed(1));
    set('dispTotal',    '₹' + fmt(total));

    // Update sidebar
    const rows = document.querySelectorAll('#itemsBody tr').length;
    set('sumItems',    rows);
    set('sumSubtotal', '₹' + fmt(subtotal));
    set('sumTax',      '₹' + fmt(taxAmt));
    set('sumTotal',    '₹' + fmt(total));
}

// ── Helpers ───────────────────────────────────────────────────────
function fmt(n) { return parseFloat(n || 0).toLocaleString('en-IN', {minimumFractionDigits:2, maximumFractionDigits:2}); }
function set(id, val) { const el = document.getElementById(id); if (el) el.textContent = val; }
function escHtml(s) { return String(s).replace(/&/g,'&amp;').replace(/"/g,'&quot;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }

// ── Due date display ──────────────────────────────────────────────
document.querySelector('[name="due_date"]')?.addEventListener('change', function () {
    const d = new Date(this.value);
    const el = document.getElementById('sumDueDate');
    if (el && !isNaN(d)) {
        el.textContent = d.toLocaleDateString('en-IN', {day:'numeric', month:'short', year:'numeric'});
    }
});

// ── Init ──────────────────────────────────────────────────────────
(function () {
    // Load prefill items
    const items = PREFILL_ITEMS;
    if (items && items.length) {
        items.forEach(it => addRow(it.description || '', it.quantity || 1, it.rate || '', it.tax_percent ?? '', it.product_id || '', it.service_id || ''));
    } else {
        addRow();
    }
    calcTotals();

    // Init contact preview
    const sel = document.getElementById('contactSelect');
    if (sel?.value) loadContact(sel.value);

    // Init due date display
    const due = document.querySelector('[name="due_date"]')?.value;
    if (due) {
        const d = new Date(due);
        const el = document.getElementById('sumDueDate');
        if (el && !isNaN(d)) el.textContent = d.toLocaleDateString('en-IN',{day:'numeric',month:'short',year:'numeric'});
    }
})();
</script>
@endpush