<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>{{ $tenant->name }} — {{ $meta['label'] }}</title>
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;600;700;800&display=swap" rel="stylesheet"/>
<style>
@if($template === 'poster')
@page { size: A4 portrait; margin: 0; }
@elseif($template === 'stand')
@page { size: A5 portrait; margin: 0; }
@else
@page { size: A4 landscape; margin: 0; }
@endif

* { box-sizing: border-box; }
html, body { margin: 0; padding: 0; }
body { font-family: 'Outfit', system-ui, sans-serif; background: #e5e7eb; color: #14162b; -webkit-print-color-adjust: exact; print-color-adjust: exact; }

.toolbar { position: sticky; top: 0; z-index: 5; display: flex; gap: 12px; align-items: center; flex-wrap: wrap; padding: 12px 20px; background: #14162b; color: #fff; font-size: 13px; }
.toolbar button { padding: 9px 18px; border: 0; border-radius: 8px; background: #6378ff; color: #fff; font: inherit; font-weight: 700; cursor: pointer; }
.toolbar a { color: #c7cdff; }
.toolbar .tip { opacity: .75; }

.stage { display: flex; justify-content: center; padding: 24px 12px 40px; }
.sheet { background: #fff; box-shadow: 0 10px 40px rgba(0,0,0,.25); overflow: hidden; position: relative; }

/* ── sizes ── */
.tpl-poster .sheet { width: 210mm; height: 297mm; }
.tpl-stand  .sheet { width: 148mm; height: 210mm; }
.tpl-tent   .sheet { width: 297mm; height: 210mm; }

.panel { display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; padding: 14mm 16mm; height: 100%; position: relative; }
.panel::before { content: ''; position: absolute; left: 0; right: 0; top: 0; height: 9mm; background: #6378ff; }
.brand { display: flex; align-items: center; gap: 4mm; margin-bottom: 6mm; }
.brand img { height: 16mm; max-width: 40mm; object-fit: contain; }
.brand .mark { width: 16mm; height: 16mm; border-radius: 4mm; background: #6378ff; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 9mm; font-weight: 800; }
.brand .name { font-size: 8mm; font-weight: 800; }
.headline { font-weight: 800; line-height: 1.12; margin: 0 0 4mm; }
.subline { color: #4b5068; margin: 0 0 7mm; font-weight: 600; }
.qr { background: #fff; padding: 3mm; border: .6mm solid #14162b; border-radius: 5mm; }
.qr svg { display: block; width: 100%; height: auto; }
.foot { margin-top: 6mm; color: #4b5068; }
.foot b { color: #14162b; }

.tpl-poster .headline { font-size: 17mm; }
.tpl-poster .subline { font-size: 6.5mm; }
.tpl-poster .qr { width: 118mm; }
.tpl-poster .foot { font-size: 4.6mm; }

.tpl-stand .brand .name { font-size: 6mm; }
.tpl-stand .headline { font-size: 11mm; }
.tpl-stand .subline { font-size: 5mm; }
.tpl-stand .qr { width: 78mm; }
.tpl-stand .foot { font-size: 3.6mm; }

.tpl-tent .sheet { display: flex; flex-direction: column; }
.tpl-tent .half { flex: 1; position: relative; }
.tpl-tent .half.flip { transform: rotate(180deg); }
.tpl-tent .half + .half { border-top: .4mm dashed #9ca3af; }
.tpl-tent .panel { flex-direction: row; gap: 14mm; padding: 10mm 18mm; text-align: left; }
.tpl-tent .copy { flex: 1; }
.tpl-tent .brand { margin-bottom: 4mm; }
.tpl-tent .brand .name { font-size: 6mm; }
.tpl-tent .brand img, .tpl-tent .brand .mark { height: 12mm; width: auto; }
.tpl-tent .brand .mark { width: 12mm; font-size: 7mm; }
.tpl-tent .headline { font-size: 11mm; }
.tpl-tent .subline { font-size: 5mm; margin-bottom: 3mm; }
.tpl-tent .qr { width: 66mm; flex: none; }
.tpl-tent .foot { font-size: 3.6mm; margin-top: 0; }

@media print {
    body { background: #fff; }
    .toolbar { display: none; }
    .stage { padding: 0; display: block; }
    .sheet { box-shadow: none; }
}
</style>
</head>
<body class="tpl-{{ $template }}">

<div class="toolbar">
    <button type="button" onclick="window.print()">Print / Save as PDF</button>
    <a href="{{ route('tenant.loyalty.qr-kit.index') }}">← Back to QR Kit</a>
    <span class="tip">Margins: None · Background graphics: On</span>
</div>

<div class="stage">
    <div class="sheet">
        @if($template === 'tent')
            @foreach([true, false] as $flip)
            <div class="half {{ $flip ? 'flip' : '' }}">
                <div class="panel">
                    <div class="copy">
                        @include('tenant.loyalty.qr-kit._brand')
                        <h1 class="headline">{{ $headline }}</h1>
                        <p class="subline">{{ $subline }}</p>
                        <div class="foot">Already a member? <b>{{ preg_replace('#^https?://#', '', $walletUrl) }}</b></div>
                    </div>
                    <div class="qr" data-qr="{{ $qrUrl }}" data-alt="{{ $subline }}"></div>
                </div>
            </div>
            @endforeach
        @else
            <div class="panel">
                @include('tenant.loyalty.qr-kit._brand')
                <h1 class="headline">{{ $headline }}</h1>
                <p class="subline">{{ $subline }}</p>
                <div class="qr" data-qr="{{ $qrUrl }}" data-alt="{{ $subline }}"></div>
                <div class="foot">Already a member? Check your rewards at <b>{{ preg_replace('#^https?://#', '', $walletUrl) }}</b></div>
            </div>
        @endif
    </div>
</div>

<script src="{{ asset('js/qrcode-generator.js') }}"></script>
<script src="{{ asset('js/qr-kit.js') }}"></script>
</body>
</html>
