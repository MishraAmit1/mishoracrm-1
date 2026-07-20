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

/* QR Connect */
.qr-setup-card { border:2px dashed var(--border-default); border-radius:var(--r-lg); padding:28px 24px; text-align:center; background:var(--bg-subtle); margin-bottom:24px; }
.qr-setup-card.connected { border-color:#22c55e; background:#f0fdf4; }
.qr-wrap { display:inline-block; background:#fff; border-radius:12px; padding:16px; box-shadow:0 2px 12px rgba(0,0,0,.08); margin:16px 0; }
.qr-steps { display:flex; gap:16px; justify-content:center; flex-wrap:wrap; margin:12px 0 0; }
.qr-step { display:flex; align-items:flex-start; gap:8px; text-align:left; max-width:160px; }
.qr-step-num { width:22px; height:22px; border-radius:50%; background:var(--accent,#6366f1); color:#fff; font-size:11px; font-weight:700; display:flex; align-items:center; justify-content:center; flex-shrink:0; margin-top:1px; }
.qr-step-text { font-size:12px; color:var(--text-300); line-height:1.4; }
.qr-timer { font-size:12px; color:var(--text-300); margin-top:6px; }
.qr-success-icon { width:56px; height:56px; border-radius:50%; background:#dcfce7; display:flex; align-items:center; justify-content:center; margin:0 auto 12px; }
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

{{-- ── QR Quick Connect ─────────────────────────────────────────── --}}
<div class="qr-setup-card" id="qrSetupCard">
    @if($settings->is_connected)
        <div class="qr-success-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2.5" style="width:28px;height:28px;">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
            </svg>
        </div>
        <div style="font-weight:700;font-size:16px;color:#16a34a;">
            Instagram Connected
            @if($accountInfo['username'] ?? null)
                — {{ '@' . $accountInfo['username'] }}
            @endif
        </div>
        @if($accountInfo['profile_picture_url'] ?? null)
            <img src="{{ $accountInfo['profile_picture_url'] }}" alt="" style="width:48px;height:48px;border-radius:50%;margin:10px auto 0;display:block;object-fit:cover;">
        @endif
        <div style="font-size:13px;color:var(--text-300);margin-top:4px;">
            @if($accountInfo['followers_count'] ?? null)
                {{ number_format($accountInfo['followers_count']) }} followers &nbsp;|&nbsp;
            @endif
            Account ID: {{ $settings->instagram_account_id }} &nbsp;|&nbsp; Page ID: {{ $settings->page_id }}
        </div>
        <button type="button" class="btn btn-sm" style="margin-top:14px;" onclick="startQrFlow()">Reconnect / Change Account</button>
    @else
        <div style="font-size:22px;margin-bottom:8px;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="width:40px;height:40px;color:var(--accent,#6366f1);margin:0 auto;display:block;">
                <rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/>
                <path stroke-linecap="round" d="M14 14h2m3 0h2M14 17v2m3-2v5m3-5v2"/>
            </svg>
        </div>
        <div style="font-weight:700;font-size:17px;color:var(--text-100);">Quick Connect with QR Code</div>
        <div style="font-size:13px;color:var(--text-300);margin-top:6px;max-width:400px;margin-left:auto;margin-right:auto;">
            Scan this QR code with your phone to connect your Instagram Business account automatically — no need to copy tokens manually.
        </div>

        {{-- QR display area --}}
        <div id="qrArea" style="display:none;margin-top:16px;">
            <div class="qr-wrap"><img id="qrImg" src="" alt="QR Code" style="width:200px;height:200px;display:block;"></div>
            <div class="qr-timer" id="qrTimer">Valid for <strong id="qrCountdown">10:00</strong></div>
            <div style="font-size:12px;color:var(--text-400);margin-top:4px;">Scan with any camera app or WhatsApp</div>
        </div>

        <div id="qrSteps" style="display:none;">
            <div class="qr-steps">
                <div class="qr-step"><div class="qr-step-num">1</div><div class="qr-step-text">Open your phone camera and scan the QR code above</div></div>
                <div class="qr-step"><div class="qr-step-num">2</div><div class="qr-step-text">Log in with your Facebook account that owns the Instagram Business page</div></div>
                <div class="qr-step"><div class="qr-step-num">3</div><div class="qr-step-text">Allow the requested permissions and this page will update automatically</div></div>
            </div>
        </div>

        <div id="qrConnecting" style="display:none;margin-top:14px;">
            <div style="font-size:13px;color:var(--text-300);">
                <span style="animation:pulse 1.5s ease-in-out infinite;display:inline-block;width:8px;height:8px;border-radius:50%;background:#f59e0b;margin-right:6px;vertical-align:middle;"></span>
                Waiting for authorization on your phone…
            </div>
        </div>

        <div id="qrDone" style="display:none;margin-top:14px;">
            <div style="font-size:15px;font-weight:600;color:#16a34a;">Connected successfully! Reloading…</div>
        </div>

        {{-- Buttons --}}
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
        <div class="card-header"><h3 class="card-title">Account Details</h3></div>
        <div class="card-body">
            @if($settings->is_connected)
                <div style="display:flex;gap:24px;flex-wrap:wrap;">
                    <div class="form-group" style="flex:1;min-width:200px;">
                        <label class="form-label">Page ID</label>
                        <input type="text" name="page_id" class="form-input" value="{{ $settings->page_id }}" placeholder="Auto-filled on QR connect">
                    </div>
                    <div class="form-group" style="flex:1;min-width:200px;">
                        <label class="form-label">Instagram Account ID</label>
                        <input type="text" name="instagram_account_id" class="form-input" value="{{ $settings->instagram_account_id }}" placeholder="Auto-filled on QR connect">
                    </div>
                </div>
                <p style="font-size:12px;color:var(--text-300);margin-top:8px;">These are filled automatically when you connect via QR code. Edit only if needed.</p>
            @else
                <p style="font-size:13px;color:var(--text-300);">Use the <strong>QR code above</strong> to connect your Instagram account. Page ID and Account ID will be filled automatically.</p>
            @endif
        </div>
    </div>

    {{-- Webhook info --}}
    <div class="card" style="grid-column:1/-1;">
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
        console.log('[Instagram Test Connection] full response:', data);

        if (data.success) {
            const sub = data.subscription;
            if (sub?.success) {
                console.log('[Instagram Test Connection] Webhook subscription: OK');
            } else {
                console.error('[Instagram Test Connection] Webhook subscription FAILED:', sub?.body ?? sub);
            }
            const subMsg = sub?.success
                ? 'Webhook subscription: OK'
                : 'Webhook subscription FAILED (this is why chatbot/automations won\'t fire) — full details logged in the browser console (F12).';
            alert('Connected! Account: @' + (data.account?.username ?? '') + '\n\n' + subMsg);
        } else {
            console.error('[Instagram Test Connection] Failed:', data);
            alert('Failed: ' + data.message + ' — full details logged in the browser console (F12).');
        }
    })
    .catch(err => {
        console.error('[Instagram Test Connection] Request error:', err);
        alert('Request failed. Check console.');
    })
    .finally(() => { btn.textContent = 'Test Connection'; btn.disabled = false; });
}

// ── QR Connect ──────────────────────────────────────────────
let qrState = null, qrPollTimer = null, qrCountdownTimer = null;

function startQrFlow() {
    fetch('{{ route("tenant.instagram.oauth.qr") }}', {
        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
    })
    .then(function(r) {
        if (!r.ok) return r.text().then(function(t) { throw new Error('Server error ' + r.status + ': ' + t.substring(0, 200)); });
        return r.json();
    })
    .then(function(data) {
        if (!data.success) { alert(data.message); return; }

        qrState = data.state;

        // QR via free image API — no JS library needed
        document.getElementById('qrImg').src =
            'https://api.qrserver.com/v1/create-qr-code/?size=200x200&margin=10&data=' + encodeURIComponent(data.url);

        document.getElementById('qrGenerateBtn').style.display = 'none';
        document.getElementById('qrArea').style.display      = 'block';
        document.getElementById('qrSteps').style.display     = 'block';
        document.getElementById('qrConnecting').style.display= 'block';
        document.getElementById('qrRefreshBtn').style.display= 'block';

        startCountdown(600);
        startPolling();
    })
    .catch(function(err) {
        alert('Could not generate QR: ' + err.message);
    });
}

function startCountdown(seconds) {
    clearInterval(qrCountdownTimer);
    let remaining = seconds;
    const el = document.getElementById('qrCountdown');

    qrCountdownTimer = setInterval(() => {
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
    qrPollTimer = setInterval(() => {
        if (!qrState) return;
        fetch('{{ route("tenant.instagram.oauth.status") }}?state=' + qrState, {
            headers: { 'Accept': 'application/json' }
        })
        .then(r => r.json())
        .then(data => {
            if (data.connected) {
                clearInterval(qrPollTimer);
                clearInterval(qrCountdownTimer);
                document.getElementById('qrConnecting').style.display = 'none';
                document.getElementById('qrArea').style.display = 'none';
                document.getElementById('qrRefreshBtn').style.display = 'none';
                document.getElementById('qrDone').style.display = 'block';
                document.getElementById('qrSetupCard').classList.add('connected');
                setTimeout(() => location.reload(), 1800);
            } else if (data.failed) {
                clearInterval(qrPollTimer);
                clearInterval(qrCountdownTimer);
                document.getElementById('qrConnecting').style.display = 'none';
                document.getElementById('qrTimer').textContent = 'Connection failed: ' + data.message;
                document.getElementById('qrTimer').style.color = '#dc2626';
            }
        })
        .catch(() => {});
    }, 3000);
}
</script>
<style>
@keyframes pulse {
    0%,100% { opacity:1; }
    50%      { opacity:.3; }
}
</style>
@endpush
