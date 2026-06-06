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

/* QR Connect */
.qr-setup-card { border:2px dashed var(--border-default); border-radius:var(--r-lg); padding:28px 24px; text-align:center; background:var(--bg-subtle); margin-bottom:24px; }
.qr-setup-card.connected { border-color:#22c55e; background:#f0fdf4; }
.qr-wrap { display:inline-block; background:#fff; border-radius:12px; padding:16px; box-shadow:0 2px 12px rgba(0,0,0,.08); margin:16px 0; }
.qr-steps { display:flex; gap:16px; justify-content:center; flex-wrap:wrap; margin:12px 0 0; }
.qr-step { display:flex; align-items:flex-start; gap:8px; text-align:left; max-width:160px; }
.qr-step-num { width:22px; height:22px; border-radius:50%; background:#25d366; color:#fff; font-size:11px; font-weight:700; display:flex; align-items:center; justify-content:center; flex-shrink:0; margin-top:1px; }
.qr-step-text { font-size:12px; color:var(--text-300); line-height:1.4; }
.qr-success-icon { width:56px; height:56px; border-radius:50%; background:#dcfce7; display:flex; align-items:center; justify-content:center; margin:0 auto 12px; }
@keyframes wapulse { 0%,100%{opacity:1} 50%{opacity:.3} }
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

{{-- ── QR Quick Connect ──────────────────────────────────────────── --}}
<div class="qr-setup-card" id="qrSetupCard">
    @if($settings->is_connected)
        <div class="qr-success-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2.5" style="width:28px;height:28px;">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
            </svg>
        </div>
        <div style="font-weight:700;font-size:16px;color:#16a34a;">WhatsApp Connected</div>
        <div style="font-size:13px;color:var(--text-300);margin-top:4px;">
            Phone Number ID: {{ $settings->phone_number_id }} &nbsp;|&nbsp; WABA ID: {{ $settings->waba_id }}
        </div>
        <button type="button" class="btn btn-sm" style="margin-top:14px;" onclick="startQrFlow()">Reconnect / Change Number</button>
    @else
        <div style="margin-bottom:8px;">
            <svg viewBox="0 0 24 24" fill="#25d366" style="width:40px;height:40px;margin:0 auto;display:block;">
                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/>
                <path d="M12.004 2C6.477 2 2 6.477 2 12.004c0 1.773.465 3.48 1.348 4.985L2 22l5.13-1.34A9.953 9.953 0 0012.004 22C17.527 22 22 17.523 22 12c0-5.522-4.473-10-9.996-10z" fill-rule="evenodd" clip-rule="evenodd"/>
            </svg>
        </div>
        <div style="font-weight:700;font-size:17px;color:var(--text-100);">Quick Connect with QR Code</div>
        <div style="font-size:13px;color:var(--text-300);margin-top:6px;max-width:420px;margin-left:auto;margin-right:auto;">
            Scan this QR code with your phone to connect your WhatsApp Business account automatically — no manual token copying needed.
        </div>

        <div id="qrArea" style="display:none;margin-top:16px;">
            <div class="qr-wrap"><img id="qrImg" src="" alt="QR Code" style="width:200px;height:200px;display:block;"></div>
            <div style="font-size:12px;color:var(--text-300);" id="qrTimer">Valid for <strong id="qrCountdown">10:00</strong></div>
        </div>

        <div id="qrSteps" style="display:none;">
            <div class="qr-steps">
                <div class="qr-step"><div class="qr-step-num">1</div><div class="qr-step-text">Scan the QR code with your phone camera</div></div>
                <div class="qr-step"><div class="qr-step-num">2</div><div class="qr-step-text">Log in with Facebook that manages your WhatsApp Business account</div></div>
                <div class="qr-step"><div class="qr-step-num">3</div><div class="qr-step-text">Allow permissions — this page will update automatically</div></div>
            </div>
        </div>

        <div id="qrConnecting" style="display:none;margin-top:14px;">
            <span style="animation:wapulse 1.5s ease-in-out infinite;display:inline-block;width:8px;height:8px;border-radius:50%;background:#25d366;margin-right:6px;vertical-align:middle;"></span>
            <span style="font-size:13px;color:var(--text-300);">Waiting for authorization on your phone…</span>
        </div>

        <div id="qrDone" style="display:none;margin-top:14px;font-size:15px;font-weight:600;color:#16a34a;">
            Connected successfully! Reloading…
        </div>

        <div id="qrGenerateBtn" style="margin-top:16px;">
            <button type="button" class="btn btn-primary" onclick="startQrFlow()">Generate QR Code</button>
        </div>
        <div id="qrRefreshBtn" style="display:none;margin-top:12px;">
            <button type="button" class="btn btn-ghost btn-sm" onclick="startQrFlow()">Generate New QR</button>
        </div>
    @endif
</div>

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
    {{-- Account details (auto-filled via QR) --}}
    <div class="card" style="grid-column:1/-1;">
        <div class="card-header"><h3 class="card-title">Account Details</h3></div>
        <div class="card-body">
            @if($settings->is_connected)
                <div style="display:flex;gap:24px;flex-wrap:wrap;">
                    <div class="form-group" style="flex:1;min-width:200px;">
                        <label class="form-label">Phone Number ID</label>
                        <input type="text" name="phone_number_id" class="form-input" value="{{ $settings->phone_number_id }}" placeholder="Auto-filled on QR connect">
                    </div>
                    <div class="form-group" style="flex:1;min-width:200px;">
                        <label class="form-label">WABA ID</label>
                        <input type="text" name="waba_id" class="form-input" value="{{ $settings->waba_id }}" placeholder="Auto-filled on QR connect">
                    </div>
                </div>
                <p style="font-size:12px;color:var(--text-300);margin-top:8px;">These are filled automatically when you connect via QR code. Edit only if needed.</p>
            @else
                <p style="font-size:13px;color:var(--text-300);">Use the <strong>QR code above</strong> to connect your WhatsApp Business account. Phone Number ID and WABA ID will be filled automatically.</p>
            @endif
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

// ── QR Connect ──────────────────────────────────────────────
let qrState = null, qrPollTimer = null, qrCountdownTimer = null;

function startQrFlow() {
    fetch('{{ route("tenant.whatsapp.oauth.qr") }}', {
        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
    })
    .then(function(r) {
        if (!r.ok) return r.text().then(function(t) { throw new Error('Server error ' + r.status + ': ' + t.substring(0, 200)); });
        return r.json();
    })
    .then(function(data) {
        if (!data.success) { alert(data.message); return; }

        qrState = data.state;

        document.getElementById('qrImg').src =
            'https://api.qrserver.com/v1/create-qr-code/?size=200x200&margin=10&data=' + encodeURIComponent(data.url);

        document.getElementById('qrGenerateBtn').style.display  = 'none';
        document.getElementById('qrArea').style.display         = 'block';
        document.getElementById('qrSteps').style.display        = 'block';
        document.getElementById('qrConnecting').style.display   = 'block';
        document.getElementById('qrRefreshBtn').style.display   = 'block';

        startCountdown(600);
        startPolling();
    })
    .catch(function(err) { alert('Could not generate QR: ' + err.message); });
}

function startCountdown(seconds) {
    clearInterval(qrCountdownTimer);
    let remaining = seconds;
    const el = document.getElementById('qrCountdown');
    qrCountdownTimer = setInterval(function() {
        remaining--;
        const m = String(Math.floor(remaining / 60)).padStart(2, '0');
        const s = String(remaining % 60).padStart(2, '0');
        if (el) el.textContent = m + ':' + s;
        if (remaining <= 0) {
            clearInterval(qrCountdownTimer);
            clearInterval(qrPollTimer);
            document.getElementById('qrConnecting').style.display = 'none';
            document.getElementById('qrTimer').textContent = 'QR code expired. Generate a new one.';
        }
    }, 1000);
}

function startPolling() {
    clearInterval(qrPollTimer);
    qrPollTimer = setInterval(function() {
        if (!qrState) return;
        fetch('{{ route("tenant.whatsapp.oauth.status") }}?state=' + qrState, {
            headers: { 'Accept': 'application/json' }
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.connected) {
                clearInterval(qrPollTimer);
                clearInterval(qrCountdownTimer);
                document.getElementById('qrConnecting').style.display = 'none';
                document.getElementById('qrArea').style.display       = 'none';
                document.getElementById('qrRefreshBtn').style.display = 'none';
                document.getElementById('qrDone').style.display       = 'block';
                document.getElementById('qrSetupCard').classList.add('connected');
                setTimeout(function() { location.reload(); }, 1800);
            }
        })
        .catch(function() {});
    }, 3000);
}
</script>
@endpush
