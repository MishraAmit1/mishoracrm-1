@extends('layouts.app')
@section('title', 'Needs review')

@push('styles')
<style>
.sub-table { width:100%; border-collapse:collapse; background:var(--bg-surface); border:1px solid var(--border-default); border-radius:var(--r-lg); overflow:hidden; }
.sub-table th { padding:10px 14px; text-align:left; font-size:11px; font-weight:700; color:var(--text-400); text-transform:uppercase; letter-spacing:.4px; background:var(--bg-elevated); border-bottom:1px solid var(--border-subtle); }
.sub-table td { padding:11px 14px; border-bottom:1px solid var(--border-subtle); font-size:13.5px; color:var(--text-100); vertical-align:middle; }
.sub-table tr:last-child td { border-bottom:none; }
</style>
@endpush

@section('content')

<div class="page-head">
    <div>
        <div style="font-size:12px;color:var(--text-300);margin-bottom:4px">
            <a href="{{ route('tenant.loyalty.index') }}" style="color:var(--text-300);text-decoration:none">Loyalty</a> › Needs review
        </div>
        <div class="page-title">Needs review</div>
        <div class="page-sub">A customer told us these contacts aren't theirs — usually a wrong phone number typed at the counter. Correct the number on the contact, merge duplicates, or dismiss if the number is right.</div>
    </div>
</div>

@if(session('success'))
<div style="padding:10px 14px;background:var(--green-dim);border:1px solid rgba(52,199,89,.25);border-radius:var(--r-sm);margin-bottom:14px;font-size:13px;color:var(--green)">
    {{ session('success') }}
</div>
@endif

<div class="sub-table-wrap">
<table class="sub-table">
    <thead>
        <tr>
            <th>Contact</th>
            <th>Phone on file</th>
            <th style="text-align:right">Points</th>
            <th>Flagged</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
        @forelse($contacts as $c)
        <tr>
            <td style="font-weight:600"><a href="{{ route('tenant.contacts.show', $c->id) }}" style="color:var(--text-100);text-decoration:none">{{ $c->name }}</a></td>
            <td>{{ $c->phone ?? '—' }}</td>
            <td style="text-align:right">{{ number_format($c->loyalty_points) }}</td>
            <td>{{ $c->link_flagged_at->diffForHumans() }}</td>
            <td style="text-align:right;white-space:nowrap">
                <a href="{{ route('tenant.contacts.edit', $c->id) }}" class="btn btn-secondary btn-sm">Fix number</a>
                <form method="POST" action="{{ route('tenant.loyalty.needs-review.dismiss', $c->id) }}" style="display:inline">
                    @csrf
                    <button type="submit" class="btn btn-secondary btn-sm">Dismiss</button>
                </form>
            </td>
        </tr>
        @empty
        <tr><td colspan="5" style="text-align:center;padding:40px;color:var(--text-400)">Nothing to review. 🎉</td></tr>
        @endforelse
    </tbody>
</table>
</div>

<div style="margin-top:14px">{{ $contacts->links() }}</div>

@endsection
