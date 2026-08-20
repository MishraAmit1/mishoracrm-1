@extends('layouts.app')
@section('title', 'Subscription Plans')

@push('styles')
<style>
.stat-row { display:grid; grid-template-columns:repeat(3,1fr); gap:14px; margin-bottom:24px; }
@media(max-width:700px){ .stat-row { grid-template-columns:1fr 1fr; } }
.stat-card { background:var(--bg-surface); border:1px solid var(--border-default); border-radius:var(--r-lg); padding:16px 20px; }
.stat-card .s-label { font-size:12px; color:var(--text-400); font-weight:600; text-transform:uppercase; letter-spacing:.04em; }
.stat-card .s-value { font-size:26px; font-weight:800; color:var(--text-100); margin-top:4px; }
.stat-card .s-sub   { font-size:12px; color:var(--text-400); margin-top:2px; }

.table-card { background:var(--bg-surface); border:1px solid var(--border-default); border-radius:var(--r-lg); overflow:hidden; }
.data-table { width:100%; border-collapse:collapse; }
.data-table th { padding:11px 16px; text-align:left; font-size:11.5px; font-weight:700; color:var(--text-400); text-transform:uppercase; letter-spacing:.05em; border-bottom:1px solid var(--border-subtle); background:var(--bg-elevated); white-space:nowrap; }
.data-table td { padding:14px 16px; font-size:13.5px; color:var(--text-200); border-bottom:1px solid var(--border-subtle); vertical-align:middle; }
.data-table tr:last-child td { border-bottom:none; }
.data-table tr:hover td { background:var(--bg-hover); }

.plan-name-cell .pname { font-size:15px; font-weight:700; color:var(--text-100); }
.plan-name-cell .pslug { font-size:11.5px; color:var(--text-400); font-family:monospace; margin-top:2px; }
.plan-name-cell .pdesc { font-size:12px; color:var(--text-400); margin-top:2px; max-width:180px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }

.price-cell .p-main { font-size:14px; font-weight:700; color:var(--text-100); }
.price-cell .p-yearly { font-size:12px; color:var(--text-400); margin-top:2px; }
.disc-badge { display:inline-flex; align-items:center; gap:3px; background:var(--green-dim); color:var(--green); font-size:11px; font-weight:700; padding:2px 7px; border-radius:100px; margin-left:5px; }

.feat-list { display:flex; flex-wrap:wrap; gap:4px; }
.feat-tag { font-size:11px; padding:2px 8px; border-radius:100px; font-weight:600; }
.feat-tag.on  { background:var(--accent-dim); color:var(--accent); }
.feat-tag.off { background:var(--bg-input); color:var(--text-400); text-decoration:line-through; }

.badge { display:inline-flex; align-items:center; padding:3px 10px; border-radius:100px; font-size:11px; font-weight:700; }
.badge-green { background:var(--green-dim); color:var(--green); }
.badge-red   { background:var(--red-dim); color:var(--red); }
.badge-gray  { background:var(--bg-input); color:var(--text-400); }
.badge-blue  { background:var(--accent-dim); color:var(--accent); }

.action-btns { display:flex; gap:6px; flex-wrap:wrap; }

.sort-handle { color:var(--text-400); cursor:grab; font-size:16px; }
.empty-state { text-align:center; padding:56px; color:var(--text-400); font-size:14px; }
</style>
@endpush

@section('content')

    <div class="page-head">
        <div class="page-title">Subscription Plans</div>
        <a href="{{ route('superadmin.plans.create') }}" class="btn btn-primary">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="width:15px;height:15px">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
            </svg>
            New Plan
        </a>
    </div>

    <div class="table-card" style="display:flex;align-items:center;justify-content:space-between;padding:16px 20px;margin-bottom:24px;">
        <div>
            <div style="font-size:14px;font-weight:700;color:var(--text-100)">Monthly Billing on Pricing Page</div>
            <div style="font-size:12.5px;color:var(--text-400);margin-top:2px">
                {{ $monthlyBillingEnabled ? 'Tenants can switch to Monthly billing on the "Choose Your Plan" page.' : 'Tenants only see Yearly pricing — Monthly is hidden until you enable it.' }}
            </div>
        </div>
        <form action="{{ route('superadmin.plans.toggle-monthly-billing') }}" method="POST">
            @csrf
            <button type="submit" class="btn btn-secondary btn-sm" style="{{ $monthlyBillingEnabled ? 'color:var(--red)' : 'color:var(--accent)' }}">
                {{ $monthlyBillingEnabled ? 'Disable Monthly Billing' : 'Enable Monthly Billing' }}
            </button>
        </form>
    </div>

    @if(session('success'))
    <div style="padding:12px 16px;background:var(--green-dim);border:1px solid rgba(29,158,117,.2);border-radius:var(--r-sm);font-size:13px;color:var(--green);margin-bottom:16px">
        ✓ {{ session('success') }}
    </div>
    @endif
    @if(session('error'))
    <div style="padding:12px 16px;background:var(--red-dim);border:1px solid rgba(224,82,82,.18);border-radius:var(--r-sm);font-size:13px;color:var(--red);margin-bottom:16px">
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
                            <a href="{{ route('superadmin.plans.edit', $plan) }}" class="btn btn-secondary btn-sm">Edit</a>

                            <form action="{{ route('superadmin.plans.toggle', $plan) }}" method="POST" style="display:inline">
                                @csrf
                                <button type="submit" class="btn btn-secondary btn-sm" style="color:var(--accent)">
                                    {{ $plan->is_active ? 'Disable' : 'Enable' }}
                                </button>
                            </form>

                            @if($plan->active_subs_count == 0)
                            <form action="{{ route('superadmin.plans.destroy', $plan) }}" method="POST" style="display:inline"
                                  onsubmit="return confirm('Delete plan \'{{ $plan->name }}\'? This cannot be undone.')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-secondary btn-sm" style="color:var(--red)">Delete</button>
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

@endsection
