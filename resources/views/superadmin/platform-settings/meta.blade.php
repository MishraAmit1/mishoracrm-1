@extends('layouts.app')
@section('title', 'Meta App Settings')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Meta App Settings</h1>
        <p class="page-sub">Platform-level Facebook / Instagram credentials — shared by all tenants</p>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success" style="margin-bottom:20px;">{{ session('success') }}</div>
@endif

<div style="max-width:560px;">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Meta Developer App</h3>
        </div>
        <div class="card-body">
            <p style="font-size:13px;color:var(--text-300);margin-bottom:20px;">
                These credentials are used for all tenants when they connect their Instagram Business account via QR code.
                Tenants do <strong>not</strong> need their own Meta developer account.
            </p>

            <form method="POST" action="{{ route('superadmin.platform-settings.meta.save') }}">
            @csrf

                <div class="form-group" style="margin-bottom:16px;">
                    <label class="form-label">App ID</label>
                    <input type="text" name="app_id" class="form-input" value="{{ old('app_id', $app_id) }}" placeholder="e.g. 1234567890123456" required>
                    <span class="form-hint">From developers.facebook.com → Your App → Settings → Basic</span>
                </div>

                <div class="form-group" style="margin-bottom:24px;">
                    <label class="form-label">App Secret</label>
                    <input type="password" name="app_secret" class="form-input" placeholder="{{ $app_secret ? 'Saved — leave blank to keep' : 'Enter App Secret' }}">
                    @if($app_secret)
                        <span class="form-hint" style="color:#16a34a;">App Secret is saved. Leave blank to keep current value.</span>
                    @else
                        <span class="form-hint">From developers.facebook.com → Your App → Settings → Basic</span>
                    @endif
                </div>

                <div style="display:flex;align-items:center;gap:12px;">
                    <button type="submit" class="btn btn-primary">Save Credentials</button>
                    @if($app_id && $app_secret)
                        <span style="font-size:13px;color:#16a34a;font-weight:600;">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px;display:inline;margin-right:3px;"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            Credentials configured
                        </span>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <div class="card" style="margin-top:16px;">
        <div class="card-header"><h3 class="card-title">Setup Checklist</h3></div>
        <div class="card-body">
            <ol style="font-size:13px;color:var(--text-200);line-height:2;padding-left:18px;">
                <li>Go to <strong>developers.facebook.com</strong> → Create App → Type: <strong>Business</strong></li>
                <li>Add product: <strong>Facebook Login</strong></li>
                <li>Facebook Login → Settings → Valid OAuth Redirect URIs:<br>
                    <code style="font-size:12px;background:var(--bg-subtle);padding:2px 6px;border-radius:4px;">{{ url('/instagram/oauth/callback') }}</code>
                </li>
                <li>Copy <strong>App ID</strong> and <strong>App Secret</strong> from App → Settings → Basic</li>
                <li>Switch app to <strong>Live mode</strong></li>
                <li>Paste here and save</li>
            </ol>
        </div>
    </div>
</div>
@endsection
