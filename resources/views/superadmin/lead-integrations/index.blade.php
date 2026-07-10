@extends('layouts.app')
@section('title', 'Lead Integrations — Tenant Access')

@push('styles')
<style>
.tenant-table{width:100%;border-collapse:collapse}
.tenant-table th{text-align:left;padding:10px 14px;font-size:11.5px;color:var(--text-300);text-transform:uppercase;letter-spacing:.07em;border-bottom:1.5px solid var(--border-default);white-space:nowrap}
.tenant-table td{padding:12px 14px;border-bottom:1px solid var(--border-default);vertical-align:middle}
.tenant-table tr:last-child td{border-bottom:none}
.tenant-table tr:hover td{background:var(--bg-elevated)}
.platform-chips{display:flex;flex-wrap:wrap;gap:5px}
.chip{display:inline-flex;align-items:center;gap:4px;padding:3px 9px;border-radius:20px;font-size:11px;font-weight:600}
.chip-on{background:#dcfce7;color:#15803d}
.chip-off{background:var(--bg-elevated);color:var(--text-300)}
.btn-edit{padding:6px 14px;border-radius:8px;background:var(--accent);color:#fff;font-size:12.5px;font-weight:600;text-decoration:none;white-space:nowrap;transition:opacity .15s}
.btn-edit:hover{opacity:.85;color:#fff}
.stat-badge{display:inline-flex;align-items:center;gap:4px;padding:3px 8px;border-radius:8px;font-size:11.5px;font-weight:700;background:var(--bg-elevated);color:var(--text-200)}
</style>
@endpush

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Lead Integrations</h1>
        <p class="page-subtitle">Control which tenants can connect external lead sources (Meta Ads, IndiaMART, JustDial, etc.)</p>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="card">
    <table class="tenant-table data-table">
        <thead>
            <tr>
                <th>Tenant</th>
                <th>Plan</th>
                <th>Enabled Integrations</th>
                <th>Leads Imported</th>
                <th>Active</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse($tenants as $row)
            @php
                $tenant  = $row['tenant'];
                $allowed = $row['allowed'];
            @endphp
            <tr>
                <td data-label="Tenant">
                    <div style="font-weight:600;color:var(--text-100)">{{ $tenant->name }}</div>
                    <div style="font-size:11.5px;color:var(--text-300)">{{ $tenant->subdomain }}.{{ config('app.base_domain', 'saas-crm.test') }}</div>
                </td>
                <td data-label="Plan">
                    <span style="font-size:12.5px;color:var(--text-200)">
                        {{ $tenant->subscription?->plan?->name ?? '—' }}
                    </span>
                </td>
                <td data-label="Enabled Integrations">
                    <div class="platform-chips">
                        @foreach(array_keys($platforms) as $key)
                        <span class="chip {{ ($allowed[$key] ?? false) ? 'chip-on' : 'chip-off' }}">
                            {{ ($allowed[$key] ?? false) ? '✓' : '✗' }} {{ $platforms[$key]['label'] }}
                        </span>
                        @endforeach
                    </div>
                </td>
                <td data-label="Leads Imported">
                    <span class="stat-badge">{{ number_format($row['total_leads']) }} leads</span>
                </td>
                <td data-label="Active">
                    <span class="stat-badge">{{ $row['active_count'] }} active</span>
                </td>
                <td>
                    <a href="{{ route('superadmin.lead-integrations.edit', $tenant->id) }}" class="btn-edit">
                        Manage Access
                    </a>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" style="text-align:center;color:var(--text-300);padding:40px">
                    No active tenants found.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
