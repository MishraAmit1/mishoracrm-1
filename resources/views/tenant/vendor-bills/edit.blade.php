@extends('layouts.app')
@section('title', 'Edit ' . $bill->number)

@section('content')
<div class="page-head">
    <div>
        <div style="font-size:12px;color:var(--text-300);margin-bottom:4px">
            <a href="{{ route('tenant.vendor-bills.index') }}" style="color:var(--text-300);text-decoration:none">Vendor Bills</a>
            › <a href="{{ route('tenant.vendor-bills.show', $bill->id) }}" style="color:var(--text-300);text-decoration:none">{{ $bill->number }}</a>
            › Edit
        </div>
        <div class="page-title">Edit {{ $bill->number }}</div>
    </div>
    <a href="{{ route('tenant.vendor-bills.show', $bill->id) }}" class="btn btn-secondary">← Back</a>
</div>

@include('tenant.vendor-bills._form', ['action' => route('tenant.vendor-bills.update', $bill->id)])
@endsection
