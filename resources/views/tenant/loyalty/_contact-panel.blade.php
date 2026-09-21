{{-- Loyalty panel for the Contact detail sidebar. Expects $contact with
     loyaltyTransactions eager-loaded. Rendered only when the loyalty module
     is enabled (guard in the parent view). --}}
@php
    $lp        = $contact->loyaltyTransactions;
    $typeCfg   = config('crm.loyalty.transaction_types');
    $tierClass = ['bronze' => 'var(--amber)', 'silver' => 'var(--accent)', 'gold' => 'var(--green)'];
@endphp

<div class="cs-card">
    <div style="padding:14px 16px 4px">
        <div class="cs-card-title">Loyalty</div>
    </div>
    <div>
        <div class="cs-dl-row">
            <span class="cs-dl-key">Points balance</span>
            <span class="cs-dl-val" style="font-weight:700">{{ number_format($contact->loyalty_points) }}</span>
        </div>
        <div class="cs-dl-row">
            <span class="cs-dl-key">Tier</span>
            <span class="cs-dl-val">
                @if($contact->loyalty_tier)
                <span style="display:inline-block;padding:2px 9px;border-radius:20px;font-size:11.5px;font-weight:600;color:{{ $tierClass[$contact->loyalty_tier] ?? 'var(--text-300)' }};background:var(--bg-elevated)">
                    {{ $contact->loyaltyTierLabel() }}
                </span>
                @else — @endif
            </span>
        </div>
        <div class="cs-dl-row">
            <span class="cs-dl-key">Lifetime points</span>
            <span class="cs-dl-val">{{ number_format($contact->loyalty_lifetime_points) }}</span>
        </div>
        @php $stampRules = auth()->user()->tenant->loyaltySettings(); @endphp
        @if(in_array($stampRules['mode'] ?? 'points', ['stamps', 'both'], true))
        <div class="cs-dl-row">
            <span class="cs-dl-key">Stamp card</span>
            <span class="cs-dl-val">
                {{ (int) $contact->stamp_count }}/{{ (int) $stampRules['stamps_required'] }}
                @if($contact->stamp_rewards_earned > 0)
                <span style="color:var(--green);font-weight:600">· {{ $contact->stamp_rewards_earned }} reward{{ $contact->stamp_rewards_earned === 1 ? '' : 's' }} unclaimed</span>
                @endif
            </span>
        </div>
        @endif
        @if(auth()->user()->tenant->hasModuleEnabled('customer_portal'))
        <div class="cs-dl-row">
            <span class="cs-dl-key">Customer account</span>
            <span class="cs-dl-val">
                @if($contact->customer_id && $contact->phone_verified)
                    Linked
                    @if($contact->customer?->last_login_at)
                    <span style="color:var(--text-400)">· last login {{ $contact->customer->last_login_at->diffForHumans() }}</span>
                    @endif
                @elseif($contact->customer_id)
                    <span style="color:var(--amber)">Pending — customer confirmation</span>
                @elseif($contact->link_flagged_at)
                    <span style="color:var(--red)">Flagged — customer said "not me"</span>
                @else
                    <span style="color:var(--text-400)">Not linked</span>
                @endif
            </span>
        </div>
        @endif
        @if($contact->referral_code)
        <div class="cs-dl-row">
            <span class="cs-dl-key">Referral code</span>
            <span class="cs-dl-val" style="font-family:var(--mono,monospace);font-weight:700;letter-spacing:1px">{{ $contact->referral_code }}</span>
        </div>
        @endif
        @if($contact->referredBy)
        <div class="cs-dl-row">
            <span class="cs-dl-key">Referred by</span>
            <span class="cs-dl-val">
                <a href="{{ route('tenant.contacts.show', $contact->referred_by_contact_id) }}" style="color:var(--accent);text-decoration:none">{{ $contact->referredBy->name }}</a>
            </span>
        </div>
        @endif
    </div>

    @if($lp->isNotEmpty())
    <div style="padding:10px 16px 4px;font-size:11px;font-weight:700;color:var(--text-400);text-transform:uppercase;letter-spacing:.4px">Recent activity</div>
    <div style="padding:0 16px 12px;display:flex;flex-direction:column;gap:7px">
        @foreach($lp->take(8) as $tx)
        <div style="display:flex;justify-content:space-between;gap:8px;font-size:12px">
            <span style="color:var(--text-300)">
                {{ $typeCfg[$tx->type]['label'] ?? ucfirst($tx->type) }}
                <span style="color:var(--text-500)">· {{ $tx->created_at->format('d M') }}</span>
            </span>
            <span style="font-weight:600;color:{{ $tx->points >= 0 ? 'var(--green)' : 'var(--red)' }}">
                {{ $tx->points >= 0 ? '+' : '' }}{{ number_format($tx->points) }}
            </span>
        </div>
        @if($tx->description)
        <div style="font-size:11px;color:var(--text-500);margin-top:-4px">{{ \Illuminate\Support\Str::limit($tx->description, 40) }}</div>
        @endif
        @endforeach
    </div>
    @endif

    @can('loyalty.manage')
    <div style="padding:10px 16px 14px;border-top:1px solid var(--border-subtle)">
        <form method="POST" action="{{ route('tenant.loyalty.adjust', $contact->id) }}" style="display:flex;flex-direction:column;gap:7px">
            @csrf
            <div style="font-size:11px;font-weight:700;color:var(--text-400);text-transform:uppercase;letter-spacing:.4px">Adjust points</div>
            <input type="number" name="points" placeholder="e.g. 100 or -50" required
                   style="padding:7px 10px;background:var(--bg-input);border:1.5px solid var(--border-default);border-radius:var(--r-sm);color:var(--text-100);font-size:12.5px"/>
            <input type="text" name="reason" placeholder="Reason (required)" maxlength="255" required
                   style="padding:7px 10px;background:var(--bg-input);border:1.5px solid var(--border-default);border-radius:var(--r-sm);color:var(--text-100);font-size:12.5px"/>
            <button type="submit" class="btn btn-secondary btn-sm">Apply</button>
        </form>
    </div>
    @endcan
</div>
