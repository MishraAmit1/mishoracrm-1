@extends('layouts.app')
@section('title', 'Choose Your Plan')

@push('styles')
<style>
.plans-wrap {
    max-width: 960px;
    margin: 0 auto;
    padding: 32px 0;
}
.plans-header {
    text-align: center;
    margin-bottom: 40px;
}
.plans-header h1 {
    font-size: 28px;
    font-weight: 800;
    color: var(--text-100);
    margin-bottom: 8px;
}
.plans-header p {
    font-size: 15px;
    color: var(--text-400);
}
.billing-toggle {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
    margin-bottom: 36px;
}
.toggle-label {
    font-size: 14px;
    font-weight: 600;
    color: var(--text-300);
    cursor: pointer;
}
.toggle-label.active {
    color: var(--text-100);
}
.toggle-switch {
    position: relative;
    width: 48px;
    height: 26px;
}
.toggle-switch input { opacity: 0; width: 0; height: 0; }
.toggle-slider {
    position: absolute;
    inset: 0;
    background: var(--bg-card);
    border: 1px solid var(--border-subtle);
    border-radius: 100px;
    cursor: pointer;
    transition: 0.2s;
}
.toggle-slider:before {
    content: '';
    position: absolute;
    width: 18px; height: 18px;
    left: 3px; bottom: 3px;
    background: var(--accent);
    border-radius: 50%;
    transition: 0.2s;
}
input:checked + .toggle-slider:before { transform: translateX(22px); }
.save-badge {
    background: var(--accent);
    color: #fff;
    font-size: 11px;
    font-weight: 700;
    padding: 2px 8px;
    border-radius: 100px;
}
.plans-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
    gap: 20px;
}
.plan-card {
    background: var(--bg-card);
    border: 1px solid var(--border-subtle);
    border-radius: var(--r-lg);
    padding: 28px;
    position: relative;
    transition: border-color 0.2s, transform 0.2s;
}
.plan-card.popular {
    border-color: var(--accent);
    box-shadow: 0 0 0 1px var(--accent);
}
.plan-card:hover { transform: translateY(-2px); }
.popular-badge {
    position: absolute;
    top: -12px;
    left: 50%;
    transform: translateX(-50%);
    background: var(--accent);
    color: #fff;
    font-size: 11px;
    font-weight: 700;
    padding: 3px 14px;
    border-radius: 100px;
    white-space: nowrap;
}
.plan-name {
    font-size: 16px;
    font-weight: 700;
    color: var(--text-100);
    margin-bottom: 4px;
}
.plan-desc {
    font-size: 13px;
    color: var(--text-400);
    margin-bottom: 20px;
}
.plan-price {
    margin-bottom: 24px;
}
.price-amount {
    font-size: 36px;
    font-weight: 800;
    color: var(--text-100);
    line-height: 1;
}
.price-original {
    font-size: 18px;
    font-weight: 500;
    color: var(--text-500);
    text-decoration: line-through;
    margin-right: 6px;
}
.price-period {
    font-size: 13px;
    color: var(--text-400);
    margin-left: 4px;
}
.price-yearly-note {
    font-size: 12px;
    color: var(--text-400);
    margin-top: 4px;
}
.discount-badge {
    display: inline-block;
    background: rgba(220,252,231,0.14);
    color: #6FEC9D;
    font-size: 11px;
    font-weight: 700;
    padding: 2px 8px;
    border-radius: 100px;
    margin-left: 6px;
    vertical-align: middle;
}
.plan-features {
    list-style: none;
    padding: 0;
    margin: 0 0 24px;
    display: flex;
    flex-direction: column;
    gap: 10px;
}
.plan-features li {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13.5px;
    color: var(--text-200);
}
.plan-features li .feat-icon {
    width: 18px; height: 18px;
    flex-shrink: 0;
    color: var(--accent);
}
.plan-features li.disabled {
    color: var(--text-500);
    text-decoration: line-through;
}
.plan-features li.disabled .feat-icon { color: var(--text-500); }
.plan-btn {
    display: block;
    width: 100%;
    padding: 11px;
    border-radius: var(--r-md);
    font-size: 14px;
    font-weight: 700;
    text-align: center;
    cursor: pointer;
    border: none;
    transition: opacity 0.2s;
    text-decoration: none;
}
.plan-btn.primary { background: var(--accent); color: #fff; }
.plan-btn.outline {
    background: transparent;
    border: 1.5px solid var(--border-subtle);
    color: var(--text-200);
}
.plan-btn.current-plan {
    background: var(--bg-input);
    color: var(--text-400);
    cursor: default;
    border: 1px solid var(--border-subtle);
}
.plan-btn:hover:not(.current-plan) { opacity: 0.85; }

.current-sub-info {
    background: var(--bg-card);
    border: 1px solid var(--border-subtle);
    border-radius: var(--r-lg);
    padding: 16px 20px;
    margin-bottom: 28px;
    display: flex;
    align-items: center;
    gap: 12px;
}
.current-sub-info .sub-icon { color: var(--accent); }
.current-sub-info .sub-text { font-size: 13.5px; color: var(--text-200); }
.current-sub-info .sub-text strong { color: var(--text-100); }
</style>
@endpush

@section('content')
<div class="page-content">
<div class="plans-wrap">

    <div class="plans-header">
        <h1>Choose Your Plan</h1>
        <p>Apne business ke liye sahi plan select karein. Kabhi bhi upgrade ya downgrade kar sakte hain.</p>
    </div>

    @if($currentSub)
    <div class="current-sub-info">
        <svg class="sub-icon" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <div class="sub-text">
            Current plan: <strong>{{ $currentSub->plan?->name ?? 'Free Trial' }}</strong>
            @if($currentSub->isTrial())
                — Trial: <strong>{{ $currentSub->trialDaysLeft() }} days left</strong>
            @elseif($currentSub->isActive())
                — Active until <strong>{{ $currentSub->ends_at?->format('d M Y') }}</strong>
            @endif
        </div>
        <a href="{{ route('tenant.subscription.current') }}" style="margin-left:auto;font-size:13px;color:var(--accent);font-weight:600;text-decoration:none;">View Details →</a>
    </div>
    @endif

    {{-- Billing Toggle --}}
    @if($monthlyBillingEnabled)
    <div class="billing-toggle">
        <span class="toggle-label active" id="lbl-monthly">Monthly</span>
        <label class="toggle-switch">
            <input type="checkbox" id="billing-toggle">
            <span class="toggle-slider"></span>
        </label>
        <span class="toggle-label" id="lbl-yearly">Yearly <span class="save-badge">Save 20%</span></span>
    </div>
    @endif

    <div class="plans-grid">
        @foreach($plans as $plan)
        @php
            $isCurrentPlan   = $currentSub && $currentSub->plan_id === $plan->id && $currentSub->isActive();
            $isPopular       = $plan->slug === 'starter';
            $monthlyPrice    = (int) $plan->monthly_price;
            $yearlyPrice     = (int) $plan->yearly_price;
            $hasDiscount     = $plan->hasDiscount();
            $discMonthly     = (int) $plan->discountedMonthlyPrice();
            $discYearly      = (int) $plan->discountedYearlyPrice();
        @endphp
        <div class="plan-card {{ $isPopular ? 'popular' : '' }}">
            @if($isPopular)
            <div class="popular-badge">Most Popular</div>
            @endif

            <div class="plan-name">{{ $plan->name }}</div>
            <div class="plan-desc">{{ $plan->description ?? '' }}</div>

            <div class="plan-price">
                {{-- Monthly price --}}
                <div class="monthly-price" @if(!$monthlyBillingEnabled) style="display:none" @endif>
                    @if($monthlyPrice == 0)
                        <span class="price-amount">Free</span>
                    @else
                        @if($hasDiscount)
                            <span class="price-original">₹{{ number_format($monthlyPrice) }}</span>
                        @endif
                        <span class="price-amount">₹{{ number_format($hasDiscount ? $discMonthly : $monthlyPrice) }}</span>
                        <span class="price-period">/month</span>
                        @if($hasDiscount)
                            <span class="discount-badge">{{ $plan->discount_percentage }}% OFF</span>
                        @endif
                    @endif
                </div>
                {{-- Yearly price --}}
                <div class="yearly-price" @if($monthlyBillingEnabled) style="display:none" @endif>
                    @if($yearlyPrice == 0)
                        <span class="price-amount">Free</span>
                    @else
                        @if($hasDiscount)
                            <span class="price-original">₹{{ number_format($yearlyPrice) }}</span>
                        @endif
                        <span class="price-amount">₹{{ number_format($hasDiscount ? $discYearly : $yearlyPrice) }}</span>
                        <span class="price-period">/year</span>
                        @if($hasDiscount)
                            <span class="discount-badge">{{ $plan->discount_percentage }}% OFF</span>
                        @endif
                    @endif
                </div>
                @if($yearlyPrice > 0)
                <div class="price-yearly-note yearly-only" @if($monthlyBillingEnabled) style="display:none" @endif>
                    (₹{{ number_format(($hasDiscount ? $discYearly : $yearlyPrice) / 12, 0) }}/month billed annually)
                </div>
                @endif
            </div>

            <ul class="plan-features">
                <li>
                    <svg class="feat-icon" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    @php $leads = $plan->getFeature('leads'); @endphp
                    {{ $leads == -1 ? 'Unlimited Leads' : number_format($leads) . ' Leads' }}
                </li>
                <li>
                    <svg class="feat-icon" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    @php $users = $plan->getFeature('users'); @endphp
                    {{ $users == -1 ? 'Unlimited Users' : number_format($users) . ' Team Members' }}
                </li>
                <li class="{{ $plan->hasFeature('whatsapp') ? '' : 'disabled' }}">
                    <svg class="feat-icon" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    WhatsApp Integration
                </li>
                <li class="{{ $plan->hasFeature('reports') ? '' : 'disabled' }}">
                    <svg class="feat-icon" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    Advanced Reports
                </li>
                @if($plan->hasFeature('social_leads'))
                <li>
                    <svg class="feat-icon" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    Social Media Leads
                </li>
                @endif
            </ul>

            @if($monthlyPrice == 0)
                <span class="plan-btn current-plan">Free Plan</span>
            @elseif($isCurrentPlan)
                <span class="plan-btn current-plan">Current Plan</span>
            @else
                <a href="{{ route('tenant.subscription.checkout', [$plan->slug, 'monthly']) }}"
                   class="plan-btn {{ $isPopular ? 'primary' : 'outline' }} monthly-btn"
                   @if(!$monthlyBillingEnabled) style="display:none" @endif>
                    {{ $currentSub && $currentSub->isActive() ? 'Switch to ' . $plan->name : 'Get Started' }}
                </a>
                <a href="{{ route('tenant.subscription.checkout', [$plan->slug, 'yearly']) }}"
                   class="plan-btn {{ $isPopular ? 'primary' : 'outline' }} yearly-btn"
                   @if($monthlyBillingEnabled) style="display:none" @endif>
                    {{ $currentSub && $currentSub->isActive() ? 'Switch to ' . $plan->name : 'Get Started (Yearly)' }}
                </a>
            @endif
        </div>
        @endforeach
    </div>

</div>
</div>
@endsection

@push('scripts')
<script>
const toggle   = document.getElementById('billing-toggle');
const lblM     = document.getElementById('lbl-monthly');
const lblY     = document.getElementById('lbl-yearly');

function switchBilling(yearly) {
    lblM.classList.toggle('active', !yearly);
    lblY.classList.toggle('active', yearly);

    document.querySelectorAll('.monthly-price, .monthly-btn').forEach(el => el.style.display = yearly ? 'none' : '');
    document.querySelectorAll('.yearly-price, .yearly-btn, .yearly-only').forEach(el => el.style.display = yearly ? '' : 'none');
}

if (toggle) {
    toggle.addEventListener('change', () => switchBilling(toggle.checked));
}
</script>
@endpush
