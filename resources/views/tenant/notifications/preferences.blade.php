@extends('layouts.app')
@section('title', 'Notification Preferences')

@push('styles')
<style>
.ch-pills { display:flex; gap:10px; flex-wrap:wrap; margin-bottom:24px; }
.ch-pill  { display:flex; align-items:center; gap:8px; padding:10px 16px; background:var(--bg-surface); border:1.5px solid var(--border-default); border-radius:var(--r-md); font-size:13px; font-weight:600; color:var(--text-200); }
.ch-pill-dot { width:8px; height:8px; border-radius:50%; }
.soon { font-size:10px; font-weight:700; padding:2px 6px; border-radius:4px; background:var(--amber-dim); color:var(--amber); letter-spacing:.3px; }

.col-headers { display:flex; align-items:center; padding:12px 20px; background:var(--bg-elevated); border-bottom:2px solid var(--border-default); gap:16px; position:sticky; top:0; z-index:10; }
.col-header-info { flex:1; font-size:11.5px; font-weight:700; color:var(--text-300); text-transform:uppercase; letter-spacing:.4px; }

.group-header { display:flex; align-items:center; gap:8px; padding:10px 20px; background:var(--bg-elevated); border-bottom:1px solid var(--border-subtle); border-top:1px solid var(--border-subtle); font-size:11.5px; font-weight:700; color:var(--text-300); text-transform:uppercase; letter-spacing:.5px; }

.pref-row { display:flex; align-items:center; padding:14px 20px; border-bottom:1px solid var(--border-subtle); gap:16px; transition:background .15s; }
.pref-row:last-child { border-bottom:none; }
.pref-row:hover { background:var(--bg-elevated); }
.pref-info  { flex:1; min-width:0; }
.pref-label { font-size:13.5px; font-weight:600; color:var(--text-100); margin-bottom:2px; }
.pref-desc  { font-size:12px; color:var(--text-400); }

.pref-channels { display:flex; gap:8px; flex-shrink:0; }

/* ── Toggle card ─────────────────────────────────────────────────── */
.toggle-card {
    display:flex; flex-direction:column; align-items:center; gap:5px;
    width:72px; padding:8px 6px; border-radius:var(--r-md);
    border:1.5px solid var(--border-default);
    background:var(--bg-elevated);
    transition:all .15s var(--ease);
    cursor:pointer;
}
.toggle-card.is-on {
    border-color:var(--accent);
    background:var(--accent-dim);
}
.toggle-card.is-disabled {
    opacity:.5; cursor:not-allowed;
}
.toggle-card-icon { width:18px; height:18px; }
.toggle-card-name { font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.3px; color:var(--text-400); }
.toggle-card.is-on .toggle-card-name { color:var(--accent); }

/* ── The actual switch ───────────────────────────────────────────── */
.sw { position:relative; width:40px; height:22px; }
.sw input { opacity:0; width:0; height:0; position:absolute; }
.sw-track {
    position:absolute; inset:0;
    background:var(--border-default);
    border-radius:22px;
    transition:background .2s;
}
.sw input:checked ~ .sw-track { background:var(--accent); }
.sw input:disabled ~ .sw-track { opacity:.5; }
.sw-thumb {
    position:absolute; top:3px; left:3px;
    width:16px; height:16px; border-radius:50%;
    background:#fff; box-shadow:0 1px 4px rgba(0,0,0,.25);
    transition:transform .2s var(--ease);
    pointer-events:none;
}
.sw input:checked ~ .sw-track .sw-thumb { transform:translateX(18px); }

/* ── ON/OFF text ─────────────────────────────────────────────────── */
.sw-state { font-size:10px; font-weight:800; font-family:var(--mono); letter-spacing:.3px; }
.sw-state.on  { color:var(--green); }
.sw-state.off { color:var(--text-400); }

/* ── Select all row ──────────────────────────────────────────────── */
.sel-all-row { display:flex; align-items:center; padding:10px 20px; background:rgba(255,122,89,.06); border-bottom:1px solid rgba(255,122,89,.12); gap:16px; }
.sel-all-label { flex:1; font-size:12.5px; font-weight:700; color:var(--accent); }
.sel-all-channels { display:flex; gap:8px; flex-shrink:0; }
.sel-btn { width:72px; padding:5px; border-radius:var(--r-sm); border:1.5px solid var(--accent); background:none; color:var(--accent); font-size:11px; font-weight:700; cursor:pointer; font-family:var(--font); transition:all .15s; }
.sel-btn:hover { background:var(--accent); color:#fff; }

.form-footer { display:flex; align-items:center; justify-content:space-between; padding:16px 20px; background:var(--bg-elevated); border-top:1px solid var(--border-subtle); }

.col-cell { width:72px; text-align:center; flex-shrink:0; }

/* ── Mobile ───────────────────────────────────────────────────────── */
@media(max-width:768px) {
    /* Column headers only make sense aligned above a horizontal row of
       toggles; once rows stack, each toggle already carries its own
       channel name, so the header row is pure redundant width. */
    .col-headers { display:none; }

    .ch-pills { gap:8px; margin-bottom:18px; }
    .ch-pill  { padding:8px 12px; font-size:12px; }

    .group-header { padding:9px 16px; }

    .pref-row { flex-direction:column; align-items:stretch; padding:14px 16px; gap:12px; }

    .pref-channels {
        display:grid;
        grid-template-columns:repeat(auto-fit, minmax(62px, 1fr));
        gap:8px;
        width:100%;
    }
    .toggle-card, .toggle-card.is-disabled { width:auto; }
    .toggle-card-name { font-size:9px; line-height:1.25; word-break:break-word; text-align:center; }

    .sel-all-row { flex-direction:column; align-items:stretch; padding:12px 16px; gap:10px; }
    .sel-all-channels {
        display:grid;
        grid-template-columns:repeat(auto-fit, minmax(62px, 1fr));
        gap:8px;
        width:100%;
    }
    .sel-all-channels .col-cell { width:auto; }
    .sel-btn { width:auto; padding:7px 4px; }

    .form-footer { flex-direction:column; align-items:stretch; gap:12px; padding:14px 16px; }
    .form-footer > div { width:100%; display:flex; gap:10px; }
    .form-footer > div .btn { flex:1; justify-content:center; }
}
</style>
@endpush

@section('content')

@php
    $channelDefs = config('notifications.channels');
    $types       = config('notifications.types');
    $groups      = collect($types)->groupBy(fn($t) => $t['group'] ?? 'Other', true);

    $channelIcons = [
        'in_app'   => '<path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0"/>',
        'email'    => '<path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/>',
        'whatsapp' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 20.25c4.97 0 9-3.694 9-8.25s-4.03-8.25-9-8.25S3 7.444 3 12c0 2.104.859 4.023 2.273 5.48.432.447.74 1.04.586 1.641a4.483 4.483 0 01-.923 1.785A5.969 5.969 0 006 21c1.282 0 2.47-.402 3.445-1.087.81.22 1.668.337 2.555.337z"/>',
        'slack'    => '<path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 01.865-.501 48.172 48.172 0 003.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0012 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018z"/>',
        'push'     => '<path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0"/>',
    ];
@endphp

<div class="page-head">
    <div>
        <div style="font-size:13px;color:var(--text-300);margin-bottom:4px">
            <a href="{{ route('tenant.notifications.index') }}" style="color:var(--text-300);text-decoration:none">Notifications</a>
            <span style="margin:0 6px">›</span> Preferences
        </div>
        <div class="page-title">Notification Preferences</div>
        <div class="page-sub">Control how & where you receive each notification</div>
    </div>
</div>

{{-- Channel legend --}}
<div class="ch-pills">
    @foreach($channelDefs as $key => $ch)
    <div class="ch-pill">
        <div class="ch-pill-dot" style="background:{{ $ch['enabled'] ? 'var(--green)':'var(--border-default)' }}"></div>
        {{ $ch['label'] }}
        @if(!$ch['enabled'])<span class="soon">Soon</span>@endif
    </div>
    @endforeach
</div>

<div style="background:var(--bg-surface);border:1px solid var(--border-default);border-radius:var(--r-lg);overflow:hidden">
    <form method="POST" action="{{ route('tenant.notifications.preferences.save') }}" id="prefForm">
        @csrf

        {{-- Column headers --}}
        <div class="col-headers">
            <div class="col-header-info">Notification Type</div>
            <div style="display:flex;gap:8px;flex-shrink:0">
                @foreach($channelDefs as $key => $ch)
                <div class="col-cell">
                    <div style="display:flex;flex-direction:column;align-items:center;gap:3px">
                        <svg fill="none" stroke="currentColor" stroke-width="1.75"
                             viewBox="0 0 24 24" style="width:16px;height:16px;color:{{ $ch['enabled'] ? 'var(--text-200)':'var(--text-400)' }}">
                            {!! $channelIcons[$key] ?? '' !!}
                        </svg>
                        <span style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.4px;color:var(--text-300)">
                            {{ $ch['label'] }}
                        </span>
                        @if(!$ch['enabled'])
                        <span class="soon" style="font-size:8px;padding:1px 4px">Soon</span>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        {{-- Select all row --}}
        <div class="sel-all-row">
            <div class="sel-all-label">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="width:14px;height:14px;display:inline;margin-right:4px"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 11-3 0m3 0a1.5 1.5 0 10-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-9.75 0h9.75"/></svg>
                Select / Deselect All
            </div>
            <div class="sel-all-channels">
                @foreach($channelDefs as $key => $ch)
                <div class="col-cell" style="display:flex;justify-content:center">
                    @if($ch['enabled'])
                    <button type="button" class="sel-btn" id="selall_{{ $key }}" data-channel="{{ $key }}" onclick="toggleAll('{{ $key }}', this)">
                        All ON
                    </button>
                    @else
                    <span style="font-size:11px;color:var(--text-400)">—</span>
                    @endif
                </div>
                @endforeach
            </div>
        </div>

        {{-- Notification rows grouped --}}
        @foreach($groups as $group => $groupTypes)

        {{-- Group header --}}
        <div class="group-header">
            @php $groupIcons = ['Leads'=>'👤','Deals'=>'💼','Follow-ups'=>'📅','Tasks'=>'✅','Quotations'=>'📄','Invoices'=>'🧾','System'=>'⚙️']; @endphp
            {{ $groupIcons[$group] ?? '📂' }} {{ $group }}
        </div>

        @foreach($groupTypes as $typeKey => $typeCfg)
        @php $p = $prefs[$typeKey] ?? []; @endphp

        <div class="pref-row">

            {{-- Notification info --}}
            <div class="pref-info">
                <div class="pref-label">{{ $typeCfg['label'] }}</div>
                <div class="pref-desc">{{ $typeCfg['message'] }}</div>
            </div>

            {{-- Toggle cards per channel --}}
            <div class="pref-channels">
                @foreach($channelDefs as $chKey => $ch)
                @php $isOn = (bool)($p[$chKey] ?? false); @endphp

                @if($ch['enabled'])
                <label class="toggle-card {{ $isOn ? 'is-on':'' }}" id="card_{{ $typeKey }}_{{ $chKey }}">

                    {{-- Preserve unchecked state so all channel prefs are submitted --}}
                    <input type="hidden" name="prefs[{{ $typeKey }}][{{ $chKey }}]" value="0" />

                    {{-- Switch (input inside .sw so CSS :checked ~ .sw-track works) --}}
                    <div class="sw">
                        <input type="checkbox"
                               name="prefs[{{ $typeKey }}][{{ $chKey }}]"
                               value="1"
                               class="ch-check ch-{{ $chKey }}"
                               data-type="{{ $typeKey }}"
                               data-channel="{{ $chKey }}"
                               {{ $isOn ? 'checked':'' }}
                               onchange="syncCard(this)"/>
                        <div class="sw-track">
                            <div class="sw-thumb"></div>
                        </div>
                    </div>

                    {{-- ON / OFF --}}
                    <span class="sw-state {{ $isOn ? 'on':'off' }}">
                        {{ $isOn ? 'ON':'OFF' }}
                    </span>

                    {{-- Channel name --}}
                    <span class="toggle-card-name">{{ $ch['label'] }}</span>
                </label>
                @else
                {{-- Disabled channel --}}
                <div class="toggle-card is-disabled">
                    <div class="sw">
                        <div class="sw-track">
                            <div class="sw-thumb"></div>
                        </div>
                    </div>
                    <span class="sw-state off">—</span>
                    <span class="soon" style="font-size:8px;padding:1px 4px">Soon</span>
                </div>
                @endif

                @endforeach
            </div>

        </div>
        @endforeach

        @endforeach

        {{-- Footer --}}
        <div class="form-footer">
            <span style="font-size:13px;color:var(--text-300)">
                💡 In-App notifications always recommended to keep ON
            </span>
            <div style="display:flex;gap:10px">
                <a href="{{ route('tenant.notifications.index') }}" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="width:15px;height:15px">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                    </svg>
                    Save Preferences
                </button>
            </div>
        </div>

    </form>
</div>

@endsection

@push('scripts')
<script>
// ── Sync card style when checkbox changes ─────────────────────────
function syncCard(input) {
    const card  = input.closest('.toggle-card');
    const state = card.querySelector('.sw-state');

    if (input.checked) {
        card.classList.add('is-on');
        state.textContent = 'ON';
        state.className   = 'sw-state on';
    } else {
        card.classList.remove('is-on');
        state.textContent = 'OFF';
        state.className   = 'sw-state off';
    }

    updateSelectAllLabel(input.dataset.channel);
}

// ── Keep "All ON / All OFF" button label matching real state ──────
// (so clicking it always sets an unambiguous state, never a blind toggle)
function updateSelectAllLabel(channel) {
    const btn = document.getElementById(`selall_${channel}`);
    if (!btn) return;

    const checks = document.querySelectorAll(`.ch-${channel}`);
    const allOn  = checks.length > 0 && Array.from(checks).every(c => c.checked);

    btn.textContent = allOn ? 'All OFF' : 'All ON';
}

// ── Toggle all for a channel ──────────────────────────────────────
function toggleAll(channel, btn) {
    const checks   = document.querySelectorAll(`.ch-${channel}`);
    const allOn    = Array.from(checks).every(c => c.checked);
    const turnOn   = !allOn; // if not all are on yet, this click turns everything ON; otherwise OFF

    checks.forEach(c => {
        c.checked = turnOn;
        syncCard(c);
    });

    updateSelectAllLabel(channel);
}

// ── Set correct initial label for each column on page load ────────
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.sel-btn').forEach(btn => {
        updateSelectAllLabel(btn.dataset.channel);
    });
});
</script>
@endpush