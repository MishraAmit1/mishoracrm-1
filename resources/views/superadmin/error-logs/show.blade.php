@extends('layouts.app')
@section('title', 'Error Detail')

@push('styles')
<style>
.error-detail-grid { display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:16px; }
@media(max-width:800px){ .error-detail-grid{ grid-template-columns:1fr; } }
.detail-row { display:flex; gap:12px; padding:10px 0; border-bottom:1px solid var(--border-subtle); align-items:flex-start; }
.detail-row:last-child { border-bottom:none; }
.detail-label { font-size:12px; color:var(--text-400); min-width:120px; padding-top:1px; }
.detail-value { font-size:13px; color:var(--text-100); word-break:break-all; flex:1; }
.stack-trace { background:var(--bg-input); border:1px solid var(--border-default); border-radius:var(--r-md);
    padding:14px; font-family:var(--mono); font-size:11.5px; color:var(--text-200);
    white-space:pre-wrap; word-break:break-all; max-height:420px; overflow-y:auto; line-height:1.7; }
.http-badge { display:inline-block; font-size:13px; font-weight:700; padding:3px 10px; border-radius:4px; font-family:var(--mono); }
.http-5xx { background:var(--red-dim); color:var(--red); }
.http-4xx { background:var(--amber-dim); color:var(--amber); }
</style>
@endpush

@section('content')

@php
    $statusClass = ($errorLog->http_status ?? 0) >= 500 ? 'http-5xx' : 'http-4xx';
@endphp

<div class="page-head">
    <div>
        <div class="page-title">
            <span class="http-badge {{ $statusClass }}" style="margin-right:8px;">{{ $errorLog->http_status }}</span>
            Error Detail
        </div>
        <div class="page-sub">{{ $errorLog->created_at->format('d M Y, H:i:s') }}</div>
    </div>
    <div style="display:flex;gap:8px;">
        @if(!$errorLog->is_resolved)
        <form method="POST" action="{{ route('superadmin.error-logs.resolve', $errorLog) }}">
            @csrf
            <button type="submit" class="btn btn-primary btn-sm">Mark Resolved</button>
        </form>
        @else
        <span style="font-size:12px;color:var(--green);padding:6px 12px;background:var(--green-dim);border-radius:var(--r-sm);">
            ✓ Resolved
        </span>
        @endif
        <a href="{{ route('superadmin.error-logs.index') }}" class="btn btn-secondary btn-sm">← Back</a>
    </div>
</div>

<div class="error-detail-grid">
    {{-- Left: Error info --}}
    <div class="card">
        <div class="card-header"><div class="card-title">Error Info</div></div>
        <div class="card-body">
            <div class="detail-row">
                <div class="detail-label">Message</div>
                <div class="detail-value" style="font-weight:600;color:var(--red);">{{ $errorLog->message }}</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Exception</div>
                <div class="detail-value" style="font-family:var(--mono);font-size:12px;">{{ $errorLog->exception_class }}</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">HTTP Status</div>
                <div class="detail-value"><span class="http-badge {{ $statusClass }}">{{ $errorLog->http_status }}</span></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Method</div>
                <div class="detail-value" style="font-family:var(--mono);font-weight:700;color:var(--accent);">{{ $errorLog->method }}</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">URL</div>
                <div class="detail-value" style="font-family:var(--mono);font-size:12px;">{{ $errorLog->url }}</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Time</div>
                <div class="detail-value">{{ $errorLog->created_at->format('d M Y, H:i:s') }}
                    <span style="color:var(--text-400);font-size:11px;">({{ $errorLog->created_at->diffForHumans() }})</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Right: User/Tenant info --}}
    <div class="card">
        <div class="card-header"><div class="card-title">User & Tenant</div></div>
        <div class="card-body">
            <div class="detail-row">
                <div class="detail-label">Tenant</div>
                <div class="detail-value">
                    @if($errorLog->tenant)
                        <a href="{{ route('superadmin.tenants.show', $errorLog->tenant) }}"
                           style="color:var(--accent);font-weight:600;">{{ $errorLog->tenant->name }}</a>
                        <div style="font-size:11.5px;color:var(--text-400)">{{ $errorLog->tenant->subdomain }}</div>
                    @else
                        <span style="color:var(--text-400)">—</span>
                    @endif
                </div>
            </div>
            <div class="detail-row">
                <div class="detail-label">User</div>
                <div class="detail-value">
                    @if($errorLog->user)
                        <div style="font-weight:600;">{{ $errorLog->user->name }}</div>
                        <div style="font-size:11.5px;color:var(--text-400)">{{ $errorLog->user->email }}</div>
                    @else
                        <span style="color:var(--text-400)">Guest / Unauthenticated</span>
                    @endif
                </div>
            </div>
            <div class="detail-row">
                <div class="detail-label">IP Address</div>
                <div class="detail-value" style="font-family:var(--mono);font-size:12px;">{{ $errorLog->ip_address ?? '—' }}</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">User Agent</div>
                <div class="detail-value" style="font-size:11.5px;color:var(--text-300);">{{ $errorLog->user_agent ?? '—' }}</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Status</div>
                <div class="detail-value">
                    @if($errorLog->is_resolved)
                        <span style="color:var(--green);font-weight:600;">Resolved</span>
                    @else
                        <span style="color:var(--red);font-weight:600;">Open</span>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Request Data --}}
@if($errorLog->request_data)
<div class="card" style="margin-bottom:16px;">
    <div class="card-header"><div class="card-title">Request Data</div></div>
    <div class="card-body">
        <pre class="stack-trace" style="max-height:200px;">{{ json_encode($errorLog->request_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
    </div>
</div>
@endif

{{-- Stack Trace --}}
@if($errorLog->stack_trace)
<div class="card">
    <div class="card-header"><div class="card-title">Stack Trace</div></div>
    <div class="card-body">
        <pre class="stack-trace">{{ $errorLog->stack_trace }}</pre>
    </div>
</div>
@endif

@endsection
