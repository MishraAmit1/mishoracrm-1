@extends('layouts.app')
@section('title', 'Instagram Settings')

@push('styles')
<style>
.settings-grid { display:grid; grid-template-columns:1fr 1fr; gap:20px; }
@media(max-width:700px){ .settings-grid { grid-template-columns:1fr; } }
.webhook-box { background:var(--bg-subtle); border:1px solid var(--border-subtle); border-radius:var(--r-md); padding:16px; font-family:var(--mono); font-size:12px; word-break:break-all; color:var(--text-200); }
.copy-btn { cursor:pointer; background:none; border:none; color:var(--text-300); padding:4px; }
.copy-btn:hover { color:var(--accent); }
.conn-strip { display:flex; align-items:center; justify-content:space-between; padding:14px 16px; border-radius:var(--r-md); border:1px solid var(--border-default); background:var(--bg-surface); margin-bottom:20px; }
</style>
@endpush

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Instagram Settings</h1>
        <p class="page-sub">Connect your Meta / Instagram Business account</p>
    </div>
    <a href="{{ route('tenant.instagram.index') }}" class="btn btn-ghost">
        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="width:16px;height:16px;">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
        </svg>
        Back
    </a>
</div>

@if(session('success'))
    <div class="alert alert-success" style="margin-bottom:20px;">{{ session('success') }}</div>
@endif

{{-- Connection status --}}
<div class="conn-strip">
    <div style="display:flex;align-items:center;gap:10px;">
        @if($settings->is_connected)
            <span style="width:10px;height:10px;border-radius:50%;background:#22c55e;"></span>
            <span style="font-weight:600;color:var(--text-100);">Connected</span>
        @else
            <span style="width:10px;height:10px;border-radius:50%;background:#ef4444;"></span>
            <span style="font-weight:600;color:var(--text-100);">Not Connected</span>
        @endif
        @if($settings->instagram_account_id)
            <span style="color:var(--text-300);font-size:13px;">• Account ID: {{ $settings->instagram_account_id }}</span>
        @endif
    </div>
    <button onclick="testConnection()" class="btn btn-sm" id="testBtn">Test Connection</button>
</div>

<form method="POST" action="{{ route('tenant.instagram.settings.save') }}">
@csrf

<div class="settings-grid">
    {{-- Meta App credentials --}}
    <div class="card" style="grid-column:1/-1;">
        <div class="card-header"><h3 class="card-title">Meta App Credentials</h3></div>
        <div class="card-body">
            <div class="form-grid" style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                <div class="form-group">
                    <label class="form-label">App ID</label>
                    <input type="text" name="app_id" class="form-input" value="{{ $settings->app_id }}" placeholder="Meta App ID">
                </div>
                <div class="form-group">
                    <label class="form-label">App Secret</label>
                    <input type="password" name="app_secret" class="form-input" placeholder="Leave blank to keep current">
                </div>
                <div class="form-group">
                    <label class="form-label">Page ID</label>
                    <input type="text" name="page_id" class="form-input" value="{{ $settings->page_id }}" placeholder="Facebook Page ID">
                    <span class="form-hint">The Facebook Page connected to your Instagram account</span>
                </div>
                <div class="form-group">
                    <label class="form-label">Instagram Account ID</label>
                    <input type="text" name="instagram_account_id" class="form-input" value="{{ $settings->instagram_account_id }}" placeholder="Instagram Business Account ID">
                </div>
                <div class="form-group" style="grid-column:1/-1;">
                    <label class="form-label">Access Token</label>
                    <input type="password" name="access_token" class="form-input" placeholder="Page Access Token (long-lived)">
                    <span class="form-hint">Never-expiring Page Access Token from Meta Business Suite</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Webhook info --}}
    <div class="card">
        <div class="card-header"><h3 class="card-title">Webhook URL</h3></div>
        <div class="card-body">
            <p style="font-size:13px;color:var(--text-300);margin-bottom:10px;">Configure this URL in your Meta App → Webhooks → Instagram</p>
            <div style="display:flex;align-items:center;gap:8px;">
                <div class="webhook-box" style="flex:1;" id="webhookUrl">{{ url('/webhook/instagram') }}</div>
                <button type="button" class="copy-btn" onclick="copyText('webhookUrl')" title="Copy">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="width:16px;height:16px;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 01-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 011.5.124m7.5 10.376h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-4.46-3.243-8.161-7.5-8.876a9.06 9.06 0 00-1.5-.124H9.375c-.621 0-1.125.504-1.125 1.125v3.5m7.5 10.375H9.375a1.125 1.125 0 01-1.125-1.125v-9.25m12 6.625v-1.875a3.375 3.375 0 00-3.375-3.375h-1.5a1.125 1.125 0 01-1.125-1.125v-1.5a3.375 3.375 0 00-3.375-3.375H9.75"/>
                    </svg>
                </button>
            </div>

            <p style="font-size:13px;color:var(--text-300);margin:16px 0 8px;">Verify Token (use in Meta App settings)</p>
            <div style="display:flex;align-items:center;gap:8px;">
                <div class="webhook-box" style="flex:1;" id="verifyToken">{{ $settings->webhook_verify_token ?? 'Will be generated on save' }}</div>
                <button type="button" class="copy-btn" onclick="copyText('verifyToken')" title="Copy">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="width:16px;height:16px;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 01-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 011.5.124m7.5 10.376h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-4.46-3.243-8.161-7.5-8.876a9.06 9.06 0 00-1.5-.124H9.375c-.621 0-1.125.504-1.125 1.125v3.5m7.5 10.375H9.375a1.125 1.125 0 01-1.125-1.125v-9.25m12 6.625v-1.875a3.375 3.375 0 00-3.375-3.375h-1.5a1.125 1.125 0 01-1.125-1.125v-1.5a3.375 3.375 0 00-3.375-3.375H9.75"/>
                    </svg>
                </button>
            </div>
            <p style="font-size:12px;color:var(--text-300);margin-top:8px;">Subscribe to: <strong>messages</strong>, <strong>comments</strong>, <strong>mention</strong></p>
        </div>
    </div>

    {{-- n8n --}}
    <div class="card">
        <div class="card-header"><h3 class="card-title">n8n Integration</h3></div>
        <div class="card-body">
            <p style="font-size:13px;color:var(--text-300);margin-bottom:14px;">Set a global n8n webhook URL to receive ALL Instagram events from this account.</p>
            <div class="form-group">
                <label class="form-label">n8n Webhook URL <span style="color:var(--text-300);font-weight:400;">(optional)</span></label>
                <input type="url" name="n8n_webhook_url" class="form-input" value="{{ $settings->n8n_webhook_url }}" placeholder="https://your-n8n.com/webhook/xxxx">
                <span class="form-hint">Every Instagram event (comment/DM) will POST to this URL with full payload</span>
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
        // brief visual feedback
        const el = document.getElementById(id);
        el.style.background = 'var(--bg-success, #dcfce7)';
        setTimeout(() => el.style.background = '', 1200);
    });
}

function testConnection() {
    const btn = document.getElementById('testBtn');
    btn.textContent = 'Testing...';
    btn.disabled = true;

    fetch('{{ route("tenant.instagram.test-connection") }}', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert('Connected! Account: @' + (data.account?.username ?? ''));
        } else {
            alert('Failed: ' + data.message);
        }
    })
    .catch(() => alert('Request failed. Check console.'))
    .finally(() => { btn.textContent = 'Test Connection'; btn.disabled = false; });
}
</script>
@endpush
