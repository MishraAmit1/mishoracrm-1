@extends('layouts.app')
@section('title', 'Add Contact')

@push('styles')
<style>
@import url('https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=DM+Mono:wght@400;500&display=swap');
@import url('https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.19.0/dist/tabler-icons.min.css');

.cf-page { font-family: 'DM Sans', var(--font), sans-serif; }
.cf-layout { display:grid; grid-template-columns:minmax(0,1fr) 260px; gap:16px; margin-top:20px; }
@media(max-width:860px){ .cf-layout { grid-template-columns:1fr; } }
.cf-main { background:var(--bg-surface); border:1px solid var(--border-default); border-radius:14px; overflow:hidden; }
.cf-section { padding:22px 24px; border-bottom:1px solid var(--border-subtle); }
.cf-section:last-of-type { border-bottom:none; }
.cf-section-header { display:flex; align-items:flex-start; gap:12px; margin-bottom:18px; }
.cf-section-icon { width:32px; height:32px; border-radius:8px; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.cf-section-title { font-size:13px; font-weight:600; color:var(--text-100); letter-spacing:-0.1px; }
.cf-section-sub { font-size:12px; color:var(--text-300); margin-top:1px; }
.cf-grid { display:grid; grid-template-columns:1fr 1fr; gap:14px; }
.cf-grid .span-full { grid-column:1/-1; }
@media(max-width:640px){ .cf-grid { grid-template-columns:1fr; } .cf-grid .span-full { grid-column:1; } }
.cf-field { display:flex; flex-direction:column; gap:5px; }
.cf-label { font-size:11.5px; font-weight:600; color:var(--text-200); text-transform:uppercase; letter-spacing:0.5px; display:flex; align-items:center; gap:5px; flex-wrap:wrap; }
.cf-req { color:var(--red); margin-left:2px; }

.cf-input {
    width:100%; padding:9px 12px;
    background:var(--bg-input); border:1.5px solid var(--border-default);
    border-radius:8px; color:var(--text-100);
    font-family:'DM Sans',var(--font),sans-serif; font-size:13.5px; outline:none;
    transition:border-color .15s, box-shadow .15s, background .2s;
    -webkit-appearance:none;
}
.cf-input:focus { border-color:var(--accent); box-shadow:0 0 0 3px var(--accent-dim); background:var(--bg-surface); }
.cf-input::placeholder { color:var(--text-400); font-size:13px; }
.cf-input.is-error { border-color:var(--red); }

/* ── Prefilled state ── */
.cf-input.prefilled {
    border-color:var(--green) !important;
    background:var(--green-dim) !important;
    box-shadow:0 0 0 3px rgba(29,158,117,.12) !important;
    transition:border-color .3s, background .3s, box-shadow .3s;
}
.cf-select { cursor:pointer; }
.cf-textarea { resize:vertical; min-height:80px; line-height:1.5; }
.cf-field-error { font-size:12px; color:var(--red); font-weight:500; }
.cf-field-hint  { font-size:12px; color:var(--text-400); }

/* "From Lead" label badge */
.pf-tag {
    display:none; align-items:center; gap:3px;
    padding:1px 7px; border-radius:10px;
    background:var(--green-dim); color:var(--green);
    font-size:10px; font-weight:600; text-transform:uppercase; letter-spacing:.3px;
}
.pf-tag.show { display:inline-flex; }

/* Lead select wrapper + spinner */
.lead-sel-wrap { position:relative; }
.lead-spinner {
    position:absolute; right:30px; top:50%; transform:translateY(-50%);
    display:none; pointer-events:none;
}
.lead-spinner.show { display:block; }

/* Prefill banner */
.pf-banner {
    display:none; align-items:center; gap:10px;
    padding:11px 16px; margin-bottom:14px;
    background:var(--green-dim); border:1px solid var(--green); border-radius:8px;
    font-size:13px; color:var(--green); font-weight:500;
}
.pf-banner.show { display:flex; }
.pf-banner-clear { margin-left:auto; background:transparent; border:none; cursor:pointer; font-size:12px; color:var(--green); font-weight:500; text-decoration:underline; font-family:'DM Sans',sans-serif; }

.cf-footer { display:flex; align-items:center; justify-content:space-between; padding:16px 24px; background:var(--bg-elevated); border-top:1px solid var(--border-subtle); }
.cf-footer-note { font-size:12px; color:var(--text-300); }
.cf-footer-note strong { color:var(--text-200); }
.cf-sidebar { display:flex; flex-direction:column; gap:14px; }
.cf-side-card { background:var(--bg-surface); border:1px solid var(--border-default); border-radius:14px; padding:18px; }
.cf-side-title { font-size:11px; font-weight:600; color:var(--text-300); text-transform:uppercase; letter-spacing:.6px; margin-bottom:14px; }
.req-list { display:flex; flex-direction:column; gap:8px; }
.req-item { display:flex; align-items:center; gap:8px; font-size:12.5px; color:var(--text-200); }
.req-dot { width:6px; height:6px; border-radius:50%; background:var(--red); flex-shrink:0; transition:background .2s; }
.req-dot.ok { background:var(--green); }
.tip-list { display:flex; flex-direction:column; gap:9px; }
.tip-item { display:flex; align-items:flex-start; gap:8px; font-size:12px; color:var(--text-300); line-height:1.45; }
.tip-dot { width:5px; height:5px; border-radius:50%; background:var(--accent); margin-top:5px; flex-shrink:0; }

@keyframes cf-spin { from{transform:rotate(0deg)} to{transform:rotate(360deg)} }
</style>
@endpush

@section('content')
@php
    $contactFields = config('contact_fields');

    /*
     * Prefill Map: contact_form_field => lead_model_attribute
     * Jab lead select ho, in fields ko lead ke data se fill karo
     */
    $prefillMap = [
        'name'        => 'name',
        'phone'       => 'phone',
        'email'       => 'email',
        'company'     => 'company',
        'designation' => 'designation',
        'city'        => 'city',
        'state'       => 'state',
    ];

    $sectionIconBg = [
        'blue'   => ['bg'=>'var(--accent-dim)','stroke'=>'var(--accent)'],
        'purple' => ['bg'=>'var(--purple-dim)','stroke'=>'var(--purple)'],
        'teal'   => ['bg'=>'var(--green-dim)','stroke'=>'var(--green)'],
        'amber'  => ['bg'=>'var(--amber-dim)','stroke'=>'var(--amber)'],
        'red'    => ['bg'=>'var(--red-dim)','stroke'=>'var(--red)'],
        'green'  => ['bg'=>'var(--green-dim)','stroke'=>'var(--green)'],
    ];

    $sections = $contactFields['sections'];
    $grouped  = collect($contactFields['fields'])
        ->reject(fn($f) => !empty($f['module']) && !auth()->user()->tenant?->hasModuleEnabled($f['module']))
        ->groupBy('section');
@endphp

<div class="cf-page">

    {{-- Header --}}
    <div class="page-head">
        <div>
            <div style="font-size:12px;color:var(--text-300);margin-bottom:4px;display:flex;align-items:center;gap:5px">
                <a href="{{ route('tenant.contacts.index') }}" style="color:var(--text-300);text-decoration:none">Contacts</a>
                <span style="opacity:.4">›</span>
                <span>Add Contact</span>
            </div>
            <div class="page-title">Add New Contact</div>
        </div>
        <a href="{{ route('tenant.contacts.index') }}" class="btn btn-secondary">
            <i class="ti ti-arrow-left" style="font-size:14px" aria-hidden="true"></i>
            Back
        </a>
    </div>

    {{-- Prefill Banner --}}
    <div class="pf-banner" id="pfBanner">
        <i class="ti ti-bolt" style="font-size:16px" aria-hidden="true"></i>
        <span id="pfBannerText">Lead details prefill ho gayi hain</span>
        <button class="pf-banner-clear" type="button" onclick="clearPrefill()">
            Clear prefill
        </button>
    </div>

    <form method="POST" action="{{ route('tenant.contacts.store') }}" enctype="multipart/form-data" novalidate id="contactForm">
        @csrf

        <div class="cf-layout">

            {{-- ── Main Form ── --}}
            <div class="cf-main">

                @foreach($sections as $sectionKey => $section)
                @if($grouped->has($sectionKey))
                @php $sColor = $sectionIconBg[$section['color']] ?? $sectionIconBg['blue']; @endphp

                <div class="cf-section">
                    <div class="cf-section-header">
                        <div class="cf-section-icon" style="background:{{ $sColor['bg'] }}">
                            <i class="{{ $section['icon'] }}" style="font-size:16px;color:{{ $sColor['stroke'] }}" aria-hidden="true"></i>
                        </div>
                        <div>
                            <div class="cf-section-title">{{ $section['title'] }}</div>
                            <div class="cf-section-sub">{{ $section['sub'] }}</div>
                        </div>
                    </div>

                    <div class="cf-grid">
                        @foreach($grouped[$sectionKey] as $field)
                        @php
                            $fieldVal   = old($field['key'], '');
                            $hasError   = $errors->has($field['key']);
                            $isFullSpan = ($field['span'] ?? 'half') === 'full';
                            $canPrefill = array_key_exists($field['key'], $prefillMap);
                        @endphp

                        <div class="cf-field {{ $isFullSpan ? 'span-full' : '' }}">
                            <label class="cf-label" for="field_{{ $field['key'] }}">
                                {{ $field['label'] }}
                                @if($field['required'] ?? false)<span class="cf-req">*</span>@endif
                                @if($canPrefill)
                                <span class="pf-tag" id="ptag_{{ $field['key'] }}">
                                    <i class="ti ti-bolt" style="font-size:10px" aria-hidden="true"></i>
                                    From Lead
                                </span>
                                @endif
                            </label>

                            @if($field['type'] === 'textarea')
                                <textarea
                                    id="field_{{ $field['key'] }}"
                                    name="{{ $field['key'] }}"
                                    class="cf-input cf-textarea {{ $hasError ? 'is-error':'' }}"
                                    placeholder="{{ $field['placeholder'] ?? '' }}"
                                    {{ ($field['required']??false) ? 'required':'' }}
                                    rows="3"
                                >{{ $fieldVal }}</textarea>

                            @elseif($field['type'] === 'select' && $field['key'] === 'lead_id')
                                {{-- ── Special: Lead Select with AJAX prefill ── --}}
                                <div class="lead-sel-wrap">
                                    <select
                                        id="field_lead_id"
                                        name="lead_id"
                                        class="cf-input cf-select {{ $hasError ? 'is-error':'' }}"
                                    >
                                        <option value="">— Select Lead (optional) —</option>
                                        @foreach($leads as $lead)
                                        <option value="{{ $lead->id }}"
                                            {{ old('lead_id') == $lead->id ? 'selected':'' }}
                                            data-name="{{ $lead->name }}"
                                            data-company="{{ $lead->company ?? '' }}">
                                            {{ $lead->name }}
                                            @if($lead->company) — {{ $lead->company }}@endif
                                        </option>
                                        @endforeach
                                    </select>
                                    <span class="lead-spinner" id="leadSpinner">
                                        <svg style="animation:cf-spin .6s linear infinite;width:14px;height:14px" viewBox="0 0 24 24" fill="none" stroke="var(--green)" stroke-width="2">
                                            <path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/>
                                        </svg>
                                    </span>
                                </div>
                                <span class="cf-field-hint">Lead select karo — basic details auto-fill ho jayenge</span>

                            @elseif($field['type'] === 'select')
                                <select
                                    id="field_{{ $field['key'] }}"
                                    name="{{ $field['key'] }}"
                                    class="cf-input cf-select {{ $hasError ? 'is-error':'' }}"
                                    {{ ($field['required']??false) ? 'required':'' }}
                                >
                                    <option value="">{{ $field['placeholder'] ?? '— Select —' }}</option>
                                    @foreach($field['options'] ?? [] as $optVal => $optLabel)
                                    <option value="{{ $optVal }}" {{ old($field['key']) == $optVal ? 'selected':'' }}>
                                        {{ $optLabel }}
                                    </option>
                                    @endforeach
                                </select>

                            @else
                                <input
                                    id="field_{{ $field['key'] }}"
                                    type="{{ $field['type'] }}"
                                    name="{{ $field['key'] }}"
                                    class="cf-input {{ $hasError ? 'is-error':'' }}"
                                    placeholder="{{ $field['placeholder'] ?? '' }}"
                                    value="{{ $fieldVal }}"
                                    {{ ($field['required']??false) ? 'required':'' }}
                                />
                            @endif

                            @if($hasError)
                                <span class="cf-field-error">{{ $errors->first($field['key']) }}</span>
                            @elseif(!empty($field['hint']) && !in_array($field['type'], ['select']) || ($field['key'] === 'phone' && !$hasError))
                                {{-- hint sirf lead_id wale ke liye upar already hai --}}
                                @if($field['key'] !== 'lead_id')
                                <span class="cf-field-hint">{{ $field['hint'] ?? '' }}</span>
                                @endif
                            @endif
                            @if($field['key'] === 'phone')
                            <div id="dupWarning" style="display:none;align-items:center;gap:6px;font-size:12px;color:var(--amber);background:var(--amber-dim);border:1px solid #F0D9A8;border-radius:6px;padding:6px 10px;margin-top:6px"></div>
                            @endif
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif
                @endforeach

                @include('tenant.contacts._employees_section')
                @include('tenant.contacts._attachments_section')

                {{-- Footer --}}
                <div class="cf-footer">
                    <div class="cf-footer-note">Fields marked <strong>*</strong> are required</div>
                    <div style="display:flex;gap:8px">
                        <a href="{{ route('tenant.contacts.index') }}" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary" id="submitBtn">
                            <i class="ti ti-plus" id="submitIcon" style="font-size:14px" aria-hidden="true"></i>
                            <span id="submitText">Create Contact</span>
                        </button>
                    </div>
                </div>
            </div>

            {{-- ── Sidebar ── --}}
            <div class="cf-sidebar">

                {{-- Lead Preview Card (hidden initially) --}}
                <div class="cf-side-card" id="leadPreviewCard"
                     style="display:none;border-color:var(--green);background:var(--green-dim)">
                    <div class="cf-side-title" style="color:var(--green)">
                        <i class="ti ti-bolt" style="font-size:13px;margin-right:4px;color:var(--green)" aria-hidden="true"></i>
                        Prefill Active
                    </div>
                    <div id="leadPreviewBody" style="display:flex;flex-direction:column;gap:9px"></div>
                    <button type="button" onclick="clearPrefill()"
                        style="margin-top:13px;width:100%;padding:7px;background:transparent;border:1px solid var(--green);border-radius:7px;color:var(--green);font-size:12px;cursor:pointer;font-family:'DM Sans',sans-serif;font-weight:500;display:flex;align-items:center;justify-content:center;gap:5px">
                        <i class="ti ti-x" style="font-size:12px" aria-hidden="true"></i>
                        Clear & Fill Manually
                    </button>
                </div>

                {{-- Required Checklist --}}
                <div class="cf-side-card">
                    <div class="cf-side-title">Required Fields</div>
                    <div class="req-list">
                        @foreach(collect($contactFields['fields'])->where('required', true) as $rf)
                        <div class="req-item">
                            <div class="req-dot" id="dot_{{ $rf['key'] }}"></div>
                            <span>{{ $rf['label'] }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>

                {{-- Tips --}}
                <div class="cf-side-card">
                    <div class="cf-side-title">Tips</div>
                    <div class="tip-list">
                        <div class="tip-item">
                            <div class="tip-dot" style="background:var(--green)"></div>
                            <span>
                                <strong style="color:var(--text-100)">"Linked Lead" select karo</strong>
                                — name, phone, email, company automatically fill ho jayenge
                            </span>
                        </div>
                        <div class="tip-item"><div class="tip-dot"></div><span>Prefilled values ko manually edit kar sakte ho</span></div>
                        <div class="tip-item"><div class="tip-dot"></div><span>GST number se invoice auto-populate hoga</span></div>
                        <div class="tip-item"><div class="tip-dot"></div><span>Notes mein key requirements zaroor likhein</span></div>
                    </div>
                </div>

                {{-- Stats --}}
                <div class="cf-side-card" style="text-align:center">
                    @php
                        $total    = count($contactFields['fields']);
                        $required = collect($contactFields['fields'])->where('required', true)->count();
                        $pfCount  = count($prefillMap);
                    @endphp
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:0;border-radius:8px;overflow:hidden;border:1px solid var(--border-subtle)">
                        <div style="padding:10px;border-right:1px solid var(--border-subtle)">
                            <div style="font-size:20px;font-weight:600;color:var(--text-100);font-family:'DM Mono',monospace">{{ $total }}</div>
                            <div style="font-size:10px;color:var(--text-300);text-transform:uppercase;letter-spacing:.4px;margin-top:1px">Fields</div>
                        </div>
                        <div style="padding:10px">
                            <div style="font-size:20px;font-weight:600;color:var(--green);font-family:'DM Mono',monospace">{{ $pfCount }}</div>
                            <div style="font-size:10px;color:var(--text-300);text-transform:uppercase;letter-spacing:.4px;margin-top:1px">Auto-fill</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@include('tenant.contacts._employees_assets')
@endsection

@push('scripts')
<script>
(function(){

    // ── Prefill map (contact_key => lead_api_key) ─────────────────
    const PREFILL_MAP = @json($prefillMap);

    // ── Lead Data API URL (tenant-scoped) ─────────────────────────
    // Controller method: leadData() — returns JSON

    // ── DOM ───────────────────────────────────────────────────────
    const leadSel    = document.getElementById('field_lead_id');
    const spinner    = document.getElementById('leadSpinner');
    const banner     = document.getElementById('pfBanner');
    const bannerText = document.getElementById('pfBannerText');
    const previewCard= document.getElementById('leadPreviewCard');
    const previewBody= document.getElementById('leadPreviewBody');
    const CSRF       = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

    let originals = {};   // store values before prefill

    // ── Lead select change ────────────────────────────────────────
    leadSel?.addEventListener('change', function(){
        if(!this.value){ clearPrefill(); return; }
        loadLeadData(this.value);
    });

    // ── Fetch lead data ───────────────────────────────────────────
    async function loadLeadData(id){
        spinner.classList.add('show');
        leadSel.disabled = true;

        try {
            const res  = await fetch(`/leads/${id}/data`, {
                headers:{
                    'X-Requested-With':'XMLHttpRequest',
                    'Accept':'application/json',
                    'X-CSRF-TOKEN': CSRF
                }
            });
            if(!res.ok) throw new Error(res.statusText);
            const data = await res.json();
            applyPrefill(data.data);
        } catch(e){
            toast('Lead details load nahi ho sake. Manually fill karein.', 'error');
        } finally {
            spinner.classList.remove('show');
            leadSel.disabled = false;
        }
    }

    // ── Apply prefill ─────────────────────────────────────────────
    function applyPrefill(data){
        let filled = 0;
        console.log('Applying prefill with data:', data);

        Object.entries(PREFILL_MAP).forEach(([contactKey, leadKey]) => {
            console.log(contactKey, leadKey);
            const el   = document.getElementById('field_' + contactKey);
            const ptag = document.getElementById('ptag_' + contactKey);
            console.log('el', data[leadKey]);

            if(!el || !data[leadKey]) return;

            
            

            originals[contactKey] = el.value;   // save original
            console.log(originals)
            el.value = data[leadKey];
            el.classList.add('prefilled');
            el.classList.remove('is-error');
            if(ptag) ptag.classList.add('show');
            filled++;

            // Remove green on manual edit
            el.addEventListener('input', function onManual(){
                el.classList.remove('prefilled');
                if(ptag) ptag.classList.remove('show');
            }, { once: true });
        });

        if(!filled) return;

        // Banner
        const opt      = leadSel.options[leadSel.selectedIndex];
        const leadName = opt?.dataset?.name ?? 'Lead';
        bannerText.textContent = `"${leadName}" ki ${filled} details prefill ho gayi hain — edit kar sakte ho`;
        banner.classList.add('show');

        // Sidebar preview
        renderPreview(data, leadName);

        // Update checklist
        syncDots();
    }

    // ── Sidebar Preview ───────────────────────────────────────────
    function renderPreview(data, name){
        const rows = [
            { k:'name',        icon:'ti-user',      label:'Name'        },
            { k:'phone',       icon:'ti-phone',     label:'Phone'       },
            { k:'email',       icon:'ti-mail',      label:'Email'       },
            { k:'company',     icon:'ti-building',  label:'Company'     },
            { k:'designation', icon:'ti-briefcase', label:'Designation' },
            { k:'city',        icon:'ti-map-pin',   label:'City'        },
            { k:'state',       icon:'ti-map',       label:'State'       },
        ];

        previewBody.innerHTML = rows
            .filter(r => data[r.k])
            .map(r => `
                <div style="display:flex;align-items:center;gap:9px">
                    <div style="width:26px;height:26px;border-radius:6px;background:#C7EDD9;display:flex;align-items:center;justify-content:center;flex-shrink:0">
                        <i class="ti ${r.icon}" style="font-size:13px;color:var(--green)" aria-hidden="true"></i>
                    </div>
                    <div>
                        <div style="font-size:10px;color:var(--green);font-weight:600;text-transform:uppercase;letter-spacing:.4px">${r.label}</div>
                        <div style="font-size:12.5px;color:var(--green);font-weight:500;margin-top:1px">${data[r.k]}</div>
                    </div>
                </div>
            `).join('');

        previewCard.style.display = 'block';
    }

    // ── Clear prefill ─────────────────────────────────────────────
    window.clearPrefill = function(){
        Object.entries(PREFILL_MAP).forEach(([ck]) => {
            const el   = document.getElementById('field_' + ck);
            const ptag = document.getElementById('ptag_' + ck);
            if(!el) return;
            el.value = originals[ck] ?? '';
            el.classList.remove('prefilled');
            if(ptag) ptag.classList.remove('show');
        });
        leadSel.value = '';
        banner.classList.remove('show');
        previewCard.style.display = 'none';
        previewBody.innerHTML = '';
        originals = {};
        syncDots();
    };

    // ── Required checklist dots ───────────────────────────────────
    const REQ_KEYS = @json(
        collect(config('contact_fields.fields'))
            ->where('required', true)->pluck('key')->values()
    );

    function syncDots(){
        REQ_KEYS.forEach(k => {
            const el  = document.querySelector(`[name="${k}"]`);
            const dot = document.getElementById('dot_' + k);
            if(el && dot) dot.classList.toggle('ok', el.value.trim() !== '');
        });
    }

    REQ_KEYS.forEach(k => {
        const el = document.querySelector(`[name="${k}"]`);
        el?.addEventListener('input',  syncDots);
        el?.addEventListener('change', syncDots);
    });
    syncDots();

    // ── Submit loading ────────────────────────────────────────────
    document.getElementById('contactForm').addEventListener('submit', function(){
        const icon = document.getElementById('submitIcon');
        const text = document.getElementById('submitText');
        icon.style.animation = 'cf-spin .7s linear infinite';
        text.textContent = 'Saving...';
        document.getElementById('submitBtn').disabled = true;
    });

    // ── Toast ─────────────────────────────────────────────────────
    function toast(msg, type='success'){
        const bg = type === 'error' ? 'var(--red)' : 'var(--accent)';
        const ic = type === 'error' ? 'ti-alert-circle' : 'ti-circle-check';
        const el = document.createElement('div');
        el.style.cssText = `position:fixed;bottom:20px;right:20px;background:${bg};color:#fff;padding:10px 16px;border-radius:8px;font-size:13px;font-weight:500;z-index:9999;display:flex;align-items:center;gap:8px;box-shadow:0 4px 16px rgba(0,0,0,.15)`;
        el.innerHTML = `<i class="ti ${ic}" style="font-size:15px" aria-hidden="true"></i>${msg}`;
        document.body.appendChild(el);
        setTimeout(() => el.remove(), 3500);
    }

    // ── Restore prefill if validation failed (old('lead_id')) ─────
    @if(old('lead_id'))
    document.addEventListener('DOMContentLoaded', () => {
        const savedId = "{{ old('lead_id') }}";
        if(savedId && leadSel){
            leadSel.value = savedId;
            loadLeadData(savedId);
        }
    });
    @endif

    // ── Live duplicate check ──────────────────────────────────────
    let dupTimer;
    const dPhone = document.getElementById('field_phone');
    const dEmail = document.getElementById('field_email');
    const dupBox = document.getElementById('dupWarning');

    function checkDup(){
        clearTimeout(dupTimer);
        dupTimer = setTimeout(async () => {
            const phone = dPhone?.value.trim() ?? '';
            const email = dEmail?.value.trim() ?? '';
            if (!phone && !email) { if (dupBox) dupBox.style.display = 'none'; return; }

            const res = await crmPost("{{ route('tenant.contacts.check-duplicate') }}", { phone, email });
            if (!dupBox) return;
            if (res.duplicate) {
                dupBox.innerHTML = `This phone/email already belongs to <strong>${res.match.name}</strong>.
                    <a href="/contacts/${res.match.id}" target="_blank" style="margin-left:auto;color:var(--accent);font-weight:600;text-decoration:none">View Contact &rarr;</a>`;
                dupBox.style.display = 'flex';
            } else {
                dupBox.style.display = 'none';
            }
        }, 400);
    }
    dPhone?.addEventListener('input', checkDup);
    dEmail?.addEventListener('input', checkDup);

})();
</script>
@endpush