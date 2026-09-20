@extends('layouts.app')
@section('title', 'WhatsApp Conversations')

@push('styles')
<style>
.filter-bar { display:flex; align-items:center; gap:10px; flex-wrap:wrap; margin-bottom:20px; }
.filter-input { padding:8px 12px; height:36px; background:var(--bg-input); border:1.5px solid var(--border-default); border-radius:var(--r-sm); color:var(--text-100); font-family:var(--font); font-size:13px; outline:none; }
.filter-input:focus { border-color:var(--accent); }
.pag-wrap { display:flex; align-items:center; justify-content:space-between; padding:14px 20px; border-top:1px solid var(--border-subtle); font-size:13px; color:var(--text-300); }
.pag-links { display:flex; gap:4px; }
.pg-btn { padding:5px 10px; border-radius:var(--r-sm); border:1px solid var(--border-default); color:var(--text-200); text-decoration:none; font-size:13px; transition:all .15s; }
.pg-btn:hover { border-color:var(--accent); color:var(--accent); }
.pg-btn.active { background:var(--accent); border-color:var(--accent); color:#fff; }
.pg-btn.disabled { opacity:.4; pointer-events:none; }
</style>
@endpush

@section('content')

<div class="page-head">
    <div>
        <div style="font-size:13px;color:var(--text-300);margin-bottom:4px">
            <a href="{{ route('tenant.whatsapp.chatbot') }}" style="color:var(--text-300);text-decoration:none">WhatsApp Chatbot</a>
            <span style="margin:0 6px">›</span> Conversations
        </div>
        <div class="page-title">Recent Conversations</div>
    </div>
    <a href="{{ route('tenant.whatsapp.chatbot') }}" class="btn btn-ghost">Back</a>
</div>

{{-- Filters --}}
<form method="GET" action="{{ route('tenant.whatsapp.conversations') }}">
    <div class="filter-bar">
        <input type="text" name="search" class="filter-input" placeholder="Search phone or name..."
               value="{{ request('search') }}" style="min-width:240px"/>
        <button type="submit" class="btn btn-secondary btn-sm">Search</button>
        @if(request()->hasAny(['search']))
        <a href="{{ route('tenant.whatsapp.conversations') }}" class="btn btn-secondary">Clear</a>
        @endif
    </div>
</form>

<div class="card">
    @if($sessions->isEmpty())
    <div style="padding:60px 20px;text-align:center;color:var(--text-300);font-size:13px">
        No conversations found
    </div>
    @else
    <div style="overflow-x:auto">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Phone</th>
                    <th>Name</th>
                    <th>Active Flow</th>
                    <th>Last Message</th>
                </tr>
            </thead>
            <tbody>
                @foreach($sessions as $session)
                <tr>
                    <td style="font-family:var(--mono);font-size:12.5px" data-label="Phone">{{ $session->wa_id }}</td>
                    <td style="font-weight:600;font-size:13.5px" data-label="Name">{{ $session->contact_name ?? '—' }}</td>
                    <td style="font-size:12.5px;color:var(--text-300)" data-label="Active Flow">{{ $session->flow?->name ?? '—' }}</td>
                    <td style="font-size:11.5px;color:var(--text-400);font-family:var(--mono)" data-label="Last Message">
                        {{ $session->last_message_at?->diffForHumans() ?? '—' }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if($sessions->hasPages())
    <div class="pag-wrap">
        <span>{{ $sessions->firstItem() }}–{{ $sessions->lastItem() }} of {{ $sessions->total() }}</span>
        <div class="pag-links">
            <a href="{{ $sessions->previousPageUrl() ?? '#' }}" class="pg-btn {{ !$sessions->previousPageUrl() ? 'disabled':'' }}">←</a>
            @foreach($sessions->getUrlRange(max(1,$sessions->currentPage()-2), min($sessions->lastPage(),$sessions->currentPage()+2)) as $page => $url)
            <a href="{{ $url }}" class="pg-btn {{ $page==$sessions->currentPage() ? 'active':'' }}">{{ $page }}</a>
            @endforeach
            <a href="{{ $sessions->nextPageUrl() ?? '#' }}" class="pg-btn {{ !$sessions->nextPageUrl() ? 'disabled':'' }}">→</a>
        </div>
    </div>
    @endif
    @endif
</div>

@endsection
