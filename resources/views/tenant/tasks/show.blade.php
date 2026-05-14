@extends('layouts.app')

@section('title', $task->title)

@php
    $taskConfig = config('task_fields');

    $statuses = $taskConfig['stages'];

    $statusData = $statuses[$task->status] ?? [
        'label'      => ucfirst($task->status),
        'color'      => '#3B82F6',
        'bg'         => '#EFF6FF',
        'text_color' => '#1E40AF',
    ];
@endphp

@push('styles')
<style>
.df-show{
    font-family:'DM Sans',sans-serif;
}

/* ─────────────────────────────
   Header
───────────────────────────── */
.ts-top{
    display:flex;
    align-items:flex-start;
    justify-content:space-between;
    gap:16px;
    margin-bottom:18px;
}

@media(max-width:700px){
    .ts-top{
        flex-direction:column;
    }
}

/* ─────────────────────────────
   Layout
───────────────────────────── */
.ts-layout{
    display:grid;
    grid-template-columns:minmax(0,1fr) 340px;
    gap:18px;
}

@media(max-width:1050px){
    .ts-layout{
        grid-template-columns:1fr;
    }
}

/* ─────────────────────────────
   Card
───────────────────────────── */
.ts-card{
    background:var(--bg-surface);
    border:1px solid var(--border-default);
    border-radius:18px;
    overflow:hidden;
}

.ts-head{
    padding:18px 22px;
    border-bottom:1px solid var(--border-subtle);
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:10px;
}

.ts-title{
    font-size:15px;
    font-weight:700;
    color:var(--text-100);
}

.ts-sub{
    margin-top:3px;
    font-size:12px;
    color:var(--text-400);
}

.ts-body{
    padding:22px;
}

/* ─────────────────────────────
   Hero
───────────────────────────── */
.task-hero{
    padding:28px;
    background:
        radial-gradient(circle at top right,
        rgba(59,130,246,.15),
        transparent 30%),
        var(--bg-surface);

    border-bottom:1px solid var(--border-subtle);
}

.task-badge{
    display:inline-flex;
    align-items:center;
    gap:8px;
    padding:8px 14px;
    border-radius:999px;
    font-size:12px;
    font-weight:700;
}

.task-dot{
    width:8px;
    height:8px;
    border-radius:50%;
}

.task-title-main{
    margin-top:18px;
    font-size:34px;
    line-height:1.3;
    font-weight:800;
    color:var(--text-100);
    letter-spacing:-1px;
}

.task-desc{
    margin-top:16px;
    max-width:900px;
    font-size:14px;
    line-height:1.9;
    color:var(--text-300);
    white-space:pre-wrap;
}

/* ─────────────────────────────
   Stats
───────────────────────────── */
.ts-stats{
    display:grid;
    grid-template-columns:repeat(4,minmax(0,1fr));
    gap:14px;
}

@media(max-width:900px){
    .ts-stats{
        grid-template-columns:repeat(2,minmax(0,1fr));
    }
}

@media(max-width:500px){
    .ts-stats{
        grid-template-columns:1fr;
    }
}

.stat-box{
    padding:16px;
    border-radius:16px;
    background:var(--bg-elevated);
    border:1px solid var(--border-subtle);
}

.stat-label{
    font-size:11px;
    text-transform:uppercase;
    letter-spacing:.5px;
    color:var(--text-400);
    font-weight:700;
}

.stat-value{
    margin-top:10px;
    font-size:15px;
    font-weight:700;
    color:var(--text-100);
}

.stat-icon{
    width:40px;
    height:40px;
    border-radius:12px;
    display:flex;
    align-items:center;
    justify-content:center;
    background:var(--accent-dim);
    color:var(--accent);
    margin-bottom:14px;
}

/* ─────────────────────────────
   Details
───────────────────────────── */
.details-grid{
    display:grid;
    grid-template-columns:repeat(2,minmax(0,1fr));
    gap:16px;
}

@media(max-width:700px){
    .details-grid{
        grid-template-columns:1fr;
    }
}

.detail-card{
    padding:18px;
    border-radius:16px;
    border:1px solid var(--border-subtle);
    background:var(--bg-elevated);
}

.detail-label{
    font-size:11px;
    text-transform:uppercase;
    letter-spacing:.5px;
    color:var(--text-400);
    font-weight:700;
}

.detail-value{
    margin-top:8px;
    font-size:14px;
    font-weight:600;
    color:var(--text-100);
}

/* ─────────────────────────────
   Sidebar
───────────────────────────── */
.side-stack{
    display:flex;
    flex-direction:column;
    gap:18px;
}

/* ─────────────────────────────
   Activity
───────────────────────────── */
.timeline{
    position:relative;
    padding-left:20px;
}

.timeline:before{
    content:'';
    position:absolute;
    left:4px;
    top:0;
    bottom:0;
    width:2px;
    background:var(--border-default);
}

.tl-item{
    position:relative;
    padding-bottom:22px;
}

.tl-dot{
    position:absolute;
    left:-20px;
    top:6px;
    width:10px;
    height:10px;
    border-radius:50%;
    background:var(--accent);
}

.tl-title{
    font-size:13px;
    font-weight:700;
    color:var(--text-100);
}

.tl-date{
    margin-top:4px;
    font-size:12px;
    color:var(--text-400);
}

/* ─────────────────────────────
   Priority
───────────────────────────── */
.priority{
    display:inline-flex;
    align-items:center;
    gap:6px;
    padding:7px 12px;
    border-radius:999px;
    font-size:12px;
    font-weight:700;
}

.priority.low{
    background:#1d9e7515;
    color:#1D9E75;
}

.priority.medium{
    background:#f59e0b15;
    color:#F59E0B;
}

.priority.high{
    background:#ef444415;
    color:#EF4444;
}

/* ─────────────────────────────
   Buttons
───────────────────────────── */
.top-actions{
    display:flex;
    align-items:center;
    gap:10px;
    flex-wrap:wrap;
}
</style>
@endpush

@section('content')

<div class="df-show">

    {{-- TOP --}}
    <div class="ts-top">

        <div>

            <div style="font-size:12px;color:var(--text-300);margin-bottom:4px">

                <a href="{{ route('tenant.tasks.index') }}"
                   style="text-decoration:none;color:inherit">
                    Tasks
                </a>

                › {{ $task->title }}

            </div>

            <div class="page-title">
                Task Overview
            </div>

        </div>

        <div class="top-actions">

            <a href="{{ route('tenant.tasks.edit', $task->id) }}"
               class="btn btn-primary">

                <i class="ti ti-edit"></i>
                Edit Task

            </a>

            <a href="{{ route('tenant.tasks.index') }}"
               class="btn btn-secondary">

                <i class="ti ti-arrow-left"></i>
                Back

            </a>

        </div>

    </div>

    <div class="ts-layout">

        {{-- MAIN --}}
        <div>

            <div class="ts-card">

                {{-- HERO --}}
                <div class="task-hero">

                    <div class="task-badge"
                         style="
                            background:{{ $statusData['bg'] }};
                            color:{{ $statusData['text_color'] }};
                         ">

                        <span class="task-dot"
                              style="background:{{ $statusData['color'] }}"></span>

                        {{ $statusData['label'] }}

                    </div>

                    <div class="task-title-main">
                        {{ $task->title }}
                    </div>

                    @if($task->description)
                    <div class="task-desc">
                        {{ $task->description }}
                    </div>
                    @endif

                </div>

                {{-- BODY --}}
                <div class="ts-body">

                    {{-- STATS --}}
                    <div class="ts-stats">

                        <div class="stat-box">

                            <div class="stat-icon">
                                <i class="ti ti-user"></i>
                            </div>

                            <div class="stat-label">
                                Assigned To
                            </div>

                            <div class="stat-value">
                                {{ $task->assignedTo?->name ?? 'Unassigned' }}
                            </div>

                        </div>

                        <div class="stat-box">

                            <div class="stat-icon">
                                <i class="ti ti-flag"></i>
                            </div>

                            <div class="stat-label">
                                Priority
                            </div>

                            <div class="stat-value">

                                <span class="priority {{ $task->priority }}">
                                    {{ ucfirst($task->priority) }}
                                </span>

                            </div>

                        </div>

                        <div class="stat-box">

                            <div class="stat-icon">
                                <i class="ti ti-calendar"></i>
                            </div>

                            <div class="stat-label">
                                Due Date
                            </div>

                            <div class="stat-value">
                                {{ $task->due_date ? $task->due_date->format('d M Y') : 'Not Set' }}
                            </div>

                        </div>

                        <div class="stat-box">

                            <div class="stat-icon">
                                <i class="ti ti-clock"></i>
                            </div>

                            <div class="stat-label">
                                Created
                            </div>

                            <div class="stat-value">
                                {{ $task->created_at->diffForHumans() }}
                            </div>

                        </div>

                    </div>

                    {{-- DETAILS --}}
                    <div class="mt-4">

                        <div class="ts-title mb-3">
                            Task Details
                        </div>

                        <div class="details-grid">

                            <div class="detail-card">

                                <div class="detail-label">
                                    Created By
                                </div>

                                <div class="detail-value">
                                    {{ $task->creator?->name ?? 'System' }}
                                </div>

                            </div>

                            <div class="detail-card">

                                <div class="detail-label">
                                    Last Updated
                                </div>

                                <div class="detail-value">
                                    {{ $task->updated_at->format('d M Y h:i A') }}
                                </div>

                            </div>

                            <div class="detail-card">

                                <div class="detail-label">
                                    Task Status
                                </div>

                                <div class="detail-value">
                                    {{ $statusData['label'] }}
                                </div>

                            </div>

                            <div class="detail-card">

                                <div class="detail-label">
                                    Task ID
                                </div>

                                <div class="detail-value">
                                    #{{ $task->id }}
                                </div>

                            </div>

                        </div>

                    </div>

                </div>

            </div>

        </div>

        {{-- SIDEBAR --}}
        <div class="side-stack">

            {{-- ACTIVITY --}}
            <div class="ts-card">

                <div class="ts-head">

                    <div>

                        <div class="ts-title">
                            Activity Timeline
                        </div>

                        <div class="ts-sub">
                            Task lifecycle activity
                        </div>

                    </div>

                </div>

                <div class="ts-body">

                    <div class="timeline">

                        <div class="tl-item">

                            <div class="tl-dot"></div>

                            <div class="tl-title">
                                Task Created
                            </div>

                            <div class="tl-date">
                                {{ $task->created_at->format('d M Y h:i A') }}
                            </div>

                        </div>

                        <div class="tl-item">

                            <div class="tl-dot"></div>

                            <div class="tl-title">
                                Task Updated
                            </div>

                            <div class="tl-date">
                                {{ $task->updated_at->format('d M Y h:i A') }}
                            </div>

                        </div>

                        @if($task->due_date)

                        <div class="tl-item">

                            <div class="tl-dot"></div>

                            <div class="tl-title">
                                Due Date Scheduled
                            </div>

                            <div class="tl-date">
                                {{ $task->due_date->format('d M Y h:i A') }}
                            </div>

                        </div>

                        @endif

                    </div>

                </div>

            </div>

            {{-- QUICK ACTION --}}
            <div class="ts-card">

                <div class="ts-head">

                    <div>

                        <div class="ts-title">
                            Quick Actions
                        </div>

                        <div class="ts-sub">
                            Fast task operations
                        </div>

                    </div>

                </div>

                <div class="ts-body">

                    <div style="display:flex;flex-direction:column;gap:10px">

                        <a href="{{ route('tenant.tasks.edit', $task->id) }}"
                           class="btn btn-primary">

                            <i class="ti ti-edit"></i>
                            Edit Task

                        </a>

                        <form method="POST"
                              action="{{ route('tenant.tasks.destroy', $task->id) }}"
                              onsubmit="return confirm('Delete this task?')">

                            @csrf
                            @method('DELETE')

                            <button type="submit"
                                    class="btn btn-danger w-100">

                                <i class="ti ti-trash"></i>
                                Delete Task

                            </button>

                        </form>

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>

@endsection