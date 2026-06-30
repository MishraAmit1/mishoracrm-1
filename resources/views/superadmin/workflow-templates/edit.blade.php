@extends('layouts.app')
@section('title', 'Edit Workflow Template')

@push('styles')
@include('superadmin.workflow-templates._styles')
@endpush

@section('content')
<div class="page-content">
<div class="form-wrap">

    <a href="{{ route('superadmin.workflow-templates.index') }}" class="back-link">
        <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
        </svg>
        Back to Templates
    </a>

    <div class="form-card">
        <h2>Edit Template</h2>
        <p style="font-size:13px;color:var(--text-400);margin-top:4px;margin-bottom:24px;">
            {{ $template->title }}
        </p>

        @include('superadmin.workflow-templates._form', [
            'action'   => route('superadmin.workflow-templates.update', $template),
            'method'   => 'PUT',
            'template' => $template,
        ])
    </div>

</div>
</div>
@endsection
