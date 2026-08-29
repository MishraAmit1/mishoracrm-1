@extends('layouts.app')
@section('title', 'Goods Receipt — ' . $grn->number)

@push('styles')
<style>
.grn-card { background:var(--bg-surface); border:1px solid var(--border-default); border-radius:14px; overflow:hidden; max-width:900px; margin-top:20px; }
.grn-head { padding:20px 22px; border-bottom:1px solid var(--border-subtle); }
.grn-t { width:100%; border-collapse:collapse; }
.grn-t thead tr { background:var(--bg-elevated); }
.grn-t th { padding:10px 14px; text-align:left; font-size:11px; font-weight:600; color:var(--text-300); text-transform:uppercase; letter-spacing:.5px; border-bottom:1px solid var(--border-subtle); }
.grn-t td { padding:12px 14px; font-size:13.5px; color:var(--text-100); border-bottom:1px solid var(--border-subtle); }
.grn-t .r { text-align:right; font-family:var(--mono); }
</style>
@endpush

@section('content')
<div class="page-head">
    <div>
        <div style="font-size:12px;color:var(--text-300);margin-bottom:4px">
            <a href="{{ route('tenant.purchase-orders.index') }}" style="color:var(--text-300);text-decoration:none">Purchase Orders</a>
            › <a href="{{ route('tenant.purchase-orders.show', $purchaseOrder->id) }}" style="color:var(--text-300);text-decoration:none">{{ $purchaseOrder->number }}</a>
            › {{ $grn->number }}
        </div>
        <div class="page-title">Goods Receipt Note</div>
    </div>
    <a href="{{ route('tenant.purchase-orders.show', $purchaseOrder->id) }}" class="btn btn-secondary">← Back to PO</a>
</div>

<div class="grn-card">
    <div class="grn-head">
        <div style="display:flex;justify-content:space-between;flex-wrap:wrap;gap:16px">
            <div>
                <div style="font-size:20px;font-weight:600;font-family:var(--mono);color:var(--text-100)">{{ $grn->number }}</div>
                <div style="font-size:13px;color:var(--text-300);margin-top:2px">
                    {{ $purchaseOrder->vendor?->name }} · Received {{ $grn->received_date?->format('d M Y') }} · by {{ $grn->createdBy?->name ?? '—' }}
                </div>
            </div>
            <div style="text-align:right">
                <div style="font-size:12px;color:var(--text-300)">Accepted / Received</div>
                <div style="font-size:16px;font-weight:700;font-family:var(--mono);color:var(--green)">{{ number_format($grn->totalAccepted(), 2) }} / {{ number_format($grn->totalReceived(), 2) }}</div>
                @if($grn->hasRejections())
                <div style="font-size:12px;color:var(--red);font-weight:600;margin-top:2px">{{ number_format($grn->totalRejected(), 2) }} rejected</div>
                @endif
            </div>
        </div>
        @if($grn->note)
        <div style="margin-top:12px;font-size:13px;color:var(--text-200)">{{ $grn->note }}</div>
        @endif
    </div>

    <div style="overflow-x:auto">
        <table class="grn-t">
            <thead>
                <tr>
                    <th>Item</th>
                    <th class="r">Ordered</th>
                    <th class="r">Received</th>
                    <th class="r">Accepted</th>
                    <th class="r">Rejected</th>
                    <th>Reject Reason</th>
                    <th>Batch</th>
                </tr>
            </thead>
            <tbody>
                @foreach($grn->items as $line)
                <tr>
                    <td style="font-weight:500">{{ $line['name'] ?? '—' }}</td>
                    <td class="r">{{ number_format((float)($line['ordered_qty'] ?? 0), 2) }}</td>
                    <td class="r">{{ number_format((float)($line['received_qty'] ?? 0), 2) }}</td>
                    <td class="r" style="color:var(--green)">{{ number_format((float)($line['accepted_qty'] ?? 0), 2) }}</td>
                    <td class="r" style="color:{{ (float)($line['rejected_qty'] ?? 0) > 0 ? 'var(--red)' : 'var(--text-300)' }}">{{ number_format((float)($line['rejected_qty'] ?? 0), 2) }}</td>
                    <td style="color:var(--text-300)">{{ $line['rejection_reason'] ?: '—' }}</td>
                    <td style="font-family:var(--mono);color:var(--text-300)">{{ $line['batch_number'] ?: 'auto' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
