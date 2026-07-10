@extends('layouts.app')
@section('title', 'Coupon Management')

@push('styles')
<style>
.page-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:24px; }
.page-header h1 { font-size:22px; font-weight:700; color:var(--text-100); }
.table-card { background:var(--bg-card); border:1px solid var(--border-subtle); border-radius:var(--r-lg); overflow:hidden; }
.data-table { width:100%; border-collapse:collapse; }
.data-table th { padding:11px 16px; text-align:left; font-size:12px; font-weight:600; color:var(--text-400); text-transform:uppercase; letter-spacing:.04em; border-bottom:1px solid var(--border-subtle); background:var(--bg-input); }
.data-table td { padding:13px 16px; font-size:13.5px; color:var(--text-200); border-bottom:1px solid var(--border-subtle); vertical-align:middle; }
.data-table tr:last-child td { border-bottom:none; }
.data-table tr:hover td { background:var(--bg-hover); }
.badge { display:inline-flex; align-items:center; padding:3px 10px; border-radius:100px; font-size:11px; font-weight:700; }
.badge-green  { background:rgba(22,163,74,.1); color:#16a34a; }
.badge-red    { background:rgba(239,68,68,.1); color:#ef4444; }
.badge-blue   { background:rgba(99,102,241,.1); color:var(--accent); }
.badge-yellow { background:rgba(234,179,8,.1); color:#ca8a04; }
.badge-gray   { background:var(--bg-input); color:var(--text-400); }
.code-chip { font-family:monospace; font-size:13px; font-weight:700; background:var(--bg-input); padding:3px 8px; border-radius:6px; color:var(--text-100); letter-spacing:.05em; }
.action-btns { display:flex; gap:6px; }
.btn-sm { padding:5px 12px; border-radius:var(--r-md); font-size:12px; font-weight:600; cursor:pointer; border:none; text-decoration:none; display:inline-block; }
.btn-edit   { background:var(--bg-input); color:var(--text-200); }
.btn-toggle { background:rgba(99,102,241,.1); color:var(--accent); }
.btn-del    { background:rgba(239,68,68,.1); color:#ef4444; }
.empty-state { text-align:center; padding:56px 16px; color:var(--text-400); font-size:14px; }
</style>
@endpush

@section('content')
<div class="page-content">

    <div class="page-header">
        <h1>Coupon Management</h1>
        <a href="{{ route('superadmin.coupons.create') }}" class="btn btn-primary" style="padding:9px 18px;border-radius:var(--r-md);background:var(--accent);color:#fff;font-size:13.5px;font-weight:700;text-decoration:none;">
            + New Coupon
        </a>
    </div>

    @if(session('success'))
    <div class="alert alert-success" style="background:rgba(22,163,74,.1);border:1px solid rgba(22,163,74,.25);color:#16a34a;padding:12px 16px;border-radius:var(--r-md);margin-bottom:16px;font-size:13.5px;">
        {{ session('success') }}
    </div>
    @endif

    <div class="table-card">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Name</th>
                    <th>Discount</th>
                    <th>Applicable To</th>
                    <th>Uses</th>
                    <th>Expires</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($coupons as $coupon)
                <tr>
                    <td data-label="Code"><span class="code-chip">{{ $coupon->code }}</span></td>
                    <td data-label="Name">
                        <div style="font-weight:600;color:var(--text-100)">{{ $coupon->name }}</div>
                        @if($coupon->description)
                            <div style="font-size:12px;color:var(--text-400)">{{ $coupon->description }}</div>
                        @endif
                    </td>
                    <td data-label="Discount">
                        <span class="badge badge-blue">{{ $coupon->discount_label }}</span>
                        @if($coupon->max_discount)
                            <div style="font-size:11px;color:var(--text-400);margin-top:2px">Max ₹{{ number_format($coupon->max_discount) }}</div>
                        @endif
                    </td>
                    <td data-label="Applicable To">
                        @if($coupon->applicable_to === 'all')
                            <span class="badge badge-green">All Users</span>
                        @else
                            <span class="badge badge-yellow">{{ $coupon->users->count() }} Specific User(s)</span>
                            @foreach($coupon->users->take(3) as $u)
                                <div style="font-size:12px;color:var(--text-100);margin-top:3px;font-weight:600">
                                    {{ $u->name }}
                                    @if($u->tenant)<span style="font-weight:400;color:var(--text-400)"> · {{ $u->tenant->name }}</span>@endif
                                </div>
                            @endforeach
                            @if($coupon->users->count() > 3)
                                <div style="font-size:11px;color:var(--text-400)">+{{ $coupon->users->count() - 3 }} more</div>
                            @endif
                        @endif
                    </td>
                    <td data-label="Uses">
                        {{ $coupon->used_count }}
                        @if($coupon->max_uses)
                            <span style="color:var(--text-400)"> / {{ $coupon->max_uses }}</span>
                        @else
                            <span style="color:var(--text-400)"> / ∞</span>
                        @endif
                    </td>
                    <td data-label="Expires">
                        @if($coupon->expires_at)
                            <span style="color: {{ $coupon->expires_at->isPast() ? '#ef4444' : 'var(--text-200)' }}">
                                {{ $coupon->expires_at->format('d M Y') }}
                            </span>
                        @else
                            <span style="color:var(--text-400)">Never</span>
                        @endif
                    </td>
                    <td data-label="Status">
                        <span class="badge {{ $coupon->is_active ? 'badge-green' : 'badge-red' }}">
                            {{ $coupon->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </td>
                    <td>
                        <div class="action-btns">
                            <a href="{{ route('superadmin.coupons.edit', $coupon) }}" class="btn-sm btn-edit">Edit</a>
                            <form action="{{ route('superadmin.coupons.toggle', $coupon) }}" method="POST" style="display:inline">
                                @csrf
                                <button type="submit" class="btn-sm btn-toggle">
                                    {{ $coupon->is_active ? 'Disable' : 'Enable' }}
                                </button>
                            </form>
                            <form action="{{ route('superadmin.coupons.destroy', $coupon) }}" method="POST" style="display:inline"
                                  onsubmit="return confirm('Delete coupon {{ $coupon->code }}?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn-sm btn-del">Delete</button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8">
                        <div class="empty-state">
                            No coupons yet. <a href="{{ route('superadmin.coupons.create') }}" style="color:var(--accent)">Create your first coupon →</a>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($coupons->hasPages())
    <div style="margin-top:16px">{{ $coupons->links() }}</div>
    @endif

</div>
@endsection
