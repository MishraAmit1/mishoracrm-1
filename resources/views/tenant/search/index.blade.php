@extends('layouts.app')
@section('title', 'Search')

@push('styles')
<style>
.sr-section{background:var(--bg-surface);border:1px solid var(--border-default);border-radius:14px;margin-bottom:16px;overflow:hidden}
.sr-section-head{padding:12px 16px;background:var(--bg-elevated);border-bottom:1px solid var(--border-subtle);display:flex;align-items:center;justify-content:space-between}
.sr-section-title{font-size:12.5px;font-weight:700;color:var(--text-200);text-transform:uppercase;letter-spacing:.5px}
.sr-row{display:flex;align-items:center;justify-content:space-between;gap:14px;padding:11px 16px;border-bottom:1px solid var(--border-subtle);text-decoration:none;color:inherit;transition:background .12s}
.sr-row:last-child{border-bottom:none}
.sr-row:hover{background:var(--bg-elevated)}
.sr-name{font-size:13.5px;font-weight:600;color:var(--text-100)}
.sr-meta{font-size:12px;color:var(--text-300);margin-top:2px}
.sr-empty{padding:24px 16px;text-align:center;color:var(--text-400);font-size:13px}
.sr-see-all{font-size:12px;color:var(--accent);text-decoration:none;font-weight:600}
.sr-noquery{background:var(--bg-surface);border:1px solid var(--border-default);border-radius:14px;padding:48px 20px;text-align:center;color:var(--text-300)}
</style>
@endpush

@section('content')
<div class="page-head">
    <div>
        <div class="page-title">Search Results</div>
        <div class="page-sub">@if($q !== '') Showing results for "{{ $q }}" @else Type a search term to get started @endif</div>
    </div>
</div>

@if ($q === '')
<div class="sr-noquery">Use the search box at the top (or press Ctrl/Cmd+K) to search leads, contacts and deals.</div>
@else

<div class="sr-section">
    <div class="sr-section-head">
        <span class="sr-section-title">Leads ({{ $leads->count() }})</span>
        <a href="{{ route('tenant.leads.index', ['search' => $q]) }}" class="sr-see-all">See all results &rarr;</a>
    </div>
    @forelse ($leads as $lead)
    <a href="{{ route('tenant.leads.show', $lead->id) }}" class="sr-row">
        <div>
            <div class="sr-name">{{ $lead->name }}</div>
            <div class="sr-meta">{{ $lead->phone }} @if($lead->company) &middot; {{ $lead->company }} @endif</div>
        </div>
        <span class="s-badge">{{ ucfirst($lead->status) }}</span>
    </a>
    @empty
    <div class="sr-empty">No matching leads.</div>
    @endforelse
</div>

<div class="sr-section">
    <div class="sr-section-head">
        <span class="sr-section-title">Contacts ({{ $contacts->count() }})</span>
        <a href="{{ route('tenant.contacts.index', ['search' => $q]) }}" class="sr-see-all">See all results &rarr;</a>
    </div>
    @forelse ($contacts as $contact)
    <a href="{{ route('tenant.contacts.show', $contact->id) }}" class="sr-row">
        <div>
            <div class="sr-name">{{ $contact->name }}</div>
            <div class="sr-meta">{{ $contact->phone }} @if($contact->company) &middot; {{ $contact->company }} @endif</div>
        </div>
    </a>
    @empty
    <div class="sr-empty">No matching contacts.</div>
    @endforelse
</div>

<div class="sr-section">
    <div class="sr-section-head">
        <span class="sr-section-title">Deals ({{ $deals->count() }})</span>
        <a href="{{ route('tenant.deals.index', ['search' => $q]) }}" class="sr-see-all">See all results &rarr;</a>
    </div>
    @forelse ($deals as $deal)
    <a href="{{ route('tenant.deals.show', $deal->id) }}" class="sr-row">
        <div>
            <div class="sr-name">{{ $deal->title }}</div>
            <div class="sr-meta">{{ $deal->formatted_value }}</div>
        </div>
        <span class="s-badge">{{ ucfirst($deal->stage) }}</span>
    </a>
    @empty
    <div class="sr-empty">No matching deals.</div>
    @endforelse
</div>

@endif
@endsection
