@extends('layouts.app')
@section('title', 'Lead — ' . $lead->name)

@push('styles')
<style>
@import url('https://fonts.googleapis.com/css2?family=DM+Sans:ital,wght@0,400;0,500;0,600;1,400&family=DM+Mono:wght@400;500&display=swap');

.ls-page { font-family: 'DM Sans', var(--font), sans-serif; }

/* ── Layout ── */
.ls-layout {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 290px;
    gap: 16px;
    margin-top: 20px;
}
.ls-main    { display: flex; flex-direction: column; gap: 14px; }
.ls-sidebar { display: flex; flex-direction: column; gap: 14px; }

@media (max-width: 900px) { .ls-layout { grid-template-columns: 1fr; } }
@media (max-width: 480px) { .ls-stats { grid-template-columns: repeat(2, 1fr); row-gap: 14px; } }

/* ── Cards ── */
.ls-card {
    background: var(--bg-surface);
    border: 1px solid var(--border-default);
    border-radius: 14px;
    overflow: hidden;
}
.ls-card-title {
    font-size: 11px; font-weight: 600;
    color: var(--text-300); text-transform: uppercase; letter-spacing: 0.6px;
}

/* ── Hero ── */
.ls-hero { padding: 22px; }
.ls-hero-top { display: flex; align-items: flex-start; gap: 16px; margin-bottom: 0; }

.ls-avatar {
    width: 56px; height: 56px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 18px; font-weight: 600; flex-shrink: 0;
    background: #E6F1FB; color: #185FA5;
}

.ls-lead-name  { font-size: 19px; font-weight: 600; color: var(--text-100); letter-spacing: -0.3px; margin-bottom: 3px; }
.ls-lead-sub   { font-size: 13px; color: var(--text-300); }
.ls-deal-value { font-size: 22px; font-weight: 600; color: var(--text-100); font-family: 'DM Mono', monospace; letter-spacing: -1px; }
.ls-deal-lbl   { font-size: 11px; color: var(--text-300); margin-top: 2px; text-align: right; }

.ls-badges { display: flex; gap: 6px; margin-top: 10px; flex-wrap: wrap; }
.ls-badge {
    padding: 4px 10px; border-radius: 20px;
    font-size: 11.5px; font-weight: 600;
    display: inline-flex; align-items: center; gap: 4px;
}

/* ── Stats Bar ── */
.ls-stats {
    display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px;
    padding: 14px 22px;
    background: var(--bg-elevated);
    border-top: 1px solid var(--border-subtle);
}
.ls-stat-val {
    font-size: 16px; font-weight: 600;
    color: var(--text-100); font-family: 'DM Mono', monospace;
    letter-spacing: -0.5px;
}
.ls-stat-lbl { font-size: 11px; color: var(--text-300); margin-top: 2px; }

/* ── Pipeline ── */
.ls-pipeline { padding: 18px 22px; }
.ls-pip-steps { display: flex; align-items: center; margin-top: 16px; }
.ls-pip-step  { flex: 1; text-align: center; position: relative; }
.ls-pip-dot {
    width: 26px; height: 26px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    margin: 0 auto 5px; font-size: 11px; font-weight: 600;
}
.ls-pip-done    .ls-pip-dot { background: #E1F5EE; color: #0F6E56; border: 2px solid #1D9E75; }
.ls-pip-active  .ls-pip-dot { background: #185FA5; color: #fff;    border: 2px solid #185FA5; }
.ls-pip-pending .ls-pip-dot {
    background: var(--bg-input); color: var(--text-300);
    border: 1px solid var(--border-default);
}
.ls-pip-lbl { font-size: 10.5px; font-weight: 500; color: var(--text-300); }
.ls-pip-active .ls-pip-lbl { color: #185FA5; font-weight: 600; }
.ls-pip-line       { flex: 1; height: 2px; background: var(--border-subtle); margin-bottom: 20px; }
.ls-pip-line.done  { background: #1D9E75; }
.ls-pip-lost  .ls-pip-dot { background: #FCEBEB; color: #A32D2D; border: 2px solid #E24B4A; }
.ls-pip-lost  .ls-pip-lbl { color: #A32D2D; font-weight: 600; }
.ls-deal-link {
    display: inline-flex; align-items: center; gap: 6px;
    font-size: 12px; font-weight: 500; color: var(--accent);
    text-decoration: none; margin-top: 14px;
}
.ls-deal-link:hover { text-decoration: underline; }

/* ── Info Grid ── */
.ls-info-grid { display: grid; grid-template-columns: 1fr 1fr; }
.ls-info-cell {
    padding: 11px 16px;
    border-bottom: 1px solid var(--border-subtle);
    border-right:  1px solid var(--border-subtle);
}
.ls-info-cell:nth-child(even)  { border-right: none; }
.ls-info-cell:nth-last-child(-n+2) { border-bottom: none; }
.ls-info-lbl {
    font-size: 11px; font-weight: 600;
    color: var(--text-300); text-transform: uppercase;
    letter-spacing: 0.5px; margin-bottom: 3px;
}
.ls-info-val { font-size: 13.5px; font-weight: 500; color: var(--text-100); }
.ls-info-val a { color: var(--accent); text-decoration: none; }
.ls-info-val.muted { color: var(--text-400); font-weight: 400; font-style: italic; }

/* ── Timeline ── */
.ls-timeline-wrap { padding: 18px 22px; }
.ls-tl-item {
    display: flex; gap: 12px;
    padding-bottom: 18px; position: relative;
}
.ls-tl-item:last-child { padding-bottom: 0; }
.ls-tl-item:not(:last-child)::after {
    content: ''; position: absolute;
    left: 15px; top: 32px; bottom: 0;
    width: 1px; background: var(--border-subtle);
}
.ls-tl-icon {
    width: 30px; height: 30px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0; z-index: 1;
}
.ls-tl-body { flex: 1; padding-top: 4px; }
.ls-tl-meta { display: flex; align-items: center; gap: 8px; margin-bottom: 3px; }
.ls-tl-action { font-size: 13px; font-weight: 600; color: var(--text-100); }
.ls-tl-time   { font-size: 11.5px; color: var(--text-300); font-family: 'DM Mono', monospace; }
.ls-tl-desc   { font-size: 12.5px; color: var(--text-300); line-height: 1.5; }
.ls-tl-tag    {
    display: inline-block; padding: 2px 8px; border-radius: 4px;
    font-size: 11px; font-weight: 600; background: #E6F1FB; color: #185FA5;
}

/* ── Note Box ── */
.ls-note-box {
    padding: 14px 22px;
    border-top: 1px solid var(--border-subtle);
    background: var(--bg-elevated);
}
.ls-note-input {
    width: 100%; padding: 9px 12px;
    background: var(--bg-surface);
    border: 1.5px solid var(--border-default);
    border-radius: 8px; font-family: 'DM Sans', var(--font), sans-serif;
    font-size: 13px; color: var(--text-100); outline: none;
    resize: none; min-height: 60px; transition: border-color .15s;
}
.ls-note-input:focus { border-color: var(--accent); }
.ls-note-input::placeholder { color: var(--text-400); }
.ls-note-actions { display: flex; justify-content: flex-end; gap: 8px; margin-top: 8px; }

/* ── Score Ring ── */
.ls-score-wrap  { display: flex; align-items: center; gap: 16px; padding: 16px; }
.ls-score-ring  { position: relative; width: 64px; height: 64px; flex-shrink: 0; }
.ls-score-ring svg { transform: rotate(-90deg); }
.ls-score-num {
    position: absolute; inset: 0;
    display: flex; align-items: center; justify-content: center;
    font-size: 16px; font-weight: 600; color: #185FA5;
    font-family: 'DM Mono', monospace;
}
.ls-score-title { font-size: 14px; font-weight: 600; color: var(--text-100); margin-bottom: 4px; }
.ls-score-sub   { font-size: 12px; color: var(--text-300); line-height: 1.4; }

/* ── Assignee ── */
.ls-assignee-row { display: flex; align-items: center; gap: 10px; }
.ls-sm-avatar {
    width: 34px; height: 34px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 12px; font-weight: 600; flex-shrink: 0;
}
.ls-assignee-name { font-size: 13.5px; font-weight: 500; color: var(--text-100); }
.ls-assignee-role { font-size: 12px; color: var(--text-300); }

/* ── Quick Actions ── */
.ls-qa-list { display: flex; flex-direction: column; gap: 6px; padding: 12px; }
.ls-qa-btn {
    display: flex; align-items: center; gap: 10px;
    padding: 9px 12px; border-radius: 8px; cursor: pointer;
    background: var(--bg-elevated); border: 1px solid var(--border-subtle);
    font-family: 'DM Sans', var(--font), sans-serif;
    font-size: 13px; color: var(--text-100); font-weight: 500;
    transition: all .15s; width: 100%; text-align: left;
    text-decoration: none;
}
.ls-qa-btn:hover { background: var(--bg-surface); border-color: var(--border-default); }
.ls-qa-icon {
    width: 28px; height: 28px; border-radius: 7px;
    display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}

/* ── Detail List ── */
.ls-dl-row {
    display: flex; justify-content: space-between; align-items: center;
    padding: 9px 16px; border-bottom: 1px solid var(--border-subtle);
}
.ls-dl-row:last-child { border-bottom: none; }
.ls-dl-key { font-size: 12px; color: var(--text-300); }
.ls-dl-val { font-size: 12.5px; font-weight: 500; color: var(--text-100); }

/* ── Status Badge Colors ── */
.status-new         { background: #E6F1FB; color: #185FA5; }
.status-contacted   { background: #E1F5EE; color: #0F6E56; }
.status-qualified   { background: #EAF3DE; color: #3B6D11; }
.status-proposal    { background: #FAEEDA; color: #854F0B; }
.status-negotiation { background: #EEEDFE; color: #3C3489; }
.status-won         { background: #E1F5EE; color: #085041; }
.status-lost        { background: #FCEBEB; color: #A32D2D; }
.priority-low       { background: #EAF3DE; color: #3B6D11; }
.priority-medium    { background: #FAEEDA; color: #854F0B; }
.priority-high      { background: #FCEBEB; color: #A32D2D; }
.source-referral    { background: #E1F5EE; color: #0F6E56; }
.source-website     { background: #E6F1FB; color: #185FA5; }
.source-social      { background: #EEEDFE; color: #534AB7; }
.source-cold_call   { background: #FAEEDA; color: #854F0B; }
.source-other       { background: #F1EFE8; color: #5F5E5A; }

@keyframes ls-spin { from{transform:rotate(0deg)} to{transform:rotate(360deg)} }

/* ── Modal ── */
.modal-overlay { position:fixed; inset:0; background:rgba(0,0,0,.5); display:flex; align-items:center; justify-content:center; z-index:1000; padding:20px; }
.modal-box { background:var(--bg-surface); border:1px solid var(--border-default); border-radius:14px; width:100%; overflow:hidden; display:flex; flex-direction:column; max-height:90vh; }
.modal-head { padding:16px 20px; border-bottom:1px solid var(--border-subtle); display:flex; justify-content:space-between; align-items:center; }
.modal-title { font-size:15px; font-weight:700; color:var(--text-100); }
.modal-body  { padding:20px; display:flex; flex-direction:column; gap:14px; overflow-y:auto; flex:1; }
.modal-foot  { padding:14px 20px; border-top:1px solid var(--border-subtle); display:flex; justify-content:flex-end; gap:8px; background:var(--bg-elevated); }
</style>
@endpush

@section('content')

@php
/* ── Helpers ── */
$initials = collect(explode(' ', $lead->name))->map(fn($p) => strtoupper($p[0] ?? ''))->join('');
$initials = substr($initials, 0, 2);

$statusLabel = [
    'new'=>'New','contacted'=>'Contacted','qualified'=>'Qualified',
    'proposal'=>'Proposal Sent','negotiation'=>'Negotiation','won'=>'Won','lost'=>'Lost',
][$lead->status ?? 'new'] ?? ucfirst($lead->status ?? 'new');

$priorityLabel = ucfirst($lead->priority ?? 'medium');
$sourceLabel   = ucwords(str_replace('_',' ', $lead->source ?? 'other'));

$daysActive = $lead->created_at ? (int) $lead->created_at->diffInDays(now()) : 0;

/* Lead pipeline stages */
$stages      = ['new', 'contacted', 'qualified', 'converted'];
$stageLabels = ['New', 'Contacted', 'Qualified', 'Converted'];
$currentStageIdx = array_search($lead->status, $stages);
if ($currentStageIdx === false) $currentStageIdx = 0;

/* Deal stage pipeline (only when converted) */
$dealStages      = ['new', 'proposal', 'negotiation', 'won'];
$dealStageLabels = ['New', 'Proposal', 'Negotiation', 'Won'];
$deal            = $lead->deal;
$dealStageIdx    = $deal ? array_search($deal->stage, $dealStages) : false;
if ($dealStageIdx === false) $dealStageIdx = 0;

/* Score: simple server-side calc */
$score = 20;
if (($lead->priority ?? '') === 'high')        $score += 25;
elseif (($lead->priority ?? '') === 'medium')  $score += 15;
else                                            $score += 5;
if (($lead->source ?? '') === 'referral')      $score += 20;
elseif (($lead->source ?? '') === 'website')   $score += 15;
elseif (($lead->source ?? '') === 'social')    $score += 10;
else                                            $score += 5;
if (($lead->lead_value ?? 0) > 100000)        $score += 20;
elseif (($lead->lead_value ?? 0) > 50000)     $score += 15;
elseif (($lead->lead_value ?? 0) > 10000)     $score += 10;
if ($lead->expected_close_date)               $score += 10;
if (strlen($lead->notes ?? '') > 20)          $score += 5;
$score = min($score, 100);

/* Ring circumference = 2π×26 ≈ 163.4 */
$ringOffset = 163.4 - (163.4 * $score / 100);

/* Assignee initials */
$assigneeInitials = '';
if ($lead->assignedTo) {
    $assigneeInitials = collect(explode(' ', $lead->assignedTo->name))
        ->map(fn($p) => strtoupper($p[0] ?? ''))->join('');
    $assigneeInitials = substr($assigneeInitials, 0, 2);
}
@endphp

<div class="ls-page">

    {{-- Page Header --}}
    <div class="page-head">
        <div>
            <div style="font-size:12px;color:var(--text-300);margin-bottom:4px;display:flex;align-items:center;gap:5px">
                <a href="{{ route('tenant.leads.index') }}" style="color:var(--text-300);text-decoration:none">Leads</a>
                <span style="opacity:.4">›</span>
                <span>{{ $lead->name }}</span>
            </div>
            <div class="page-title">Lead Detail</div>
        </div>
        <div style="display:flex;gap:8px">
            @if($isAdmin || $lead->assigned_to === auth()->id())
            <a href="{{ route('tenant.leads.edit', $lead) }}" class="btn btn-secondary">
                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                Edit Lead
            </a>
            @endif
            <button class="btn btn-primary" onclick="document.getElementById('logCallModal').classList.add('open')">
                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                Log Call
            </button>
            @if($isAdmin || $lead->assigned_to === auth()->id())
            <form method="POST" action="{{ route('tenant.leads.destroy', $lead) }}"
                  onsubmit="return confirm('Are you sure you want to delete this lead?')" style="display:inline">
                @csrf @method('DELETE')
                <button type="submit" class="btn" style="background:#FCEBEB;border-color:#F09595;color:#A32D2D">
                    <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                </button>
            </form>
            @endif
        </div>
    </div>

    <div class="ls-layout">

        {{-- ── Main Column ── --}}
        <div class="ls-main">

            {{-- Hero Card --}}
            <div class="ls-card">
                <div class="ls-hero">
                    <div class="ls-hero-top">
                        <div class="ls-avatar">{{ $initials }}</div>
                        <div style="flex:1">
                            <div class="ls-lead-name">{{ $lead->name }}</div>
                            <div class="ls-lead-sub">
                                {{ $lead->designation ?? '—' }}
                                @if($lead->company) · {{ $lead->company }} @endif
                            </div>
                            <div class="ls-badges">
                                <span class="ls-badge status-{{ $lead->status ?? 'new' }}">
                                    <svg width="7" height="7" viewBox="0 0 8 8"><circle cx="4" cy="4" r="4" fill="currentColor"/></svg>
                                    {{ $statusLabel }}
                                </span>
                                <span class="ls-badge priority-{{ $lead->priority ?? 'medium' }}">
                                    {{ $priorityLabel }} Priority
                                </span>
                                <span class="ls-badge source-{{ $lead->source ?? 'other' }}">
                                    {{ $sourceLabel }}
                                </span>
                                @if($lead->city)
                                <span class="ls-badge" style="background:var(--bg-elevated);color:var(--text-200)">
                                    {{ $lead->city }}@if($lead->state), {{ $lead->state }}@endif
                                </span>
                                @endif
                            </div>
                        </div>
                        @if($lead->lead_value)
                        <div style="flex-shrink:0;text-align:right">
                            <div class="ls-deal-value">₹{{ number_format($lead->lead_value) }}</div>
                            <div class="ls-deal-lbl">Deal Value</div>
                        </div>
                        @endif
                    </div>
                </div>
                <div class="ls-stats">
                    <div>
                        <div class="ls-stat-val">{{ $daysActive }}</div>
                        <div class="ls-stat-lbl">Days Active</div>
                    </div>
                    <div>
                        <div class="ls-stat-val">{{ $lead->callLogs->count() }}</div>
                        <div class="ls-stat-lbl">Touchpoints</div>
                    </div>
                    <div>
                        <div class="ls-stat-val" style="color:#1D9E75">{{ $score }}</div>
                        <div class="ls-stat-lbl">Lead Score</div>
                    </div>
                    <div>
                        <div class="ls-stat-val" style="color:#BA7517">
                            {{ $lead->expected_close_date ? \Carbon\Carbon::parse($lead->expected_close_date)->format('d M') : '—' }}
                        </div>
                        <div class="ls-stat-lbl">Close Date</div>
                    </div>
                </div>
            </div>

            {{-- Lead Pipeline --}}
            <div class="ls-card">
                <div class="ls-pipeline">
                    <div class="ls-card-title">Lead Pipeline</div>
                    <div class="ls-pip-steps">
                        @foreach($stages as $i => $stage)
                        @php
                            if ($lead->status === 'lost' && $i === $currentStageIdx) {
                                $cls = 'ls-pip-lost';
                            } elseif ($i < $currentStageIdx) {
                                $cls = 'ls-pip-done';
                            } elseif ($i === $currentStageIdx) {
                                $cls = 'ls-pip-active';
                            } else {
                                $cls = 'ls-pip-pending';
                            }
                        @endphp
                        <div class="ls-pip-step {{ $cls }}">
                            <div class="ls-pip-dot">
                                @if($i < $currentStageIdx)
                                <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                @else
                                {{ $i + 1 }}
                                @endif
                            </div>
                            <div class="ls-pip-lbl">{{ $stageLabels[$i] }}</div>
                        </div>
                        @if($i < count($stages) - 1)
                        <div class="ls-pip-line {{ $i < $currentStageIdx ? 'done' : '' }}"></div>
                        @endif
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Deal Stage (only when lead is converted and deal exists) --}}
            @if($lead->isConverted() && $deal)
            <div class="ls-card">
                <div class="ls-pipeline">
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:4px">
                        <div class="ls-card-title">Deal Stage</div>
                        @php
                            $dealStageBadgeMap = [
                                'new'         => ['bg'=>'#E6F1FB','color'=>'#185FA5'],
                                'proposal'    => ['bg'=>'#FAEEDA','color'=>'#854F0B'],
                                'negotiation' => ['bg'=>'#EEEDFE','color'=>'#3C3489'],
                                'won'         => ['bg'=>'#E1F5EE','color'=>'#085041'],
                                'lost'        => ['bg'=>'#FCEBEB','color'=>'#A32D2D'],
                            ];
                            $dsb = $dealStageBadgeMap[$deal->stage] ?? ['bg'=>'#F1EFE8','color'=>'#5F5E5A'];
                        @endphp
                        <span style="font-size:11.5px;font-weight:600;padding:3px 10px;border-radius:20px;background:{{ $dsb['bg'] }};color:{{ $dsb['color'] }}">
                            {{ \App\Models\Deal::stages()[$deal->stage] ?? ucfirst($deal->stage) }}
                        </span>
                    </div>

                    <div class="ls-pip-steps" style="margin-top:16px">
                        @foreach($dealStages as $i => $dStage)
                        @php
                            if ($deal->stage === 'lost' && $i === $dealStageIdx) {
                                $dcls = 'ls-pip-lost';
                            } elseif ($i < $dealStageIdx) {
                                $dcls = 'ls-pip-done';
                            } elseif ($i === $dealStageIdx) {
                                $dcls = 'ls-pip-active';
                            } else {
                                $dcls = 'ls-pip-pending';
                            }
                        @endphp
                        <div class="ls-pip-step {{ $dcls }}">
                            <div class="ls-pip-dot">
                                @if($i < $dealStageIdx)
                                <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                @else
                                {{ $i + 1 }}
                                @endif
                            </div>
                            <div class="ls-pip-lbl">{{ $dealStageLabels[$i] }}</div>
                        </div>
                        @if($i < count($dealStages) - 1)
                        <div class="ls-pip-line {{ $i < $dealStageIdx ? 'done' : '' }}"></div>
                        @endif
                        @endforeach
                    </div>

                    <div style="display:flex;align-items:center;justify-content:space-between;margin-top:10px;padding-top:10px;border-top:1px solid var(--border-subtle)">
                        <div style="font-size:12px;color:var(--text-300)">
                            Deal Value:
                            <strong style="color:var(--text-100)">₹{{ number_format($deal->value) }}</strong>
                        </div>
                        <a href="{{ route('tenant.deals.show', $deal->id) }}" class="ls-deal-link">
                            View Deal
                            <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                        </a>
                    </div>
                </div>
            </div>
            @endif

            {{-- Contact Info --}}
            <div class="ls-card">
                <div style="padding:14px 22px 12px;border-bottom:1px solid var(--border-subtle)">
                    <div class="ls-card-title">Contact Information</div>
                </div>
                <div class="ls-info-grid">
                    <div class="ls-info-cell">
                        <div class="ls-info-lbl">Phone</div>
                        <div class="ls-info-val">
                            @if($lead->phone)
                            <a href="tel:{{ $lead->phone }}">{{ $lead->phone }}</a>
                            @else <span class="muted">Not provided</span> @endif
                        </div>
                    </div>
                    <div class="ls-info-cell">
                        <div class="ls-info-lbl">Email</div>
                        <div class="ls-info-val">
                            @if($lead->email)
                            <a href="mailto:{{ $lead->email }}">{{ $lead->email }}</a>
                            @else <span class="muted">Not provided</span> @endif
                        </div>
                    </div>
                    <div class="ls-info-cell">
                        <div class="ls-info-lbl">Company</div>
                        <div class="ls-info-val">{{ $lead->company ?? '—' }}</div>
                    </div>
                    <div class="ls-info-cell">
                        <div class="ls-info-lbl">Designation</div>
                        <div class="ls-info-val">{{ $lead->designation ?? '—' }}</div>
                    </div>
                    <div class="ls-info-cell">
                        <div class="ls-info-lbl">City</div>
                        <div class="ls-info-val">{{ $lead->city ?? '—' }}</div>
                    </div>
                    <div class="ls-info-cell">
                        <div class="ls-info-lbl">State</div>
                        <div class="ls-info-val">{{ $lead->state ?? '—' }}</div>
                    </div>
                </div>
                @if($lead->notes)
                <div style="padding:14px 16px;border-top:1px solid var(--border-subtle)">
                    <div class="ls-info-lbl" style="margin-bottom:6px">Notes</div>
                    <div style="font-size:13px;color:var(--text-200);line-height:1.6">{{ $lead->notes }}</div>
                </div>
                @endif
            </div>

            {{-- Follow-up History --}}
            <div class="ls-card">
                <div style="padding:16px 22px 14px;border-bottom:1px solid var(--border-subtle);display:flex;align-items:center;justify-content:space-between">
                    <div class="ls-card-title">
                        Follow-up History
                        @if($lead->followups->count())
                        <span style="color:var(--text-400);font-weight:500;text-transform:none;letter-spacing:0">({{ $lead->followups->count() }})</span>
                        @endif
                    </div>
                    <a href="{{ route('tenant.followups.create', ['lead_id' => $lead->id]) }}"
                       style="font-size:12px;font-weight:600;color:var(--accent);text-decoration:none;display:inline-flex;align-items:center;gap:4px">
                        <svg width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                        Schedule Follow-up
                    </a>
                </div>

                @php
                $fuStatusMap = [
                    'scheduled'   => ['bg'=>'#E6F1FB','color'=>'#185FA5','label'=>'Scheduled'],
                    'done'        => ['bg'=>'#E1F5EE','color'=>'#0F6E56','label'=>'Done'],
                    'missed'      => ['bg'=>'#FCEBEB','color'=>'#A32D2D','label'=>'Missed'],
                    'rescheduled' => ['bg'=>'#FAEEDA','color'=>'#854F0B','label'=>'Rescheduled'],
                ];
                $fuTypeIcons = [
                    'call'     => 'M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z',
                    'email'    => 'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z',
                    'whatsapp' => 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z',
                    'meeting'  => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
                    'other'    => 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z',
                ];
                $sortedFollowups = $lead->followups->sortByDesc('scheduled_at');
                @endphp

                @if($sortedFollowups->isEmpty())
                <div style="padding:26px 22px;text-align:center;color:var(--text-300);font-size:13px">
                    No follow-ups scheduled yet for this lead.
                </div>
                @else
                <div class="ls-timeline-wrap">
                    @foreach($sortedFollowups as $fu)
                    @php
                        $fs   = $fuStatusMap[$fu->status] ?? ['bg'=>'#F1EFE8','color'=>'#5F5E5A','label'=>ucfirst($fu->status)];
                        $path = $fuTypeIcons[$fu->type] ?? $fuTypeIcons['other'];
                    @endphp
                    <div class="ls-tl-item">
                        <div class="ls-tl-icon" style="background:{{ $fs['bg'] }}">
                            <svg width="14" height="14" fill="none" stroke="{{ $fs['color'] }}" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $path }}"/></svg>
                        </div>
                        <div class="ls-tl-body">
                            <div class="ls-tl-meta">
                                <span class="ls-tl-action">{{ \App\Models\Followup::types()[$fu->type] ?? ucfirst($fu->type) }}</span>
                                <span class="ls-badge" style="background:{{ $fs['bg'] }};color:{{ $fs['color'] }};padding:2px 8px;font-size:10.5px">{{ $fs['label'] }}</span>
                                <span class="ls-tl-time">{{ $fu->scheduled_at?->format('M d, Y · g:i A') }}</span>
                            </div>
                            @if($fu->status === 'done' && $fu->outcome)
                            <div class="ls-tl-desc">{{ $fu->outcome }}</div>
                            @elseif($fu->notes)
                            <div class="ls-tl-desc">{{ $fu->notes }}</div>
                            @else
                            <div class="ls-tl-desc" style="color:var(--text-400);font-style:italic">No notes added</div>
                            @endif
                            <a href="{{ route('tenant.followups.show', $fu) }}" style="font-size:11px;color:var(--accent);text-decoration:none;margin-top:3px;display:inline-block">View details →</a>
                        </div>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>

            {{-- Activity Timeline --}}
            <div class="ls-card">
                <div style="padding:16px 22px 14px;border-bottom:1px solid var(--border-subtle)">
                    <div class="ls-card-title">Activity Timeline</div>
                </div>

                <div class="ls-timeline-wrap">
                    @php
                    $tlIconColors = [
                        'note'     => ['bg'=>'#E6F1FB','stroke'=>'#185FA5'],
                        'call'     => ['bg'=>'#E1F5EE','stroke'=>'#0F6E56'],
                        'email'    => ['bg'=>'#FAEEDA','stroke'=>'#BA7517'],
                        'meeting'  => ['bg'=>'#EEEDFE','stroke'=>'#534AB7'],
                        'whatsapp' => ['bg'=>'#E1F5EE','stroke'=>'#0F6E56'],
                    ];
                    $outcomeLabels = \App\Models\LeadCallLog::outcomes();
                    @endphp

                    @forelse($lead->callLogs as $log)
                    @php $ic = $tlIconColors[$log->type] ?? $tlIconColors['note']; @endphp
                    <div class="ls-tl-item">
                        <div class="ls-tl-icon" style="background:{{ $ic['bg'] }}">
                            @if($log->type === 'call')
                            <svg width="14" height="14" fill="none" stroke="{{ $ic['stroke'] }}" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                            @elseif($log->type === 'email')
                            <svg width="14" height="14" fill="none" stroke="{{ $ic['stroke'] }}" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            @elseif($log->type === 'meeting')
                            <svg width="14" height="14" fill="none" stroke="{{ $ic['stroke'] }}" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            @elseif($log->type === 'whatsapp')
                            <svg width="14" height="14" fill="none" stroke="{{ $ic['stroke'] }}" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                            @else
                            <svg width="14" height="14" fill="none" stroke="{{ $ic['stroke'] }}" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            @endif
                        </div>
                        <div class="ls-tl-body">
                            <div class="ls-tl-meta">
                                <span class="ls-tl-action">{{ \App\Models\LeadCallLog::types()[$log->type] ?? ucfirst($log->type) }}</span>
                                @if($log->type === 'call' && $log->call_outcome)
                                <span class="ls-tl-tag">{{ $outcomeLabels[$log->call_outcome] ?? $log->call_outcome }}</span>
                                @endif
                                @if($log->type === 'call' && $log->call_duration)
                                <span style="font-size:11px;color:var(--text-300)">{{ $log->call_duration }}m</span>
                                @endif
                                <span class="ls-tl-time">{{ $log->created_at->diffForHumans() }}</span>
                            </div>
                            <div class="ls-tl-desc">{{ $log->description }}</div>
                            @if($log->createdBy)
                            <div style="font-size:11px;color:var(--text-400);margin-top:3px">by {{ $log->createdBy->name }}</div>
                            @endif
                        </div>
                    </div>
                    @empty
                    @endforelse

                    {{-- custom fields --}}
                    @include('components.custom-fields-display', [
                        'customFields' => $customFields,
                        'customValues' => $customValues,
                    ])
                    {{-- Lead Created entry --}}
                    <div class="ls-tl-item" style="margin-top:{{ $lead->callLogs->count() ? '4px' : '0' }}">
                        <div class="ls-tl-icon" style="background:#EAF3DE">
                            <svg width="14" height="14" fill="none" stroke="#3B6D11" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
                        </div>
                        <div class="ls-tl-body">
                            <div class="ls-tl-meta">
                                <span class="ls-tl-action">Lead Created</span>
                                <span class="ls-tl-time">{{ $lead->created_at->format('M d, Y · g:i A') }}</span>
                            </div>
                            <div class="ls-tl-desc">
                                Created by {{ $lead->createdBy->name ?? 'System' }} · Source: {{ $sourceLabel }}
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Add Note Box --}}
                <div class="ls-note-box">
                    <form method="POST" action="{{ route('tenant.leads.call-log.store', $lead) }}" id="noteForm">
                        @csrf
                        <input type="hidden" name="type" id="activityType" value="note">
                        <textarea name="description" class="ls-note-input"
                                  placeholder="Add a note, log a call, or record an update..."
                                  required></textarea>
                        <div class="ls-note-actions">
                            <button type="button" class="btn btn-secondary"
                                    onclick="document.getElementById('logCallModal').classList.add('open')">
                                <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                Log Call
                            </button>
                            <button type="submit" class="btn btn-primary">Add Note</button>
                        </div>
                    </form>
                </div>
            </div>

        </div>{{-- /ls-main --}}

        {{-- ── Sidebar ── --}}
        <div class="ls-sidebar">

            {{-- Lead Score --}}
            <div class="ls-card">
                <div class="ls-score-wrap">
                    <div class="ls-score-ring">
                        <svg width="64" height="64" viewBox="0 0 64 64">
                            <circle cx="32" cy="32" r="26" fill="none" stroke="#E6F1FB" stroke-width="6"/>
                            <circle cx="32" cy="32" r="26" fill="none" stroke="#185FA5" stroke-width="6"
                                    stroke-dasharray="163.4"
                                    stroke-dashoffset="{{ round($ringOffset, 1) }}"
                                    stroke-linecap="round"/>
                        </svg>
                        <div class="ls-score-num">{{ $score }}</div>
                    </div>
                    <div>
                        <div class="ls-score-title">Lead Score</div>
                        <div class="ls-score-sub">
                            @if($score >= 70) High quality lead. Strong conversion potential.
                            @elseif($score >= 45) Moderate lead. Follow up regularly.
                            @else Early stage. Needs more qualification.
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Assigned To --}}
            <div class="ls-card" style="padding:16px">
                <div class="ls-card-title" style="margin-bottom:14px">Assigned To</div>
                @if($lead->assignee)
                <div class="ls-assignee-row">
                    <div class="ls-sm-avatar" style="background:#E6F1FB;color:#185FA5">
                        {{ $assigneeInitials }}
                    </div>
                    <div>
                        <div class="ls-assignee-name">{{ $lead->assignee->name }}</div>
                        <div class="ls-assignee-role">{{ $lead->assignee->designation ?? 'Sales Team' }}</div>
                    </div>
                    @if($isAdmin || $lead->assigned_to === auth()->id())
                    <a href="{{ route('tenant.leads.edit', $lead) }}" style="margin-left:auto;font-size:12px;color:var(--accent);text-decoration:none">Change</a>
                    @endif
                </div>
                @else
                <div style="font-size:13px;color:var(--text-300);font-style:italic">Unassigned</div>
                @if($isAdmin || $lead->assigned_to === auth()->id())
                <a href="{{ route('tenant.leads.edit', $lead) }}" class="btn btn-secondary" style="margin-top:10px;justify-content:center;width:100%;font-size:12px">
                    Assign Now
                </a>
                @endif
                @endif
            </div>

            {{-- Quick Actions --}}
            <div class="ls-card">
                <div style="padding:14px 16px 6px">
                    <div class="ls-card-title">Quick Actions</div>
                </div>
                <div class="ls-qa-list">
                    @if($lead->email)
                    <a href="mailto:{{ $lead->email }}" class="ls-qa-btn">
                        <div class="ls-qa-icon" style="background:#E6F1FB">
                            <svg width="14" height="14" fill="none" stroke="#185FA5" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        </div>
                        Send Email
                    </a>
                    @endif
                    @if($lead->phone)
                    <a href="https://wa.me/91{{ preg_replace('/\D/','',$lead->phone) }}" target="_blank" class="ls-qa-btn">
                        <div class="ls-qa-icon" style="background:#E1F5EE">
                            <svg width="14" height="14" fill="none" stroke="#0F6E56" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                        </div>
                        Send WhatsApp
                    </a>
                    @endif
                    <button class="ls-qa-btn" onclick="document.getElementById('proposalModal').classList.add('open')">
                        <div class="ls-qa-icon" style="background:#FAEEDA">
                            <svg width="14" height="14" fill="none" stroke="#BA7517" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        </div>
                        Create Proposal
                    </button>
                    <a href="{{ route('tenant.followups.create', ['lead_id' => $lead->id]) }}" class="ls-qa-btn">
                        <div class="ls-qa-icon" style="background:#EEEDFE">
                            <svg width="14" height="14" fill="none" stroke="#534AB7" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        </div>
                        Schedule Follow-up
                    </a>
                    @if(!$lead->isConverted())
                    <form method="POST" action="{{ route('tenant.leads.convert', $lead) }}">
                        @csrf
                        <button type="submit" class="ls-qa-btn" style="color:#1D9E75;border-color:#9FE1CB;background:#E1F5EE;width:100%">
                            <div class="ls-qa-icon" style="background:#E1F5EE">
                                <svg width="14" height="14" fill="none" stroke="#1D9E75" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                            Mark as Won
                        </button>
                    </form>
                    @endif
                </div>
            </div>

            {{-- Lead Meta Details --}}
            <div class="ls-card">
                <div style="padding:14px 16px 4px">
                    <div class="ls-card-title">Lead Details</div>
                </div>
                <div>
                    <div class="ls-dl-row">
                        <span class="ls-dl-key">Source</span>
                        <span class="ls-badge source-{{ $lead->source ?? 'other' }}" style="font-size:11.5px">{{ $sourceLabel }}</span>
                    </div>
                    <div class="ls-dl-row">
                        <span class="ls-dl-key">Priority</span>
                        <span class="ls-badge priority-{{ $lead->priority ?? 'medium' }}" style="font-size:11.5px">{{ $priorityLabel }}</span>
                    </div>
                    <div class="ls-dl-row">
                        <span class="ls-dl-key">Created</span>
                        <span class="ls-dl-val">{{ $lead->created_at->format('M d, Y') }}</span>
                    </div>
                    <div class="ls-dl-row">
                        <span class="ls-dl-key">Close Date</span>
                        <span class="ls-dl-val" style="{{ $lead->expected_close_date ? 'color:#BA7517' : '' }}">
                            {{ $lead->expected_close_date ? \Carbon\Carbon::parse($lead->expected_close_date)->format('M d, Y') : '—' }}
                        </span>
                    </div>
                    <div class="ls-dl-row">
                        <span class="ls-dl-key">Lead ID</span>
                        <span class="ls-dl-val" style="font-family:'DM Mono',monospace;font-size:12px">#LD-{{ str_pad($lead->id,4,'0',STR_PAD_LEFT) }}</span>
                    </div>
                    @if($lead->updated_at)
                    <div class="ls-dl-row">
                        <span class="ls-dl-key">Last Updated</span>
                        <span class="ls-dl-val" style="font-size:12px">{{ $lead->updated_at->diffForHumans() }}</span>
                    </div>
                    @endif
                </div>
            </div>

        </div>{{-- /ls-sidebar --}}

    </div>{{-- /ls-layout --}}
</div>

{{-- ── Log Call Modal ─────────────────────────────────────────── --}}
<div class="modal-overlay" id="logCallModal" style="display:none" onclick="if(event.target===this)closeCallModal()">
    <div class="modal-box" style="max-width:500px">
        <div class="modal-head">
            <span class="modal-title">Log Call</span>
            <button type="button" onclick="closeCallModal()" style="background:none;border:none;cursor:pointer;color:var(--text-300);font-size:20px;line-height:1">&times;</button>
        </div>
        <form method="POST" action="{{ route('tenant.leads.call-log.store', $lead) }}" id="callLogForm">
            @csrf
            <input type="hidden" name="type" value="call">
            <div class="modal-body">
                <div>
                    <label style="font-size:12px;font-weight:600;color:var(--text-300);text-transform:uppercase;letter-spacing:.5px;display:block;margin-bottom:6px">Call Outcome</label>
                    <select name="call_outcome" style="width:100%;padding:9px 12px;border:1.5px solid var(--border-default);border-radius:8px;background:var(--bg-surface);color:var(--text-100);font-size:13px;outline:none">
                        <option value="">— Select outcome —</option>
                        <option value="connected">Connected</option>
                        <option value="no_answer">No Answer</option>
                        <option value="voicemail">Left Voicemail</option>
                        <option value="callback">Requested Callback</option>
                        <option value="not_interested">Not Interested</option>
                    </select>
                </div>
                <div>
                    <label style="font-size:12px;font-weight:600;color:var(--text-300);text-transform:uppercase;letter-spacing:.5px;display:block;margin-bottom:6px">Duration (minutes)</label>
                    <input type="number" name="call_duration" min="1" max="999" placeholder="e.g. 5"
                           style="width:100%;padding:9px 12px;border:1.5px solid var(--border-default);border-radius:8px;background:var(--bg-surface);color:var(--text-100);font-size:13px;outline:none">
                </div>
                <div>
                    <label style="font-size:12px;font-weight:600;color:var(--text-300);text-transform:uppercase;letter-spacing:.5px;display:block;margin-bottom:6px">Notes <span style="color:#E05252">*</span></label>
                    <textarea name="description" required rows="3"
                              placeholder="What was discussed on the call?"
                              style="width:100%;padding:9px 12px;border:1.5px solid var(--border-default);border-radius:8px;background:var(--bg-surface);color:var(--text-100);font-size:13px;outline:none;resize:none;font-family:inherit"></textarea>
                </div>
            </div>
            <div class="modal-foot">
                <button type="button" onclick="closeCallModal()" class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn btn-primary" id="callLogSubmitBtn">
                    <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                    Save Call
                </button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
(function(){
    /* Modal helpers */
    function openCallModal() {
        const m = document.getElementById('logCallModal');
        m.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
    function closeCallModal() {
        const m = document.getElementById('logCallModal');
        m.style.display = 'none';
        document.body.style.overflow = '';
    }
    window.closeCallModal = closeCallModal;

    /* Wire the "Log Call" button in the header */
    document.querySelectorAll('[onclick*="logCallModal"]').forEach(el => {
        el.removeAttribute('onclick');
        el.addEventListener('click', openCallModal);
    });

    /* Call log form submit */
    const callLogForm = document.getElementById('callLogForm');
    if (callLogForm) {
        callLogForm.addEventListener('submit', function () {
            const btn = document.getElementById('callLogSubmitBtn');
            if (btn) { btn.textContent = 'Saving...'; btn.disabled = true; }
        });
    }

    /* Note form submit loading state */
    const noteForm = document.getElementById('noteForm');
    if (noteForm) {
        noteForm.addEventListener('submit', function () {
            const btn = this.querySelector('button[type="submit"]');
            if (btn) { btn.textContent = 'Saving...'; btn.disabled = true; }
        });
    }
})();
</script>
@endpush