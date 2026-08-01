@extends('layouts.app')
@section('title', 'Import Complete')

@push('styles')
<style>
.imp-steps{display:flex;gap:18px;margin-bottom:22px}
.imp-step{display:flex;align-items:center;gap:8px;font-size:12.5px;color:var(--text-300);font-weight:600}
.imp-step .imp-dot{width:22px;height:22px;border-radius:50%;background:var(--bg-elevated);border:1.5px solid var(--border-default);display:flex;align-items:center;justify-content:center;font-size:11px}
.imp-step.done .imp-dot{background:var(--green,#1D9E75);border-color:var(--green,#1D9E75);color:#fff}
.imp-step.done{color:var(--text-100)}
.res-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:12px;margin-bottom:20px}
.res-tile{background:var(--bg-surface);border:1px solid var(--border-default);border-radius:14px;padding:18px;text-align:center}
.res-num{font-size:26px;font-weight:800;font-family:var(--mono);line-height:1}
.res-lbl{font-size:11.5px;color:var(--text-300);text-transform:uppercase;letter-spacing:.5px;margin-top:6px}
.err-table-wrap{background:var(--bg-surface);border:1px solid var(--border-default);border-radius:14px;overflow:hidden}
.err-table{width:100%;border-collapse:collapse}
.err-table th{padding:9px 14px;text-align:left;font-size:10.5px;font-weight:700;color:var(--text-400);text-transform:uppercase;letter-spacing:.5px;border-bottom:1px solid var(--border-subtle);background:var(--bg-elevated)}
.err-table td{padding:9px 14px;font-size:12.5px;color:var(--text-100);border-bottom:1px solid var(--border-subtle)}
</style>
@endpush

@section('content')
<div class="page-head">
    <div>
        <div class="page-title">Import Complete</div>
        <div class="page-sub">{{ $type }} import finished — here's what happened.</div>
    </div>
    <a href="{{ route($backRoute) }}" class="btn btn-primary">Done</a>
</div>

<div class="imp-steps">
    <div class="imp-step done"><span class="imp-dot">&#10003;</span> Upload file</div>
    <div class="imp-step done"><span class="imp-dot">&#10003;</span> Map columns</div>
    <div class="imp-step done"><span class="imp-dot">&#10003;</span> Review results</div>
</div>

<div class="res-grid">
    <div class="res-tile">
        <div class="res-num" style="color:var(--green,#1D9E75)">{{ $created }}</div>
        <div class="res-lbl">Created</div>
    </div>
    <div class="res-tile">
        <div class="res-num" style="color:var(--amber,#EF9F27)">{{ $skipped }}</div>
        <div class="res-lbl">Skipped (Duplicate)</div>
    </div>
    <div class="res-tile">
        <div class="res-num" style="color:var(--red,#E05252)">{{ count($errors) }}</div>
        <div class="res-lbl">Errors</div>
    </div>
</div>

@if (count($errors))
<div class="err-table-wrap">
    <table class="err-table">
        <thead><tr><th style="width:80px">Row</th><th>Issue</th></tr></thead>
        <tbody>
            @foreach ($errors as $err)
            <tr><td>{{ $err['row'] }}</td><td>{{ $err['message'] }}</td></tr>
            @endforeach
        </tbody>
    </table>
</div>
@endif
@endsection
