@extends('layouts.app')
@section('title', 'Create Plan')

@push('styles')
@include('superadmin.plans._styles')
@endpush

@section('content')
<div class="form-wrap">

    <a href="{{ route('superadmin.plans.index') }}" class="back-link">
        <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
        </svg>
        Back to Plans
    </a>

    <div class="form-card">
        <h2>Create New Plan</h2>
        @include('superadmin.plans._form', [
            'action' => route('superadmin.plans.store'),
            'method' => 'POST',
        ])
    </div>

</div>
@endsection
