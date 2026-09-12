@extends('layouts.app')
@section('title', 'Meta App Setup — Superadmin')

@push('styles')
<style>
/* ── Guide layout ─────────────────────────────────────────────── */
.guide-wrap { display:grid; grid-template-columns:340px 1fr; gap:24px; align-items:start; }
@media(max-width:900px){ .guide-wrap { grid-template-columns:1fr; } }

/* ── Credentials card ─────────────────────────────────────────── */
.creds-card { position:sticky; top:20px; }

/* ── Steps ────────────────────────────────────────────────────── */
.step-list { display:flex; flex-direction:column; gap:0; }
.step-item {
    display:flex; gap:0;
    position:relative;
}
.step-item:not(:last-child) .step-spine::after {
    content:'';
    position:absolute;
    top:36px; left:50%; transform:translateX(-50%);
    width:2px; bottom:0;
    background:var(--border-subtle);
}
.step-spine {
    width:40px; flex-shrink:0;
    display:flex; flex-direction:column; align-items:center;
    padding-top:4px;
    position:relative;
}
.step-num {
    width:32px; height:32px; border-radius:50%;
    background:var(--bg-surface); border:2px solid var(--border-default);
    color:var(--text-300); font-size:13px; font-weight:700;
    display:flex; align-items:center; justify-content:center;
    flex-shrink:0; z-index:1; position:relative;
}
.step-num.green  { background:rgba(220,252,231,0.14); border-color:#22c55e; color:#6FEC9D; }
.step-num.blue   { background:rgba(219,234,254,0.14); border-color:#3b82f6; color:#6F90EC; }
.step-num.purple { background:rgba(237,233,254,0.14); border-color:#8b5cf6; color:#6d28d9; }
.step-num.orange { background:rgba(255,247,237,0.14); border-color:#f97316; color:#F58F65; }
.step-num.pink   { background:rgba(252,231,243,0.14); border-color:#ec4899; color:#ED6EA3; }
.step-num.teal   { background:rgba(204,251,241,0.14); border-color:#14b8a6; color:#6EEDE3; }

.step-body  { flex:1; padding:0 0 28px 16px; }
.step-title { font-weight:700; font-size:14px; color:var(--text-100); margin-bottom:6px; line-height:1.3; }
.step-desc  { font-size:13px; color:var(--text-300); line-height:1.6; margin-bottom:10px; }
.step-desc strong { color:var(--text-200); }

/* ── Code / URL box ───────────────────────────────────────────── */
.url-box {
    display:flex; align-items:center; gap:8px;
    background:var(--bg-subtle); border:1px solid var(--border-subtle);
    border-radius:var(--r-md); padding:8px 12px;
    margin:8px 0;
}
.url-box code {
    font-family:var(--mono); font-size:12px; color:var(--text-200);
    word-break:break-all; flex:1;
}
.copy-btn { cursor:pointer; background:none; border:none; color:var(--text-400); padding:2px 4px; flex-shrink:0; }
.copy-btn:hover { color:var(--accent); }

/* ── Note / Warning ───────────────────────────────────────────── */
.step-note {
    display:flex; gap:8px; align-items:flex-start;
    background:rgba(255,251,235,0.14); border:1px solid #fde68a;
    border-radius:var(--r-md); padding:10px 12px;
    font-size:12px; color:#F19D6A; line-height:1.5;
    margin-top:8px;
}
.step-note.info {
    background:rgba(239,246,255,0.14); border-color:rgba(191,219,254,0.35); color:#748FE7;
}
.step-note.success {
    background:rgba(240,253,244,0.14); border-color:rgba(187,247,208,0.35); color:#73E89F;
}

/* ── Tag badge ────────────────────────────────────────────────── */
.tag {
    display:inline-block; padding:2px 8px; border-radius:20px;
    font-size:11px; font-weight:600; vertical-align:middle;
}
.tag-wa  { background:rgba(220,252,231,0.14); color:#73E89F; }
.tag-ig  { background:rgba(252,231,243,0.14); color:#ED6EA3; }
.tag-both { background:rgba(237,233,254,0.14); color:#6d28d9; }

/* ── Section divider ──────────────────────────────────────────── */
.section-label {
    font-size:11px; font-weight:700; letter-spacing:.08em;
    text-transform:uppercase; color:var(--text-400);
    padding:4px 0 12px;
}

/* ── Sub-steps ────────────────────────────────────────────────── */
.sub-steps { list-style:none; padding:0; margin:8px 0 0; display:flex; flex-direction:column; gap:6px; }
.sub-steps li { display:flex; gap:8px; align-items:flex-start; font-size:13px; color:var(--text-300); line-height:1.45; }
.sub-steps li::before { content:'›'; color:var(--accent); font-weight:700; flex-shrink:0; margin-top:1px; }
.sub-steps li strong { color:var(--text-200); }

/* ── Permission chips ─────────────────────────────────────────── */
.perm-chips { display:flex; flex-wrap:wrap; gap:6px; margin-top:8px; }
.perm-chip {
    font-family:var(--mono); font-size:11px;
    background:var(--bg-subtle); border:1px solid var(--border-subtle);
    border-radius:var(--r-sm); padding:3px 8px; color:var(--text-200);
}
</style>
@endpush

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Meta App Setup</h1>
        <p class="page-sub">Platform-level setup — one time configuration, used by all tenants for WhatsApp &amp; Instagram</p>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success" style="margin-bottom:20px;">{{ session('success') }}</div>
@endif

<div class="guide-wrap">

    {{-- ── LEFT: Credentials card (sticky) ──────────────────────── --}}
    <div class="creds-card">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Step 7 — Save Credentials</h3>
            </div>
            <div class="card-body">
                <p style="font-size:13px;color:var(--text-300);margin-bottom:18px;">
                    After completing all steps, paste your <strong>App ID</strong> and <strong>App Secret</strong> here.
                    These are shared across all tenants.
                </p>

                <form method="POST" action="{{ route('superadmin.platform-settings.meta.save') }}">
                @csrf
                    <div class="form-group" style="margin-bottom:16px;">
                        <label class="form-label">App ID</label>
                        <input type="text" name="app_id" class="form-input"
                            value="{{ old('app_id', $app_id) }}"
                            placeholder="e.g. 1234567890123456" required>
                        <span class="form-hint">App → Settings → Basic → App ID</span>
                    </div>

                    <div class="form-group" style="margin-bottom:20px;">
                        <label class="form-label">App Secret</label>
                        <input type="password" name="app_secret" class="form-input"
                            placeholder="{{ $app_secret ? '••••••• (saved — leave blank to keep)' : 'Paste App Secret here' }}">
                        @if($app_secret)
                            <span class="form-hint" style="color:#6FEC9D;">
                                <svg viewBox="0 0 16 16" fill="currentColor" style="width:12px;height:12px;display:inline;margin-right:3px;"><path d="M13.854 3.646a.5.5 0 010 .708l-7 7a.5.5 0 01-.708 0l-3.5-3.5a.5.5 0 11.708-.708L6.5 10.293l6.646-6.647a.5.5 0 01.708 0z"/></svg>
                                Secret saved — leave blank to keep current.
                            </span>
                        @else
                            <span class="form-hint">App → Settings → Basic → App Secret → Show</span>
                        @endif
                    </div>

                    <div style="border-top:1px solid var(--border-subtle);padding-top:16px;margin-bottom:16px;">
                        <div class="section-label" style="padding:0 0 8px;">Instagram Login credentials</div>
                        <p style="font-size:12px;color:var(--text-300);margin-bottom:14px;">
                            <span class="tag tag-ig">Instagram</span>
                            From <strong>App → Instagram → API setup with Instagram login</strong>.
                            Leave blank to reuse the Facebook App ID / Secret above.
                        </p>

                        <div class="form-group" style="margin-bottom:14px;">
                            <label class="form-label">Instagram App ID</label>
                            <input type="text" name="ig_app_id" class="form-input"
                                value="{{ old('ig_app_id', $ig_app_id) }}"
                                placeholder="e.g. 1234567890123456">
                        </div>

                        <div class="form-group" style="margin-bottom:0;">
                            <label class="form-label">Instagram App Secret</label>
                            <input type="password" name="ig_app_secret" class="form-input"
                                placeholder="{{ $ig_app_secret ? '••••••• (saved — leave blank to keep)' : 'Paste Instagram App Secret here' }}">
                            @if($ig_app_secret)
                                <span class="form-hint" style="color:#6FEC9D;">Secret saved — leave blank to keep current.</span>
                            @endif
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary" style="width:100%;">Save Credentials</button>
                </form>

                @if($app_id && $app_secret)
                <div class="step-note success" style="margin-top:14px;">
                    <svg viewBox="0 0 20 20" fill="currentColor" style="width:15px;height:15px;flex-shrink:0;margin-top:1px;"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                    <span>Credentials are configured. Tenants can now connect WhatsApp &amp; Instagram via QR code.</span>
                </div>
                @else
                <div class="step-note" style="margin-top:14px;">
                    <svg viewBox="0 0 20 20" fill="currentColor" style="width:15px;height:15px;flex-shrink:0;margin-top:1px;"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                    <span>Credentials not set yet — tenants cannot connect WhatsApp or Instagram until you save these.</span>
                </div>
                @endif

                {{-- Quick URLs reference --}}
                <div style="margin-top:20px;padding-top:16px;border-top:1px solid var(--border-subtle);">
                    <div class="section-label">Callback URLs to configure in Meta</div>

                    <div style="font-size:11px;color:var(--text-400);margin-bottom:4px;">
                        <span class="tag tag-ig">Instagram</span> &amp;
                        <span class="tag tag-wa">WhatsApp</span>
                        OAuth
                    </div>
                    <div class="url-box">
                        <code id="igUrl">{{ url('/instagram/oauth/callback') }}</code>
                        <button type="button" class="copy-btn" onclick="copyText('igUrl')" title="Copy">
                            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="width:14px;height:14px;"><path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                        </button>
                    </div>
                    <div class="url-box">
                        <code id="waUrl">{{ url('/whatsapp/oauth/callback') }}</code>
                        <button type="button" class="copy-btn" onclick="copyText('waUrl')" title="Copy">
                            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="width:14px;height:14px;"><path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                        </button>
                    </div>

                    <div style="font-size:11px;color:var(--text-400);margin:10px 0 4px;">
                        <span class="tag tag-wa">WhatsApp</span>
                        Webhook
                    </div>
                    <div class="url-box">
                        <code id="waWebhook">{{ url('/webhook/whatsapp') }}</code>
                        <button type="button" class="copy-btn" onclick="copyText('waWebhook')" title="Copy">
                            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="width:14px;height:14px;"><path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                        </button>
                    </div>

                    <div style="font-size:11px;color:var(--text-400);margin:10px 0 4px;">
                        <span class="tag tag-ig">Instagram</span>
                        Webhook
                    </div>
                    <div class="url-box">
                        <code id="igWebhook">{{ url('/webhook/instagram') }}</code>
                        <button type="button" class="copy-btn" onclick="copyText('igWebhook')" title="Copy">
                            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="width:14px;height:14px;"><path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── RIGHT: Step-by-step guide ──────────────────────────────── --}}
    <div>
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Step-by-Step Setup Guide</h3>
                <p style="font-size:12px;color:var(--text-400);margin-top:4px;margin-bottom:0;">
                    One-time setup. All tenants will use this same Meta App.
                </p>
            </div>
            <div class="card-body" style="padding-top:20px;">

                <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:20px;">
                    <span class="tag tag-wa">WhatsApp Business</span>
                    <span class="tag tag-ig">Instagram</span>
                    <span class="tag tag-both">Facebook Login</span>
                    <span style="font-size:12px;color:var(--text-400);align-self:center;">— all covered in this guide</span>
                </div>

                <div class="step-list">

                    {{-- ── STEP 1 ───────────────────────────────────── --}}
                    <div class="step-item">
                        <div class="step-spine">
                            <div class="step-num blue">1</div>
                        </div>
                        <div class="step-body">
                            <div class="step-title">Create a Meta Developer App</div>
                            <div class="step-desc">
                                Go to <strong>developers.facebook.com</strong> → click <strong>My Apps → Create App</strong>.
                            </div>
                            <ul class="sub-steps">
                                <li>Use case: select <strong>"Other"</strong></li>
                                <li>App type: select <strong>"Business"</strong></li>
                                <li>Give any name, e.g. <em>"YourCRM Platform"</em></li>
                                <li>Link to your <strong>Business Manager account</strong> (required for WhatsApp)</li>
                                <li>Click <strong>Create App</strong></li>
                            </ul>
                            <div class="step-note info" style="margin-top:10px;">
                                <svg viewBox="0 0 20 20" fill="currentColor" style="width:15px;height:15px;flex-shrink:0;margin-top:1px;"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>
                                <span>You need a <strong>Business Manager account</strong> at business.facebook.com before this step. If you don't have one, create it first.</span>
                            </div>
                        </div>
                    </div>

                    {{-- ── STEP 2 ───────────────────────────────────── --}}
                    <div class="step-item">
                        <div class="step-spine">
                            <div class="step-num green">2</div>
                        </div>
                        <div class="step-body">
                            <div class="step-title">Add Facebook Login Product <span class="tag tag-ig" style="margin-left:4px;">Instagram</span> <span class="tag tag-wa">WhatsApp</span></div>
                            <div class="step-desc">
                                Needed so tenants can connect via QR code (OAuth flow).
                            </div>
                            <ul class="sub-steps">
                                <li>In app dashboard → <strong>Add a Product</strong></li>
                                <li>Find <strong>"Facebook Login"</strong> → click <strong>Set up</strong></li>
                                <li>Choose <strong>"Web"</strong></li>
                                <li>Go to <strong>Facebook Login → Settings</strong></li>
                                <li>Under <strong>Valid OAuth Redirect URIs</strong>, add <strong>both</strong> URLs below:</li>
                            </ul>
                            <div class="url-box" style="margin-top:10px;">
                                <code>{{ url('/instagram/oauth/callback') }}</code>
                                <button type="button" class="copy-btn" onclick="navigator.clipboard.writeText('{{ url('/instagram/oauth/callback') }}')" title="Copy">
                                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="width:14px;height:14px;"><path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                </button>
                            </div>
                            <div class="url-box">
                                <code>{{ url('/whatsapp/oauth/callback') }}</code>
                                <button type="button" class="copy-btn" onclick="navigator.clipboard.writeText('{{ url('/whatsapp/oauth/callback') }}')" title="Copy">
                                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="width:14px;height:14px;"><path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                </button>
                            </div>
                            <ul class="sub-steps" style="margin-top:10px;">
                                <li>Enable <strong>"Login with the JavaScript SDK"</strong> → OFF (not needed)</li>
                                <li>Click <strong>Save Changes</strong></li>
                            </ul>
                        </div>
                    </div>

                    {{-- ── STEP 3 ───────────────────────────────────── --}}
                    <div class="step-item">
                        <div class="step-spine">
                            <div class="step-num pink">3</div>
                        </div>
                        <div class="step-body">
                            <div class="step-title">Add WhatsApp Product + Coexistence Enable Karein <span class="tag tag-wa" style="margin-left:4px;">WhatsApp</span></div>
                            <div class="step-desc">
                                Enables tenants to connect their WhatsApp Business accounts.
                                <strong>Coexistence mode</strong> allows tenants to use the same number in both the WhatsApp Business App (phone) and the CRM API simultaneously.
                            </div>

                            {{-- Coexistence callout --}}
                            <div style="background:rgba(240,253,244,0.14);border:1.5px solid #86efac;border-radius:8px;padding:12px 14px;margin-bottom:14px;">
                                <div style="font-weight:700;font-size:13px;color:#73E89F;margin-bottom:6px;">
                                    What is Coexistence?
                                </div>
                                <div style="font-size:12px;color:#79E2A1;line-height:1.6;">
                                    By default, migrating a number to WhatsApp Cloud API <strong>removes</strong> it from the WhatsApp Business App.
                                    With <strong>Coexistence mode</strong>, the number stays on the phone app AND also works via the API:
                                    <ul style="margin:6px 0 0 16px;padding:0;">
                                        <li>Incoming messages go to <strong>both</strong> the phone app and the CRM webhook</li>
                                        <li>Messages sent via API appear in the phone app's chat list</li>
                                        <li>Business can handle some chats manually + automate others via CRM</li>
                                    </ul>
                                </div>
                            </div>

                            <ul class="sub-steps">
                                <li>App dashboard → <strong>Add a Product</strong></li>
                                <li>Find <strong>"WhatsApp"</strong> → click <strong>Set up</strong></li>
                                <li>Link a <strong>WhatsApp Business Account (WABA)</strong> when prompted — this is a test WABA for development</li>
                                <li>Go to <strong>WhatsApp → Configuration</strong></li>
                                <li>Under <strong>Webhook</strong> → click <strong>Edit</strong></li>
                                <li><strong>Callback URL:</strong></li>
                            </ul>
                            <div class="url-box" style="margin-top:8px;">
                                <code>{{ url('/webhook/whatsapp') }}</code>
                                <button type="button" class="copy-btn" onclick="navigator.clipboard.writeText('{{ url('/webhook/whatsapp') }}')" title="Copy">
                                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="width:14px;height:14px;"><path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                </button>
                            </div>
                            <ul class="sub-steps" style="margin-top:8px;">
                                <li><strong>Verify Token:</strong> get this from a tenant's WhatsApp API Settings page → Webhook section</li>
                                <li>Click <strong>Verify and Save</strong></li>
                                <li>After saving, click <strong>Manage</strong> → subscribe to <strong>"messages"</strong> field</li>
                            </ul>
                            <div class="step-note">
                                <svg viewBox="0 0 20 20" fill="currentColor" style="width:15px;height:15px;flex-shrink:0;margin-top:1px;"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                                <span>Each tenant has their own Verify Token. To subscribe webhooks per tenant, the tenant must first connect and then you configure their webhook in Meta using <em>their</em> Verify Token.</span>
                            </div>

                            {{-- Tenant coexistence instruction --}}
                            <div class="step-note info" style="margin-top:8px;">
                                <svg viewBox="0 0 20 20" fill="currentColor" style="width:15px;height:15px;flex-shrink:0;margin-top:1px;"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>
                                <span>
                                    <strong>Tell your tenants:</strong> Before connecting to the CRM, tenants must enable Coexistence in their
                                    WhatsApp Business App → Settings → Business Tools → WhatsApp Business API →
                                    choose <em>"Continue using WhatsApp Business App"</em>.
                                    This keeps their phone app working while also enabling the CRM API.
                                </span>
                            </div>
                        </div>
                    </div>

                    {{-- ── STEP 4 ───────────────────────────────────── --}}
                    <div class="step-item">
                        <div class="step-spine">
                            <div class="step-num purple">4</div>
                        </div>
                        <div class="step-body">
                            <div class="step-title">Add Instagram Product — "API setup with Instagram login" <span class="tag tag-ig" style="margin-left:4px;">Instagram</span></div>
                            <div class="step-desc">
                                Enables tenants to connect their Instagram Professional (Business/Creator) accounts
                                <strong>directly</strong> — no Facebook Page required.
                            </div>
                            <ul class="sub-steps">
                                <li>App dashboard → <strong>Add a Product</strong> → <strong>Instagram</strong> → <strong>Set up</strong></li>
                                <li>Open <strong>Instagram → API setup with Instagram login</strong> (NOT the Facebook-Page "Instagram Graph API" path)</li>
                                <li>Section <strong>3. Set up Instagram business login</strong> → <strong>Business login settings</strong>:</li>
                                <li>Add <strong>OAuth redirect URI</strong>: <code>{{ url('/instagram/oauth/callback') }}</code></li>
                                <li>Copy the <strong>Instagram app ID</strong> + <strong>Instagram app secret</strong> shown here into the form on the left</li>
                                <li>Section <strong>2. Configure webhooks</strong> → Callback URL:</li>
                            </ul>
                            <div class="url-box" style="margin-top:8px;">
                                <code>{{ url('/webhook/instagram') }}</code>
                                <button type="button" class="copy-btn" onclick="navigator.clipboard.writeText('{{ url('/webhook/instagram') }}')" title="Copy">
                                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="width:14px;height:14px;"><path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                </button>
                            </div>
                            <ul class="sub-steps" style="margin-top:8px;">
                                <li>Subscribe to fields: <strong>messages</strong>, <strong>comments</strong></li>
                                <li>Use the tenant's Verify Token from their Instagram Settings page</li>
                            </ul>
                            <div class="step-note info" style="margin-top:8px;">
                                <svg viewBox="0 0 20 20" fill="currentColor" style="width:15px;height:15px;flex-shrink:0;margin-top:1px;"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>
                                <span>Each connected account is auto-subscribed by MishoraCRM via the API right after the tenant scans the QR — this dashboard step only covers the one-time callback verification handshake.</span>
                            </div>
                        </div>
                    </div>

                    {{-- ── STEP 5 ───────────────────────────────────── --}}
                    <div class="step-item">
                        <div class="step-spine">
                            <div class="step-num orange">5</div>
                        </div>
                        <div class="step-body">
                            <div class="step-title">Configure App Permissions <span class="tag tag-both" style="margin-left:4px;">Required</span></div>
                            <div class="step-desc">
                                Go to <strong>App Review → Permissions and Features</strong> and request these permissions:
                            </div>
                            <div style="margin-bottom:10px;">
                                <div style="font-size:11px;color:var(--text-400);margin-bottom:5px;">For WhatsApp:</div>
                                <div class="perm-chips">
                                    <span class="perm-chip">whatsapp_business_management</span>
                                    <span class="perm-chip">whatsapp_business_messaging</span>
                                </div>
                            </div>
                            <div>
                                <div style="font-size:11px;color:var(--text-400);margin-bottom:5px;">For Instagram (Instagram Login — no Facebook Page permissions):</div>
                                <div class="perm-chips">
                                    <span class="perm-chip">instagram_business_basic</span>
                                    <span class="perm-chip">instagram_business_manage_messages</span>
                                    <span class="perm-chip">instagram_business_manage_comments</span>
                                </div>
                            </div>
                            <div class="step-note info" style="margin-top:10px;">
                                <svg viewBox="0 0 20 20" fill="currentColor" style="width:15px;height:15px;flex-shrink:0;margin-top:1px;"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>
                                <span>During development/testing, permissions work for <strong>app admins and testers</strong> without full review. For production (all users), you need to submit for App Review.</span>
                            </div>
                        </div>
                    </div>

                    {{-- ── STEP 6 ───────────────────────────────────── --}}
                    <div class="step-item">
                        <div class="step-spine">
                            <div class="step-num teal">6</div>
                        </div>
                        <div class="step-body">
                            <div class="step-title">Switch App to Live Mode</div>
                            <div class="step-desc">
                                While in <strong>Development mode</strong>, only app admins/testers can connect.
                                Switch to <strong>Live</strong> so all tenants can use it.
                            </div>
                            <ul class="sub-steps">
                                <li>App dashboard → top toggle: <strong>Development → Live</strong></li>
                                <li>You may need to agree to Meta's Platform Policy</li>
                                <li>Some permissions require App Review before Live mode allows them for all users</li>
                            </ul>
                            <div class="step-note">
                                <svg viewBox="0 0 20 20" fill="currentColor" style="width:15px;height:15px;flex-shrink:0;margin-top:1px;"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                                <span>For internal/B2B use (where you know your tenants), Development mode works fine — just add each tenant's Facebook account as an <strong>App Tester</strong> in App Roles.</span>
                            </div>
                        </div>
                    </div>

                    {{-- ── STEP 7 ───────────────────────────────────── --}}
                    <div class="step-item">
                        <div class="step-spine">
                            <div class="step-num green" style="background:#25d366;border-color:#25d366;color:#fff;">7</div>
                        </div>
                        <div class="step-body" style="padding-bottom:8px;">
                            <div class="step-title">Copy &amp; Save App ID + Secret ← <span style="color:#6FEC9D;">Do this now</span></div>
                            <div class="step-desc">
                                Go to <strong>App → Settings → Basic</strong>.
                            </div>
                            <ul class="sub-steps">
                                <li>Copy <strong>App ID</strong> — visible directly on the page</li>
                                <li>Click <strong>"Show"</strong> next to App Secret → copy it</li>
                                <li>Paste both in the <strong>form on the left</strong> → Save</li>
                            </ul>
                            <div class="step-note success" style="margin-top:10px;">
                                <svg viewBox="0 0 20 20" fill="currentColor" style="width:15px;height:15px;flex-shrink:0;margin-top:1px;"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                <span>Once saved, tenants can immediately go to their <strong>WhatsApp API Settings</strong> or <strong>Instagram Settings</strong> and connect with QR code — no further superadmin action needed per tenant.</span>
                            </div>
                        </div>
                    </div>

                </div>{{-- end step-list --}}

                {{-- Quick FAQ --}}
                <div style="margin-top:8px;padding-top:20px;border-top:1px solid var(--border-subtle);">
                    <div class="section-label">Common Questions</div>
                    <div style="display:flex;flex-direction:column;gap:14px;">
                        <div>
                            <div style="font-size:13px;font-weight:600;color:var(--text-200);margin-bottom:3px;">Can I use one Meta App for both WhatsApp and Instagram?</div>
                            <div style="font-size:13px;color:var(--text-300);line-height:1.5;">Yes — add both WhatsApp and Instagram products to the same app. This is the recommended setup.</div>
                        </div>
                        <div>
                            <div style="font-size:13px;font-weight:600;color:var(--text-200);margin-bottom:3px;">Do tenants need their own Meta developer account?</div>
                            <div style="font-size:13px;color:var(--text-300);line-height:1.5;">No. Tenants only need a <strong>Facebook Business account</strong> that manages their WhatsApp Business Account (WABA) or Instagram Business Page. They don't touch Meta Developer at all.</div>
                        </div>
                        <div>
                            <div style="font-size:13px;font-weight:600;color:var(--text-200);margin-bottom:3px;">What if a tenant gets "App not approved" error?</div>
                            <div style="font-size:13px;color:var(--text-300);line-height:1.5;">Either add the tenant's Facebook account as an <strong>App Tester</strong> in your Meta App → App Roles, or complete App Review to go Live.</div>
                        </div>
                        <div>
                            <div style="font-size:13px;font-weight:600;color:var(--text-200);margin-bottom:3px;">Webhook verification fails?</div>
                            <div style="font-size:13px;color:var(--text-300);line-height:1.5;">Get the <strong>Verify Token</strong> from the specific tenant's WhatsApp/Instagram Settings page and use that exact token in Meta Webhook config. Each tenant has a unique token.</div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
function copyText(id) {
    const text = document.getElementById(id).textContent.trim();
    navigator.clipboard.writeText(text).then(() => {
        const el = document.getElementById(id);
        el.style.background = '#dcfce7';
        setTimeout(() => el.style.background = '', 1400);
    });
}
</script>
@endpush
