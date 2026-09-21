@extends('layouts.portal')

@section('title', 'Sign in')

@section('content')
<div class="pt-card">
    <div class="pt-hero">
        <div class="pt-brand">One login for all your shops</div>
        <div class="pt-title">My Rewards Wallet</div>
    </div>

    <div class="pt-body">
        <div class="pt-step-title">Sign in with your phone</div>
        <div class="pt-step-sub">Enter the mobile number you use at your favourite shops. We'll send a one-time code to confirm it's you.</div>

        <form method="POST" action="{{ route('portal.login.request-otp') }}">
            @csrf
            <div class="pt-field">
                <label for="phone">Mobile number</label>
                <input type="tel" name="phone" id="phone" class="pt-input" value="{{ old('phone') }}" inputmode="numeric" autocomplete="tel" required autofocus/>
                @error('phone')<span class="pt-err">{{ $message }}</span>@enderror
            </div>
            <button type="submit" class="pt-btn">Send code</button>
        </form>

        <div class="pt-divider">or use your PIN</div>

        <form method="POST" action="{{ route('portal.login.pin') }}">
            @csrf
            <div class="pt-field">
                <label for="pin_phone">Mobile number</label>
                <input type="tel" name="phone" id="pin_phone" class="pt-input" inputmode="numeric" autocomplete="tel" required/>
            </div>
            <div class="pt-field">
                <label for="pin">PIN</label>
                <input type="password" name="pin" id="pin" class="pt-input" inputmode="numeric" maxlength="6" autocomplete="current-password" required/>
                @error('pin')<span class="pt-err">{{ $message }}</span>@enderror
            </div>
            <button type="submit" class="pt-btn ghost">Sign in with PIN</button>
        </form>
    </div>
</div>
@endsection
