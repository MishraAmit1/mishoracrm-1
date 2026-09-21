{{-- Mode-adaptive loyalty card (docs/customer-portal-loyalty.txt Gap 5).
     Reads ONLY the LoyaltyService::snapshot() array, so pure-stamp shops
     don't get a broken-looking "0 points, no tier" card. --}}
@props(['snapshot', 'tenant', 'offers' => 0, 'href' => null])

@once
@push('styles')
.wc { display:block; text-decoration:none; color:inherit; background:var(--bg-surface); border:1px solid var(--border-default); border-radius:20px; overflow:hidden; box-shadow:0 20px 40px -22px rgba(20,20,45,.28); }
a.wc:hover { transform:translateY(-1px); border-color:var(--accent); }
.wc-head { display:flex; align-items:center; gap:12px; padding:16px 18px; background:linear-gradient(135deg,var(--accent),var(--accent-hover)); color:#fff; }
.wc-logo { width:38px; height:38px; flex:none; border-radius:11px; background:rgba(255,255,255,.18); border:1px solid rgba(255,255,255,.35); display:flex; align-items:center; justify-content:center; font-size:16px; font-weight:800; }
.wc-name { font-size:16px; font-weight:800; flex:1; min-width:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.wc-offers { flex:none; font-size:11px; font-weight:700; padding:3px 10px; border-radius:20px; background:rgba(255,255,255,.22); }
.wc-unlocked { padding:11px 18px; background:var(--green-dim); color:var(--green); font-size:13px; font-weight:700; text-align:center; }
.wc-main { padding:20px 18px; text-align:center; }
.wc-big { font-size:42px; font-weight:800; color:var(--text-100); line-height:1; }
.wc-lbl { font-size:11.5px; color:var(--text-400); text-transform:uppercase; letter-spacing:.5px; margin-top:6px; }
.wc-tier { display:inline-block; margin-top:10px; padding:3px 12px; border-radius:20px; font-size:12px; font-weight:700; background:var(--accent-dim); color:var(--accent); }
.wc-tier.bronze { background:var(--amber-dim); color:var(--amber); }
.wc-tier.gold { background:var(--green-dim); color:var(--green); }
.wc-value { padding:11px 18px; background:var(--green-dim); color:var(--green); font-size:13px; font-weight:600; text-align:center; }
.wc-stamps { display:flex; flex-wrap:wrap; justify-content:center; gap:9px; margin-bottom:12px; }
.wc-stamp { width:30px; height:30px; border-radius:50%; border:2px dashed var(--border-strong, var(--border-default)); }
.wc-stamp.on { border:2px solid var(--accent); background:var(--accent); box-shadow:0 0 0 3px var(--accent-dim); }
.wc-stamp-text { font-size:13px; color:var(--text-200); font-weight:600; }
.wc-stamp-sub { font-size:12px; color:var(--text-400); margin-top:3px; }
.wc-compact { display:flex; align-items:center; justify-content:center; gap:10px; padding:12px 18px; border-top:1px solid var(--border-subtle); font-size:13px; color:var(--text-300); }
.wc-compact b { color:var(--text-100); font-size:15px; }
.wc-empty { font-size:13px; color:var(--text-400); }
@endpush
@endonce

@php
    $mode       = $snapshot['mode'];
    $showStamps = in_array($mode, ['stamps', 'both'], true);
    $showPoints = in_array($mode, ['points', 'both'], true);
    $required   = $snapshot['stamps_required'];
    $count      = min($snapshot['stamp_count'], $required);
    $remaining  = max(0, $required - $count);
    $reward     = $snapshot['stamp_reward'] ?: 'a free reward';
    $tierKey    = $snapshot['tier'] ?: '';
    $hasPoints  = $snapshot['points'] > 0 || $snapshot['lifetime_points'] > 0;
@endphp

@if($href)<a href="{{ $href }}" class="wc">@else<div class="wc">@endif
    <div class="wc-head">
        <div class="wc-logo">{{ strtoupper(substr($tenant->name, 0, 1)) }}</div>
        <div class="wc-name">{{ $tenant->name }}</div>
        @if($offers > 0)
        <div class="wc-offers">{{ $offers }} {{ \Illuminate\Support\Str::plural('offer', $offers) }}</div>
        @endif
    </div>

    @if($showStamps && $snapshot['rewards_unlocked'] > 0)
    <div class="wc-unlocked">🎁 {{ $snapshot['rewards_unlocked'] }} free {{ \Illuminate\Support\Str::plural('reward', $snapshot['rewards_unlocked']) }} unlocked — show at the counter</div>
    @endif

    @if($showStamps)
    <div class="wc-main">
        @if($required <= 30)
        <div class="wc-stamps">
            @for($i = 1; $i <= $required; $i++)
            <span class="wc-stamp {{ $i <= $count ? 'on' : '' }}"></span>
            @endfor
        </div>
        @endif
        <div class="wc-stamp-text">{{ $count }}/{{ $required }} stamps</div>
        <div class="wc-stamp-sub">
            @if($count === 0)
                Visit to start your card — {{ $required }} stamps for {{ $reward }}
            @else
                {{ $remaining }} more for {{ $reward }}
            @endif
        </div>
    </div>
    @endif

    @if($showPoints && $mode === 'points')
    <div class="wc-main">
        @if($hasPoints)
        <div class="wc-big">{{ number_format($snapshot['points']) }}</div>
        <div class="wc-lbl">points available</div>
        @if($snapshot['tier'])
        <div><span class="wc-tier {{ $tierKey }}">{{ $snapshot['tier_label'] }} member</span></div>
        @endif
        @else
        <div class="wc-empty">Start earning points on your next visit.</div>
        @endif
    </div>
    @if($snapshot['redeemable_value'] > 0)
    <div class="wc-value">Worth up to ₹{{ number_format($snapshot['redeemable_value'], 0) }} off your next bill</div>
    @endif
    @endif

    @if($showPoints && $mode === 'both')
    <div class="wc-compact">
        <span><b>{{ number_format($snapshot['points']) }}</b> points</span>
        @if($snapshot['tier'])<span class="wc-tier {{ $tierKey }}" style="margin:0">{{ $snapshot['tier_label'] }}</span>@endif
        @if($snapshot['redeemable_value'] > 0)<span>· ₹{{ number_format($snapshot['redeemable_value'], 0) }} redeemable</span>@endif
    </div>
    @endif
@if($href)</a>@else</div>@endif
