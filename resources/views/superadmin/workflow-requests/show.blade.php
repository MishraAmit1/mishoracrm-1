@extends('layouts.app')
@section('title', 'Workflow Request #{{ $req->id }}')

@push('styles')
<style>
.req-status { display:inline-flex;align-items:center;font-size:12px;font-weight:600;padding:4px 12px;border-radius:20px; }
.req-status.new         { background:var(--accent-dim); color:var(--accent); }
.req-status.in_progress { background:var(--amber-dim);  color:var(--amber);  }
.req-status.completed   { background:var(--green-dim);  color:var(--green);  }
.req-status.rejected    { background:var(--red-dim);    color:var(--red);    }

.detail-grid { display:grid; grid-template-columns:1fr 1fr; gap:20px; margin-bottom:20px; }
@media(max-width:640px) { .detail-grid { grid-template-columns:1fr; } }
.detail-item label { font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.05em; color:var(--text-400); display:block; margin-bottom:5px; }
.detail-item p { font-size:14px; color:var(--text-100); line-height:1.5; }

.status-card { position:sticky; top:20px; }
</style>
@endpush

@section('content')

<div class="page-head">
    <div>
        <div class="page-title">Request #{{ $req->id }}</div>
        <div class="page-sub">{{ $req->tenant?->name }} &mdash; {{ $req->created_at->format('d M Y, h:i A') }}</div>
    </div>
    <a href="{{ route('superadmin.workflow-requests.index') }}" class="btn btn-secondary">
        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
        </svg>
        Back
    </a>
</div>

<div style="display:grid;grid-template-columns:1fr 340px;gap:20px;align-items:start;">

    {{-- Left: Request details --}}
    <div class="card">
        <div class="card-header">
            <div>
                <div class="card-title">Request Details</div>
            </div>
            <span class="req-status {{ $req->status }}">
                {{ ucfirst(str_replace('_', ' ', $req->status)) }}
            </span>
        </div>
        <div class="card-body">

            <div class="detail-grid">
                <div class="detail-item">
                    <label>Tenant</label>
                    <p>{{ $req->tenant?->name ?? '—' }}</p>
                </div>
                <div class="detail-item">
                    <label>Requested By</label>
                    <p>{{ $req->user?->name ?? '—' }}<br>
                        <span style="font-size:12px;color:var(--text-300);">{{ $req->user?->email }}</span>
                    </p>
                </div>
                <div class="detail-item">
                    <label>Workflow Template</label>
                    <p>{{ $req->template?->title ?? 'Custom Request' }}</p>
                </div>
                <div class="detail-item">
                    <label>Business Type</label>
                    <p>{{ $req->business_type }}</p>
                </div>
                <div class="detail-item">
                    <label>Contact Preference</label>
                    <p>{{ ucfirst($req->contact_preference) }}</p>
                </div>
                <div class="detail-item">
                    <label>Contact Value</label>
                    <p>{{ $req->contact_value }}</p>
                </div>
            </div>

            <div style="padding-top:16px;border-top:1px solid var(--border-subtle);">
                <div class="detail-item">
                    <label>Problem Description</label>
                    <p style="white-space:pre-wrap;line-height:1.7;">{{ $req->problem_description }}</p>
                </div>
            </div>

            @if($req->admin_notes)
            <div style="margin-top:16px;padding-top:16px;border-top:1px solid var(--border-subtle);">
                <div class="detail-item">
                    <label>Admin Notes</label>
                    <p style="white-space:pre-wrap;line-height:1.7;">{{ $req->admin_notes }}</p>
                </div>
            </div>
            @endif

        </div>
    </div>

    {{-- Right: Update status --}}
    <div class="card status-card">
        <div class="card-header">
            <div class="card-title">Update Status</div>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('superadmin.workflow-requests.update-status', $req) }}">
                @csrf @method('PATCH')

                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-control">
                        @foreach(['new','in_progress','completed','rejected'] as $s)
                            <option value="{{ $s }}" {{ $req->status === $s ? 'selected' : '' }}>
                                {{ ucfirst(str_replace('_', ' ', $s)) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Admin Notes</label>
                    <textarea name="admin_notes" class="form-control" rows="5"
                              placeholder="Internal notes ya client ke liye message...">{{ old('admin_notes', $req->admin_notes) }}</textarea>
                </div>

                <button type="submit" class="btn btn-primary" style="width:100%;">Update Request</button>
            </form>
        </div>
    </div>

</div>

@endsection
