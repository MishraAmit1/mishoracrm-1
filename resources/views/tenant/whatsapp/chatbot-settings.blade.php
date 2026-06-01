@extends('layouts.app')
@section('title', 'WhatsApp API Settings')

@push('styles')
<style>
.settings-grid { display:grid; grid-template-columns:1fr 1fr; gap:20px; }
@media(max-width:700px){ .settings-grid { grid-template-columns:1fr; } }
.webhook-box { background:var(--bg-subtle); border:1px solid var(--border-subtle); border-radius:var(--r-md); padding:12px 14px; font-family:var(--mono); font-size:12px; word-break:break-all; color:var(--text-200); }
.conn-strip { display:flex; align-items:center; justify-content:space-between; padding:14px 16px; border-radius:var(--r-md); border:1px solid var(--border-default); background:var(--bg-surface); margin-bottom:20px; }
.copy-btn { cursor:pointer; background:none; border:none; color:var(--text-300); padding:4px; }
.copy-btn:hover { color:var(--accent); }
</style>
@endpush

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">WhatsApp Business API Settings</h1>
        <p class="page-sub">Connect Meta WhatsApp Cloud API for chatbot &amp; automation</p>
    </div>
    <a href="{{ route('tenant.whatsapp.index') }}" class="btn btn-ghost">Back to WhatsApp</a>
</div>

@if(session('success'))
    <div class="alert alert-success" style="margin-bottom:20px;">{{ session('success') }}</div>
@endif

{{-- Connection status --}}
<div class="conn-strip">
    <div style="display:flex;align-items:center;gap:10px;">
        @if($settings->is_connected)
            <span style="width:10px;height:10px;border-radius:50%;background:#22c55e;"></span>
            <span style="font-weight:600;">Connected</span>
            @if($settings->phone_number_id)
                <span style="color:var(--text-300);font-size:13px;">• Phone Number ID: {{ $settings->phone_number_id }}</span>
            @endif
        @else
            <span style="width:10px;height:10px;border-radius:50%;background:#ef4444;"></span>
            <span style="font-weight:600;">Not Connected</span>
        @endif
    </div>
    <button onclick="testConnection()" class="btn btn-sm" id="testBtn">Test Connection</button>
</div>

<form method="POST" action="{{ route('tenant.whatsapp.api-settings.save') }}">
@csrf

<div class="settings-grid">
    {{-- Meta credentials --}}
    <div class="card" style="grid-column:1/-1;">
        <div class="card-header"><h3 class="card-title">WhatsApp Cloud API Credentials</h3></div>
        <div class="card-body">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                <div class="form-group">
                    <label class="form-label">Phone Number ID</label>
                    <input type="text" name="phone_number_id" class="form-input" value="{{ $settings->phone_number_id }}" placeholder="From Meta Business Manager">
                </div>
                <div class="form-group">
                    <label class="form-label">WhatsApp Business Account ID (WABA ID)</label>
                    <input type="text" name="waba_id" class="form-input" value="{{ $settings->waba_id }}" placeholder="Business Account ID">
                </div>
                <div class="form-group" style="grid-column:1/-1;">
                    <label class="form-label">Access Token (Permanent)</label>
                    <input type="password" name="access_token" class="form-input" placeholder="Leave blank to keep current token">
                    <span class="form-hint">System User Access Token from Meta Business Manager (never expires)</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Webhook --}}
    <div class="card">
        <div class="card-header"><h3 class="card-title">Webhook Configuration</h3></div>
        <div class="card-body">
            <p style="font-size:13px;color:var(--text-300);margin-bottom:10px;">Configure in Meta App → WhatsApp → Configuration → Webhook</p>
            <label class="form-label" style="margin-bottom:6px;">Callback URL</label>
            <div style="display:flex;gap:8px;margin-bottom:14px;">
                <div class="webhook-box" style="flex:1;" id="webhookUrl">{{ url('/webhook/whatsapp') }}</div>
                <button type="button" class="copy-btn" onclick="copyText('webhookUrl')">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="width:16px;height:16px;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 01-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 011.5.124m7.5 10.376h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-4.46-3.243-8.161-7.5-8.876a9.06 9.06 0 00-1.5-.124H9.375c-.621 0-1.125.504-1.125 1.125v3.5m7.5 10.375H9.375a1.125 1.125 0 01-1.125-1.125v-9.25m12 6.625v-1.875a3.375 3.375 0 00-3.375-3.375h-1.5a1.125 1.125 0 01-1.125-1.125v-1.5a3.375 3.375 0 00-3.375-3.375H9.75"/>
                    </svg>
                </button>
            </div>
            <label class="form-label" style="margin-bottom:6px;">Verify Token</label>
            <div style="display:flex;gap:8px;">
                <div class="webhook-box" style="flex:1;" id="verifyToken">{{ $settings->webhook_verify_token ?? 'Will be generated on save' }}</div>
                <button type="button" class="copy-btn" onclick="copyText('verifyToken')">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="width:16px;height:16px;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 01-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 011.5.124m7.5 10.376h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-4.46-3.243-8.161-7.5-8.876a9.06 9.06 0 00-1.5-.124H9.375c-.621 0-1.125.504-1.125 1.125v3.5m7.5 10.375H9.375a1.125 1.125 0 01-1.125-1.125v-9.25m12 6.625v-1.875a3.375 3.375 0 00-3.375-3.375h-1.5a1.125 1.125 0 01-1.125-1.125v-1.5a3.375 3.375 0 00-3.375-3.375H9.75"/>
                    </svg>
                </button>
            </div>
            <p style="font-size:12px;color:var(--text-300);margin-top:8px;">Subscribe to field: <strong>messages</strong></p>
        </div>
    </div>

    {{-- n8n + Chatbot --}}
    <div class="card">
        <div class="card-header"><h3 class="card-title">Chatbot &amp; n8n</h3></div>
        <div class="card-body">
            <div class="form-group">
                <label style="display:flex;align-items:center;gap:10px;cursor:pointer;">
                    <input type="checkbox" name="chatbot_enabled" value="1" {{ $settings->chatbot_enabled ? 'checked' : '' }}
                        style="width:18px;height:18px;">
                    <div>
                        <div style="font-weight:600;font-size:14px;">Enable Chatbot</div>
                        <div style="font-size:12px;color:var(--text-300);">Auto-reply to incoming WhatsApp messages using keyword flows</div>
                    </div>
                </label>
            </div>
            <div class="form-group" style="margin-top:16px;">
                <label class="form-label">n8n Webhook URL <span style="font-weight:400;color:var(--text-300);">(optional)</span></label>
                <input type="url" name="n8n_webhook_url" class="form-input" value="{{ $settings->n8n_webhook_url }}" placeholder="https://your-n8n.com/webhook/xxxx">
                <span class="form-hint">All incoming WhatsApp messages will be forwarded to this n8n webhook</span>
            </div>
        </div>
    </div>
</div>

<div style="margin-top:20px;display:flex;justify-content:flex-end;">
    <button type="submit" class="btn btn-primary">Save Settings</button>
</div>
</form>
@endsection

@push('scripts')
<script>
function copyText(id) {
    const text = document.getElementById(id).textContent.trim();
    navigator.clipboard.writeText(text).then(() => {
        const el = document.getElementById(id);
        el.style.background = '#dcfce7';
        setTimeout(() => el.style.background = '', 1200);
    });
}
function testConnection() {
    const btn = document.getElementById('testBtn');
    btn.textContent = 'Testing...';
    btn.disabled = true;
    fetch('{{ route("tenant.whatsapp.api-settings.test") }}', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert('Connected! ' + (data.account?.display_phone_number ?? ''));
        } else {
            alert('Failed: ' + data.message);
        }
    })
    .catch(() => alert('Request failed.'))
    .finally(() => { btn.textContent = 'Test Connection'; btn.disabled = false; });
}
</script>
@endpush
