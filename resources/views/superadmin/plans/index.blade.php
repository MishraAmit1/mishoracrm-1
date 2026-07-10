@extends('layouts.app')
@section('title', 'Subscription Plans')

@push('styles')
<style>
.page-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:24px; }
.page-header h1 { font-size:22px; font-weight:700; color:var(--text-100); }

.stat-row { display:grid; grid-template-columns:repeat(3,1fr); gap:14px; margin-bottom:24px; }
@media(max-width:700px){ .stat-row { grid-template-columns:1fr 1fr; } }
.stat-card { background:var(--bg-card); border:1px solid var(--border-subtle); border-radius:var(--r-lg); padding:16px 20px; }
.stat-card .s-label { font-size:12px; color:var(--text-400); font-weight:600; text-transform:uppercase; letter-spacing:.04em; }
.stat-card .s-value { font-size:26px; font-weight:800; color:var(--text-100); margin-top:4px; }
.stat-card .s-sub   { font-size:12px; color:var(--text-400); margin-top:2px; }

.table-card { background:var(--bg-card); border:1px solid var(--border-subtle); border-radius:var(--r-lg); overflow:hidden; }
.data-table { width:100%; border-collapse:collapse; }
.data-table th { padding:11px 16px; text-align:left; font-size:11.5px; font-weight:700; color:var(--text-400); text-transform:uppercase; letter-spacing:.05em; border-bottom:1px solid var(--border-subtle); background:var(--bg-input); white-space:nowrap; }
.data-table td { padding:14px 16px; font-size:13.5px; color:var(--text-200); border-bottom:1px solid var(--border-subtle); vertical-align:middle; }
.data-table tr:last-child td { border-bottom:none; }
.data-table tr:hover td { background:var(--bg-hover); }

.plan-name-cell .pname { font-size:15px; font-weight:700; color:var(--text-100); }
.plan-name-cell .pslug { font-size:11.5px; color:var(--text-400); font-family:monospace; margin-top:2px; }
.plan-name-cell .pdesc { font-size:12px; color:var(--text-400); margin-top:2px; max-width:180px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }

.price-cell .p-main { font-size:14px; font-weight:700; color:var(--text-100); }
.price-cell .p-yearly { font-size:12px; color:var(--text-400); margin-top:2px; }
.disc-badge { display:inline-flex; align-items:center; gap:3px; background:rgba(22,163,74,.12); color:#16a34a; font-size:11px; font-weight:700; padding:2px 7px; border-radius:100px; margin-left:5px; }

.feat-list { display:flex; flex-wrap:wrap; gap:4px; }
.feat-tag { font-size:11px; padding:2px 8px; border-radius:100px; font-weight:600; }
.feat-tag.on  { background:rgba(99,102,241,.1); color:var(--accent); }
.feat-tag.off { background:var(--bg-input); color:var(--text-500); text-decoration:line-through; }

.badge { display:inline-flex; align-items:center; padding:3px 10px; border-radius:100px; font-size:11px; font-weight:700; }
.badge-green { background:rgba(22,163,74,.1); color:#16a34a; }
.badge-red   { background:rgba(239,68,68,.1); color:#ef4444; }
.badge-gray  { background:var(--bg-input); color:var(--text-400); }
.badge-blue  { background:rgba(99,102,241,.1); color:var(--accent); }

.action-btns { display:flex; gap:6px; flex-wrap:wrap; }
.btn-sm { padding:5px 12px; border-radius:var(--r-md); font-size:12px; font-weight:600; cursor:pointer; border:none; text-decoration:none; display:inline-block; white-space:nowrap; }
.btn-edit   { background:var(--bg-input); color:var(--text-200); }
.btn-toggle { background:rgba(99,102,241,.1); color:var(--accent); }
.btn-del    { background:rgba(239,68,68,.1); color:#ef4444; }

.sort-handle { color:var(--text-500); cursor:grab; font-size:16px; }
.empty-state { text-align:center; padding:56px; color:var(--text-400); font-size:14px; }
</style>
@endpush

@section('content')
<div class="page-content">

    <div class="page-header">
        <h1>Subscription Plans</h1>
        <a href="{{ route('superadmin.plans.create') }}"
           style="padding:9px 18px;border-radius:var(--r-md);background:var(--accent);color:#fff;font-size:13.5px;font-weight:700;text-decoration:none;">
            + New Plan
        </a>
    </div>

    @if(session('success'))
    <div style="background:rgba(22,163,74,.1);border:1px solid rgba(22,163,74,.25);color:#16a34a;padding:12px 16px;border-radius:var(--r-md);margin-bottom:16px;font-size:13.5px;">
        {{ session('success') }}
    </div>
    @endif
    @if(session('error'))
    <div style="background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.25);color:#ef4444;padding:12px 16px;border-radius:var(--r-md);margin-bottom:16px;font-size:13.5px;">
        {{ session('error') }}
    </div>
    @endif

    {{-- Stats --}}
    <div class="stat-row">
        <div class="stat-card">
            <div class="s-label">Total Plans</div>
            <div class="s-value">{{ $plans->count() }}</div>
            <div class="s-sub">{{ $plans->where('is_active', true)->count() }} active</div>
        </div>
        <div class="stat-card">
            <div class="s-label">Active Subscriptions</div>
            <div class="s-value">{{ $totalActiveSubs }}</div>
            <div class="s-sub">Across all plans</div>
        </div>
        <div class="stat-card">
            <div class="s-label">Plans with Discount</div>
            <div class="s-value">{{ $plans->whereNotNull('discount_percentage')->where('discount_percentage', '>', 0)->count() }}</div>
            <div class="s-sub">Showing on pricing page</div>
        </div>
    </div>

    {{-- Plans Table --}}
    <div class="table-card">
        <table class="data-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Plan</th>
                    <th>Monthly Price</th>
                    <th>Yearly Price</th>
                    <th>Features</th>
                    <th>Active Subs</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($plans as $plan)
                @php
                    $features = $plan->features ?? [];
                    $leads = $features['leads'] ?? 0;
                    $users = $features['users'] ?? 0;
                @endphp
                <tr>
                    <td style="color:var(--text-400);font-size:13px;font-weight:600" data-label="#">{{ $plan->sort_order }}</td>

                    <td class="plan-name-cell" data-label="Plan">
                        <div class="pname">{{ $plan->name }}</div>
                        <div class="pslug">{{ $plan->slug }}</div>
                        @if($plan->description)
                            <div class="pdesc">{{ $plan->description }}</div>
                        @endif
                    </td>

                    <td class="price-cell" data-label="Monthly Price">
                        @if($plan->monthly_price == 0)
                            <div class="p-main">Free</div>
                        @else
                            <div class="p-main">
                                ₹{{ number_format($plan->monthly_price) }}
                                @if($plan->hasDiscount())
                                    <span class="disc-badge">{{ $plan->discount_percentage }}% OFF</span>
                                @endif
                            </div>
                            @if($plan->hasDiscount())
                                <div class="p-yearly" style="color:#16a34a">
                                    → ₹{{ number_format($plan->discountedMonthlyPrice()) }}/mo
                                </div>
                            @endif
                        @endif
                    </td>

                    <td class="price-cell" data-label="Yearly Price">
                        @if($plan->yearly_price == 0)
                            <div class="p-main">—</div>
                        @else
                            <div class="p-main">₹{{ number_format($plan->yearly_price) }}</div>
                            @if($plan->hasDiscount())
                                <div class="p-yearly" style="color:#16a34a">
                                    → ₹{{ number_format($plan->discountedYearlyPrice()) }}/yr
                                </div>
                            @endif
                        @endif
                    </td>

                    <td data-label="Features">
                        <div class="feat-list">
                            <span class="feat-tag on">
                                {{ $leads == -1 ? '∞' : number_format($leads) }} Leads
                            </span>
                            <span class="feat-tag on">
                                {{ $users == -1 ? '∞' : number_format($users) }} Users
                            </span>
                            <span class="feat-tag {{ ($features['whatsapp'] ?? false) ? 'on' : 'off' }}">WhatsApp</span>
                            <span class="feat-tag {{ ($features['reports'] ?? false) ? 'on' : 'off' }}">Reports</span>
                            @if($features['social_leads'] ?? false)
                                <span class="feat-tag on">Social Leads</span>
                            @endif
                        </div>
                    </td>

                    <td data-label="Active Subs">
                        <span class="badge {{ $plan->active_subs_count > 0 ? 'badge-blue' : 'badge-gray' }}">
                            {{ $plan->active_subs_count }}
                        </span>
                    </td>

                    <td data-label="Status">
                        <span class="badge {{ $plan->is_active ? 'badge-green' : 'badge-red' }}">
                            {{ $plan->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </td>

                    <td>
                        <div class="action-btns">
                            <a href="{{ route('superadmin.plans.edit', $plan) }}" class="btn-sm btn-edit">Edit</a>

                            <form action="{{ route('superadmin.plans.toggle', $plan) }}" method="POST" style="display:inline">
                                @csrf
                                <button type="submit" class="btn-sm btn-toggle">
                                    {{ $plan->is_active ? 'Disable' : 'Enable' }}
                                </button>
                            </form>

                            @if($plan->active_subs_count == 0)
                            <form action="{{ route('superadmin.plans.destroy', $plan) }}" method="POST" style="display:inline"
                                  onsubmit="return confirm('Delete plan \'{{ $plan->name }}\'? This cannot be undone.')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn-sm btn-del">Delete</button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8">
                        <div class="empty-state">
                            No plans yet.
                            <a href="{{ route('superadmin.plans.create') }}" style="color:var(--accent)">Create your first plan →</a>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

</div>
@endsection
