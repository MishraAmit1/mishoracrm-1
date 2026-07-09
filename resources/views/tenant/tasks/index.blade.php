@extends('layouts.app')
@section('title', 'Tasks')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.19.0/dist/tabler-icons.min.css"/>
<style>
/* ── Base ───────────────────────────────────────────────────────── */
.di { font-family: var(--font), sans-serif; }

/* ── Summary cards ──────────────────────────────────────────────── */
.di-summary {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 10px;
    margin-bottom: 16px;
}
@media(max-width:1100px) { .di-summary { grid-template-columns: repeat(3,1fr); } }
@media(max-width:600px)  { .di-summary { grid-template-columns: repeat(2,1fr); } }

.di-sum {
    background: var(--bg-surface); border: 1px solid var(--border-default);
    border-radius: 10px; padding: 13px 15px; text-decoration: none;
    display: block; position: relative; overflow: hidden;
    transition: border-color .15s, transform .15s; cursor: pointer;
}
.di-sum:hover { border-color: var(--accent); transform: translateY(-1px); }
.di-sum.active { border-color: var(--accent); background: var(--accent-dim); }
.di-sum-val { font-size: 22px; font-weight: 700; color: var(--text-100); font-family: var(--mono); letter-spacing: -.5px; line-height: 1; }
.di-sum.active .di-sum-val { color: var(--accent); }
.di-sum-lbl { font-size: 11px; color: var(--text-300); margin-top: 3px; font-weight: 500; }
.di-sum-bar { position: absolute; bottom: 0; left: 0; height: 3px; border-radius: 0 2px 0 0; }

/* ── Toolbar ────────────────────────────────────────────────────── */
.di-toolbar { display:flex; align-items:center; gap:8px; flex-wrap:wrap; margin-bottom:16px; }
.di-fi {
    padding: 8px 11px; height: 36px;
    background: var(--bg-surface); border: 1px solid var(--border-default);
    border-radius: 8px; font-size: 12.5px; color: var(--text-100);
    font-family: var(--font); outline: none;
    transition: border-color .15s, box-shadow .15s;
    -webkit-appearance: none; cursor: pointer;
}
.di-fi:focus { border-color: var(--accent); box-shadow: 0 0 0 3px var(--accent-dim); }
.di-sw  { position: relative; flex: 1; min-width: 180px; max-width: 260px; }
.di-sw svg { position: absolute; left: 10px; top: 50%; transform: translateY(-50%); pointer-events: none; }
.di-fi-s { width: 100%; padding-left: 32px; }

.view-toggle { display:flex; border:1px solid var(--border-default); border-radius:8px; overflow:hidden; margin-left:auto; }
.vt-btn {
    padding: 7px 13px; background: transparent; border: none;
    cursor: pointer; color: var(--text-300);
    transition: all .15s; display: flex; align-items: center; gap: 5px;
    font-size: 12.5px; font-family: var(--font); font-weight: 500; text-decoration: none;
}
.vt-btn.active { background: var(--accent); color: #fff; }
.vt-btn:not(.active):hover { background: var(--bg-elevated); color: var(--text-100); }

/* ── Kanban ─────────────────────────────────────────────────────── */
.kanban-scroll { overflow-x: auto; padding-bottom: 8px; -webkit-overflow-scrolling: touch; }
.kanban-board  { display: flex; gap: 14px; min-width: max-content; padding: 2px 0 6px; align-items: flex-start; }

.k-col {
    width: 290px; flex-shrink: 0; display: flex; flex-direction: column;
    background: var(--bg-elevated); border: 1px solid var(--border-subtle);
    border-radius: 12px; overflow: hidden;
}
.k-col-head {
    display: flex; align-items: center; justify-content: space-between;
    padding: 12px 14px; border-bottom: 1px solid var(--border-subtle);
}
.k-col-head-l { display: flex; align-items: center; gap: 8px; }
.k-col-title  { font-size: 13px; font-weight: 600; }
.k-col-count  { font-size: 11px; font-family: var(--mono); padding: 2px 7px; border-radius: 10px; font-weight: 600; color: #fff; }

/* Drop zone */
.k-drop-zone {
    flex: 1; min-height: 200px; padding: 10px;
    display: flex; flex-direction: column; gap: 8px;
    transition: background .2s;
}
.k-drop-zone.drag-over {
    background: rgba(99,120,255,.06);
    outline: 2px dashed var(--accent);
    outline-offset: -6px; border-radius: 6px;
}

/* Task card */
.task-card {
    background: var(--bg-surface); border: 1px solid var(--border-default);
    border-radius: 10px; padding: 14px; cursor: grab;
    transition: border-color .15s, box-shadow .15s, opacity .15s, transform .15s;
    user-select: none; position: relative;
    border-left: 3px solid transparent;
}
.task-card:hover  { border-color: var(--border-strong); box-shadow: 0 2px 12px rgba(0,0,0,.08); }
.task-card.is-dragging { opacity: .35; cursor: grabbing; transform: scale(.97); }

.tc-top     { display:flex; align-items:flex-start; justify-content:space-between; gap:6px; margin-bottom:6px; }
.tc-title   { font-size: 13px; font-weight: 600; color: var(--text-100); line-height: 1.35; flex: 1; }
.tc-grip    { width:16px; height:20px; flex-shrink:0; opacity:0; transition:opacity .15s; display:flex; align-items:center; justify-content:center; color:var(--text-400); cursor:grab; }
.task-card:hover .tc-grip { opacity: 1; }
.tc-desc    { font-size: 11.5px; color: var(--text-300); margin-bottom: 10px; line-height: 1.4; }
.tc-sep     { height: 1px; background: var(--border-subtle); margin: 10px 0; }
.tc-foot    { display:flex; align-items:center; justify-content:space-between; }
.tc-cnts    { display:flex; gap:8px; }
.tc-cnt     { display:inline-flex; align-items:center; gap:3px; font-size:11px; color:var(--text-400); }
.tc-right   { display:flex; align-items:center; gap:5px; }
.tc-date    { font-size: 11px; color: var(--text-400); font-family: var(--mono); }
.tc-av      { width:22px; height:22px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:9px; font-weight:700; }
.tc-view-btn {
    width: 22px; height: 22px; border-radius: 5px;
    background: var(--bg-elevated); display: flex; align-items: center; justify-content: center;
    color: var(--text-400); text-decoration: none; transition: all .15s; flex-shrink: 0;
}
.tc-view-btn:hover { background: var(--accent); color: #fff; }
.k-empty { text-align:center; padding:24px 12px; font-size:12px; color:var(--text-400); line-height:1.5; }
.k-add-btn {
    display:flex; align-items:center; justify-content:center; gap:5px;
    margin: 0 10px 10px; padding: 8px;
    border: 1px dashed var(--border-default); border-radius: 8px;
    font-size: 12px; color: var(--text-400); cursor: pointer;
    font-family: var(--font); transition: all .15s; text-decoration: none; background: transparent;
}
.k-add-btn:hover { border-color: var(--accent); color: var(--accent); background: var(--accent-dim); }

/* Toast */
.move-toast {
    position: fixed; bottom: 24px; left: 50%;
    transform: translateX(-50%) translateY(80px);
    background: var(--accent); color: #fff;
    padding: 10px 20px; border-radius: 10px;
    font-size: 13px; font-weight: 500;
    display: flex; align-items: center; gap: 8px;
    box-shadow: 0 4px 24px rgba(0,0,0,.2);
    transition: transform .35s cubic-bezier(.34,1.56,.64,1), opacity .3s;
    z-index: 9999; opacity: 0; pointer-events: none;
}
.move-toast.show { transform: translateX(-50%) translateY(0); opacity: 1; }
.move-toast.error { background: var(--red); }

/* ── List view ──────────────────────────────────────────────────── */
.list-wrap { background:var(--bg-surface); border:1px solid var(--border-default); border-radius:12px; overflow:hidden; }
.list-head { display:flex; align-items:center; justify-content:space-between; padding:12px 18px; border-bottom:1px solid var(--border-subtle); background:var(--bg-elevated); }
.list-count { font-size:12px; color:var(--text-300); }
.list-count strong { color:var(--text-100); font-weight:600; }

.di-table { width:100%; border-collapse:collapse; min-width:700px; }
.di-table thead tr { background:var(--bg-elevated); }
.di-table th { padding:9px 14px; text-align:left; font-size:11px; font-weight:600; color:var(--text-300); text-transform:uppercase; letter-spacing:.5px; border-bottom:1px solid var(--border-subtle); white-space:nowrap; }
.di-table td { padding:11px 14px; font-size:13px; color:var(--text-100); border-bottom:1px solid var(--border-subtle); vertical-align:middle; }
.di-table tr:last-child td { border-bottom:none; }
.di-table tbody tr:hover td { background:var(--bg-elevated); }

.st-badge { display:inline-flex; align-items:center; gap:4px; padding:3px 9px; border-radius:20px; font-size:11px; font-weight:600; white-space:nowrap; }

.row-actions { display:flex; align-items:center; gap:3px; opacity:0; transition:opacity .15s; }
.di-table tbody tr:hover .row-actions { opacity:1; }
.act-btn {
    width:28px; height:28px; display:flex; align-items:center; justify-content:center;
    border-radius:6px; border:1px solid var(--border-subtle);
    background:transparent; cursor:pointer; color:var(--text-300); text-decoration:none; transition:all .15s;
}
.act-btn:hover     { background:var(--bg-elevated); color:var(--text-100); border-color:var(--border-default); }
.act-btn.del:hover { background:var(--red-dim); border-color:var(--red); color:var(--red); }

.pag-wrap { display:flex; align-items:center; justify-content:space-between; padding:13px 18px; border-top:1px solid var(--border-subtle); background:var(--bg-elevated); flex-wrap:wrap; gap:8px; }
.pag-info { font-size:12px; color:var(--text-300); }
.pag-info strong { color:var(--text-100); font-weight:600; }
.pag-btns { display:flex; gap:4px; }
.pg-btn { min-width:32px; height:32px; padding:0 9px; display:inline-flex; align-items:center; justify-content:center; border-radius:7px; border:1px solid var(--border-default); background:var(--bg-surface); font-size:13px; font-weight:500; cursor:pointer; color:var(--text-200); text-decoration:none; transition:all .15s; font-family:var(--font); }
.pg-btn:hover  { background:var(--bg-elevated); color:var(--text-100); }
.pg-btn.active { background:var(--accent); border-color:var(--accent); color:#fff; }
.pg-btn.disabled { opacity:.35; pointer-events:none; }

.di-empty { text-align:center; padding:56px 24px; }
.di-empty-icon { width:46px; height:46px; border-radius:12px; background:var(--bg-elevated); display:flex; align-items:center; justify-content:center; margin:0 auto 12px; }
.di-empty-title { font-size:14px; font-weight:600; color:var(--text-100); margin-bottom:5px; }
.di-empty-sub   { font-size:13px; color:var(--text-300); margin-bottom:16px; }
</style>
@endpush

@section('content')

@php
    $cfgStages   = config('task_fields.stages');
    $cfgPriorities = config('task_fields.priorities');
    $currentView = $view ?? 'kanban';
    $currentStage = request('stage', '');
    $sortCol = request('sort', 'created_at');
    $sortDir = request('dir', 'desc');

    $avColors = [
        ['#E6F1FB','#185FA5'],
        ['#E1F5EE','#0F6E56'],
        ['#FAEEDA','#854F0B'],
        ['#EEEDFE','#3C3489'],
    ];

    $initials = fn(string $name): string =>
        collect(explode(' ', $name))->map(fn($p) => strtoupper($p[0] ?? ''))->join('');

    $allCount = $stageSummary->sum();
@endphp

<div class="di">

    {{-- Page header --}}
    <div class="page-head">
        <div>
            <div class="page-title">Tasks</div>
            <div style="font-size:12px;color:var(--text-300);margin-top:2px">
                Track and manage all tasks
            </div>
        </div>
        <div style="display:flex;gap:8px">
            <a href="{{ route('tenant.tasks.create') }}" class="btn btn-primary">
                <i class="ti ti-plus" style="font-size:14px"></i> Add Task
            </a>
        </div>
    </div>

    @if(session('success'))
    <div style="display:flex;align-items:center;gap:10px;padding:11px 15px;background:var(--green-dim);border:1px solid rgba(45,212,160,.3);border-radius:8px;margin-bottom:14px;font-size:13px;color:var(--green);font-weight:500">
        <i class="ti ti-circle-check" style="font-size:16px"></i>
        {{ session('success') }}
    </div>
    @endif

    {{-- Summary --}}
    <div class="di-summary">
        <a href="{{ route('tenant.tasks.index', array_merge(request()->except(['stage','page']), ['view'=>$currentView])) }}"
           class="di-sum {{ $currentStage === '' ? 'active' : '' }}">
            <div class="di-sum-val">{{ $allCount }}</div>
            <div class="di-sum-lbl">All Tasks</div>
            <div class="di-sum-bar" style="width:100%;background:var(--accent)"></div>
        </a>
        @foreach($cfgStages as $slug => $stage)
        @php
            $ss   = $stageSummary->get($slug);
            $sc   = $ss?->count ?? 0;
            $pct  = $allCount > 0 ? round(($sc / $allCount) * 100) : 0;
        @endphp
        <a href="{{ route('tenant.tasks.index', array_merge(request()->except(['stage','page']), ['stage'=>$slug,'view'=>$currentView])) }}"
           class="di-sum {{ $currentStage === $slug ? 'active' : '' }}">
            <div class="di-sum-val">{{ $sc }}</div>
            <div class="di-sum-lbl">{{ $stage['label'] }}</div>
            <div class="di-sum-bar" style="width:{{ $pct }}%;background:{{ $stage['color'] }}"></div>
        </a>
        @endforeach
    </div>

    {{-- Toolbar --}}
    <form method="GET" action="{{ route('tenant.tasks.index') }}" id="filterForm">
        <input type="hidden" name="view" value="{{ $currentView }}">
        @if(request('stage'))  <input type="hidden" name="stage"  value="{{ request('stage') }}"> @endif
        @if(request('sort'))   <input type="hidden" name="sort"   value="{{ request('sort') }}"> @endif
        @if(request('dir'))    <input type="hidden" name="dir"    value="{{ request('dir') }}"> @endif

        <div class="di-toolbar">
            {{-- Search --}}
            <div class="di-sw">
                <svg width="14" height="14" fill="none" stroke="var(--text-300)" stroke-width="2" viewBox="0 0 24 24">
                    <circle cx="11" cy="11" r="8"/><path stroke-linecap="round" d="M21 21l-4.35-4.35"/>
                </svg>
                <input type="text" name="search" class="di-fi di-fi-s"
                       placeholder="Search tasks..."
                       value="{{ request('search') }}" autocomplete="off"/>
            </div>

            {{-- Assigned to --}}
            <select name="assigned_to" class="di-fi" style="min-width:130px" onchange="this.form.submit()">
                <option value="">All Assignees</option>
                @foreach($staffList as $staff)
                <option value="{{ $staff->id }}" {{ request('assigned_to') == $staff->id ? 'selected':'' }}>
                    {{ $staff->name }}
                </option>
                @endforeach
            </select>

            {{-- Priority --}}
            <select name="priority" class="di-fi" onchange="this.form.submit()">
                <option value="">All Priority</option>
                @foreach($cfgPriorities as $key => $p)
                <option value="{{ $key }}" {{ request('priority') === $key ? 'selected':'' }}>
                    {{ $p['label'] }}
                </option>
                @endforeach
            </select>

            <button type="submit" class="btn btn-secondary">
                <i class="ti ti-filter" style="font-size:13px"></i> Filter
            </button>

            @if(request()->hasAny(['search','assigned_to','priority']))
            <a href="{{ route('tenant.tasks.index', array_merge(request()->only(['view','stage','sort','dir']))) }}"
               class="btn btn-secondary">
                <i class="ti ti-x" style="font-size:13px"></i> Clear
            </a>
            @endif

            {{-- View toggle --}}
            <div class="view-toggle">
                <a href="{{ route('tenant.tasks.index', array_merge(request()->except('view'), ['view'=>'kanban'])) }}"
                   class="vt-btn {{ $currentView === 'kanban' ? 'active':'' }}">
                    <i class="ti ti-layout-columns" style="font-size:13px"></i> Kanban
                </a>
                <a href="{{ route('tenant.tasks.index', array_merge(request()->except('view'), ['view'=>'list'])) }}"
                   class="vt-btn {{ $currentView === 'list' ? 'active':'' }}">
                    <i class="ti ti-list" style="font-size:13px"></i> List
                </a>
            </div>
        </div>
    </form>

    {{-- ═══ KANBAN VIEW ════════════════════════════════════════════ --}}
    @if($currentView === 'kanban')

    <div class="kanban-scroll">
        <div class="kanban-board" id="kanbanBoard">

            @foreach($cfgStages as $slug => $stage)
            @php $colTasks = $kanbanTasks->get($slug, collect()); @endphp

            <div class="k-col" data-stage="{{ $slug }}">

                {{-- Column header --}}
                <div class="k-col-head" style="border-top:3px solid {{ $stage['color'] }}">
                    <div class="k-col-head-l">
                        <span class="k-col-title" style="color:{{ $stage['text_color'] }}">
                            {{ $stage['label'] }}
                        </span>
                        <span class="k-col-count" id="count_{{ $slug }}"
                              style="background:{{ $stage['color'] }}">
                            {{ $colTasks->count() }}
                        </span>
                    </div>
                </div>

                {{-- Drop zone --}}
                <div class="k-drop-zone" id="zone_{{ $slug }}" data-stage="{{ $slug }}">

                    @if($colTasks->isEmpty())
                    <div class="k-empty" id="empty_{{ $slug }}">
                        <i class="ti ti-inbox" style="font-size:20px;display:block;margin-bottom:6px"></i>
                        No tasks here
                    </div>
                    @else
                    <div class="k-empty" id="empty_{{ $slug }}" style="display:none">
                        <i class="ti ti-inbox" style="font-size:20px;display:block;margin-bottom:6px"></i>
                        No tasks here
                    </div>
                    @endif

                    @foreach($colTasks as $i => $task)
                    @php
                        [$avBg, $avTx] = $avColors[$i % 4];
                        $priority = $cfgPriorities[$task->priority] ?? null;
                        $taskInitials = $task->assignedTo ? $initials($task->assignedTo->name) : '';
                    @endphp

                    <div class="task-card"
                         draggable="true"
                         id="card_{{ $task->id }}"
                         data-task-id="{{ $task->id }}"
                         data-stage="{{ $slug }}"
                         data-title="{{ e($task->title) }}"
                         style="border-left-color:{{ $stage['color'] }}">

                        <div class="tc-top">
                            <div class="tc-title">{{ $task->title }}</div>
                            <div class="tc-grip">
                                <i class="ti ti-grip-vertical"></i>
                            </div>
                        </div>

                        @if($task->description)
                        <div class="tc-desc">
                            {{ \Illuminate\Support\Str::limit($task->description, 80) }}
                        </div>
                        @endif

                        @if($priority)
                        <div style="margin-top:6px">
                            <span class="st-badge"
                                  style="background:{{ $priority['bg'] }};color:{{ $priority['color'] }};border:1px solid {{ $priority['color'] }}30">
                                <i class="ti ti-flag" style="font-size:10px"></i>
                                {{ $priority['label'] }}
                            </span>
                        </div>
                        @endif

                        <div class="tc-sep"></div>

                        <div class="tc-foot">
                            <div class="tc-cnts">
                                @if($task->due_at)
                                <span class="tc-cnt {{ \Carbon\Carbon::parse($task->due_at)->isPast() && $task->status !== 'completed' ? 'style=color:var(--red)' : '' }}">
                                    <i class="ti ti-calendar" style="font-size:11px"></i>
                                    {{ \Carbon\Carbon::parse($task->due_at)->format('M d') }}
                                </span>
                                @endif
                            </div>
                            <div class="tc-right">
                                @if($task->assignedTo)
                                <div class="tc-av" style="background:{{ $avBg }};color:{{ $avTx }}"
                                     title="{{ $task->assignedTo->name }}">
                                    {{ substr($taskInitials,0,2) }}
                                </div>
                                @endif
                                <a href="{{ route('tenant.tasks.show', $task->id) }}"
                                   class="tc-view-btn" onclick="event.stopPropagation()">
                                    <i class="ti ti-arrow-right"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>

                {{-- Add task to this stage --}}
                <a href="{{ route('tenant.tasks.create', ['status'=>$slug]) }}" class="k-add-btn">
                    <i class="ti ti-plus"></i> Add Task
                </a>

            </div>
            @endforeach

        </div>
    </div>

    {{-- Toast --}}
    <div class="move-toast" id="moveToast">
        <i class="ti ti-check"></i>
        <span id="toastText">Task moved!</span>
    </div>

    {{-- ═══ LIST VIEW ══════════════════════════════════════════════ --}}
    @else

    <div class="list-wrap">
        <div class="list-head">
            <div class="list-count">
                @if(isset($tasks) && $tasks->total())
                    Showing <strong>{{ $tasks->firstItem() }}–{{ $tasks->lastItem() }}</strong>
                    of <strong>{{ number_format($tasks->total()) }}</strong> tasks
                @else
                    <strong>0</strong> tasks found
                @endif
            </div>
        </div>

        @if(!isset($tasks) || $tasks->isEmpty())
        <div class="di-empty">
            <div class="di-empty-icon">
                <i class="ti ti-checklist" style="font-size:22px;color:var(--text-300)"></i>
            </div>
            <div class="di-empty-title">No tasks found</div>
            <div class="di-empty-sub">Start by creating your first task</div>
            <a href="{{ route('tenant.tasks.create') }}" class="btn btn-primary">Add Task</a>
        </div>
        @else
        <div style="overflow-x:auto">
            <table class="di-table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Status</th>
                        <th>Priority</th>
                        <th>Due Date</th>
                        <th>Assigned To</th>
                        <th width="100"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($tasks as $i => $task)
                    @php
                        [$avBg,$avTx] = $avColors[$i % 4];
                        $status   = $cfgStages[$task->status]   ?? ['label'=>ucfirst($task->status),'color'=>'#999','bg'=>'#eee','text_color'=>'#555'];
                        $priority = $cfgPriorities[$task->priority] ?? null;
                        $taskInitials = $task->assignedTo ? $initials($task->assignedTo->name) : '';
                    @endphp
                    <tr>
                        <td data-label="Title">
                            <div style="font-weight:600">
                                <a href="{{ route('tenant.tasks.show', $task->id) }}"
                                   style="text-decoration:none;color:inherit">
                                    {{ $task->title }}
                                </a>
                            </div>
                            @if($task->description)
                            <div style="font-size:12px;color:var(--text-400)">
                                {{ \Illuminate\Support\Str::limit($task->description, 60) }}
                            </div>
                            @endif
                        </td>
                        <td data-label="Status">
                            <span class="st-badge"
                                  style="background:{{ $status['bg'] }};color:{{ $status['text_color'] }};border:1px solid {{ $status['color'] }}30">
                                {{ $status['label'] }}
                            </span>
                        </td>
                        <td data-label="Priority">
                            @if($priority)
                            <span class="st-badge"
                                  style="background:{{ $priority['bg'] }};color:{{ $priority['color'] }};border:1px solid {{ $priority['color'] }}30">
                                {{ $priority['label'] }}
                            </span>
                            @else —
                            @endif
                        </td>
                        <td data-label="Due Date">
                            @if($task->due_at)
                            <span style="font-size:12px;{{ \Carbon\Carbon::parse($task->due_at)->isPast() && $task->status !== 'completed' ? 'color:var(--red)':'' }}">
                                {{ \Carbon\Carbon::parse($task->due_at)->format('d M Y, h:i A') }}
                            </span>
                            @else — @endif
                        </td>
                        <td data-label="Assigned To">
                            @if($task->assignedTo)
                            <div style="display:flex;align-items:center;gap:8px">
                                <div class="tc-av" style="background:{{ $avBg }};color:{{ $avTx }}">
                                    {{ substr($taskInitials,0,2) }}
                                </div>
                                <span>{{ $task->assignedTo->name }}</span>
                            </div>
                            @else
                            <span style="color:var(--text-400)">Unassigned</span>
                            @endif
                        </td>
                        <td>
                            <div class="row-actions">
                                <a href="{{ route('tenant.tasks.show', $task->id) }}" class="act-btn">
                                    <i class="ti ti-eye"></i>
                                </a>
                                <a href="{{ route('tenant.tasks.edit', $task->id) }}" class="act-btn">
                                    <i class="ti ti-edit"></i>
                                </a>
                                <form method="POST" action="{{ route('tenant.tasks.destroy', $task->id) }}"
                                      style="display:inline" onsubmit="return confirm('Delete this task?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="act-btn del">
                                        <i class="ti ti-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($tasks->hasPages())
        <div class="pag-wrap">
            <div class="pag-info">
                Showing <strong>{{ $tasks->firstItem() }}</strong>–<strong>{{ $tasks->lastItem() }}</strong>
                of <strong>{{ $tasks->total() }}</strong>
            </div>
            <div class="pag-btns">
                <a href="{{ $tasks->previousPageUrl() ?? '#' }}"
                   class="pg-btn {{ !$tasks->previousPageUrl() ? 'disabled':'' }}">← Prev</a>
                @foreach($tasks->getUrlRange(max(1,$tasks->currentPage()-2), min($tasks->lastPage(),$tasks->currentPage()+2)) as $page => $url)
                <a href="{{ $url }}" class="pg-btn {{ $page==$tasks->currentPage() ? 'active':'' }}">{{ $page }}</a>
                @endforeach
                <a href="{{ $tasks->nextPageUrl() ?? '#' }}"
                   class="pg-btn {{ !$tasks->nextPageUrl() ? 'disabled':'' }}">Next →</a>
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
(function () {
    'use strict';

    // ── Config from PHP ───────────────────────────────────────────
    const CSRF    = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
    const STAGES  = @json(config('task_fields.stages'));

    // ── Build update URL ──────────────────────────────────────────
    // Route: tasks/{id}/stage  (PATCH)
    function stageUrl(taskId) {
        return 'tasks/' + taskId + '/update-stage';
    }

    // ── Drag state ────────────────────────────────────────────────
    let dragCard   = null;
    let dragStage  = null;
    let dragZone   = null;

    // ── Attach listeners to all cards ─────────────────────────────
    function attachCardListeners() {
        document.querySelectorAll('.task-card').forEach(card => {
            card.removeEventListener('dragstart', onDragStart);
            card.removeEventListener('dragend',   onDragEnd);
            card.addEventListener('dragstart', onDragStart);
            card.addEventListener('dragend',   onDragEnd);
        });
    }

    // ── Attach listeners to all zones ─────────────────────────────
    function attachZoneListeners() {
        document.querySelectorAll('.k-drop-zone').forEach(zone => {
            zone.addEventListener('dragover',  onDragOver);
            zone.addEventListener('dragenter', onDragEnter);
            zone.addEventListener('dragleave', onDragLeave);
            zone.addEventListener('drop',      onDrop);
        });
    }

    // ── DragStart ─────────────────────────────────────────────────
    function onDragStart(e) {
        dragCard  = e.currentTarget;
        dragStage = dragCard.dataset.stage;
        dragZone  = dragCard.closest('.k-drop-zone');

        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/plain', dragCard.dataset.taskId);

        // Delay so the ghost image renders before we add class
        requestAnimationFrame(() => {
            dragCard.classList.add('is-dragging');
        });
    }

    // ── DragEnd ───────────────────────────────────────────────────
    function onDragEnd(e) {
        if (dragCard) dragCard.classList.remove('is-dragging');
        document.querySelectorAll('.k-drop-zone').forEach(z => z.classList.remove('drag-over'));
        dragCard  = null;
        dragStage = null;
        dragZone  = null;
    }

    // ── DragOver ──────────────────────────────────────────────────
    function onDragOver(e) {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';
    }

    // ── DragEnter ─────────────────────────────────────────────────
    function onDragEnter(e) {
        e.preventDefault();
        e.currentTarget.classList.add('drag-over');
    }

    // ── DragLeave ─────────────────────────────────────────────────
    function onDragLeave(e) {
        // Only remove if leaving the zone itself, not a child element
        if (!e.currentTarget.contains(e.relatedTarget)) {
            e.currentTarget.classList.remove('drag-over');
        }
    }

    // ── Drop ──────────────────────────────────────────────────────
    async function onDrop(e) {
        e.preventDefault();

        const targetZone = e.currentTarget;
        targetZone.classList.remove('drag-over');

        const taskId   = e.dataTransfer.getData('text/plain');
        const newStage = targetZone.dataset.stage;

        // Nothing changed
        if (!taskId || !dragCard || newStage === dragStage) return;

        const oldStage = dragStage;
        const oldZone  = dragZone;
        const card     = dragCard;
        const cardTitle = card.dataset.title;

        // ── Optimistic UI update ──────────────────────────────────

        // Remove from old zone
        card.remove();

        // Append to new zone (before the k-empty div)
        const emptyNew = targetZone.querySelector('.k-empty');
        targetZone.insertBefore(card, emptyNew);

        // Update card stage data + accent color
        card.dataset.stage = newStage;
        const stageConfig = STAGES[newStage];
        if (stageConfig) {
            card.style.borderLeftColor = stageConfig.color;
        }

        // Show/hide empty states
        updateEmptyState(targetZone, newStage);
        if (oldZone) updateEmptyState(oldZone, oldStage);

        // Update column counts
        updateCount(oldStage);
        updateCount(newStage);

        showToast('"' + cardTitle + '" moved to ' + (stageConfig?.label ?? newStage));

        // ── API call ──────────────────────────────────────────────
        try {
            const resp = await fetch(stageUrl(taskId), {
                method: 'PATCH',
                headers: {
                    'Content-Type':  'application/json',
                    'X-CSRF-TOKEN':  CSRF,
                    'Accept':        'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({ status: newStage }),
            });

            if (!resp.ok) {
                throw new Error('Server error: ' + resp.status);
            }

        } catch (err) {
            console.error('Stage update failed:', err);

            // Revert optimistic update
            if (oldZone) {
                card.remove();
                oldZone.appendChild(card);
                card.dataset.stage = oldStage;
                const oldConfig = STAGES[oldStage];
                if (oldConfig) card.style.borderLeftColor = oldConfig.color;
                updateEmptyState(oldZone, oldStage);
                updateEmptyState(targetZone, newStage);
                updateCount(oldStage);
                updateCount(newStage);
            }

            showToast('Failed to update. Please try again.', true);
        }
    }

    // ── Helpers ───────────────────────────────────────────────────

    function updateEmptyState(zone, stage) {
        const empty = document.getElementById('empty_' + stage);
        if (!empty) return;
        const cards = zone.querySelectorAll('.task-card');
        empty.style.display = cards.length === 0 ? 'block' : 'none';
    }

    function updateCount(stage) {
        const zone  = document.getElementById('zone_' + stage);
        const badge = document.getElementById('count_' + stage);
        if (!zone || !badge) return;
        badge.textContent = zone.querySelectorAll('.task-card').length;
    }

    function showToast(msg, isError = false) {
        const toast = document.getElementById('moveToast');
        const text  = document.getElementById('toastText');
        if (!toast || !text) return;

        text.textContent = msg;
        toast.classList.toggle('error', isError);
        toast.classList.add('show');

        clearTimeout(toast._timer);
        toast._timer = setTimeout(() => toast.classList.remove('show'), 3000);
    }

    // ── Init ──────────────────────────────────────────────────────
    attachCardListeners();
    attachZoneListeners();

    // ── Search debounce ───────────────────────────────────────────
    const searchInput = document.querySelector('.di-fi-s');
    if (searchInput) {
        let debounceTimer;
        searchInput.addEventListener('input', () => {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                document.getElementById('filterForm').submit();
            }, 500);
        });
    }

})();
</script>
@endpush