@extends('layouts.app')
@section('title', 'Add Vendor')

@push('styles')
<style>
@import url('https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=DM+Mono:wght@400;500&display=swap');
@import url('https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.19.0/dist/tabler-icons.min.css');

.vf-page { font-family: 'DM Sans', var(--font), sans-serif; }
.vf-main { background:var(--bg-surface); border:1px solid var(--border-default); border-radius:14px; overflow:hidden; max-width:760px; }
.vf-section { padding:22px 24px; border-bottom:1px solid var(--border-subtle); }
.vf-section:last-of-type { border-bottom:none; }
.vf-section-header { display:flex; align-items:flex-start; gap:12px; margin-bottom:18px; }
.vf-section-icon { width:32px; height:32px; border-radius:8px; display:flex; align-items:center; justify-content:center; flex-shrink:0; background:var(--accent-dim); }
.vf-section-title { font-size:13px; font-weight:600; color:var(--text-100); }
.vf-section-sub { font-size:12px; color:var(--text-300); margin-top:1px; }
.vf-grid { display:grid; grid-template-columns:1fr 1fr; gap:14px; }
.vf-grid .span-full { grid-column:1/-1; }
@media(max-width:640px){ .vf-grid { grid-template-columns:1fr; } .vf-grid .span-full { grid-column:1; } }
.vf-field { display:flex; flex-direction:column; gap:5px; }
.vf-label { font-size:11.5px; font-weight:600; color:var(--text-200); text-transform:uppercase; letter-spacing:0.5px; }
.vf-req { color:var(--red); margin-left:2px; }
.vf-input {
    width:100%; padding:9px 12px;
    background:var(--bg-input); border:1.5px solid var(--border-default);
    border-radius:8px; color:var(--text-100);
    font-family:'DM Sans',var(--font),sans-serif; font-size:13.5px; outline:none;
    transition:border-color .15s, box-shadow .15s, background .2s;
}
.vf-input:focus { border-color:var(--accent); box-shadow:0 0 0 3px var(--accent-dim); background:var(--bg-surface); }
.vf-input.is-error { border-color:var(--red); }
.vf-textarea { resize:vertical; min-height:80px; line-height:1.5; }
.vf-field-error { font-size:12px; color:var(--red); font-weight:500; }
.vf-footer { display:flex; align-items:center; justify-content:space-between; padding:16px 24px; background:var(--bg-elevated); border-top:1px solid var(--border-subtle); }
.vf-footer-note { font-size:12px; color:var(--text-300); }
.vf-footer-note strong { color:var(--text-200); }
</style>
@endpush

@section('content')
<div class="vf-page">

    <div class="page-head">
        <div>
            <div style="font-size:12px;color:var(--text-300);margin-bottom:4px;display:flex;align-items:center;gap:5px">
                <a href="{{ route('tenant.vendors.index') }}" style="color:var(--text-300);text-decoration:none">Vendors</a>
                <span style="opacity:.4">›</span>
                <span>Add Vendor</span>
            </div>
            <div class="page-title">Add New Vendor</div>
        </div>
        <a href="{{ route('tenant.vendors.index') }}" class="btn btn-secondary">
            <i class="ti ti-arrow-left" style="font-size:14px"></i> Back
        </a>
    </div>

    <form method="POST" action="{{ route('tenant.vendors.store') }}" novalidate>
        @csrf
        @include('tenant.vendors._form')

        <div class="vf-main" style="margin-top:14px">
            <div class="vf-footer">
                <div class="vf-footer-note">Fields marked <strong>*</strong> are required</div>
                <div style="display:flex;gap:8px">
                    <a href="{{ route('tenant.vendors.index') }}" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">
                        <i class="ti ti-plus" style="font-size:14px"></i> Create Vendor
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection
