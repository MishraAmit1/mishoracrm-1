@extends('layouts.app')
@section('title', 'Field Manager')

@push('styles')
<style>
/* ── Module grid ─────────────────────────────────────────────────── */
.module-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(300px,1fr)); gap:16px; }

.module-card {
    background:var(--bg-surface); border:1px solid var(--border-default);
    border-radius:var(--r-lg); overflow:hidden; text-decoration:none;
    display:flex; flex-direction:column;
    transition:border-color .15s var(--ease), transform .15s var(--ease);
}
.module-card:hover { border-color:var(--accent); transform:translateY(-2px); }

/* Card top */
.mc-top {
    padding:20px 20px 16px;
    display:flex; align-items:flex-start; gap:14px;
}
.mc-icon {
    width:48px; height:48px; border-radius:var(--r-md);
    display:flex; align-items:center; justify-content:center;
    font-size:22px; flex-shrink:0;
}
.mc-title { font-size:15px; font-weight:800; color:var(--text-100); margin-bottom:3px; }
.mc-desc  { font-size:12.5px; color:var(--text-300); line-height:1.5; }

/* Stats row */
.mc-stats {
    display:grid; grid-template-columns:repeat(3,1fr);
    border-top:1px solid var(--border-subtle);
    border-bottom:1px solid var(--border-subtle);
}
.mc-stat { padding:12px 10px; text-align:center; }
.mc-stat + .mc-stat { border-left:1px solid var(--border-subtle); }
.mc-stat-num { font-size:20px; font-weight:800; font-family:var(--mono); letter-spacing:-.5px; }
.mc-stat-lbl { font-size:10.5px; color:var(--text-400); font-weight:600; text-transform:uppercase; letter-spacing:.3px; margin-top:2px; }

/* Progress bar */
.mc-progress { padding:14px 20px 16px; }
.prog-track  { height:5px; background:var(--border-subtle); border-radius:3px; overflow:hidden; margin-bottom:6px; }
.prog-fill   { height:100%; border-radius:3px; background:var(--accent); transition:width .6s var(--ease); }
.prog-label  { display:flex; justify-content:space-between; font-size:11.5px; color:var(--text-400); }

/* Footer */
.mc-footer {
    padding:12px 20px; margin-top:auto;
    display:flex; align-items:center; justify-content:space-between;
    background:var(--bg-elevated);
    border-top:1px solid var(--border-subtle);
}
.mc-link { font-size:13px; font-weight:600; color:var(--accent); display:flex; align-items:center; gap:5px; }
.mc-link svg { width:14px; height:14px; }

/* Info banner */
.info-banner {
    background:var(--accent-dim); border:1.5px solid rgba(99,120,255,.2);
    border-radius:var(--r-lg); padding:18px 22px; margin-bottom:24px;
    display:grid; grid-template-columns:auto 1fr auto; gap:16px; align-items:center;
}
@media(max-width:700px) { .info-banner { grid-template-columns:1fr; } }
.ib-icon  { font-size:28px; }
.ib-title { font-size:14px; font-weight:700; color:var(--text-100); margin-bottom:4px; }
.ib-desc  { font-size:13px; color:var(--text-200); line-height:1.6; }

/* Summary bar */
.summary-bar {
    display:flex; gap:12px; flex-wrap:wrap; margin-bottom:24px;
}
.sb-item {
    display:flex; align-items:center; gap:8px;
    padding:10px 16px; background:var(--bg-surface);
    border:1px solid var(--border-default); border-radius:var(--r-md);
    font-size:13px; color:var(--text-200);
}
.sb-num { font-size:18px; font-weight:800; font-family:var(--mono); }

/* Quick actions */
.quick-actions { display:grid; grid-template-columns:repeat(3,1fr); gap:10px; margin-bottom:24px; }
@media(max-width:700px) { .quick-actions { grid-template-columns:1fr; } }
.qa-card {
    padding:14px 16px; background:var(--bg-surface);
    border:1.5px solid var(--border-default); border-radius:var(--r-md);
    text-decoration:none; transition:all .15s;
    display:flex; align-items:center; gap:12px;
}
.qa-card:hover { border-color:var(--accent); background:var(--accent-dim); }
.qa-icon { font-size:20px; flex-shrink:0; }
.qa-label { font-size:13.5px; font-weight:700; color:var(--text-100); }
.qa-sub   { font-size:12px; color:var(--text-300); margin-top:1px; }
</style>
@endpush

@section('content')

@php
    $moduleIcons = [
        'lead'      => ['icon'=>'👤','bg'=>'var(--accent-dim)',  'desc'=>'Leads form ke liye fields manage karo'],
        'contact'   => ['icon'=>'📒','bg'=>'var(--purple-dim)', 'desc'=>'Contacts form ke liye fields manage karo'],
        'deal'      => ['icon'=>'💼','bg'=>'var(--amber-dim)',  'desc'=>'Deals form ke liye fields manage karo'],
        'quotation' => ['icon'=>'📄','bg'=>'var(--green-dim)',  'desc'=>'Quotation form ke liye fields manage karo'],
        'task'      => ['icon'=>'✅','bg'=>'var(--red-dim)',    'desc'=>'Tasks form ke liye fields manage karo'],
    ];

    // Total stats across all modules
    $totalActive    = collect($stats)->sum('active');
    $totalFields    = collect($stats)->sum('total');
    $totalAvailable = collect($stats)->sum('available');
@endphp

<div class="page-head">
    <div>
        <div class="page-title">Field Manager</div>
        <div class="page-sub">Module ke forms mein kaunse fields dikhein — yahan manage karo</div>
    </div>
    <div class="page-actions">
        <a href="{{ route('tenant.custom-fields.index') }}" class="btn btn-secondary">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="width:15px;height:15px">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.43l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z"/>
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
            Custom Fields
        </a>
    </div>
</div>

{{-- Info banner --}}
<div class="info-banner">
    <div class="ib-icon">🎛️</div>
    <div>
        <div class="ib-title">Apne forms customize karo</div>
        <div class="ib-desc">
            Har module ke liye choose karo ki kaunse fields form mein dikhein —
            Global templates mein se select karo ya khud banao.
            Fields ko active/inactive karo, required mark karo aur order change karo.
        </div>
    </div>
    <a href="{{ route('tenant.custom-fields.index') }}" class="btn btn-primary" style="white-space:nowrap">
        Manage Custom Fields →
    </a>
</div>

{{-- Summary stats --}}
<div class="summary-bar">
    @php
        $summaryItems = [
            ['num'=>$totalActive,    'label'=>'Active Fields',     'color'=>'var(--green)'],
            ['num'=>$totalFields,    'label'=>'Total Assigned',    'color'=>'var(--accent)'],
            ['num'=>$totalAvailable, 'label'=>'Global Templates',  'color'=>'var(--purple)'],
            ['num'=>count($modules), 'label'=>'Modules',           'color'=>'var(--amber)'],
        ];
    @endphp
    @foreach($summaryItems as $s)
    <div class="sb-item">
        <div class="sb-num" style="color:{{ $s['color'] }}">{{ $s['num'] }}</div>
        <div style="font-size:12.5px;color:var(--text-300)">{{ $s['label'] }}</div>
    </div>
    @endforeach
</div>

{{-- Quick actions --}}
<div class="quick-actions">
    @php
        $quickActions = [
            ['href'=>route('tenant.tenant-fields.module','lead'),      'icon'=>'👤','label'=>'Leads Fields',    'sub'=>'Lead form mein fields manage karo'],
            ['href'=>route('tenant.tenant-fields.module','contact'),   'icon'=>'📒','label'=>'Contact Fields',  'sub'=>'Contact form customize karo'],
            ['href'=>route('tenant.tenant-fields.module','deal'),      'icon'=>'💼','label'=>'Deal Fields',     'sub'=>'Deal form ke fields set karo'],
        ];
    @endphp
    @foreach($quickActions as $qa)
    <a href="{{ $qa['href'] }}" class="qa-card">
        <div class="qa-icon">{{ $qa['icon'] }}</div>
        <div>
            <div class="qa-label">{{ $qa['label'] }}</div>
            <div class="qa-sub">{{ $qa['sub'] }}</div>
        </div>
    </a>
    @endforeach
</div>

{{-- Module cards --}}
<div style="font-size:13.5px;font-weight:700;color:var(--text-100);margin-bottom:14px">
    All Modules
</div>
<div class="module-grid">
    @foreach($modules as $key => $mod)
    @php
        $mi  = $moduleIcons[$key] ?? ['icon'=>'📋','bg'=>'var(--bg-elevated)','desc'=>''];
        $st  = $stats[$key] ?? ['active'=>0,'total'=>0,'available'=>0];
        $pct = $st['available'] > 0 ? round(($st['active'] / max($st['available'],1)) * 100) : 0;
    @endphp
    <a href="{{ route('tenant.tenant-fields.module', $key) }}" class="module-card">

        {{-- Top --}}
        <div class="mc-top">
            <div class="mc-icon" style="background:{{ $mi['bg'] }}">{{ $mi['icon'] }}</div>
            <div>
                <div class="mc-title">{{ $mod['label'] }}</div>
                <div class="mc-desc">{{ $mi['desc'] }}</div>
            </div>
        </div>

        {{-- Stats --}}
        <div class="mc-stats">
            <div class="mc-stat">
                <div class="mc-stat-num" style="color:var(--green)">{{ $st['active'] }}</div>
                <div class="mc-stat-lbl">Active</div>
            </div>
            <div class="mc-stat">
                <div class="mc-stat-num" style="color:var(--accent)">{{ $st['total'] }}</div>
                <div class="mc-stat-lbl">Assigned</div>
            </div>
            <div class="mc-stat">
                <div class="mc-stat-num" style="color:var(--purple)">{{ $st['available'] }}</div>
                <div class="mc-stat-lbl">Available</div>
            </div>
        </div>

        {{-- Progress --}}
        <div class="mc-progress">
            <div class="prog-track">
                <div class="prog-fill" style="width:{{ $pct }}%"></div>
            </div>
            <div class="prog-label">
                <span>{{ $st['active'] }} of {{ $st['available'] }} fields active</span>
                <span style="font-family:var(--mono);font-weight:600;color:var(--accent)">{{ $pct }}%</span>
            </div>
        </div>

        {{-- Footer --}}
        <div class="mc-footer">
            @if($st['total'] === 0)
            <span style="font-size:12.5px;color:var(--text-400)">No fields configured yet</span>
            @else
            <span style="font-size:12.5px;color:var(--text-300)">
                {{ $st['active'] }} active
                @if($st['total'] - $st['active'] > 0)
                · {{ $st['total'] - $st['active'] }} disabled
                @endif
            </span>
            @endif
            <span class="mc-link">
                Manage
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3"/>
                </svg>
            </span>
        </div>

    </a>
    @endforeach
</div>

{{-- How it works --}}
<div style="margin-top:24px;background:var(--bg-elevated);border:1px solid var(--border-subtle);border-radius:var(--r-lg);padding:20px 24px">
    <div style="font-size:14px;font-weight:700;color:var(--text-100);margin-bottom:14px">
        💡 Kaise kaam karta hai?
    </div>
    <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px">
        @foreach([
            ['num'=>'1','title'=>'Module Choose Karo','desc'=>'Upar kisi bhi module pe click karo jisme fields add karne hain'],
            ['num'=>'2','title'=>'Fields Add Karo','desc'=>'Global templates mein se choose karo ya apna custom field banao'],
            ['num'=>'3','title'=>'Configure Karo','desc'=>'Active/Required/List/Filter toggle karo aur order set karo'],
            ['num'=>'4','title'=>'Auto Apply Hoga','desc'=>'Fields automatically us module ke create/edit forms mein dikhenge'],
        ] as $step)
        <div style="display:flex;gap:10px;align-items:flex-start">
            <div style="width:24px;height:24px;border-radius:50%;background:var(--accent);color:#fff;font-size:11px;font-weight:800;display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:1px">
                {{ $step['num'] }}
            </div>
            <div>
                <div style="font-size:13px;font-weight:700;color:var(--text-100);margin-bottom:3px">{{ $step['title'] }}</div>
                <div style="font-size:12px;color:var(--text-300);line-height:1.5">{{ $step['desc'] }}</div>
            </div>
        </div>
        @endforeach
    </div>
</div>

@endsection