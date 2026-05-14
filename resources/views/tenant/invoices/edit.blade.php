@extends('layouts.app')

@section('title', 'Edit Invoice')

@php
    $statuses = [
        'draft'   => 'Draft',
        'sent'    => 'Sent',
        'partial' => 'Partial',
        'paid'    => 'Paid',
        'overdue' => 'Overdue',
    ];
@endphp

@push('styles')
<link rel="stylesheet" href="{{ asset('css/tenant/invoices/edit.css') }}">
@endpush

@section('content')

<div class="invoice-wrap">

    {{-- HEADER --}}
    <div class="page-top">

        <div>
            <div class="page-title">
                Edit Invoice
            </div>

            <div class="page-sub">
                Update invoice details, customer information and invoice items
            </div>
        </div>

        <div style="display:flex;gap:10px">

            <a href="{{ route('tenant.invoices.show', $invoice->id) }}"
               class="btn btn-secondary">
                View Invoice
            </a>

            <a href="{{ route('tenant.invoices.index') }}"
               class="btn btn-secondary">
                Back
            </a>

        </div>

    </div>

    <form method="POST"
          action="{{ route('tenant.invoices.update', $invoice->id) }}"
          id="invoiceForm">

        @csrf
        @method('PUT')

        <div class="invoice-grid">

            {{-- LEFT SIDE --}}
            <div>

                {{-- BASIC INFORMATION --}}
                <div class="inv-card mb-4">

                    <div class="inv-card-head">

                        <div>
                            <div class="inv-card-title">
                                Invoice Information
                            </div>

                            <div class="inv-card-sub">
                                Basic invoice details and customer selection
                            </div>
                        </div>

                    </div>

                    <div class="inv-card-body">

                        <div class="form-grid">

                            {{-- NUMBER --}}
                            <div class="field">

                                <label class="label">
                                    <i class="ti ti-hash"></i>
                                    Invoice Number
                                </label>

                                <input type="text"
                                       class="input"
                                       value="{{ $invoice->number }}"
                                       readonly>

                            </div>

                            {{-- STATUS --}}
                            <div class="field">

                                <label class="label">
                                    <i class="ti ti-check"></i>
                                    Status
                                </label>

                                <select name="status"
                                        class="select">

                                    @foreach($statuses as $key => $label)

                                        <option value="{{ $key }}"
                                            {{ $invoice->status == $key ? 'selected' : '' }}>

                                            {{ $label }}

                                        </option>

                                    @endforeach

                                </select>

                            </div>

                            {{-- CUSTOMER --}}
                            {{-- <div class="field">

                                <label class="label">
                                    <i class="ti ti-user"></i>
                                    Customer
                                    <span class="req">*</span>
                                </label>

                                <select name="contact_id"
                                        id="customerSelect"
                                        class="select select2"
                                        required>

                                    <option value="">
                                        Search Customer
                                    </option>

                                    @foreach($contacts as $contact)

                                        <option value="{{ $contact->id }}"
                                                data-name="{{ $contact->name }}"
                                                data-company="{{ $contact->company }}"
                                                data-phone="{{ $contact->phone }}"
                                                data-email="{{ $contact->email }}"
                                                {{ $invoice->contact_id == $contact->id ? 'selected' : '' }}>

                                            {{ $contact->name }}
                                            @if($contact->company)
                                                — {{ $contact->company }}
                                            @endif

                                        </option>

                                    @endforeach

                                </select>

                            </div> --}}

                            {{-- QUOTATION --}}
                            {{-- <div class="field">

                                <label class="label">
                                    <i class="ti ti-file-text"></i>
                                    Linked Quotation
                                </label>

                                <select name="quotation_id"
                                        id="quotationSelect"
                                        class="select select2">

                                    <option value="">
                                        None
                                    </option>

                                    @foreach($quotations as $quotation)

                                        <option value="{{ $quotation->id }}"
                                            {{ $invoice->quotation_id == $quotation->id ? 'selected' : '' }}>

                                            {{ $quotation->number }}

                                        </option>

                                    @endforeach

                                </select>

                            </div> --}}

                            {{-- DATE --}}
                            <div class="field">

                                <label class="label">
                                    <i class="ti ti-calendar"></i>
                                    Invoice Date
                                </label>

                                <input type="date"
                                       name="date"
                                       class="input"
                                       value="{{ old('date', optional($invoice->date)->format('Y-m-d')) }}">

                            </div>

                            {{-- DUE DATE --}}
                            <div class="field">

                                <label class="label">
                                    <i class="ti ti-calendar-due"></i>
                                    Due Date
                                </label>

                                <input type="date"
                                       name="due_date"
                                       class="input"
                                       value="{{ old('due_date', optional($invoice->due_date)->format('Y-m-d')) }}">

                            </div>

                        </div>

                    </div>

                </div>

                {{-- CUSTOMER OVERVIEW --}}
                <div class="inv-card mb-4"
                     id="customerOverview">

                    <div class="inv-card-head">

                        <div>
                            <div class="inv-card-title">
                                Customer Overview
                            </div>

                            <div class="inv-card-sub">
                                Customer details and recent activity
                            </div>
                        </div>

                    </div>

                    <div class="inv-card-body">

                        <div class="customer-grid">

                            <div class="customer-box">
                                <div class="customer-label">Customer</div>
                                <div class="customer-value"
                                     id="overviewName">
                                    —
                                </div>
                            </div>

                            <div class="customer-box">
                                <div class="customer-label">Company</div>
                                <div class="customer-value"
                                     id="overviewCompany">
                                    —
                                </div>
                            </div>

                            <div class="customer-box">
                                <div class="customer-label">Phone</div>
                                <div class="customer-value"
                                     id="overviewPhone">
                                    —
                                </div>
                            </div>

                            <div class="customer-box">
                                <div class="customer-label">Email</div>
                                <div class="customer-value"
                                     id="overviewEmail">
                                    —
                                </div>
                            </div>

                        </div>

                        {{-- TABS --}}
                        <div class="activity-tabs">

                            <button type="button"
                                    class="activity-tab active"
                                    data-target="quotation">
                                Quotations
                            </button>

                            <button type="button"
                                    class="activity-tab"
                                    data-target="deal">
                                Deals
                            </button>

                            <button type="button"
                                    class="activity-tab"
                                    data-target="invoice">
                                Invoices
                            </button>

                        </div>

                        {{-- TABLE --}}
                        <div class="activity-table-wrap">

                            <table class="activity-table">

                                <thead>

                                    <tr>
                                        <th>Reference</th>
                                        <th>Status</th>
                                        <th>Amount</th>
                                        <th>Date</th>
                                    </tr>

                                </thead>

                                <tbody id="customerActivityBody">

                                    <tr>
                                        <td colspan="4"
                                            style="text-align:center">
                                            No records
                                        </td>
                                    </tr>

                                </tbody>

                            </table>

                        </div>

                    </div>

                </div>

                {{-- ITEMS --}}
                <div class="inv-card">

                    <div class="inv-card-head">

                        <div>
                            <div class="inv-card-title">
                                Invoice Items
                            </div>

                            <div class="inv-card-sub">
                                Products or services included in invoice
                            </div>
                        </div>

                    </div>

                    <div class="inv-card-body">

                        <div class="items-wrap">

                            <table class="items-table">

                                <thead>

                                    <tr>
                                        <th width="28%">Description</th>
                                        <th width="10%">HSN</th>
                                        <th width="10%">Qty</th>
                                        <th width="10%">Unit</th>
                                        <th width="12%">Rate</th>
                                        <th width="10%">Tax</th>
                                        <th width="12%">Amount</th>
                                        <th width="5%"></th>
                                    </tr>

                                </thead>

                                <tbody id="itemBody">

                                    @foreach($invoice->items as $index => $item)

                                    <tr class="item-row">

                                        <td>
                                            <textarea
                                                name="items[{{ $index }}][description]"
                                                class="item-input item-desc"
                                                placeholder="Description">{{ $item['description'] ?? '' }}</textarea>
                                        </td>

                                        <td>
                                            <input type="text"
                                                   name="items[{{ $index }}][hsn]"
                                                   class="item-input"
                                                   value="{{ $item['hsn'] ?? '' }}">
                                        </td>

                                        <td>
                                            <input type="number"
                                                   step="0.01"
                                                   name="items[{{ $index }}][qty]"
                                                   class="item-input qty"
                                                   value="{{ $item['qty'] ?? 1 }}">
                                        </td>

                                        <td>
                                            <input type="text"
                                                   name="items[{{ $index }}][unit]"
                                                   class="item-input"
                                                   value="{{ $item['unit'] ?? '' }}">
                                        </td>

                                        <td>
                                            <input type="number"
                                                   step="0.01"
                                                   name="items[{{ $index }}][rate]"
                                                   class="item-input rate"
                                                   value="{{ $item['rate'] ?? 0 }}">
                                        </td>

                                        <td>
                                            <input type="number"
                                                   step="0.01"
                                                   name="items[{{ $index }}][tax]"
                                                   class="item-input tax"
                                                   value="{{ $item['tax'] ?? 18 }}">
                                        </td>

                                        <td>
                                            <input type="text"
                                                   readonly
                                                   class="item-input amount"
                                                   value="{{ $item['amount'] ?? 0 }}">
                                        </td>

                                        <td>

                                            <button type="button"
                                                    class="remove-btn">
                                                ×
                                            </button>

                                        </td>

                                    </tr>

                                    @endforeach

                                </tbody>

                            </table>

                        </div>

                        <button type="button"
                                class="add-row"
                                id="addItemBtn">

                            <i class="ti ti-plus"></i>
                            Add Item

                        </button>

                    </div>

                </div>

                {{-- NOTES --}}
                <div class="inv-card mt-4">

                    <div class="inv-card-head">

                        <div>
                            <div class="inv-card-title">
                                Notes & Terms
                            </div>
                        </div>

                    </div>

                    <div class="inv-card-body">

                        <div class="form-grid">

                            <div class="field full">

                                <label class="label">
                                    Customer Notes
                                </label>

                                <textarea name="notes"
                                          class="textarea">{{ old('notes', $invoice->notes) }}</textarea>

                            </div>

                            <div class="field full">

                                <label class="label">
                                    Terms & Conditions
                                </label>

                                <textarea name="terms"
                                          class="textarea">{{ old('terms', $invoice->terms) }}</textarea>

                            </div>

                        </div>

                    </div>

                </div>

            </div>

            {{-- RIGHT SIDE --}}
            <div>

                <div class="inv-card summary-card">

                    <div class="inv-card-head">

                        <div>
                            <div class="inv-card-title">
                                Invoice Summary
                            </div>

                            <div class="inv-card-sub">
                                Automatically calculated totals
                            </div>
                        </div>

                    </div>

                    <div class="inv-card-body">

                        <div class="summary-list">

                            <div class="summary-row">

                                <div class="summary-label">
                                    Subtotal
                                </div>

                                <div class="summary-value"
                                     id="subtotalText">
                                    ₹0.00
                                </div>

                            </div>

                            <div class="summary-row">

                                <div class="summary-label">
                                    Tax
                                </div>

                                <div class="summary-value"
                                     id="taxText">
                                    ₹0.00
                                </div>

                            </div>

                            <div class="summary-row">

                                <div class="summary-label">
                                    Discount
                                </div>

                                <input type="number"
                                       step="0.01"
                                       name="discount"
                                       id="discountInput"
                                       class="input"
                                       value="{{ old('discount', $invoice->discount) }}">

                            </div>

                            <div class="summary-row summary-total">

                                <div class="summary-label">
                                    Grand Total
                                </div>

                                <div class="summary-value"
                                     id="grandTotalText">
                                    ₹0.00
                                </div>

                            </div>

                        </div>

                        <div class="action-stack">

                            <button type="submit"
                                    class="btn-submit">

                                Update Invoice

                            </button>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </form>

</div>

@endsection

@push('scripts')

<script>

let rowIndex = {{ count($invoice->items ?? []) }};

function calculateInvoice(){

    let subtotal = 0;
    let totalTax = 0;

    document.querySelectorAll('.item-row').forEach(row => {

        const qty  = parseFloat(row.querySelector('.qty').value || 0);
        const rate = parseFloat(row.querySelector('.rate').value || 0);
        const tax  = parseFloat(row.querySelector('.tax').value || 0);

        const amount    = qty * rate;
        const taxAmount = (amount * tax) / 100;

        subtotal += amount;
        totalTax += taxAmount;

        row.querySelector('.amount').value =
            (amount + taxAmount).toFixed(2);

    });

    const discount = parseFloat(
        document.getElementById('discountInput').value || 0
    );

    const grandTotal = subtotal + totalTax - discount;

    $('#subtotalText').html('₹' + subtotal.toFixed(2));
    $('#taxText').html('₹' + totalTax.toFixed(2));
    $('#grandTotalText').html('₹' + grandTotal.toFixed(2));

}

calculateInvoice();

document.addEventListener('input', function(e){

    if(
        e.target.classList.contains('qty') ||
        e.target.classList.contains('rate') ||
        e.target.classList.contains('tax')
    ){
        calculateInvoice();
    }

});

$('#discountInput').on('input', calculateInvoice);

$('#addItemBtn').on('click', function(){

    $('#itemBody').append(`
        <tr class="item-row">

            <td>
                <textarea
                    name="items[${rowIndex}][description]"
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
                       value="0"
                       class="item-input amount">
            </td>

            <td>
                <button type="button"
                        class="remove-btn">
                    ×
                </button>
            </td>

        </tr>
    `);

    rowIndex++;

});

$(document).on('click', '.remove-btn', function(){

    if($('.item-row').length > 1){

        $(this).closest('.item-row').remove();

        calculateInvoice();

    }

});

</script>

@endpush