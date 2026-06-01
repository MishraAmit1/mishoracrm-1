@extends('layouts.app')
@section('title', 'Payment Successful')

@push('styles')
<style>
.success-wrap {
    max-width: 480px;
    margin: 64px auto;
    text-align: center;
}
.success-icon {
    width: 80px; height: 80px;
    background: rgba(34,197,94,0.12);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 24px;
    color: #22c55e;
}
.success-wrap h1 {
    font-size: 26px;
    font-weight: 800;
    color: var(--text-100);
    margin-bottom: 8px;
}
.success-wrap p {
    font-size: 14.5px;
    color: var(--text-400);
    line-height: 1.6;
    margin-bottom: 32px;
}
.sub-detail-box {
    background: var(--bg-card);
    border: 1px solid var(--border-subtle);
    border-radius: var(--r-lg);
    padding: 20px 24px;
    text-align: left;
    margin-bottom: 28px;
}
.sub-detail-row {
    display: flex;
    justify-content: space-between;
    font-size: 13.5px;
    padding: 6px 0;
}
.sub-detail-row .lbl { color: var(--text-400); }
.sub-detail-row .val { color: var(--text-100); font-weight: 600; }
.action-btns {
    display: flex;
    gap: 12px;
    justify-content: center;
}
.btn-go {
    padding: 11px 24px;
    border-radius: var(--r-md);
    font-size: 14px;
    font-weight: 700;
    text-decoration: none;
    transition: opacity 0.2s;
}
.btn-go.primary { background: var(--accent); color: #fff; }
.btn-go.outline { border: 1.5px solid var(--border-subtle); color: var(--text-200); }
.btn-go:hover { opacity: 0.85; }
</style>
@endpush

@section('content')
<div class="page-content">
<div class="success-wrap">

    <div class="success-icon">
        <svg width="40" height="40" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
    </div>

    <h1>Payment Successful!</h1>
    <p>
        Aapka subscription activate ho gaya hai. Ab aap {{ config('app.name') }} ke saare features use kar sakte hain.
    </p>

    @if($subscription)
    <div class="sub-detail-box">
        <div class="sub-detail-row">
            <span class="lbl">Plan</span>
            <span class="val">{{ $subscription->plan?->name ?? 'N/A' }}</span>
        </div>
        <div class="sub-detail-row">
            <span class="lbl">Billing Cycle</span>
            <span class="val">{{ ucfirst($subscription->billing_cycle) }}</span>
        </div>
        <div class="sub-detail-row">
            <span class="lbl">Valid Until</span>
            <span class="val">{{ $subscription->ends_at?->format('d M Y') }}</span>
        </div>
        @if(session('payment_id'))
        <div class="sub-detail-row">
            <span class="lbl">Payment ID</span>
            <span class="val" style="font-family:var(--mono);font-size:12px">{{ session('payment_id') }}</span>
        </div>
        @endif
    </div>
    @endif

    <div class="action-btns">
        <a href="{{ route('tenant.dashboard') }}" class="btn-go primary">Go to Dashboard</a>
        <a href="{{ route('tenant.subscription.current') }}" class="btn-go outline">View Subscription</a>
    </div>

</div>
</div>
@endsection
