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

            <button class="btn btn-success">
                Send Invoice
            </button>

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

                                <td>
                                    <div class="item-title">
                                        {{ $item['description'] ?? '-' }}
                                    </div>

                                    <div class="item-sub">
                                        HSN: {{ $item['hsn'] ?? '-' }}
                                    </div>
                                </td>

                                <td>{{ $item['qty'] }}</td>

                                <td>
                                    ₹{{ number_format($item['rate'], 2) }}
                                </td>

                                <td>
                                    {{ $item['tax'] }}%
                                </td>

                                <td>
                                    {{-- ₹{{ number_format($item['amount'], 2) }} --}}
                                </td>

                            </tr>

                            @endforeach

                        </tbody>

                    </table>

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