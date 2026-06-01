@extends('layouts.app')
@section('title', 'Edit Plan — ' . $plan->name)

@push('styles')
@include('superadmin.plans._styles')
@endpush

@section('content')
<div class="page-content">
<div class="form-wrap">

    <a href="{{ route('superadmin.plans.index') }}" class="back-link">
        <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
        </svg>
        Back to Plans
    </a>

    <div class="form-card">
        <h2>Edit Plan</h2>
        <div style="font-size:13px;color:var(--text-400);margin-bottom:24px">
            Slug: <code style="background:var(--bg-input);padding:2px 6px;border-radius:4px;font-size:12px">{{ $plan->slug }}</code>
            &nbsp;·&nbsp;
            {{ $plan->activeSubscriptions()->count() }} active subscription(s)
        </div>

        @include('superadmin.plans._form', [
            'plan'   => $plan,
            'action' => route('superadmin.plans.update', $plan),
            'method' => 'PUT',
        ])
    </div>

</div>
</div>
@endsection
