@extends('layouts.app')
@section('title', 'Deals')

@push('styles')
<style>
@import url('https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=DM+Mono:wght@400;500&display=swap');
@import url('https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.19.0/dist/tabler-icons.min.css');

.di { font-family: 'DM Sans', var(--font), sans-serif; }

/* Summary */
.di-summary {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
    gap: 14px;
    margin-bottom: 20px;
}
@media(max-width:1000px){ .di-summary { grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); } }
@media(max-width:640px) { .di-summary { grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); } }
.di-sum {
    background: var(--bg-surface);
    border: 1px solid var(--border-default);
    border-radius: 18px;
    padding: 18px 18px 14px;
    text-decoration: none;
    display: grid;
    grid-template-rows: auto 1fr auto;
    row-gap: 8px;
    position: relative;
    overflow: hidden;
    transition: transform .15s, border-color .15s, background .15s, box-shadow .15s;
}
.di-sum:hover {
    border-color: var(--accent);
    transform: translateY(-1px);
    box-shadow: 0 6px 22px rgba(0,0,0,.06);
}
.di-sum.active {
    border-color: var(--accent);
    background: rgba(56,138,221,.08);
    box-shadow: 0 10px 28px rgba(56,138,221,.08);
}
.di-sum-val {
    font-size: 22px;
    font-weight: 700;
    color: var(--text-100);
    font-family: 'DM Mono', monospace;
    letter-spacing: -.5px;
    line-height: 1;
}
.di-sum.active .di-sum-val { color: #185FA5; }
.di-sum-lbl {
    font-size: 11px;
    color: var(--text-300);
    margin-top: 2px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: .08em;
}
.di-sum.active .di-sum-lbl { color: var(--text-300); }
.di-sum-amt {
    font-size: 12px;
    color: var(--text-400);
    font-family: 'DM Mono', monospace;
}
.di-sum-bar {
    position: absolute;
    bottom: 0;
    left: 0;
    width: 100%;
    height: 4px;
    border-radius: 0 0 18px 18px;
}

/* Toolbar */
.di-toolbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    flex-wrap: wrap;
    margin-bottom: 18px;
}
.di-toolbar > * { min-width: 0; }
.di-fi {
    padding: 8px 11px; height: 36px; background: var(--bg-surface);
    border: 1px solid var(--border-default); border-radius: 8px; font-size: 12.5px;
    color: var(--text-100); font-family: 'DM Sans', var(--font), sans-serif;
    outline: none; transition: border-color .15s, box-shadow .15s; -webkit-appearance: none; cursor: pointer;
    min-width: 0;
}
.di-fi:focus { border-color: var(--accent); box-shadow: 0 0 0 3px var(--accent-dim); }
.di-sw  { position: relative; flex: 1; min-width: 200px; max-width: 320px; }
.di-sw svg { position: absolute; left: 10px; top: 50%; transform: translateY(-50%); pointer-events: none; }
.di-fi-s { width: 100%; padding-left: 34px; }
.view-toggle { display: flex; border: 1px solid var(--border-default); border-radius: 10px; overflow: hidden; margin-left: auto; }
.vt-btn {
    padding: 8px 14px;
    background: transparent;
    border: none;
    cursor: pointer;
    color: var(--text-300);
    transition: all .15s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    min-width: 96px;
    font-size: 12.5px;
    font-family: 'DM Sans', var(--font), sans-serif;
    font-weight: 500;
    text-decoration: none;
}
.vt-btn.active { background: #185FA5; color: #fff; }
.vt-btn:not(.active):hover { background: var(--bg-elevated); color: var(--text-100); }

/* ── KANBAN ── */
.kanban-scroll {
    width: 100%;
    padding-bottom: 12px;
}
.kanban-board {
    display: grid;
    gap: 12px;
    width: 100%;
    padding: 2px 0 6px;
    align-items: flex-start;
}

.k-col {
    min-width: 0;
    display: flex;
    flex-direction: column;
    background: var(--bg-elevated);
    border: 1px solid var(--border-subtle);
    border-radius: 12px;
    overflow: hidden;
}
.k-col-head {
    display: flex; align-items: center; justify-content: space-between;
    padding: 12px 14px; border-bottom: 1px solid var(--border-subtle);
}
.k-col-head-l  { display: flex; align-items: center; gap: 8px; }
.k-col-title   { font-size: 13px; font-weight: 600; }
.k-col-count   { font-size: 11px; font-family: 'DM Mono', monospace; padding: 2px 7px; border-radius: 10px; font-weight: 600; color: #fff; }
.k-col-amt     { font-size: 11.5px; font-family: 'DM Mono', monospace; font-weight: 500; }

.k-drop-zone {
    flex: 1; min-height: 200px; padding: 10px;
    display: flex; flex-direction: column; gap: 8px; transition: background .2s;
}
.k-drop-zone.drag-over {
    background: rgba(55,138,221,.06);
    outline: 2px dashed var(--accent);
    outline-offset: -6px; border-radius: 6px;
}

/* Deal Card */
.deal-card {
    background: var(--bg-surface);
    border: 1px solid var(--border-default);
    border-radius: 10px;
    padding: 13px 14px;
    cursor: grab;
    transition: border-color .15s, box-shadow .15s, opacity .2s, transform .15s;
    display: block;
    user-select: none;
    position: relative;
    border-left: 3px solid transparent;
    word-break: break-word;
}
.deal-card:hover { border-color: var(--border-strong); box-shadow: 0 2px 12px rgba(0,0,0,.08); }
.deal-card.dragging { opacity: .35; cursor: grabbing; transform: scale(.97); }

.dc-top     { display: flex; align-items: flex-start; justify-content: space-between; gap: 6px; margin-bottom: 6px; }
.dc-title   { font-size: 13px; font-weight: 600; color: var(--text-100); line-height: 1.35; flex: 1; }
.dc-grip    {
    width: 16px; height: 20px; flex-shrink: 0; opacity: 0; transition: opacity .15s;
    display: flex; align-items: center; justify-content: center; color: var(--text-400); cursor: grab;
}
.deal-card:hover .dc-grip { opacity: 1; }
.dc-contact { display: flex; align-items: center; gap: 4px; font-size: 11.5px; color: var(--text-300); margin-bottom: 10px; }
.dc-value   { font-size: 18px; font-weight: 600; color: var(--text-100); font-family: 'DM Mono', monospace; letter-spacing: -.5px; }
.dc-prob-wrap { margin: 8px 0 0; }
.dc-prob-track { height: 3px; border-radius: 2px; background: var(--border-subtle); overflow: hidden; margin-bottom: 3px; }
.dc-prob-fill  { height: 100%; border-radius: 2px; }
.dc-prob-lbl   { font-size: 10.5px; color: var(--text-400); }
.dc-sep     { height: 1px; background: var(--border-subtle); margin: 10px 0; }
.dc-foot    { display: flex; align-items: center; justify-content: space-between; }
.dc-cnts    { display: flex; gap: 8px; }
.dc-cnt     { display: inline-flex; align-items: center; gap: 3px; font-size: 11px; color: var(--text-400); }
.dc-right   { display: flex; align-items: center; gap: 5px; }
.dc-date    { font-size: 11px; color: var(--text-400); font-family: 'DM Mono', monospace; }
.dc-av      { width: 22px; height: 22px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 9px; font-weight: 700; }
.dc-view-btn {
    width: 22px; height: 22px; border-radius: 5px; background: var(--bg-elevated);
    display: flex; align-items: center; justify-content: center;
    color: var(--text-400); text-decoration: none; transition: all .15s; flex-shrink: 0;
}
.dc-view-btn:hover { background: var(--accent); color: #fff; }

.k-empty-col {
    text-align: center; padding: 24px 12px;
    font-size: 12px; color: var(--text-400); line-height: 1.5;
}

.k-drop-hint {
    display: none; align-items: center; justify-content: center;
    height: 60px; border: 2px dashed var(--accent);
    border-radius: 10px; font-size: 12px; color: var(--accent); font-weight: 500;
}
.k-drop-zone.drag-over .k-drop-hint { display: flex; }
.k-drop-zone.drag-over .k-empty-col { display: none; }

.k-add-btn {
    display: flex; align-items: center; justify-content: center; gap: 5px;
    margin: 0 10px 10px; padding: 8px; background: transparent;
    border: 1px dashed var(--border-default); border-radius: 8px;
    font-size: 12px; color: var(--text-400); cursor: pointer;
    font-family: 'DM Sans', var(--font), sans-serif; transition: all .15s; text-decoration: none;
}
.k-add-btn:hover { border-color: var(--accent); color: var(--accent); background: rgba(55,138,221,.05); }

/* Toast */
.move-toast {
    position: fixed; bottom: 24px; left: 50%; transform: translateX(-50%) translateY(80px);
    background: #185FA5; color: #fff; padding: 10px 20px; border-radius: 10px;
    font-size: 13px; font-weight: 500; display: flex; align-items: center; gap: 8px;
    box-shadow: 0 4px 24px rgba(0,0,0,.2); transition: transform .35s cubic-bezier(.34,1.56,.64,1), opacity .3s;
    z-index: 9999; opacity: 0; pointer-events: none;
}
.move-toast.show { transform: translateX(-50%) translateY(0); opacity: 1; }

/* ── LIST VIEW ── */
.list-wrap { background: var(--bg-surface); border: 1px solid var(--border-default); border-radius: 12px; overflow: hidden; }
.list-head { display: flex; align-items: center; justify-content: space-between; padding: 12px 18px; border-bottom: 1px solid var(--border-subtle); background: var(--bg-elevated); }
.list-count { font-size: 12px; color: var(--text-300); }
.list-count strong { color: var(--text-100); font-weight: 600; }
.di-table { width: 100%; border-collapse: collapse; table-layout: fixed; min-width: 820px; }
.di-table thead tr { background: var(--bg-elevated); }
.di-table th { padding: 9px 14px; text-align: left; font-size: 11px; font-weight: 600; color: var(--text-300); text-transform: uppercase; letter-spacing: .5px; border-bottom: 1px solid var(--border-subtle); white-space: nowrap; }
.di-table th a { color: inherit; text-decoration: none; display: inline-flex; align-items: center; gap: 3px; }
.di-table th a:hover { color: var(--text-100); }
.di-table td { padding: 11px 14px; font-size: 13px; color: var(--text-100); border-bottom: 1px solid var(--border-subtle); vertical-align: middle; }
.di-table tr:last-child td  { border-bottom: none; }
.di-table tbody tr:hover td { background: var(--bg-elevated); }
.st-badge { display: inline-flex; align-items: center; gap: 4px; padding: 3px 9px; border-radius: 20px; font-size: 11px; font-weight: 600; white-space: nowrap; }
.st-badge::before { content: ''; width: 5px; height: 5px; border-radius: 50%; background: currentColor; display: inline-block; }
.lp-wrap  { display: flex; align-items: center; gap: 7px; }
.lp-track { flex: 1; height: 4px; background: var(--border-subtle); border-radius: 2px; overflow: hidden; }
.lp-fill  { height: 100%; border-radius: 2px; }
.row-actions { display: flex; align-items: center; gap: 3px; opacity: 0; transition: opacity .15s; }
.di-table tbody tr:hover .row-actions { opacity: 1; }
.act-btn { width: 28px; height: 28px; display: flex; align-items: center; justify-content: center; border-radius: 6px; border: 1px solid var(--border-subtle); background: transparent; cursor: pointer; color: var(--text-300); text-decoration: none; transition: all .15s; }
.act-btn:hover     { background: var(--bg-elevated); color: var(--text-100); border-color: var(--border-default); }
.act-btn.del:hover { background: #FCEBEB; border-color: #F09595; color: #A32D2D; }
.pag-wrap { display: flex; align-items: center; justify-content: space-between; padding: 13px 18px; border-top: 1px solid var(--border-subtle); background: var(--bg-elevated); flex-wrap: wrap; gap: 8px; }
.pag-info { font-size: 12px; color: var(--text-300); }
.pag-info strong { color: var(--text-100); font-weight: 600; }
.pag-btns { display: flex; gap: 4px; }
.pg-btn { min-width: 32px; height: 32px; padding: 0 9px; display: inline-flex; align-items: center; justify-content: center; gap: 4px; border-radius: 7px; border: 1px solid var(--border-default); background: var(--bg-surface); font-size: 13px; font-weight: 500; cursor: pointer; color: var(--text-200); text-decoration: none; transition: all .15s; font-family: 'DM Sans', var(--font), sans-serif; }
.pg-btn:hover    { background: var(--bg-elevated); color: var(--text-100); }
.pg-btn.active   { background: #185FA5; border-color: #185FA5; color: #fff; }
.pg-btn.disabled { opacity: .35; pointer-events: none; }
.pg-btn.dots     { border: none; background: transparent; pointer-events: none; color: var(--text-300); }
.di-empty { text-align: center; padding: 56px 24px; }
.di-empty-icon { width: 46px; height: 46px; border-radius: 12px; background: var(--bg-elevated); display: flex; align-items: center; justify-content: center; margin: 0 auto 12px; }
.di-empty-title { font-size: 14px; font-weight: 600; color: var(--text-100); margin-bottom: 5px; }
.di-empty-sub   { font-size: 13px; color: var(--text-300); margin-bottom: 16px; }
</style>
@endpush

@section('content')
@php
$dealConfig  = config('deal_fields');
$cfgStages   = $dealConfig['stages'];
$listColumns = $dealConfig['list_columns'];
$currentView  = $view;
$currentStage = request('stage', '');
$sortCol      = request('sort', 'created_at');
$sortDir      = request('dir', 'desc');
$sortUrl = fn(string $col) => route('tenant.deals.index', array_merge(
    request()->query(),
    ['sort'=>$col,'dir'=>($sortCol===$col && $sortDir==='asc')?'desc':'asc','view'=>'list']
));
$avColors = [
    ['#E6F1FB','#185FA5'],['#E1F5EE','#0F6E56'],
    ['#FAEEDA','#854F0B'],['#EEEDFE','#3C3489'],
];
$probColor = function(int $p): string {
    if($p >= 60) return '#1D9E75';
    if($p >= 30) return '#EF9F27';
    return '#378ADD';
};
$initials = fn(string $name): string =>
    substr(collect(explode(' ',$name))->map(fn($p)=>strtoupper($p[0]??''))->join(''),0,2);
$allCount = $stageSummary->sum('count');
$allTotal = $stageSummary->sum('total');
@endphp

<div class="di">

<div class="page-head">
    <div>
        <div class="page-title">Deals</div>
        <div style="font-size:12px;color:var(--text-300);margin-top:2px">Track and manage your sales pipeline</div>
    </div>
    <div style="display:flex;gap:8px">
        <button class="btn btn-secondary" onclick="exportDeals()">
            <i class="ti ti-download" style="font-size:14px"></i> Export
        </button>
        <a href="{{ route('tenant.deals.create') }}" class="btn btn-primary">
            <i class="ti ti-plus" style="font-size:14px"></i> Add Deal
        </a>
    </div>
</div>

@if(session('success'))
<div style="display:flex;align-items:center;gap:10px;padding:11px 15px;background:#E1F5EE;border:1px solid #9FE1CB;border-radius:8px;margin-bottom:14px;font-size:13px;color:#0F6E56;font-weight:500">
    <i class="ti ti-circle-check" style="font-size:16px"></i> {{ session('success') }}
</div>
@endif

{{-- Summary --}}
<div class="di-summary">
    <a href="{{ route('tenant.deals.index', array_merge(request()->except(['stage','page']),['view'=>$currentView])) }}"
       class="di-sum {{ $currentStage==='' ? 'active':'' }}">
        <div class="di-sum-val">{{ $allCount }}</div>
        <div class="di-sum-lbl">All Deals</div>
        <div class="di-sum-amt">₹{{ number_format($allTotal/100000,1) }}L</div>
        <div class="di-sum-bar" style="width:100%;background:#378ADD"></div>
    </a>
    @foreach($cfgStages as $slug => $stage)
    @php $ss=$stageSummary->get($slug); $sc=$ss?->count??0; $sv=$ss?->total??0; $pct=$allCount>0?round(($sc/$allCount)*100):0; @endphp
    <a href="{{ route('tenant.deals.index', array_merge(request()->except(['stage','page']),['stage'=>$slug,'view'=>$currentView])) }}"
       class="di-sum {{ $currentStage===$slug ? 'active':'' }}">
        <div class="di-sum-val">{{ $sc }}</div>
        <div class="di-sum-lbl">{{ $stage['label'] }}</div>
        <div class="di-sum-amt">₹{{ number_format($sv/100000,1) }}L</div>
        <div class="di-sum-bar" style="width:{{ $pct }}%;background:{{ $stage['color'] }}"></div>
    </a>
    @endforeach
</div>

{{-- Toolbar --}}
<form method="GET" action="{{ route('tenant.deals.index') }}" id="filterForm">
    <input type="hidden" name="view" value="{{ $currentView }}">
    @if(request('sort'))  <input type="hidden" name="sort"  value="{{ request('sort') }}"> @endif
    @if(request('dir'))   <input type="hidden" name="dir"   value="{{ request('dir') }}">  @endif
    @if(request('stage')) <input type="hidden" name="stage" value="{{ request('stage') }}">@endif
    <div class="di-toolbar">
        <div class="di-sw">
            <svg width="14" height="14" fill="none" stroke="var(--text-300)" stroke-width="2" viewBox="0 0 24 24">
                <circle cx="11" cy="11" r="8"/>
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35"/>
            </svg>
            <input type="text" name="search" class="di-fi di-fi-s"
                   placeholder="Search deals, contacts..." value="{{ request('search') }}" autocomplete="off"/>
        </div>
        <select name="assigned_to" class="di-fi" style="min-width:130px" onchange="this.form.submit()">
            <option value="">All Assignees</option>
            @foreach($staffList as $staff)
            <option value="{{ $staff->id }}" {{ request('assigned_to')==$staff->id?'selected':'' }}>{{ $staff->name }}</option>
            @endforeach
        </select>
        <input type="number" name="value_min" class="di-fi" placeholder="Min ₹" style="width:88px" value="{{ request('value_min') }}"/>
        <input type="number" name="value_max" class="di-fi" placeholder="Max ₹" style="width:88px" value="{{ request('value_max') }}"/>
        <button type="submit" class="btn btn-secondary">
            <i class="ti ti-filter" style="font-size:13px"></i> Filter
        </button>
        @if(request()->hasAny(['search','assigned_to','value_min','value_max']))
        <a href="{{ route('tenant.deals.index',array_merge(request()->only(['view','stage','sort','dir']))) }}" class="btn btn-secondary">
            <i class="ti ti-x" style="font-size:13px"></i> Clear
        </a>
        @endif
        <div class="view-toggle">
            <a href="{{ route('tenant.deals.index',array_merge(request()->except('view'),['view'=>'kanban'])) }}"
               class="vt-btn {{ $currentView==='kanban'?'active':'' }}">
                <i class="ti ti-layout-columns" style="font-size:13px"></i> Kanban
            </a>
            <a href="{{ route('tenant.deals.index',array_merge(request()->except('view'),['view'=>'list'])) }}"
               class="vt-btn {{ $currentView==='list'?'active':'' }}">
                <i class="ti ti-list" style="font-size:13px"></i> List
            </a>
        </div>
    </div>
</form>

{{-- ═══ KANBAN ═══ --}}
@if($currentView === 'kanban')
<div class="kanban-scroll">
<div class="kanban-board" id="kanbanBoard" style="grid-template-columns: repeat({{ count($cfgStages) }}, 1fr)">
@foreach($cfgStages as $slug => $stage)
@php $colDeals=$kanbanDeals->get($slug,collect()); $colTotal=$colDeals->sum('value'); @endphp
<div class="k-col" data-stage="{{ $slug }}">

    {{-- Header with top color bar --}}
    <div class="k-col-head" style="border-top:3px solid {{ $stage['color'] }}">
        <div class="k-col-head-l">
            <span class="k-col-title" style="color:{{ $stage['text_color'] }}">{{ $stage['label'] }}</span>
            <span class="k-col-count" style="background:{{ $stage['color'] }}">{{ $colDeals->count() }}</span>
        </div>
        <span class="k-col-amt" style="color:{{ $stage['text_color'] }}">
            ₹{{ number_format($colTotal/100000,1) }}L
        </span>
    </div>

    {{-- Drop Zone --}}
    <div class="k-drop-zone"
         id="zone_{{ $slug }}"
         data-stage="{{ $slug }}"
         ondragover="handleDragOver(event)"
         ondragenter="handleDragEnter(event)"
         ondragleave="handleDragLeave(event)"
         ondrop="handleDrop(event)">

        @if($colDeals->isEmpty())
        <div class="k-empty-col">
            <i class="ti ti-inbox" style="font-size:20px;color:var(--text-400);display:block;margin-bottom:5px"></i>
            No deals in {{ $stage['label'] }}
        </div>
        @endif

        <div class="k-drop-hint">
            <i class="ti ti-arrow-down" style="font-size:14px;margin-right:5px"></i>
            Drop here
        </div>

        @foreach($colDeals as $i => $deal)
        @php
            [$avBg,$avTx] = $avColors[$i % 4];
            $prob   = (int)($deal->probability ?? $stage['probability']);
            $pColor = $probColor($prob);
            $assInit = $deal->assignedTo ? $initials($deal->assignedTo->name) : '';
        @endphp

        <div class="deal-card"
             draggable="true"
             id="card_{{ $deal->id }}"
             data-deal-id="{{ $deal->id }}"
             data-stage="{{ $slug }}"
             data-value="{{ $deal->value }}"
             data-title="{{ addslashes($deal->title) }}"
             style="border-left-color:{{ $stage['color'] }}"
             ondragstart="handleDragStart(event)"
             ondragend="handleDragEnd(event)">

            <div class="dc-top">
                <div class="dc-title">{{ $deal->title }}</div>
                <div class="dc-grip">
                    <svg width="10" height="16" viewBox="0 0 10 16" fill="currentColor">
                        <circle cx="2" cy="2"  r="1.5"/><circle cx="8" cy="2"  r="1.5"/>
                        <circle cx="2" cy="8"  r="1.5"/><circle cx="8" cy="8"  r="1.5"/>
                        <circle cx="2" cy="14" r="1.5"/><circle cx="8" cy="14" r="1.5"/>
                    </svg>
                </div>
            </div>

            @if($deal->contact)
            <div class="dc-contact">
                <i class="ti ti-user" style="font-size:11px"></i>
                {{ $deal->contact->name }}
                @if($deal->contact->company)
                <span style="opacity:.5">·</span>
                {{ \Illuminate\Support\Str::limit($deal->contact->company,16) }}
                @endif
            </div>
            @endif

            <div class="dc-value">₹{{ number_format($deal->value) }}</div>

            <div class="dc-prob-wrap">
                <div class="dc-prob-track">
                    <div class="dc-prob-fill" style="width:{{ $prob }}%;background:{{ $pColor }}"></div>
                </div>
                <div class="dc-prob-lbl">{{ $prob }}% probability</div>
            </div>

            <div class="dc-sep"></div>

            <div class="dc-foot">
                <div class="dc-cnts">
                    @if($deal->tasks_count)
                    <span class="dc-cnt">
                        <i class="ti ti-checkbox" style="font-size:11px"></i> {{ $deal->tasks_count }}
                    </span>
                    @endif
                    @if($deal->followups_count)
                    <span class="dc-cnt">
                        <i class="ti ti-calendar" style="font-size:11px"></i> {{ $deal->followups_count }}
                    </span>
                    @endif
                </div>
                <div class="dc-right">
                    @if($deal->expected_close_date)
                    <span class="dc-date">{{ \Carbon\Carbon::parse($deal->expected_close_date)->format('M d') }}</span>
                    @endif
                    @if($deal->assignedTo)
                    <div class="dc-av" style="background:{{ $avBg }};color:{{ $avTx }}">{{ $assInit }}</div>
                    @endif
                    <a href="{{ route('tenant.deals.show',$deal->id) }}" class="dc-view-btn"
                       onclick="event.stopPropagation()" title="View">
                        <i class="ti ti-arrow-right" style="font-size:11px"></i>
                    </a>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <a href="{{ route('tenant.deals.create',['stage'=>$slug]) }}" class="k-add-btn">
        <i class="ti ti-plus" style="font-size:12px"></i> Add Deal
    </a>
</div>
@endforeach
</div>
</div>

<div class="move-toast" id="moveToast">
    <i class="ti ti-check" style="font-size:15px"></i>
    <span id="moveToastText">Deal moved!</span>
</div>

{{-- ═══ LIST VIEW ═══ --}}
@else
<div class="list-wrap">
    <div class="list-head">
        <div class="list-count">
            @if($deals->total())
            Showing <strong>{{ $deals->firstItem() }}–{{ $deals->lastItem() }}</strong>
            of <strong>{{ number_format($deals->total()) }}</strong> deals
            @else <strong>0</strong> deals found @endif
        </div>
        <div style="display:flex;align-items:center;gap:6px">
            <span style="font-size:12px;color:var(--text-300)">Sort</span>
            <form method="GET" style="display:inline">
                @foreach(request()->except(['sort','dir']) as $k => $v)
                <input type="hidden" name="{{ $k }}" value="{{ $v }}">
                @endforeach
                <select name="sort" class="di-fi" style="height:30px;padding:4px 9px;font-size:12px" onchange="this.form.submit()">
                    <option value="created_at"          {{ $sortCol==='created_at'?'selected':'' }}>Newest first</option>
                    <option value="value"               {{ $sortCol==='value'?'selected':'' }}>Value: High–Low</option>
                    <option value="expected_close_date" {{ $sortCol==='expected_close_date'?'selected':'' }}>Close Date</option>
                    <option value="title"               {{ $sortCol==='title'?'selected':'' }}>Title A–Z</option>
                    <option value="stage"               {{ $sortCol==='stage'?'selected':'' }}>Stage</option>
                </select>
                <input type="hidden" name="dir" value="{{ $sortDir==='asc'?'desc':'asc' }}">
            </form>
        </div>
    </div>

    @if($deals->isEmpty())
    <div class="di-empty">
        <div class="di-empty-icon"><i class="ti ti-currency-rupee" style="font-size:22px;color:var(--text-300)"></i></div>
        <div class="di-empty-title">No deals found</div>
        <div class="di-empty-sub">
            @if(request()->hasAny(['search','assigned_to','value_min','value_max','stage']))
            Try adjusting your filters or <a href="{{ route('tenant.deals.index',['view'=>'list']) }}" style="color:var(--accent)">clear all</a>
            @else Start by creating your first deal @endif
        </div>
        <a href="{{ route('tenant.deals.create') }}" class="btn btn-primary">Add Deal</a>
    </div>
    @else
    <div style="overflow-x:auto">
    <table class="di-table">
        <thead><tr>
            <th style="width:36px;padding:9px 8px 9px 16px">
                <input type="checkbox" id="selectAll" style="width:14px;height:14px;cursor:pointer"/>
            </th>
            @foreach($listColumns as $col)
            @php $cf=collect($dealConfig['fields'])->firstWhere('key',$col['key']); $sortable=$cf['sortable']??false; @endphp
            <th style="width:{{ $col['width'] }}">
                @if($sortable)
                <a href="{{ $sortUrl($col['key']) }}" style="{{ $sortCol===$col['key']?'color:var(--accent)':'' }}">
                    {{ $col['label'] }}
                    <svg width="9" height="9" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="opacity:{{ $sortCol===$col['key']?'1':'0.4' }}">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M7 16V4m0 0L3 8m4-4l4 4M17 8v12m0 0l4-4m-4 4l-4-4"/>
                    </svg>
                    @if($sortCol===$col['key'])<span style="font-size:10px">{{ $sortDir==='asc'?'↑':'↓' }}</span>@endif
                </a>
                @else {{ $col['label'] }} @endif
            </th>
            @endforeach
            <th style="width:80px"></th>
        </tr></thead>
        <tbody>
        @foreach($deals as $i => $deal)
        @php
            [$avBg,$avTx]=$avColors[$i%4];
            $stg=$cfgStages[$deal->stage]??$cfgStages['new'];
            $prob=(int)($deal->probability??$stg['probability']);
            $pColor=$probColor($prob);
            $assInit=$deal->assignedTo?$initials($deal->assignedTo->name):'';
        @endphp
        <tr>
            <td style="padding:11px 8px 11px 16px">
                <input type="checkbox" class="row-check" value="{{ $deal->id }}" style="width:14px;height:14px;cursor:pointer"/>
            </td>
            @foreach($listColumns as $col)
            <td>
                @switch($col['key'])
                @case('title')
                    <div style="font-weight:600;font-size:13px">
                        <a href="{{ route('tenant.deals.show',$deal->id) }}" style="color:var(--text-100);text-decoration:none">{{ $deal->title }}</a>
                    </div>
                    @if($deal->tasks_count || $deal->followups_count)
                    <div style="display:flex;gap:8px;margin-top:3px">
                        @if($deal->tasks_count)<span style="font-size:11px;color:var(--text-400)"><i class="ti ti-checkbox" style="font-size:11px"></i> {{ $deal->tasks_count }}</span>@endif
                        @if($deal->followups_count)<span style="font-size:11px;color:var(--text-400)"><i class="ti ti-calendar" style="font-size:11px"></i> {{ $deal->followups_count }}</span>@endif
                    </div>
                    @endif
                @break
                @case('contact_id')
                    @if($deal->contact)
                    <div style="display:flex;align-items:center;gap:7px">
                        <div style="width:24px;height:24px;border-radius:50%;background:{{ $avBg }};color:{{ $avTx }};display:flex;align-items:center;justify-content:center;font-size:9px;font-weight:700;flex-shrink:0">{{ strtoupper(substr($deal->contact->name,0,1)) }}</div>
                        <div>
                            <div style="font-size:12.5px">{{ $deal->contact->name }}</div>
                            @if($deal->contact->company)<div style="font-size:11px;color:var(--text-400)">{{ $deal->contact->company }}</div>@endif
                        </div>
                    </div>
                    @else <span style="font-size:12px;color:var(--text-400);font-style:italic">—</span> @endif
                @break
                @case('stage')
                    <span class="st-badge" style="background:{{ $stg['bg'] }};color:{{ $stg['text_color'] }};border:1px solid {{ $stg['color'] }}30">{{ $stg['label'] }}</span>
                @break
                @case('value')
                    <span style="font-family:'DM Mono',monospace;font-size:13px;font-weight:600">₹{{ number_format($deal->value) }}</span>
                @break
                @case('probability')
                    <div class="lp-wrap">
                        <div class="lp-track"><div class="lp-fill" style="width:{{ $prob }}%;background:{{ $pColor }}"></div></div>
                        <span style="font-family:'DM Mono',monospace;font-size:11.5px;color:var(--text-300);min-width:32px">{{ $prob }}%</span>
                    </div>
                @break
                @case('expected_close_date')
                    @if($deal->expected_close_date)
                    @php $cd=\Carbon\Carbon::parse($deal->expected_close_date); $ov=$cd->isPast()&&!in_array($deal->stage,['won','lost']); @endphp
                    <span style="font-family:'DM Mono',monospace;font-size:12px;color:{{ $ov?'#E24B4A':'var(--text-300)' }}">
                        @if($ov)<i class="ti ti-alert-triangle" style="font-size:12px"></i> @endif{{ $cd->format('M d, Y') }}
                    </span>
                    @else <span style="font-size:12px;color:var(--text-400)">—</span> @endif
                @break
                @case('assigned_to')
                    @if($deal->assignedTo)
                    <div style="display:flex;align-items:center;gap:7px">
                        <div style="width:24px;height:24px;border-radius:50%;background:{{ $avBg }};color:{{ $avTx }};display:flex;align-items:center;justify-content:center;font-size:9px;font-weight:700;flex-shrink:0">{{ $assInit }}</div>
                        <span style="font-size:12.5px;color:var(--text-200)">{{ \Illuminate\Support\Str::limit($deal->assignedTo->name,14) }}</span>
                    </div>
                    @else <span style="font-size:12px;color:var(--text-400);font-style:italic">Unassigned</span> @endif
                @break
                @default <span style="font-size:13px;color:var(--text-200)">{{ $deal->{$col['key']}??'—' }}</span>
                @endswitch
            </td>
            @endforeach
            <td>
                <div class="row-actions">
                    <a href="{{ route('tenant.deals.show',$deal->id) }}" class="act-btn" title="View"><i class="ti ti-eye" style="font-size:13px"></i></a>
                    <a href="{{ route('tenant.deals.edit',$deal->id) }}" class="act-btn" title="Edit"><i class="ti ti-edit" style="font-size:13px"></i></a>
                    <form method="POST" action="{{ route('tenant.deals.destroy',$deal->id) }}" style="display:inline" onsubmit="return confirm('Delete this deal?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="act-btn del" title="Delete"><i class="ti ti-trash" style="font-size:13px"></i></button>
                    </form>
                </div>
            </td>
        </tr>
        @endforeach
        </tbody>
    </table>
    </div>
    @if($deals->hasPages())
    <div class="pag-wrap">
        <span class="pag-info">Showing <strong>{{ $deals->firstItem() }}–{{ $deals->lastItem() }}</strong> of <strong>{{ number_format($deals->total()) }}</strong> deals</span>
        <div class="pag-btns">
            @if($deals->onFirstPage()) <span class="pg-btn disabled"><i class="ti ti-chevron-left" style="font-size:12px"></i> Prev</span>
            @else <a href="{{ $deals->previousPageUrl() }}" class="pg-btn"><i class="ti ti-chevron-left" style="font-size:12px"></i> Prev</a> @endif
            @php $cur=$deals->currentPage();$last=$deals->lastPage();$from=max(1,$cur-2);$to=min($last,$cur+2); @endphp
            @if($from>1) <a href="{{ $deals->url(1) }}" class="pg-btn">1</a> @if($from>2)<span class="pg-btn dots">…</span>@endif @endif
            @for($p=$from;$p<=$to;$p++) @if($p===$cur)<span class="pg-btn active">{{ $p }}</span>@else<a href="{{ $deals->url($p) }}" class="pg-btn">{{ $p }}</a>@endif @endfor
            @if($to<$last) @if($to<$last-1)<span class="pg-btn dots">…</span>@endif <a href="{{ $deals->url($last) }}" class="pg-btn">{{ $last }}</a> @endif
            @if($deals->hasMorePages()) <a href="{{ $deals->nextPageUrl() }}" class="pg-btn">Next <i class="ti ti-chevron-right" style="font-size:12px"></i></a>
            @else <span class="pg-btn disabled">Next <i class="ti ti-chevron-right" style="font-size:12px"></i></span> @endif
        </div>
    </div>
    @endif
    @endif
</div>
@endif

</div>
@endsection

@push('scripts')
<script>
(function(){
const CSRF      = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
const STAGE_URL = @json(route('tenant.deals.update_stage', ['id' => 0])).replace('/0', '/');
const STAGES    =  @json(config('deal_fields.stages'));

/* ─── Drag State ─── */
let dragCard   = null;
let dragSource = null;

/* ─── DragStart ─── */
window.handleDragStart = function(e){
    dragCard   = e.currentTarget;
    dragSource = dragCard.dataset.stage;

    e.dataTransfer.effectAllowed = 'move';
    e.dataTransfer.setData('application/x-deal-id', dragCard.dataset.dealId);
    e.dataTransfer.setData('text/plain', dragCard.dataset.dealId);

    setTimeout(() => dragCard.classList.add('dragging'), 0);
};

/* ─── DragEnd ─── */
window.handleDragEnd = function(e){
    if(dragCard) dragCard.classList.remove('dragging');
    document.querySelectorAll('.k-drop-zone').forEach(z => z.classList.remove('drag-over'));
    dragCard = null; dragSource = null;
};

/* ─── DragOver ─── */
window.handleDragOver = function(e){
    e.preventDefault();
    e.dataTransfer.dropEffect = 'move';
};

/* ─── DragEnter ─── */
window.handleDragEnter = function(e){
    e.preventDefault();
    e.currentTarget.classList.add('drag-over');
};

/* ─── DragLeave ─── */
window.handleDragLeave = function(e){
    if(!e.currentTarget.contains(e.relatedTarget)){
        e.currentTarget.classList.remove('drag-over');
    }
};

/* ─── Drop ─── */
window.handleDrop = async function(e){
    e.preventDefault();
    const zone     = e.currentTarget;
    zone.classList.remove('drag-over');

    const dealId   = e.dataTransfer.getData('application/x-deal-id') || e.dataTransfer.getData('text/plain');
    const newStage = zone.dataset.stage;

    if(!dealId || newStage === dragSource) return;

    const card = document.getElementById('card_' + dealId);
    if(!card) return;

    const oldStage = dragSource;

    /* ── Optimistic move ── */
    const oldZone = document.getElementById('zone_' + oldStage);
    if(oldZone) oldZone.removeChild(card);

    /* Insert before the drop-hint div */
    const hint = zone.querySelector('.k-drop-hint');
    zone.insertBefore(card, hint);

    /* Update card accent color */
    const stg = STAGES[newStage];
    if(stg) card.style.borderLeftColor = stg.color;
    card.dataset.stage = newStage;

    /* Hide empty state if was showing */
    const emptyEl = zone.querySelector('.k-empty-col');
    if(emptyEl) emptyEl.style.display = 'none';

    /* Show empty in old zone if now empty */
    const oldEmpty = oldZone?.querySelector('.k-empty-col');
    const oldCards = oldZone?.querySelectorAll('.deal-card');
    if(oldEmpty && oldCards?.length === 0) oldEmpty.style.display = 'block';

    /* Update column counts */
    updateColCount(oldStage);
    updateColCount(newStage);

    /* Toast */
    showToast(`"${card.dataset.title}" moved to ${STAGES[newStage]?.label ?? newStage}`);

    /* ── API call ── */
    try {
        const url  = STAGE_URL + dealId;
        console.log('URL',STAGE_URL);
        const fd   = new FormData();
        fd.append('_token', CSRF);
        fd.append('_method', 'PATCH');
        fd.append('stage', newStage);

        const res = await fetch(url, { method: 'POST', body: fd });
        if(!res.ok) throw new Error('HTTP ' + res.status);

    } catch(err){
        console.error('Stage update failed', err);
        /* Revert on failure */
        if(oldZone) {
            zone.removeChild(card);
            oldZone.appendChild(card);
            card.style.borderLeftColor = STAGES[oldStage]?.color ?? '';
            card.dataset.stage = oldStage;
        }
        showToast('Update failed. Please try again.', true);
    }
};

function updateColCount(stage){
    const zone   = document.getElementById('zone_' + stage);
    const col    = zone?.closest('.k-col');
    if(!col) return;
    const count  = zone.querySelectorAll('.deal-card').length;
    const countEl = col.querySelector('.k-col-count');
    if(countEl) countEl.textContent = count;
}

function showToast(msg, isErr = false){
    const t    = document.getElementById('moveToast');
    const text = document.getElementById('moveToastText');
    if(!t) return;
    t.style.background = isErr ? '#A32D2D' : '#185FA5';
    text.textContent   = msg;
    t.classList.add('show');
    setTimeout(() => t.classList.remove('show'), 3000);
}

/* ─── Select All ─── */
const sa = document.getElementById('selectAll');
if(sa){
    sa.addEventListener('change', function(){
        document.querySelectorAll('.row-check').forEach(cb => cb.checked = this.checked);
    });
    document.querySelectorAll('.row-check').forEach(cb => {
        cb.addEventListener('change', () => {
            const all = document.querySelectorAll('.row-check');
            const chk = document.querySelectorAll('.row-check:checked');
            sa.indeterminate = chk.length > 0 && chk.length < all.length;
            sa.checked = chk.length === all.length;
        });
    });
}

/* ─── Search debounce ─── */
const si = document.querySelector('.di-fi-s');
if(si){ let t; si.addEventListener('input', () => { clearTimeout(t); t = setTimeout(() => document.getElementById('filterForm').submit(), 500); }); }

/* ─── Export ─── */
window.exportDeals = function(){
    const p = new URLSearchParams(window.location.search);
    p.set('export','csv');
    window.location.href = '{{ route("tenant.deals.index") }}?' + p.toString();
};

})();
</script>
@endpush