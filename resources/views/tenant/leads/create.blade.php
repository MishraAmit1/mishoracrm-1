@extends('layouts.app')
@section('title', 'Add Lead')

@push('styles')
<style>
/* ── Google Fonts ── */
@import url('https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=DM+Mono:wght@400;500&display=swap');

/* ── Reset ── */
*, *::before, *::after { box-sizing: border-box; }

/* ── Page Layout ── */
.lead-page { font-family: 'DM Sans', var(--font), sans-serif; }

.lead-form-layout {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 260px;
    gap: 16px;
    margin-top: 20px;
}

/* ── Main Card ── */
.lead-main-card {
    background: var(--bg-surface);
    border: 1px solid var(--border-default);
    border-radius: 14px;
    overflow: hidden;
}

/* ── Sidebar ── */
.lead-sidebar { display: flex; flex-direction: column; gap: 14px; }

.sidebar-card {
    background: var(--bg-surface);
    border: 1px solid var(--border-default);
    border-radius: 14px;
    padding: 18px;
}

.sidebar-card-title {
    font-size: 11px;
    font-weight: 600;
    color: var(--text-300);
    text-transform: uppercase;
    letter-spacing: 0.6px;
    margin-bottom: 14px;
}

/* ── Sections ── */
.lf-section {
    padding: 22px 24px;
    border-bottom: 1px solid var(--border-subtle);
}
.lf-section:last-of-type { border-bottom: none; }

.lf-section-header {
    display: flex; align-items: flex-start; gap: 12px;
    margin-bottom: 20px;
}

.lf-section-icon {
    width: 32px; height: 32px; border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
}
.icon-blue   { background: #E6F1FB; }
.icon-teal   { background: #E1F5EE; }
.icon-amber  { background: #FAEEDA; }

.lf-section-meta { flex: 1; }
.lf-section-title {
    font-size: 13px; font-weight: 600;
    color: var(--text-100); letter-spacing: -0.1px;
}
.lf-section-sub {
    font-size: 12px; color: var(--text-300); margin-top: 1px;
}

/* ── Grid ── */
.lf-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 14px;
}
.lf-grid .span-2 { grid-column: 1 / -1; }
@media (max-width: 640px) {
    .lf-grid { grid-template-columns: 1fr; }
    .lf-grid .span-2 { grid-column: 1; }
    .lead-form-layout { grid-template-columns: 1fr; }
}

/* ── Fields ── */
.lf-field { display: flex; flex-direction: column; gap: 5px; }

.lf-label {
    font-size: 11.5px; font-weight: 600;
    color: var(--text-200); text-transform: uppercase;
    letter-spacing: 0.5px;
}
.lf-label .req { color: var(--red); margin-left: 3px; }

.lf-input-wrap { position: relative; }

.lf-prefix {
    position: absolute; left: 11px; top: 50%; transform: translateY(-50%);
    font-size: 13px; color: var(--text-300); pointer-events: none;
    font-family: 'DM Mono', monospace;
}

.lf-input {
    width: 100%;
    padding: 9px 12px;
    background: var(--bg-input);
    border: 1.5px solid var(--border-default);
    border-radius: 8px;
    color: var(--text-100);
    font-family: 'DM Sans', var(--font), sans-serif;
    font-size: 13.5px;
    outline: none;
    transition: border-color 0.15s var(--ease), box-shadow 0.15s var(--ease), background 0.15s;
    -webkit-appearance: none;
}
.lf-input:focus {
    border-color: var(--accent);
    box-shadow: 0 0 0 3px var(--accent-dim);
    background: var(--bg-surface);
}
.lf-input::placeholder { color: var(--text-400); font-size: 13px; }
.lf-input.has-prefix { padding-left: 28px; }
.lf-input.is-error { border-color: var(--red); }
.lf-input.lf-select { cursor: pointer; }
.lf-input.lf-textarea { resize: vertical; min-height: 88px; line-height: 1.5; }

.lf-field-error { font-size: 12px; color: var(--red); font-weight: 500; }
.lf-field-hint  { font-size: 12px; color: var(--text-400); }

.char-count {
    font-size: 11px; color: var(--text-400);
    text-align: right; font-family: 'DM Mono', monospace;
}

/* ── Status Dot ── */
.status-select-wrap { position: relative; }
.status-dot-indicator {
    position: absolute; left: 11px; top: 50%; transform: translateY(-50%);
    pointer-events: none;
}
.status-dot {
    width: 7px; height: 7px; border-radius: 50%;
    display: inline-block;
    transition: background 0.2s;
}
.status-select-wrap .lf-input { padding-left: 26px; }

/* ── Source Pills ── */
.source-pill-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 6px;
}
.source-pill {
    padding: 8px;
    border: 1.5px solid var(--border-default);
    border-radius: 8px;
    cursor: pointer;
    font-size: 12px;
    font-weight: 500;
    text-align: center;
    background: var(--bg-input);
    color: var(--text-200);
    transition: all 0.15s;
    font-family: 'DM Sans', var(--font), sans-serif;
}
.source-pill:hover { border-color: var(--accent); color: var(--text-100); }
.source-pill.active {
    background: #E6F1FB; border-color: #378ADD; color: #185FA5;
    font-weight: 600;
}

/* ── Priority Pills ── */
.priority-pills { display: flex; gap: 8px; }
.priority-pill {
    flex: 1; padding: 9px;
    border: 1.5px solid var(--border-default);
    border-radius: 8px; cursor: pointer;
    font-size: 12.5px; font-weight: 500;
    font-family: 'DM Sans', var(--font), sans-serif;
    background: var(--bg-input);
    color: var(--text-200);
    transition: all 0.15s;
    display: flex; align-items: center; justify-content: center; gap: 5px;
}
.priority-pill:hover { border-color: var(--border-hover, var(--accent)); color: var(--text-100); }
.priority-pill.active-low    { background: #EAF3DE; border-color: #97C459; color: #3B6D11; }
.priority-pill.active-medium { background: #FAEEDA; border-color: #EF9F27; color: #854F0B; }
.priority-pill.active-high   { background: #FCEBEB; border-color: #E24B4A; color: #A32D2D; }

/* ── Lead Score Card ── */
.lead-score-card {
    background: linear-gradient(135deg, #E6F1FB 0%, #EEEDFE 100%);
    border: 1px solid #B5D4F4;
    border-radius: 12px;
    padding: 18px;
    text-align: center;
}
.score-number {
    font-size: 40px; font-weight: 600;
    color: #185FA5; line-height: 1;
    font-family: 'DM Mono', monospace;
    transition: all 0.3s;
}
.score-label {
    font-size: 11px; color: #378ADD;
    text-transform: uppercase; letter-spacing: 0.6px;
    font-weight: 600; margin-top: 4px;
}
.score-bar-wrap {
    height: 4px; background: rgba(55,138,221,0.2);
    border-radius: 4px; margin-top: 14px; overflow: hidden;
}
.score-bar {
    height: 100%; background: #378ADD; border-radius: 4px;
    transition: width 0.45s cubic-bezier(.4,0,.2,1);
}

/* ── Assign Options ── */
.assign-option {
    display: flex; align-items: center; gap: 10px;
    padding: 8px 10px; border-radius: 8px;
    cursor: pointer; transition: all 0.15s;
    border: 1.5px solid transparent;
}
.assign-option:hover { background: var(--bg-elevated); }
.assign-option.selected { border-color: rgba(0,0,0,0.1); }

.assign-avatar {
    width: 30px; height: 30px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 11px; font-weight: 600; flex-shrink: 0;
}
.assign-name { font-size: 13px; font-weight: 500; color: var(--text-100); }
.assign-meta { font-size: 11px; color: var(--text-300); }

/* ── Tips ── */
.tips-list { display: flex; flex-direction: column; gap: 10px; }
.tip-item {
    display: flex; align-items: flex-start; gap: 8px;
    font-size: 12px; color: var(--text-300); line-height: 1.4;
}
.tip-dot {
    width: 5px; height: 5px; border-radius: 50%;
    background: var(--accent, #378ADD); margin-top: 5px; flex-shrink: 0;
}

/* ── Footer ── */
.lf-footer {
    display: flex; align-items: center; justify-content: space-between;
    padding: 16px 24px;
    background: var(--bg-elevated);
    border-top: 1px solid var(--border-subtle);
}
.lf-footer-note { font-size: 12px; color: var(--text-300); }
.lf-footer-note strong { color: var(--text-200); }
.lf-footer-actions { display: flex; gap: 8px; }

/* ── Spin ── */
@keyframes lf-spin {
    from { transform: rotate(0deg); }
    to   { transform: rotate(360deg); }
}
.spinning { animation: lf-spin 0.7s linear infinite; }
</style>
@endpush

@section('content')
<div class="lead-page">

    {{-- Page Header --}}
    <div class="page-head">
        <div>
            <div style="font-size:12px;color:var(--text-300);margin-bottom:4px;display:flex;align-items:center;gap:6px">
                <a href="{{ route('tenant.leads.index') }}" style="color:var(--text-300);text-decoration:none">Leads</a>
                <span style="opacity:.4">›</span>
                <span>Add New Lead</span>
            </div>
            <div class="page-title">Add New Lead</div>
        </div>
        <a href="{{ route('tenant.leads.index') }}" class="btn btn-secondary">
            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 12H5M12 5l-7 7 7 7"/></svg>
            Back to Leads
        </a>
    </div>

    <form method="POST" action="{{ route('tenant.leads.store') }}" novalidate id="leadForm">
        @csrf

        {{-- Hidden inputs for pill selections --}}
        <input type="hidden" name="source"   id="sourceHidden"   value="{{ old('source', 'website') }}">
        <input type="hidden" name="priority" id="priorityHidden" value="{{ old('priority', 'medium') }}">

        <div class="lead-form-layout">

            {{-- ── Main Form ── --}}
            <div class="lead-main-card">

                {{-- Basic Information --}}
                <div class="lf-section">
                    <div class="lf-section-header">
                        <div class="lf-section-icon icon-blue">
                            <svg width="16" height="16" fill="none" stroke="#378ADD" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        </div>
                        <div class="lf-section-meta">
                            <div class="lf-section-title">Basic Information</div>
                            <div class="lf-section-sub">Contact details aur company information</div>
                        </div>
                    </div>

                    <div class="lf-grid">
                        <div class="lf-field">
                            <label class="lf-label">Full Name <span class="req">*</span></label>
                            <input type="text" name="name"
                                   class="lf-input {{ $errors->has('name') ? 'is-error' : '' }}"
                                   placeholder="e.g. Rahul Sharma"
                                   value="{{ old('name') }}" required />
                            @error('name') <span class="lf-field-error">{{ $message }}</span> @enderror
                        </div>

                        <div class="lf-field">
                            <label class="lf-label">Phone <span class="req">*</span></label>
                            <div class="lf-input-wrap">
                                <span class="lf-prefix">+91</span>
                                <input type="tel" name="phone"
                                       class="lf-input has-prefix {{ $errors->has('phone') ? 'is-error' : '' }}"
                                       placeholder="98765 43210"
                                       value="{{ old('phone') }}" required />
                            </div>
                            @error('phone') <span class="lf-field-error">{{ $message }}</span> @enderror
                        </div>

                        <div class="lf-field">
                            <label class="lf-label">Email</label>
                            <input type="email" name="email"
                                   class="lf-input {{ $errors->has('email') ? 'is-error' : '' }}"
                                   placeholder="rahul@company.com"
                                   value="{{ old('email') }}" />
                            @error('email') <span class="lf-field-error">{{ $message }}</span> @enderror
                        </div>

                        <div class="lf-field">
                            <label class="lf-label">Company</label>
                            <input type="text" name="company" class="lf-input"
                                   placeholder="Company name" value="{{ old('company') }}" />
                        </div>

                        <div class="lf-field">
                            <label class="lf-label">Designation</label>
                            <input type="text" name="designation" class="lf-input"
                                   placeholder="e.g. Purchase Manager" value="{{ old('designation') }}" />
                        </div>

                        <div class="lf-field">
                            <label class="lf-label">Lead Value (₹)</label>
                            <div class="lf-input-wrap">
                                <span class="lf-prefix">₹</span>
                                <input type="number" name="lead_value" id="leadValueInput"
                                       class="lf-input has-prefix"
                                       placeholder="0" value="{{ old('lead_value') }}"
                                       min="0" step="1000" />
                            </div>
                            <span class="lf-field-hint">Expected deal value</span>
                        </div>

                        <div class="lf-field">
                            <label class="lf-label">City</label>
                            <input type="text" name="city" class="lf-input"
                                   placeholder="Mumbai" value="{{ old('city') }}" />
                        </div>

                        <div class="lf-field">
                            <label class="lf-label">State</label>
                            <input type="text" name="state" class="lf-input"
                                   placeholder="Maharashtra" value="{{ old('state') }}" />
                        </div>
                    </div>
                </div>

                {{-- Lead Details --}}
                <div class="lf-section">
                    <div class="lf-section-header">
                        <div class="lf-section-icon icon-teal">
                            <svg width="16" height="16" fill="none" stroke="#1D9E75" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                        </div>
                        <div class="lf-section-meta">
                            <div class="lf-section-title">Lead Details</div>
                            <div class="lf-section-sub">Source, status aur assignment</div>
                        </div>
                    </div>
{{-- custom fields --}}




{{-- End mein, form close hone se pehle --}}
@include('components.custom-fields.render', [
    'fields' => \App\Models\CustomField::forModule('lead'),
    'values' => [],
])

{{-- end custom fields --}}
                    <div class="lf-grid">
                        {{-- Source Pills --}}
                        <div class="lf-field span-2">
                            <label class="lf-label">Source</label>
                            <div class="source-pill-grid" id="sourcePillGrid">
                                @foreach($sources as $val => $label)
                                <button type="button"
                                        class="source-pill {{ old('source', 'website') === $val ? 'active' : '' }}"
                                        data-val="{{ $val }}">{{ $label }}</button>
                                @endforeach
                            </div>
                        </div>

                        {{-- Status --}}
                        <div class="lf-field">
                            <label class="lf-label">Status</label>
                            <div class="status-select-wrap">
                                <span class="status-dot-indicator">
                                    <span class="status-dot" id="statusDot" style="background:#378ADD"></span>
                                </span>
                                <select name="status" class="lf-input lf-select" id="statusSelect">
                                    @foreach($statuses as $val => $label)
                                    <option value="{{ $val }}" {{ old('status','new') === $val ? 'selected' : '' }}>
                                        {{ is_array($label) ? $label['label'] : $label }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        {{-- Expected Close Date --}}
                        <div class="lf-field">
                            <label class="lf-label">Expected Close Date</label>
                            <input type="date" name="expected_close_date" id="closeDateInput"
                                   class="lf-input"
                                   value="{{ old('expected_close_date') }}"
                                   min="{{ now()->addDay()->format('Y-m-d') }}" />
                        </div>

                        {{-- Priority Pills --}}
                        <div class="lf-field span-2">
                            <label class="lf-label">Priority</label>
                            <div class="priority-pills" id="priorityPills">
                                @php $currentPriority = old('priority', 'medium'); @endphp
                                <button type="button"
                                        class="priority-pill {{ $currentPriority === 'low' ? 'active-low' : '' }}"
                                        data-p="low">
                                    <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7"/></svg>
                                    Low
                                </button>
                                <button type="button"
                                        class="priority-pill {{ $currentPriority === 'medium' ? 'active-medium' : '' }}"
                                        data-p="medium">
                                    <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14"/></svg>
                                    Medium
                                </button>
                                <button type="button"
                                        class="priority-pill {{ $currentPriority === 'high' ? 'active-high' : '' }}"
                                        data-p="high">
                                    <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                                    High
                                </button>
                            </div>
                        </div>

                        {{-- Assign To --}}
                        <div class="lf-field span-2">
                            <label class="lf-label">Assign To</label>
                            <select name="assigned_to" class="lf-input lf-select" id="assignSelect">
                                <option value="">— Unassigned —</option>
                                @foreach($staffList as $staff)
                                <option value="{{ $staff->id }}"
                                    {{ old('assigned_to') == $staff->id ? 'selected' : '' }}>
                                    {{ $staff->name }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                {{-- Notes --}}
                <div class="lf-section">
                    <div class="lf-section-header">
                        <div class="lf-section-icon icon-amber">
                            <svg width="16" height="16" fill="none" stroke="#BA7517" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        </div>
                        <div class="lf-section-meta">
                            <div class="lf-section-title">Notes</div>
                            <div class="lf-section-sub">Additional context about this lead</div>
                        </div>
                    </div>
                    <div class="lf-field">
                        <textarea name="notes" id="notesArea"
                                  class="lf-input lf-textarea"
                                  maxlength="500"
                                  placeholder="Add any relevant notes, requirements, or context about this lead...">{{ old('notes') }}</textarea>
                        <div class="char-count" id="charCount">0 / 500</div>
                    </div>
                </div>

                {{-- Footer Actions --}}
                <div class="lf-footer">
                    <div class="lf-footer-note">Fields marked <strong>*</strong> are required</div>
                    <div class="lf-footer-actions">
                        <a href="{{ route('tenant.leads.index') }}" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary" id="submitBtn">
                            <svg id="submitIcon" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                            <span id="submitText">Create Lead</span>
                        </button>
                    </div>
                </div>

            </div>{{-- /lead-main-card --}}

            {{-- ── Sidebar ── --}}
            <div class="lead-sidebar">

                {{-- Lead Score --}}
                <div class="lead-score-card">
                    <div class="score-number" id="scoreNumber">40</div>
                    <div class="score-label">Lead Score</div>
                    <div class="score-bar-wrap">
                        <div class="score-bar" id="scoreBar" style="width:40%"></div>
                    </div>
                </div>

                {{-- Quick Assign --}}
                <div class="sidebar-card">
                    <div class="sidebar-card-title">Quick Assign</div>
                    <div id="assignAvatarList">
                        @php
                        $avatarColors = [
                            ['bg'=>'#E6F1FB','text'=>'#185FA5'],
                            ['bg'=>'#E1F5EE','text'=>'#0F6E56'],
                            ['bg'=>'#FAEEDA','text'=>'#854F0B'],
                            ['bg'=>'#EEEDFE','text'=>'#3C3489'],
                        ];
                        @endphp
                        @foreach($staffList->take(4) as $i => $staff)
                        @php
                            $c = $avatarColors[$i % count($avatarColors)];
                            $initials = collect(explode(' ', $staff->name))->filter()->map(fn($p)=>strtoupper($p[0]))->join('');
                        @endphp
                        <div class="assign-option"
                             data-id="{{ $staff->id }}"
                             data-bg="{{ $c['bg'] }}"
                             data-text="{{ $c['text'] }}"
                             onclick="quickAssign(this)">
                            <div class="assign-avatar"
                                 style="background:{{ $c['bg'] }};color:{{ $c['text'] }}">
                                {{ substr($initials, 0, 2) }}
                            </div>
                            <div>
                                <div class="assign-name">{{ $staff->name }}</div>
                                <div class="assign-meta">{{ $staff->leads_count ?? rand(1,8) }} active leads</div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>

                {{-- Tips --}}
                <div class="sidebar-card">
                    <div class="sidebar-card-title">Tips</div>
                    <div class="tips-list">
                        <div class="tip-item">
                            <div class="tip-dot"></div>
                            <span>Phone number required for SMS follow-ups</span>
                        </div>
                        <div class="tip-item">
                            <div class="tip-dot"></div>
                            <span>Close date enable karne se pipeline forecast improve hota hai</span>
                        </div>
                        <div class="tip-item">
                            <div class="tip-dot"></div>
                            <span>High priority leads ke liye team ko instant notification milti hai</span>
                        </div>
                        <div class="tip-item">
                            <div class="tip-dot"></div>
                            <span>Referral leads ka conversion rate 3x higher hota hai</span>
                        </div>
                    </div>
                </div>

            </div>{{-- /lead-sidebar --}}

        </div>{{-- /lead-form-layout --}}
    </form>

</div>
@endsection

@push('scripts')
<script>
(function () {
    // ── Status dot color map ──
    const statusColors = {
        new: '#378ADD', contacted: '#1D9E75', qualified: '#639922',
        proposal: '#BA7517', negotiation: '#EF9F27', won: '#1D9E75', lost: '#E24B4A'
    };

    const statusSelect = document.getElementById('statusSelect');
    const statusDot    = document.getElementById('statusDot');
    if (statusSelect) {
        statusSelect.addEventListener('change', function () {
            statusDot.style.background = statusColors[this.value] || '#378ADD';
        });
        // Init dot on page load (handles old() value)
        statusDot.style.background = statusColors[statusSelect.value] || '#378ADD';
    }

    // ── Source pills ──
    document.querySelectorAll('.source-pill').forEach(btn => {
        btn.addEventListener('click', function () {
            document.querySelectorAll('.source-pill').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            document.getElementById('sourceHidden').value = this.dataset.val;
            updateScore();
        });
    });

    // ── Priority pills ──
    document.querySelectorAll('.priority-pill').forEach(btn => {
        btn.addEventListener('click', function () {
            document.querySelectorAll('.priority-pill').forEach(b => {
                b.classList.remove('active-low', 'active-medium', 'active-high');
            });
            this.classList.add('active-' + this.dataset.p);
            document.getElementById('priorityHidden').value = this.dataset.p;
            updateScore();
        });
    });

    // ── Notes char count ──
    const notesArea = document.getElementById('notesArea');
    const charCount = document.getElementById('charCount');
    if (notesArea) {
        notesArea.addEventListener('input', function () {
            charCount.textContent = this.value.length + ' / 500';
            updateScore();
        });
        // Init on load
        charCount.textContent = notesArea.value.length + ' / 500';
    }

    // ── Lead Value / Close Date trigger score ──
    const leadVal   = document.getElementById('leadValueInput');
    const closeDate = document.getElementById('closeDateInput');
    if (leadVal)   leadVal.addEventListener('input', updateScore);
    if (closeDate) closeDate.addEventListener('input', updateScore);

    // ── Score calculator ──
    function updateScore() {
        let score = 20;
        const priority = document.getElementById('priorityHidden').value;
        if (priority === 'high')   score += 25;
        else if (priority === 'medium') score += 15;
        else score += 5;

        const source = document.getElementById('sourceHidden').value;
        if (source === 'referral') score += 20;
        else if (source === 'website') score += 15;
        else if (source === 'social')  score += 10;
        else score += 5;

        const val = parseInt(leadVal ? leadVal.value : 0) || 0;
        if (val > 100000) score += 20;
        else if (val > 50000) score += 15;
        else if (val > 10000) score += 10;

        if (closeDate && closeDate.value) score += 10;
        if (notesArea && notesArea.value.length > 20) score += 5;

        score = Math.min(score, 100);
        document.getElementById('scoreNumber').textContent = score;
        document.getElementById('scoreBar').style.width    = score + '%';
    }

    // ── Quick Assign sidebar click ──
    window.quickAssign = function (el) {
        document.querySelectorAll('.assign-option').forEach(o => {
            o.classList.remove('selected');
            o.style.background = 'transparent';
        });
        el.classList.add('selected');
        el.style.background = el.dataset.bg;
        const assignSelect = document.getElementById('assignSelect');
        if (assignSelect) assignSelect.value = el.dataset.id;
    };

    // ── Submit button loading state ──
    document.getElementById('leadForm').addEventListener('submit', function () {
        const btn  = document.getElementById('submitBtn');
        const icon = document.getElementById('submitIcon');
        const text = document.getElementById('submitText');
        if (btn) {
            icon.style.animation = 'lf-spin .7s linear infinite';
            text.textContent = 'Saving...';
            btn.disabled = true;
        }
    });

    // ── Init score on load ──
    updateScore();
})();
</script>
@endpush