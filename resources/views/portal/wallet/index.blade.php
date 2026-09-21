@extends('layouts.portal')

@section('title', 'My Wallet')

@push('styles')
.pt-links { display:flex; justify-content:space-between; align-items:center; padding:12px 26px; font-size:12.5px; border-top:1px solid var(--border-subtle); }
.pt-links a, .pt-linkbtn { color:var(--accent); text-decoration:none; background:none; border:none; padding:0; font:inherit; cursor:pointer; }
.pt-pending { display:flex; flex-direction:column; gap:10px; padding:14px 0; border-bottom:1px solid var(--border-subtle); }
.pt-pending:last-child { border-bottom:none; padding-bottom:0; }
.pt-pending-actions { display:flex; gap:8px; }
.pt-pending-actions form { flex:1; }
.pt-pending-actions .pt-btn { margin-top:0; padding:10px; font-size:13px; }
.pt-empty { text-align:center; padding:32px 26px; }
.pt-empty .em { font-size:34px; }
.pt-empty p { color:var(--text-300); font-size:13px; margin:8px 0 0; }
@endpush

@section('content')
<div class="pt-card">
    <div class="pt-hero">
        <div class="pt-brand">Signed in as •••• {{ substr($customer->phone, -4) }}</div>
        <div class="pt-title">Hi {{ $customer->name ?: 'there' }} 👋</div>
    </div>
    <div class="pt-links">
        <a href="{{ route('portal.profile.edit') }}">Profile</a>
        <form method="POST" action="{{ route('portal.logout') }}" style="display:inline">
            @csrf
            <button type="submit" class="pt-linkbtn">Sign out</button>
        </form>
    </div>
</div>

@if($pending->isNotEmpty())
<div class="pt-card">
    <div class="pt-body">
        <div class="pt-step-title">Is this you?</div>
        <div class="pt-step-sub">These shops have an account on your number. Confirm the ones that are yours.</div>
        @foreach($pending as $contact)
        <div class="pt-pending">
            <div style="font-size:13.5px;color:var(--text-100)">
                <strong>{{ $contact->tenant->name }}</strong> —
                {{ number_format($contact->loyalty_points) }} points
            </div>
            <div class="pt-pending-actions">
                @foreach(['yes' => ['Yes, it\'s me', ''], 'no' => ['Not me', 'ghost']] as $answer => [$label, $class])
                <form method="POST" action="{{ route('portal.wallet.confirm', $contact->tenant_id) }}">
                    @csrf
                    <input type="hidden" name="contact_id" value="{{ $contact->id }}"/>
                    <input type="hidden" name="answer" value="{{ $answer }}"/>
                    <button type="submit" class="pt-btn {{ $class }}">{{ $label }}</button>
                </form>
                @endforeach
            </div>
        </div>
        @endforeach
    </div>
</div>
@endif

@forelse($cards as $card)
<x-portal.wallet-card
    :snapshot="$card['snapshot']"
    :tenant="$card['tenant']"
    :offers="$card['offers']"
    :href="route('portal.wallet.show', $card['tenant']->id)"/>
@empty
@if($pending->isEmpty())
<div class="pt-card">
    <div class="pt-empty">
        <div class="em">🎟️</div>
        <div class="pt-step-title" style="margin-top:8px">No rewards cards yet</div>
        <p>Visit a participating shop to start collecting. Your cards will show up here automatically.</p>
    </div>
</div>
@endif
@endforelse
@endsection
