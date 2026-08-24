@extends('layouts.app')
@section('title', 'Lead Integrations')

@push('styles')
<style>
.integ-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:18px;margin-top:24px}
.integ-card{background:var(--bg-surface);border:1.5px solid var(--border-default);border-radius:18px;padding:22px;display:flex;flex-direction:column;gap:14px;transition:box-shadow .2s,border-color .2s}
.integ-card:hover{box-shadow:0 4px 18px rgba(0,0,0,.1)}
.integ-card.locked{opacity:.55;pointer-events:none}
.integ-header{display:flex;align-items:center;gap:12px}
.integ-icon{width:44px;height:44px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:18px;color:#fff;flex-shrink:0}
.integ-name{font-size:15px;font-weight:700;color:var(--text-100)}
.integ-type{font-size:11px;color:var(--text-300);font-weight:500;text-transform:uppercase;letter-spacing:.07em}
.badge-on{display:inline-flex;align-items:center;gap:5px;background:rgba(220,252,231,0.14);color:#6FEC9D;font-size:11px;font-weight:700;padding:3px 9px;border-radius:20px}
.badge-on::before{content:'';width:6px;height:6px;border-radius:50%;background:#16a34a}
.badge-off{display:inline-flex;align-items:center;gap:5px;background:var(--bg-elevated);color:var(--text-300);font-size:11px;font-weight:600;padding:3px 9px;border-radius:20px}
.badge-locked{display:inline-flex;align-items:center;gap:5px;background:rgba(254,243,199,0.14);color:#F7B364;font-size:11px;font-weight:600;padding:3px 9px;border-radius:20px}
.integ-stats{display:flex;gap:16px;padding:10px 14px;background:var(--bg-elevated);border-radius:10px}
.integ-stat{display:flex;flex-direction:column;gap:2px}
.integ-stat-val{font-size:16px;font-weight:800;font-family:var(--mono);color:var(--text-100)}
.integ-stat-lbl{font-size:11px;color:var(--text-300);font-weight:500}
.integ-footer{display:flex;align-items:center;justify-content:space-between;gap:8px;margin-top:auto}
.btn-configure{padding:8px 18px;border-radius:9px;background:var(--accent);color:#fff;font-size:13px;font-weight:600;text-decoration:none;border:none;cursor:pointer;transition:opacity .15s}
.btn-configure:hover{opacity:.85}
.btn-configure-outline{padding:8px 18px;border-radius:9px;background:transparent;color:var(--accent);font-size:13px;font-weight:600;text-decoration:none;border:1.5px solid var(--accent);cursor:pointer;transition:all .15s}
.btn-configure-outline:hover{background:var(--accent-dim)}
</style>
@endpush

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Lead Integrations</h1>
        <p class="page-subtitle">Connect external platforms to automatically import leads into your CRM.</p>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif

<div class="integ-grid">
    @foreach($platforms as $item)
    @php
        $key         = $item['key'];
        $info        = $item['info'];
        $allowed     = $item['allowed'];
        $integration = $item['integration'];
        $isActive    = $integration?->is_active ?? false;
        $imported    = $integration?->leads_imported ?? 0;
        $lastSync    = $integration?->last_synced_at;
    @endphp

    <div class="integ-card {{ !$allowed ? 'locked' : '' }}">
        <div class="integ-header">
            <div class="integ-icon" style="background:{{ $info['color'] }}">
                <i class="{{ $info['icon'] }}"></i>
            </div>
            <div>
                <div class="integ-name">{{ $info['label'] }}</div>
                <div class="integ-type">{{ $info['type'] === 'webhook' ? 'Webhook (Auto)' : 'API Polling' }}</div>
            </div>
            <div class="ms-auto">
                @if(!$allowed)
                    <span class="badge-locked">🔒 Locked</span>
                @elseif($isActive)
                    <span class="badge-on">Active</span>
                @else
                    <span class="badge-off">Inactive</span>
                @endif
            </div>
        </div>

        @if($allowed && $integration)
        <div class="integ-stats">
            <div class="integ-stat">
                <div class="integ-stat-val">{{ number_format($imported) }}</div>
                <div class="integ-stat-lbl">Leads Imported</div>
            </div>
            <div class="integ-stat">
                <div class="integ-stat-val">{{ $lastSync ? $lastSync->diffForHumans() : '—' }}</div>
                <div class="integ-stat-lbl">Last Synced</div>
            </div>
        </div>
        @endif

        <div class="integ-footer">
            @if(!$allowed)
                <small class="text-muted">Contact support to enable this integration.</small>
            @else
                <a href="{{ route('tenant.lead-integrations.setup', $key) }}" class="btn-configure">
                    {{ $integration ? 'Configure' : 'Set Up' }}
                </a>
                @if($info['type'] === 'polling' && $isActive)
                    <form method="POST" action="{{ route('tenant.lead-integrations.sync', $key) }}">
                        @csrf
                        <button class="btn-configure-outline">Sync Now</button>
                    </form>
                @endif
            @endif
        </div>
    </div>
    @endforeach
</div>
@endsection
