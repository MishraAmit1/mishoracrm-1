@extends('layouts.app')
@section('title', 'Add Deal')

@push('styles')
<style>
@import url('https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=DM+Mono:wght@400;500&display=swap');
@import url('https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.19.0/dist/tabler-icons.min.css');

.df-page { font-family: 'DM Sans', var(--font), sans-serif; }

/* ── Layout ── */
.df-layout {
    display: grid;
    grid-template-columns: minmax(0,1fr) 270px;
    gap: 16px;
    margin-top: 20px;
}
@media(max-width:900px){ .df-layout { grid-template-columns: 1fr; } }

/* ── Main Card ── */
.df-main {
    background: var(--bg-surface);
    border: 1px solid var(--border-default);
    border-radius: 14px;
    overflow: hidden;
}

/* ── Sections ── */
.df-section {
    padding: 20px 22px;
    border-bottom: 1px solid var(--border-subtle);
}
.df-section:last-of-type { border-bottom: none; }

.df-sec-head {
    display: flex; align-items: flex-start; gap: 11px;
    margin-bottom: 16px;
}
.df-sec-icon {
    width: 30px; height: 30px; border-radius: 8px;
    display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}
.df-sec-title { font-size: 13px; font-weight: 600; color: var(--text-100); letter-spacing: -.1px; }
.df-sec-sub   { font-size: 12px; color: var(--text-300); margin-top: 1px; }

/* ── Grid ── */
.df-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
.df-grid .span-full { grid-column: 1/-1; }
@media(max-width:640px){ .df-grid { grid-template-columns: 1fr; } .df-grid .span-full { grid-column:1; } }

/* ── Field ── */
.df-field { display: flex; flex-direction: column; gap: 5px; }
.df-label {
    font-size: 11.5px; font-weight: 600; color: var(--text-200);
    text-transform: uppercase; letter-spacing: .5px;
}
.df-req  { color: var(--red, #E24B4A); margin-left: 2px; }
.df-hint { font-size: 12px; color: var(--text-400); }
.df-err  { font-size: 12px; color: var(--red, #E24B4A); font-weight: 500; }

/* ── Inputs ── */
.df-input {
    width: 100%; padding: 9px 12px;
    background: var(--bg-input);
    border: 1.5px solid var(--border-default);
    border-radius: 8px; color: var(--text-100);
    font-family: 'DM Sans', var(--font), sans-serif;
    font-size: 13.5px; outline: none;
    transition: border-color .15s, box-shadow .15s, background .15s;
    -webkit-appearance: none;
}
.df-input:focus { border-color: var(--accent); box-shadow: 0 0 0 3px var(--accent-dim); background: var(--bg-surface); }
.df-input::placeholder { color: var(--text-400); font-size: 13px; }
.df-input.is-err { border-color: var(--red, #E24B4A); }
.df-sel  { cursor: pointer; }
.df-area { resize: vertical; min-height: 82px; line-height: 1.55; }

/* Prefix input */
.input-prefix-wrap { position: relative; }
.input-prefix {
    position: absolute; left: 11px; top: 50%; transform: translateY(-50%);
    font-size: 13px; color: var(--text-300); pointer-events: none;
    font-family: 'DM Mono', monospace;
}
.df-input.has-prefix { padding-left: 24px; }

/* ── Stage Picker ── */
.stage-picker { display: flex; gap: 6px; flex-wrap: wrap; }
.sp-btn {
    flex: 1; min-width: 80px; padding: 9px 8px;
    border-radius: 8px; border: 1.5px solid var(--border-default);
    background: var(--bg-input); cursor: pointer;
    font-family: 'DM Sans', var(--font), sans-serif;
    font-size: 12px; font-weight: 500; color: var(--text-300);
    transition: all .15s; display: flex; align-items: center;
    justify-content: center; gap: 6px;
}
.sp-btn:hover { border-color: var(--border-strong); color: var(--text-100); }
.sp-dot { width: 7px; height: 7px; border-radius: 50%; flex-shrink: 0; }

/* ── Probability Slider ── */
.prob-slider-wrap { display: flex; align-items: center; gap: 10px; }
.prob-display {
    font-size: 15px; font-weight: 600; color: var(--text-100);
    font-family: 'DM Mono', monospace; min-width: 40px; flex-shrink: 0;
}
.df-range {
    flex: 1; height: 4px; border-radius: 2px; cursor: pointer;
    outline: none; accent-color: #185FA5;
}
.prob-bar-bg  { height: 4px; border-radius: 2px; background: var(--border-subtle); margin-top: 5px; overflow: hidden; }
.prob-bar-fill { height: 100%; border-radius: 2px; background: #185FA5; transition: width .2s; }

/* ── Footer ── */
.df-footer {
    display: flex; align-items: center; justify-content: space-between;
    padding: 15px 22px; background: var(--bg-elevated);
    border-top: 1px solid var(--border-subtle);
}
.df-footer-note { font-size: 12px; color: var(--text-300); }
.df-footer-note strong { color: var(--text-200); }

/* ── Sidebar ── */
.df-sidebar { display: flex; flex-direction: column; gap: 13px; }
.df-sc {
    background: var(--bg-surface);
    border: 1px solid var(--border-default);
    border-radius: 14px; padding: 17px;
}
.df-sc-title {
    font-size: 11px; font-weight: 600; color: var(--text-300);
    text-transform: uppercase; letter-spacing: .6px; margin-bottom: 13px;
}

/* Deal Preview Card */
.dp-value {
    font-size: 28px; font-weight: 600; color: var(--text-100);
    font-family: 'DM Mono', monospace; letter-spacing: -1px; line-height: 1;
}
.dp-title { font-size: 13px; color: var(--text-300); margin-top: 4px; line-height: 1.4; }
.dp-stage-badge {
    display: inline-flex; align-items: center; gap: 5px;
    margin-top: 10px; padding: 4px 11px; border-radius: 20px;
    font-size: 12px; font-weight: 600;
    transition: background .2s, color .2s;
}

/* Required Checklist */
.req-list { display: flex; flex-direction: column; gap: 8px; }
.req-item { display: flex; align-items: center; gap: 8px; font-size: 12.5px; color: var(--text-200); }
.req-dot  { width: 6px; height: 6px; border-radius: 50%; background: #E24B4A; flex-shrink: 0; transition: background .2s; }
.req-dot.ok { background: #1D9E75; }

/* Tips */
.tip-list { display: flex; flex-direction: column; gap: 9px; }
.tip-item { display: flex; align-items: flex-start; gap: 8px; font-size: 12px; color: var(--text-300); line-height: 1.45; }
.tip-dot  { width: 5px; height: 5px; border-radius: 50%; background: var(--accent,#378ADD); margin-top: 5px; flex-shrink: 0; }

@keyframes df-spin { from{transform:rotate(0deg)} to{transform:rotate(360deg)} }
</style>
@endpush

@section('content')
@php
    $dealConfig = config('deal_fields');
    $cfgStages  = $dealConfig['stages'];
    $isEdit     = false;
    $model      = null;

    /* Pre-fill from contact/lead if coming from their pages */
    $prefillContact = $contact ?? null;   // from controller
    $prefillLead    = $lead    ?? null;   // from controller

    $activeStage = old('stage', request('stage', 'new'));
    $activeStageData = $cfgStages[$activeStage] ?? $cfgStages['new'];

    $requiredFields = collect($dealConfig['fields'])->where('required', true);
@endphp

<div class="df-page">

    {{-- Header --}}
    <div class="page-head">
        <div>
            <div style="font-size:12px;color:var(--text-300);margin-bottom:4px;display:flex;align-items:center;gap:5px">
                <a href="{{ route('tenant.deals.index') }}" style="color:var(--text-300);text-decoration:none">Deals</a>
                <span style="opacity:.4">›</span>
                <span>Add Deal</span>
            </div>
            <div class="page-title">Add New Deal</div>
        </div>
        <a href="{{ route('tenant.deals.index') }}" class="btn btn-secondary">
            <i class="ti ti-arrow-left" style="font-size:14px"></i>
            Back
        </a>
    </div>

    {{-- Prefill notice if coming from contact/lead page --}}
    @if($prefillContact || $prefillLead)
    <div style="display:flex;align-items:center;gap:10px;padding:11px 15px;background:#E6F1FB;border:1px solid #9FE1CB;border-radius:8px;margin-bottom:14px;font-size:13px;color:#185FA5;font-weight:500">
        <i class="ti ti-bolt" style="font-size:16px"></i>
        @if($prefillContact)
        Contact <strong>{{ $prefillContact->name }}</strong> pre-linked hai is deal mein
        @elseif($prefillLead)
        Lead <strong>{{ $prefillLead->name }}</strong> pre-linked hai is deal mein
        @endif
    </div>
    @endif

    <form method="POST" action="{{ route('tenant.deals.store') }}" novalidate id="dealForm">
        @csrf

        <div class="df-layout">

            {{-- ── Main Form ── --}}
            <div class="df-main">

                {{-- Dynamic sections from config --}}
                @include('tenant.deals._form', [
                    'dealConfig' => $dealConfig,
                    'contacts'   => $contacts,
                    'leads'      => $leads,
                    'staffList'  => $staffList,
                    'model'      => null,
                    'isEdit'     => false,
                ])

                {{-- Footer --}}
                <div class="df-footer">
                    <div class="df-footer-note">Fields marked <strong>*</strong> are required</div>
                    <div style="display:flex;gap:8px">
                        <a href="{{ route('tenant.deals.index') }}" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary" id="submitBtn">
                            <i class="ti ti-plus" id="submitIcon" style="font-size:14px"></i>
                            <span id="submitText">Create Deal</span>
                        </button>
                    </div>
                </div>
            </div>

            {{-- ── Sidebar ── --}}
            <div class="df-sidebar">

                {{-- Live Deal Preview --}}
                <div class="df-sc">
                    <div class="df-sc-title">Deal Preview</div>
                    <div class="dp-value" id="previewValue">₹0</div>
                    <div class="dp-title" id="previewTitle"
                         style="color:var(--text-400);font-style:italic">
                        Enter deal title...
                    </div>
                    <div class="dp-stage-badge" id="previewStage"
                         style="background:{{ $activeStageData['bg'] }};color:{{ $activeStageData['text_color'] }}">
                        <span style="width:6px;height:6px;border-radius:50%;background:{{ $activeStageData['color'] }};display:inline-block"></span>
                        {{ $activeStageData['label'] }}
                    </div>
                </div>

                {{-- Required Checklist --}}
                <div class="df-sc">
                    <div class="df-sc-title">Required Fields</div>
                    <div class="req-list">
                        @foreach($requiredFields as $rf)
                        <div class="req-item">
                            <div class="req-dot" id="dot_{{ $rf['key'] }}"></div>
                            <span>{{ $rf['label'] }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>

                {{-- Stage Reference --}}
                <div class="df-sc">
                    <div class="df-sc-title">Stage Reference</div>
                    <div style="display:flex;flex-direction:column;gap:7px">
                        @foreach($cfgStages as $slug => $stg)
                        <div style="display:flex;align-items:center;justify-content:space-between">
                            <div style="display:flex;align-items:center;gap:7px">
                                <span style="width:7px;height:7px;border-radius:50%;background:{{ $stg['color'] }};display:inline-block"></span>
                                <span style="font-size:12.5px;color:var(--text-200);font-weight:500">{{ $stg['label'] }}</span>
                            </div>
                            <span style="font-size:11.5px;color:var(--text-400);font-family:'DM Mono',monospace">{{ $stg['probability'] }}%</span>
                        </div>
                        @endforeach
                    </div>
                </div>

                {{-- Tips --}}
                <div class="df-sc">
                    <div class="df-sc-title">Tips</div>
                    <div class="tip-list">
                        <div class="tip-item"><div class="tip-dot"></div><span>Stage select karne par probability auto-set hogi</span></div>
                        <div class="tip-item"><div class="tip-dot"></div><span>Contact link karne se deal tracking easy hogi</span></div>
                        <div class="tip-item"><div class="tip-dot"></div><span>Close date set karne se revenue forecasting better hogi</span></div>
                        <div class="tip-item"><div class="tip-dot"></div><span>Won stage par actual_close_date automatically set hota hai</span></div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
(function(){

    /* ── Stage config from PHP ── */
const stagesConfig = @json(config('deal_fields.stages'));
    /* ── Sidebar live preview ── */
    window.updateDealPreview = function(){
        const title   = document.getElementById('df_title')?.value ?? '';
        const val     = parseInt(document.getElementById('df_value')?.value) || 0;
        const stage   = document.getElementById('stageHidden')?.value ?? 'new';
        const stg     = stagesConfig[stage] ?? stagesConfig['new'];

        /* Value */
        document.getElementById('previewValue').textContent =
            '₹' + val.toLocaleString('en-IN');

        /* Title */
        const titleEl = document.getElementById('previewTitle');
        if(title){
            titleEl.textContent  = title;
            titleEl.style.color  = 'var(--color-text-primary, var(--text-100))';
            titleEl.style.fontStyle = 'normal';
        } else {
            titleEl.textContent  = 'Enter deal title...';
            titleEl.style.color  = 'var(--text-400)';
            titleEl.style.fontStyle = 'italic';
        }

        /* Stage badge */
        const badge = document.getElementById('previewStage');
        badge.style.background = stg.bg;
        badge.style.color      = stg.text_color;
        badge.innerHTML = `<span style="width:6px;height:6px;border-radius:50%;background:${stg.color};display:inline-block"></span> ${stg.label}`;

        /* Required dots */
        syncRequiredDots();
    };

    /* ── Required dots ── */
    const reqKeys = @json(collect(config('deal_fields.fields'))->where('required',true)->pluck('key')->values());

    function syncRequiredDots(){
        reqKeys.forEach(key => {
            const el  = document.querySelector(`[name="${key}"]`);
            const dot = document.getElementById(`dot_${key}`);
            if(!el || !dot) return;
            const filled = key === 'stage'
                ? (el.value && el.value !== '')
                : (el.value && el.value.toString().trim() !== '');
            dot.classList.toggle('ok', filled);
        });
    }

    /* Attach listeners */
    reqKeys.forEach(key => {
        const el = document.querySelector(`[name="${key}"]`);
        el?.addEventListener('input',  syncRequiredDots);
        el?.addEventListener('change', syncRequiredDots);
    });
    syncRequiredDots();

    /* ── Submit loading ── */
    document.getElementById('dealForm').addEventListener('submit', function(){
        const icon = document.getElementById('submitIcon');
        const text = document.getElementById('submitText');
        icon.style.animation = 'df-spin .7s linear infinite';
        text.textContent = 'Saving...';
        document.getElementById('submitBtn').disabled = true;
    });

    /* ── Prefill contact/lead if passed ── */
    @if(isset($prefillContact) && $prefillContact)
    document.addEventListener('DOMContentLoaded', () => {
        const sel = document.getElementById('df_contact_id');
        if(sel) sel.value = '{{ $prefillContact->id }}';
    });
    @endif

    @if(isset($prefillLead) && $prefillLead)
    document.addEventListener('DOMContentLoaded', () => {
        const sel = document.getElementById('df_lead_id');
        if(sel) sel.value = '{{ $prefillLead->id }}';
    });
    @endif

    /* Initial render */
    updateDealPreview();
})();
</script>
@endpush