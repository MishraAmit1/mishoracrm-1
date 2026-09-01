@extends('layouts.app')
@section('title', 'New Campaign')

@push('styles')
<style>
.pf-card { background:var(--bg-surface); border:1px solid var(--border-default); border-radius:var(--r-lg); overflow:hidden; max-width:760px; }
.pf-body { padding:24px; display:flex; flex-direction:column; gap:20px; }
.pf-foot { padding:14px 24px; background:var(--bg-elevated); border-top:1px solid var(--border-subtle); display:flex; justify-content:space-between; }
.pf-sec-h { font-size:12px; font-weight:700; color:var(--text-200); text-transform:uppercase; letter-spacing:.5px; margin-bottom:10px; }
.field { display:flex; flex-direction:column; gap:6px; margin-bottom:12px; }
.fl { font-size:12px; font-weight:600; color:var(--text-200); }
.fi { padding:9px 13px; background:var(--bg-input); border:1.5px solid var(--border-default); border-radius:var(--r-sm); color:var(--text-100); font-size:14px; width:100%; }
.fg2 { display:grid; grid-template-columns:1fr 1fr; gap:14px; }
.hidden { display:none; }
@media(max-width:640px){ .fg2 { grid-template-columns:1fr; } }
</style>
@endpush

@section('content')

<div class="page-head">
    <div>
        <div style="font-size:12px;color:var(--text-300);margin-bottom:4px">
            <a href="{{ route('tenant.loyalty.campaigns.index') }}" style="color:var(--text-300);text-decoration:none">Campaigns</a> › New
        </div>
        <div class="page-title">New Campaign</div>
    </div>
</div>

@if($errors->any())
<div style="padding:10px 14px;background:var(--red-dim);border:1px solid rgba(255,82,87,.25);border-radius:var(--r-sm);margin-bottom:14px;font-size:13px;color:var(--red)">{{ $errors->first() }}</div>
@endif

<form method="POST" action="{{ route('tenant.loyalty.campaigns.store') }}">
@csrf
<div class="pf-card">
    <div class="pf-body">

        <div class="field">
            <label class="fl">Campaign name</label>
            <input type="text" name="name" class="fi" value="{{ old('name') }}" required placeholder="e.g. Win back March lapsed customers"/>
        </div>

        <div>
            <div class="pf-sec-h">Audience</div>
            <div class="field">
                <label class="fl">Segment</label>
                <select name="segment_type" class="fi" id="segType" onchange="segToggle()">
                    <option value="spend">Total spend is at least…</option>
                    <option value="visits">Number of paid visits is at least…</option>
                    <option value="inactive">Hasn't visited in N days (win-back)</option>
                    <option value="tier">Loyalty tier is…</option>
                    <option value="category">Spends on a product/service category…</option>
                    <option value="manual">Hand-picked customers</option>
                </select>
            </div>

            <div class="seg seg-spend fg2">
                <div class="field"><label class="fl">Minimum spend (₹)</label><input type="number" step="0.01" name="min_spend" class="fi" value="{{ old('min_spend', 5000) }}"/></div>
                <div class="field"><label class="fl">Within last N days (blank = all time)</label><input type="number" name="spend_within_days" class="fi" value="{{ old('spend_within_days') }}"/></div>
            </div>
            <div class="seg seg-visits fg2 hidden">
                <div class="field"><label class="fl">Minimum paid visits</label><input type="number" name="min_visits" class="fi" value="{{ old('min_visits', 5) }}"/></div>
                <div class="field"><label class="fl">Within last N days (blank = all time)</label><input type="number" name="visits_within_days" class="fi" value="{{ old('visits_within_days') }}"/></div>
            </div>
            <div class="seg seg-inactive field hidden">
                <label class="fl">Inactive for at least (days)</label>
                <input type="number" name="inactive_days" class="fi" value="{{ old('inactive_days', 60) }}"/>
            </div>
            <div class="seg seg-tier field hidden">
                <label class="fl">Tier</label>
                <select name="tier" class="fi"><option value="bronze">Bronze</option><option value="silver">Silver</option><option value="gold" selected>Gold</option></select>
            </div>
            <div class="seg seg-category hidden">
                <div class="field"><label class="fl">Category (as tagged on products/services)</label><input type="text" name="category" class="fi" value="{{ old('category') }}" placeholder="e.g. Beverages"/></div>
                <div class="fg2">
                    <div class="field"><label class="fl">Minimum spend in this category (₹, 0 = any)</label><input type="number" step="0.01" name="category_min_spend" class="fi" value="{{ old('category_min_spend', 0) }}"/></div>
                    <div class="field"><label class="fl">Within last N days (blank = all time)</label><input type="number" name="category_within_days" class="fi" value="{{ old('category_within_days') }}"/></div>
                </div>
            </div>
            <div class="seg seg-manual field hidden">
                <label class="fl">Customers</label>
                <select name="contact_ids[]" class="fi" multiple size="8">
                    @foreach($contacts as $ct)
                    <option value="{{ $ct->id }}">{{ $ct->name }}{{ $ct->phone ? ' · ' . $ct->phone : '' }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div>
            <div class="pf-sec-h">Reward</div>
            <div class="fg2">
                <div class="field">
                    <label class="fl">Type</label>
                    <select name="reward_type" class="fi" id="rewType" onchange="rewToggle()">
                        <option value="percent">% discount</option>
                        <option value="flat">Flat ₹ off</option>
                        <option value="points">Bonus points (paid out on launch)</option>
                        <option value="free_item">Free item</option>
                    </select>
                </div>
                <div class="field rew rew-value">
                    <label class="fl" id="rewValueLabel">Discount %</label>
                    <input type="number" step="0.01" name="reward_value" class="fi" value="{{ old('reward_value', 10) }}"/>
                </div>
                <div class="field rew rew-max">
                    <label class="fl">Max discount cap (₹, optional)</label>
                    <input type="number" step="0.01" name="max_discount" class="fi" value="{{ old('max_discount') }}"/>
                </div>
                <div class="field rew rew-item hidden">
                    <label class="fl">Free item</label>
                    <input type="text" name="reward_item" class="fi" value="{{ old('reward_item') }}" placeholder="e.g. Regular coffee"/>
                </div>
            </div>
        </div>

        <div>
            <div class="pf-sec-h">Code &amp; guardrails</div>
            <div class="fg2">
                <div class="field">
                    <label class="fl">Code type</label>
                    <select name="code_mode" class="fi" id="codeMode" onchange="codeToggle()">
                        <option value="unique">Unique code per customer</option>
                        <option value="shared">One shared code</option>
                    </select>
                </div>
                <div class="field code-shared hidden">
                    <label class="fl">Shared code</label>
                    <input type="text" name="shared_code" class="fi" value="{{ old('shared_code') }}" placeholder="e.g. WELCOME10"/>
                </div>
                <div class="field">
                    <label class="fl">Uses allowed per customer</label>
                    <input type="number" name="usage_limit_per_customer" class="fi" value="{{ old('usage_limit_per_customer', 1) }}" min="1"/>
                </div>
                <div class="field">
                    <label class="fl">Total redemption cap (optional budget guard)</label>
                    <input type="number" name="total_redemption_cap" class="fi" value="{{ old('total_redemption_cap') }}" min="1"/>
                </div>
                <div class="field">
                    <label class="fl">Expires on (optional)</label>
                    <input type="date" name="expires_at" class="fi" value="{{ old('expires_at') }}"/>
                </div>
                <div class="field">
                    <label class="fl">Send the offer via</label>
                    <select name="delivery" class="fi">
                        <option value="none">Don't send (redeem code only)</option>
                        <option value="whatsapp">WhatsApp</option>
                        <option value="email">Email</option>
                        <option value="both">WhatsApp + Email</option>
                    </select>
                </div>
            </div>
        </div>

    </div>
    <div class="pf-foot">
        <a href="{{ route('tenant.loyalty.campaigns.index') }}" class="btn btn-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary">Save Draft</button>
    </div>
</div>
</form>

<script>
function segToggle() {
    const t = document.getElementById('segType').value;
    document.querySelectorAll('.seg').forEach(el => el.classList.add('hidden'));
    document.querySelectorAll('.seg-' + t).forEach(el => el.classList.remove('hidden'));
}
function rewToggle() {
    const t = document.getElementById('rewType').value;
    const isItem = t === 'free_item';
    document.querySelector('.rew-value').classList.toggle('hidden', isItem);
    document.querySelector('.rew-max').classList.toggle('hidden', t !== 'percent');
    document.querySelector('.rew-item').classList.toggle('hidden', !isItem);
    document.getElementById('rewValueLabel').textContent = t === 'percent' ? 'Discount %' : (t === 'flat' ? 'Amount (₹)' : 'Points');
}
function codeToggle() {
    document.querySelector('.code-shared').classList.toggle('hidden', document.getElementById('codeMode').value !== 'shared');
}
segToggle(); rewToggle(); codeToggle();
</script>

@endsection
