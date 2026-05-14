@extends('layouts.app')
@section('title', 'Leads')

@push('styles')
<style>
@import url('https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=DM+Mono:wght@400;500&display=swap');

.li-page { font-family: 'DM Sans', var(--font), sans-serif; }

/* ── Stats ── */
.li-stats {
    display: grid;
    grid-template-columns: repeat(6, 1fr);
    gap: 10px;
    margin-bottom: 16px;
}
@media(max-width:900px) { .li-stats { grid-template-columns: repeat(3,1fr); } }
@media(max-width:560px) { .li-stats { grid-template-columns: repeat(2,1fr); } }

.li-stat {
    background: var(--bg-surface);
    border: 1px solid var(--border-default);
    border-radius: 10px;
    padding: 14px 16px;
    cursor: pointer;
    transition: all .15s;
    text-decoration: none;
    display: block;
    position: relative;
    overflow: hidden;
}
.li-stat:hover { border-color: var(--border-hover, var(--accent)); }
.li-stat.active { border-color: var(--accent); background: #E6F1FB; }
.li-stat-num {
    font-size: 22px; font-weight: 600;
    color: var(--text-100); font-family: 'DM Mono', monospace;
    letter-spacing: -1px;
}
.li-stat.active .li-stat-num { color: #185FA5; }
.li-stat-lbl { font-size: 11px; color: var(--text-300); margin-top: 2px; font-weight: 500; }
.li-stat-bar { position: absolute; bottom: 0; left: 0; height: 2.5px; border-radius: 0 2px 0 0; }

/* ── Filters ── */
.li-filters {
    display: flex; align-items: center; gap: 8px;
    flex-wrap: wrap; margin-bottom: 14px;
}
.li-fi {
    padding: 8px 11px;
    background: var(--bg-surface);
    border: 1px solid var(--border-default);
    border-radius: 8px; font-size: 12.5px;
    color: var(--text-100); font-family: 'DM Sans', var(--font), sans-serif;
    outline: none; transition: border-color .15s;
    -webkit-appearance: none; cursor: pointer;
}
.li-fi:focus { border-color: var(--accent); }
.li-search-wrap { position: relative; flex: 1; min-width: 180px; }
.li-search-ico {
    position: absolute; left: 11px; top: 50%;
    transform: translateY(-50%); pointer-events: none;
}
.li-fi-search { width: 100%; padding-left: 34px; }

/* ── Table Card ── */
.li-card {
    background: var(--bg-surface);
    border: 1px solid var(--border-default);
    border-radius: 12px; overflow: hidden;
}
.li-card-head {
    display: flex; align-items: center; justify-content: space-between;
    padding: 14px 18px; border-bottom: 1px solid var(--border-subtle);
}
.li-count { font-size: 12px; color: var(--text-300); }
.li-count strong { color: var(--text-100); font-weight: 600; }

/* ── Table ── */
.li-table-wrap { overflow-x: auto; }
.li-table { width: 100%; border-collapse: collapse; table-layout: fixed; min-width: 780px; }
.li-table thead tr { background: var(--bg-elevated); }
.li-table th {
    padding: 10px 14px; text-align: left;
    font-size: 11px; font-weight: 600;
    color: var(--text-300); text-transform: uppercase;
    letter-spacing: .5px; border-bottom: 1px solid var(--border-subtle);
    white-space: nowrap; user-select: none;
}
.li-table th a {
    color: inherit; text-decoration: none;
    display: inline-flex; align-items: center; gap: 3px;
}
.li-table th a:hover { color: var(--text-100); }
.li-table td {
    padding: 12px 14px; font-size: 13px;
    color: var(--text-100);
    border-bottom: 1px solid var(--border-subtle);
    vertical-align: middle;
}
.li-table tr:last-child td { border-bottom: none; }
.li-table tbody tr:hover td { background: var(--bg-elevated); }

/* ── Lead Cell ── */
.li-lead-name { font-weight: 600; font-size: 13.5px; color: var(--text-100); }
.li-lead-sub  { font-size: 11.5px; color: var(--text-300); margin-top: 1px; }

.li-avatar {
    width: 32px; height: 32px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 11px; font-weight: 600; flex-shrink: 0;
    background: #E6F1FB; color: #185FA5;
}

/* ── Badges ── */
.li-badge {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 3px 9px; border-radius: 20px;
    font-size: 11px; font-weight: 600; white-space: nowrap;
}
.s-new         { background: #E6F1FB; color: #185FA5; }
.s-contacted   { background: #E1F5EE; color: #0F6E56; }
.s-qualified   { background: #EAF3DE; color: #3B6D11; }
.s-proposal    { background: #FAEEDA; color: #854F0B; }
.s-negotiation { background: #EEEDFE; color: #3C3489; }
.s-converted   { background: #E1F5EE; color: #085041; }
.s-lost        { background: #FCEBEB; color: #A32D2D; }
.p-low         { background: #EAF3DE; color: #3B6D11; }
.p-medium      { background: #FAEEDA; color: #854F0B; }
.p-high        { background: #FCEBEB; color: #A32D2D; }

/* ── Action Buttons ── */
.li-actions {
    display: flex; align-items: center; gap: 4px;
    opacity: 0; transition: opacity .15s;
}
.li-table tbody tr:hover .li-actions { opacity: 1; }
.li-act-btn {
    width: 28px; height: 28px;
    display: flex; align-items: center; justify-content: center;
    border-radius: 6px; border: 1px solid var(--border-subtle);
    background: transparent; cursor: pointer; transition: all .15s;
    color: var(--text-300);
}
.li-act-btn:hover { background: var(--bg-elevated); color: var(--text-100); border-color: var(--border-default); }
.li-act-btn.danger:hover { background: #FCEBEB; border-color: #F09595; color: #A32D2D; }

/* ── Empty State ── */
.li-empty { text-align: center; padding: 60px 24px; }
.li-empty-icon {
    width: 48px; height: 48px; border-radius: 12px;
    background: var(--bg-elevated);
    display: flex; align-items: center; justify-content: center;
    margin: 0 auto 14px;
}
.li-empty-title { font-size: 15px; font-weight: 600; color: var(--text-100); margin-bottom: 6px; }
.li-empty-sub   { font-size: 13px; color: var(--text-300); }

/* ── Pagination ── */
.li-pag {
    display: flex; align-items: center; justify-content: space-between;
    padding: 14px 18px;
    border-top: 1px solid var(--border-subtle);
    background: var(--bg-elevated);
    flex-wrap: wrap; gap: 10px;
}
.li-pag-info { font-size: 12px; color: var(--text-300); }
.li-pag-btns { display: flex; gap: 4px; align-items: center; }
.li-pg-btn {
    min-width: 32px; height: 32px; padding: 0 8px;
    display: flex; align-items: center; justify-content: center; gap: 4px;
    border-radius: 7px; border: 1px solid var(--border-default);
    background: var(--bg-surface); font-size: 13px; font-weight: 500;
    cursor: pointer; color: var(--text-200);
    font-family: 'DM Sans', var(--font), sans-serif;
    transition: all .15s; text-decoration: none;
}
.li-pg-btn:hover { background: var(--bg-elevated); color: var(--text-100); }
.li-pg-btn.active { background: #185FA5; border-color: #185FA5; color: #fff; }
.li-pg-btn[aria-disabled="true"] { opacity: .35; pointer-events: none; }
.li-pg-dot { border: none; background: transparent; }

/* ── Sort Arrow ── */
.sort-asc  .sort-icon { transform: rotate(180deg); }
.sort-icon { display: inline-block; transition: transform .15s; }
</style>
@endpush

@section('content')

@php
/* ── Helper: sort chevron ── */
$sortDir = request('dir', 'desc');
$sortCol = request('sort', 'created_at');
$nextDir = fn(string $col) => ($sortCol === $col && $sortDir === 'asc') ? 'desc' : 'asc';
$isActive = fn(string $col) => $sortCol === $col;

/* ── Badge helpers ── */
$statusClass = [
    'new'=>'s-new','contacted'=>'s-contacted','qualified'=>'s-qualified',
    'proposal'=>'s-proposal','negotiation'=>'s-negotiation',
    'converted'=>'s-converted','lost'=>'s-lost',
];
$priorityClass = ['low'=>'p-low','medium'=>'p-medium','high'=>'p-high'];
$avatarColors  = [
    ['#E6F1FB','#185FA5'],['#E1F5EE','#0F6E56'],
    ['#FAEEDA','#854F0B'],['#EEEDFE','#3C3489'],
];
$statBars = [
    'all'=>['100%','#378ADD'],'new'=>['31%','#378ADD'],
    'contacted'=>['23%','#1D9E75'],'qualified'=>['17%','#639922'],
    'converted'=>['15%','#085041'],'lost'=>['15%','#E24B4A'],
];
@endphp

<div class="li-page">

    {{-- Page Header --}}
    <div class="page-head">
        <div>
            <div class="page-title">Leads</div>
            <div style="font-size:12px;color:var(--text-300);margin-top:2px">
                Manage and track all your sales leads
            </div>
        </div>
        <a href="{{ route('tenant.leads.create') }}" class="btn btn-primary">
            <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
            Add Lead
        </a>
    </div>

    {{-- Flash Messages --}}
    @if(session('success'))
    <div style="display:flex;align-items:center;gap:10px;padding:12px 16px;background:#E1F5EE;border:1px solid #9FE1CB;border-radius:8px;margin-bottom:14px;font-size:13px;color:#0F6E56;font-weight:500">
        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        {{ session('success') }}
    </div>
    @endif

    {{-- Status Stats --}}
    <div class="li-stats">
        @php
        $statItems = [
            ['key'=>'',           'label'=>'All Leads',  'count'=>$counts['all'],       'bar'=>$statBars['all']],
            ['key'=>'new',        'label'=>'New',        'count'=>$counts['new'],        'bar'=>$statBars['new']],
            ['key'=>'contacted',  'label'=>'Contacted',  'count'=>$counts['contacted'],  'bar'=>$statBars['contacted']],
            ['key'=>'qualified',  'label'=>'Qualified',  'count'=>$counts['qualified'],  'bar'=>$statBars['qualified']],
            ['key'=>'converted',  'label'=>'Converted',  'count'=>$counts['converted'],  'bar'=>$statBars['converted']],
            ['key'=>'lost',       'label'=>'Lost',       'count'=>$counts['lost'],       'bar'=>$statBars['lost']],
        ];
        $currentStatus = request('status', '');
        @endphp
        @foreach($statItems as $stat)
        <a href="{{ route('tenant.leads.index', array_merge(request()->except(['status','page']), $stat['key'] ? ['status'=>$stat['key']] : [])) }}"
           class="li-stat {{ $currentStatus === $stat['key'] ? 'active' : '' }}">
            <div class="li-stat-num">{{ $stat['count'] }}</div>
            <div class="li-stat-lbl">{{ $stat['label'] }}</div>
            <div class="li-stat-bar" style="width:{{ $stat['bar'][0] }};background:{{ $stat['bar'][1] }}"></div>
        </a>
        @endforeach
    </div>

    {{-- Filters --}}
    <form method="GET" action="{{ route('tenant.leads.index') }}" id="filterForm">
        <div class="li-filters">
            <div class="li-search-wrap">
                <span class="li-search-ico">
                    <svg width="14" height="14" fill="none" stroke="var(--text-300)" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35"/></svg>
                </span>
                <input type="text" name="search" class="li-fi li-fi-search"
                       placeholder="Search name, phone, company..."
                       value="{{ request('search') }}"
                       autocomplete="off" />
            </div>

            <select name="source" class="li-fi" style="min-width:130px" onchange="this.form.submit()">
                <option value="">All Sources</option>
                @foreach($sources as $val => $label)
                <option value="{{ $val }}" {{ request('source') === $val ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>

            <select name="priority" class="li-fi" style="min-width:120px" onchange="this.form.submit()">
                <option value="">All Priorities</option>
                @foreach($priorities as $val => $label)
                <option value="{{ $val }}" {{ request('priority') === $val ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>

            <select name="assigned_to" class="li-fi" style="min-width:130px" onchange="this.form.submit()">
                <option value="">All Assignees</option>
                @foreach($staffList as $staff)
                <option value="{{ $staff->id }}" {{ request('assigned_to') == $staff->id ? 'selected' : '' }}>
                    {{ $staff->name }}
                </option>
                @endforeach
            </select>

            <input type="date" name="date_from" class="li-fi"
                   value="{{ request('date_from') }}" title="From date"
                   onchange="this.form.submit()" />

            <input type="date" name="date_to" class="li-fi"
                   value="{{ request('date_to') }}" title="To date"
                   onchange="this.form.submit()" />

            {{-- Preserve status & sort --}}
            @if(request('status'))  <input type="hidden" name="status"  value="{{ request('status') }}">  @endif
            @if(request('sort'))    <input type="hidden" name="sort"    value="{{ request('sort') }}">    @endif
            @if(request('dir'))     <input type="hidden" name="dir"     value="{{ request('dir') }}">     @endif

            <button type="submit" class="btn btn-secondary">
                <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35"/></svg>
                Search
            </button>

            @if(request()->hasAny(['search','source','priority','assigned_to','date_from','date_to','status']))
            <a href="{{ route('tenant.leads.index') }}" class="btn btn-secondary">
                <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                Reset
            </a>
            @endif
        </div>
    </form>

    {{-- Table Card --}}
    <div class="li-card">
        <div class="li-card-head">
            <div class="li-count">
                Showing
                <strong>{{ $leads->firstItem() ?? 0 }}–{{ $leads->lastItem() ?? 0 }}</strong>
                of <strong>{{ $leads->total() }}</strong> leads
            </div>
            <div style="display:flex;align-items:center;gap:6px">
                <span style="font-size:12px;color:var(--text-300)">Sort by</span>
                <form method="GET" style="display:inline">
                    @foreach(request()->except(['sort','dir']) as $k => $v)
                    <input type="hidden" name="{{ $k }}" value="{{ $v }}">
                    @endforeach
                    <select name="sort" class="li-fi" style="padding:6px 10px;font-size:12px" onchange="this.form.submit()">
                        <option value="created_at" {{ $sortCol==='created_at' ? 'selected':'' }}>Newest first</option>
                        <option value="name"       {{ $sortCol==='name'       ? 'selected':'' }}>Name A–Z</option>
                        <option value="lead_value" {{ $sortCol==='lead_value' ? 'selected':'' }}>Value: High–Low</option>
                        <option value="priority"   {{ $sortCol==='priority'   ? 'selected':'' }}>Priority</option>
                        <option value="status"     {{ $sortCol==='status'     ? 'selected':'' }}>Status</option>
                    </select>
                    <input type="hidden" name="dir" value="{{ $sortDir === 'asc' ? 'desc' : 'asc' }}">
                </form>
            </div>
        </div>

        <div class="li-table-wrap">
            <table class="li-table">
                <thead>
                    <tr>
                        <th style="width:36px;padding:10px 8px 10px 18px">
                            <input type="checkbox" id="selectAll" style="width:14px;height:14px;cursor:pointer" />
                        </th>
                        <th style="width:220px">
                            <a href="{{ route('tenant.leads.index', array_merge(request()->query(), ['sort'=>'name','dir'=>$nextDir('name')])) }}"
                               class="{{ $isActive('name') ? 'sort-'.$sortDir : '' }}">
                                Lead
                                <svg class="sort-icon" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M7 16V4m0 0L3 8m4-4l4 4M17 8v12m0 0l4-4m-4 4l-4-4"/></svg>
                            </a>
                        </th>
                        <th style="width:110px">Status</th>
                        <th style="width:90px">Priority</th>
                        <th style="width:110px">Source</th>
                        <th style="width:130px">
                            <a href="{{ route('tenant.leads.index', array_merge(request()->query(), ['sort'=>'lead_value','dir'=>$nextDir('lead_value')])) }}"
                               class="{{ $isActive('lead_value') ? 'sort-'.$sortDir : '' }}">
                                Value
                                <svg class="sort-icon" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M7 16V4m0 0L3 8m4-4l4 4M17 8v12m0 0l4-4m-4 4l-4-4"/></svg>
                            </a>
                        </th>
                        <th style="width:130px">Assigned To</th>
                        <th style="width:105px">
                            <a href="{{ route('tenant.leads.index', array_merge(request()->query(), ['sort'=>'created_at','dir'=>$nextDir('created_at')])) }}"
                               class="{{ $isActive('created_at') ? 'sort-'.$sortDir : '' }}">
                                Created
                                <svg class="sort-icon" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M7 16V4m0 0L3 8m4-4l4 4M17 8v12m0 0l4-4m-4 4l-4-4"/></svg>
                            </a>
                        </th>
                        <th style="width:80px"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($leads as $i => $lead)
                    @php
                        $initials = collect(explode(' ', $lead->name))->map(fn($p)=>strtoupper($p[0]??''))->join('');
                        $initials = substr($initials, 0, 2);
                        [$avBg, $avTx] = $avatarColors[$i % 4];
                        $sc = $statusClass[$lead->status ?? 'new'] ?? 's-new';
                        $pc = $priorityClass[$lead->priority ?? 'medium'] ?? 'p-medium';
                        $statusLabel = $statuses[$lead->status ?? 'new'] ?? ucfirst($lead->status ?? '');
                        $sourceLabel = $sources[$lead->source ?? 'other'] ?? ucfirst($lead->source ?? '');
                        $priorityLabel = $priorities[$lead->priority ?? 'medium'] ?? ucfirst($lead->priority ?? '');

                        /* Assignee initials */
                        $assInit = '';
                        if ($lead->assignedTo) {
                            $assInit = collect(explode(' ', $lead->assignedTo->name))
                                ->map(fn($p) => strtoupper($p[0]??''))->join('');
                            $assInit = substr($assInit, 0, 2);
                        }
                        [$assBg, $assTx] = $avatarColors[($i+1) % 4];
                    @endphp
                    <tr>
                        <td style="padding:12px 8px 12px 18px">
                            <input type="checkbox" class="row-check" value="{{ $lead->id }}"
                                   style="width:14px;height:14px;cursor:pointer" />
                        </td>

                        {{-- Lead Name --}}
                        <td>
                            <div style="display:flex;align-items:center;gap:10px">
                                <div class="li-avatar" style="background:{{ $avBg }};color:{{ $avTx }}">
                                    {{ $initials }}
                                </div>
                                <div>
                                    <div class="li-lead-name">
                                        <a href="{{ route('tenant.leads.show', ['tenant'=>auth()->user()->tenant->subdomain,'id'=>$lead->id]) }}"
                                           style="color:inherit;text-decoration:none">
                                            {{ $lead->name }}
                                        </a>
                                    </div>
                                    <div class="li-lead-sub">
                                        @if($lead->company) {{ $lead->company }} · @endif
                                        {{ $lead->phone }}
                                    </div>
                                </div>
                            </div>
                        </td>

                        {{-- Status --}}
                        <td>
                            <span class="li-badge {{ $sc }}">
                                <span style="width:5px;height:5px;border-radius:50%;background:currentColor;display:inline-block"></span>
                                {{ $statusLabel }}
                            </span>
                        </td>

                        {{-- Priority --}}
                        <td>
                            <span class="li-badge {{ $pc }}">{{ $priorityLabel }}</span>
                        </td>

                        {{-- Source --}}
                        <td style="font-size:12.5px;color:var(--text-300)">{{ $sourceLabel }}</td>

                        {{-- Value --}}
                        <td>
                            @if($lead->lead_value)
                            <span style="font-family:'DM Mono',monospace;font-size:13px;font-weight:500;color:var(--text-100)">
                                ₹{{ number_format($lead->lead_value) }}
                            </span>
                            @else
                            <span style="color:var(--text-400);font-size:12px">—</span>
                            @endif
                        </td>

                        {{-- Assigned To --}}
                        <td>
                            @if($lead->assignedTo)
                            <div style="display:flex;align-items:center;gap:7px">
                                <div style="width:24px;height:24px;border-radius:50%;background:{{ $assBg }};color:{{ $assTx }};display:flex;align-items:center;justify-content:center;font-size:9px;font-weight:700;flex-shrink:0">
                                    {{ $assInit }}
                                </div>
                                <span style="font-size:12.5px;color:var(--text-200)">
                                    {{ \Illuminate\Support\Str::limit($lead->assignedTo->name, 14) }}
                                </span>
                            </div>
                            @else
                            <span style="font-size:12px;color:var(--text-400);font-style:italic">Unassigned</span>
                            @endif
                        </td>

                        {{-- Created At --}}
                        <td>
                            <span style="font-size:12px;color:var(--text-300);font-family:'DM Mono',monospace">
                                {{ $lead->created_at->format('M d, Y') }}
                            </span>
                        </td>

                        {{-- Actions --}}
                        <td>
                            <div class="li-actions">
                                <a href="{{ route('tenant.leads.show', ['tenant'=>auth()->user()->tenant->subdomain,'id'=>$lead->id]) }}"
                                   class="li-act-btn" title="View">
                                    <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                </a>
                                <a href="{{ route('tenant.leads.edit', ['tenant'=>auth()->user()->tenant->subdomain,'id'=>$lead->id]) }}"
                                   class="li-act-btn" title="Edit">
                                    <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </a>
                                <form method="POST"
                                      action="{{ route('tenant.leads.destroy', ['tenant'=>auth()->user()->tenant->subdomain,'id'=>$lead->id]) }}"
                                      onsubmit="return confirm('Delete lead \'{{ addslashes($lead->name) }}\'? This cannot be undone.')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="li-act-btn danger" title="Delete">
                                        <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9">
                            <div class="li-empty">
                                <div class="li-empty-icon">
                                    <svg width="22" height="22" fill="none" stroke="var(--text-300)" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                                </div>
                                <div class="li-empty-title">No leads found</div>
                                <div class="li-empty-sub">
                                    @if(request()->hasAny(['search','source','priority','assigned_to','status']))
                                        Try adjusting your filters or
                                        <a href="{{ route('tenant.leads.index') }}" style="color:var(--accent)">clear all filters</a>
                                    @else
                                        Get started by <a href="{{ route('tenant.leads.create') }}" style="color:var(--accent)">adding your first lead</a>
                                    @endif
                                </div>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($leads->hasPages())
        <div class="li-pag">
            <div class="li-pag-info">
                Page {{ $leads->currentPage() }} of {{ $leads->lastPage() }}
                · {{ number_format($leads->total()) }} total leads
            </div>
            <div class="li-pag-btns">

                {{-- Previous --}}
                @if($leads->onFirstPage())
                <span class="li-pg-btn" aria-disabled="true">
                    <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                    Prev
                </span>
                @else
                <a href="{{ $leads->previousPageUrl() }}" class="li-pg-btn">
                    <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                    Prev
                </a>
                @endif

                {{-- Page numbers --}}
                @php
                $window    = 2;
                $current   = $leads->currentPage();
                $last      = $leads->lastPage();
                $from      = max(1, $current - $window);
                $to        = min($last, $current + $window);
                @endphp

                @if($from > 1)
                <a href="{{ $leads->url(1) }}" class="li-pg-btn">1</a>
                @if($from > 2)
                <span class="li-pg-btn li-pg-dot" style="min-width:24px">…</span>
                @endif
                @endif

                @for($p = $from; $p <= $to; $p++)
                @if($p === $current)
                <span class="li-pg-btn active">{{ $p }}</span>
                @else
                <a href="{{ $leads->url($p) }}" class="li-pg-btn">{{ $p }}</a>
                @endif
                @endfor

                @if($to < $last)
                @if($to < $last - 1)
                <span class="li-pg-btn li-pg-dot" style="min-width:24px">…</span>
                @endif
                <a href="{{ $leads->url($last) }}" class="li-pg-btn">{{ $last }}</a>
                @endif

                {{-- Next --}}
                @if($leads->hasMorePages())
                <a href="{{ $leads->nextPageUrl() }}" class="li-pg-btn">
                    Next
                    <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                </a>
                @else
                <span class="li-pg-btn" aria-disabled="true">
                    Next
                    <svg width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                </span>
                @endif
            </div>
        </div>
        @endif
    </div>{{-- /li-card --}}

</div>
@endsection

@push('scripts')
<script>
(function(){
    /* Select All checkbox */
    const selectAll = document.getElementById('selectAll');
    if(selectAll){
        selectAll.addEventListener('change', function(){
            document.querySelectorAll('.row-check').forEach(cb => cb.checked = this.checked);
        });
        document.querySelectorAll('.row-check').forEach(cb => {
            cb.addEventListener('change', function(){
                const all   = document.querySelectorAll('.row-check');
                const checked = document.querySelectorAll('.row-check:checked');
                selectAll.indeterminate = checked.length > 0 && checked.length < all.length;
                selectAll.checked = checked.length === all.length;
            });
        });
    }

    /* Auto-submit search on enter */
    const searchInput = document.querySelector('.li-fi-search');
    if(searchInput){
        let timer;
        searchInput.addEventListener('input', function(){
            clearTimeout(timer);
            timer = setTimeout(() => this.form.submit(), 500);
        });
    }
})();
</script>
@endpush