@extends('layouts.app')
@section('title', 'Workflow Requests — Superadmin')

@push('styles')
<style>
.req-status { display:inline-flex;align-items:center;gap:5px;font-size:11.5px;font-weight:600;padding:3px 9px;border-radius:20px; }
.req-status.new         { background:var(--accent-dim); color:var(--accent); }
.req-status.in_progress { background:var(--amber-dim);  color:var(--amber);  }
.req-status.completed   { background:var(--green-dim);  color:var(--green);  }
.req-status.rejected    { background:var(--red-dim);    color:var(--red);    }
</style>
@endpush

@section('content')

<div class="page-head">
    <div>
        <div class="page-title">
            Workflow Requests
            @if($newCount > 0)
                <span style="background:var(--red);color:#fff;font-size:11px;font-weight:700;padding:2px 8px;border-radius:20px;margin-left:8px;vertical-align:middle;">
                    {{ $newCount }} new
                </span>
            @endif
        </div>
        <div class="page-sub">Automation requests submitted by tenants</div>
    </div>
    <form method="GET" style="display:flex;gap:8px;align-items:center;">
        <select name="status" class="form-control" style="width:160px;" onchange="this.form.submit()">
            <option value="">All Status</option>
            @foreach(['new','in_progress','completed','rejected'] as $s)
                <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>
                    {{ ucfirst(str_replace('_', ' ', $s)) }}
                </option>
            @endforeach
        </select>
    </form>
</div>

<div class="card">
    <div class="card-header">
        <div>
            <div class="card-title">Requests</div>
            <div class="card-subtitle">{{ $requests->total() }} total</div>
        </div>
    </div>
    <div style="overflow-x:auto;">
        @if($requests->isNotEmpty())
        <table class="data-table">
            <thead>
                <tr>
                    <th>Tenant</th>
                    <th>Requested By</th>
                    <th>Workflow</th>
                    <th>Business Type</th>
                    <th>Contact</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($requests as $req)
                <tr>
                    <td data-label="Tenant">
                        <div style="font-weight:600;color:var(--text-100);font-size:13px;">
                            {{ $req->tenant?->name ?? '—' }}
                        </div>
                    </td>
                    <td style="font-size:13px;color:var(--text-200);" data-label="Requested By">{{ $req->user?->name ?? '—' }}</td>
                    <td style="font-size:13px;color:var(--text-200);" data-label="Workflow">{{ $req->template?->title ?? 'Custom' }}</td>
                    <td style="font-size:13px;color:var(--text-200);" data-label="Business Type">{{ $req->business_type }}</td>
                    <td data-label="Contact">
                        <div style="font-size:12px;color:var(--text-300);">
                            {{ ucfirst($req->contact_preference) }}<br>
                            <span style="color:var(--text-200);">{{ $req->contact_value }}</span>
                        </div>
                    </td>
                    <td data-label="Status">
                        <span class="req-status {{ $req->status }}">
                            {{ ucfirst(str_replace('_', ' ', $req->status)) }}
                        </span>
                    </td>
                    <td style="font-size:12px;color:var(--text-300);" data-label="Date">{{ $req->created_at->format('d M Y') }}</td>
                    <td>
                        <a href="{{ route('superadmin.workflow-requests.show', $req) }}"
                           class="btn btn-secondary btn-sm">View</a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <div style="padding:16px 20px;">
            {{ $requests->links() }}
        </div>
        @else
        <div class="card-body" style="text-align:center;padding:48px 24px;color:var(--text-300);">
            <p style="font-size:14px;">No requests yet.</p>
        </div>
        @endif
    </div>
</div>

@endsection
