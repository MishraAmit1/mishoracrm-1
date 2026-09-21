@extends('layouts.app')
@section('title', 'QR Kit')

@push('styles')
<style>
.qk-card { background:var(--bg-surface); border:1px solid var(--border-default); border-radius:var(--r-lg); padding:20px 22px; max-width:760px; margin-bottom:16px; }
.qk-h { font-size:12px; font-weight:700; color:var(--text-200); text-transform:uppercase; letter-spacing:.5px; margin-bottom:10px; }
.qk-opt { display:flex; gap:10px; align-items:flex-start; padding:10px 12px; border:1.5px solid var(--border-default); border-radius:var(--r-md); margin-bottom:8px; cursor:pointer; }
.qk-opt.is-off { opacity:.55; cursor:not-allowed; }
.qk-opt input { margin-top:3px; }
.qk-opt b { font-size:13.5px; color:var(--text-100); }
.qk-opt span { display:block; font-size:12px; color:var(--text-400); margin-top:2px; word-break:break-all; }
.qk-input { width:100%; padding:9px 13px; background:var(--bg-input); border:1.5px solid var(--border-default); border-radius:var(--r-sm); color:var(--text-100); font-size:14px; }
.qk-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:12px; }
@media(max-width:700px){ .qk-grid { grid-template-columns:1fr; } }
.qk-tpl { border:1px solid var(--border-default); border-radius:var(--r-md); padding:14px; display:flex; flex-direction:column; gap:6px; }
.qk-tpl b { font-size:14px; color:var(--text-100); }
.qk-tpl small { font-size:12px; color:var(--text-400); }
.qk-tpl .sz { font-size:11px; font-weight:700; color:var(--accent); text-transform:uppercase; letter-spacing:.4px; }
.qk-tpl button { margin-top:auto; }
</style>
@endpush

@section('content')

<div class="page-head">
    <div>
        <div style="font-size:12px;color:var(--text-300);margin-bottom:4px">
            <a href="{{ route('tenant.loyalty.index') }}" style="color:var(--text-300);text-decoration:none">Loyalty</a> › QR Kit
        </div>
        <div class="page-title">QR Kit</div>
        <div class="page-sub">Print a poster, counter stand or table tent with your QR — customers scan it to join or open their wallet.</div>
    </div>
</div>

<form method="GET" target="_blank">
    <div class="qk-card">
        <div class="qk-h">What should the QR open?</div>

        <label class="qk-opt {{ $joinUrl ? '' : 'is-off' }}">
            <input type="radio" name="qr" value="join" @checked($joinUrl) @disabled(!$joinUrl)/>
            <div>
                <b>Join on WhatsApp</b>
                @if($joinUrl)
                <span>{{ $joinUrl }}</span>
                @else
                <span>Set a welcome bonus and your WhatsApp number in <a href="{{ route('tenant.loyalty.settings') }}" style="color:var(--accent)">Loyalty Rules</a> to enable this.</span>
                @endif
            </div>
        </label>

        <label class="qk-opt">
            <input type="radio" name="qr" value="wallet" @checked(!$joinUrl)/>
            <div>
                <b>Open my rewards wallet</b>
                <span>{{ $walletUrl }}</span>
            </div>
        </label>
    </div>

    <div class="qk-card">
        <div class="qk-h">Headline</div>
        <input type="text" name="headline" class="qk-input" maxlength="90" value="{{ $headline }}"/>
        <div style="font-size:11.5px;color:var(--text-400);margin-top:6px">Printed above the QR. Leave it as is, or write your own.</div>
    </div>

    <div class="qk-card">
        <div class="qk-h">Choose a layout</div>
        <div class="qk-grid">
            @foreach($templates as $key => $tpl)
            <div class="qk-tpl">
                <span class="sz">{{ $tpl['size'] }}</span>
                <b>{{ $tpl['label'] }}</b>
                <small>{{ $tpl['desc'] }}</small>
                <button type="submit" formaction="{{ route('tenant.loyalty.qr-kit.show', $key) }}" class="btn btn-primary btn-sm">Open &amp; print</button>
            </div>
            @endforeach
        </div>
        <div style="font-size:11.5px;color:var(--text-400);margin-top:12px">In the print dialog choose <b>Save as PDF</b> (or your printer), set margins to <b>None</b> and turn <b>Background graphics</b> on.</div>
    </div>
</form>

@endsection
