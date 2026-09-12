@extends('layouts.app')
@section('title', 'AI & Workflow Automation')

@push('styles')
<style>
/* ── Page hero ──────────────────────────────────────────────────── */
.auto-hero {
    background: linear-gradient(135deg, var(--bg-elevated) 0%, var(--bg-surface) 100%);
    border: 1px solid var(--border-subtle);
    border-radius: var(--r-xl);
    padding: 36px 32px;
    margin-bottom: 28px;
    display: flex;
    align-items: center;
    gap: 22px;
    position: relative;
    overflow: hidden;
}
.auto-hero::before {
    content: '';
    position: absolute;
    top: -40px; right: -40px;
    width: 200px; height: 200px;
    background: radial-gradient(circle, var(--accent-glow) 0%, transparent 70%);
    pointer-events: none;
}
.auto-hero-icon {
    width: 60px; height: 60px; border-radius: var(--r-lg);
    background: linear-gradient(135deg, var(--accent), var(--accent-hover));
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
}
.auto-hero-icon svg { width: 28px; height: 28px; stroke: #fff; }
.auto-hero-text h1 { font-size: 20px; font-weight: 800; color: var(--text-100); margin-bottom: 5px; letter-spacing: -0.3px; }
.auto-hero-text p  { font-size: 13.5px; color: var(--text-300); line-height: 1.6; max-width: 520px; }

/* ── Section header ─────────────────────────────────────────────── */
.auto-section-hd { margin-bottom: 18px; }
.auto-section-hd h2 { font-size: 15px; font-weight: 700; color: var(--text-100); margin-bottom: 3px; }
.auto-section-hd p  { font-size: 13px; color: var(--text-300); }

/* ── Template grid ──────────────────────────────────────────────── */
.tmpl-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(290px, 1fr));
    gap: 16px;
    margin-bottom: 32px;
}

/* ── Template card ──────────────────────────────────────────────── */
.tmpl-card {
    background: var(--bg-surface);
    border: 1px solid var(--border-subtle);
    border-radius: var(--r-xl);
    padding: 22px;
    display: flex; flex-direction: column; gap: 14px;
    transition: border-color .18s, box-shadow .18s, transform .18s;
}
.tmpl-card:hover {
    border-color: var(--border-default);
    box-shadow: var(--shadow-md);
    transform: translateY(-2px);
}
.tmpl-card-head { display: flex; align-items: flex-start; gap: 14px; }
.tmpl-icon {
    width: 42px; height: 42px; border-radius: var(--r-md);
    display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}
.tmpl-icon svg { width: 20px; height: 20px; }
.tmpl-icon.blue   { background: #dbeafe; color: #1d4ed8; }
.tmpl-icon.purple { background: #ede9fe; color: #6d28d9; }
.tmpl-icon.green  { background: #dcfce7; color: #15803d; }
.tmpl-icon.orange { background: #fff7ed; color: #c2410c; }
.tmpl-icon.pink   { background: #fce7f3; color: #be185d; }
.tmpl-icon.teal   { background: #ccfbf1; color: #0f766e; }
.tmpl-icon.yellow { background: #fefce8; color: #854d0e; }
[data-theme="light"] .tmpl-icon.blue   { background: rgba(59,130,246,.15);  color: #60a5fa; }
[data-theme="light"] .tmpl-icon.purple { background: rgba(139,92,246,.15);  color: #a78bfa; }
[data-theme="light"] .tmpl-icon.green  { background: rgba(34,197,94,.15);   color: #4ade80; }
[data-theme="light"] .tmpl-icon.orange { background: rgba(249,115,22,.15);  color: #fb923c; }
[data-theme="light"] .tmpl-icon.pink   { background: rgba(236,72,153,.15);  color: #f472b6; }
[data-theme="light"] .tmpl-icon.teal   { background: rgba(20,184,166,.15);  color: #2dd4bf; }
[data-theme="light"] .tmpl-icon.yellow { background: rgba(234,179,8,.15);   color: #facc15; }

.tmpl-meta h3 { font-size: 14px; font-weight: 700; color: var(--text-100); margin-bottom: 4px; }
.tmpl-meta p  { font-size: 12.5px; color: var(--text-300); line-height: 1.55; }

.tmpl-features { display: flex; flex-direction: column; gap: 5px; }
.tmpl-feat-item { display: flex; align-items: center; gap: 7px; font-size: 12px; color: var(--text-200); }
.tmpl-feat-item svg { width: 13px; height: 13px; color: var(--green); flex-shrink: 0; }

.tmpl-badge {
    display: inline-flex; align-items: center;
    font-size: 11px; font-weight: 600;
    padding: 2px 8px; border-radius: 20px;
    background: var(--bg-elevated);
    border: 1px solid var(--border-subtle);
    color: var(--text-400);
}

.tmpl-footer {
    display: flex; align-items: center; justify-content: space-between;
    margin-top: auto; padding-top: 2px;
}

/* ── CTA banner ─────────────────────────────────────────────────── */
.auto-cta-banner {
    background: linear-gradient(135deg, var(--accent) 0%, var(--accent-hover) 100%);
    border-radius: var(--r-xl);
    padding: 28px 32px;
    display: flex; align-items: center; justify-content: space-between; gap: 20px;
    margin-bottom: 32px;
    flex-wrap: wrap;
}
.auto-cta-banner h3 { font-size: 16px; font-weight: 700; color: #fff; margin-bottom: 4px; }
.auto-cta-banner p  { font-size: 13px; color: rgba(255,255,255,.8); }
.auto-cta-btn {
    background: #fff; color: var(--accent);
    border: none; padding: 10px 20px;
    border-radius: var(--r-md);
    font-weight: 700; font-size: 13px;
    cursor: pointer; white-space: nowrap;
    transition: opacity .15s; flex-shrink: 0;
}
.auto-cta-btn:hover { opacity: .9; }

/* ── Status badges ───────────────────────────────────────────────── */
.req-status { display:inline-flex;align-items:center;font-size:11.5px;font-weight:600;padding:3px 9px;border-radius:20px; }
.req-status.new         { background:var(--accent-dim); color:var(--accent); }
.req-status.in_progress { background:var(--amber-dim);  color:var(--amber);  }
.req-status.completed   { background:var(--green-dim);  color:var(--green);  }
.req-status.rejected    { background:var(--red-dim);    color:var(--red);    }

/* ── Spinner animation ──────────────────────────────────────────── */
@keyframes reqSpin { to { transform: rotate(360deg); } }
.req-spinner {
    display: inline-block;
    width: 13px; height: 13px;
    border: 2px solid currentColor;
    border-right-color: transparent;
    border-radius: 50%;
    animation: reqSpin .6s linear infinite;
    vertical-align: middle;
    margin-right: 6px;
}

/* ── Modal form overrides ───────────────────────────────────────── */
#reqBackdrop .modal-box .form-group { margin-bottom: 14px; }
#reqBackdrop .modal-box textarea.form-control { resize: vertical; min-height: 90px; }
#reqBackdrop .req-close-btn {
    width: 28px; height: 28px; flex-shrink: 0;
    border-radius: var(--r-sm);
    background: var(--bg-elevated);
    border: 1px solid var(--border-subtle);
    color: var(--text-400);
    cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    margin-top: 2px;
    transition: color .15s, border-color .15s;
}
#reqBackdrop .req-close-btn:hover { color: var(--text-100); border-color: var(--border-default); }
#reqBackdrop .req-close-btn svg { width: 13px; height: 13px; }

/* ── MOBILE REQUEST CARDS (<768px) ────────────────────────────── */
.automation-mobile-list{display:none}
@media(max-width:768px){
    .automation-table-wrap{display:none}
    .automation-mobile-list{display:flex;flex-direction:column;gap:10px;padding:14px}
}
.au-card{background:var(--bg-surface);border:1px solid var(--border-subtle);border-radius:var(--r-md);padding:14px}
.au-top{display:flex;align-items:flex-start;justify-content:space-between;gap:10px;margin-bottom:10px}
.au-title{font-size:13.5px;font-weight:700;color:var(--text-100);word-break:break-word}
.au-sub{font-size:11.5px;color:var(--text-400);margin-top:2px}
.au-contact{font-size:12px;color:var(--text-300);margin-bottom:10px;padding:9px 11px;background:var(--bg-elevated);border-radius:8px}
.au-foot{display:flex;align-items:center;justify-content:flex-end;font-size:11.5px;color:var(--text-400);padding-top:10px;border-top:1px solid var(--border-subtle)}
</style>
@endpush

@section('content')

{{-- Hero --}}
<div class="auto-hero">
    <div class="auto-hero-icon">
        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round"
                d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z"/>
        </svg>
    </div>
    <div class="auto-hero-text">
        <h1>AI & Workflow Automation</h1>
        <p>Automate repetitive tasks and grow faster with smart workflows built for your business. Browse templates below or request a custom automation.</p>
    </div>
</div>

{{-- Templates --}}
@if($templates->isNotEmpty())
<div class="auto-section-hd">
    <h2>Popular Automation Templates</h2>
    <p>Click "Request This" on any template to get it built for your business</p>
</div>

<div class="tmpl-grid">
    @foreach($templates as $tmpl)
    <div class="tmpl-card">
        <div class="tmpl-card-head">
            <div class="tmpl-icon {{ $tmpl->color }}">
                @if($tmpl->icon_type === 'zap')
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z"/></svg>
                @elseif($tmpl->icon_type === 'chat')
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.625 9.75a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375m-13.5 3.01c0 1.6 1.123 2.994 2.707 3.227 1.087.16 2.185.283 3.293.369V21l4.184-4.183a1.14 1.14 0 01.778-.332 48.294 48.294 0 005.83-.498c1.585-.233 2.708-1.626 2.708-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0012 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018z"/></svg>
                @elseif($tmpl->icon_type === 'invoice')
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z"/></svg>
                @elseif($tmpl->icon_type === 'users')
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.75 3.75 0 11-6.75 0 3.75 3.75 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/></svg>
                @elseif($tmpl->icon_type === 'bell')
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0"/></svg>
                @elseif($tmpl->icon_type === 'star')
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z"/></svg>
                @else
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z"/></svg>
                @endif
            </div>
            <div class="tmpl-meta">
                <h3>{{ $tmpl->title }}</h3>
                <p>{{ $tmpl->description }}</p>
            </div>
        </div>

        @if($tmpl->features)
        <div class="tmpl-features">
            @foreach(array_slice($tmpl->features, 0, 4) as $feat)
            <div class="tmpl-feat-item">
                <svg fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                </svg>
                {{ $feat }}
            </div>
            @endforeach
        </div>
        @endif

        <div class="tmpl-footer">
            @if($tmpl->suitable_for)
                <span class="tmpl-badge">{{ $tmpl->suitable_for }}</span>
            @else
                <span></span>
            @endif
            <button class="btn btn-primary btn-sm"
                onclick="openReqModal({{ $tmpl->id }}, '{{ addslashes($tmpl->title) }}')">
                Request This
            </button>
        </div>
    </div>
    @endforeach
</div>
@else
<div class="card" style="margin-bottom:28px;">
    <div class="card-body" style="text-align:center;padding:48px 24px;color:var(--text-300);">
        <svg fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"
             style="width:40px;height:40px;margin:0 auto 12px;display:block;opacity:.4;">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z"/>
        </svg>
        <p style="font-size:14px;">Automation templates coming soon!</p>
    </div>
</div>
@endif

{{-- CTA Banner --}}
<div class="auto-cta-banner">
    <div>
        <h3>Want a Custom AI Workflow for Your Business?</h3>
        <p>Tell us your challenge — we'll build an n8n automation workflow tailored exactly for you.</p>
    </div>
    <button class="auto-cta-btn" onclick="openReqModal(null, null)">
        Request Custom Workflow
    </button>
</div>

{{-- My Requests --}}
@if($myRequests->isNotEmpty())
<div class="auto-section-hd">
    <h2>My Requests</h2>
    <p>Track your submitted automation requests</p>
</div>
<div class="card" style="margin-bottom:28px;">
<div class="automation-table-wrap" style="overflow-x:auto;">
    <table class="data-table">
        <thead>
            <tr>
                <th>Workflow</th>
                <th>Business Type</th>
                <th>Contact</th>
                <th>Status</th>
                <th>Submitted</th>
            </tr>
        </thead>
        <tbody>
            @foreach($myRequests as $req)
            <tr>
                <td style="font-weight:600;" data-label="Workflow">{{ $req->template?->title ?? 'Custom Request' }}</td>
                <td data-label="Business Type">{{ $req->business_type }}</td>
                <td style="font-size:12px;color:var(--text-300);" data-label="Contact">
                    {{ ucfirst($req->contact_preference) }}: {{ $req->contact_value }}
                </td>
                <td data-label="Status">
                    <span class="req-status {{ $req->status }}">
                        {{ ucfirst(str_replace('_', ' ', $req->status)) }}
                    </span>
                </td>
                <td style="font-size:12px;color:var(--text-300);" data-label="Submitted">{{ $req->created_at->diffForHumans() }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

{{-- Mobile card list (shown only <768px, table above hides itself) --}}
<div class="automation-mobile-list">
@foreach($myRequests as $req)
<div class="au-card">
    <div class="au-top">
        <div>
            <div class="au-title">{{ $req->template?->title ?? 'Custom Request' }}</div>
            <div class="au-sub">{{ $req->business_type }}</div>
        </div>
        <span class="req-status {{ $req->status }}">
            {{ ucfirst(str_replace('_', ' ', $req->status)) }}
        </span>
    </div>
    <div class="au-contact">{{ ucfirst($req->contact_preference) }}: {{ $req->contact_value }}</div>
    <div class="au-foot">{{ $req->created_at->diffForHumans() }}</div>
</div>
@endforeach
</div>
</div>
@endif

{{-- ── Request Modal ──────────────────────────────────────────────── --}}
<div class="modal-backdrop" id="reqBackdrop" style="display:none;">
    <div class="modal-box" style="max-width:520px;text-align:left;padding:28px;" onclick="event.stopPropagation()">

        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;margin-bottom:20px;">
            <div>
                <div class="modal-title" id="reqModalTitle">Request Custom Workflow</div>
                <div style="font-size:13px;color:var(--text-300);margin-top:3px;" id="reqModalSub">Tell us your challenge — we'll build the automation for you.</div>
            </div>
            <button type="button" class="req-close-btn" onclick="closeReqModal()">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <form id="reqForm" autocomplete="off">
            <input type="hidden" id="reqTemplateId" name="workflow_template_id" value="">

            <div class="form-group">
                <label class="form-label">Business Type <span style="color:var(--red);">*</span></label>
                <input type="text" name="business_type" class="form-control"
                       placeholder="e.g. Real Estate, EdTech, E-commerce" required>
            </div>

            <div class="form-group">
                <label class="form-label">What do you want to automate? <span style="color:var(--red);">*</span></label>
                <textarea name="problem_description" class="form-control"
                          placeholder="Describe your challenge or the process you want automated..."
                          rows="3" required></textarea>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                <div class="form-group" style="margin-bottom:0;">
                    <label class="form-label">Contact Via <span style="color:var(--red);">*</span></label>
                    <select name="contact_preference" id="reqContactPref" class="form-control" onchange="reqUpdateContactLabel()">
                        <option value="whatsapp">WhatsApp</option>
                        <option value="email">Email</option>
                    </select>
                </div>
                <div class="form-group" style="margin-bottom:0;">
                    <label class="form-label" id="reqContactLabel">WhatsApp Number <span style="color:var(--red);">*</span></label>
                    <input type="text" name="contact_value" id="reqContactValue" class="form-control"
                           placeholder="+91 98765 43210" required>
                </div>
            </div>

            <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:24px;padding-top:18px;border-top:1px solid var(--border-subtle);">
                <button type="button" class="btn btn-secondary" onclick="closeReqModal()">Cancel</button>
                <button type="submit" class="btn btn-primary" id="reqSubmitBtn">Submit Request</button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
(function () {
    var REQ_URL = '{{ route("tenant.automation.request") }}';
    var backdrop = document.getElementById('reqBackdrop');

    // Close when clicking backdrop (outside modal-box)
    backdrop.addEventListener('click', function (e) {
        if (e.target === backdrop) closeReqModal();
    });

    window.openReqModal = function (templateId, templateTitle) {
        document.getElementById('reqForm').reset();
        document.getElementById('reqTemplateId').value = templateId || '';
        document.getElementById('reqModalTitle').textContent = templateTitle
            ? 'Request: ' + templateTitle
            : 'Request Custom Workflow';
        document.getElementById('reqModalSub').textContent = templateTitle
            ? 'Fill in your details and we\'ll set this up for your business.'
            : 'Tell us your challenge — we\'ll build the automation for you.';
        reqUpdateContactLabel();

        backdrop.style.display = 'flex';
        requestAnimationFrame(function () { backdrop.classList.add('open'); });
    };

    window.closeReqModal = function () {
        backdrop.classList.remove('open');
        setTimeout(function () { backdrop.style.display = 'none'; }, 200);
    };

    window.reqUpdateContactLabel = function () {
        var pref = document.getElementById('reqContactPref').value;
        document.getElementById('reqContactLabel').innerHTML =
            (pref === 'whatsapp' ? 'WhatsApp Number' : 'Email Address') +
            ' <span style="color:var(--red);">*</span>';
        var inp = document.getElementById('reqContactValue');
        inp.placeholder = pref === 'whatsapp' ? '+91 98765 43210' : 'you@example.com';
        inp.type        = pref === 'email' ? 'email' : 'text';
    };

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && backdrop.classList.contains('open')) closeReqModal();
    });

    document.getElementById('reqForm').addEventListener('submit', async function (e) {
        e.preventDefault();
        var btn  = document.getElementById('reqSubmitBtn');
        var orig = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="req-spinner"></span>Submitting...';

        try {
            var res  = await fetch(REQ_URL, {
                method:  'POST',
                body:    new FormData(this),
                headers: { 'X-CSRF-TOKEN': window.CrmCsrf, 'Accept': 'application/json' },
            });
            var json = await res.json();

            if (res.ok && json.success) {
                closeReqModal();
                showToast(json.message || 'Request submitted! We\'ll contact you soon.', 'success');
                setTimeout(function () { location.reload(); }, 1800);
            } else {
                var msg = json.errors
                    ? Object.values(json.errors).flat().join(' ')
                    : (json.message || 'Something went wrong.');
                showToast(msg, 'error');
            }
        } catch (err) {
            showToast('Network error. Please try again.', 'error');
        } finally {
            btn.disabled = false;
            btn.innerHTML = orig;
        }
    });
})();
</script>
@endpush
