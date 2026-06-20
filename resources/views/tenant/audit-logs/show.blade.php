@extends('layouts.app')
@section('title', 'Audit Log Detail')

@push('styles')
<style>
.detail-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px;margin-bottom:20px}
.detail-card{background:var(--bg-surface);border:1px solid var(--border-default);border-radius:var(--r-md);padding:14px 16px}
.detail-label{font-size:10.5px;font-weight:700;color:var(--text-400);text-transform:uppercase;letter-spacing:.5px;margin-bottom:5px}
.detail-value{font-size:13.5px;color:var(--text-100);font-weight:500}
.detail-value.mono{font-family:var(--mono);font-size:12.5px}

.a-badge{display:inline-flex;align-items:center;gap:5px;font-size:10.5px;font-weight:700;padding:3px 9px;border-radius:100px;white-space:nowrap;letter-spacing:.03em;text-transform:uppercase}
.a-badge.created{background:var(--green-dim);color:var(--green)}
.a-badge.updated{background:var(--amber-dim);color:var(--amber)}
.a-badge.deleted{background:var(--red-dim);color:var(--red)}
.a-badge.restored{background:var(--accent-dim);color:var(--accent)}
.a-badge.login{background:var(--accent-dim);color:var(--accent)}
.a-badge.logout{background:var(--bg-elevated);color:var(--text-300)}

.changes-table{width:100%;border-collapse:collapse}
.changes-table th{padding:8px 12px;text-align:left;font-size:10.5px;font-weight:700;color:var(--text-400);text-transform:uppercase;letter-spacing:.5px;border-bottom:1px solid var(--border-subtle);background:var(--bg-elevated)}
.changes-table td{padding:9px 12px;font-size:13px;border-bottom:1px solid var(--border-subtle);vertical-align:top;font-family:var(--mono)}
.changes-table tbody tr:last-child td{border-bottom:none}
.old-val{color:var(--red);background:rgba(224,82,82,.06);padding:2px 6px;border-radius:4px;word-break:break-all}
.new-val{color:var(--green);background:rgba(29,158,117,.06);padding:2px 6px;border-radius:4px;word-break:break-all}
.field-name{font-size:12px;font-weight:600;color:var(--text-200);font-family:var(--mono)}

.json-block{background:var(--bg-elevated);border:1px solid var(--border-default);border-radius:var(--r-sm);padding:14px;font-family:var(--mono);font-size:12px;color:var(--text-200);overflow-x:auto;white-space:pre-wrap;word-break:break-all;max-height:300px;overflow-y:auto}
</style>
@endpush

@section('content')
<div class="page-header">
    <div style="display:flex;align-items:center;gap:12px">
        <a href="{{ route('tenant.audit-logs.index') }}" class="btn btn-ghost btn-sm">
            <i data-feather="arrow-left" style="width:14px;height:14px"></i>
            Back
        </a>
        <div>
            <h1 class="page-title">Audit Log #{{ $log->id }}</h1>
            <p class="page-sub">{{ $log->created_at->format('d M Y, H:i:s') }}</p>
        </div>
    </div>
</div>

{{-- Summary cards --}}
<div class="detail-grid">
    <div class="detail-card">
        <div class="detail-label">Action</div>
        <div class="detail-value">
            <span class="a-badge {{ $log->action }}">
                <i data-feather="{{ $log->action_icon }}" style="width:11px;height:11px"></i>
                {{ $log->action }}
            </span>
        </div>
    </div>

    <div class="detail-card">
        <div class="detail-label">Module</div>
        <div class="detail-value mono">
            {{ $log->model_short_name }}
            @if($log->model_id)
                <span style="color:var(--text-400)">#{{ $log->model_id }}</span>
            @endif
        </div>
    </div>

    <div class="detail-card">
        <div class="detail-label">Record</div>
        <div class="detail-value">{{ $log->model_label ?? '—' }}</div>
    </div>

    <div class="detail-card">
        <div class="detail-label">Performed By</div>
        <div class="detail-value">
            @if($log->user)
                {{ $log->user->name }}
                <div style="font-size:11.5px;color:var(--text-400);margin-top:2px">{{ $log->user->email }}</div>
            @else
                <span style="color:var(--text-400)">System / Webhook</span>
            @endif
        </div>
    </div>

    <div class="detail-card">
        <div class="detail-label">IP Address</div>
        <div class="detail-value mono">{{ $log->ip_address ?? '—' }}</div>
    </div>

    <div class="detail-card">
        <div class="detail-label">Timestamp</div>
        <div class="detail-value">
            {{ $log->created_at->format('d M Y, H:i:s') }}
            <div style="font-size:11.5px;color:var(--text-400);margin-top:2px">{{ $log->created_at->diffForHumans() }}</div>
        </div>
    </div>
</div>

@if($log->user_agent)
    <div class="card" style="margin-bottom:16px">
        <div class="detail-label" style="margin-bottom:6px">User Agent</div>
        <div style="font-size:12px;color:var(--text-300);font-family:var(--mono)">{{ $log->user_agent }}</div>
    </div>
@endif

{{-- Changed fields (for updates) --}}
@if($log->action === 'updated' && count($log->changed_fields) > 0)
    <div class="card" style="padding:0;overflow:hidden;margin-bottom:16px">
        <div style="padding:14px 16px;border-bottom:1px solid var(--border-subtle)">
            <h3 style="font-size:14px;font-weight:700;margin:0">Changed Fields</h3>
        </div>
        <table class="changes-table">
            <thead>
                <tr>
                    <th style="width:180px">Field</th>
                    <th>Old Value</th>
                    <th>New Value</th>
                </tr>
            </thead>
            <tbody>
                @foreach($log->changed_fields as $change)
                    <tr>
                        <td><span class="field-name">{{ $change['field'] }}</span></td>
                        <td>
                            @if($change['old'] !== null)
                                <span class="old-val">{{ is_array($change['old']) ? json_encode($change['old']) : $change['old'] }}</span>
                            @else
                                <span style="color:var(--text-400)">null</span>
                            @endif
                        </td>
                        <td>
                            @if($change['new'] !== null)
                                <span class="new-val">{{ is_array($change['new']) ? json_encode($change['new']) : $change['new'] }}</span>
                            @else
                                <span style="color:var(--text-400)">null</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif

{{-- New values (for created) --}}
@if($log->action === 'created' && $log->new_values)
    <div class="card" style="margin-bottom:16px">
        <h3 style="font-size:14px;font-weight:700;margin:0 0 12px">Created Values</h3>
        <pre class="json-block">{{ json_encode($log->new_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
    </div>
@endif

{{-- Raw diff for updated (old + new side by side) --}}
@if($log->action === 'updated')
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:16px">
        @if($log->old_values)
            <div class="card">
                <h3 style="font-size:13px;font-weight:700;margin:0 0 10px;color:var(--red)">Before</h3>
                <pre class="json-block">{{ json_encode($log->old_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
            </div>
        @endif
        @if($log->new_values)
            <div class="card">
                <h3 style="font-size:13px;font-weight:700;margin:0 0 10px;color:var(--green)">After</h3>
                <pre class="json-block">{{ json_encode($log->new_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
            </div>
        @endif
    </div>
@endif
@endsection
