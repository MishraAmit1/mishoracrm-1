@extends('layouts.app')
@section('title', 'Work Orders')

@push('styles')
<style>
.filter-bar { display:flex; align-items:center; gap:10px; flex-wrap:wrap; margin-bottom:20px; }
.filter-input {
    padding:8px 12px; height:36px; background:var(--bg-input); border:1.5px solid var(--border-default);
    border-radius:var(--r-sm); color:var(--text-100); font-family:var(--font); font-size:13px; outline:none;
}
.filter-input:focus { border-color:var(--accent); }
.search-wrap { position:relative; flex:1; min-width:180px; max-width:280px; }
.search-wrap svg { position:absolute; left:10px; top:50%; transform:translateY(-50%); width:15px; height:15px; color:var(--text-300); pointer-events:none; }
.search-wrap input { width:100%; padding-left:34px; }
.status-tabs { display:flex; gap:4px; flex-wrap:wrap; margin-bottom:20px; }
.status-tab { padding:6px 14px; border-radius:20px; font-size:12.5px; font-weight:600; text-decoration:none; border:1.5px solid var(--border-default); color:var(--text-300); background:none; display:flex; align-items:center; gap:6px; }
.status-tab:hover { border-color:var(--border-strong); color:var(--text-100); }
.status-tab.active { background:var(--accent-dim); border-color:var(--accent); color:var(--accent); }
.tab-count { font-size:11px; font-family:var(--mono); background:var(--bg-elevated); padding:0 5px; border-radius:10px; color:var(--text-300); }
.status-tab.active .tab-count { background:var(--accent); color:#fff; }
.pagination-wrap { display:flex; align-items:center; justify-content:space-between; padding:14px 20px; border-top:1px solid var(--border-subtle); font-size:13px; color:var(--text-300); }
.pagination-links { display:flex; gap:4px; }
.page-link { padding:5px 10px; border-radius:var(--r-sm); border:1px solid var(--border-default); color:var(--text-200); text-decoration:none; font-size:13px; }
.page-link:hover { border-color:var(--accent); color:var(--accent); }
.page-link.active { background:var(--accent); border-color:var(--accent); color:#fff; }
.page-link.disabled { opacity:0.4; pointer-events:none; }
.empty-state { padding:60px 20px; text-align:center; }
.empty-icon { font-size:40px; margin-bottom:12px; }
.empty-title { font-size:15px; font-weight:700; color:var(--text-100); margin-bottom:6px; }
.empty-sub { font-size:13px; color:var(--text-300); margin-bottom:20px; }
</style>
@endpush

@section('content')
@php $curStatus = request('status', ''); @endphp

<div class="page-head">
    <div>
        <div class="page-title">Work Orders</div>
        <div class="page-sub">{{ $counts['all'] }} total work orders</div>
    </div>
    <div class="page-actions">
        @can('create', \App\Models\WorkOrder::class)
        <a href="{{ route('tenant.work-orders.create') }}" class="btn btn-primary">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
            </svg>
            New Work Order
        </a>
        @endcan
    </div>
</div>

<div class="status-tabs">
    <a href="{{ route('tenant.work-orders.index') }}" class="status-tab {{ $curStatus === '' ? 'active' : '' }}">
        All <span class="tab-count">{{ $counts['all'] }}</span>
    </a>
    @foreach($statuses as $key => $label)
    <a href="{{ route('tenant.work-orders.index', ['status' => $key]) }}" class="status-tab {{ $curStatus === $key ? 'active' : '' }}">
        {{ $label }} <span class="tab-count">{{ $counts[$key] ?? 0 }}</span>
    </a>
    @endforeach
</div>

<form method="GET" action="{{ route('tenant.work-orders.index') }}" id="filterForm">
    @if($curStatus) <input type="hidden" name="status" value="{{ $curStatus }}"/> @endif
    <div class="filter-bar">
        <div class="search-wrap">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/>
            </svg>
            <input type="text" name="search" class="filter-input" placeholder="Search number..." value="{{ request('search') }}"/>
        </div>
        <button type="submit" class="btn btn-secondary">Search</button>
        @if(request()->hasAny(['search']))
        <a href="{{ route('tenant.work-orders.index', $curStatus ? ['status'=>$curStatus] : []) }}" class="btn btn-secondary">Clear</a>
        @endif
    </div>
</form>

<div class="card">
    @if($workOrders->isEmpty())
    <div class="empty-state">
        <div class="empty-icon">🛠️</div>
        <div class="empty-title">No work orders found</div>
        <div class="empty-sub">Create your first production work order</div>
        @can('create', \App\Models\WorkOrder::class)
        <a href="{{ route('tenant.work-orders.create') }}" class="btn btn-primary">New Work Order</a>
        @endcan
    </div>
    @else
    <div style="overflow-x:auto">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Number</th>
                    <th>Product</th>
                    <th>Quantity</th>
                    <th>Created By</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($workOrders as $wo)
                @php
                    $badge = [
                        'pending'     => ['bg' => 'var(--amber-dim)', 'text' => 'var(--amber)'],
                        'in_progress' => ['bg' => 'var(--accent-dim)', 'text' => 'var(--accent)'],
                        'completed'   => ['bg' => 'var(--green-dim)', 'text' => 'var(--green)'],
                        'cancelled'   => ['bg' => 'var(--red-dim)', 'text' => 'var(--red)'],
                    ][$wo->status] ?? ['bg' => 'var(--amber-dim)', 'text' => 'var(--amber)'];
                @endphp
                <tr>
                    <td data-label="Number">
                        <a href="{{ route('tenant.work-orders.show', $wo->id) }}" style="text-decoration:none;color:var(--text-100);font-weight:600">{{ $wo->number }}</a>
                    </td>
                    <td data-label="Product">{{ $wo->product?->name ?? '—' }}</td>
                    <td data-label="Quantity">{{ number_format($wo->quantity, 2) }}</td>
                    <td data-label="Created By">{{ $wo->createdBy?->name ?? '—' }}</td>
                    <td data-label="Status">
                        <span class="badge" style="padding:3px 10px;border-radius:20px;font-size:11.5px;font-weight:600;background:{{ $badge['bg'] }};color:{{ $badge['text'] }}">
                            {{ \App\Models\WorkOrder::statuses()[$wo->status] ?? ucfirst($wo->status) }}
                        </span>
                    </td>
                    <td>
                        <a href="{{ route('tenant.work-orders.show', $wo->id) }}" class="btn btn-secondary btn-sm btn-icon" title="View">
                            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if($workOrders->hasPages())
    <div class="pagination-wrap">
        <span>Showing {{ $workOrders->firstItem() }}–{{ $workOrders->lastItem() }} of {{ $workOrders->total() }}</span>
        <div class="pagination-links">
            <a href="{{ $workOrders->previousPageUrl() ?? '#' }}" class="page-link {{ !$workOrders->previousPageUrl() ? 'disabled' : '' }}">←</a>
            @foreach($workOrders->getUrlRange(max(1,$workOrders->currentPage()-2), min($workOrders->lastPage(),$workOrders->currentPage()+2)) as $page => $url)
            <a href="{{ $url }}" class="page-link {{ $page == $workOrders->currentPage() ? 'active' : '' }}">{{ $page }}</a>
            @endforeach
            <a href="{{ $workOrders->nextPageUrl() ?? '#' }}" class="page-link {{ !$workOrders->nextPageUrl() ? 'disabled' : '' }}">→</a>
        </div>
    </div>
    @endif
    @endif
</div>

@endsection
