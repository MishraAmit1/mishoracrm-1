@extends('layouts.app')
@section('title', 'Custom Fields')

@push('styles')
<style>
.module-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(280px,1fr)); gap:16px; }

.module-card {
    background:var(--bg-surface); border:1px solid var(--border-default);
    border-radius:var(--r-lg); overflow:hidden;
    transition:border-color .15s, transform .15s;
    text-decoration:none; display:block;
}
.module-card:hover { border-color:var(--accent); transform:translateY(-2px); }

.mc-head {
    padding:20px; display:flex; align-items:center; gap:14px;
    border-bottom:1px solid var(--border-subtle);
}
.mc-icon {
    width:48px; height:48px; border-radius:var(--r-md);
    display:flex; align-items:center; justify-content:center;
    font-size:22px; flex-shrink:0;
}
.mc-title { font-size:15px; font-weight:700; color:var(--text-100); margin-bottom:2px; }
.mc-sub   { font-size:12.5px; color:var(--text-300); }

.mc-stats {
    display:grid; grid-template-columns:repeat(3,1fr);
    border-bottom:1px solid var(--border-subtle);
}
.mc-stat { padding:14px; text-align:center; border-right:1px solid var(--border-subtle); }
.mc-stat:last-child { border-right:none; }
.mc-stat-num { font-size:20px; font-weight:800; font-family:var(--mono); color:var(--text-100); }
.mc-stat-lbl { font-size:10.5px; color:var(--text-400); font-weight:600; text-transform:uppercase; letter-spacing:.3px; margin-top:2px; }

.mc-foot {
    padding:12px 20px; display:flex; align-items:center; justify-content:space-between;
    font-size:13px;
}
.mc-action { display:flex; align-items:center; gap:5px; color:var(--accent); font-weight:600; }
.mc-action svg { width:14px; height:14px; }

/* Info box */
.info-box {
    background:var(--accent-dim); border:1.5px solid rgba(99,120,255,.2);
    border-radius:var(--r-lg); padding:20px 24px; margin-bottom:24px;
    display:flex; gap:16px; align-items:flex-start;
}
.info-box-icon { font-size:24px; flex-shrink:0; margin-top:2px; }
.info-box-title { font-size:14px; font-weight:700; color:var(--text-100); margin-bottom:6px; }
.info-box-steps { display:flex; flex-direction:column; gap:4px; }
.info-step { font-size:13px; color:var(--text-200); display:flex; gap:8px; align-items:flex-start; }
.step-num { width:20px; height:20px; border-radius:50%; background:var(--accent); color:#fff; font-size:10px; font-weight:700; display:flex; align-items:center; justify-content:center; flex-shrink:0; margin-top:1px; }
</style>
@endpush

@section('content')

@php
    $moduleIcons = [
        'lead'      => ['icon'=>'👤', 'bg'=>'var(--accent-dim)',  'desc'=>'Leads module custom fields'],
        'contact'   => ['icon'=>'📒', 'bg'=>'var(--purple-dim)', 'desc'=>'Contacts module custom fields'],
        'deal'      => ['icon'=>'💼', 'bg'=>'var(--amber-dim)',  'desc'=>'Deals module custom fields'],
        'quotation' => ['icon'=>'📄', 'bg'=>'var(--green-dim)',  'desc'=>'Quotations module custom fields'],
        'task'      => ['icon'=>'✅', 'bg'=>'var(--red-dim)',    'desc'=>'Tasks module custom fields'],
    ];
@endphp

<div class="page-head">
    <div>
        <div class="page-title">Custom Fields</div>
        <div class="page-sub">Add custom fields to any module as per your business needs</div>
    </div>
</div>

{{-- How it works --}}
<div class="info-box">
    <div class="info-box-icon">💡</div>
    <div>
        <div class="info-box-title">How Custom Fields Work</div>
        <div class="info-box-steps">
            <div class="info-step">
                <div class="step-num">1</div>
                <span>Kisi bhi module pe click karo (Lead, Contact, Deal, etc.)</span>
            </div>
            <div class="info-step">
                <div class="step-num">2</div>
                <span>Apni zaroorat ke hisaab se fields add karo — Text, Dropdown, Date, Checkbox etc.</span>
            </div>
            <div class="info-step">
                <div class="step-num">3</div>
                <span>Fields automatically uss module ke create/edit forms mein dikhne lagenge</span>
            </div>
            <div class="info-step">
                <div class="step-num">4</div>
                <span>Data har record ke saath save hoga aur detail page pe dikhega</span>
            </div>
        </div>
    </div>
</div>

{{-- Module cards --}}
<div class="module-grid">
    @foreach($modules as $key => $mod)
    @php
        $mi  = $moduleIcons[$key] ?? ['icon'=>'📋','bg'=>'var(--bg-elevated)','desc'=>''];
        $st  = $stats[$key];
    @endphp
    <a href="{{ route('tenant.custom-fields.module', $key) }}" class="module-card">

        <div class="mc-head">
            <div class="mc-icon" style="background:{{ $mi['bg'] }}">{{ $mi['icon'] }}</div>
            <div>
                <div class="mc-title">{{ $mod['label'] }}</div>
                <div class="mc-sub">{{ $mi['desc'] }}</div>
            </div>
        </div>

        <div class="mc-stats">
            <div class="mc-stat">
                <div class="mc-stat-num">{{ $st['total'] }}</div>
                <div class="mc-stat-lbl">Total</div>
            </div>
            <div class="mc-stat">
                <div class="mc-stat-num" style="color:var(--green)">{{ $st['active'] }}</div>
                <div class="mc-stat-lbl">Active</div>
            </div>
            <div class="mc-stat">
                <div class="mc-stat-num" style="color:var(--red)">{{ $st['required'] }}</div>
                <div class="mc-stat-lbl">Required</div>
            </div>
        </div>

        <div class="mc-foot">
            <span style="font-size:12.5px;color:var(--text-300)">
                {{ $st['total'] > 0 ? 'Manage fields →' : 'No fields yet' }}
            </span>
            <div class="mc-action">
                Add Field
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                </svg>
            </div>
        </div>
    </a>
    @endforeach
</div>

@endsection