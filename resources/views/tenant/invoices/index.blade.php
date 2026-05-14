@extends('layouts.app')

@section('title', 'Invoices')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/tenant/invoices/invoices.css') }}">
@endpush

@section('content')

{{-- ──────────────────────────────────────────────────────────
     Header
────────────────────────────────────────────────────────── --}}
<div class="invoice-head">

    <div>
        <div class="invoice-title">
            Invoices
        </div>

        <div class="invoice-sub">
            Manage billing, collections & payment tracking
        </div>
    </div>

    <div class="invoice-actions">

        <a href="{{ route('tenant.invoices.create') }}"
           class="btn btn-primary">
            + Create Invoice
        </a>

        <button class="btn btn-secondary">
            Export
        </button>

    </div>

</div>

{{-- ──────────────────────────────────────────────────────────
     Summary
────────────────────────────────────────────────────────── --}}
<div class="invoice-summary">

    <div class="summary-card total">
        <div class="summary-label">Total Revenue</div>
        <div class="summary-value">
            ₹{{ number_format($summary['all']['amount'] ?? 0) }}
        </div>
        <div class="summary-sub">
            {{ $summary['all']['count'] ?? 0 }} invoices
        </div>
    </div>

    <div class="summary-card paid">
        <div class="summary-label">Collected</div>
        <div class="summary-value">
            ₹{{ number_format($summary['paid']['amount'] ?? 0) }}
        </div>
        <div class="summary-sub">
            {{ $summary['paid']['count'] ?? 0 }} paid
        </div>
    </div>

    <div class="summary-card partial">
        <div class="summary-label">Partial</div>
        <div class="summary-value">
            ₹{{ number_format($summary['partial']['amount'] ?? 0) }}
        </div>
        <div class="summary-sub">
            Partial payments
        </div>
    </div>

    <div class="summary-card overdue">
        <div class="summary-label">Overdue</div>
        <div class="summary-value">
            ₹{{ number_format($summary['overdue']['amount'] ?? 0) }}
        </div>
        <div class="summary-sub">
            Pending recovery
        </div>
    </div>

    <div class="summary-card pending">
        <div class="summary-label">Outstanding</div>
        <div class="summary-value">
            ₹{{ number_format(
                ($summary['all']['amount'] ?? 0)
                -
                ($summary['paid']['amount'] ?? 0)
            ) }}
        </div>
        <div class="summary-sub">
            Remaining receivable
        </div>
    </div>

</div>

{{-- ──────────────────────────────────────────────────────────
     Filters
────────────────────────────────────────────────────────── --}}
<form method="GET">

    <div class="filter-wrap">

        <div class="filter-row">

            <div class="filter-group">

                <div class="search-box">

                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M21 21l-4.35-4.35m1.85-5.15a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>

                    <input type="text"
                           name="search"
                           value="{{ request('search') }}"
                           placeholder="Search invoice number or customer..."
                           class="filter-input">

                </div>

            </div>

            <div class="filter-group">
                <select name="status"
                        onchange="this.form.submit()"
                        class="filter-input">

                    <option value="">
                        All Status
                    </option>

                    @foreach($statuses as $key => $label)
                        <option value="{{ $key }}"
                            {{ request('status') == $key ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach

                </select>
            </div>

            <div class="filter-group">
                <input type="date"
                       name="date_from"
                       value="{{ request('date_from') }}"
                       class="filter-input">
            </div>

            <div class="filter-group">
                <input type="date"
                       name="date_to"
                       value="{{ request('date_to') }}"
                       class="filter-input">
            </div>

            <div class="filter-group" style="max-width:140px">
                <button type="submit"
                        class="btn btn-primary"
                        style="width:100%">
                    Filter
                </button>
            </div>

        </div>

    </div>

</form>

{{-- ──────────────────────────────────────────────────────────
     Table
────────────────────────────────────────────────────────── --}}
<div class="invoice-table-card">

    @if($invoices->count())

    <div style="overflow:auto">

        <table class="invoice-table">

            <thead>
            <tr>
                <th>Invoice</th>
                <th>Customer</th>
                <th>Due Date</th>
                <th>Total</th>
                <th>Paid</th>
                <th>Status</th>
                <th width="120"></th>
            </tr>
            </thead>

            <tbody>

            @foreach($invoices as $invoice)

                <tr class="invoice-row">

                    {{-- Invoice --}}
                    <td>

                        <a href="{{ route('tenant.invoices.show', $invoice->id) }}"
                           class="invoice-number">

                            {{ $invoice->number }}

                        </a>

                        <div class="invoice-date">
                            {{ $invoice->date?->format('d M Y') }}
                        </div>

                    </td>

                    {{-- Customer --}}
                    <td>

                        <div class="customer-name">
                            {{ $invoice->contact?->name ?? '—' }}
                        </div>

                        <div class="customer-company">
                            {{ $invoice->contact?->company ?? 'No company' }}
                        </div>

                    </td>

                    {{-- Due --}}
                    <td>

                        <div class="amount"
                             style="font-size:12px">

                            {{ $invoice->due_date?->format('d M Y') ?? '—' }}

                        </div>

                    </td>

                    {{-- Total --}}
                    <td>

                        <div class="amount">
                            ₹{{ number_format($invoice->total, 2) }}
                        </div>

                    </td>

                    {{-- Paid --}}
                    <td>

                        <div class="amount">
                            ₹{{ number_format($invoice->paid_amount, 2) }}
                        </div>

                        <div class="amount-paid">
                            Balance:
                            ₹{{ number_format($invoice->total - $invoice->paid_amount, 2) }}
                        </div>

                    </td>

                    {{-- Status --}}
                    <td>

                        <span class="status-badge status-{{ $invoice->status }}">
                            {{ ucfirst($invoice->status) }}
                        </span>

                    </td>

                    {{-- Actions --}}
                    <td>

                        <div class="table-actions">

                            <a href="{{ route('tenant.invoices.show', $invoice->id) }}"
                               class="icon-btn">

                                <svg fill="none"
                                     stroke="currentColor"
                                     stroke-width="2"
                                     viewBox="0 0 24 24">

                                    <path stroke-linecap="round"
                                          stroke-linejoin="round"
                                          d="M2.458 12C3.732 7.943 7.523 5 12
                                          5c4.478 0 8.268 2.943 9.542 7-1.274
                                          4.057-5.064 7-9.542 7-4.477
                                          0-8.268-2.943-9.542-7z"/>

                                    <circle cx="12"
                                            cy="12"
                                            r="3"/>

                                </svg>

                            </a>

                            <a href="{{ route('tenant.invoices.edit', $invoice->id) }}"
                               class="icon-btn">

                                <svg fill="none"
                                     stroke="currentColor"
                                     stroke-width="2"
                                     viewBox="0 0 24 24">

                                    <path stroke-linecap="round"
                                          stroke-linejoin="round"
                                          d="M11 5h2m-1-1v2m7.364
                                          2.636l-1.414-1.414M5.636
                                          18.364l-1.414-1.414M18
                                          11h2m-1-1v2M5
                                          11H3m8 8h2m-1-1v2"/>

                                </svg>

                            </a>

                        </div>

                    </td>

                </tr>

            @endforeach

            </tbody>

        </table>

    </div>

    {{-- Pagination --}}
    <div class="pagination-wrap">

        <div class="pagination-info">
            Showing
            {{ $invoices->firstItem() }}
            to
            {{ $invoices->lastItem() }}
            of
            {{ $invoices->total() }}
        </div>

        <div>
            {{ $invoices->links() }}
        </div>

    </div>

    @else

    {{-- Empty --}}
    <div class="empty-state">

        <div class="empty-icon">
            📄
        </div>

        <div class="empty-title">
            No invoices found
        </div>

        <div class="empty-sub">
            Create your first invoice to start billing customers
        </div>

        <div style="margin-top:18px">

            <a href="{{ route('tenant.invoices.create') }}"
               class="btn btn-primary">
                Create Invoice
            </a>

        </div>

    </div>

    @endif

</div>

@endsection