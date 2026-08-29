@extends('layouts.app')
@section('title', 'Vendor Bills')

@push('styles')
<style>
.filter-bar { display:flex; align-items:center; gap:10px; flex-wrap:wrap; margin-bottom:20px; }
.filter-input { padding:8px 12px; height:36px; background:var(--bg-input); border:1.5px solid var(--border-default); border-radius:var(--r-sm); color:var(--text-100); font-family:var(--font); font-size:13px; outline:none; }
.status-tabs { display:flex; gap:4px; flex-wrap:wrap; margin-bottom:16px; }
.status-tab { padding:6px 14px; border-radius:20px; font-size:12.5px; font-weight:600; text-decoration:none; border:1.5px solid var(--border-default); color:var(--text-300); background:none; display:flex; align-items:center; gap:6px; }
.status-tab:hover { border-color:var(--border-strong); color:var(--text-100); }
.status-tab.active { background:var(--accent-dim); border-color:var(--accent); color:var(--accent); }
.tab-count { font-size:11px; font-family:var(--mono); background:var(--bg-elevated); padding:0 5px; border-radius:10px; color:var(--text-300); }
.status-tab.active .tab-count { background:var(--accent); color:#fff; }
.ap-stat { display:inline-flex; align-items:center; gap:8px; padding:9px 14px; margin-bottom:16px; background:var(--bg-surface); border:1px solid var(--border-default); border-radius:var(--r-sm); }
.pagination-wrap { display:flex; align-items:center; justify-content:space-between; padding:14px 20px; border-top:1px solid var(--border-subtle); font-size:13px; color:var(--text-300); }
.page-link { padding:5px 10px; border-radius:var(--r-sm); border:1px solid var(--border-default); color:var(--text-200); text-decoration:none; font-size:13px; }
.page-link.active { background:var(--accent); border-color:var(--accent); color:#fff; }
.page-link.disabled { opacity:0.4; pointer-events:none; }
.empty-state { padding:60px 20px; text-align:center; }
</style>
@endpush

@section('content')
@php $curStatus = request('status', ''); @endphp

<div class="page-head">
    <div>
        <div class="page-title">Vendor Bills</div>
        <div class="page-sub">Accounts payable — {{ $counts['all'] }} total</div>
    </div>
    <div class="page-actions">
        @can('create', \App\Models\VendorBill::class)
        <a href="{{ route('tenant.vendor-bills.create') }}" class="btn btn-primary">+ New Vendor Bill</a>
        @endcan
    </div>
</div>

@foreach(['success','error'] as $t)
@if(session($t))
<div style="padding:11px 15px;background:{{ $t==='success'?'var(--green-dim)':'var(--red-dim)' }};border:1px solid {{ $t==='success'?'var(--green)':'var(--red)' }};border-radius:8px;margin-bottom:14px;font-size:13px;color:{{ $t==='success'?'var(--green)':'var(--red)' }};font-weight:500">
    {{ session($t) }}
</div>
@endif
@endforeach

<div class="ap-stat">
    <span style="font-size:11px;font-weight:700;color:var(--text-400);text-transform:uppercase;letter-spacing:.4px">Total Outstanding (AP)</span>
    <span style="font-size:15px;font-weight:700;color:var(--red);font-family:var(--mono)">₹{{ number_format($outstanding, 2) }}</span>
</div>

<div class="status-tabs">
    <a href="{{ route('tenant.vendor-bills.index') }}" class="status-tab {{ $curStatus === '' ? 'active' : '' }}">All <span class="tab-count">{{ $counts['all'] }}</span></a>
    @foreach($statuses as $key => $label)
    <a href="{{ route('tenant.vendor-bills.index', ['status' => $key]) }}" class="status-tab {{ $curStatus === $key ? 'active' : '' }}">{{ $label }} <span class="tab-count">{{ $counts[$key] ?? 0 }}</span></a>
    @endforeach
    <a href="{{ route('tenant.vendor-bills.index', ['status' => 'overdue']) }}" class="status-tab {{ $curStatus === 'overdue' ? 'active' : '' }}" style="{{ $counts['overdue'] ? 'border-color:var(--red);color:var(--red)' : '' }}">Overdue <span class="tab-count">{{ $counts['overdue'] }}</span></a>
</div>

<form method="GET" action="{{ route('tenant.vendor-bills.index') }}">
    @if($curStatus) <input type="hidden" name="status" value="{{ $curStatus }}"/> @endif
    <div class="filter-bar">
        <input type="text" name="search" class="filter-input" placeholder="Search bill no., vendor invoice, vendor…" value="{{ request('search') }}" style="min-width:240px"/>
        <select name="vendor_id" class="filter-input" onchange="this.form.submit()">
            <option value="">All vendors</option>
            @foreach($vendors as $v)
            <option value="{{ $v->id }}" {{ (string) request('vendor_id') === (string) $v->id ? 'selected' : '' }}>{{ $v->name }}</option>
            @endforeach
        </select>
        <button type="submit" class="btn btn-secondary">Search</button>
        @if(request()->hasAny(['search','vendor_id']))
        <a href="{{ route('tenant.vendor-bills.index', $curStatus ? ['status'=>$curStatus] : []) }}" class="btn btn-secondary">Clear</a>
        @endif
    </div>
</form>

<div class="card">
    @if($bills->isEmpty())
    <div class="empty-state">
        <div style="font-size:40px;margin-bottom:12px">🧾</div>
        <div style="font-size:15px;font-weight:700;color:var(--text-100);margin-bottom:6px">No vendor bills found</div>
        <div style="font-size:13px;color:var(--text-300);margin-bottom:20px">Record a bill when a supplier invoices you.</div>
        @can('create', \App\Models\VendorBill::class)
        <a href="{{ route('tenant.vendor-bills.create') }}" class="btn btn-primary">Create Vendor Bill</a>
        @endcan
    </div>
    @else
    <div style="overflow-x:auto">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Bill</th>
                    <th>Vendor</th>
                    <th>Date</th>
                    <th>Due</th>
                    <th>Total</th>
                    <th>Paid</th>
                    <th>Balance</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($bills as $bill)
                @php
                    $meta = config('crm.vendor_bill.statuses')[$bill->status] ?? ['color' => 'text-300', 'bg' => 'bg-elevated'];
                @endphp
                <tr>
                    <td data-label="Bill">
                        <a href="{{ route('tenant.vendor-bills.show', $bill->id) }}" style="text-decoration:none;color:var(--text-100);font-weight:600">{{ $bill->number }}</a>
                        @if($bill->vendor_invoice_number)<div style="font-size:11.5px;color:var(--text-400)">Ref: {{ $bill->vendor_invoice_number }}</div>@endif
                    </td>
                    <td data-label="Vendor">{{ $bill->vendor?->name ?? '—' }}</td>
                    <td class="td-mono" style="font-size:12.5px" data-label="Date">{{ $bill->date?->format('d M Y') }}</td>
                    <td class="td-mono" style="font-size:12.5px" data-label="Due">
                        {{ $bill->due_date?->format('d M Y') ?? '—' }}
                        @if($bill->isOverdue())<span style="color:var(--red);font-weight:600"> • overdue</span>@endif
                    </td>
                    <td class="td-mono" data-label="Total">₹{{ number_format($bill->total, 2) }}</td>
                    <td class="td-mono" style="color:var(--green)" data-label="Paid">₹{{ number_format($bill->amount_paid, 2) }}</td>
                    <td class="td-mono" style="color:var(--red);font-weight:700" data-label="Balance">₹{{ number_format($bill->due_amount, 2) }}</td>
                    <td data-label="Status">
                        <span class="badge" style="background:var(--{{ $meta['bg'] }});color:var(--{{ $meta['color'] }});padding:3px 10px;border-radius:20px;font-size:11.5px;font-weight:600">{{ $statuses[$bill->status] ?? $bill->status }}</span>
                    </td>
                    <td>
                        <a href="{{ route('tenant.vendor-bills.show', $bill->id) }}" class="btn btn-secondary btn-sm">View</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if($bills->hasPages())
    <div class="pagination-wrap">
        <span>Showing {{ $bills->firstItem() }}–{{ $bills->lastItem() }} of {{ $bills->total() }}</span>
        <div style="display:flex;gap:4px">
            <a href="{{ $bills->previousPageUrl() ?? '#' }}" class="page-link {{ !$bills->previousPageUrl() ? 'disabled' : '' }}">←</a>
            <a href="{{ $bills->nextPageUrl() ?? '#' }}" class="page-link {{ !$bills->nextPageUrl() ? 'disabled' : '' }}">→</a>
        </div>
    </div>
    @endif
    @endif
</div>
@endsection
