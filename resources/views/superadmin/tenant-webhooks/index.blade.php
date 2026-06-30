@extends('layouts.app')
@section('title', 'Webhooks — ' . $tenant->name)

@push('styles')
<style>
.wh-event-badge {
    display: inline-flex; align-items: center; gap: 5px;
    font-size: 11.5px; font-weight: 600; padding: 3px 9px; border-radius: 20px;
}
.wh-event-badge.lead    { background: var(--accent-dim); color: var(--accent); }
.wh-event-badge.deal    { background: var(--green-dim);  color: var(--green); }
.wh-event-badge.invoice { background: var(--amber-dim);  color: var(--amber); }

.wh-status { display: inline-flex; align-items: center; gap: 5px; font-size: 12px; font-weight: 600; }
.wh-status .dot { width: 7px; height: 7px; border-radius: 50%; }
.wh-status.active   .dot { background: var(--green); }
.wh-status.inactive .dot { background: var(--text-400); }

.url-cell {
    font-family: 'DM Mono', monospace; font-size: 12px;
    color: var(--text-300); max-width: 320px;
    overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
}

.edit-url-form { display: none; gap: 6px; align-items: center; }
.edit-url-form.open { display: flex; }
.edit-url-form input { flex: 1; }
</style>
@endpush

@php $webhookToken = $tenant->getWebhookToken(); @endphp

@section('content')

<div class="page-head">
    <div>
        <div class="page-title">Webhooks — {{ $tenant->name }}</div>
        <div class="page-sub">Jab CRM events ho tab n8n ko automatically notify karo</div>
    </div>
    <a href="{{ route('superadmin.tenants.show', $tenant) }}" class="btn btn-secondary">
        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="width:15px;height:15px;">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
        </svg>
        Back to Tenant
    </a>
</div>

{{-- Security Token --}}
<div class="card" style="margin-bottom:20px;border-color:var(--accent-dim);">
    <div class="card-header" style="background:var(--accent-dim);">
        <div>
            <div class="card-title" style="color:var(--accent);">Webhook Security Token</div>
            <div class="card-subtitle">n8n is token se verify karega ki request CRM se aayi hai</div>
        </div>
        <form method="POST" action="{{ route('superadmin.tenant-webhooks.regenerate-token', $tenant) }}">
            @csrf
            <button type="button" class="btn btn-secondary btn-sm"
                onclick="confirmAction({ title: 'Regenerate Token?', message: 'Old token invalid ho jaayega — n8n mein naaya token update karna hoga.', ok: 'Regenerate', danger: true, onConfirm: () => this.closest(\'form\').submit() })">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="width:13px;height:13px;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99"/>
                </svg>
                Regenerate
            </button>
        </form>
    </div>
    <div class="card-body">
        <div style="display:flex;align-items:center;gap:10px;">
            <div style="flex:1;display:flex;align-items:center;gap:0;background:var(--bg-input);border:1px solid var(--border-subtle);border-radius:var(--r-md);overflow:hidden;">
                <input type="text" id="webhookTokenField"
                       value="{{ $webhookToken }}"
                       readonly
                       style="flex:1;padding:10px 14px;background:transparent;border:none;outline:none;font-family:'DM Mono',monospace;font-size:13px;color:var(--text-100);">
                <button type="button" onclick="copyToken()"
                        style="padding:0 14px;height:42px;background:var(--bg-elevated);border:none;border-left:1px solid var(--border-subtle);color:var(--text-300);cursor:pointer;font-size:12px;font-weight:600;white-space:nowrap;"
                        id="copyTokenBtn">
                    Copy
                </button>
            </div>
        </div>
        <div style="margin-top:12px;padding:12px 14px;background:var(--bg-elevated);border-radius:var(--r-md);border:1px solid var(--border-subtle);">
            <div style="font-size:12px;font-weight:700;color:var(--text-300);margin-bottom:8px;text-transform:uppercase;letter-spacing:.05em;">n8n mein kaise use karein</div>
            <ol style="font-size:12.5px;color:var(--text-300);line-height:1.9;padding-left:18px;margin:0;">
                <li>n8n workflow mein Webhook node ke baad <strong style="color:var(--text-100);">HTTP Request node</strong> add karo</li>
                <li>Method: <code style="background:var(--bg-input);padding:1px 5px;border-radius:3px;">POST</code> &nbsp; URL: <code style="background:var(--bg-input);padding:1px 5px;border-radius:3px;">{{ config('app.url') }}/api/v1/webhook/validate</code></li>
                <li>Body: <code style="background:var(--bg-input);padding:1px 5px;border-radius:3px;">&#123; "token": "&#123;&#123; $json.crm_token &#125;&#125;" &#125;</code></li>
                <li>Response check karo: <code style="background:var(--bg-input);padding:1px 5px;border-radius:3px;">&#123;&#123; $json.valid &#125;&#125; === true</code> ho tab aage flow chale</li>
            </ol>
        </div>
    </div>
</div>

{{-- Add Webhook --}}
<div class="card" style="margin-bottom:20px;">
    <div class="card-header">
        <div class="card-title">Add Webhook</div>
    </div>
    <div class="card-body">
        <form method="POST" action="{{ route('superadmin.tenant-webhooks.store', $tenant) }}"
              style="display:grid;grid-template-columns:1fr 2fr auto;gap:12px;align-items:end;">
            @csrf

            <div class="form-group" style="margin:0;">
                <label class="form-label">CRM Event <span style="color:var(--red);">*</span></label>
                <select name="event" class="form-control @error('event') is-invalid @enderror" required>
                    <option value="">— Select event —</option>
                    @foreach($events as $key => $label)
                        <option value="{{ $key }}" {{ old('event') === $key ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
                @error('event')<div class="form-error">{{ $message }}</div>@enderror
            </div>

            <div class="form-group" style="margin:0;">
                <label class="form-label">n8n Webhook URL <span style="color:var(--red);">*</span></label>
                <input type="url" name="webhook_url" class="form-control @error('webhook_url') is-invalid @enderror"
                       placeholder="https://n8n.yourdomain.com/webhook/xxxxxxxx"
                       value="{{ old('webhook_url') }}" required>
                @error('webhook_url')<div class="form-error">{{ $message }}</div>@enderror
            </div>

            <button type="submit" class="btn btn-primary" style="white-space:nowrap;">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="width:15px;height:15px;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                </svg>
                Add Webhook
            </button>
        </form>
    </div>
</div>

{{-- Webhooks List --}}
<div class="card">
    <div class="card-header">
        <div>
            <div class="card-title">Configured Webhooks</div>
            <div class="card-subtitle">{{ $webhooks->count() }} total</div>
        </div>
    </div>

    @if($webhooks->isNotEmpty())
    <div style="overflow-x:auto;">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Event</th>
                    <th>Webhook URL</th>
                    <th>Status</th>
                    <th>Added</th>
                    <th style="text-align:right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($webhooks as $wh)
                @php
                    $eventPrefix = explode('.', $wh->event)[0];
                @endphp
                <tr id="wh-row-{{ $wh->id }}">
                    <td>
                        <span class="wh-event-badge {{ $eventPrefix }}">
                            {{ $events[$wh->event] ?? $wh->event }}
                        </span>
                    </td>
                    <td>
                        <div class="url-cell" id="url-text-{{ $wh->id }}" title="{{ $wh->webhook_url }}">
                            {{ $wh->webhook_url }}
                        </div>
                        <form method="POST" action="{{ route('superadmin.tenant-webhooks.update', [$tenant, $wh]) }}"
                              class="edit-url-form" id="edit-form-{{ $wh->id }}">
                            @csrf @method('PATCH')
                            <input type="url" name="webhook_url" class="form-control"
                                   value="{{ $wh->webhook_url }}" required style="font-size:12px;font-family:monospace;">
                            <button type="submit" class="btn btn-primary btn-sm">Save</button>
                            <button type="button" class="btn btn-secondary btn-sm"
                                onclick="toggleEdit({{ $wh->id }})">Cancel</button>
                        </form>
                    </td>
                    <td>
                        <span class="wh-status {{ $wh->is_active ? 'active' : 'inactive' }}">
                            <span class="dot"></span>
                            {{ $wh->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </td>
                    <td style="font-size:12px;color:var(--text-300);">
                        {{ $wh->created_at->format('d M Y') }}
                    </td>
                    <td>
                        <div style="display:flex;gap:6px;justify-content:flex-end;align-items:center;">
                            {{-- Test --}}
                            <button type="button" class="btn btn-secondary btn-sm"
                                onclick="testWebhook({{ $wh->id }}, '{{ route('superadmin.tenant-webhooks.test', [$tenant, $wh]) }}')"
                                data-tip="Send test ping to n8n">
                                Test
                            </button>

                            {{-- Edit --}}
                            <button type="button" class="btn btn-secondary btn-sm"
                                onclick="toggleEdit({{ $wh->id }})"
                                data-tip="Edit URL">
                                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="width:13px;height:13px;">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z"/>
                                </svg>
                            </button>

                            {{-- Toggle --}}
                            <form method="POST" action="{{ route('superadmin.tenant-webhooks.toggle', [$tenant, $wh]) }}">
                                @csrf
                                <button type="submit" class="btn btn-secondary btn-sm"
                                    data-tip="{{ $wh->is_active ? 'Deactivate' : 'Activate' }}">
                                    @if($wh->is_active)
                                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="width:13px;height:13px;color:var(--amber);">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                                        </svg>
                                    @else
                                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="width:13px;height:13px;color:var(--green);">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                    @endif
                                </button>
                            </form>

                            {{-- Delete --}}
                            <button type="button" class="btn btn-danger btn-sm"
                                onclick="confirmAction({ title: 'Delete Webhook?', message: 'This will stop CRM events from reaching this n8n URL.', ok: 'Delete', onConfirm: () => document.getElementById('del-{{ $wh->id }}').submit() })"
                                data-tip="Delete">
                                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="width:13px;height:13px;">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/>
                                </svg>
                            </button>
                            <form id="del-{{ $wh->id }}" method="POST"
                                  action="{{ route('superadmin.tenant-webhooks.destroy', [$tenant, $wh]) }}" style="display:none;">
                                @csrf @method('DELETE')
                            </form>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @else
    <div class="card-body" style="text-align:center;padding:48px 24px;color:var(--text-300);">
        <svg fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"
             style="width:40px;height:40px;margin:0 auto 12px;display:block;opacity:.35;">
            <path stroke-linecap="round" stroke-linejoin="round"
                d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m13.35-.622l1.757-1.757a4.5 4.5 0 00-6.364-6.364l-4.5 4.5a4.5 4.5 0 001.242 7.244"/>
        </svg>
        <p style="font-size:14px;">Koi webhook configured nahi hai.</p>
        <p style="font-size:12.5px;margin-top:4px;">Upar form se pehla webhook add karo.</p>
    </div>
    @endif
</div>

{{-- Event Reference --}}
<div class="card" style="margin-top:20px;">
    <div class="card-header">
        <div class="card-title">n8n ko kya data milta hai?</div>
    </div>
    <div class="card-body" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:16px;">
        @foreach([
            ['lead.created', 'Lead Created', '{"event":"lead.created","tenant_id":5,"data":{"id":123,"name":"Rahul Sharma","phone":"9876543210","source":"IndiaMART","status":"new"}}'],
            ['lead.status_changed', 'Lead Status Changed', '{"event":"lead.status_changed","tenant_id":5,"data":{"id":123,"name":"Rahul Sharma","old_status":"new","new_status":"contacted"}}'],
            ['deal.won', 'Deal Won', '{"event":"deal.won","tenant_id":5,"data":{"id":45,"title":"Website Project","value":50000,"contact_name":"Priya Singh"}}'],
            ['deal.lost', 'Deal Lost', '{"event":"deal.lost","tenant_id":5,"data":{"id":45,"title":"Website Project","lost_reason":"Budget nahi tha"}}'],
            ['invoice.created', 'Invoice Created', '{"event":"invoice.created","tenant_id":5,"data":{"id":78,"number":"INV-001","total":25000,"due_date":"2026-07-15","contact_name":"Ramesh Ltd"}}'],
            ['invoice.paid', 'Invoice Paid', '{"event":"invoice.paid","tenant_id":5,"data":{"id":78,"number":"INV-001","total":25000,"paid_at":"2026-06-30T10:30:00Z"}}'],
        ] as [$ev, $label, $sample])
        @php $prefix = explode('.', $ev)[0]; @endphp
        <div style="background:var(--bg-elevated);border:1px solid var(--border-subtle);border-radius:var(--r-lg);padding:14px;">
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:10px;">
                <span class="wh-event-badge {{ $prefix }}" style="font-size:11px;">{{ $label }}</span>
            </div>
            <pre style="font-size:10.5px;font-family:'DM Mono',monospace;color:var(--text-300);white-space:pre-wrap;word-break:break-all;margin:0;line-height:1.6;">{{ $sample }}</pre>
        </div>
        @endforeach
    </div>
</div>

@endsection

@push('scripts')
<script>
function copyToken() {
    var field = document.getElementById('webhookTokenField');
    var btn   = document.getElementById('copyTokenBtn');
    navigator.clipboard.writeText(field.value).then(function () {
        btn.textContent = 'Copied!';
        btn.style.color = 'var(--green)';
        setTimeout(function () { btn.textContent = 'Copy'; btn.style.color = ''; }, 2000);
    });
}

function toggleEdit(id) {
    var textEl = document.getElementById('url-text-' + id);
    var formEl = document.getElementById('edit-form-' + id);
    var isOpen = formEl.classList.contains('open');
    textEl.style.display = isOpen ? '' : 'none';
    formEl.classList.toggle('open', !isOpen);
}

function testWebhook(id, url) {
    var btn = event.target.closest('button');
    var orig = btn.textContent;
    btn.disabled = true;
    btn.textContent = 'Testing...';

    fetch(url, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': window.CrmCsrf, 'Accept': 'application/json' },
    })
    .then(function(r) { return r.json(); })
    .then(function(json) {
        if (json.success) {
            showToast('Test ping sent! n8n status: ' + json.status, 'success');
        } else {
            showToast('Failed: ' + (json.message || 'Unknown error'), 'error');
        }
    })
    .catch(function() { showToast('Network error', 'error'); })
    .finally(function() { btn.disabled = false; btn.textContent = orig; });
}
</script>
@endpush
