@extends('layouts.app')

@section('title', 'Create Invoice')

@php
    $statuses = [
        'draft' => 'Draft',
        'sent' => 'Sent',
        'partial' => 'Partial',
        'paid' => 'Paid',
        'overdue' => 'Overdue',
    ];
@endphp

@push('styles')
<link rel="stylesheet" href="{{ asset('css/tenant/invoices/create.css') }}">
@endpush

@section('content')
    <div class="invoice-page">

        <div class="invoice-top">
            <div>
                <div class="invoice-title">Create Invoice</div>
                <div class="invoice-sub">
                    Create GST compliant invoice with quotation conversion support
                </div>
            </div>

            <a href="{{ route('tenant.invoices.index') }}" class="btn btn-secondary">
                Back
            </a>
        </div>

        <form action="{{ route('tenant.invoices.store') }}" method="POST" id="invoiceForm">
            @csrf

            <div class="invoice-layout">

                {{-- LEFT --}}
                <div>

                    {{-- BASIC --}}
                    <div class="inv-card mb-4">

                        <div class="inv-head">
                            <div>
                                <div class="inv-head-title">Invoice Information</div>
                                <div class="inv-head-sub">
                                    Customer, quotation and invoice metadata
                                </div>
                            </div>
                        </div>

                        <div class="inv-body">

                            <div class="form-grid">

                                <div class="field">
                                    <label class="label">
                                        <i class="ti ti-hash"></i>
                                        Invoice Number
                                    </label>

                                    <input type="text" class="input" readonly value="{{ $nextInvoiceNumber }}">
                                </div>

                                <div class="field">
                                    <label class="label">
                                        <i class="ti ti-circle-check"></i>
                                        Status
                                    </label>

                                    <select name="status" class="select2">
                                        @foreach($statuses as $key => $value)
                                            <option value="{{ $key }}" {{ old('status') == $key ? 'selected' : '' }}>
                                                {{ trim($value) }}
                                            </option>
                                        @endforeach

                                    </select>
                                </div>

                                <div class="field full">
                                    <label class="label">
                                        <i class="ti ti-user"></i>
                                        Customer
                                        <span class="required">*</span>
                                    </label>

                                    <select name="contact_id" id="customerSelect" class="select2" required>

                                        <option value="">Search Customer</option>

                                        @foreach($contacts as $contact)
                                            <option value="{{ $contact->id }}" data-name="{{ $contact->name }}"
                                                data-company="{{ $contact->company }}" data-phone="{{ $contact->phone }}"
                                                data-email="{{ $contact->email }}">

                                                {{ trim($contact->name) }}
                                                @if($contact->company)
                                                    — {{ trim($contact->company) }}
                                                @endif
                                            </option>
                                        @endforeach
                                    </select>

                                    @error('contact_id')
                                        <div class="error-text">{{ $message }}</div>
                                    @enderror
                                </div>



                                <div class="field">
                                    <label class="label">
                                        <i class="ti ti-file-invoice"></i>
                                        Linked Quotation
                                    </label>

                                    <select name="quotation_id" class="select2" id="quotationSelect">

                                        <option value="">Select Quotation</option>

                                        @foreach($quotations as $quotation)
                                            <option value="{{ $quotation->id }}">
                                                {{ $quotation->number }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="field">
                                    <label class="label">
                                        <i class="ti ti-calendar"></i>
                                        Invoice Date
                                    </label>

                                    <input type="date" name="date" class="input"
                                        value="{{ old('date', now()->format('Y-m-d')) }}">
                                </div>

                                <div class="field">
                                    <label class="label">
                                        <i class="ti ti-calendar-due"></i>
                                        Due Date
                                    </label>

                                    <input type="date" name="due_date" class="input"
                                        value="{{ old('due_date', now()->addDays(7)->format('Y-m-d')) }}">
                                </div>

                                <div class="field">
                                    <label class="label">
                                        <i class="ti ti-cash"></i>
                                        Paid Amount
                                    </label>

                                    <input type="number" step="0.01" name="paid_amount" class="input"
                                        value="{{ old('paid_amount', 0) }}">
                                </div>

                            </div>

                        </div>
                    </div>

                    {{-- ITEMS --}}
                    <div class="inv-card mb-4">

                        <div class="inv-head">
                            <div>
                                <div class="inv-head-title">Invoice Items</div>
                                <div class="inv-head-sub">
                                    Products or services billed in invoice
                                </div>
                            </div>
                        </div>

                        <div class="inv-body">

                            <div class="items-table-wrap">

                                <table class="items-table">
                                    <thead>
                                        <tr>
                                            <th>Description</th>
                                            <th>HSN/SAC</th>
                                            <th>Qty</th>
                                            <th>Unit</th>
                                            <th>Rate</th>
                                            <th>Tax %</th>
                                            <th>Total</th>
                                            <th></th>
                                        </tr>
                                    </thead>

                                    <tbody id="itemBody">

                                        <tr class="item-row">

                                            <td>
                                                <textarea name="items[0][description]" class="item-input item-desc"
                                                    placeholder="Product or service description"></textarea>
                                            </td>

                                            <td>
                                                <input type="text" name="items[0][hsn]" class="item-input"
                                                    placeholder="9983">
                                            </td>

                                            <td>
                                                <input type="number" step="0.01" value="1" name="items[0][qty]"
                                                    class="item-input qty">
                                            </td>

                                            <td>
                                                <input type="text" name="items[0][unit]" class="item-input"
                                                    placeholder="Nos">
                                            </td>

                                            <td>
                                                <input type="number" step="0.01" value="0" name="items[0][rate]"
                                                    class="item-input rate">
                                            </td>

                                            <td>
                                                <input type="number" step="0.01" value="18" name="items[0][tax]"
                                                    class="item-input tax">
                                            </td>

                                            <td>
                                                <input type="text" readonly value="0.00"
                                                    class="item-input amount-box amount">
                                            </td>

                                            <td>
                                                <button type="button" class="remove-btn">
                                                    ×
                                                </button>
                                            </td>

                                        </tr>

                                    </tbody>
                                </table>

                            </div>

                            <button type="button" class="add-item-btn" id="addItemBtn">
                                + Add Item
                            </button>

                        </div>
                    </div>

                    {{-- NOTES --}}
                    <div class="inv-card">

                        <div class="inv-head">
                            <div>
                                <div class="inv-head-title">Terms & Notes</div>
                                <div class="inv-head-sub">
                                    Customer communication and invoice terms
                                </div>
                            </div>
                        </div>

                        <div class="inv-body">

                            <div class="form-grid">

                                <div class="field full">
                                    <label class="label">
                                        <i class="ti ti-notes"></i>
                                        Notes
                                    </label>

                                    <textarea name="notes" class="textarea"
                                        placeholder="Additional instructions or notes">{{ old('notes') }}</textarea>
                                </div>

                                <div class="field full">
                                    <label class="label">
                                        <i class="ti ti-file-description"></i>
                                        Terms & Conditions
                                    </label>

                                    <textarea name="terms" class="textarea">{{ old('terms', '1. Payment once made will not be refunded.
                        2. Subject to Surat jurisdiction only.
                        3. Goods once sold will not be taken back.') }}</textarea>
                                </div>

                            </div>

                        </div>
                    </div>

                </div>

                {{-- RIGHT --}}
                <div>

                    <div class="inv-card summary-sticky">

                        <div class="inv-head">
                            <div>
                                <div class="inv-head-title">Invoice Summary</div>
                                <div class="inv-head-sub">
                                    Auto calculated totals and GST
                                </div>
                            </div>
                        </div>

                        <div class="inv-body">

                            <div class="summary-list">

                                <div class="summary-row">
                                    <div class="summary-label">Subtotal</div>
                                    <div class="summary-value" id="subtotalText">₹0.00</div>
                                </div>

                                <div class="summary-row">
                                    <div class="summary-label">GST</div>
                                    <div class="summary-value" id="taxText">₹0.00</div>
                                </div>

                                <div class="summary-row">
                                    <div class="summary-label">Discount</div>

                                    <input type="number" name="discount" id="discountInput" class="input" value="0"
                                        step="0.01">
                                </div>

                                <div class="summary-row summary-total">
                                    <div class="summary-label">Grand Total</div>
                                    <div class="summary-value" id="grandTotalText">₹0.00</div>
                                </div>

                            </div>

                            <div class="action-stack">

                                <button type="submit" name="action" value="save" class="btn-main">
                                    Save Invoice
                                </button>

                                <button type="submit" name="action" value="send" class="btn-alt">
                                    Save & Send Invoice
                                </button>

                            </div>

                        </div>
                    </div>

                </div>

            </div>

        </form>

    </div>
    <div class="customer-report">

        {{-- NAV --}}
        <div class="report-tabs">

            <button class="report-tab active" data-tab="quotationTab">
                Quotations
            </button>

            <button class="report-tab" data-tab="dealTab">
                Deals
            </button>

            <button class="report-tab" data-tab="invoiceTab">
                Invoices
            </button>

        </div>

        {{-- QUOTATIONS --}}
        <div class="report-pane active" id="quotationTab">

            <table class="report-table">
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Status</th>
                        <th>Total</th>
                        <th>Date</th>
                    </tr>
                </thead>

                <tbody id="quotationTableBody"></tbody>
            </table>

        </div>

        {{-- DEALS --}}
        <div class="report-pane" id="dealTab">

            <table class="report-table">
                <thead>
                    <tr>
                        <th>Deal</th>
                        <th>Stage</th>
                        <th>Value</th>
                        <th>Expected Close</th>
                    </tr>
                </thead>

                <tbody id="dealTableBody"></tbody>
            </table>

        </div>

        {{-- INVOICES --}}
        <div class="report-pane" id="invoiceTab">

            <table class="report-table">
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Status</th>
                        <th>Total</th>
                        <th>Date</th>
                    </tr>
                </thead>

                <tbody id="invoiceTableBody"></tbody>
            </table>

        </div>

    </div>
@endsection

@push('scripts')
    <script>
        let rowIndex = 1;

        // $('#customerSelect').select2({
        //     width: '100%',
        //     placeholder: 'Search customer',
        // });

        function calculateInvoice() {

            let subtotal = 0;
            let totalTax = 0;

            document.querySelectorAll('.item-row').forEach(row => {

                const qty = parseFloat(row.querySelector('.qty').value || 0);
                const rate = parseFloat(row.querySelector('.rate').value || 0);
                const tax = parseFloat(row.querySelector('.tax').value || 0);

                const amount = qty * rate;
                const taxAmount = amount * tax / 100;

                subtotal += amount;
                totalTax += taxAmount;

                row.querySelector('.amount').value =
                    (amount + taxAmount).toFixed(2);

            });

            const discount = parseFloat(
                document.getElementById('discountInput').value || 0
            );

            const grandTotal = subtotal + totalTax - discount;

            document.getElementById('subtotalText').innerHTML =
                '₹' + subtotal.toFixed(2);

            document.getElementById('taxText').innerHTML =
                '₹' + totalTax.toFixed(2);

            document.getElementById('grandTotalText').innerHTML =
                '₹' + grandTotal.toFixed(2);
        }

        calculateInvoice();

        // add row
        $('#addItemBtn').on('click', function () {

            const row = `
                                <tr class="item-row">

                                    <td>
                                        <textarea name="items[${rowIndex}][description]"
                                                  class="item-input item-desc"></textarea>
                                    </td>

                                    <td>
                                        <input type="text"
                                               name="items[${rowIndex}][hsn]"
                                               class="item-input">
                                    </td>

                                    <td>
                                        <input type="number"
                                               step="0.01"
                                               value="1"
                                               name="items[${rowIndex}][qty]"
                                               class="item-input qty">
                                    </td>

                                    <td>
                                        <input type="text"
                                               name="items[${rowIndex}][unit]"
                                               class="item-input">
                                    </td>

                                    <td>
                                        <input type="number"
                                               step="0.01"
                                               value="0"
                                               name="items[${rowIndex}][rate]"
                                               class="item-input rate">
                                    </td>

                                    <td>
                                        <input type="number"
                                               step="0.01"
                                               value="18"
                                               name="items[${rowIndex}][tax]"
                                               class="item-input tax">
                                    </td>

                                    <td>
                                        <input type="text"
                                               readonly
                                               value="0.00"
                                               class="item-input amount-box amount">
                                    </td>

                                    <td>
                                        <button type="button" class="remove-btn">×</button>
                                    </td>

                                </tr>
                            `;

            $('#itemBody').append(row);

            rowIndex++;

            calculateInvoice();
        });

        // remove row
        $(document).on('click', '.remove-btn', function () {

            if ($('.item-row').length > 1) {
                $(this).closest('.item-row').remove();
                calculateInvoice();
            }
        });

        // recalculate
        $(document).on('input', '.qty,.rate,.tax,#discountInput', function () {
            calculateInvoice();
        });

        // quotation autofill
        $('#quotationSelect').on('change', async function () {

            const quotationId = $(this).val();

            if (!quotationId) return;

            const response = await fetch(`/tenant/quotations/${quotationId}/data`);

            const data = await response.json();

            $('#customerSelect').val(data.contact_id).trigger('change');

            $('[name="notes"]').val(data.notes || '');

            $('[name="terms"]').val(data.terms || '');

            $('#itemBody').html('');

            rowIndex = 0;

            data.items.forEach(item => {

                const row = `
                                    <tr class="item-row">

                                        <td>
                                            <textarea name="items[${rowIndex}][description]"
                                                      class="item-input item-desc">${item.description ?? ''}</textarea>
                                        </td>

                                        <td>
                                            <input type="text"
                                                   name="items[${rowIndex}][hsn]"
                                                   value="${item.hsn ?? ''}"
                                                   class="item-input">
                                        </td>

                                        <td>
                                            <input type="number"
                                                   step="0.01"
                                                   value="${item.qty ?? 1}"
                                                   name="items[${rowIndex}][qty]"
                                                   class="item-input qty">
                                        </td>

                                        <td>
                                            <input type="text"
                                                   value="${item.unit ?? ''}"
                                                   name="items[${rowIndex}][unit]"
                                                   class="item-input">
                                        </td>

                                        <td>
                                            <input type="number"
                                                   step="0.01"
                                                   value="${item.rate ?? 0}"
                                                   name="items[${rowIndex}][rate]"
                                                   class="item-input rate">
                                        </td>

                                        <td>
                                            <input type="number"
                                                   step="0.01"
                                                   value="${item.tax ?? 18}"
                                                   name="items[${rowIndex}][tax]"
                                                   class="item-input tax">
                                        </td>

                                        <td>
                                            <input type="text"
                                                   readonly
                                                   value="0.00"
                                                   class="item-input amount-box amount">
                                        </td>

                                        <td>
                                            <button type="button" class="remove-btn">×</button>
                                        </td>

                                    </tr>
                                `;

                $('#itemBody').append(row);

                rowIndex++;
            });

            calculateInvoice();
        });

        $(document).on('click', '.report-tab', function () {

            $('.report-tab').removeClass('active');
            $(this).addClass('active');

            $('.report-pane').removeClass('active');

            $('#' + $(this).data('tab')).addClass('active');

        });

        // customer report load
        $('#customerSelect').on('change', async function () {

            const selected = $(this).find(':selected');
            let quotationHtml = '';
            let dealHtml = '';
            let invoiceHtml = '';

            $('#customerOverview').show();

            $('#overviewName').text(selected.data('name') || '-');
            $('#overviewCompany').text(selected.data('company') || '-');
            $('#overviewPhone').text(selected.data('phone') || '-');
            $('#overviewEmail').text(selected.data('email') || '-');

            const customerId = $(this).val();

            if (!customerId) return;

            const response = await fetch(`/contacts/${customerId}/report`);

            const data = await response.json();

            // let html = '';

            // ── Quotations ─────────────────────────────
            (data.quotations || []).forEach(item => {

                quotationHtml += `
                                <tr>
                                    <td>
                                        <span class="badge badge-warning">
                                            Quotation
                                        </span>
                                    </td>

                                    <td>${item.number ?? '-'}</td>

                                    <td>${item.status ?? '-'}</td>

                                    <td>₹${parseFloat(item.total || 0).toFixed(2)}</td>

                                    <td>
                                        ${item.date
                        ? new Date(item.date).toLocaleDateString()
                        : '-'}
                                    </td>
                                </tr>
                            `;
            });

            // ── Deals ─────────────────────────────────
            (data.deals || []).forEach(item => {

                dealHtml += `
                                <tr>
                                    <td>
                                        <span class="badge badge-info">
                                            Deal
                                        </span>
                                    </td>

                                    <td>${item.title ?? '-'}</td>

                                    <td>${item.stage ?? '-'}</td>

                                    <td>₹${parseFloat(item.value || 0).toFixed(2)}</td>

                                    <td>
                                        ${item.created_at
                        ? new Date(item.created_at).toLocaleDateString()
                        : '-'}
                                    </td>
                                </tr>
                            `;
            });

            // ── Invoices ──────────────────────────────
            (data.invoices || []).forEach(item => {

                invoiceHtml += `
                                <tr>
                                    <td>
                                        <span class="badge badge-success">
                                            Invoice
                                        </span>
                                    </td>

                                    <td>${item.number ?? '-'}</td>

                                    <td>${item.status ?? '-'}</td>

                                    <td>₹${parseFloat(item.total || 0).toFixed(2)}</td>

                                    <td>
                                        ${item.date
                        ? new Date(item.date).toLocaleDateString()
                        : '-'}
                                    </td>
                                </tr>
                            `;
            });

            // ── Empty State ───────────────────────────
            if (quotationHtml === '' && dealHtml === '' && invoiceHtml === '') {

                html = `
                                <tr>
                                    <td colspan="5" style="text-align:center;padding:20px">
                                        No records found
                                    </td>
                                </tr>
                            `;
            }

            $('#quotationTableBody').html(quotationHtml);
            $('#dealTableBody').html(dealHtml);
            $('#invoiceTableBody').html(invoiceHtml);

            // $('#customerActivityBody').html(quotationHtml + dealHtml + invoiceHtml);

        });
    </script>
@endpush