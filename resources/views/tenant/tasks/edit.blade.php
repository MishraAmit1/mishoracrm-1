@extends('layouts.app')

@section('title', 'Edit Task')
@push('styles')
<style>
@import url('https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=DM+Mono:wght@400;500&display=swap');
@import url('https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.19.0/dist/tabler-icons.min.css');

.df-page{
    font-family:'DM Sans',sans-serif;
}

/* ─────────────────────────────
   Layout
───────────────────────────── */
.df-layout{
    display:grid;
    grid-template-columns:minmax(0,1fr) 280px;
    gap:16px;
    margin-top:20px;
}

@media(max-width:900px){
    .df-layout{
        grid-template-columns:1fr;
    }
}

/* ─────────────────────────────
   Main Card
───────────────────────────── */
.df-main{
    background:var(--bg-surface);
    border:1px solid var(--border-default);
    border-radius:14px;
    overflow:hidden;
}

/* ─────────────────────────────
   Section
───────────────────────────── */
.df-section{
    padding:20px 22px;
    border-bottom:1px solid var(--border-subtle);
}

.df-sec-head{
    display:flex;
    align-items:flex-start;
    gap:11px;
    margin-bottom:16px;
}

.df-sec-icon{
    width:32px;
    height:32px;
    border-radius:8px;
    display:flex;
    align-items:center;
    justify-content:center;
}

.df-sec-title{
    font-size:13px;
    font-weight:600;
    color:var(--text-100);
}

.df-sec-sub{
    font-size:12px;
    color:var(--text-300);
    margin-top:2px;
}

/* ─────────────────────────────
   Grid
───────────────────────────── */
.df-grid{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:14px;
}

.span-full{
    grid-column:1/-1;
}

@media(max-width:640px){

    .df-grid{
        grid-template-columns:1fr;
    }

    .span-full{
        grid-column:1;
    }
}

/* ─────────────────────────────
   Fields
───────────────────────────── */
.df-field{
    display:flex;
    flex-direction:column;
    gap:6px;
}

.df-label{
    font-size:11.5px;
    font-weight:600;
    color:var(--text-200);
    text-transform:uppercase;
    letter-spacing:.5px;
}

.df-req{
    color:#E24B4A;
}

.df-input{
    width:100%;
    padding:10px 12px;
    border-radius:8px;
    border:1.5px solid var(--border-default);
    background:var(--bg-input);
    color:var(--text-100);
    font-size:13.5px;
    outline:none;
    transition:.15s;
}

.df-input:focus{
    border-color:var(--accent);
    box-shadow:0 0 0 3px var(--accent-dim);
}

.df-area{
    resize:vertical;
    min-height:90px;
}

.df-sel{
    cursor:pointer;
}

.df-err{
    font-size:12px;
    color:#E24B4A;
}

.df-hint{
    font-size:12px;
    color:var(--text-400);
}

/* ─────────────────────────────
   Status Picker
───────────────────────────── */
.stage-picker{
    display:flex;
    flex-wrap:wrap;
    gap:7px;
}

.sp-btn{
    border:1.5px solid var(--border-default);
    background:var(--bg-input);
    border-radius:8px;
    padding:9px 12px;
    font-size:12px;
    font-weight:600;
    cursor:pointer;
    display:flex;
    align-items:center;
    gap:6px;
    transition:.15s;
}

.sp-dot{
    width:7px;
    height:7px;
    border-radius:50%;
}

/* ─────────────────────────────
   Footer
───────────────────────────── */
.df-footer{
    padding:16px 22px;
    display:flex;
    align-items:center;
    justify-content:space-between;
    border-top:1px solid var(--border-subtle);
    background:var(--bg-elevated);
}

/* ─────────────────────────────
   Sidebar
───────────────────────────── */
.df-sidebar{
    display:flex;
    flex-direction:column;
    gap:14px;
}

.df-sc{
    background:var(--bg-surface);
    border:1px solid var(--border-default);
    border-radius:14px;
    padding:16px;
}

.df-sc-title{
    font-size:11px;
    text-transform:uppercase;
    letter-spacing:.6px;
    color:var(--text-300);
    font-weight:600;
    margin-bottom:14px;
}

/* ─────────────────────────────
   Preview
───────────────────────────── */
.tp-title{
    font-size:18px;
    font-weight:600;
    color:var(--text-100);
    line-height:1.4;
}

.tp-desc{
    font-size:12.5px;
    color:var(--text-300);
    margin-top:7px;
    line-height:1.5;
}

.tp-badge{
    display:inline-flex;
    align-items:center;
    gap:6px;
    padding:5px 10px;
    border-radius:999px;
    margin-top:12px;
    font-size:12px;
    font-weight:600;
}

/* ─────────────────────────────
   Checklist
───────────────────────────── */
.req-list{
    display:flex;
    flex-direction:column;
    gap:8px;
}

.req-item{
    display:flex;
    align-items:center;
    gap:8px;
    font-size:12.5px;
}

.req-dot{
    width:7px;
    height:7px;
    border-radius:50%;
    background:#E24B4A;
}

.req-dot.ok{
    background:#1D9E75;
}
</style>
@endpush

@php

$taskConfig = config('task_fields');

$cfgStatuses = $taskConfig['stages'];

$requiredFields = collect($taskConfig['fields'])
    ->where('required', true);

$activeStatus =
    old('status', $task->status ?? 'pending');

$activeStatusData =
    $cfgStatuses[$activeStatus];

@endphp

@push('styles')
<style>
@import url('https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap');

.df-page{
    font-family:'DM Sans',sans-serif;
}

/* ─────────────────────────────
   Layout
───────────────────────────── */
.df-layout{
    display:grid;
    grid-template-columns:minmax(0,1fr) 300px;
    gap:18px;
    margin-top:20px;
}

@media(max-width:950px){

    .df-layout{
        grid-template-columns:1fr;
    }
}

/* ─────────────────────────────
   Main Card
───────────────────────────── */
.df-main{
    background:var(--bg-surface);
    border:1px solid var(--border-default);
    border-radius:18px;
    overflow:hidden;
}

/* ─────────────────────────────
   Sidebar
───────────────────────────── */
.df-sidebar{
    display:flex;
    flex-direction:column;
    gap:16px;
}

/* ─────────────────────────────
   Card
───────────────────────────── */
.df-sc{
    background:var(--bg-surface);
    border:1px solid var(--border-default);
    border-radius:16px;
    overflow:hidden;
}

.df-sc-head{
    padding:16px 18px;
    border-bottom:1px solid var(--border-subtle);
}

.df-sc-title{
    font-size:13px;
    font-weight:700;
    color:var(--text-100);
}

.df-sc-sub{
    margin-top:4px;
    font-size:12px;
    color:var(--text-400);
}

.df-sc-body{
    padding:18px;
}

/* ─────────────────────────────
   Footer
───────────────────────────── */
.df-footer{
    padding:18px 22px;
    border-top:1px solid var(--border-subtle);
    background:var(--bg-elevated);

    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:14px;
}

@media(max-width:700px){

    .df-footer{
        flex-direction:column;
        align-items:flex-start;
    }
}

/* ─────────────────────────────
   Preview
───────────────────────────── */
.tp-title{
    font-size:22px;
    font-weight:700;
    line-height:1.4;
    color:var(--text-100);
}

.tp-desc{
    margin-top:10px;
    font-size:13px;
    line-height:1.7;
    color:var(--text-300);
    white-space:pre-wrap;
}

.tp-badge{
    display:inline-flex;
    align-items:center;
    gap:7px;

    padding:8px 14px;
    border-radius:999px;

    margin-top:18px;

    font-size:12px;
    font-weight:700;
}

/* ─────────────────────────────
   Required
───────────────────────────── */
.req-list{
    display:flex;
    flex-direction:column;
    gap:10px;
}

.req-item{
    display:flex;
    align-items:center;
    gap:10px;

    font-size:13px;
    color:var(--text-200);
}

.req-dot{
    width:9px;
    height:9px;
    border-radius:50%;
    background:#ef4444;
    transition:.2s ease;
}

.req-dot.ok{
    background:#10b981;
}

/* ─────────────────────────────
   Timeline
───────────────────────────── */
.timeline{
    display:flex;
    flex-direction:column;
    gap:16px;
}

.tl-item{
    display:flex;
    gap:12px;
}

.tl-dot{
    width:10px;
    height:10px;
    border-radius:50%;
    background:var(--accent);
    margin-top:6px;
}

.tl-title{
    font-size:13px;
    font-weight:700;
    color:var(--text-100);
}

.tl-date{
    margin-top:3px;
    font-size:12px;
    color:var(--text-400);
}
</style>
@endpush

@section('content')

<div class="df-page">

    {{-- HEADER --}}
    <div class="page-head">

        <div>

            <div style="
                font-size:12px;
                color:var(--text-300);
                margin-bottom:4px
            ">

                <a href="{{ route('tenant.tasks.index') }}"
                   style="text-decoration:none;color:inherit">

                    Tasks

                </a>

                ›

                <a href="{{ route('tenant.tasks.show', $task->id) }}"
                   style="text-decoration:none;color:inherit">

                    {{ Str::limit($task->title, 30) }}

                </a>

                › Edit

            </div>

            <div class="page-title">
                Edit Task
            </div>

        </div>

        <div style="display:flex;gap:10px">

            <a href="{{ route('tenant.tasks.show', $task->id) }}"
               class="btn btn-secondary">

                <i class="ti ti-eye"></i>

                View

            </a>

            <a href="{{ route('tenant.tasks.index') }}"
               class="btn btn-secondary">

                <i class="ti ti-arrow-left"></i>

                Back

            </a>

        </div>

    </div>

    {{-- FORM --}}
    <form method="POST"
          action="{{ route('tenant.tasks.update', $task->id) }}"
          id="taskForm">

        @csrf
        @method('PUT')

        <div class="df-layout">

            {{-- MAIN --}}
            <div class="df-main">

                @include('tenant.tasks._form', [

                    'taskConfig' => $taskConfig,

                    'staffList' => $staffList,

                    'model' => $task,

                    'isEdit' => true,

                    'contacts' => $contacts,

                    'leads' => $leads,

                    'deals' => $deals,

                ])

                {{-- FOOTER --}}
                <div class="df-footer">

                    <div style="
                        font-size:12px;
                        color:var(--text-300)
                    ">
                        Last updated
                        {{ $task->updated_at->diffForHumans() }}
                    </div>

                    <div style="
                        display:flex;
                        align-items:center;
                        gap:10px;
                    ">

                        <a href="{{ route('tenant.tasks.show', $task->id) }}"
                           class="btn btn-secondary">

                            Cancel

                        </a>

                        <button type="submit"
                                class="btn btn-primary"
                                id="submitBtn">

                            <i class="ti ti-device-floppy"></i>

                            <span id="submitText">
                                Update Task
                            </span>

                        </button>

                    </div>

                </div>

            </div>

            {{-- SIDEBAR --}}
            <div class="df-sidebar">

                {{-- LIVE PREVIEW --}}
                <div class="df-sc">

                    <div class="df-sc-head">

                        <div class="df-sc-title">
                            Live Preview
                        </div>

                        <div class="df-sc-sub">
                            Real-time task preview
                        </div>

                    </div>

                    <div class="df-sc-body">

                        <div class="tp-title"
                             id="previewTitle">

                            {{ $task->title }}

                        </div>

                        <div class="tp-desc"
                             id="previewDesc">

                            {{ $task->description ?: 'No description added' }}

                        </div>

                        <div class="tp-badge"
                             id="previewStatus"
                             style="
                                background:{{ $activeStatusData['bg'] }};
                                color:{{ $activeStatusData['text_color'] }};
                             ">

                            <span style="
                                width:7px;
                                height:7px;
                                border-radius:50%;
                                background:{{ $activeStatusData['color'] }};
                                display:inline-block;
                            "></span>

                            {{ $activeStatusData['label'] }}

                        </div>

                    </div>

                </div>

                {{-- REQUIRED --}}
                <div class="df-sc">

                    <div class="df-sc-head">

                        <div class="df-sc-title">
                            Required Fields
                        </div>

                        <div class="df-sc-sub">
                            Validation tracker
                        </div>

                    </div>

                    <div class="df-sc-body">

                        <div class="req-list">

                            @foreach($requiredFields as $field)

                            <div class="req-item">

                                <div class="req-dot"
                                     id="dot_{{ $field['key'] }}"></div>

                                {{ $field['label'] }}

                            </div>

                            @endforeach

                        </div>

                    </div>

                </div>

                {{-- ACTIVITY --}}
                <div class="df-sc">

                    <div class="df-sc-head">

                        <div class="df-sc-title">
                            Activity
                        </div>

                        <div class="df-sc-sub">
                            Task history
                        </div>

                    </div>

                    <div class="df-sc-body">

                        <div class="timeline">

                            <div class="tl-item">

                                <div class="tl-dot"></div>

                                <div>

                                    <div class="tl-title">
                                        Task Created
                                    </div>

                                    <div class="tl-date">
                                        {{ $task->created_at->format('d M Y h:i A') }}
                                    </div>

                                </div>

                            </div>

                            <div class="tl-item">

                                <div class="tl-dot"></div>

                                <div>

                                    <div class="tl-title">
                                        Last Updated
                                    </div>

                                    <div class="tl-date">
                                        {{ $task->updated_at->format('d M Y h:i A') }}
                                    </div>

                                </div>

                            </div>

                        </div>

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

    const statuses = @json(config('task_fields.stages'));

    /*
    |--------------------------------------------------------------------------
    | Live Preview
    |--------------------------------------------------------------------------
    */
    window.updateTaskPreview = function(){

        const title =
            document.getElementById('df_title')?.value || '';

        const desc =
            document.getElementById('df_description')?.value || '';

        const status =
            document.getElementById('statusHidden')?.value || 'pending';

        const stg = statuses[status];

        /*
        |--------------------------------------------------------------------------
        | Title
        |--------------------------------------------------------------------------
        */
        document.getElementById('previewTitle').textContent =
            title || 'Enter task title...';

        /*
        |--------------------------------------------------------------------------
        | Description
        |--------------------------------------------------------------------------
        */
        document.getElementById('previewDesc').textContent =
            desc || 'No description added';

        /*
        |--------------------------------------------------------------------------
        | Badge
        |--------------------------------------------------------------------------
        */
        const badge =
            document.getElementById('previewStatus');

        badge.style.background = stg.bg;

        badge.style.color = stg.text_color;

        badge.innerHTML = `
            <span style="
                width:7px;
                height:7px;
                border-radius:50%;
                background:${stg.color};
                display:inline-block;
            "></span>

            ${stg.label}
        `;

        syncRequiredDots();
    };

    /*
    |--------------------------------------------------------------------------
    | Required Fields
    |--------------------------------------------------------------------------
    */
    const reqKeys = @json(
        collect(config('task_fields.fields'))
            ->where('required', true)
            ->pluck('key')
            ->values()
    );

    function syncRequiredDots(){

        reqKeys.forEach(key => {

            const el =
                document.querySelector(`[name="${key}"]`);

            const dot =
                document.getElementById(`dot_${key}`);

            if(!el || !dot) return;

            const filled =
                el.value &&
                el.value.toString().trim() !== '';

            dot.classList.toggle('ok', filled);

        });
    }

    /*
    |--------------------------------------------------------------------------
    | Input Events
    |--------------------------------------------------------------------------
    */
    ['df_title','df_description'].forEach(id => {

        const el =
            document.getElementById(id);

        el?.addEventListener('input', updateTaskPreview);

    });

    /*
    |--------------------------------------------------------------------------
    | Submit Loading
    |--------------------------------------------------------------------------
    */
    document.getElementById('taskForm')
        .addEventListener('submit', function(){

            document.getElementById('submitText')
                .textContent = 'Updating...';

            document.getElementById('submitBtn')
                .disabled = true;
        });

    updateTaskPreview();

})();
</script>
@endpush