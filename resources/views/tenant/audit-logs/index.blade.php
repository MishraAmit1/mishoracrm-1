@extends('layouts.app')
@section('title', 'Audit Logs')

@push('styles')
<style>
/* ── FILTER BAR ──────────────────────────────────────────────── */
.filter-bar{display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:18px}
.fi{padding:7px 11px;height:34px;background:var(--bg-input);border:1.5px solid var(--border-default);border-radius:var(--r-sm);color:var(--text-100);font-family:var(--font);font-size:13px;outline:none;transition:border-color .15s;-webkit-appearance:none}
.fi:focus{border-color:var(--accent)}
.search-wrap{position:relative;flex:1;min-width:180px;max-width:260px}
.search-wrap svg{position:absolute;left:9px;top:50%;transform:translateY(-50%);width:14px;height:14px;color:var(--text-300);pointer-events:none}
.search-wrap .fi{width:100%;padding-left:32px}

/* ── TABLE ───────────────────────────────────────────────────── */
.data-table{width:100%;border-collapse:collapse}
.data-table th{padding:9px 14px;text-align:left;font-size:10.5px;font-weight:700;color:var(--text-400);text-transform:uppercase;letter-spacing:.5px;border-bottom:1px solid var(--border-subtle);white-space:nowrap;background:var(--bg-elevated)}
.data-table td{padding:10px 14px;font-size:13px;color:var(--text-100);border-bottom:1px solid var(--border-subtle);vertical-align:middle}
.data-table tbody tr{transition:background .12s;cursor:pointer}
.data-table tbody tr:hover td{background:var(--bg-elevated)}
.data-table tbody tr:last-child td{border-bottom:none}

/* ── ACTION BADGES ───────────────────────────────────────────── */
.a-badge{display:inline-flex;align-items:center;gap:5px;font-size:10.5px;font-weight:700;padding:3px 9px;border-radius:100px;white-space:nowrap;letter-spacing:.03em;text-transform:uppercase}
.a-badge.created{background:var(--green-dim);color:var(--green)}
.a-badge.updated{background:var(--amber-dim);color:var(--amber)}
.a-badge.deleted{background:var(--red-dim);color:var(--red)}
.a-badge.restored{background:var(--accent-dim);color:var(--accent)}
.a-badge.login{background:var(--accent-dim);color:var(--accent)}
.a-badge.logout{background:var(--bg-elevated);color:var(--text-300)}

/* ── PAGINATION ──────────────────────────────────────────────── */
.pag-wrap{display:flex;align-items:center;justify-content:space-between;padding:12px 16px;border-top:1px solid var(--border-subtle);font-size:12.5px;color:var(--text-300)}
.pag-links{display:flex;gap:4px}
.pg-btn{padding:4px 9px;border-radius:var(--r-sm);border:1px solid var(--border-default);color:var(--text-200);text-decoration:none;font-size:12.5px;transition:all .15s}
.pg-btn:hover{border-color:var(--accent);color:var(--accent)}
.pg-btn.active{background:var(--accent);border-color:var(--accent);color:#fff}
.pg-btn.disabled{opacity:.4;pointer-events:none}

.model-chip{font-size:11px;font-weight:600;padding:2px 8px;border-radius:6px;background:var(--bg-elevated);border:1px solid var(--border-default);color:var(--text-300)}
.user-av{width:26px;height:26px;border-radius:50%;background:var(--accent-dim);color:var(--accent);font-size:10px;font-weight:700;display:inline-flex;align-items:center;justify-content:center;flex-shrink:0}
</style>
@endpush

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Audit Logs</h1>
        <p class="page-sub">Track all create, update, delete actions across your CRM</p>
    </div>
</div>

{{-- Filters --}}
<form method="GET" action="{{ route('tenant.audit-logs.index') }}" id="filterForm">
    <div class="filter-bar">
        <div class="search-wrap">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/>
            </svg>
            <input type="text" name="search" value="{{ request('search') }}" class="fi" placeholder="Search description…">
        </div>

        <select name="action" class="fi" onchange="this.form.submit()">
            <option value="">All Actions</option>
            @foreach($actions as $action)
                <option value="{{ $action }}" {{ request('action') === $action ? 'selected' : '' }}>
                    {{ ucfirst($action) }}
                </option>
            @endforeach
        </select>

        <select name="model_type" class="fi" onchange="this.form.submit()">
            <option value="">All Modules</option>
            @foreach($modelTypes as $mt)
                <option value="{{ $mt['value'] }}" {{ request('model_type') === $mt['value'] ? 'selected' : '' }}>
                    {{ $mt['label'] }}
                </option>
            @endforeach
        </select>

        <select name="user_id" class="fi" onchange="this.form.submit()">
            <option value="">All Users</option>
            @foreach($users as $u)
                <option value="{{ $u->id }}" {{ request('user_id') == $u->id ? 'selected' : '' }}>
                    {{ $u->name }}
                </option>
            @endforeach
        </select>

        <input type="date" name="date_from" value="{{ request('date_from') }}" class="fi" onchange="this.form.submit()" title="From date">
        <input type="date" name="date_to"   value="{{ request('date_to') }}"   class="fi" onchange="this.form.submit()" title="To date">

        @if(request()->hasAny(['search','action','model_type','user_id','date_from','date_to']))
            <a href="{{ route('tenant.audit-logs.index') }}" class="btn btn-ghost btn-sm">Clear</a>
        @endif
    </div>
</form>

{{-- Table --}}
<div class="card" style="padding:0;overflow:hidden">
    <div style="overflow-x:auto">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Action</th>
                    <th>Module</th>
                    <th>Description</th>
                    <th>User</th>
                    <th>IP</th>
                    <th>When</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                    <tr onclick="window.location='{{ route('tenant.audit-logs.show', $log->id) }}'">
                        <td data-label="Action">
                            <span class="a-badge {{ $log->action }}">
                                <i data-feather="{{ $log->action_icon }}" style="width:11px;height:11px"></i>
                                {{ $log->action }}
                            </span>
                        </td>
                        <td data-label="Module">
                            @if($log->model_type)
                                <span class="model-chip">{{ $log->model_short_name }}</span>
                                @if($log->model_id)
                                    <span style="color:var(--text-400);font-size:11px;margin-left:4px">#{{ $log->model_id }}</span>
                                @endif
                            @else
                                <span style="color:var(--text-400)">—</span>
                            @endif
                        </td>
                        <td style="max-width:320px" data-label="Description">
                            <span style="display:block;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                                {{ $log->description ?? '—' }}
                            </span>
                        </td>
                        <td data-label="User">
                            @if($log->user)
                                <div style="display:flex;align-items:center;gap:7px">
                                    <span class="user-av">{{ strtoupper(substr($log->user->name, 0, 2)) }}</span>
                                    <span style="font-size:12.5px">{{ $log->user->name }}</span>
                                </div>
                            @else
                                <span style="color:var(--text-400)">System</span>
                            @endif
                        </td>
                        <td style="font-family:var(--mono);font-size:12px;color:var(--text-300)" data-label="IP">
                            {{ $log->ip_address ?? '—' }}
                        </td>
                        <td style="white-space:nowrap;color:var(--text-300);font-size:12.5px" data-label="When">
                            {{ $log->created_at->diffForHumans() }}
                            <div style="font-size:11px;color:var(--text-400)">{{ $log->created_at->format('d M Y, H:i') }}</div>
                        </td>
                        <td>
                            <a href="{{ route('tenant.audit-logs.show', $log->id) }}" class="btn btn-ghost btn-xs"
                               onclick="event.stopPropagation()">View</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="text-align:center;padding:40px;color:var(--text-400)">
                            No audit logs found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    @if($logs->hasPages())
        <div class="pag-wrap">
            <span>Showing {{ $logs->firstItem() }}–{{ $logs->lastItem() }} of {{ $logs->total() }} entries</span>
            <div class="pag-links">
                @if($logs->onFirstPage())
                    <span class="pg-btn disabled">‹</span>
                @else
                    <a class="pg-btn" href="{{ $logs->previousPageUrl() }}">‹</a>
                @endif

                @foreach($logs->getUrlRange(max(1, $logs->currentPage()-2), min($logs->lastPage(), $logs->currentPage()+2)) as $page => $url)
                    @if($page == $logs->currentPage())
                        <span class="pg-btn active">{{ $page }}</span>
                    @else
                        <a class="pg-btn" href="{{ $url }}">{{ $page }}</a>
                    @endif
                @endforeach

                @if($logs->hasMorePages())
                    <a class="pg-btn" href="{{ $logs->nextPageUrl() }}">›</a>
                @else
                    <span class="pg-btn disabled">›</span>
                @endif
            </div>
        </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
// Submit search on Enter
document.querySelector('input[name=search]')?.addEventListener('keydown', e => {
    if (e.key === 'Enter') { e.target.form.submit(); }
});
</script>
@endpush
