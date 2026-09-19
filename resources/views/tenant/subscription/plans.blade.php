@extends('layouts.app')
@section('title', 'Choose Your Plan')

@section('content')
<div class="page-content">
<div class="plans-wrap">

    <div class="plans-header">
        <h1>Choose Your Plan</h1>
        <p>Pick the plan that fits your team. You can upgrade or downgrade at any time — your data stays intact.</p>
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

    @include('tenant.subscription._plan-grid')

</div>
</div>
@endsection
