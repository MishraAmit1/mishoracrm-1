@extends('layouts.app')
@section('title', 'WhatsApp API Settings')

@push('styles')
<style>
/* ── Layout ───────────────────────────────────────────────────── */
.wa-settings-grid { display:grid; grid-template-columns:1fr 1fr; gap:20px; }
@media(max-width:720px){ .wa-settings-grid { grid-template-columns:1fr; } }

/* ── Coexistence banner ───────────────────────────────────────── */
.coex-banner {
    background:var(--green-dim);
    border:1.5px solid rgba(45,212,160,0.35);
    border-radius:var(--r-lg);
    padding:0;
    margin-bottom:20px;
    overflow:hidden;
}
.coex-banner-header {
    display:flex; align-items:center; justify-content:space-between;
    padding:16px 20px;
    cursor:pointer;
    user-select:none;
}
.coex-banner-title {
    display:flex; align-items:center; gap:10px;
    font-weight:700; font-size:14px; color:var(--green);
}
.coex-banner-sub { font-size:12px; color:var(--text-200); font-weight:400; margin-top:2px; }
.coex-chevron { color:var(--green); transition:transform .25s; flex-shrink:0; }
.coex-chevron.open { transform:rotate(180deg); }

.coex-body {
    padding:0 20px 20px;
    border-top:1px solid rgba(45,212,160,0.35);
}

/* ── Method tabs ──────────────────────────────────────────────── */
.method-tabs { display:flex; gap:0; margin-bottom:16px; border-bottom:2px solid var(--border-subtle); }
.method-tab {
    padding:10px 18px; font-size:13px; font-weight:600; cursor:pointer;
    color:var(--text-300); border-bottom:2px solid transparent; margin-bottom:-2px;
    transition:all .15s;
}
.method-tab.active { color:var(--green); border-bottom-color:#25d366; }

.method-panel { display:none; }
.method-panel.active { display:block; }

/* ── Steps inside coexistence ─────────────────────────────────── */
.coex-steps { list-style:none; padding:0; margin:0; display:flex; flex-direction:column; gap:10px; }
.coex-step { display:flex; gap:10px; align-items:flex-start; }
.coex-step-num {
    min-width:24px; height:24px; border-radius:50%;
    background:#25d366; color:#fff;
    font-size:11px; font-weight:700;
    display:flex; align-items:center; justify-content:center;
    flex-shrink:0; margin-top:1px;
}
.coex-step-text { font-size:13px; color:var(--text-200); line-height:1.5; }
.coex-step-text strong { color:var(--text-100); }
.coex-step-text .path {
    display:inline-block; background:var(--bg-subtle); border:1px solid var(--border-subtle);
    border-radius:4px; padding:1px 7px; font-size:12px; font-family:var(--mono);
    color:var(--text-200); margin:0 1px;
}

.coex-note {
    display:flex; gap:8px; align-items:flex-start;
    background:var(--green-dim); border:1px solid rgba(45,212,160,0.35);
    border-radius:var(--r-md); padding:10px 14px;
    font-size:12px; color:var(--text-200); line-height:1.5;
    margin-top:14px;
}

/* ── Warning / prerequisite boxes ─────────────────────────────── */
.coex-warn {
    display:flex; gap:10px; align-items:flex-start;
    background:var(--amber-dim); border:1px solid rgba(248,184,78,0.4);
    border-radius:var(--r-md); padding:14px 16px;
    margin-top:16px;
}
.coex-warn > svg { color:var(--amber); margin-top:2px; }
.coex-warn-title { font-size:12.5px; font-weight:700; color:var(--amber); margin-bottom:6px; }
.coex-warn-list { list-style:none; margin:0; padding:0; display:flex; flex-direction:column; gap:5px; }
.coex-warn-list li { display:flex; gap:7px; align-items:flex-start; font-size:12px; color:var(--text-200); line-height:1.5; }
.coex-warn-list li::before { content:'!'; flex-shrink:0; width:14px; height:14px; margin-top:1px; border-radius:50%; background:rgba(248,184,78,0.35); color:var(--text-200); font-size:9.5px; font-weight:800; display:flex; align-items:center; justify-content:center; }
.coex-warn-list strong { color:var(--text-100); }

/* ── Prerequisites checklist ─────────────────────────────────── */
.coex-prereq-title {
    font-size:12px; font-weight:700; letter-spacing:.04em; text-transform:uppercase;
    color:var(--text-300); margin-bottom:10px;
}
.coex-prereq-grid { display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-bottom:22px; }
@media(max-width:640px){ .coex-prereq-grid { grid-template-columns:1fr; } }
.coex-prereq-card {
    display:flex; gap:10px; align-items:flex-start;
    background:var(--bg-surface); border:1px solid var(--border-subtle);
    border-radius:var(--r-md); padding:12px 14px;
}
.coex-prereq-icon {
    width:26px; height:26px; border-radius:8px; flex-shrink:0;
    background:rgba(37,211,102,0.12); color:#25d366;
    display:flex; align-items:center; justify-content:center;
}
.coex-prereq-text { font-size:12.5px; color:var(--text-200); line-height:1.5; }
.coex-prereq-text strong { color:var(--text-100); display:block; font-size:13px; margin-bottom:1px; }
.coex-prereq-card { flex-direction:column; align-items:stretch; }
.coex-prereq-card-head { display:flex; gap:10px; align-items:flex-start; }
.coex-prereq-link {
    display:inline-flex; align-items:center; gap:5px;
    margin-top:9px; align-self:flex-start;
    font-size:12px; font-weight:600; color:var(--accent);
    text-decoration:none; padding:5px 10px;
    background:var(--accent-dim); border-radius:20px;
    transition:background .15s;
}
.coex-prereq-link:hover { background:var(--accent-glow); }
.coex-prereq-link svg { width:11px; height:11px; }

/* ── Section divider label (Part 1 / Part 2) ────────────────────── */
.coex-section-label {
    display:flex; align-items:center; gap:10px;
    margin:22px 0 12px;
}
.coex-section-label:first-of-type { margin-top:0; }
.coex-section-label .pill {
    flex-shrink:0; display:flex; align-items:center; gap:6px;
    background:rgba(37,211,102,0.12); color:#1FA463;
    border-radius:20px; padding:4px 12px 4px 8px;
    font-size:12px; font-weight:700;
}
.coex-section-label .pill .num {
    width:18px; height:18px; border-radius:50%; background:#25d366; color:#fff;
    font-size:10px; font-weight:800; display:flex; align-items:center; justify-content:center;
}
.coex-section-label .line { flex:1; height:1px; background:var(--border-subtle); }

/* ── Connect wizard card ──────────────────────────────────────── */
.wa-connect-card {
    background:var(--bg-surface);
    border:1.5px solid var(--border-default);
    border-radius:var(--r-lg);
    overflow:hidden;
    margin-bottom:20px;
}
.wa-connect-header {
    display:flex; align-items:center; justify-content:space-between;
    padding:18px 20px 14px;
    border-bottom:1px solid var(--border-subtle);
}
.wa-connect-title {
    display:flex; align-items:center; gap:10px;
    font-weight:700; font-size:15px; color:var(--text-100);
}
.wa-badge-connected    { background:var(--green-dim); color:var(--green); border-radius:20px; padding:3px 10px; font-size:12px; font-weight:600; }
.wa-badge-disconnected { background:var(--red-dim); color:var(--red); border-radius:20px; padding:3px 10px; font-size:12px; font-weight:600; }

/* ── Steps bar ────────────────────────────────────────────────── */
.wa-steps {
    display:flex; align-items:flex-start; gap:0;
    padding:20px 24px 0;
    position:relative;
}
.wa-steps::before {
    content:'';
    position:absolute;
    top:34px; left:calc(24px + 14px); right:calc(24px + 14px);
    height:2px; background:var(--border-subtle);
    z-index:0;
}
.wa-step { flex:1; display:flex; flex-direction:column; align-items:center; gap:8px; position:relative; z-index:1; }
.wa-step-dot {
    width:28px; height:28px; border-radius:50%;
    display:flex; align-items:center; justify-content:center;
    font-size:12px; font-weight:700;
    border:2px solid var(--border-default);
    background:var(--bg-surface); color:var(--text-300);
    transition:all .2s;
}
.wa-step.active .wa-step-dot { background:#25d366; border-color:#25d366; color:#fff; }
.wa-step.done   .wa-step-dot { background:var(--green-dim); border-color:var(--green); color:var(--green); }
.wa-step-label { font-size:11px; color:var(--text-400); text-align:center; line-height:1.3; max-width:80px; }
.wa-step.active .wa-step-label { color:var(--green); font-weight:600; }
.wa-step.done   .wa-step-label { color:var(--green); }

/* ── QR body ──────────────────────────────────────────────────── */
.wa-connect-body { padding:24px; display:flex; gap:32px; align-items:flex-start; flex-wrap:wrap; }
.wa-qr-col { display:flex; flex-direction:column; align-items:center; gap:12px; }
.wa-qr-frame {
    width:200px; height:200px; border-radius:14px;
    border:2px solid var(--border-subtle); background:var(--bg-subtle);
    display:flex; align-items:center; justify-content:center;
    overflow:hidden; position:relative;
}
.wa-qr-frame img { width:100%; height:100%; display:block; }
.wa-qr-placeholder { display:flex; flex-direction:column; align-items:center; gap:10px; color:var(--text-400); }
.wa-qr-placeholder svg { opacity:.35; }
.wa-qr-placeholder span { font-size:12px; text-align:center; max-width:120px; line-height:1.4; }
.wa-timer { font-size:12px; color:var(--text-300); text-align:center; }
.wa-timer strong { color:var(--text-200); }

/* ── Info col ─────────────────────────────────────────────────── */
.wa-info-col { flex:1; min-width:220px; }
.wa-info-title { font-weight:700; font-size:16px; color:var(--text-100); margin-bottom:6px; }
.wa-info-sub   { font-size:13px; color:var(--text-300); margin-bottom:20px; line-height:1.5; }
.wa-how-list { list-style:none; padding:0; margin:0 0 20px; display:flex; flex-direction:column; gap:12px; }
.wa-how-item { display:flex; align-items:flex-start; gap:10px; }
.wa-how-num {
    min-width:22px; height:22px; border-radius:50%;
    background:var(--green-dim); border:1.5px solid var(--green);
    color:var(--green); font-size:11px; font-weight:700;
    display:flex; align-items:center; justify-content:center; margin-top:1px;
}
.wa-how-text { font-size:13px; color:var(--text-200); line-height:1.45; }
.wa-how-text strong { color:var(--text-100); }

/* ── Status indicators ────────────────────────────────────────── */
.wa-status-waiting {
    display:flex; align-items:center; gap:8px;
    padding:10px 14px; background:var(--amber-dim); border:1px solid rgba(248,184,78,0.4);
    border-radius:var(--r-md); font-size:13px; color:var(--amber);
}
.wa-pulse { width:8px; height:8px; border-radius:50%; background:var(--amber); animation:waPulse 1.4s ease-in-out infinite; flex-shrink:0; }
.wa-status-success {
    display:flex; align-items:center; gap:8px;
    padding:10px 14px; background:var(--green-dim); border:1px solid rgba(45,212,160,0.4);
    border-radius:var(--r-md); font-size:13px; color:var(--green); font-weight:600;
}

/* ── Connected state ──────────────────────────────────────────── */
.wa-connected-body { padding:24px; display:flex; gap:24px; align-items:center; flex-wrap:wrap; }
.wa-connected-icon { width:64px; height:64px; border-radius:50%; background:var(--green-dim); display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.wa-connected-info { flex:1; min-width:200px; }
.wa-connected-info h3 { font-weight:700; font-size:16px; color:var(--green); margin:0 0 6px; }
.wa-meta-row { display:flex; gap:20px; flex-wrap:wrap; margin-top:10px; }
.wa-meta-item { font-size:12px; color:var(--text-300); }
.wa-meta-item strong { color:var(--text-200); display:block; font-size:11px; margin-bottom:2px; letter-spacing:.03em; text-transform:uppercase; }

/* ── Misc ─────────────────────────────────────────────────────── */
.webhook-box { background:var(--bg-subtle); border:1px solid var(--border-subtle); border-radius:var(--r-md); padding:10px 12px; font-family:var(--mono); font-size:12px; word-break:break-all; color:var(--text-200); }
.copy-btn { cursor:pointer; background:none; border:none; color:var(--text-300); padding:4px; }
.copy-btn:hover { color:var(--accent); }

@keyframes waPulse { 0%,100%{opacity:1} 50%{opacity:.25} }
</style>
@endpush

@section('content')
<div id="fb-root"></div>
<div class="page-header">
    <div>
        <h1 class="page-title">WhatsApp Business API</h1>
        <p class="page-sub">Connect Meta WhatsApp Cloud API — phone app + CRM dono ek saath chalega</p>
    </div>
    <a href="{{ route('tenant.whatsapp.index') }}" class="btn btn-ghost">← Back</a>
</div>

@if(session('success'))
    <div class="alert alert-success" style="margin-bottom:20px;">{{ session('success') }}</div>
@endif

{{-- ══════════════════════════════════════════════════════════════
     COEXISTENCE GUIDE — Phone App + CRM dono saath
══════════════════════════════════════════════════════════════ --}}
<div class="coex-banner">
    <div class="coex-banner-header" onclick="toggleCoex()" id="coexHeader">
        <div>
            <div class="coex-banner-title">
                <svg viewBox="0 0 24 24" fill="none" stroke="var(--green)" stroke-width="2" style="width:20px;height:20px;flex-shrink:0;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Coexistence Mode — Same Number: Phone App + CRM dono ek saath
            </div>
            <div class="coex-banner-sub">
                Aap same WhatsApp Business number apne phone pe bhi rakh sakte hain aur CRM automation bhi chala sakte hain — <strong>number change karne ki zaroorat nahi</strong>.
                &nbsp;Setup guide dekhne ke liye click karein ↓
            </div>
        </div>
        <svg id="coexChevron" class="coex-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="width:18px;height:18px;">
            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
        </svg>
    </div>

    <div id="coexBody" class="coex-body" style="display:none;">

        {{-- What coexistence means --}}
        <div style="display:flex;gap:16px;flex-wrap:wrap;margin-bottom:20px;padding-top:4px;">
            <div style="flex:1;min-width:200px;background:var(--green-dim);border:1px solid rgba(45,212,160,0.35);border-radius:var(--r-md);padding:14px 16px;">
                <div style="font-size:12px;font-weight:700;color:var(--green);margin-bottom:6px;">
                    <svg viewBox="0 0 20 20" fill="#25d366" style="width:14px;height:14px;display:inline;margin-right:4px;vertical-align:middle;"><path d="M2 5a2 2 0 012-2h7a2 2 0 012 2v4a2 2 0 01-2 2H9l-3 3v-3H4a2 2 0 01-2-2V5z"/><path d="M15 7v2a4 4 0 01-4 4H9.828l-1.766 1.767c.28.149.599.233.938.233h2l3 3v-3h2a2 2 0 002-2V9a2 2 0 00-2-2h-1z"/></svg>
                    WhatsApp Business App (Phone)
                </div>
                <div style="font-size:12px;color:var(--text-200);line-height:1.5;">
                    ✓ Manual chats as usual<br>
                    ✓ Incoming messages dikhenge<br>
                    ✓ Manually reply kar sakte hain<br>
                    ✓ App normally kaam karta hai
                </div>
            </div>
            <div style="display:flex;align-items:center;font-size:20px;color:var(--green);padding:0 4px;">+</div>
            <div style="flex:1;min-width:200px;background:var(--green-dim);border:1px solid rgba(45,212,160,0.35);border-radius:var(--r-md);padding:14px 16px;">
                <div style="font-size:12px;font-weight:700;color:var(--green);margin-bottom:6px;">
                    <svg viewBox="0 0 20 20" fill="var(--accent)" style="width:14px;height:14px;display:inline;margin-right:4px;vertical-align:middle;"><path fill-rule="evenodd" d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z" clip-rule="evenodd"/></svg>
                    CRM (WhatsApp Cloud API)
                </div>
                <div style="font-size:12px;color:var(--text-200);line-height:1.5;">
                    ✓ Chatbot auto-reply karta hai<br>
                    ✓ Incoming messages webhook pe aate hain<br>
                    ✓ CRM se messages bhejna
                </div>
            </div>
        </div>

        {{-- Prerequisites --}}
        <div class="coex-section-label" style="margin-top:0;">
            <span class="pill"><span class="num">0</span> Pehle ye accounts ready karein</span>
            <span class="line"></span>
        </div>
        <div class="coex-prereq-title" style="margin-top:-4px;">Neeche diye link se direct login ya naya account bana sakte hain</div>
        <div class="coex-prereq-grid">
            <div class="coex-prereq-card">
                <div class="coex-prereq-card-head">
                    <div class="coex-prereq-icon">
                        <svg viewBox="0 0 20 20" fill="currentColor" style="width:14px;height:14px;"><path d="M10 8a3 3 0 100-6 3 3 0 000 6zM3.465 14.493a1.23 1.23 0 00.41 1.412A9.957 9.957 0 0010 18c2.31 0 4.438-.784 6.131-2.1.43-.333.604-.903.408-1.41a7.002 7.002 0 00-13.074.003z"/></svg>
                    </div>
                    <div class="coex-prereq-text">
                        <strong>Facebook (personal) account</strong>
                        Isi se aage login karke permissions allow karni hain — nahi hai toh yahin se bana lein.
                    </div>
                </div>
                <a href="https://www.facebook.com/" target="_blank" rel="noopener" class="coex-prereq-link">
                    Facebook par login/create karein
                    <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M12.293 4.293a1 1 0 011.414 0l5 5a1 1 0 010 1.414l-5 5a1 1 0 01-1.414-1.414L15.586 11H4a1 1 0 110-2h11.586l-3.293-3.293a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                </a>
            </div>
            <div class="coex-prereq-card">
                <div class="coex-prereq-card-head">
                    <div class="coex-prereq-icon">
                        <svg viewBox="0 0 20 20" fill="currentColor" style="width:14px;height:14px;"><path fill-rule="evenodd" d="M4 4a2 2 0 012-2h8a2 2 0 012 2v12a1 1 0 01-1.447.894L10 15.118l-4.553 1.776A1 1 0 014 16V4z" clip-rule="evenodd"/></svg>
                    </div>
                    <div class="coex-prereq-text">
                        <strong>Meta Business Manager</strong>
                        Aapka account uska Admin ho. Nahi hai toh chinta mat karein — "WhatsApp Business App se" tarike mein Meta khud ek bana deta hai, ya yahan se manually bana lein.
                    </div>
                </div>
                <a href="https://business.facebook.com/" target="_blank" rel="noopener" class="coex-prereq-link">
                    Business Manager login/create karein
                    <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M12.293 4.293a1 1 0 011.414 0l5 5a1 1 0 010 1.414l-5 5a1 1 0 01-1.414-1.414L15.586 11H4a1 1 0 110-2h11.586l-3.293-3.293a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                </a>
            </div>
            <div class="coex-prereq-card">
                <div class="coex-prereq-card-head">
                    <div class="coex-prereq-icon">
                        <svg viewBox="0 0 20 20" fill="currentColor" style="width:14px;height:14px;"><path fill-rule="evenodd" d="M6 2a2 2 0 00-2 2v12a2 2 0 002 2h8a2 2 0 002-2V4a2 2 0 00-2-2H6zm2 12a1 1 0 100 2h4a1 1 0 100-2H8z" clip-rule="evenodd"/></svg>
                    </div>
                    <div class="coex-prereq-text">
                        <strong>WhatsApp Business App</strong>
                        Phone par installed ho, latest version tak updated — normal WhatsApp nahi, "Business" wala app chahiye.
                    </div>
                </div>
                <a href="https://www.whatsapp.com/business/download" target="_blank" rel="noopener" class="coex-prereq-link">
                    App download/update karein
                    <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M12.293 4.293a1 1 0 011.414 0l5 5a1 1 0 010 1.414l-5 5a1 1 0 01-1.414-1.414L15.586 11H4a1 1 0 110-2h11.586l-3.293-3.293a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                </a>
            </div>
            <div class="coex-prereq-card">
                <div class="coex-prereq-card-head">
                    <div class="coex-prereq-icon">
                        <svg viewBox="0 0 20 20" fill="currentColor" style="width:14px;height:14px;"><path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z"/></svg>
                    </div>
                    <div class="coex-prereq-text">
                        <strong>Active number</strong>
                        Jo number connect karna hai wo abhi WhatsApp Business App par active ho, kisi aur WABA/API se juda na ho.
                    </div>
                </div>
            </div>
        </div>

        {{-- Method tabs --}}
        <div style="font-size:13px;font-weight:600;color:var(--text-200);margin-bottom:10px;">
            Coexistence enable karne ke do tarike hain — CRM se connect karne se PEHLE ye karna hai:
        </div>

        <div class="method-tabs">
            <div class="method-tab active" onclick="switchMethod('app')" id="tab-app">
                📱 WhatsApp Business App se (Easiest)
            </div>
            <div class="method-tab" onclick="switchMethod('bm')" id="tab-bm">
                🌐 Meta Business Manager se
            </div>
        </div>

        {{-- Method A: Via WhatsApp Business App --}}
        <div class="method-panel active" id="panel-app">
            <div class="coex-section-label">
                <span class="pill"><span class="num">1</span> Phone par coexistence link karein</span>
                <span class="line"></span>
            </div>
            <ul class="coex-steps">
                <li class="coex-step">
                    <div class="coex-step-num">1</div>
                    <div class="coex-step-text">
                        Apne phone pe <strong>WhatsApp Business App</strong> kholein
                    </div>
                </li>
                <li class="coex-step">
                    <div class="coex-step-num">2</div>
                    <div class="coex-step-text">
                        <strong>Business Tools</strong> tak pahunchein — naye app version mein bottom mein ek alag <span class="path">Tools</span> tab (camera icon ki jagah) direct de deta hai; purane version mein <strong>3 dots (⋮)</strong>/<strong>Settings gear</strong> → <span class="path">Settings</span> → <span class="path">Business Tools</span> se milega
                    </div>
                </li>
                <li class="coex-step">
                    <div class="coex-step-num">3</div>
                    <div class="coex-step-text">
                        <span class="path">WhatsApp Business Platform</span> (kahin-kahin abhi bhi <span class="path">WhatsApp Business API</span> naam se dikhta hai) pe tap karein
                    </div>
                </li>
                <li class="coex-step">
                    <div class="coex-step-num">4</div>
                    <div class="coex-step-text">
                        <strong>"Connect your existing WhatsApp Business app account"</strong> ya
                        <strong>"Continue using WhatsApp Business App"</strong> option select karein — <strong>naya account ya naya number CREATE mat karein</strong>, apna existing number hi link karna hai
                    </div>
                </li>
                <li class="coex-step">
                    <div class="coex-step-num">5</div>
                    <div class="coex-step-text">
                        Apne <strong>Facebook account</strong> se login karein → jo Business Manager aapko dikhe usko select karein (ya naya bana lein) → permissions allow karein
                    </div>
                </li>
            </ul>

            <div class="coex-section-label">
                <span class="pill"><span class="num">2</span> CRM se QR scan karke connect karein</span>
                <span class="line"></span>
            </div>
            <ul class="coex-steps">
                <li class="coex-step">
                    <div class="coex-step-num">6</div>
                    <div class="coex-step-text">
                        Neeche <strong>"Generate QR"</strong> button dabayein — CRM ek QR code dikhayega
                    </div>
                </li>
                <li class="coex-step">
                    <div class="coex-step-num">7</div>
                    <div class="coex-step-text">
                        WhatsApp Business App mein wahi flow (Step 3-5) se dobara jayein — ab app camera khol dega, isse <strong>CRM wala QR code scan karein</strong>
                    </div>
                </li>
                <li class="coex-step">
                    <div class="coex-step-num">8</div>
                    <div class="coex-step-text">
                        Phone par ek message/prompt aayega — <strong>"Connect"</strong> pe tap karein
                    </div>
                </li>
                <li class="coex-step">
                    <div class="coex-step-num">9</div>
                    <div class="coex-step-text">
                        Uske baad <strong>"Confirm"</strong> screen aayegi jisme chat history CRM/partner ke saath share karne ka option milega — apni marzi se allow ya skip karein (isse coexistence connect hona nahi rukta)
                    </div>
                </li>
                <li class="coex-step">
                    <div class="coex-step-num">10</div>
                    <div class="coex-step-text">
                        Confirm karte hi CRM ki screen apne aap <strong>"Connected"</strong> dikha degi — number, dono jagah (phone + CRM) ek saath kaam karne lagega
                    </div>
                </li>
            </ul>

            <div class="coex-note">
                <svg viewBox="0 0 20 20" fill="currentColor" style="width:15px;height:15px;flex-shrink:0;margin-top:1px;color:var(--green);"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>
                <span>
                    Agar ye option nahi dikh raha, toh app update karein — Play Store/App Store se latest WhatsApp Business App version lein.
                    Meta samay-samay pe menu ka naam/jagah badalta rehta hai, par matlab wahi rehta hai: "connect to WhatsApp Business Platform / API".
                </span>
            </div>

            <div class="coex-warn">
                <svg viewBox="0 0 20 20" fill="currentColor" style="width:16px;height:16px;flex-shrink:0;"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                <div style="flex:1;">
                    <div class="coex-warn-title">Connect karne se pehle ye jaan lein — Meta khud ye karta hai, CRM ki wajah se nahi</div>
                    <ul class="coex-warn-list">
                        <li><span><strong>Linked/companion devices</strong> (WhatsApp Web, Desktop app) automatically unlink ho jaayenge — connect hone ke baad dobara link karne honge</span></li>
                        <li><span><strong>Disappearing messages</strong> sab 1:1 chats mein off ho jaayengi</span></li>
                        <li><span><strong>View-once messages</strong> disable ho jaayenge</span></li>
                        <li><span><strong>Broadcast lists</strong> read-only ho jaayengi — nayi broadcast list nahi bana sakenge</span></li>
                        <li><span>Agar number <strong>Meta-verified (green tick)</strong> hai toh badge temporarily hat sakta hai — baad mein dobara apply kiya ja sakta hai</span></li>
                    </ul>
                </div>
            </div>
        </div>

        {{-- Method B: Via Meta Business Manager --}}
        <div class="method-panel" id="panel-bm">
            <ul class="coex-steps">
                <li class="coex-step">
                    <div class="coex-step-num">1</div>
                    <div class="coex-step-text">
                        Browser mein <strong>business.facebook.com</strong> kholen aur apne Business Manager account se login karein
                    </div>
                </li>
                <li class="coex-step">
                    <div class="coex-step-num">2</div>
                    <div class="coex-step-text">
                        Left sidebar → <span class="path">Business Settings</span> → <span class="path">Accounts</span> → <span class="path">WhatsApp Accounts</span>
                    </div>
                </li>
                <li class="coex-step">
                    <div class="coex-step-num">3</div>
                    <div class="coex-step-text">
                        Apna WhatsApp Business Account (WABA) select karein → <span class="path">Settings</span> → <span class="path">Phone Numbers</span>
                    </div>
                </li>
                <li class="coex-step">
                    <div class="coex-step-num">4</div>
                    <div class="coex-step-text">
                        Apna number click karein → <span class="path">API Setup</span> ya <span class="path">Configure</span> → <strong>"Coexistence"</strong> option enable karein
                    </div>
                </li>
                <li class="coex-step">
                    <div class="coex-step-num">5</div>
                    <div class="coex-step-text">
                        Save karein — ab aapka number coexistence mode mein hai
                    </div>
                </li>
                <li class="coex-step">
                    <div class="coex-step-num">6</div>
                    <div class="coex-step-text">
                        <strong>Neeche CRM se QR code scan karke connect karein</strong>
                    </div>
                </li>
            </ul>
            <div class="coex-note">
                <svg viewBox="0 0 20 20" fill="currentColor" style="width:15px;height:15px;flex-shrink:0;margin-top:1px;color:var(--green);"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                <span>
                    Agar aapne pehle number ko API pe migrate kar diya tha (without coexistence), toh WhatsApp Business App pe wapas laane ke liye Meta Business Manager mein number re-register karna hoga.
                </span>
            </div>
        </div>

    </div>{{-- end coex-body --}}
</div>

{{-- ══════════════════════════════════════════════════════════════
     CONNECT WIZARD CARD
══════════════════════════════════════════════════════════════ --}}
<div class="wa-connect-card" id="waConnectCard">

    <div class="wa-connect-header">
        <div class="wa-connect-title">
            <svg viewBox="0 0 24 24" fill="#25d366" style="width:22px;height:22px;flex-shrink:0;">
                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/>
                <path d="M12.004 2C6.477 2 2 6.477 2 12.004c0 1.773.465 3.48 1.348 4.985L2 22l5.13-1.34A9.953 9.953 0 0012.004 22C17.527 22 22 17.523 22 12c0-5.522-4.473-10-9.996-10z" fill-rule="evenodd" clip-rule="evenodd"/>
            </svg>
            Step 2 — CRM se Connect Karein
        </div>
        @if($settings->is_connected)
            <span class="wa-badge-connected">● Connected</span>
        @else
            <span class="wa-badge-disconnected">● Not Connected</span>
        @endif
    </div>

    {{-- Steps bar --}}
    <div class="wa-steps" id="waStepsBar">
        <div class="wa-step {{ $settings->is_connected ? 'done' : 'active' }}" id="step1">
            <div class="wa-step-dot">
                @if($settings->is_connected)
                    <svg viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2" style="width:12px;height:12px;"><path stroke-linecap="round" stroke-linejoin="round" d="M2 6l3 3 5-5"/></svg>
                @else 1 @endif
            </div>
            <div class="wa-step-label">Generate QR</div>
        </div>
        <div class="wa-step {{ $settings->is_connected ? 'done' : '' }}" id="step2">
            <div class="wa-step-dot">
                @if($settings->is_connected)
                    <svg viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2" style="width:12px;height:12px;"><path stroke-linecap="round" stroke-linejoin="round" d="M2 6l3 3 5-5"/></svg>
                @else 2 @endif
            </div>
            <div class="wa-step-label">Scan Phone</div>
        </div>
        <div class="wa-step {{ $settings->is_connected ? 'done' : '' }}" id="step3">
            <div class="wa-step-dot">
                @if($settings->is_connected)
                    <svg viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2" style="width:12px;height:12px;"><path stroke-linecap="round" stroke-linejoin="round" d="M2 6l3 3 5-5"/></svg>
                @else 3 @endif
            </div>
            <div class="wa-step-label">Facebook Allow</div>
        </div>
        <div class="wa-step {{ $settings->is_connected ? 'done' : '' }}" id="step4">
            <div class="wa-step-dot">
                @if($settings->is_connected)
                    <svg viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2" style="width:12px;height:12px;"><path stroke-linecap="round" stroke-linejoin="round" d="M2 6l3 3 5-5"/></svg>
                @else 4 @endif
            </div>
            <div class="wa-step-label">Auto Connected!</div>
        </div>
    </div>

    @if($settings->is_connected)
        {{-- CONNECTED --}}
        <div class="wa-connected-body">
            <div class="wa-connected-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="var(--green)" stroke-width="2.5" style="width:32px;height:32px;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                </svg>
            </div>
            <div class="wa-connected-info">
                <h3>WhatsApp Business Connected</h3>
                <p style="font-size:13px;color:var(--text-300);margin:0;">Coexistence mode — phone app + CRM automation dono kaam kar rahe hain.</p>
                <div class="wa-meta-row">
                    <div class="wa-meta-item"><strong>Connected Number</strong>{{ $settings->display_phone_number ?? 'Unknown — click Test Connection' }}</div>
                    <div class="wa-meta-item"><strong>Verified Name</strong>{{ $settings->verified_name ?? '—' }}</div>
                    <div class="wa-meta-item"><strong>Phone Number ID</strong>{{ $settings->phone_number_id ?? '—' }}</div>
                    <div class="wa-meta-item"><strong>WABA ID</strong>{{ $settings->waba_id ?? '—' }}</div>
                </div>
            </div>
            <button type="button" class="btn btn-ghost btn-sm" onclick="startQrFlow()" id="reconnectBtn">
                Reconnect / Change Number
            </button>
        </div>
        <div id="qrReconnectArea" style="display:none;padding:0 24px 24px;">
            @if($metaAppId && $metaConfigId)
            <div style="margin-bottom:16px;">
                <button type="button" class="btn btn-primary" id="esConnectBtn" onclick="connectWhatsAppEmbedded()"
                    style="gap:8px;display:inline-flex;align-items:center;background:#25d366;border-color:#25d366;">
                    Connect WhatsApp (Instant)
                </button>
                <span style="font-size:11px;color:var(--text-400);margin-left:10px;">— ya neeche QR se connect karein</span>
            </div>
            @endif
            <div style="display:flex;gap:24px;align-items:flex-start;flex-wrap:wrap;">
                <div class="wa-qr-col">
                    <div class="wa-qr-frame"><img id="qrImg" src="" alt="QR Code"></div>
                    <div class="wa-timer" id="qrTimer">Valid for <strong id="qrCountdown">10:00</strong></div>
                    <button type="button" class="btn btn-ghost btn-sm" id="qrRefreshBtn" style="display:none;" onclick="startQrFlow()">New QR Code</button>
                </div>
                <div class="wa-info-col" style="min-width:180px;">
                    <div class="wa-status-waiting" id="qrConnecting">
                        <span class="wa-pulse"></span>Waiting for authorization…
                    </div>
                    <div class="wa-status-success" id="qrDone" style="display:none;">
                        <svg viewBox="0 0 20 20" fill="currentColor" style="width:16px;height:16px;flex-shrink:0;"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                        Connected! Reloading…
                    </div>
                </div>
            </div>
        </div>

    @else
        {{-- NOT CONNECTED --}}
        <div class="wa-connect-body">
            <div class="wa-qr-col">
                <div class="wa-qr-frame" id="qrFrame">
                    <div class="wa-qr-placeholder" id="qrPlaceholder">
                        <svg viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg" style="width:64px;height:64px;">
                            <rect x="4" y="4" width="28" height="28" rx="3" stroke="currentColor" stroke-width="3"/>
                            <rect x="11" y="11" width="14" height="14" rx="1" fill="currentColor"/>
                            <rect x="48" y="4" width="28" height="28" rx="3" stroke="currentColor" stroke-width="3"/>
                            <rect x="55" y="11" width="14" height="14" rx="1" fill="currentColor"/>
                            <rect x="4" y="48" width="28" height="28" rx="3" stroke="currentColor" stroke-width="3"/>
                            <rect x="11" y="55" width="14" height="14" rx="1" fill="currentColor"/>
                            <rect x="48" y="48" width="8" height="8" rx="1" fill="currentColor"/>
                            <rect x="62" y="48" width="8" height="8" rx="1" fill="currentColor"/>
                            <rect x="48" y="62" width="8" height="8" rx="1" fill="currentColor"/>
                            <rect x="62" y="62" width="8" height="8" rx="1" fill="currentColor"/>
                        </svg>
                        <span>"Connect Now" click karein</span>
                    </div>
                    <img id="qrImg" src="" alt="QR Code" style="display:none;width:100%;height:100%;">
                </div>
                <div class="wa-timer" id="qrTimer" style="display:none;">Valid for <strong id="qrCountdown">10:00</strong></div>
                <button type="button" class="btn btn-ghost btn-sm" id="qrRefreshBtn" style="display:none;" onclick="startQrFlow()">New QR Code</button>
            </div>

            <div class="wa-info-col">
                <div class="wa-info-title">CRM se Connect Karein</div>
                <div class="wa-info-sub">
                    Coexistence enable ho jaane ke baad — neeche button click karein aur QR scan karein.
                </div>
                <ul class="wa-how-list">
                    <li class="wa-how-item">
                        <div class="wa-how-num">1</div>
                        <div class="wa-how-text"><strong>Connect Now</strong> click karein — QR code generate hoga.</div>
                    </li>
                    <li class="wa-how-item">
                        <div class="wa-how-num">2</div>
                        <div class="wa-how-text"><strong>QR scan karein</strong> — phone camera se ya kisi bhi QR scanner se.</div>
                    </li>
                    <li class="wa-how-item">
                        <div class="wa-how-num">3</div>
                        <div class="wa-how-text">Facebook page khulega — <strong>Facebook Business account se login karein</strong> aur <strong>Allow</strong> tap karein.</div>
                    </li>
                </ul>

                <div id="qrConnecting" style="display:none;margin-bottom:12px;">
                    <div class="wa-status-waiting">
                        <span class="wa-pulse"></span>Waiting for authorization on your phone…
                    </div>
                </div>
                <div id="qrDone" style="display:none;margin-bottom:12px;">
                    <div class="wa-status-success">
                        <svg viewBox="0 0 20 20" fill="currentColor" style="width:16px;height:16px;flex-shrink:0;"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                        Connected! Reloading…
                    </div>
                </div>

                @if($metaAppId && $metaConfigId)
                <div style="margin-bottom:14px;">
                    <button type="button" class="btn btn-primary" id="esConnectBtn" onclick="connectWhatsAppEmbedded()"
                        style="gap:8px;display:inline-flex;align-items:center;background:#25d366;border-color:#25d366;">
                        <svg viewBox="0 0 24 24" fill="#fff" style="width:16px;height:16px;">
                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/>
                            <path d="M12.004 2C6.477 2 2 6.477 2 12.004c0 1.773.465 3.48 1.348 4.985L2 22l5.13-1.34A9.953 9.953 0 0012.004 22C17.527 22 22 17.523 22 12c0-5.522-4.473-10-9.996-10z" fill-rule="evenodd" clip-rule="evenodd"/>
                        </svg>
                        Connect WhatsApp
                    </button>
                    <p style="font-size:11px;color:var(--text-400);margin-top:8px;line-height:1.5;">
                        Ek click mein — popup ke andar hi Business/WABA/number create ya select ho jayega, alag se kahin jaana nahi padega.
                    </p>
                </div>
                <div style="display:flex;align-items:center;gap:10px;margin:14px 0;">
                    <div style="flex:1;height:1px;background:var(--border-subtle);"></div>
                    <span style="font-size:11px;color:var(--text-400);">YA agar existing WABA hai</span>
                    <div style="flex:1;height:1px;background:var(--border-subtle);"></div>
                </div>
                @endif

                <div id="qrGenerateBtn">
                    <button type="button" class="btn btn-ghost" onclick="startQrFlow()" style="gap:8px;display:inline-flex;align-items:center;">
                        <svg viewBox="0 0 20 20" fill="currentColor" style="width:16px;height:16px;">
                            <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h3a1 1 0 011 1v3a1 1 0 01-1 1H4a1 1 0 01-1-1V4zm2 2V5h1v1H5zM3 13a1 1 0 011-1h3a1 1 0 011 1v3a1 1 0 01-1 1H4a1 1 0 01-1-1v-3zm2 2v-1h1v1H5zM13 3a1 1 0 00-1 1v3a1 1 0 001 1h3a1 1 0 001-1V4a1 1 0 00-1-1h-3zm1 2v1h1V5h-1z" clip-rule="evenodd"/>
                            <path d="M11 4a1 1 0 10-2 0v1a1 1 0 002 0V4zM10 7a1 1 0 011 1v1h2a1 1 0 110 2h-3a1 1 0 01-1-1V8a1 1 0 011-1zM16 9a1 1 0 100 2 1 1 0 000-2zM9 13a1 1 0 011-1h1a1 1 0 110 2v2a1 1 0 11-2 0v-3zM7 11a1 1 0 100-2H4a1 1 0 100 2h3zM17 13a1 1 0 01-1 1h-2a1 1 0 110-2h2a1 1 0 011 1zM16 17a1 1 0 100-2h-3a1 1 0 100 2h3z"/>
                        </svg>
                        Connect via QR Code
                    </button>
                </div>
                <p style="font-size:11px;color:var(--text-400);margin-top:10px;line-height:1.5;">
                    Coexistence rakhna hai (phone app + CRM dono saath) toh pehle upar wala <strong>Coexistence setup</strong> complete karein.
                </p>
            </div>
        </div>
    @endif
</div>

{{-- ══════════════════════════════════════════════════════════════
     SETTINGS FORM
══════════════════════════════════════════════════════════════ --}}
<form method="POST" action="{{ route('tenant.whatsapp.api-settings.save') }}">
@csrf
<div class="wa-settings-grid">

    <div class="card">
        <div class="card-header"><h3 class="card-title">Webhook Configuration</h3></div>
        <div class="card-body">
            <p style="font-size:12px;color:var(--text-300);margin-bottom:14px;">
                Meta App → WhatsApp → Configuration → Webhook mein ye daalein
            </p>
            <label class="form-label" style="margin-bottom:5px;">Callback URL</label>
            <div style="display:flex;gap:8px;margin-bottom:14px;">
                <div class="webhook-box" style="flex:1;" id="webhookUrl">{{ url('/webhook/whatsapp') }}</div>
                <button type="button" class="copy-btn" onclick="copyText('webhookUrl')" title="Copy">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="width:16px;height:16px;"><path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                </button>
            </div>
            <label class="form-label" style="margin-bottom:5px;">Verify Token</label>
            <div style="display:flex;gap:8px;">
                <div class="webhook-box" style="flex:1;" id="verifyToken">{{ $settings->webhook_verify_token ?? 'Will be generated on save' }}</div>
                <button type="button" class="copy-btn" onclick="copyText('verifyToken')" title="Copy">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="width:16px;height:16px;"><path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                </button>
            </div>
            <p style="font-size:11px;color:var(--text-400);margin-top:8px;">Subscribe field: <strong>messages</strong></p>
            @if($settings->is_connected)
            <div style="margin-top:16px;padding-top:14px;border-top:1px solid var(--border-subtle);">
                <div style="display:flex;gap:16px;flex-wrap:wrap;">
                    <div class="form-group" style="flex:1;min-width:140px;margin:0;">
                        <label class="form-label" style="font-size:11px;">Phone Number ID</label>
                        <input type="text" name="phone_number_id" class="form-input" value="{{ $settings->phone_number_id }}" placeholder="Auto-filled via QR">
                    </div>
                    <div class="form-group" style="flex:1;min-width:140px;margin:0;">
                        <label class="form-label" style="font-size:11px;">WABA ID</label>
                        <input type="text" name="waba_id" class="form-input" value="{{ $settings->waba_id }}" placeholder="Auto-filled via QR">
                    </div>
                </div>
            </div>
            @endif
            <div style="margin-top:16px;">
                <button onclick="testConnection()" type="button" class="btn btn-ghost btn-sm" id="testBtn">Test Connection</button>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h3 class="card-title">Chatbot</h3></div>
        <div class="card-body">
            <div class="form-group">
                <label style="display:flex;align-items:flex-start;gap:10px;cursor:pointer;">
                    <input type="checkbox" name="chatbot_enabled" value="1" {{ $settings->chatbot_enabled ? 'checked' : '' }} style="width:18px;height:18px;margin-top:2px;flex-shrink:0;">
                    <div>
                        <div style="font-weight:600;font-size:14px;">Enable Chatbot</div>
                        <div style="font-size:12px;color:var(--text-300);margin-top:2px;">Incoming messages pe keyword-based auto-reply</div>
                    </div>
                </label>
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
@if($metaAppId && $metaConfigId)
<script>
window.fbAsyncInit = function () {
    FB.init({ appId: '{{ $metaAppId }}', xfbml: false, version: 'v21.0' });
};
</script>
<script async defer crossorigin="anonymous" src="https://connect.facebook.net/en_US/sdk.js"></script>
<script>
// ── WhatsApp Embedded Signup — one-click connect ───────────────
let esWabaId = null, esPhoneId = null;

window.addEventListener('message', function (event) {
    if (typeof event.origin !== 'string' || !event.origin.endsWith('facebook.com')) return;
    let data;
    try { data = JSON.parse(event.data); } catch (e) { return; }
    if (data.type === 'WA_EMBEDDED_SIGNUP' && data.event === 'FINISH' && data.data) {
        esWabaId  = data.data.waba_id || null;
        esPhoneId = data.data.phone_number_id || null;
    }
});

function connectWhatsAppEmbedded() {
    if (typeof FB === 'undefined') {
        alert('Facebook SDK abhi load ho raha hai, thodi der mein dobara try karein.');
        return;
    }
    esWabaId = null;
    esPhoneId = null;

    FB.login(function (response) {
        if (!response.authResponse || !response.authResponse.code) {
            return; // user cancelled or closed the popup
        }
        if (!esWabaId || !esPhoneId) {
            alert('WhatsApp account create/select nahi ho paya. Dobara try karein.');
            return;
        }

        const btn = document.getElementById('esConnectBtn');
        if (btn) { btn.disabled = true; btn.textContent = 'Connecting…'; }

        fetch('{{ route("tenant.whatsapp.embedded-signup.connect") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                code: response.authResponse.code,
                waba_id: esWabaId,
                phone_number_id: esPhoneId,
            }),
        })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (data.success) {
                location.reload();
            } else {
                alert('Connect failed: ' + data.message);
                if (btn) { btn.disabled = false; btn.textContent = 'Connect WhatsApp'; }
            }
        })
        .catch(function () {
            alert('Request failed.');
            if (btn) { btn.disabled = false; btn.textContent = 'Connect WhatsApp'; }
        });
    }, {
        config_id: '{{ $metaConfigId }}',
        response_type: 'code',
        override_default_response_type: true,
        extras: { setup: {} },
    });
}
</script>
@endif
<script>
// ── Coexistence guide toggle ───────────────────────────────────
function toggleCoex() {
    const body = document.getElementById('coexBody');
    const chevron = document.getElementById('coexChevron');
    const isOpen = body.style.display !== 'none';
    body.style.display = isOpen ? 'none' : 'block';
    chevron.classList.toggle('open', !isOpen);
}

function switchMethod(method) {
    ['app','bm'].forEach(function(m) {
        document.getElementById('tab-' + m).classList.toggle('active', m === method);
        document.getElementById('panel-' + m).classList.toggle('active', m === method);
    });
}

// ── Utilities ─────────────────────────────────────────────────
function copyText(id) {
    const text = document.getElementById(id).textContent.trim();
    navigator.clipboard.writeText(text).then(() => {
        const el = document.getElementById(id);
        el.style.background = '#dcfce7';
        setTimeout(() => el.style.background = '', 1400);
    });
}

function testConnection() {
    const btn = document.getElementById('testBtn');
    btn.textContent = 'Testing…';
    btn.disabled = true;
    fetch('{{ route("tenant.whatsapp.api-settings.test") }}', {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert('Connected! ' + (data.account?.display_phone_number ?? ''));
            location.reload();
        } else {
            alert('Failed: ' + data.message);
        }
    })
    .catch(() => alert('Request failed.'))
    .finally(() => { btn.textContent = 'Test Connection'; btn.disabled = false; });
}

// ── QR Connect flow ────────────────────────────────────────────
let qrState = null, qrPollTimer = null, qrCountdownTimer = null;
const isConnected = {{ $settings->is_connected ? 'true' : 'false' }};

function startQrFlow() {
    if (isConnected) {
        const area = document.getElementById('qrReconnectArea');
        if (area) area.style.display = 'block';
        const btn = document.getElementById('reconnectBtn');
        if (btn) btn.style.display = 'none';
    }

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

        const img = document.getElementById('qrImg');
        img.src = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&margin=10&data=' + encodeURIComponent(data.url);
        img.style.display = 'block';

        const ph = document.getElementById('qrPlaceholder');
        if (ph) ph.style.display = 'none';

        const timer = document.getElementById('qrTimer');
        if (timer) timer.style.display = 'block';

        setStep(2);

        const genBtn = document.getElementById('qrGenerateBtn');
        if (genBtn) genBtn.style.display = 'none';

        const conn = document.getElementById('qrConnecting');
        if (conn) conn.style.display = 'block';

        const ref = document.getElementById('qrRefreshBtn');
        if (ref) ref.style.display = 'inline-flex';

        startCountdown(600);
        startPolling();
    })
    .catch(function(err) { alert('Could not generate QR: ' + err.message); });
}

function setStep(n) {
    for (let i = 1; i <= 4; i++) {
        const el = document.getElementById('step' + i);
        if (!el) continue;
        el.classList.remove('active','done');
        if (i < n) el.classList.add('done');
        else if (i === n) el.classList.add('active');
    }
}

function startCountdown(seconds) {
    clearInterval(qrCountdownTimer);
    let remaining = seconds;
    const el = document.getElementById('qrCountdown');
    qrCountdownTimer = setInterval(function() {
        remaining--;
        const m = String(Math.floor(remaining / 60)).padStart(2,'0');
        const s = String(remaining % 60).padStart(2,'0');
        if (el) el.textContent = m + ':' + s;
        if (remaining <= 0) {
            clearInterval(qrCountdownTimer);
            clearInterval(qrPollTimer);
            const c = document.getElementById('qrConnecting');
            if (c) c.style.display = 'none';
            const t = document.getElementById('qrTimer');
            if (t) t.textContent = 'QR expired — generate a new one.';
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
                const c = document.getElementById('qrConnecting');
                if (c) c.style.display = 'none';
                const d = document.getElementById('qrDone');
                if (d) d.style.display = 'block';
                const r = document.getElementById('qrRefreshBtn');
                if (r) r.style.display = 'none';
                setStep(4);
                document.getElementById('waConnectCard').style.borderColor = 'var(--green)';
                setTimeout(function() { location.reload(); }, 1800);
            }
        })
        .catch(function() {});
    }, 3000);
}
</script>
@endpush
