@extends('layouts.app')
@section('title', 'Loyalty Rules')

@push('styles')
<style>
.pf-card  { background:var(--bg-surface); border:1px solid var(--border-default); border-radius:var(--r-lg); overflow:hidden; max-width:760px; }
.pf-body  { padding:24px; display:flex; flex-direction:column; gap:22px; }
.pf-foot  { padding:14px 24px; background:var(--bg-elevated); border-top:1px solid var(--border-subtle); display:flex; justify-content:space-between; align-items:center; }
.pf-sec   { display:flex; flex-direction:column; gap:12px; }
.pf-sec-h { font-size:12px; font-weight:700; color:var(--text-200); text-transform:uppercase; letter-spacing:.5px; }
.field    { display:flex; flex-direction:column; gap:6px; }
.fl       { font-size:12px; font-weight:600; color:var(--text-200); }
.fh       { font-size:11px; color:var(--text-400); }
.fi       { padding:9px 13px; background:var(--bg-input); border:1.5px solid var(--border-default); border-radius:var(--r-sm); color:var(--text-100); font-family:var(--font); font-size:14px; outline:none; width:100%; }
.fg2 { display:grid; grid-template-columns:1fr 1fr; gap:14px; }
.fg3 { display:grid; grid-template-columns:1fr 1fr 1fr; gap:14px; }
.inline { display:flex; align-items:center; gap:8px; flex-wrap:wrap; font-size:13.5px; color:var(--text-200); }
.inline .fi { width:110px; }
@media(max-width:640px) { .fg2,.fg3 { grid-template-columns:1fr; } }
</style>
@endpush

@section('content')

<div class="page-head">
    <div>
        <div style="font-size:12px;color:var(--text-300);margin-bottom:4px">
            <a href="{{ route('tenant.loyalty.index') }}" style="color:var(--text-300);text-decoration:none">Loyalty</a> › Rules
        </div>
        <div class="page-title">Loyalty Rules</div>
        <div class="page-sub">You control all of these — the system just follows them.</div>
    </div>
    <a href="{{ route('tenant.loyalty.index') }}" class="btn btn-secondary">← Back</a>
</div>

@if(session('success'))
<div style="padding:10px 14px;background:var(--green-dim);border:1px solid rgba(52,199,89,.25);border-radius:var(--r-sm);margin-bottom:14px;font-size:13px;color:var(--green)">
    {{ session('success') }}
</div>
@endif
@if($errors->any())
<div style="padding:10px 14px;background:var(--red-dim);border:1px solid rgba(255,82,87,.25);border-radius:var(--r-sm);margin-bottom:14px;font-size:13px;color:var(--red)">
    {{ $errors->first() }}
</div>
@endif

<form method="POST" action="{{ route('tenant.loyalty.settings.update') }}">
@csrf
<div class="pf-card">
    <div class="pf-body">

        <div class="pf-sec">
            <div class="pf-sec-h">Earning</div>
            <div class="inline">
                Give
                <input type="number" name="points_per_amount" class="fi" min="0" max="1000" value="{{ old('points_per_amount', $settings['points_per_amount']) }}" required/>
                point(s) for every
                <input type="number" step="0.01" name="amount_per_point_block" class="fi" min="1" value="{{ old('amount_per_point_block', $settings['amount_per_point_block']) }}" required/>
                {{ $tenant->currency ?? '₹' }} a customer pays.
            </div>
            <div class="field">
                <label class="fl">Anti-abuse cap</label>
                <div class="inline">
                    Max
                    <input type="number" name="max_points_per_day" class="fi" min="0" value="{{ old('max_points_per_day', $settings['max_points_per_day']) }}" required/>
                    points one customer can earn per day. <span class="fh">(0 = no cap)</span>
                </div>
            </div>
            <div class="field">
                <label class="fl">Points expiry</label>
                <div class="inline">
                    Points expire
                    <input type="number" name="expiry_months" class="fi" min="0" max="120" value="{{ old('expiry_months', $settings['expiry_months']) }}" required/>
                    months after they are earned. <span class="fh">(0 = never expire)</span>
                </div>
            </div>
        </div>

        <div class="pf-sec">
            <div class="pf-sec-h">Redeeming</div>
            <div class="inline">
                <input type="number" name="redeem_points_block" class="fi" min="1" value="{{ old('redeem_points_block', $settings['redeem_points_block']) }}" required/>
                points are worth
                <input type="number" step="0.01" name="redeem_value" class="fi" min="0" value="{{ old('redeem_value', $settings['redeem_value']) }}" required/>
                {{ $tenant->currency ?? '₹' }} of discount.
            </div>
            <div class="fg2">
                <div class="field">
                    <label class="fl">Minimum discount per bill</label>
                    <input type="number" step="0.01" name="min_discount" class="fi" min="0" value="{{ old('min_discount', $settings['min_discount']) }}" required/>
                </div>
                <div class="field">
                    <label class="fl">Maximum discount per bill (% of bill)</label>
                    <input type="number" step="0.01" name="max_discount_percent" class="fi" min="0" max="100" value="{{ old('max_discount_percent', $settings['max_discount_percent']) }}" required/>
                </div>
            </div>
        </div>

        <div class="pf-sec">
            <div class="pf-sec-h">Customer messaging</div>
            <label class="inline" style="cursor:pointer">
                <input type="checkbox" name="notify_customers" value="1" @checked(old('notify_customers', $settings['notify_customers'] ?? false)) style="width:15px;height:15px"/>
                Message the customer (WhatsApp / Email) after every points earn — "you earned X points, balance Y"
            </label>
            <label class="inline" style="cursor:pointer">
                <input type="checkbox" name="whatsapp_self_check" value="1" @checked(old('whatsapp_self_check', $settings['whatsapp_self_check'] ?? false)) style="width:15px;height:15px"/>
                Let customers text <strong>points</strong> / <strong>balance</strong> to your WhatsApp number and get their balance back automatically
            </label>
            <div class="fh">Both need WhatsApp connected and/or SMTP set up under Settings. Off by default.</div>

            <label class="inline" style="cursor:pointer;margin-top:6px">
                <input type="checkbox" name="public_lookup" value="1" @checked(old('public_lookup', $settings['public_lookup'] ?? false)) style="width:15px;height:15px"/>
                Enable a public "check my rewards" page — customers verify with a one-time code, then see their points
            </label>
            @if($settings['public_lookup'] ?? false)
            <div class="fh">
                Public page: <code style="color:var(--accent)">{{ $tenant->rewardsPublicUrl() }}</code><br>
                API (server-side, with your API key): <code style="color:var(--accent)">GET /api/v1/tenant/loyalty/lookup?identifier=&lt;phone|email&gt;</code>
            </div>
            @endif
        </div>

        <div class="pf-sec">
            <div class="pf-sec-h">Engagement rewards <span style="font-weight:400;text-transform:none;color:var(--text-400)">(0 = feature off)</span></div>
            <div class="fg3">
                <div class="field">
                    <label class="fl">Birthday bonus points</label>
                    <input type="number" name="birthday_bonus_points" class="fi" min="0" value="{{ old('birthday_bonus_points', $settings['birthday_bonus_points']) }}" required/>
                </div>
                <div class="field">
                    <label class="fl">Anniversary bonus points</label>
                    <input type="number" name="anniversary_bonus_points" class="fi" min="0" value="{{ old('anniversary_bonus_points', $settings['anniversary_bonus_points']) }}" required/>
                </div>
                <div class="field">
                    <label class="fl">Referral bonus points <span class="fh">(each side)</span></label>
                    <input type="number" name="referral_bonus_points" class="fi" min="0" value="{{ old('referral_bonus_points', $settings['referral_bonus_points']) }}" required/>
                </div>
            </div>
            <div class="field">
                <label class="fl">Birthday message <span class="fh">— placeholders: @{{contact_name}} @{{points}} @{{balance}} @{{tenant_name}}</span></label>
                <textarea name="birthday_message" class="fi" rows="2" placeholder="{{ \App\Services\LoyaltyNotifier::DEFAULTS['birthday_message'] }}">{{ old('birthday_message', $settings['birthday_message'] ?? '') }}</textarea>
            </div>
            <div class="field">
                <label class="fl">Anniversary message</label>
                <textarea name="anniversary_message" class="fi" rows="2" placeholder="{{ \App\Services\LoyaltyNotifier::DEFAULTS['anniversary_message'] }}">{{ old('anniversary_message', $settings['anniversary_message'] ?? '') }}</textarea>
            </div>
        </div>

        <div class="pf-sec">
            <div class="pf-sec-h">Win-back</div>
            <div class="inline">
                Treat a customer as "gone quiet" after
                <input type="number" name="inactive_days" class="fi" min="1" value="{{ old('inactive_days', $settings['inactive_days']) }}" required/>
                days without a paid invoice.
            </div>
            <label class="inline" style="cursor:pointer">
                <input type="checkbox" name="winback_digest" value="1" @checked(old('winback_digest', $settings['winback_digest'] ?? false)) style="width:15px;height:15px"/>
                Email me a weekly digest of quiet loyalty customers
            </label>
            <div class="inline">
                Remind customers
                <input type="number" name="expiry_reminder_days" class="fi" min="0" max="90" value="{{ old('expiry_reminder_days', $settings['expiry_reminder_days'] ?? 7) }}"/>
                days before their points expire. <span class="fh">(0 = don't remind)</span>
            </div>
        </div>

        <div class="pf-sec">
            <div class="pf-sec-h">Flash / double-points days</div>
            <div class="inline">
                On the days below, multiply points earned by
                <input type="number" step="0.5" name="multiplier" class="fi" min="1" max="10" value="{{ old('multiplier', $settings['multiplier'] ?? 1) }}"/>
                ×
            </div>
            @php $mdays = (array) old('multiplier_days', $settings['multiplier_days'] ?? []); @endphp
            <div style="display:flex;gap:14px;flex-wrap:wrap">
                @foreach(['mon'=>'Mon','tue'=>'Tue','wed'=>'Wed','thu'=>'Thu','fri'=>'Fri','sat'=>'Sat','sun'=>'Sun'] as $d => $lbl)
                <label class="inline" style="cursor:pointer;gap:6px">
                    <input type="checkbox" name="multiplier_days[]" value="{{ $d }}" @checked(in_array($d, $mdays)) style="width:15px;height:15px"/> {{ $lbl }}
                </label>
                @endforeach
            </div>
        </div>

        <div class="pf-sec">
            <div class="pf-sec-h">Reward catalog <span style="font-weight:400;text-transform:none;color:var(--text-400)">— "X points = a free item"</span></div>
            <div class="fh">Staff redeem these on an invoice; the free item is added to the order manually.</div>
            @php
                $catalog = old('reward_name')
                    ? collect(old('reward_name'))->map(fn($n, $i) => ['name' => $n, 'points' => old("reward_points.$i")])->all()
                    : ($settings['reward_catalog'] ?? []);
                $catalog = array_pad(array_values($catalog), max(3, count($catalog) + 1), ['name' => '', 'points' => '']);
            @endphp
            @foreach($catalog as $row)
            <div class="fg2">
                <div class="field"><input type="text" name="reward_name[]" class="fi" value="{{ $row['name'] ?? '' }}" placeholder="e.g. Free regular coffee"/></div>
                <div class="field"><input type="number" name="reward_points[]" class="fi" min="1" value="{{ $row['points'] ?? '' }}" placeholder="Points cost"/></div>
            </div>
            @endforeach
        </div>

        <div class="pf-sec">
            <div class="pf-sec-h">WhatsApp "scan to join" welcome</div>
            <div class="fh">Print a QR on bills / tables. A customer scans, WhatsApp opens with a prefilled word, they send it — and they're auto-enrolled with a welcome bonus. No form to fill.</div>
            <div class="fg3">
                <div class="field">
                    <label class="fl">Welcome bonus points <span class="fh">(0 = off)</span></label>
                    <input type="number" name="welcome_bonus_points" class="fi" min="0" value="{{ old('welcome_bonus_points', $settings['welcome_bonus_points'] ?? 0) }}"/>
                </div>
                <div class="field">
                    <label class="fl">Your WhatsApp Business number</label>
                    <input type="text" name="welcome_wa_number" class="fi" value="{{ old('welcome_wa_number', $settings['welcome_wa_number'] ?? '') }}" placeholder="e.g. 919876543210"/>
                </div>
                <div class="field">
                    <label class="fl">Keyword</label>
                    <input type="text" name="welcome_keyword" class="fi" value="{{ old('welcome_keyword', $settings['welcome_keyword'] ?? 'JOIN') }}"/>
                </div>
            </div>
            <div class="field">
                <label class="fl">Welcome message <span class="fh">— placeholders: @{{contact_name}} @{{points}} @{{tenant_name}}</span></label>
                <textarea name="welcome_message" class="fi" rows="2" placeholder="{{ \App\Models\Tenant::LOYALTY_DEFAULTS['welcome_message'] }}">{{ old('welcome_message', $settings['welcome_message'] ?? '') }}</textarea>
            </div>
            @php $welcomeUrl = $tenant->loyaltyWelcomeUrl(); @endphp
            @if($welcomeUrl)
            <div class="fh" style="display:flex;gap:16px;align-items:center;flex-wrap:wrap;margin-top:6px">
                <img src="https://quickchart.io/qr?size=150&text={{ urlencode($welcomeUrl) }}" alt="Join QR" width="150" height="150" style="border:1px solid var(--border-default);border-radius:8px;background:#fff"/>
                <div>
                    Link: <code style="color:var(--accent)">{{ $welcomeUrl }}</code><br>
                    Print this QR on your bills or put it on tables. Save the rules first to refresh it.
                </div>
            </div>
            @endif
        </div>

        <div class="pf-sec">
            <div class="pf-sec-h">Tiers — by lifetime points earned</div>
            <div class="fh">A customer keeps their tier even after spending points. Thresholds must increase from Bronze to Gold.</div>
            <div class="fg3">
                <div class="field">
                    <label class="fl">Bronze from</label>
                    <input type="number" name="tiers[bronze]" class="fi" min="0" value="{{ old('tiers.bronze', $settings['tiers']['bronze']) }}" required/>
                </div>
                <div class="field">
                    <label class="fl">Silver from</label>
                    <input type="number" name="tiers[silver]" class="fi" min="0" value="{{ old('tiers.silver', $settings['tiers']['silver']) }}" required/>
                </div>
                <div class="field">
                    <label class="fl">Gold from</label>
                    <input type="number" name="tiers[gold]" class="fi" min="0" value="{{ old('tiers.gold', $settings['tiers']['gold']) }}" required/>
                </div>
            </div>
        </div>

        @if($tenant->hasModuleEnabled('customer_portal'))
        <div class="pf-sec">
            <div class="pf-sec-h">Customer Portal</div>
            <div class="fh">
                Customer Portal is <strong style="color:var(--green)">ON</strong> for your shop. Customers who share their phone number with you can sign in at
                <code style="color:var(--accent)">{{ \App\Models\Tenant::portalLoginUrl() }}</code> and see their points, tier and offers here in one wallet alongside other shops.
                Your customers' data stays yours — other shops never see it.
            </div>
            <div style="display:flex;gap:8px;flex-wrap:wrap">
                <a href="{{ route('tenant.loyalty.qr-kit.index') }}" class="btn btn-secondary btn-sm">Print QR Kit</a>
                <a href="{{ route('tenant.loyalty.needs-review') }}" class="btn btn-secondary btn-sm">Contacts needing review</a>
            </div>
        </div>

        <div class="pf-sec">
            <div class="pf-sec-h">Stamp card</div>
            <div class="fh">Prefer "buy 5, get the 6th free" over points? Choose how the customer's wallet card works. Stamps are added at the counter (or automatically when a bill is paid).</div>
            <div class="field">
                <label class="fl">Card type</label>
                <select name="mode" class="fi">
                    @foreach(['points' => 'Points only', 'stamps' => 'Stamp card only', 'both' => 'Stamp card + points'] as $value => $label)
                    <option value="{{ $value }}" @selected(old('mode', $settings['mode'] ?? 'points') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="fg2">
                <div class="field">
                    <label class="fl">Stamps to fill a card</label>
                    <input type="number" name="stamps_required" class="fi" min="1" max="30" value="{{ old('stamps_required', $settings['stamps_required'] ?? 5) }}"/>
                </div>
                <div class="field">
                    <label class="fl">Reward for a full card</label>
                    <input type="text" name="stamp_reward" class="fi" maxlength="120" placeholder="e.g. Free regular coffee" value="{{ old('stamp_reward', $settings['stamp_reward'] ?? '') }}"/>
                </div>
            </div>
            <div class="fg3">
                <div class="field">
                    <label class="fl">A stamp is earned</label>
                    <select name="stamp_per" class="fi">
                        <option value="visit" @selected(in_array(old('stamp_per', $settings['stamp_per'] ?? 'visit'), ['visit', 'invoice'], true))>Once per paid bill / visit</option>
                        <option value="amount" @selected(old('stamp_per', $settings['stamp_per'] ?? 'visit') === 'amount')>Per amount spent</option>
                    </select>
                </div>
                <div class="field">
                    <label class="fl">Amount per stamp <span class="fh">(₹)</span></label>
                    <input type="number" name="stamp_amount" class="fi" min="1" step="1" value="{{ old('stamp_amount', $settings['stamp_amount'] ?? 100) }}"/>
                </div>
                <div class="field">
                    <label class="fl">Reset idle cards after <span class="fh">(days, 0 = never)</span></label>
                    <input type="number" name="stamp_expiry_days" class="fi" min="0" value="{{ old('stamp_expiry_days', $settings['stamp_expiry_days'] ?? 0) }}"/>
                </div>
            </div>
        </div>
        @endif

    </div>
    <div class="pf-foot">
        <a href="{{ route('tenant.loyalty.index') }}" class="btn btn-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary">Save Rules</button>
    </div>
</div>
</form>

@endsection
