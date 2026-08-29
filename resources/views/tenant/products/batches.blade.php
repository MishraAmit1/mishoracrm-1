@extends('layouts.app')
@section('title', 'Batches — ' . $product->name)

@push('styles')
<style>
.ls-table { width:100%; border-collapse:collapse; background:var(--bg-surface); border:1px solid var(--border-default); border-radius:var(--r-lg); overflow:hidden; }
.ls-table th { padding:10px 14px; text-align:left; font-size:11px; font-weight:700; color:var(--text-400); text-transform:uppercase; letter-spacing:.4px; background:var(--bg-elevated); border-bottom:1px solid var(--border-subtle); }
.ls-table td { padding:11px 14px; border-bottom:1px solid var(--border-subtle); font-size:13.5px; color:var(--text-100); vertical-align:top; }
.ls-table tr:last-child td { border-bottom:none; }
.mono { font-family:var(--mono); }
.badge-expiring { display:inline-block; padding:2px 9px; border-radius:20px; font-size:11.5px; font-weight:600; background:var(--red-dim); color:var(--red); }
.badge-source { display:inline-block; padding:2px 9px; border-radius:20px; font-size:11px; font-weight:600; background:var(--bg-elevated); color:var(--text-300); }
</style>
@endpush

@section('content')

<div class="page-head">
    <div>
        <div class="page-title">Batches — {{ $product->name }}</div>
        <div class="page-sub">{{ number_format($product->current_stock, 2) }} {{ $product->unit }} in stock across {{ $batches->where('quantity', '>', 0)->count() }} active batch(es)</div>
    </div>
    <a href="{{ route('tenant.products.index') }}" class="btn btn-secondary">← Back to Products</a>
</div>

<div style="overflow-x:auto">
<table class="ls-table">
    <thead>
        <tr>
            <th>Batch #</th>
            <th>Received</th>
            <th>Expiry</th>
            <th>Remaining / Initial</th>
            <th style="text-align:right">Unit Cost</th>
            <th>Source</th>
        </tr>
    </thead>
    <tbody>
        @forelse($batches as $batch)
        @php $expiringSoon = $batch->expiry_date && $batch->expiry_date->isFuture() && $batch->expiry_date->diffInDays(now()) <= 7; @endphp
        <tr style="{{ $batch->quantity <= 0 ? 'opacity:.5' : '' }}">
            <td class="mono" style="font-weight:600">{{ $batch->batch_number }}</td>
            <td class="mono">{{ $batch->received_at?->format('d M Y') }}</td>
            <td class="mono">
                {{ $batch->expiry_date?->format('d M Y') ?? '—' }}
                @if($expiringSoon)<span class="badge-expiring" style="margin-left:6px">Expiring Soon</span>@endif
            </td>
            <td class="mono">{{ number_format($batch->quantity, 2) }} / {{ number_format($batch->initial_quantity, 2) }} {{ $product->unit }}</td>
            <td class="mono" style="text-align:right">{{ $batch->unit_cost !== null ? '₹'.number_format($batch->unit_cost, 2) : '—' }}</td>
            <td>
                @if($batch->purchase_order_id)
                    <a href="{{ route('tenant.purchase-orders.show', $batch->purchase_order_id) }}" class="badge-source" style="text-decoration:none">PO #{{ $batch->purchase_order_id }}</a>
                @elseif($batch->work_order_id)
                    <a href="{{ route('tenant.work-orders.show', $batch->work_order_id) }}" class="badge-source" style="text-decoration:none">WO #{{ $batch->work_order_id }}</a>
                @else
                    <span class="badge-source">Manual / Adjustment</span>
                @endif
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="6" style="text-align:center;padding:40px;color:var(--text-400)">
                No batches recorded yet — batches are created when stock is received via a Purchase Order or a Work Order completes.
            </td>
        </tr>
        @endforelse
    </tbody>
</table>
</div>

@endsection
