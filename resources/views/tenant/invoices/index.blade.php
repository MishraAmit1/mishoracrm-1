@extends('layouts.app')
@section('title', 'Invoices')

@push('styles')
<style>
.stat-row { display:grid; grid-template-columns:repeat(4,1fr); gap:12px; margin-bottom:20px; }
@media(max-width:900px) { .stat-row { grid-template-columns:repeat(2,1fr); } }
.stat-card { background:var(--bg-surface); border:1px solid var(--border-default); border-radius:var(--r-md); padding:16px 18px; }
.stat-val   { font-size:22px; font-weight:800; font-family:var(--mono); color:var(--text-100); }
.stat-lbl   { font-size:12px; color:var(--text-400); margin-top:3px; }

.status-tabs { display:flex; gap:4px; flex-wrap:wrap; margin-bottom:16px; }
.s-tab { padding:7px 14px; border-radius:var(--r-sm); font-size:12.5px; font-weight:600; text-decoration:none; color:var(--text-300); border:1.5px solid transparent; transition:all .15s; }
.s-tab:hover { color:var(--text-100); background:var(--bg-elevated); }
.s-tab.active { background:var(--accent-dim); color:var(--accent); border-color:rgba(var(--accent-rgb),.25); }

.filter-bar { display:flex; gap:10px; flex-wrap:wrap; margin-bottom:16px; }
.fi { padding:8px 12px; height:36px; background:var(--bg-input); border:1.5px solid var(--border-default); border-radius:var(--r-sm); color:var(--text-100); font-family:var(--font); font-size:13px; outline:none; }
.fi:focus { border-color:var(--accent); }
.fi-search { flex:1; min-width:200px; }

.data-table { width:100%; border-collapse:collapse; }
.data-table th { padding:10px 16px; text-align:left; font-size:11px; font-weight:700; color:var(--text-400); text-transform:uppercase; letter-spacing:.5px; border-bottom:1px solid var(--border-subtle); white-space:nowrap; }
.data-table td { padding:13px 16px; font-size:13.5px; color:var(--text-100); border-bottom:1px solid var(--border-subtle); }
.data-table tr:last-child td { border-bottom:none; }
.data-table tbody tr:hover td { background:var(--bg-elevated); cursor:pointer; }

.badge { font-size:11.5px; font-weight:700; padding:3px 10px; border-radius:20px; }
.pay-mini { height:4px; background:var(--bg-elevated); border-radius:2px; margin-top:4px; overflow:hidden; }
.pay-mini-fill { height:100%; border-radius:2px; background:var(--green); }

.pag { display:flex; align-items:center; justify-content:space-between; padding:14px 20px; border-top:1px solid var(--border-subtle); font-size:13px; color:var(--text-300); }
.pag-links { display:flex; gap:4px; }
.pg-btn { padding:5px 10px; border-radius:var(--r-sm); border:1px solid var(--border-default); color:var(--text-200); text-decoration:none; font-size:13px; }
.pg-btn:hover { border-color:var(--accent); color:var(--accent); }
.pg-btn.active { background:var(--accent); border-color:var(--accent); color:#fff; }
.pg-btn.disabled { opacity:.4; pointer-events:none; }

.empty-state { padding:60px 20px; text-align:center; color:var(--text-300); font-size:13px; }
</style>
@endpush

@section('content')

@php
    $currentStatus = request('status', '');
    $statusCfg = [
        'draft'   => ['label'=>'Draft',   'color'=>'amber',  'bg'=>'amber-dim'],
        'sent'    => ['label'=>'Sent',    'color'=>'accent', 'bg'=>'accent-dim'],
        'paid'    => ['label'=>'Paid',    'color'=>'green',  'bg'=>'green-dim'],
        'partial' => ['label'=>'Partial', 'color'=>'purple', 'bg'=>'purple-dim'],
        'overdue' => ['label'=>'Overdue', 'color'=>'red',    'bg'=>'red-dim'],
    ];
@endphp

<div class="page-head">
    <div>
        <div class="page-title">Invoices</div>
        <div class="page-sub">Manage billing & payments</div>
    </div>
    <a href="{{ route('tenant.invoices.create') }}" class="btn btn-primary">
        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="width:15px;height:15px">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
        </svg>
        New Invoice
    </a>
</div>

{{-- Stats --}}
<div class="stat-row">
    <div class="stat-card">
        <div class="stat-val" style="color:var(--green)">₹{{ number_format($revenue['total_paid'], 2) }}</div>
        <div class="stat-lbl">Total Collected</div>
    </div>
    <div class="stat-card">
        <div class="stat-val" style="color:var(--accent)">₹{{ number_format($revenue['total_pending'], 2) }}</div>
        <div class="stat-lbl">Pending</div>
    </div>
    <div class="stat-card">
        <div class="stat-val" style="color:var(--red)">₹{{ number_format($revenue['total_overdue'], 2) }}</div>
        <div class="stat-lbl">Overdue</div>
    </div>
    <div class="stat-card">
        <div class="stat-val">{{ $counts['all'] }}</div>
        <div class="stat-lbl">Total Invoices</div>
    </div>
</div>

{{-- Status tabs --}}
<div class="status-tabs">
    <a href="{{ route('tenant.invoices.index') }}"
       class="s-tab {{ !$currentStatus ? 'active':'' }}">
       All ({{ $counts['all'] }})
    </a>
    @foreach($statusCfg as $key => $cfg)
    <a href="{{ route('tenant.invoices.index', ['status'=>$key]) }}"
       class="s-tab {{ $currentStatus === $key ? 'active':'' }}"
       style="{{ $currentStatus === $key ? 'background:var(--'.$cfg['bg'].');color:var(--'.$cfg['color'].');border-color:rgba(0,0,0,.08)':'' }}">
        {{ $cfg['label'] }}
        <span style="opacity:.65">({{ $counts[$key] ?? 0 }})</span>
    </a>
    @endforeach
</div>

{{-- Filters --}}
<form method="GET" action="{{ route('tenant.invoices.index') }}" id="filterForm">
    @if(request('status'))<input type="hidden" name="status" value="{{ request('status') }}"/>@endif
    <div class="filter-bar">
        <input type="text" name="search" class="fi fi-search"
               placeholder="Search invoice # or contact..."
               value="{{ request('search') }}"
               onchange="document.getElementById('filterForm').submit()"/>
        <input type="date" name="date_from" class="fi" value="{{ request('date_from') }}"
               onchange="this.form.submit()"/>
        <input type="date" name="date_to" class="fi" value="{{ request('date_to') }}"
               onchange="this.form.submit()"/>
        @if(request()->hasAny(['search','date_from','date_to']))
        <a href="{{ route('tenant.invoices.index', request('status') ? ['status'=>request('status')] : []) }}"
           class="btn btn-secondary">Clear</a>
        @endif
    </div>
</form>

{{-- Table --}}
<div style="background:var(--bg-surface);border:1px solid var(--border-default);border-radius:var(--r-lg);overflow:hidden">
    @if($invoices->isEmpty())
    <div class="empty-state">
        <div style="font-size:32px;margin-bottom:10px">🧾</div>
        <div style="font-size:15px;font-weight:600;color:var(--text-200);margin-bottom:6px">No invoices yet</div>
        <div>Create your first invoice to get started</div>
        <a href="{{ route('tenant.invoices.create') }}" class="btn btn-primary" style="display:inline-flex;margin-top:14px">
            New Invoice
        </a>
    </div>
    @else
    <div style="overflow-x:auto">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Invoice #</th>
                    <th>Contact</th>
                    <th>Date</th>
                    <th>Due Date</th>
                    <th style="text-align:right">Amount</th>
                    <th style="text-align:right">Paid</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoices as $inv)
                @php
                    $sc      = $statusCfg[$inv->status] ?? ['label'=>ucfirst($inv->status),'color'=>'accent','bg'=>'accent-dim'];
                    $isOD    = $inv->isOverdue();
                    $paidPct = $inv->total > 0 ? min(100, round(($inv->paid_amount / $inv->total) * 100)) : 0;
                @endphp
                <tr onclick="window.location='{{ route('tenant.invoices.show', $inv->id) }}'">
                    <td data-label="Invoice #">
                        <div style="font-family:var(--mono);font-size:13px;font-weight:700;color:var(--accent)">
                            {{ $inv->number }}
                        </div>
                        @if($inv->quotation)
                        <div style="font-size:11px;color:var(--text-400);margin-top:2px">
                            From {{ $inv->quotation->number }}
                        </div>
                        @endif
                    </td>
                    <td data-label="Contact">
                        <div style="font-weight:600">{{ $inv->contact?->name ?? '—' }}</div>
                        @if($inv->contact?->company)
                        <div style="font-size:12px;color:var(--text-300)">{{ $inv->contact->company }}</div>
                        @endif
                    </td>
                    <td style="color:var(--text-200);font-size:13px" data-label="Date">
                        {{ $inv->date?->format('d M Y') ?? '—' }}
                    </td>
                    <td style="color:{{ $isOD ? 'var(--red)':'var(--text-200)' }};font-size:13px;font-weight:{{ $isOD ? '700':'400' }}" data-label="Due Date">
                        {{ $inv->due_date?->format('d M Y') ?? '—' }}
                        @if($isOD)
                        <div style="font-size:11px">Overdue</div>
                        @endif
                    </td>
                    <td style="text-align:right;font-family:var(--mono);font-weight:700" data-label="Amount">
                        ₹{{ number_format($inv->total, 2) }}
                    </td>
                    <td style="text-align:right;min-width:100px" data-label="Paid">
                        @if($inv->paid_amount > 0)
                        <div style="font-family:var(--mono);font-size:13px;color:var(--green);font-weight:600">
                            ₹{{ number_format($inv->paid_amount, 2) }}
                        </div>
                        <div class="pay-mini">
                            <div class="pay-mini-fill" style="width:{{ $paidPct }}%"></div>
                        </div>
                        @else
                        <span style="color:var(--text-400);font-size:12.5px">—</span>
                        @endif
                    </td>
                    <td data-label="Status">
                        <span class="badge" style="background:var(--{{ $sc['bg'] }});color:var(--{{ $sc['color'] }})">
                            {{ $isOD ? 'Overdue' : $sc['label'] }}
                        </span>
                    </td>
                    <td onclick="event.stopPropagation()">
                        <div style="display:flex;gap:4px">
                            <a href="{{ route('tenant.invoices.pdf', $inv->id) }}" target="_blank"
                               class="btn btn-secondary btn-sm" title="PDF">📄</a>
                            @if(!$inv->isPaid())
                            <a href="{{ route('tenant.invoices.edit', $inv->id) }}"
                               class="btn btn-secondary btn-sm" title="Edit">✎</a>
                            @endif
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if($invoices->hasPages())
    <div class="pag">
        <span>{{ $invoices->firstItem() }}–{{ $invoices->lastItem() }} of {{ $invoices->total() }}</span>
        <div class="pag-links">
            <a href="{{ $invoices->previousPageUrl() ?? '#' }}"
               class="pg-btn {{ !$invoices->previousPageUrl() ? 'disabled':'' }}">←</a>
            @foreach($invoices->getUrlRange(max(1,$invoices->currentPage()-2), min($invoices->lastPage(),$invoices->currentPage()+2)) as $page => $url)
            <a href="{{ $url }}" class="pg-btn {{ $page == $invoices->currentPage() ? 'active':'' }}">{{ $page }}</a>
            @endforeach
            <a href="{{ $invoices->nextPageUrl() ?? '#' }}"
               class="pg-btn {{ !$invoices->nextPageUrl() ? 'disabled':'' }}">→</a>
        </div>
    </div>
    @endif
    @endif
</div>

@endsection