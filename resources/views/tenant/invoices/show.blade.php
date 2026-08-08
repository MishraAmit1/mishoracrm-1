@extends('layouts.app')

@section('title', $invoice->number)
@push('styles')
<link rel="stylesheet" href="{{ asset('css/tenant/invoices/show.css') }}">
@endpush
@section('content')

<div class="invoice-show-wrap">

    {{-- HEADER --}}
    <div class="invoice-head">

        <div>
            <div class="invoice-number">
                {{ $invoice->number }}
            </div>

            <div class="invoice-meta-line">
                Created {{ $invoice->date->format('d M Y') }}
            </div>
        </div>

        <div class="invoice-actions">

            <a href="{{ route('tenant.invoices.edit', $invoice->id) }}"
               class="btn btn-secondary">
                Edit
            </a>

            <a href="{{ route('tenant.invoices.pdf', $invoice->id) }}"
               class="btn btn-primary">
                Download PDF
            </a>

            @php
                $sendToEmail = $invoice->contact?->primaryEmail();
                $sendCcCount = $invoice->contact ? count($invoice->contact->ccEmails()) : 0;
            @endphp
            @if($sendToEmail)
            <form method="POST" action="{{ route('tenant.invoices.send', $invoice->id) }}"
                  onsubmit="return confirm('Send this invoice to {{ addslashes($sendToEmail) }}{{ $sendCcCount ? ' (cc: '.$sendCcCount.')' : '' }}?')">
                @csrf
                <button type="submit" class="btn btn-success">
                    Send Invoice{{ $sendCcCount ? ' (cc: '.$sendCcCount.')' : '' }}
                </button>
            </form>
            @else
            <button class="btn btn-success" disabled title="Contact has no email address">
                Send Invoice
            </button>
            @endif

        </div>

    </div>

    {{-- STATUS CARDS --}}
    <div class="invoice-stats">

        <div class="stat-card">
            <div class="stat-label">Total</div>
            <div class="stat-value">
                ₹{{ number_format($invoice->total, 2) }}
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-label">Paid</div>
            <div class="stat-value text-green">
                ₹{{ number_format($invoice->paid_amount, 2) }}
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-label">Balance</div>
            <div class="stat-value text-red">
                ₹{{ number_format($invoice->total - $invoice->paid_amount, 2) }}
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-label">Status</div>

            <div class="status-badge status-{{ $invoice->status }}">
                {{ ucfirst($invoice->status) }}
            </div>
        </div>

    </div>

    {{-- GRID --}}
    <div class="invoice-grid">

        {{-- LEFT --}}
        <div>

            {{-- CUSTOMER --}}
            <div class="card mb-4">

                <div class="card-head">
                    Customer Information
                </div>

                <div class="card-body">

                    <div class="customer-name">
                        {{ $invoice->contact?->name }}
                    </div>

                    <div class="meta-line">
                        {{ $invoice->contact?->company }}
                    </div>

                    <div class="meta-line">
                        {{ $invoice->contact?->phone }}
                    </div>

                    <div class="meta-line">
                        {{ $invoice->contact?->email }}
                    </div>

                    <div class="meta-line">
                        GST: {{ $invoice->contact?->gst_number ?: '-' }}
                    </div>

                </div>

            </div>

            {{-- ITEMS --}}
            <div class="card">

                <div class="card-head">
                    Invoice Items
                </div>

                <div class="card-body p-0">

                    <table class="invoice-table">

                        <thead>
                            <tr>
                                <th>Description</th>
                                <th>Qty</th>
                                <th>Rate</th>
                                <th>Tax</th>
                                <th>Total</th>
                            </tr>
                        </thead>

                        <tbody>

                            @foreach($invoice->items as $item)

                            <tr>

                                <td data-label="Description">
                                    <div class="item-title">
                                        {{ $item['description'] ?? '-' }}
                                    </div>

                                    <div class="item-sub">
                                        HSN: {{ $item['hsn'] ?? '-' }}
                                    </div>
                                </td>

                                <td data-label="Qty">{{ $item['quantity'] ?? '-' }}</td>

                                <td data-label="Rate">
                                    ₹{{ number_format($item['rate'], 2) }}
                                </td>

                                <td data-label="Tax">
                                    {{ $item['tax_percent'] ?? $invoice->tax_percent }}%
                                </td>

                                <td data-label="Total">
                                    ₹{{ number_format($item['amount'] ?? ($item['quantity'] * $item['rate']), 2) }}
                                </td>

                            </tr>

                            @endforeach

                        </tbody>

                    </table>

                </div>

                {{-- PAYMENTS — part of the same items card, its own line-item table --}}
                <div class="card-subhead">
                    Payments
                </div>

                <div class="card-body p-0">

                    @if($invoice->payments->isEmpty())

                    <div class="empty-state">
                        <div class="empty-sub">No payments recorded yet.</div>
                    </div>

                    @else

                    <table class="invoice-table">

                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Method</th>
                                <th>Note</th>
                                <th>Recorded By</th>
                                <th class="right">Amount</th>
                            </tr>
                        </thead>

                        <tbody>

                            @foreach($invoice->payments as $payment)

                            <tr>
                                <td data-label="Date">{{ $payment->paid_at->format('d M Y') }}</td>
                                <td data-label="Method">{{ \App\Models\Invoice::paymentMethods()[$payment->method] ?? ucfirst($payment->method) }}</td>
                                <td data-label="Note">{{ $payment->note ?: '-' }}</td>
                                <td data-label="Recorded By">{{ $payment->recordedBy?->name ?? '-' }}</td>
                                <td data-label="Amount" class="right">₹{{ number_format($payment->amount, 2) }}</td>
                            </tr>

                            @endforeach

                        </tbody>

                        <tfoot>
                            <tr>
                                <td colspan="4" class="right"><strong>Total Paid</strong></td>
                                <td class="right"><strong>₹{{ number_format($invoice->paid_amount, 2) }}</strong></td>
                            </tr>
                        </tfoot>

                    </table>

                    @endif

                </div>

            </div>

        </div>

        {{-- RIGHT --}}
        <div>

            {{-- SUMMARY --}}
            <div class="card sticky-top">

                <div class="card-head">
                    Payment Summary
                </div>

                <div class="card-body">

                    <div class="summary-row">
                        <span>Subtotal</span>
                        <strong>
                            ₹{{ number_format($invoice->subtotal, 2) }}
                        </strong>
                    </div>

                    <div class="summary-row">
                        <span>Tax</span>
                        <strong>
                            ₹{{ number_format($invoice->tax_amount, 2) }}
                        </strong>
                    </div>

                    <div class="summary-row">
                        <span>Discount</span>
                        <strong>
                            ₹{{ number_format($invoice->discount, 2) }}
                        </strong>
                    </div>

                    <div class="summary-row total">
                        <span>Total</span>
                        <strong>
                            ₹{{ number_format($invoice->total, 2) }}
                        </strong>
                    </div>

                </div>

            </div>

            {{-- RECORD PAYMENT --}}
            @if($invoice->due_amount > 0)
            <div class="card mt-4">

                <div class="card-head">
                    Record Payment
                </div>

                <div class="card-body">

                    <form method="POST" action="{{ route('tenant.invoices.record_payment', $invoice->id) }}" id="payForm">
                        @csrf

                        <div style="overflow-x:auto">
                            <table class="pay-table">
                                <thead>
                                    <tr>
                                        <th>Amount (₹)</th>
                                        <th>Method</th>
                                        <th>Paid On</th>
                                        <th>Note</th>
                                        <th style="width:36px"></th>
                                    </tr>
                                </thead>
                                <tbody id="payRowsBody">
                                    {{-- rows injected by JS --}}
                                </tbody>
                            </table>
                        </div>

                        <button type="button" class="add-row-btn" onclick="addPayRow()">
                            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="width:14px;height:14px">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                            </svg>
                            Add Payment
                        </button>

                        <div class="totals-box" style="margin-top:14px">
                            <div class="total-row">
                                <span class="total-label">Due Amount</span>
                                <span class="total-value">₹{{ number_format($invoice->due_amount, 2) }}</span>
                            </div>
                            <div class="total-row grand">
                                <span>Total Being Recorded</span>
                                <span id="payRunningTotal">₹0.00</span>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary" style="width:100%;margin-top:14px;">
                            Record Payment(s)
                        </button>

                    </form>

                </div>

            </div>
            @endif

            {{-- NOTES --}}
            <div class="card mt-4">

                <div class="card-head">
                    Notes
                </div>

                <div class="card-body">

                    {{ $invoice->notes ?: 'No notes added.' }}

                </div>

            </div>

        </div>

    </div>

</div>

@endsection

@if($invoice->due_amount > 0)
@push('scripts')
<script>
const PAYMENT_METHODS = @json(\App\Models\Invoice::paymentMethods());
const DUE_AMOUNT       = {{ $invoice->due_amount }};
let payRowCount = 0;

function payMethodOptions() {
    return Object.entries(PAYMENT_METHODS)
        .map(([key, label]) => `<option value="${key}">${label}</option>`)
        .join('');
}

function addPayRow(amount = '') {
    const tbody = document.getElementById('payRowsBody');
    const i     = payRowCount++;
    const tr    = document.createElement('tr');
    tr.dataset.row = i;

    tr.innerHTML = `
        <td data-label="Amount">
            <input type="number" name="payments[${i}][amount]" class="pay-input right"
                   min="0.01" step="0.01" max="${DUE_AMOUNT}"
                   value="${amount}" oninput="calcPayTotal()" required/>
        </td>
        <td data-label="Method">
            <select name="payments[${i}][method]" class="pay-input" required>
                ${payMethodOptions()}
            </select>
        </td>
        <td data-label="Paid On">
            <input type="date" name="payments[${i}][paid_at]" class="pay-input"
                   value="${new Date().toISOString().slice(0,10)}" required/>
        </td>
        <td data-label="Note">
            <input type="text" name="payments[${i}][note]" class="pay-input" maxlength="255" placeholder="Optional"/>
        </td>
        <td style="text-align:center">
            <button type="button" class="del-row" onclick="delPayRow(this)">✕</button>
        </td>`;

    tbody.appendChild(tr);
    calcPayTotal();
}

function delPayRow(btn) {
    const tbody = document.getElementById('payRowsBody');
    if (tbody.rows.length <= 1) return;
    btn.closest('tr').remove();
    calcPayTotal();
}

function calcPayTotal() {
    let total = 0;
    document.querySelectorAll('#payRowsBody [name$="[amount]"]').forEach(input => {
        total += parseFloat(input.value) || 0;
    });
    const el = document.getElementById('payRunningTotal');
    if (el) {
        el.textContent = '₹' + total.toLocaleString('en-IN', {minimumFractionDigits:2, maximumFractionDigits:2});
        el.style.color = total > DUE_AMOUNT + 0.01 ? 'var(--red)' : 'var(--accent)';
    }
}

(function () {
    addPayRow(DUE_AMOUNT);
})();
</script>
@endpush
@endif