@extends('layouts.app')
@section('title', 'Integration Access — ' . $tenant->name)

@push('styles')
<style>
.edit-wrap{max-width:600px}
.platform-row{display:flex;align-items:center;justify-content:space-between;padding:16px 0;border-bottom:1px solid var(--border-default)}
.platform-row:last-child{border-bottom:none}
.platform-info{display:flex;align-items:center;gap:12px}
.plat-icon{width:38px;height:38px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:16px;color:#fff;flex-shrink:0}
.plat-name{font-size:14px;font-weight:700;color:var(--text-100)}
.plat-type{font-size:11.5px;color:var(--text-300)}
.plat-stats{font-size:11.5px;color:var(--text-300);margin-top:2px}
.switch{position:relative;display:inline-block;width:46px;height:25px}
.switch input{opacity:0;width:0;height:0}
.slider{position:absolute;cursor:pointer;top:0;left:0;right:0;bottom:0;background:#cbd5e1;border-radius:25px;transition:.3s}
.slider:before{position:absolute;content:"";height:19px;width:19px;left:3px;bottom:3px;background:#fff;border-radius:50%;transition:.3s;box-shadow:0 1px 3px rgba(0,0,0,.2)}
input:checked+.slider{background:var(--accent)}
input:checked+.slider:before{transform:translateX(21px)}
</style>
@endpush

@section('content')
<div class="page-header">
    <div style="display:flex;align-items:center;gap:10px">
        <a href="{{ route('superadmin.lead-integrations.index') }}" class="btn-back">← Back</a>
        <h1 class="page-title">Integration Access: {{ $tenant->name }}</h1>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="edit-wrap">
    <div class="card" style="margin-bottom:16px;padding:14px 18px">
        <div style="font-size:13px;color:var(--text-200)">
            <strong>Tenant:</strong> {{ $tenant->name }}
            &nbsp;|&nbsp;
            <strong>Plan:</strong> {{ $tenant->subscription?->plan?->name ?? 'No active plan' }}
            &nbsp;|&nbsp;
            <strong>Email:</strong> {{ $tenant->email }}
        </div>
    </div>

    <form method="POST" action="{{ route('superadmin.lead-integrations.update', $tenant->id) }}">
        @csrf
        @method('PUT')

        <div class="card" style="padding:8px 22px">
            @foreach($platforms as $key => $info)
            @php
                $isAllowed = (bool) ($allowed[$key] ?? false);
                $stat      = $stats->get($key);
            @endphp

            <div class="platform-row">
                <div class="platform-info">
                    <div class="plat-icon" style="background:{{ $info['color'] }}">
                        <i class="{{ $info['icon'] }}"></i>
                    </div>
                    <div>
                        <div class="plat-name">{{ $info['label'] }}</div>
                        <div class="plat-type">{{ $info['type'] === 'webhook' ? 'Webhook (auto push)' : 'API Polling (every 30 min)' }}</div>
                        @if($stat)
                        <div class="plat-stats">
                            Configured: {{ $stat->is_active ? 'Active' : 'Inactive' }}
                            &bull; {{ number_format($stat->leads_imported) }} leads imported
                            @if($stat->last_synced_at)
                                &bull; Last sync {{ $stat->last_synced_at->diffForHumans() }}
                            @endif
                        </div>
                        @endif
                    </div>
                </div>

                <label class="switch">
                    <input type="checkbox"
                           name="integrations[{{ $key }}]"
                           value="1"
                           {{ $isAllowed ? 'checked' : '' }}>
                    <span class="slider"></span>
                </label>
            </div>
            @endforeach
        </div>

        <div style="display:flex;gap:10px;margin-top:16px">
            <button type="submit" class="btn btn-primary">Save Access Settings</button>
            <a href="{{ route('superadmin.lead-integrations.index') }}" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </form>
</div>
@endsection
