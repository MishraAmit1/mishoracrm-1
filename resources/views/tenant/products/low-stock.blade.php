@extends('layouts.app')
@section('title', 'Low Stock')

@push('styles')
<style>
.ls-table { width:100%; border-collapse:collapse; background:var(--bg-surface); border:1px solid var(--border-default); border-radius:var(--r-lg); overflow:hidden; }
.ls-table th { padding:10px 14px; text-align:left; font-size:11px; font-weight:700; color:var(--text-400); text-transform:uppercase; letter-spacing:.4px; background:var(--bg-elevated); border-bottom:1px solid var(--border-subtle); }
.ls-table td { padding:11px 14px; border-bottom:1px solid var(--border-subtle); font-size:13.5px; color:var(--text-100); vertical-align:top; }
.ls-table tr:last-child td { border-bottom:none; }
.mono { font-family:var(--mono); }
.badge-type-fg  { display:inline-block; padding:2px 9px; border-radius:20px; font-size:11.5px; font-weight:600; background:var(--accent-dim); color:var(--accent); }
.badge-type-rm  { display:inline-block; padding:2px 9px; border-radius:20px; font-size:11.5px; font-weight:600; background:var(--purple-dim); color:var(--purple); }
.badge-low-stock { display:inline-block; padding:2px 9px; border-radius:20px; font-size:11.5px; font-weight:600; background:var(--red-dim); color:var(--red); }
.suggest-box { margin-top:8px; padding:9px 11px; background:var(--bg-elevated); border-radius:8px; font-size:12px; }
.suggest-row { display:flex; justify-content:space-between; gap:10px; padding:3px 0; color:var(--text-300); }
.suggest-row strong { color:var(--text-100); }
</style>
@endpush

@section('content')

<div class="page-head">
    <div>
        <div class="page-title">Low Stock</div>
        <div class="page-sub">Products at or below their reorder level</div>
    </div>
    <a href="{{ route('tenant.products.index') }}" class="btn btn-secondary">← Back to Products</a>
</div>

@if(session('success'))
<div style="padding:10px 14px;background:var(--green-dim);border:1px solid rgba(52,199,89,.25);border-radius:var(--r-sm);margin-bottom:14px;font-size:13px;color:var(--green)">
    {{ session('success') }}
</div>
@endif

<div style="overflow-x:auto">
<table class="ls-table">
    <thead>
        <tr>
            <th>Product</th>
            <th>Type</th>
            <th>Current Stock</th>
            <th>Reorder Level</th>
            <th>Suggested Purchase</th>
            <th style="width:180px"></th>
        </tr>
    </thead>
    <tbody>
        @forelse($products as $p)
        <tr>
            <td style="font-weight:600">
                {{ $p->name }}
                @if($p->product_code)<div class="mono" style="font-size:11px;color:var(--text-400);font-weight:400">{{ $p->product_code }}</div>@endif
            </td>
            <td>
                @if($p->type === 'raw_material')
                    <span class="badge-type-rm">Raw Material</span>
                @else
                    <span class="badge-type-fg">Finished Good</span>
                @endif
            </td>
            <td class="mono">
                {{ number_format($p->current_stock, 2) }} {{ $p->unit }}
                <span class="badge-low-stock" style="margin-left:6px">Low</span>
            </td>
            <td class="mono">{{ number_format($p->reorder_level, 2) }} {{ $p->unit }}</td>
            <td>
                @php $rows = $suggestions[$p->id] ?? []; @endphp
                @if($p->type === 'finished_good' && count($rows))
                    <div class="suggest-box">
                        @foreach($rows as $row)
                        <div class="suggest-row">
                            <span>{{ $row['name'] }}</span>
                            <strong>{{ $row['quantity'] }}</strong>
                        </div>
                        @endforeach
                    </div>
                @elseif($p->type === 'finished_good')
                    <span style="color:var(--text-400);font-size:12.5px">No BOM shortfall — raw materials in stock, or no BOM set</span>
                @else
                    <span style="color:var(--text-400);font-size:12.5px">—</span>
                @endif
            </td>
            <td>
                <a href="{{ route('tenant.purchase-requests.create', ['suggest_from' => $p->id]) }}" class="btn btn-primary btn-sm">
                    Create Purchase Request
                </a>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="6" style="text-align:center;padding:40px;color:var(--text-400)">
                Nothing is low on stock right now.
            </td>
        </tr>
        @endforelse
    </tbody>
</table>
</div>

@endsection
