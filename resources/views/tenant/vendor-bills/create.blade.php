@extends('layouts.app')
@section('title', 'New Vendor Bill — ' . $number)

@section('content')
<div class="page-head">
    <div>
        <div style="font-size:12px;color:var(--text-300);margin-bottom:4px">
            <a href="{{ route('tenant.vendor-bills.index') }}" style="color:var(--text-300);text-decoration:none">Vendor Bills</a>
            › New
        </div>
        <div class="page-title">New Vendor Bill</div>
    </div>
    <a href="{{ route('tenant.vendor-bills.index') }}" class="btn btn-secondary">← Back</a>
</div>

@if($sourcePo ?? null)
<div style="display:flex;align-items:center;gap:9px;padding:10px 14px;background:var(--green-dim);border:1px solid var(--green);border-radius:8px;margin-bottom:14px;font-size:12.5px;color:var(--green);font-weight:500;max-width:900px">
    Pre-filled from Purchase Order <strong>{{ $sourcePo->number }}</strong>
</div>
@endif

@include('tenant.vendor-bills._form', ['action' => route('tenant.vendor-bills.store')])
@endsection
