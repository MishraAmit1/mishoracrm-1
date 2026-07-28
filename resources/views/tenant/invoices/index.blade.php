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

/* ── MOBILE INVOICE CARDS (<768px) ─────────────────────────────── */
.iv-mobile-list{display:none}
@media(max-width:768px){
    .iv-table-wrap{display:none}
    .iv-mobile-list{display:flex;flex-direction:column;gap:10px;padding:14px}
}
.iv-card{background:var(--bg-surface);border:1px solid var(--border-default);border-radius:var(--r-md);padding:14px;cursor:pointer;transition:border-color .15s,box-shadow .15s}
.iv-card:active{border-color:var(--accent)}
.iv-top{display:flex;align-items:flex-start;justify-content:space-between;gap:10px;margin-bottom:12px}
.iv-num{font-family:var(--mono);font-size:14px;font-weight:700;color:var(--accent)}
.iv-quo{font-size:11px;color:var(--text-400);margin-top:2px}
.iv-contact{font-size:12.5px;font-weight:600;color:var(--text-100);margin-top:4px}
.iv-company{font-size:11.5px;color:var(--text-300)}
.iv-amounts{display:flex;flex-direction:column;gap:2px;margin-bottom:10px;padding:9px 11px;background:var(--bg-elevated);border-radius:8px}
.iv-amt-row{display:flex;align-items:center;justify-content:space-between;font-size:12.5px}
.iv-amt-lbl{color:var(--text-400)}
.iv-amt-val{font-family:var(--mono);font-weight:700;color:var(--text-100)}
.iv-meta{display:flex;align-items:center;justify-content:space-between;gap:8px;flex-wrap:wrap;margin-bottom:10px;font-size:11.5px}
.iv-meta-lbl{color:var(--text-400)}
.iv-meta-val{color:var(--text-200);font-weight:600}
.iv-foot{display:flex;align-items:center;justify-content:space-between;gap:8px;padding-top:10px;border-top:1px solid var(--border-subtle)}
.iv-acts{display:flex;align-items:center;gap:6px;flex-shrink:0}
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
    <div class="page-actions">
        @if(auth()->user()->isTenantAdmin())
        <a href="{{ route('tenant.invoice-pdf-style.index') }}" class="btn btn-secondary">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="width:15px;height:15px">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9.53 16.122a3 3 0 00-5.78 1.128 2.25 2.25 0 01-2.4 2.245 4.5 4.5 0 008.4-2.245c0-.399-.078-.78-.22-1.128zm0 0a15.998 15.998 0 003.388-1.62m-5.043-.025a15.994 15.994 0 011.622-3.395m3.42 3.42a15.995 15.995 0 004.764-4.648l3.876-5.814a1.151 1.151 0 00-1.597-1.597L14.146 6.32a15.996 15.996 0 00-4.649 4.763m3.42 3.42a6.776 6.776 0 00-3.42-3.42"/>
            </svg>
            PDF Style
        </a>
        @endif
        <a href="{{ route('tenant.invoices.create') }}" class="btn btn-primary">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="width:15px;height:15px">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
            </svg>
            New Invoice
        </a>
    </div>
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
    <div class="iv-table-wrap" style="overflow-x:auto">
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

    {{-- Mobile card list (shown only <768px, table above hides itself) --}}
    <div class="iv-mobile-list">
    @foreach($invoices as $inv)
    @php
        $sc      = $statusCfg[$inv->status] ?? ['label'=>ucfirst($inv->status),'color'=>'accent','bg'=>'accent-dim'];
        $isOD    = $inv->isOverdue();
        $paidPct = $inv->total > 0 ? min(100, round(($inv->paid_amount / $inv->total) * 100)) : 0;
    @endphp
    <div class="iv-card" onclick="window.location='{{ route('tenant.invoices.show', $inv->id) }}'">
        <div class="iv-top">
            <div style="min-width:0">
                <div class="iv-num">{{ $inv->number }}</div>
                @if($inv->quotation)
                <div class="iv-quo">From {{ $inv->quotation->number }}</div>
                @endif
                <div class="iv-contact">{{ $inv->contact?->name ?? '—' }}</div>
                @if($inv->contact?->company)
                <div class="iv-company">{{ $inv->contact->company }}</div>
                @endif
            </div>
            <span class="badge" style="background:var(--{{ $sc['bg'] }});color:var(--{{ $sc['color'] }});flex-shrink:0">
                {{ $isOD ? 'Overdue' : $sc['label'] }}
            </span>
        </div>
        <div class="iv-amounts">
            <div class="iv-amt-row">
                <span class="iv-amt-lbl">Amount</span>
                <span class="iv-amt-val">₹{{ number_format($inv->total, 2) }}</span>
            </div>
            @if($inv->paid_amount > 0)
            <div class="iv-amt-row">
                <span class="iv-amt-lbl">Paid</span>
                <span class="iv-amt-val" style="color:var(--green)">₹{{ number_format($inv->paid_amount, 2) }}</span>
            </div>
            <div class="pay-mini">
                <div class="pay-mini-fill" style="width:{{ $paidPct }}%"></div>
            </div>
            @endif
        </div>
        <div class="iv-meta">
            <span><span class="iv-meta-lbl">Date: </span><span class="iv-meta-val">{{ $inv->date?->format('d M Y') ?? '—' }}</span></span>
            <span>
                <span class="iv-meta-lbl">Due: </span>
                <span class="iv-meta-val" style="{{ $isOD ? 'color:var(--red)' : '' }}">{{ $inv->due_date?->format('d M Y') ?? '—' }}</span>
            </span>
        </div>
        <div class="iv-foot" onclick="event.stopPropagation()">
            <div></div>
            <div class="iv-acts">
                <a href="{{ route('tenant.invoices.pdf', $inv->id) }}" target="_blank"
                   class="btn btn-secondary btn-sm" title="PDF">📄</a>
                @if(!$inv->isPaid())
                <a href="{{ route('tenant.invoices.edit', $inv->id) }}"
                   class="btn btn-secondary btn-sm" title="Edit">✎</a>
                @endif
            </div>
        </div>
    </div>
    @endforeach
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