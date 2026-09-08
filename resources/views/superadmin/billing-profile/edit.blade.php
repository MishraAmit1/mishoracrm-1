@extends('layouts.app')
@section('title', 'Billing Profile — Superadmin')

@push('styles')
<style>
.bp-wrap { max-width:820px; margin:0 auto; }
.back-link { display:flex; align-items:center; gap:6px; color:var(--text-400); font-size:13px; text-decoration:none; margin-bottom:18px; }
.back-link:hover { color:var(--text-100); }

.bp-card { background:var(--bg-surface); border:1px solid var(--border-default); border-radius:var(--r-lg); padding:26px 28px; margin-bottom:20px; }
.bp-card h2 { font-size:15px; font-weight:700; color:var(--text-100); margin:0 0 4px; }
.bp-card .bp-sub { font-size:12.5px; color:var(--text-400); margin-bottom:20px; }

.form-group { margin-bottom:16px; }
.form-label { display:block; font-size:13px; font-weight:600; color:var(--text-300); margin-bottom:6px; }
.form-control {
    width:100%; padding:9px 12px;
    background:var(--bg-input); border:1.5px solid var(--border-default);
    border-radius:var(--r-sm); color:var(--text-100); font-size:13.5px; outline:none;
    box-sizing:border-box; transition:border-color .15s, box-shadow .15s;
}
.form-control:focus { border-color:var(--accent); box-shadow:0 0 0 3px var(--accent-dim); }
.invalid-feedback { font-size:12px; color:var(--red); margin-top:4px; }
.form-hint { font-size:12px; color:var(--text-400); margin-top:4px; }
.form-row { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
.form-row-3 { display:grid; grid-template-columns:1fr 1fr 1fr; gap:16px; }
@media(max-width:600px){ .form-row, .form-row-3 { grid-template-columns:1fr; } }

textarea.form-control { min-height:80px; resize:vertical; font-family:inherit; line-height:1.5; }

.color-row { display:flex; align-items:center; gap:10px; }
.color-row input[type=color] { width:44px; height:38px; padding:2px; border:1.5px solid var(--border-default); border-radius:var(--r-sm); background:var(--bg-input); cursor:pointer; }

.toggle-line { display:flex; align-items:center; gap:10px; margin-bottom:16px; }
.toggle-line input[type=checkbox] { width:16px; height:16px; accent-color:var(--accent); }
.toggle-line label { font-size:13.5px; font-weight:600; color:var(--text-200); cursor:pointer; margin:0; }

.status-pill { display:inline-flex; align-items:center; gap:5px; font-size:11.5px; font-weight:700; padding:3px 10px; border-radius:20px; }
.status-pill.on  { background:var(--green-dim); color:var(--green); }
.status-pill.off { background:var(--bg-elevated); color:var(--text-400); border:1px solid var(--border-subtle); }

.form-footer { display:flex; gap:10px; margin-top:8px; }
.callout { display:flex; gap:9px; align-items:flex-start; background:var(--accent-dim); border:1px solid var(--border-subtle); border-radius:var(--r-md); padding:11px 13px; font-size:12.5px; color:var(--text-300); line-height:1.55; }
.callout svg { width:15px; height:15px; flex-shrink:0; margin-top:2px; color:var(--accent); }
</style>
@endpush

@section('content')
<div class="bp-wrap">

    <a href="{{ route('superadmin.plans.index') }}" class="back-link">
        <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
        Back to Plans
    </a>

    <div class="page-header" style="margin-bottom:18px">
        <div>
            <h1 class="page-title">Billing Profile</h1>
            <p class="page-sub">Your company's tax identity and invoice defaults — printed on every subscription tax invoice sent to tenants.</p>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success" style="margin-bottom:18px;">{{ session('success') }}</div>
    @endif

    <form action="{{ route('superadmin.billing-profile.update') }}" method="POST">
        @csrf
        @method('PUT')

        {{-- ── Company identity ────────────────────────────────── --}}
        <div class="bp-card">
            <h2>Company Identity (Seller)</h2>
            <div class="bp-sub">Shown as "Billed By" on the invoice. GSTIN drives the CGST/SGST vs IGST split against each tenant's state.</div>

            <div class="form-group">
                <label class="form-label">Legal / Registered Name</label>
                <input type="text" name="billing_legal_name" class="form-control @error('billing_legal_name') is-invalid @enderror"
                       value="{{ old('billing_legal_name', $settings['billing_legal_name']) }}" placeholder="{{ config('app.name') }} Technologies Pvt. Ltd.">
                @error('billing_legal_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <div class="form-hint">Leave blank to use "{{ config('app.name') }}".</div>
            </div>

            <div class="form-group">
                <label class="form-label">Registered Address</label>
                <textarea name="billing_address" class="form-control @error('billing_address') is-invalid @enderror"
                          placeholder="Street, area, landmark">{{ old('billing_address', $settings['billing_address']) }}</textarea>
                @error('billing_address')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="form-row-3">
                <div class="form-group">
                    <label class="form-label">City</label>
                    <input type="text" name="billing_city" class="form-control" value="{{ old('billing_city', $settings['billing_city']) }}">
                </div>
                <div class="form-group">
                    <label class="form-label">State</label>
                    <select name="billing_state" class="form-control @error('billing_state') is-invalid @enderror">
                        <option value="">— Select —</option>
                        @foreach($states as $code => $name)
                            <option value="{{ $name }}" {{ old('billing_state', $settings['billing_state']) === $name ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                    @error('billing_state')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="form-group">
                    <label class="form-label">PIN Code</label>
                    <input type="text" name="billing_pincode" class="form-control" value="{{ old('billing_pincode', $settings['billing_pincode']) }}">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">GSTIN</label>
                    <input type="text" name="billing_gstin" class="form-control @error('billing_gstin') is-invalid @enderror"
                           value="{{ old('billing_gstin', $settings['billing_gstin']) }}" placeholder="e.g. 29ABCDE1234F1Z5" style="text-transform:uppercase">
                    @error('billing_gstin')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <div class="form-hint">Blank = invoice prints "GST not charged" and no tax lines.</div>
                </div>
                <div class="form-group">
                    <label class="form-label">PAN</label>
                    <input type="text" name="billing_pan" class="form-control" value="{{ old('billing_pan', $settings['billing_pan']) }}" style="text-transform:uppercase">
                </div>
            </div>

            <div class="form-row-3">
                <div class="form-group">
                    <label class="form-label">Billing Email</label>
                    <input type="email" name="billing_email" class="form-control @error('billing_email') is-invalid @enderror"
                           value="{{ old('billing_email', $settings['billing_email']) }}" placeholder="billing@{{ str_replace(' ', '', strtolower(config('app.name'))) }}.com">
                    @error('billing_email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Billing Phone</label>
                    <input type="text" name="billing_phone" class="form-control" value="{{ old('billing_phone', $settings['billing_phone']) }}">
                </div>
                <div class="form-group">
                    <label class="form-label">Website</label>
                    <input type="text" name="billing_website" class="form-control" value="{{ old('billing_website', $settings['billing_website']) }}" placeholder="www.example.com">
                </div>
            </div>
        </div>

        {{-- ── Invoice defaults ────────────────────────────────── --}}
        <div class="bp-card">
            <h2>Invoice Defaults</h2>
            <div class="bp-sub">
                GST rate is set on the <a href="{{ route('superadmin.plans.index') }}" style="color:var(--accent)">Plans</a> page (currently
                <strong>{{ rtrim(rtrim(number_format((float) $gstPercentage, 2), '0'), '.') }}%</strong>, applied at checkout).
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Invoice Number Prefix</label>
                    <input type="text" name="invoice_prefix" class="form-control @error('invoice_prefix') is-invalid @enderror"
                           value="{{ old('invoice_prefix', $settings['invoice_prefix']) }}" placeholder="auto (initials of {{ config('app.name') }})">
                    @error('invoice_prefix')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <div class="form-hint">Numbers look like <code>PREFIX/26-27/00042</code>, reset each financial year.</div>
                </div>
                <div class="form-group">
                    <label class="form-label">HSN / SAC Code</label>
                    <input type="text" name="invoice_hsn_sac" class="form-control"
                           value="{{ old('invoice_hsn_sac', $settings['invoice_hsn_sac']) }}" placeholder="{{ $defaultSac }}">
                    <div class="form-hint">Default {{ $defaultSac }} — SaaS / online software services.</div>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Terms &amp; Conditions</label>
                <textarea name="invoice_terms" class="form-control" placeholder="{{ $defaultTerms }}">{{ old('invoice_terms', $settings['invoice_terms']) }}</textarea>
                <div class="form-hint">Blank = use the built-in default text.</div>
            </div>

            <div class="form-group">
                <label class="form-label">Footer Note (optional)</label>
                <input type="text" name="invoice_footer_note" class="form-control" value="{{ old('invoice_footer_note', $settings['invoice_footer_note']) }}"
                       placeholder="e.g. Thank you for choosing {{ config('app.name') }}!">
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Invoice Primary Colour</label>
                    <div class="color-row">
                        <input type="color" id="pc" value="{{ old('invoice_primary_color', $settings['invoice_primary_color'] ?: '#1e293b') }}"
                               oninput="document.getElementsByName('invoice_primary_color')[0].value=this.value">
                        <input type="text" name="invoice_primary_color" class="form-control @error('invoice_primary_color') is-invalid @enderror"
                               value="{{ old('invoice_primary_color', $settings['invoice_primary_color']) }}" placeholder="#1e293b"
                               oninput="document.getElementById('pc').value=this.value">
                    </div>
                    @error('invoice_primary_color')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Invoice Accent Colour</label>
                    <div class="color-row">
                        <input type="color" id="ac" value="{{ old('invoice_accent_color', $settings['invoice_accent_color'] ?: '#4f46e5') }}"
                               oninput="document.getElementsByName('invoice_accent_color')[0].value=this.value">
                        <input type="text" name="invoice_accent_color" class="form-control @error('invoice_accent_color') is-invalid @enderror"
                               value="{{ old('invoice_accent_color', $settings['invoice_accent_color']) }}" placeholder="#4f46e5"
                               oninput="document.getElementById('ac').value=this.value">
                    </div>
                    @error('invoice_accent_color')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
        </div>

        {{-- ── Platform WhatsApp ───────────────────────────────── --}}
        <div class="bp-card">
            <h2 style="display:flex;align-items:center;gap:8px">
                Platform WhatsApp Sender
                <span class="status-pill {{ $waEnabled ? 'on' : 'off' }}">{{ $waEnabled ? 'Active' : 'Not configured' }}</span>
            </h2>
            <div class="bp-sub">
                A Meta WhatsApp Cloud API number owned by {{ config('app.name') }} (separate from the per-tenant WhatsApp integration).
                Used to deliver the invoice link to the tenant owner after payment. If left off, invoices still go by email
                and tenants get a "Share on WhatsApp" link in-app.
            </div>

            <div class="toggle-line">
                <input type="hidden" name="platform_wa_enabled" value="0">
                <input type="checkbox" name="platform_wa_enabled" id="platform_wa_enabled" value="1" {{ old('platform_wa_enabled', $settings['platform_wa_enabled']) === '1' ? 'checked' : '' }}>
                <label for="platform_wa_enabled">Send invoice notifications via WhatsApp</label>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Phone Number ID</label>
                    <input type="text" name="platform_wa_phone_number_id" class="form-control @error('platform_wa_phone_number_id') is-invalid @enderror"
                           value="{{ old('platform_wa_phone_number_id', $settings['platform_wa_phone_number_id']) }}" placeholder="e.g. 123456789012345">
                    @error('platform_wa_phone_number_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <div class="form-hint">Meta → WhatsApp → API Setup → "Phone number ID".</div>
                </div>
                <div class="form-group">
                    <label class="form-label">Permanent Access Token</label>
                    <input type="password" name="platform_wa_access_token" class="form-control @error('platform_wa_access_token') is-invalid @enderror"
                           placeholder="{{ $settings['platform_wa_access_token'] ? '•••••••• (saved — blank keeps current)' : 'Paste system-user token' }}">
                    @error('platform_wa_access_token')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="callout">
                <svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>
                <span>Free-form WhatsApp text only reaches a user who messaged the number in the last 24&nbsp;hours. For guaranteed delivery, register an approved template — the plain-text send here is a best-effort convenience.</span>
            </div>
        </div>

        <div class="form-footer">
            <button type="submit" class="btn btn-primary">Save Billing Profile</button>
            <a href="{{ route('superadmin.plans.index') }}" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>
@endsection
