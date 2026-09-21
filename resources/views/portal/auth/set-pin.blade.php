@extends('layouts.portal')

@section('title', 'Set a PIN')

@section('content')
<div class="pt-card">
    <div class="pt-hero">
        <div class="pt-brand">Faster sign-in</div>
        <div class="pt-title">Set a PIN</div>
    </div>

    <div class="pt-body">
        <div class="pt-step-title">Skip the code next time</div>
        <div class="pt-step-sub">Choose a 4–6 digit PIN. You can always sign in with a one-time code instead.</div>

        <form method="POST" action="{{ route('portal.pin.set') }}">
            @csrf
            <div class="pt-field">
                <label for="pin">New PIN</label>
                <input type="password" name="pin" id="pin" class="pt-input" inputmode="numeric" maxlength="6" autocomplete="new-password" required autofocus/>
                @error('pin')<span class="pt-err">{{ $message }}</span>@enderror
            </div>
            <div class="pt-field">
                <label for="pin_confirmation">Confirm PIN</label>
                <input type="password" name="pin_confirmation" id="pin_confirmation" class="pt-input" inputmode="numeric" maxlength="6" autocomplete="new-password" required/>
            </div>
            <button type="submit" class="pt-btn">Save PIN</button>
        </form>

        <div class="pt-foot"><a href="/wallet">Skip for now</a></div>
    </div>
</div>
@endsection
