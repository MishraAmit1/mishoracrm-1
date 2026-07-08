@extends('layouts.app')
@section('title', 'Instagram Integration Guide')

@push('styles')
<style>
/* ── Layout ──────────────────────────────────────────────── */
.guide-body   { max-width:900px; }
.guide-toc    { background:var(--bg-surface); border:1px solid var(--border-default); border-radius:var(--r-lg); padding:20px 24px; margin-bottom:28px; }
.guide-toc h4 { font-size:12px; font-weight:700; color:var(--text-300); text-transform:uppercase; letter-spacing:.06em; margin-bottom:12px; }
.guide-toc a  { display:block; font-size:13.5px; color:var(--accent); padding:3px 0; text-decoration:none; }
.guide-toc a:hover { text-decoration:underline; }

/* ── Section headings ────────────────────────────────────── */
.g-section      { margin-bottom:36px; scroll-margin-top:20px; }
.g-section-head { display:flex; align-items:center; gap:12px; margin-bottom:18px; }
.g-section-num  { width:32px; height:32px; border-radius:var(--r-md); background:var(--accent); color:#fff; font-weight:800; font-size:15px; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.g-section-title{ font-size:18px; font-weight:800; color:var(--text-100); }
.g-section-sub  { font-size:13px; color:var(--text-300); margin-top:2px; }

/* ── Flow diagrams ───────────────────────────────────────── */
.flow-wrap   { display:flex; align-items:center; flex-wrap:wrap; gap:0; margin:18px 0; }
.flow-box    { background:var(--bg-surface); border:1.5px solid var(--border-default); border-radius:var(--r-md); padding:12px 16px; min-width:130px; text-align:center; }
.flow-box-title { font-size:12px; font-weight:700; color:var(--text-100); }
.flow-box-sub   { font-size:11px; color:var(--text-300); margin-top:3px; }
.flow-arrow  { font-size:20px; color:var(--text-300); padding:0 10px; flex-shrink:0; }
.flow-box.accent-box { background:#ede9fe; border-color:#a78bfa; }
.flow-box.accent-box .flow-box-title { color:#5b21b6; }
.flow-box.green-box { background:#d1fae5; border-color:#6ee7b7; }
.flow-box.green-box .flow-box-title { color:#065f46; }
.flow-box.orange-box { background:#fff7ed; border-color:#fdba74; }
.flow-box.orange-box .flow-box-title { color:#c2410c; }
.flow-box.pink-box   { background:#fce7f3; border-color:#f9a8d4; }
.flow-box.pink-box .flow-box-title  { color:#831843; }
.flow-box.blue-box   { background:#dbeafe; border-color:#93c5fd; }
.flow-box.blue-box .flow-box-title  { color:#1e40af; }
.flow-or   { padding:4px 10px; font-size:11px; font-weight:700; color:var(--text-300); background:var(--bg-subtle); border-radius:99px; margin:0 4px; }

/* ── Branch flows ────────────────────────────────────────── */
.branch-wrap  { display:grid; grid-template-columns:repeat(3,1fr); gap:12px; margin:12px 0; }
@media(max-width:700px){ .branch-wrap { grid-template-columns:1fr; } }
.branch-card  { border:1.5px solid var(--border-default); border-radius:var(--r-md); padding:14px 16px; background:var(--bg-surface); }
.branch-label { font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.05em; color:var(--text-300); margin-bottom:6px; }
.branch-title { font-size:13px; font-weight:700; color:var(--text-100); margin-bottom:4px; }
.branch-desc  { font-size:12px; color:var(--text-300); line-height:1.5; }
.branch-card.purple { border-color:#a78bfa; background:#faf5ff; }
.branch-card.purple .branch-title { color:#5b21b6; }
.branch-card.green  { border-color:#6ee7b7; background:#f0fdf4; }
.branch-card.green .branch-title  { color:#065f46; }
.branch-card.sky    { border-color:#7dd3fc; background:#f0f9ff; }
.branch-card.sky .branch-title    { color:#0369a1; }

/* ── Steps ───────────────────────────────────────────────── */
.steps-list  { list-style:none; padding:0; margin:0; display:flex; flex-direction:column; gap:12px; }
.step-item   { display:flex; gap:14px; align-items:flex-start; }
.step-num    { width:26px; height:26px; border-radius:50%; background:var(--accent); color:#fff; font-size:12px; font-weight:700; display:flex; align-items:center; justify-content:center; flex-shrink:0; margin-top:2px; }
.step-body   { flex:1; }
.step-title  { font-size:13.5px; font-weight:700; color:var(--text-100); margin-bottom:3px; }
.step-desc   { font-size:13px; color:var(--text-300); line-height:1.6; }
.step-desc a { color:var(--accent); }

/* ── Code / payload boxes ────────────────────────────────── */
.payload-box { background:#1e1e2e; border-radius:var(--r-md); padding:18px 20px; font-family:var(--mono); font-size:12px; color:#cdd6f4; overflow-x:auto; margin:12px 0; line-height:1.7; }
.payload-box .key   { color:#89b4fa; }
.payload-box .str   { color:#a6e3a1; }
.payload-box .num   { color:#fab387; }
.payload-box .comment { color:#6c7086; font-style:italic; }

/* ── Info / tip boxes ────────────────────────────────────── */
.tip-box  { display:flex; gap:12px; padding:14px 16px; border-radius:var(--r-md); margin:12px 0; font-size:13px; line-height:1.6; }
.tip-box.info { background:#eff6ff; border:1px solid #bfdbfe; color:#1e40af; }
.tip-box.warn { background:#fff7ed; border:1px solid #fed7aa; color:#c2410c; }
.tip-box.ok   { background:#f0fdf4; border:1px solid #bbf7d0; color:#15803d; }
.tip-box svg  { flex-shrink:0; margin-top:1px; }

/* ── Pill badge ──────────────────────────────────────────── */
.pill { display:inline-block; padding:2px 10px; border-radius:99px; font-size:11px; font-weight:600; }
.pill-purple { background:#ede9fe; color:#5b21b6; }
.pill-green  { background:#d1fae5; color:#065f46; }
.pill-orange { background:#fff7ed; color:#c2410c; }
.pill-blue   { background:#dbeafe; color:#1e40af; }
.pill-pink   { background:#fce7f3; color:#831843; }

/* ── Method table ────────────────────────────────────────── */
.method-table { width:100%; border-collapse:collapse; font-size:13px; }
.method-table th { padding:10px 12px; text-align:left; background:var(--bg-subtle); font-size:11.5px; font-weight:700; color:var(--text-300); text-transform:uppercase; letter-spacing:.04em; border-bottom:2px solid var(--border-default); }
.method-table td { padding:10px 12px; border-bottom:1px solid var(--border-subtle); vertical-align:top; }
.method-table tr:hover td { background:var(--bg-hover); }
.method-name { font-family:var(--mono); font-size:12px; font-weight:700; color:#5b21b6; background:#ede9fe; padding:2px 8px; border-radius:4px; }
.method-file { font-family:var(--mono); font-size:11px; color:var(--text-300); }
</style>
@endpush

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Instagram Integration — Complete Guide</h1>
        <p class="page-sub">Architecture, flow, methods, and how to use everything</p>
    </div>
    <a href="{{ route('tenant.instagram.index') }}" class="btn btn-ghost">
        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="width:15px;height:15px;">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
        </svg>
        Back to Instagram
    </a>
</div>

<div class="guide-body">

{{-- Table of Contents --}}
<div class="guide-toc">
    <h4>On this page</h4>
    <a href="#overview">1. Overview — What this module does</a>
    <a href="#architecture">2. Architecture — How it all connects</a>
    <a href="#instagram-flow">3. Instagram Flow — Comment → DM</a>
    <a href="#chatbot-flow">4. Instagram Chatbot — DM keyword reply</a>
    <a href="#whatsapp-flow">5. WhatsApp Chatbot Flow</a>
    <a href="#n8n-flow">6. n8n Integration Flow (WhatsApp only)</a>
    <a href="#setup-steps">7. Step-by-step Setup</a>
    <a href="#methods">8. Controller Methods Reference</a>
    <a href="#payloads">9. n8n Payload Examples</a>
</div>

{{-- ══════════════════════════════════════════════════════ --}}
{{-- 1. OVERVIEW                                            --}}
{{-- ══════════════════════════════════════════════════════ --}}
<div class="g-section" id="overview">
    <div class="g-section-head">
        <div class="g-section-num">1</div>
        <div>
            <div class="g-section-title">Overview — What this module does</div>
            <div class="g-section-sub">Three tools in one: Automations, Chatbot, Post Picker</div>
        </div>
    </div>

    <div class="branch-wrap">
        <div class="branch-card purple">
            <div class="branch-label">Tool 1</div>
            <div class="branch-title">⚡ Automations</div>
            <div class="branch-desc">Jab koi aapki post pe comment kare ya DM kare, automatically ek specific action trigger hota hai — DM bhejo ya comment reply karo. Rule-based, keyword filtering ke saath, fully Laravel backend mein.</div>
        </div>
        <div class="branch-card green">
            <div class="branch-label">Tool 2</div>
            <div class="branch-title">🤖 Chatbot</div>
            <div class="branch-desc">DM mein agar koi keyword likhe — jaise "price", "hi", "help" — toh auto-reply chala jata hai. Koi bhi staff involvement nahi. 24/7 kaam karta hai.</div>
        </div>
        <div class="branch-card sky">
            <div class="branch-label">Tool 3</div>
            <div class="branch-title">📸 Post Picker</div>
            <div class="branch-desc">"Comment on SPECIFIC post" automation banate waqt, apni actual Instagram posts ka thumbnail gallery dikhta hai — Post ID manually copy-paste karne ki zaroorat nahi, click ya drag karke select karo.</div>
        </div>
    </div>

    <div class="tip-box info" style="margin-top:12px;">
        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="width:18px;height:18px;">
            <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z"/>
        </svg>
        <span><strong>Note:</strong> Instagram Automation ab n8n workflow use nahi karta — matching aur action dono fully Laravel backend mein hote hain (koi external webhook dependency nahi). n8n integration sirf <a href="#n8n-flow">WhatsApp Chatbot flows</a> ke liye available hai.</span>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════ --}}
{{-- 2. ARCHITECTURE                                        --}}
{{-- ══════════════════════════════════════════════════════ --}}
<div class="g-section" id="architecture">
    <div class="g-section-head">
        <div class="g-section-num">2</div>
        <div>
            <div class="g-section-title">Architecture — How it all connects</div>
            <div class="g-section-sub">Instagram → Meta Webhook → Laravel → Action</div>
        </div>
    </div>

    <div class="flow-wrap">
        <div class="flow-box pink-box">
            <div class="flow-box-title">Instagram User</div>
            <div class="flow-box-sub">Comments ya DM karta hai</div>
        </div>
        <div class="flow-arrow">→</div>
        <div class="flow-box orange-box">
            <div class="flow-box-title">Meta Platform</div>
            <div class="flow-box-sub">Event detect karta hai</div>
        </div>
        <div class="flow-arrow">→</div>
        <div class="flow-box blue-box">
            <div class="flow-box-title">Our Webhook</div>
            <div class="flow-box-sub">/webhook/instagram</div>
        </div>
        <div class="flow-arrow">→</div>
        <div class="flow-box accent-box">
            <div class="flow-box-title">Laravel Engine</div>
            <div class="flow-box-sub">Automation / Chatbot check</div>
        </div>
        <div class="flow-arrow">→</div>
        <div class="flow-box green-box">
            <div class="flow-box-title">Action</div>
            <div class="flow-box-sub">DM / Reply</div>
        </div>
    </div>

    <div class="tip-box info">
        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="width:18px;height:18px;">
            <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z"/>
        </svg>
        <span><strong>Real-time:</strong> Meta aapka webhook URL call karta hai event ke baad kuch milliseconds mein. Aapka Laravel app process karta hai aur Graph API ke through reply bhejta hai — bilkul real-time, koi external workflow engine involved nahi.</span>
    </div>

    <div style="margin-top:16px;">
        <strong style="font-size:13px;color:var(--text-200);">Webhook URL (Meta App mein register karo):</strong>
        <div style="display:flex;align-items:center;gap:10px;margin-top:8px;">
            <code style="background:var(--bg-subtle);padding:8px 14px;border-radius:var(--r-sm);font-size:13px;font-family:var(--mono);flex:1;border:1px solid var(--border-subtle);">{{ $webhookUrl }}</code>
            <button onclick="navigator.clipboard.writeText('{{ $webhookUrl }}')" class="btn btn-ghost btn-sm">Copy</button>
        </div>
        @if($settings->webhook_verify_token)
        <div style="display:flex;align-items:center;gap:10px;margin-top:8px;">
            <div style="font-size:12px;color:var(--text-300);min-width:110px;">Verify Token:</div>
            <code style="background:var(--bg-subtle);padding:6px 14px;border-radius:var(--r-sm);font-size:12px;font-family:var(--mono);flex:1;border:1px solid var(--border-subtle);">{{ $settings->webhook_verify_token }}</code>
            <button onclick="navigator.clipboard.writeText('{{ $settings->webhook_verify_token }}')" class="btn btn-ghost btn-sm">Copy</button>
        </div>
        @endif
    </div>
</div>

{{-- ══════════════════════════════════════════════════════ --}}
{{-- 3. INSTAGRAM AUTOMATION FLOW                           --}}
{{-- ══════════════════════════════════════════════════════ --}}
<div class="g-section" id="instagram-flow">
    <div class="g-section-head">
        <div class="g-section-num">3</div>
        <div>
            <div class="g-section-title">Instagram Automation Flow</div>
            <div class="g-section-sub">Comment karo → Auto DM milega</div>
        </div>
    </div>

    {{-- Comment flow --}}
    <div style="font-size:13px;font-weight:700;color:var(--text-200);margin-bottom:10px;">Comment Trigger Flow:</div>
    <div class="flow-wrap">
        <div class="flow-box pink-box">
            <div class="flow-box-title">User Comments</div>
            <div class="flow-box-sub">"price?" ya kuch bhi</div>
        </div>
        <div class="flow-arrow">→</div>
        <div class="flow-box blue-box">
            <div class="flow-box-title">Webhook Receives</div>
            <div class="flow-box-sub">changes.comments event</div>
        </div>
        <div class="flow-arrow">→</div>
        <div class="flow-box orange-box">
            <div class="flow-box-title">Automation Match</div>
            <div class="flow-box-sub">Keyword check + post check</div>
        </div>
        <div class="flow-arrow">→</div>
    </div>
    <div style="margin-left:20px;margin-top:-4px;margin-bottom:16px;">
        <div style="font-size:12px;color:var(--text-300);margin-bottom:8px;">↓ Matched automation ke action ke hisaab se:</div>
        <div class="branch-wrap" style="grid-template-columns:repeat(2,1fr);">
            <div class="branch-card purple">
                <div class="branch-label">Action: send_dm</div>
                <div class="branch-title">💬 DM bhejo</div>
                <div class="branch-desc">Instagram Graph API call hoti hai. Commenter ko private DM jaata hai. Koi publicly visible nahi.</div>
            </div>
            <div class="branch-card green">
                <div class="branch-label">Action: reply_comment</div>
                <div class="branch-title">💭 Comment Reply</div>
                <div class="branch-desc">Usi comment pe public reply karti hai system. Sab dekh sakte hain.</div>
            </div>
        </div>
    </div>

    <div style="font-size:13px;font-weight:700;color:var(--text-200);margin:16px 0 10px;">Specific post select karna — Post Picker:</div>
    <div style="padding:14px 16px;border:1px solid var(--border-default);border-radius:var(--r-md);background:var(--bg-surface);">
        <div style="font-size:13px;color:var(--text-200);line-height:1.6;">
            <code style="font-size:11px;">specific_post_comment</code> trigger select karne par, automation form mein aapke Instagram account ke recent posts ka thumbnail grid load ho jaata hai. Post ID manually nikaal kar paste karne ki zaroorat nahi:
        </div>
        <ul style="margin:10px 0 0 18px;font-size:12.5px;color:var(--text-300);line-height:1.8;">
            <li><strong>Click</strong> karo kisi post par → Post ID field apne aap fill ho jaata hai (sab devices — mobile/desktop — par kaam karta hai)</li>
            <li><strong>Drag</strong> karke bhi Post ID field mein drop kar sakte ho (desktop par)</li>
            <li>Agar account connect nahi hai ya posts load nahi hote, tab bhi Post ID manually type kiya ja sakta hai — field plain text hi rehta hai</li>
        </ul>
    </div>

    <div style="font-size:13px;font-weight:700;color:var(--text-200);margin:16px 0 10px;">Trigger Types:</div>
    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px;">
        <div style="padding:12px;border:1px solid #fde68a;background:#fefce8;border-radius:var(--r-md);">
            <div style="font-size:12px;font-weight:700;color:#92400e;margin-bottom:4px;">any_post_comment</div>
            <div style="font-size:12px;color:#78350f;">Aapke kisi bhi post pe comment aaye aur keyword match ho → trigger</div>
        </div>
        <div style="padding:12px;border:1px solid #bfdbfe;background:#eff6ff;border-radius:var(--r-md);">
            <div style="font-size:12px;font-weight:700;color:#1e40af;margin-bottom:4px;">specific_post_comment</div>
            <div style="font-size:12px;color:#1e3a8a;">Ek specific post ID pe comment aaye aur keyword match ho → trigger</div>
        </div>
        <div style="padding:12px;border:1px solid #bbf7d0;background:#f0fdf4;border-radius:var(--r-md);">
            <div style="font-size:12px;font-weight:700;color:#065f46;margin-bottom:4px;">dm_keyword</div>
            <div style="font-size:12px;color:#064e3b;">Koi DM kare aur message mein keyword ho → trigger (automation ke through)</div>
        </div>
    </div>

    <div class="tip-box warn" style="margin-top:16px;">
        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="width:18px;height:18px;">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
        </svg>
        <span><strong>Priority Rule:</strong> Ek event pe sirf <em>pehli</em> matching automation run hogi. Multiple automations ek saath nahi chalti. Isliye automations ka order matter karta hai.</span>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════ --}}
{{-- 4. CHATBOT FLOW                                        --}}
{{-- ══════════════════════════════════════════════════════ --}}
<div class="g-section" id="chatbot-flow">
    <div class="g-section-head">
        <div class="g-section-num">4</div>
        <div>
            <div class="g-section-title">Instagram Chatbot Flow</div>
            <div class="g-section-sub">DM aaya → keyword match karo → auto reply</div>
        </div>
    </div>

    <div class="flow-wrap">
        <div class="flow-box pink-box">
            <div class="flow-box-title">User DM karta hai</div>
            <div class="flow-box-sub">"hello" ya "price?"</div>
        </div>
        <div class="flow-arrow">→</div>
        <div class="flow-box blue-box">
            <div class="flow-box-title">Webhook Receives</div>
            <div class="flow-box-sub">messaging event</div>
        </div>
        <div class="flow-arrow">→</div>
        <div class="flow-box orange-box">
            <div class="flow-box-title">Automation Check</div>
            <div class="flow-box-sub">dm_keyword automations pehle check</div>
        </div>
        <div class="flow-arrow">→</div>
        <div class="flow-box accent-box">
            <div class="flow-box-title">Chatbot Check</div>
            <div class="flow-box-sub">agar automation na mile</div>
        </div>
        <div class="flow-arrow">→</div>
        <div class="flow-box green-box">
            <div class="flow-box-title">DM Reply</div>
            <div class="flow-box-sub">matched flow ka message</div>
        </div>
    </div>

    <div style="margin-top:16px;padding:16px;background:var(--bg-subtle);border-radius:var(--r-md);border:1px solid var(--border-subtle);">
        <div style="font-size:13px;font-weight:700;color:var(--text-100);margin-bottom:12px;">Chatbot priority order:</div>
        <div style="display:flex;flex-direction:column;gap:8px;">
            <div style="display:flex;align-items:center;gap:10px;">
                <span style="width:22px;height:22px;background:#7c3aed;color:#fff;border-radius:50%;font-size:11px;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0;">1</span>
                <span style="font-size:13px;color:var(--text-100);">Automation check (<code style="font-size:11px;">trigger_type = dm_keyword</code>) — agar koi match ho</span>
            </div>
            <div style="display:flex;align-items:center;gap:10px;">
                <span style="width:22px;height:22px;background:#7c3aed;color:#fff;border-radius:50%;font-size:11px;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0;">2</span>
                <span style="font-size:13px;color:var(--text-100);">Chatbot flows check — <code style="font-size:11px;">sort_order</code> ke hisaab se, non-default pehle</span>
            </div>
            <div style="display:flex;align-items:center;gap:10px;">
                <span style="width:22px;height:22px;background:#7c3aed;color:#fff;border-radius:50%;font-size:11px;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0;">3</span>
                <span style="font-size:13px;color:var(--text-100);">Default flow — agar koi keyword match na ho toh fallback</span>
            </div>
            <div style="display:flex;align-items:center;gap:10px;">
                <span style="width:22px;height:22px;background:#9ca3af;color:#fff;border-radius:50%;font-size:11px;font-weight:700;display:flex;align-items:center;justify-content:center;flex-shrink:0;">✕</span>
                <span style="font-size:13px;color:var(--text-300);">Koi match nahi → koi reply nahi, log mein skip record</span>
            </div>
        </div>
    </div>

    <div style="margin-top:16px;">
        <div style="font-size:13px;font-weight:700;color:var(--text-200);margin-bottom:8px;">Keyword match modes:</div>
        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px;font-size:12.5px;">
            <div style="padding:10px 12px;border:1px solid var(--border-default);border-radius:var(--r-sm);background:var(--bg-surface);">
                <div style="font-weight:700;margin-bottom:4px;">contains</div>
                <div style="color:var(--text-300);">Message mein keyword ka koi bhi part ho. "price list?" → matches "price"</div>
            </div>
            <div style="padding:10px 12px;border:1px solid var(--border-default);border-radius:var(--r-sm);background:var(--bg-surface);">
                <div style="font-weight:700;margin-bottom:4px;">exact</div>
                <div style="color:var(--text-300);">Poora message exactly keyword ho. "hi" → sirf "hi" matches, "hi there" nahi</div>
            </div>
            <div style="padding:10px 12px;border:1px solid var(--border-default);border-radius:var(--r-sm);background:var(--r-sm);background:var(--bg-surface);">
                <div style="font-weight:700;margin-bottom:4px;">any</div>
                <div style="color:var(--text-300);">Contains ke jaisa hi — keyword kisi bhi position pe ho</div>
            </div>
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════ --}}
{{-- 5. WHATSAPP CHATBOT FLOW                               --}}
{{-- ══════════════════════════════════════════════════════ --}}
<div class="g-section" id="whatsapp-flow">
    <div class="g-section-head">
        <div class="g-section-num">5</div>
        <div>
            <div class="g-section-title">WhatsApp Chatbot Flow</div>
            <div class="g-section-sub">Meta Cloud API → Webhook → Auto-reply</div>
        </div>
    </div>

    <div class="flow-wrap">
        <div class="flow-box green-box">
            <div class="flow-box-title">WhatsApp Message</div>
            <div class="flow-box-sub">User texts your number</div>
        </div>
        <div class="flow-arrow">→</div>
        <div class="flow-box blue-box">
            <div class="flow-box-title">Meta Cloud API</div>
            <div class="flow-box-sub">Sends to /webhook/whatsapp</div>
        </div>
        <div class="flow-arrow">→</div>
        <div class="flow-box orange-box">
            <div class="flow-box-title">WhatsappWebhookController</div>
            <div class="flow-box-sub">WABA ID se tenant find</div>
        </div>
        <div class="flow-arrow">→</div>
        <div class="flow-box accent-box">
            <div class="flow-box-title">WhatsappChatbotService</div>
            <div class="flow-box-sub">chatbot_enabled check</div>
        </div>
        <div class="flow-arrow">→</div>
        <div class="flow-box green-box">
            <div class="flow-box-title">Reply Sent</div>
            <div class="flow-box-sub">Cloud API se message</div>
        </div>
    </div>

    <div class="tip-box ok">
        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="width:18px;height:18px;">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <span>WhatsApp chatbot Instagram chatbot se simpler hai — sirf keyword flows, koi automations nahi. Enable/disable toggle hai WhatsApp API Settings mein. Chatbot off ho toh messages sirf log hote hain, reply nahi jaata.</span>
    </div>

    <div style="margin-top:14px;padding:14px 16px;border:1px solid var(--border-default);border-radius:var(--r-md);background:var(--bg-surface);">
        <div style="font-size:13px;font-weight:700;margin-bottom:8px;">WhatsApp ke liye 2 alag webhooks:</div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;font-size:12.5px;">
            <div>
                <div style="font-weight:700;color:var(--text-100);">Callback URL</div>
                <code style="font-size:11px;color:var(--text-300);">{{ url('/webhook/whatsapp') }}</code>
                <div style="color:var(--text-300);margin-top:3px;">Meta App → WhatsApp → Configuration → Webhook</div>
            </div>
            <div>
                <div style="font-weight:700;color:var(--text-100);">Subscribe to field</div>
                <code style="font-size:11px;color:var(--text-300);">messages</code>
                <div style="color:var(--text-300);margin-top:3px;">Sirf messages field subscribe karo</div>
            </div>
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════ --}}
{{-- 6. n8n INTEGRATION FLOW                               --}}
{{-- ══════════════════════════════════════════════════════ --}}
<div class="g-section" id="n8n-flow">
    <div class="g-section-head">
        <div class="g-section-num">6</div>
        <div>
            <div class="g-section-title">n8n Integration Flow (WhatsApp only)</div>
            <div class="g-section-sub">2 levels mein n8n webhook support</div>
        </div>
    </div>

    <div class="tip-box warn" style="margin-bottom:16px;">
        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="width:18px;height:18px;">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
        </svg>
        <span><strong>Instagram Automation n8n use nahi karta.</strong> Yeh section sirf WhatsApp ke liye hai. Instagram comment/DM automation fully Laravel backend mein process hota hai — koi n8n webhook involved nahi.</span>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:16px;">
        <div style="padding:14px 16px;border:1.5px solid #a78bfa;background:#faf5ff;border-radius:var(--r-md);">
            <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#7c3aed;margin-bottom:6px;">Level 1 — Global</div>
            <div style="font-size:13px;font-weight:700;color:#4c1d95;margin-bottom:4px;">WhatsApp API Settings mein</div>
            <div style="font-size:12px;color:#6d28d9;line-height:1.5;">Tenant ka ek global n8n URL. <strong>Har incoming WhatsApp message</strong> par n8n ko call jaata hai.</div>
        </div>
        <div style="padding:14px 16px;border:1.5px solid #7dd3fc;background:#f0f9ff;border-radius:var(--r-md);">
            <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#0284c7;margin-bottom:6px;">Level 2 — Per Chatbot Flow</div>
            <div style="font-size:13px;font-weight:700;color:#0c4a6e;margin-bottom:4px;">WhatsApp Chatbot flow mein</div>
            <div style="font-size:12px;color:#075985;line-height:1.5;">Jab <strong>specific chatbot flow match</strong> ho, n8n call hoga. Reply bhi jayega aur n8n bhi trigger hoga — dono ek saath.</div>
        </div>
    </div>

    <div style="font-size:13px;font-weight:700;color:var(--text-200);margin-bottom:10px;">n8n ko payload milta hai POST body mein (JSON):</div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
        <div>
            <div class="pill pill-green" style="margin-bottom:8px;">WhatsApp Message Received</div>
            <div class="payload-box">{
  <span class="key">"event"</span>: <span class="str">"whatsapp_message_received"</span>,
  <span class="key">"wa_id"</span>: <span class="str">"919876543210"</span>,
  <span class="key">"message"</span>: <span class="str">"hi there"</span>,
  <span class="key">"tenant_id"</span>: <span class="num">3</span>
}</div>
        </div>
        <div>
            <div class="pill pill-blue" style="margin-bottom:8px;">WhatsApp Chatbot Flow Triggered</div>
            <div class="payload-box">{
  <span class="key">"event"</span>: <span class="str">"whatsapp_chatbot_triggered"</span>,
  <span class="key">"wa_id"</span>: <span class="str">"919876543210"</span>,
  <span class="key">"contact_name"</span>: <span class="str">"Raj Sharma"</span>,
  <span class="key">"message"</span>: <span class="str">"price?"</span>,
  <span class="key">"flow_name"</span>: <span class="str">"Price Inquiry"</span>
}</div>
        </div>
    </div>

    <div class="tip-box info" style="margin-top:16px;">
        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="width:18px;height:18px;">
            <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z"/>
        </svg>
        <span><strong>n8n mein kya kar sakte ho:</strong> Is payload se CRM mein lead banao, Slack notification bhejo, Google Sheets update karo, email trigger karo, ya koi bhi complex logic — n8n ke 400+ integrations se.</span>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════ --}}
{{-- 7. SETUP STEPS                                         --}}
{{-- ══════════════════════════════════════════════════════ --}}
<div class="g-section" id="setup-steps">
    <div class="g-section-head">
        <div class="g-section-num">7</div>
        <div>
            <div class="g-section-title">Step-by-step Setup</div>
            <div class="g-section-sub">Pehli baar setup karne ke liye complete guide</div>
        </div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
        {{-- Instagram setup --}}
        <div class="card">
            <div class="card-header" style="background:linear-gradient(135deg,#f09433,#dc2743);color:#fff;">
                <h3 class="card-title" style="color:#fff;">Instagram Setup</h3>
            </div>
            <div class="card-body">
                <ul class="steps-list">
                    <li class="step-item">
                        <div class="step-num">1</div>
                        <div class="step-body">
                            <div class="step-title">Meta Developer Account banao</div>
                            <div class="step-desc"><a href="https://developers.facebook.com" target="_blank">developers.facebook.com</a> pe jao → New App banao → Business type select karo</div>
                        </div>
                    </li>
                    <li class="step-item">
                        <div class="step-num">2</div>
                        <div class="step-body">
                            <div class="step-title">Instagram Basic Display ya Messenger add karo</div>
                            <div class="step-desc">App mein "Instagram" product add karo. Facebook Page aur Instagram Business account connect karo.</div>
                        </div>
                    </li>
                    <li class="step-item">
                        <div class="step-num">3</div>
                        <div class="step-body">
                            <div class="step-title">Page Access Token lo</div>
                            <div class="step-desc">Graph API Explorer se long-lived Page Access Token generate karo. Instagram Account ID bhi note karo.</div>
                        </div>
                    </li>
                    <li class="step-item">
                        <div class="step-num">4</div>
                        <div class="step-body">
                            <div class="step-title">CRM Settings mein credentials dalo</div>
                            <div class="step-desc"><a href="{{ route('tenant.instagram.settings') }}">Instagram → Settings</a> page pe jao — App ID, App Secret, Page ID, Instagram Account ID, Access Token fill karo → Save</div>
                        </div>
                    </li>
                    <li class="step-item">
                        <div class="step-num">5</div>
                        <div class="step-body">
                            <div class="step-title">Webhook register karo</div>
                            <div class="step-desc">Meta App → Webhooks → Instagram → Edit. Webhook URL aur Verify Token copy karke paste karo. Subscribe karo: <strong>messages</strong>, <strong>comments</strong></div>
                        </div>
                    </li>
                    <li class="step-item">
                        <div class="step-num">6</div>
                        <div class="step-body">
                            <div class="step-title">Test Connection → Automations banao</div>
                            <div class="step-desc">Settings pe "Test Connection" click karo. Connected dikhe toh <a href="{{ route('tenant.instagram.automations') }}">Automations</a> mein jao aur pehla rule banao.</div>
                        </div>
                    </li>
                </ul>
            </div>
        </div>

        {{-- WhatsApp setup --}}
        <div class="card">
            <div class="card-header" style="background:#25d366;color:#fff;">
                <h3 class="card-title" style="color:#fff;">WhatsApp Setup</h3>
            </div>
            <div class="card-body">
                <ul class="steps-list">
                    <li class="step-item">
                        <div class="step-num">1</div>
                        <div class="step-body">
                            <div class="step-title">Meta Business Suite pe WhatsApp add karo</div>
                            <div class="step-desc">Meta Developers App mein WhatsApp product add karo. Business phone number verify karo.</div>
                        </div>
                    </li>
                    <li class="step-item">
                        <div class="step-num">2</div>
                        <div class="step-body">
                            <div class="step-title">System User + Permanent Token</div>
                            <div class="step-desc">Business Manager mein System User banao. Usse WhatsApp permission do. Permanent (never-expiring) token generate karo.</div>
                        </div>
                    </li>
                    <li class="step-item">
                        <div class="step-num">3</div>
                        <div class="step-body">
                            <div class="step-title">CRM WA API Settings mein credentials</div>
                            <div class="step-desc"><a href="{{ route('tenant.whatsapp.api-settings') }}">WhatsApp → API Settings</a> pe Phone Number ID, WABA ID, Access Token dalo → Chatbot enable karo → Save</div>
                        </div>
                    </li>
                    <li class="step-item">
                        <div class="step-num">4</div>
                        <div class="step-body">
                            <div class="step-title">Webhook register karo</div>
                            <div class="step-desc">Meta App → WhatsApp → Configuration → Webhook. Callback URL aur Verify Token paste karo. Subscribe: <strong>messages</strong></div>
                        </div>
                    </li>
                    <li class="step-item">
                        <div class="step-num">5</div>
                        <div class="step-body">
                            <div class="step-title">Chatbot flows banao</div>
                            <div class="step-desc"><a href="{{ route('tenant.whatsapp.chatbot') }}">WhatsApp → Chatbot</a> page pe keyword flows add karo. Ek default flow zaroor rakho (fallback).</div>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════ --}}
{{-- 8. METHODS REFERENCE                                   --}}
{{-- ══════════════════════════════════════════════════════ --}}
<div class="g-section" id="methods">
    <div class="g-section-head">
        <div class="g-section-num">8</div>
        <div>
            <div class="g-section-title">Controller Methods Reference</div>
            <div class="g-section-sub">Har method kya karta hai</div>
        </div>
    </div>

    <div style="margin-bottom:16px;">
        <div style="font-size:13px;font-weight:700;color:var(--text-200);margin-bottom:8px;">
            InstagramController
            <span class="method-file" style="margin-left:8px;">app/Http/Controllers/Web/Tenant/InstagramController.php</span>
        </div>
        <div style="overflow-x:auto;">
        <table class="method-table">
            <thead>
                <tr><th>Method</th><th>Route</th><th>Kya karta hai</th></tr>
            </thead>
            <tbody>
                <tr><td><code class="method-name">index()</code></td><td>GET /instagram</td><td>Dashboard: stats + recent logs. Settings ka is_connected check karta hai.</td></tr>
                <tr><td><code class="method-name">settings()</code></td><td>GET /instagram/settings</td><td>Settings form show karta hai — credentials, webhook URLs.</td></tr>
                <tr><td><code class="method-name">saveSettings()</code></td><td>POST /instagram/settings</td><td>Credentials save karta hai. Agar webhook_verify_token nahi hai toh generate karta hai.</td></tr>
                <tr><td><code class="method-name">testConnection()</code></td><td>POST /instagram/test-connection</td><td>Graph API call karke account info fetch karta hai. is_connected update karta hai.</td></tr>
                <tr><td><code class="method-name">automations()</code></td><td>GET /instagram/automations</td><td>Tenant ki saari automations list karta hai — active/inactive ke saath.</td></tr>
                <tr><td><code class="method-name">storeAutomation()</code></td><td>POST /instagram/automations</td><td>Naya automation create karta hai. Keywords comma-separated se array mein convert.</td></tr>
                <tr><td><code class="method-name">toggleAutomation()</code></td><td>POST /instagram/automations/{id}/toggle</td><td>is_active toggle karta hai — AJAX response deta hai.</td></tr>
                <tr><td><code class="method-name">fetchPosts()</code></td><td>GET /instagram/automations/posts</td><td>Post Picker ke liye — tenant ke recent Instagram posts JSON mein return karta hai (thumbnail, caption, permalink).</td></tr>
                <tr><td><code class="method-name">chatbot()</code></td><td>GET /instagram/chatbot</td><td>Saare chatbot flows paginated list karta hai.</td></tr>
                <tr><td><code class="method-name">storeChatbotFlow()</code></td><td>POST /instagram/chatbot</td><td>Naya chatbot keyword flow create karta hai.</td></tr>
                <tr><td><code class="method-name">guide()</code></td><td>GET /instagram/guide</td><td>Yahi page — complete guide view.</td></tr>
                <tr><td><code class="method-name">logs()</code></td><td>GET /instagram/logs</td><td>Saare events filter ke saath paginated logs.</td></tr>
            </tbody>
        </table>
        </div>
    </div>

    <div style="margin-bottom:16px;">
        <div style="font-size:13px;font-weight:700;color:var(--text-200);margin-bottom:8px;">
            InstagramWebhookController
            <span class="method-file" style="margin-left:8px;">app/Http/Controllers/Web/InstagramWebhookController.php</span>
        </div>
        <div style="overflow-x:auto;">
        <table class="method-table">
            <thead>
                <tr><th>Method</th><th>Route</th><th>Kya karta hai</th></tr>
            </thead>
            <tbody>
                <tr><td><code class="method-name">verify()</code></td><td>GET /webhook/instagram</td><td>Meta ka webhook verification handle karta hai — hub_challenge return karta hai agar token match ho.</td></tr>
                <tr><td><code class="method-name">handle()</code></td><td>POST /webhook/instagram</td><td>Saare Meta events receive karta hai. messaging → handleDm(), changes.comments → handleComment().</td></tr>
                <tr><td><code class="method-name">handleDm()</code> (private)</td><td>—</td><td>DM event process karta hai: automations check → chatbot check → reply. Log banata hai.</td></tr>
                <tr><td><code class="method-name">handleComment()</code> (private)</td><td>—</td><td>Comment event process karta hai: automations check → execute. Log banata hai.</td></tr>
                <tr><td><code class="method-name">executeAutomation()</code> (private)</td><td>—</td><td>Automation run karta hai — send_dm / reply_comment. Log update karta hai. (n8n support 2026-07 mein hata diya gaya.)</td></tr>
            </tbody>
        </table>
        </div>
    </div>

    <div style="margin-bottom:16px;">
        <div style="font-size:13px;font-weight:700;color:var(--text-200);margin-bottom:8px;">
            Services
        </div>
        <div style="overflow-x:auto;">
        <table class="method-table">
            <thead>
                <tr><th>Service / Method</th><th>Kya karta hai</th></tr>
            </thead>
            <tbody>
                <tr><td><code class="method-name">InstagramService::sendDm()</code></td><td>Graph API v21.0 POST call — recipient ko private DM bhejta hai access token se.</td></tr>
                <tr><td><code class="method-name">InstagramService::replyToComment()</code></td><td>Comment ID ke /{commentId}/replies endpoint pe POST karta hai.</td></tr>
                <tr><td><code class="method-name">InstagramService::getAccountInfo()</code></td><td>Account details fetch karta hai — test connection ke liye.</td></tr>
                <tr><td><code class="method-name">InstagramService::getRecentMedia()</code></td><td>Graph API se recent posts (id, caption, thumbnail_url, permalink) fetch karta hai — Post Picker gallery ke liye.</td></tr>
                <tr><td><code class="method-name">WhatsappChatbotService::handleIncomingMessage()</code></td><td>WA message ke liye matching flow dhundhta hai, n8n trigger karta hai, aur reply bhejta hai.</td></tr>
                <tr><td><code class="method-name">WhatsappChatbotService::sendMessage()</code></td><td>Meta Cloud API ke through WhatsApp message bhejta hai.</td></tr>
                <tr><td><code class="method-name">N8nService::trigger()</code></td><td>Kisi bhi n8n webhook URL pe 5s timeout ke saath POST karta hai JSON payload ke saath. <strong>Ab sirf WhatsApp chatbot flows use karte hain</strong> — Instagram se unhook kar diya gaya hai.</td></tr>
            </tbody>
        </table>
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════ --}}
{{-- 9. PAYLOAD EXAMPLES                                    --}}
{{-- ══════════════════════════════════════════════════════ --}}
<div class="g-section" id="payloads">
    <div class="g-section-head">
        <div class="g-section-num">9</div>
        <div>
            <div class="g-section-title">n8n Payload Examples (WhatsApp only)</div>
            <div class="g-section-sub">n8n mein ye data milega — Instagram ke liye ab koi n8n payload nahi banta</div>
        </div>
    </div>

    <div style="display:grid;gap:14px;">
        <div>
            <div style="font-size:12.5px;font-weight:700;color:var(--text-200);margin-bottom:6px;">
                ① WhatsApp — Chatbot flow trigger hone pe (per-flow n8n URL)
            </div>
            <div class="payload-box">{
  <span class="key">"event"</span>: <span class="str">"whatsapp_chatbot_triggered"</span>,
  <span class="key">"wa_id"</span>: <span class="str">"919876543210"</span>,             <span class="comment">// phone number</span>
  <span class="key">"contact_name"</span>: <span class="str">"Rahul Sharma"</span>,        <span class="comment">// WhatsApp display name</span>
  <span class="key">"message"</span>: <span class="str">"price list chahiye"</span>,
  <span class="key">"flow_name"</span>: <span class="str">"Price Inquiry"</span>             <span class="comment">// flow ka naam</span>
}</div>
        </div>

        <div>
            <div style="font-size:12.5px;font-weight:700;color:var(--text-200);margin-bottom:6px;">
                ② Global WhatsApp webhook (har incoming message pe)
            </div>
            <div class="payload-box">{
  <span class="key">"event"</span>: <span class="str">"whatsapp_message_received"</span>,
  <span class="key">"wa_id"</span>: <span class="str">"919876543210"</span>,
  <span class="key">"message"</span>: <span class="str">"hello"</span>,
  <span class="key">"tenant_id"</span>: <span class="num">3</span>
}</div>
        </div>
    </div>

    <div class="tip-box info" style="margin-top:16px;">
        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="width:18px;height:18px;">
            <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z"/>
        </svg>
        <div>
            <strong>n8n mein lead automatically create karna ho toh:</strong><br>
            n8n mein ek HTTP Request node se aapka CRM API call karo with the payload data. Ya n8n ke built-in CRM integrations use karo. Payload mein <code>wa_id</code> ya <code>user_id</code> se contact identify hoga.
        </div>
    </div>
</div>

{{-- Quick links --}}
<div style="margin-top:32px;padding:20px 24px;background:var(--bg-surface);border:1px solid var(--border-default);border-radius:var(--r-lg);display:flex;gap:12px;flex-wrap:wrap;align-items:center;">
    <span style="font-size:13px;font-weight:700;color:var(--text-200);">Quick links:</span>
    <a href="{{ route('tenant.instagram.settings') }}" class="btn btn-sm">Instagram Settings</a>
    <a href="{{ route('tenant.instagram.automations.create') }}" class="btn btn-sm">+ New Automation</a>
    <a href="{{ route('tenant.instagram.chatbot') }}" class="btn btn-sm">Instagram Chatbot</a>
    <a href="{{ route('tenant.instagram.logs') }}" class="btn btn-sm">Activity Logs</a>
    <a href="{{ route('tenant.whatsapp.api-settings') }}" class="btn btn-sm">WhatsApp API Settings</a>
    <a href="{{ route('tenant.whatsapp.chatbot') }}" class="btn btn-sm">WhatsApp Chatbot</a>
</div>

</div>{{-- guide-body end --}}
@endsection
