@extends('layouts.app')
@section('title', 'Products / Services')

@push('styles')
<style>
.prod-head { display:flex; justify-content:space-between; align-items:center; margin-bottom:16px; }
.prod-table { width:100%; border-collapse:collapse; background:var(--bg-surface); border:1px solid var(--border-default); border-radius:var(--r-lg); overflow:hidden; }
.prod-table th { padding:10px 14px; text-align:left; font-size:11px; font-weight:700; color:var(--text-400); text-transform:uppercase; letter-spacing:.4px; background:var(--bg-elevated); border-bottom:1px solid var(--border-subtle); }
.prod-table td { padding:11px 14px; border-bottom:1px solid var(--border-subtle); font-size:13.5px; color:var(--text-100); vertical-align:middle; }
.prod-table tr:last-child td { border-bottom:none; }
.prod-table tr:hover td { background:var(--bg-elevated); }
.badge-active   { display:inline-block; padding:2px 9px; border-radius:20px; font-size:11.5px; font-weight:600; background:var(--green-dim); color:var(--green); }
.badge-inactive { display:inline-block; padding:2px 9px; border-radius:20px; font-size:11.5px; font-weight:600; background:var(--bg-elevated); color:var(--text-400); }
.mono { font-family:var(--mono); }
</style>
@endpush

@section('content')

<div class="prod-head">
    <div>
        <div class="page-title">Products / Services</div>
        <div class="page-sub">Item catalog used for auto-filling invoices & quotations</div>
    </div>
    <a href="{{ route('tenant.products.create') }}" class="btn btn-primary">+ Add Product</a>
</div>

@if(session('success'))
<div style="padding:10px 14px;background:var(--green-dim);border:1px solid rgba(52,199,89,.25);border-radius:var(--r-sm);margin-bottom:14px;font-size:13px;color:var(--green)">
    {{ session('success') }}
</div>
@endif

<form method="GET" style="margin-bottom:14px;display:flex;gap:8px">
    <input type="text" name="search" value="{{ request('search') }}"
           placeholder="Search products..."
           style="padding:9px 13px;border:1.5px solid var(--border-default);border-radius:var(--r-sm);background:var(--bg-input);color:var(--text-100);font-size:13.5px;outline:none;width:280px"/>
    <button class="btn btn-secondary" type="submit">Search</button>
    @if(request('search'))
    <a href="{{ route('tenant.products.index') }}" class="btn btn-secondary">Clear</a>
    @endif
</form>

<table class="prod-table">
    <thead>
        <tr>
            <th>Name</th>
            <th>Description</th>
            <th>HSN</th>
            <th>Rate (₹)</th>
            <th>GST %</th>
            <th>Unit</th>
            <th>Status</th>
            <th style="width:100px"></th>
        </tr>
    </thead>
    <tbody>
        @forelse($products as $p)
        <tr>
            <td style="font-weight:600">{{ $p->name }}</td>
            <td style="color:var(--text-300);font-size:12.5px;max-width:200px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                {{ $p->description ?: '—' }}
            </td>
            <td class="mono" style="font-size:12.5px">{{ $p->hsn ?: '—' }}</td>
            <td class="mono">₹{{ number_format($p->rate, 2) }}</td>
            <td class="mono">{{ $p->tax_percent }}%</td>
            <td>{{ $p->unit ?: '—' }}</td>
            <td>
                @if($p->is_active)
                    <span class="badge-active">Active</span>
                @else
                    <span class="badge-inactive">Inactive</span>
                @endif
            </td>
            <td style="display:flex;gap:6px">
                <a href="{{ route('tenant.products.edit', $p->id) }}" class="btn btn-secondary btn-sm">Edit</a>
                <form method="POST" action="{{ route('tenant.products.destroy', $p->id) }}"
                      onsubmit="return confirm('Delete this product?')">
                    @csrf @method('DELETE')
                    <button class="btn btn-sm" type="submit"
                            style="background:var(--red-dim);color:var(--red);border:1px solid rgba(255,82,87,.25)">
                        Del
                    </button>
                </form>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="8" style="text-align:center;padding:40px;color:var(--text-400)">
                No products yet. <a href="{{ route('tenant.products.create') }}" style="color:var(--accent)">Add your first product</a>.
            </td>
        </tr>
        @endforelse
    </tbody>
</table>

<div style="margin-top:14px">{{ $products->links() }}</div>

@endsection
