@extends('layouts.portal')

@section('title', $tenant->name)

@push('styles')
.pt-back { font-size:12.5px; }
.pt-back a { color:var(--accent); text-decoration:none; }
.pt-sec { padding:18px 22px; }
.pt-sec h4 { font-size:11px; font-weight:700; color:var(--text-400); text-transform:uppercase; letter-spacing:.5px; margin:0 0 10px; }
.pt-offer { display:flex; justify-content:space-between; align-items:center; gap:10px; padding:10px 0; border-bottom:1px solid var(--border-subtle); font-size:13px; }
.pt-offer:last-child { border-bottom:none; }
.pt-offer .n { color:var(--text-100); font-weight:600; }
.pt-offer .r { color:var(--green); font-weight:700; font-size:12.5px; }
.pt-offer .s { color:var(--text-400); font-size:11px; }
.pt-code { font-family:var(--mono,monospace); font-weight:700; letter-spacing:1.5px; padding:4px 10px; border-radius:8px; background:var(--bg-elevated); color:var(--text-100); }
.pt-tx { display:flex; justify-content:space-between; gap:8px; padding:8px 0; border-bottom:1px solid var(--border-subtle); font-size:13px; }
.pt-tx:last-child { border-bottom:none; }
.pt-tx .d { color:var(--text-300); }
.pt-tx .p.pos { color:var(--green); font-weight:700; }
.pt-tx .p.neg { color:var(--red); font-weight:700; }
@endpush

@section('content')
<div class="pt-back"><a href="{{ route('portal.wallet.index') }}">← All my cards</a></div>

<x-portal.wallet-card :snapshot="$snapshot" :tenant="$tenant" :offers="$offers->count()"/>

<div class="pt-card" id="wallet-qr" data-url="{{ route('portal.wallet.qr', $tenant->id) }}">
    <div class="pt-sec" style="text-align:center">
        <h4>Show at counter</h4>
        <img alt="Your wallet QR code" hidden style="width:220px;max-width:100%;height:auto;image-rendering:pixelated;border-radius:10px;background:#fff"/>
        <div data-role="note" class="pt-step-sub" style="margin:10px 0 0">Loading your code…</div>
    </div>
</div>
@push('scripts')
<script src="{{ asset('js/qrcode-generator.js') }}"></script>
<script src="{{ asset('js/wallet-qr.js') }}"></script>
@endpush

@if($offers->isNotEmpty())
<div class="pt-card">
    <div class="pt-sec">
        <h4>My offers</h4>
        @foreach($offers as $offer)
        <div class="pt-offer">
            <div>
                <div class="n">{{ $offer->campaign->name }}</div>
                <div class="r">{{ $offer->campaign->rewardLabel() }}</div>
                @if($offer->campaign->expires_at)
                <div class="s">Valid till {{ $offer->campaign->expires_at->format('d M Y') }}</div>
                @endif
            </div>
            @if($offer->code)
            <span class="pt-code">{{ $offer->code }}</span>
            @endif
        </div>
        @endforeach
    </div>
</div>
@endif

@if(count($snapshot['recent']))
<div class="pt-card">
    <div class="pt-sec">
        <h4>Recent activity</h4>
        @foreach($snapshot['recent'] as $tx)
        <div class="pt-tx">
            <span class="d">{{ $tx['description'] ?: ucfirst($tx['type']) }}<br><span style="font-size:11px;color:var(--text-500)">{{ \Illuminate\Support\Carbon::parse($tx['date'])->format('d M Y') }}</span></span>
            @php $unit = $tx['type'] === 'stamp' ? ' stamp' : ($tx['type'] === 'stamp_reward' ? ' reward' : ''); @endphp
            <span class="p {{ $tx['points'] >= 0 ? 'pos' : 'neg' }}">{{ $tx['points'] >= 0 ? '+' : '' }}{{ number_format($tx['points']) }}{{ $unit }}{{ $unit && abs($tx['points']) !== 1 ? 's' : '' }}</span>
        </div>
        @endforeach
    </div>
</div>
@endif
@endsection
