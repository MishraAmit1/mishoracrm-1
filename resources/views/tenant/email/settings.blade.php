@extends('layouts.app')
@section('title', 'Email Settings')

@push('styles')
<style>
.em-settings-grid { display:grid; grid-template-columns:1fr 1fr; gap:20px; }
@media(max-width:900px) { .em-settings-grid { grid-template-columns:1fr; } }

.em-badge-connected    { background:rgba(220,252,231,0.14); color:#73E89F; border-radius:20px; padding:3px 10px; font-size:12px; font-weight:600; }
.em-badge-disconnected { background:rgba(254,226,226,0.14); color:#EA7171; border-radius:20px; padding:3px 10px; font-size:12px; font-weight:600; }

.em-steps-list { list-style:none; padding:0; margin:0; display:flex; flex-direction:column; gap:14px; }
.em-step-item  { display:flex; gap:12px; align-items:flex-start; }
.em-step-num   { min-width:24px; height:24px; border-radius:50%; background:var(--accent); color:#fff; font-size:12px; font-weight:700; display:flex; align-items:center; justify-content:center; flex-shrink:0; margin-top:1px; }
.em-step-title { font-size:13.5px; font-weight:700; color:var(--text-100); margin-bottom:2px; }
.em-step-desc  { font-size:12.5px; color:var(--text-300); line-height:1.6; }
.em-step-desc a { color:var(--accent); }
.em-step-desc code { background:var(--bg-elevated); border:1px solid var(--border-subtle); border-radius:4px; padding:1px 6px; font-size:11.5px; }

.em-provider-tabs { display:flex; gap:0; margin-bottom:16px; border-bottom:2px solid var(--border-subtle); flex-wrap:wrap; }
.em-provider-tab {
    padding:9px 14px; font-size:12.5px; font-weight:600; cursor:pointer;
    color:var(--text-300); border-bottom:2px solid transparent; margin-bottom:-2px;
    transition:all .15s;
}
.em-provider-tab.active { color:var(--accent); border-bottom-color:var(--accent); }
.em-provider-panel { display:none; }
.em-provider-panel.active { display:block; }

.em-tip-box {
    display:flex; gap:10px; padding:12px 14px; border-radius:var(--r-md);
    font-size:12.5px; line-height:1.6; margin-top:16px;
    background:rgba(99,120,255,.06); border:1px solid rgba(99,120,255,.15); color:var(--text-200);
}
.em-tip-box svg { flex-shrink:0; margin-top:1px; width:16px; height:16px; color:var(--accent); }

.em-test-result { font-size:12.5px; font-weight:600; margin-left:10px; }
.em-test-result.ok  { color:var(--green); }
.em-test-result.bad { color:var(--red); }
</style>
@endpush

@section('content')

<div class="page-head">
    <div>
        <div class="page-title">Email Settings</div>
        <div class="page-sub">Apna SMTP connect karein — sab CRM emails (compose, bulk, notifications) isi se bhejenge</div>
    </div>
    <div class="page-actions">
        <a href="{{ route('tenant.email.index') }}" class="btn btn-secondary">← Back to Email</a>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success" style="margin-bottom:20px;">{{ session('success') }}</div>
@endif
@if($errors->any())
<div class="alert alert-error" style="margin-bottom:20px;">{{ $errors->first() }}</div>
@endif

{{-- ── Setup guide ─────────────────────────────────────────────── --}}
<div class="card" style="margin-bottom:20px;">
    <div class="card-header">
        <div>
            <div class="card-title">SMTP Credentials Kahan Se Milengi</div>
            <div style="font-size:12.5px;color:var(--text-300);margin-top:2px;">Apna provider choose karein, ya kisi bhi hosting/email provider ka SMTP daal sakte hain.</div>
        </div>
    </div>
    <div class="card-body">
        <div class="em-provider-tabs">
            <div class="em-provider-tab active" onclick="switchProvider('gmail')" id="tab-gmail">Gmail</div>
            <div class="em-provider-tab" onclick="switchProvider('other')" id="tab-other">Zoho / Outlook / Hosting</div>
        </div>

        <div class="em-provider-panel active" id="panel-gmail">
            <ol class="em-steps-list">
                <li class="em-step-item">
                    <div class="em-step-num">1</div>
                    <div>
                        <div class="em-step-title">2-Step Verification On Karein</div>
                        <div class="em-step-desc">Apne Google Account → Security → <a href="https://myaccount.google.com/security" target="_blank" rel="noopener">2-Step Verification</a> enable karein (agar pehle se on nahi hai).</div>
                    </div>
                </li>
                <li class="em-step-item">
                    <div class="em-step-num">2</div>
                    <div>
                        <div class="em-step-title">App Password Banayein</div>
                        <div class="em-step-desc">Google Account → Security → <a href="https://myaccount.google.com/apppasswords" target="_blank" rel="noopener">App Passwords</a> → naam do (e.g. "CRM") → 16-character password copy karein. <b>Apna Gmail login password yahan use na karein.</b></div>
                    </div>
                </li>
                <li class="em-step-item">
                    <div class="em-step-num">3</div>
                    <div>
                        <div class="em-step-title">Neeche Ye Values Bharein</div>
                        <div class="em-step-desc">Host: <code>smtp.gmail.com</code> · Port: <code>587</code> · Encryption: <code>TLS</code> · Username: apka Gmail address · Password: upar wala App Password.</div>
                    </div>
                </li>
            </ol>
        </div>

        <div class="em-provider-panel" id="panel-other">
            <ol class="em-steps-list">
                <li class="em-step-item">
                    <div class="em-step-num">1</div>
                    <div>
                        <div class="em-step-title">Apne Email Provider Ki SMTP Details Nikalein</div>
                        <div class="em-step-desc">Zoho Mail, Outlook/Microsoft 365, ya apki hosting (cPanel/GoDaddy/Hostinger) ke email settings mein "SMTP" section dekhein — wahan Host, Port, Encryption diya hota hai.</div>
                    </div>
                </li>
                <li class="em-step-item">
                    <div class="em-step-num">2</div>
                    <div>
                        <div class="em-step-title">Common Providers</div>
                        <div class="em-step-desc">
                            Zoho: <code>smtp.zoho.com</code> · Port <code>587</code> (TLS)<br>
                            Outlook/365: <code>smtp.office365.com</code> · Port <code>587</code> (TLS)<br>
                            cPanel Hosting: usually <code>mail.yourdomain.com</code> · Port <code>465</code> (SSL) — apke hosting provider se confirm karein.
                        </div>
                    </div>
                </li>
                <li class="em-step-item">
                    <div class="em-step-num">3</div>
                    <div>
                        <div class="em-step-title">Username/Password</div>
                        <div class="em-step-desc">Username usually apka full email address hota hai, password wahi jo mailbox login ke liye use karte hain (ya provider ka app-specific password, agar available ho).</div>
                    </div>
                </li>
            </ol>
        </div>

        <div class="em-tip-box">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z"/></svg>
            Neeche form fill karke save karein, phir <b>"Test Connection"</b> click karke confirm karein ki emails bhej rahe hain. Jab tak "Connected" nahi dikhta, CRM system mailer use karega (jo abhi live emails nahi bhejta).
        </div>
    </div>
</div>

{{-- ── SMTP form ────────────────────────────────────────────────── --}}
<form method="POST" action="{{ route('tenant.email.settings.save') }}">
@csrf
<div class="em-settings-grid">

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">SMTP Server</h3>
            @if($settings->exists)
                @if($settings->is_connected)
                    <span class="em-badge-connected">● Connected</span>
                @else
                    <span class="em-badge-disconnected">● Not Connected</span>
                @endif
            @endif
        </div>
        <div class="card-body">
            <div style="display:flex;gap:16px;flex-wrap:wrap;margin-bottom:14px;">
                <div class="form-group" style="flex:2;min-width:180px;margin:0;">
                    <label class="form-label">SMTP Host <span style="color:var(--red)">*</span></label>
                    <input type="text" name="smtp_host" class="form-input" placeholder="smtp.gmail.com" value="{{ old('smtp_host', $settings->smtp_host) }}" required>
                </div>
                <div class="form-group" style="flex:1;min-width:100px;margin:0;">
                    <label class="form-label">Port <span style="color:var(--red)">*</span></label>
                    <input type="number" name="smtp_port" class="form-input" placeholder="587" value="{{ old('smtp_port', $settings->smtp_port ?? 587) }}" required>
                </div>
            </div>
            <div class="form-group" style="margin-bottom:14px;">
                <label class="form-label">Encryption <span style="color:var(--red)">*</span></label>
                <select name="smtp_encryption" class="form-input" required>
                    @php $enc = old('smtp_encryption', $settings->smtp_encryption ?? 'tls'); @endphp
                    <option value="tls"  {{ $enc === 'tls'  ? 'selected' : '' }}>TLS (recommended, port 587)</option>
                    <option value="ssl"  {{ $enc === 'ssl'  ? 'selected' : '' }}>SSL (usually port 465)</option>
                    <option value="none" {{ $enc === 'none' ? 'selected' : '' }}>None</option>
                </select>
            </div>
            <div class="form-group" style="margin-bottom:14px;">
                <label class="form-label">SMTP Username <span style="color:var(--red)">*</span></label>
                <input type="text" name="smtp_username" class="form-input" placeholder="you@yourbusiness.com" value="{{ old('smtp_username', $settings->smtp_username) }}" required>
            </div>
            <div class="form-group">
                <label class="form-label">SMTP Password {{ $settings->exists ? '' : '*' }}</label>
                <input type="password" name="smtp_password" class="form-input" placeholder="{{ $settings->exists ? '•••••••• (leave blank to keep current)' : 'App Password / mailbox password' }}" {{ $settings->exists ? '' : 'required' }}>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h3 class="card-title">From Address</h3></div>
        <div class="card-body">
            <div class="form-group" style="margin-bottom:14px;">
                <label class="form-label">From Email <span style="color:var(--red)">*</span></label>
                <input type="email" name="from_address" class="form-input" placeholder="hello@yourbusiness.com" value="{{ old('from_address', $settings->from_address) }}" required>
                <p style="font-size:11px;color:var(--text-400);margin-top:6px;">Zyadatar providers ke liye ye aapke SMTP Username jaisa hi hona chahiye.</p>
            </div>
            <div class="form-group">
                <label class="form-label">From Name <span style="color:var(--red)">*</span></label>
                <input type="text" name="from_name" class="form-input" placeholder="Your Business Name" value="{{ old('from_name', $settings->from_name ?? auth()->user()->tenant->name ?? '') }}" required>
            </div>

            @if($settings->exists)
            <div style="margin-top:20px;padding-top:16px;border-top:1px solid var(--border-subtle);">
                <button type="button" class="btn btn-ghost btn-sm" id="testBtn" onclick="testEmailConnection()">Test Connection</button>
                <span id="testResult" class="em-test-result"></span>
                @if($settings->last_tested_at)
                    <div style="font-size:11px;color:var(--text-400);margin-top:8px;">Last tested {{ $settings->last_tested_at->diffForHumans() }}</div>
                @endif
            </div>
            @endif
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
function switchProvider(p) {
    ['gmail','other'].forEach(function(k) {
        document.getElementById('tab-' + k).classList.toggle('active', k === p);
        document.getElementById('panel-' + k).classList.toggle('active', k === p);
    });
}

function testEmailConnection() {
    const btn    = document.getElementById('testBtn');
    const result = document.getElementById('testResult');

    btn.disabled = true;
    result.textContent = 'Sending…';
    result.className = 'em-test-result';

    fetch("{{ route('tenant.email.settings.test') }}", {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '{{ csrf_token() }}',
            'Accept': 'application/json',
        },
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            result.textContent = '✓ Test email sent — check your inbox';
            result.className = 'em-test-result ok';
            setTimeout(() => location.reload(), 1500);
        } else {
            result.textContent = '✗ Failed: ' + (data.message || 'Unknown error');
            result.className = 'em-test-result bad';
        }
    })
    .catch(err => {
        result.textContent = '✗ Request failed: ' + err.message;
        result.className = 'em-test-result bad';
    })
    .finally(() => { btn.disabled = false; });
}
</script>
@endpush
