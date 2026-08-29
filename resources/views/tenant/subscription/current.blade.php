@extends('layouts.app')
@section('title', 'My Subscription')

@push('styles')
<style>
.sub-wrap { max-width: 680px; margin: 0 auto; padding: 32px 0; }
.sub-top {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    margin-bottom: 24px;
    flex-wrap: wrap;
    gap: 12px;
}
.sub-top h1 { font-size: 22px; font-weight: 800; color: var(--text-100); }
.sub-top p { font-size: 14px; color: var(--text-400); margin-top: 4px; }
.sub-status-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 5px 14px;
    border-radius: 100px;
    font-size: 12.5px;
    font-weight: 700;
}
.sub-status-badge.active { background: rgba(34,197,94,0.12); color: #22c55e; }
.sub-status-badge.trial  { background: rgba(99,102,241,0.12); color: var(--accent); }
.sub-status-badge.cancelled { background: rgba(239,68,68,0.1); color: #ef4444; }
.sub-status-badge.expired { background: rgba(239,68,68,0.1); color: #ef4444; }

.info-card {
    background: var(--bg-card);
    border: 1px solid var(--border-subtle);
    border-radius: var(--r-lg);
    padding: 24px;
    margin-bottom: 20px;
}
.info-card h3 {
    font-size: 13px;
    font-weight: 700;
    color: var(--text-400);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 16px;
}
.info-row {
    display: flex;
    justify-content: space-between;
    padding: 10px 0;
    border-bottom: 1px solid var(--border-subtle);
    font-size: 14px;
}
.info-row:last-child { border-bottom: none; }
.info-row .lbl { color: var(--text-400); }
.info-row .val { color: var(--text-100); font-weight: 600; }

.features-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
}
.feature-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 14px;
    background: var(--bg-input);
    border-radius: var(--r-md);
    font-size: 13.5px;
    color: var(--text-200);
}
.feature-item .f-icon { color: var(--accent); }
.feature-item.off { opacity: 0.5; }

.action-bar {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
    margin-top: 20px;
}
.btn-action {
    padding: 10px 20px;
    border-radius: var(--r-md);
    font-size: 14px;
    font-weight: 700;
    cursor: pointer;
    text-decoration: none;
    border: none;
    transition: opacity 0.2s;
}
.btn-action.primary { background: var(--accent); color: #fff; }
.btn-action.danger  { background: rgba(239,68,68,0.12); color: #ef4444; border: 1px solid rgba(239,68,68,0.2); }
.btn-action.outline { border: 1.5px solid var(--border-subtle); color: var(--text-200); }
.btn-action:hover { opacity: 0.85; }

.trial-bar {
    background: rgba(99,102,241,0.08);
    border: 1px solid rgba(99,102,241,0.2);
    border-radius: var(--r-md);
    padding: 14px 18px;
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 20px;
    font-size: 13.5px;
    color: var(--text-200);
}
.trial-bar strong { color: var(--text-100); }
</style>
@endpush

@section('content')
<div class="page-content">
<div class="sub-wrap">

    <div class="sub-top">
        <div>
            <h1>My Subscription</h1>
            <p>Aapke current plan aur subscription ki details</p>
        </div>
        @php
            $badgeClass = match($subscription->status) {
                'active'    => 'active',
                'trial'     => 'trial',
                'cancelled' => 'cancelled',
                default     => 'expired',
            };
        @endphp
        <span class="sub-status-badge {{ $badgeClass }}">
            <svg width="8" height="8" viewBox="0 0 8 8" fill="currentColor"><circle cx="4" cy="4" r="4"/></svg>
            {{ ucfirst($subscription->status) }}
        </span>
    </div>

    @if($subscription->isTrial())
    <div class="trial-bar">
        <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        Free trial chal raha hai — <strong>{{ $subscription->trialDaysLeft() }} days bacha hai</strong>. Apna plan upgrade karein.
        <a href="{{ route('tenant.subscription.plans') }}" style="margin-left:auto;color:var(--accent);font-weight:700;text-decoration:none;">Upgrade Now →</a>
    </div>
    @endif

    @if($subscription->status === 'cancelled' && $subscription->ends_at && $subscription->ends_at->isFuture())
    <div class="trial-bar" style="background:rgba(239,68,68,0.08);border-color:rgba(239,68,68,0.2);">
        <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>
        </svg>
        Subscription cancelled hai — <strong>{{ $subscription->ends_at->format('d M Y') }}</strong> tak access chalega, uske baad band ho jayega.
        <a href="{{ route('tenant.subscription.plans') }}" style="margin-left:auto;color:var(--accent);font-weight:700;text-decoration:none;">Resume Plan →</a>
    </div>
    @endif

    @if($subscription->isFree())
    <div class="trial-bar" style="background:rgba(34,197,94,0.08);border-color:rgba(34,197,94,0.2);">
        <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        Aap free plan par hain — koi expiry nahi. Zyada features ke liye upgrade karein.
        <a href="{{ route('tenant.subscription.plans') }}" style="margin-left:auto;color:var(--accent);font-weight:700;text-decoration:none;">See Plans →</a>
    </div>
    @endif

    <div class="info-card">
        <h3>Plan Details</h3>
        <div class="info-row">
            <span class="lbl">Plan Name</span>
            <span class="val">{{ $subscription->plan?->name ?? 'N/A' }}</span>
        </div>
        <div class="info-row">
            <span class="lbl">Billing Cycle</span>
            <span class="val">{{ ucfirst($subscription->billing_cycle) }}</span>
        </div>
        <div class="info-row">
            <span class="lbl">Started On</span>
            <span class="val">{{ $subscription->started_at?->format('d M Y') ?? '—' }}</span>
        </div>
        <div class="info-row">
            <span class="lbl">Valid Until</span>
            <span class="val">{{ $subscription->isFree() ? 'No expiry' : ($subscription->ends_at?->format('d M Y') ?? '—') }}</span>
        </div>
        @if($subscription->isActive() && !$subscription->isFree())
        <div class="info-row">
            <span class="lbl">Days Remaining</span>
            <span class="val">{{ $subscription->daysLeft() }} days</span>
        </div>
        @endif
        @if($subscription->razorpay_payment_id)
        <div class="info-row">
            <span class="lbl">Payment ID</span>
            <span class="val" style="font-family:var(--mono);font-size:12px">{{ $subscription->razorpay_payment_id }}</span>
        </div>
        @endif
    </div>

    @if($subscription->plan)
    <div class="info-card">
        <h3>Included Features</h3>
        <div class="features-grid">
            <div class="feature-item">
                <svg class="f-icon" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/>
                </svg>
                @php $users = $subscription->plan->getFeature('users'); @endphp
                {{ $users == -1 ? 'Unlimited Users' : $users . ' Users' }}
            </div>
            <div class="feature-item">
                <svg class="f-icon" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 7.5L7.5 3m0 0L12 7.5M7.5 3v13.5m13.5 0L16.5 21m0 0L12 16.5m4.5 4.5V7.5"/>
                </svg>
                @php $leads = $subscription->plan->getFeature('leads'); @endphp
                {{ $leads == -1 ? 'Unlimited Leads' : number_format($leads) . ' Leads' }}
            </div>
            <div class="feature-item {{ $subscription->plan->hasFeature('whatsapp') ? '' : 'off' }}">
                <svg class="f-icon" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 01.865-.501 48.172 48.172 0 003.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0012 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018z"/>
                </svg>
                WhatsApp Integration
            </div>
            <div class="feature-item {{ $subscription->plan->hasFeature('reports') ? '' : 'off' }}">
                <svg class="f-icon" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z"/>
                </svg>
                Advanced Reports
            </div>
        </div>
    </div>
    @endif

    <div class="action-bar">
        <a href="{{ route('tenant.subscription.plans') }}" class="btn-action primary">Upgrade Plan</a>
        @if($subscription->isActive() && !$subscription->isTrial() && !$subscription->isFree())
        <form action="{{ route('tenant.subscription.cancel') }}" method="POST"
              onsubmit="return confirm('Are you sure you want to cancel? You can still use until {{ $subscription->ends_at?->format('d M Y') }}')">
            @csrf
            <button type="submit" class="btn-action danger">Cancel Subscription</button>
        </form>
        @endif
    </div>

</div>
</div>
@endsection
