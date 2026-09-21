@extends('layouts.portal')

@section('title', 'Profile')

@section('content')
<div class="pt-card">
    <div class="pt-hero">
        <div class="pt-brand">•••• {{ substr($customer->phone, -4) }}</div>
        <div class="pt-title">My profile</div>
    </div>

    <div class="pt-body">
        <form method="POST" action="{{ route('portal.profile.update') }}">
            @csrf
            <div class="pt-field">
                <label for="name">Name</label>
                <input type="text" name="name" id="name" class="pt-input" maxlength="150" value="{{ old('name', $customer->name) }}"/>
                @error('name')<span class="pt-err">{{ $message }}</span>@enderror
            </div>
            <div class="pt-field">
                <label for="email">Email</label>
                <input type="email" name="email" id="email" class="pt-input" maxlength="150" value="{{ old('email', $customer->email) }}"/>
                @error('email')<span class="pt-err">{{ $message }}</span>@enderror
            </div>
            <button type="submit" class="pt-btn">Save</button>
        </form>

        <div class="pt-foot"><a href="{{ route('portal.pin.setup') }}">{{ $customer->pin_hash ? 'Change my PIN' : 'Set a PIN' }}</a> · <a href="{{ route('portal.wallet.index') }}">Back to wallet</a></div>
    </div>
</div>

<div class="pt-card">
    <div class="pt-body">
        <div class="pt-step-title" style="color:var(--red)">Delete my account</div>
        <div class="pt-step-sub">
            This removes your wallet login and clears your name, email and PIN. Each shop keeps the records it already
            has (they belong to the shop), but they will no longer be linked to you. If you sign in with this number
            again later, your cards can be linked back.
        </div>
        <form method="POST" action="{{ route('portal.account.delete') }}" onsubmit="return confirm('Delete your wallet account? You will be signed out.')">
            @csrf
            <div class="pt-field">
                <label for="confirm">Type DELETE to confirm</label>
                <input type="text" name="confirm" id="confirm" class="pt-input" autocomplete="off" required/>
                @error('confirm')<span class="pt-err">{{ $message }}</span>@enderror
            </div>
            <button type="submit" class="pt-btn ghost" style="color:var(--red);border-color:var(--red)">Delete my account</button>
        </form>
    </div>
</div>
@endsection
