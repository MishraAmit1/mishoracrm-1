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

            @if($invoice->contact?->email)
            <form method="POST" action="{{ route('tenant.invoices.send', $invoice->id) }}"
                  onsubmit="return confirm('Send this invoice to {{ $invoice->contact->email }}?')">
                @csrf
                <button type="submit" class="btn btn-success">
                    Send Invoice
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

            </div>

            {{-- PAYMENT HISTORY --}}
            <div class="card mt-4">

                <div class="card-head">
                    Payment History
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
                                <th>Amount</th>
                                <th>Note</th>
                                <th>Recorded By</th>
                            </tr>
                        </thead>

                        <tbody>

                            @foreach($invoice->payments as $payment)

                            <tr>
                                <td data-label="Date">{{ $payment->paid_at->format('d M Y') }}</td>
                                <td data-label="Method">{{ \App\Models\Invoice::paymentMethods()[$payment->method] ?? ucfirst($payment->method) }}</td>
                                <td data-label="Amount">₹{{ number_format($payment->amount, 2) }}</td>
                                <td data-label="Note">{{ $payment->note ?: '-' }}</td>
                                <td data-label="Recorded By">{{ $payment->recordedBy?->name ?? '-' }}</td>
                            </tr>

                            @endforeach

                        </tbody>

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

                    <form method="POST" action="{{ route('tenant.invoices.record_payment', $invoice->id) }}" class="pay-form">
                        @csrf

                        <div class="field">
                            <label class="field-label">Amount</label>
                            <input type="number" step="0.01" min="0.01" max="{{ $invoice->due_amount }}"
                                   name="amount" value="{{ $invoice->due_amount }}" class="field-input" required>
                        </div>

                        <div class="field">
                            <label class="field-label">Method</label>
                            <select name="method" class="field-input field-select" required>
                                @foreach(\App\Models\Invoice::paymentMethods() as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="field">
                            <label class="field-label">Paid On</label>
                            <input type="date" name="paid_at" value="{{ now()->format('Y-m-d') }}" class="field-input" required>
                        </div>

                        <div class="field">
                            <label class="field-label">Note</label>
                            <input type="text" name="note" maxlength="255" class="field-input" placeholder="Optional">
                        </div>

                        <button type="submit" class="btn btn-primary" style="width:100%;margin-top:4px;">
                            Record Payment
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