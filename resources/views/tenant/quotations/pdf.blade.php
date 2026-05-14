<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<title>Quotation {{ $quotation->number }}</title>
<style>

/*
  ╔══════════════════════════════════════════════════════════════════╗
  ║  DomPDF STRICT RULES                                            ║
  ║  1. @page margin MUST equal fixed header+footer height exactly  ║
  ║     Header = 80px → margin-top: 85px                           ║
  ║     Footer = 28px → margin-bottom: 34px                        ║
  ║  2. position:fixed ONLY for #hd and #ft                        ║
  ║  3. Page numbers via <script type="text/php"> canvas API        ║
  ║     Requires: 'isPhpEnabled' => true in config/dompdf.php      ║
  ║  4. &#8377; for rupee — NEVER raw Unicode ₹                     ║
  ║  5. No flexbox, no grid, no border-radius, no overflow:hidden   ║
  ║  6. px / pt only                                                ║
  ╚══════════════════════════════════════════════════════════════════╝
*/

@page {
    size: A4 portrait;
    margin-top: 85px;
    margin-bottom: 34px;
    margin-left: 0;
    margin-right: 0;
}

* { box-sizing: border-box; margin: 0; padding: 0; }

body {
    font-family: 'DejaVu Sans', sans-serif;
    font-size: 8.5pt;
    color: #1a1a2e;
    background: #fff;
    line-height: 1.5;
}

/* Fixed header: 80px total (3px accent + 51px main + 26px ribbon) */
#hd { position: fixed; top: 0; left: 0; right: 0; height: 80px; }

/* Fixed footer: 28px total */
#ft { position: fixed; bottom: 0; left: 0; right: 0; height: 28px; }

/* Body — no top/bottom margin needed, handled by @page */
#body { padding: 0 32px; }

/* ── HEADER ─────────────────────────────────────────────────────── */
.hd-top {
    background: #1a1a2e;
    padding: 10px 32px 0 32px;
    height: 54px;
}
.hd-co-name { font-size: 12pt; font-weight: 700; color: #f8f8f8; letter-spacing: -0.2px; }
.hd-co-meta { font-size: 6.5pt; color: #6b7280; margin-top: 3px; line-height: 1.5; }
.hd-doc-word {
    font-size: 17pt; font-weight: 700;
    color: #fff; text-align: right;
    text-transform: uppercase; letter-spacing: 2px; line-height: 1;
}
.hd-doc-num {
    font-size: 6.5pt; color: #9ca3af; text-align: right;
    font-family: 'DejaVu Sans Mono', monospace; margin-top: 4px;
}
.hd-ribbon {
    background: #374151;
    padding: 5px 32px;
    font-size: 6pt; font-weight: 700;
    color: #fff; letter-spacing: 1.8px; text-transform: uppercase;
}

/* ── FOOTER ─────────────────────────────────────────────────────── */
.ft-inner {
    background: #1a1a2e;
    border-top: 1px solid #374151;
    padding: 6px 32px;
    height: 28px;
}
.ft-l { font-size: 6pt; color: #4b5563; vertical-align: middle; }
.ft-c { font-size: 6pt; color: #6b7280; text-align: center; vertical-align: middle; }
.ft-r { font-size: 6pt; color: #4b5563; text-align: right; font-family: 'DejaVu Sans Mono', monospace; vertical-align: middle; }

/* ── WATERMARK ──────────────────────────────────────────────────── */
.wm {
    position: fixed; top: 44%; left: 50%;
    transform: translate(-50%, -50%) rotate(-30deg);
    font-size: 68pt; font-weight: 900;
    text-transform: uppercase; letter-spacing: 8px;
    opacity: 0.04; pointer-events: none; white-space: nowrap; z-index: -1;
}

/* ── META BAND ──────────────────────────────────────────────────── */
.meta {
    border: 1px solid #e5e7eb;
    border-top: 2px solid #1a1a2e;
    background: #fafafa;
    padding: 10px 0;
    margin-bottom: 14px;
}
.mc  { padding: 0 14px; vertical-align: middle; }
.md  { width: 1px; padding: 0; background: #e5e7eb; }
.ml  { font-size: 5.5pt; font-weight: 700; color: #9ca3af; text-transform: uppercase; letter-spacing: 0.9px; margin-bottom: 3px; }
.mv  { font-size: 8.5pt; font-weight: 700; color: #111827; font-family: 'DejaVu Sans Mono', monospace; }
.mtl { font-size: 5.5pt; font-weight: 700; color: #9ca3af; text-transform: uppercase; letter-spacing: 0.9px; text-align: right; margin-bottom: 3px; }
.mtv { font-size: 13pt; font-weight: 700; color: #111827; text-align: right; font-family: 'DejaVu Sans Mono', monospace; }
.badge-exp {
    font-size: 5.5pt; font-weight: 700;
    background: #fef2f2; border: 1px solid #fecaca;
    color: #dc2626; padding: 1px 5px; margin-left: 4px;
    vertical-align: middle; text-transform: uppercase;
}

/* ── PARTY CARDS ────────────────────────────────────────────────── */
.pc       { border: 1px solid #e5e7eb; background: #fff; }
.pc-bar   { height: 2px; background: #1a1a2e; }
.pc-head  {
    background: #f3f4f6; border-bottom: 1px solid #e5e7eb;
    padding: 4px 12px;
    font-size: 5.5pt; font-weight: 700; color: #6b7280;
    text-transform: uppercase; letter-spacing: 1.2px;
}
.pc-body  { padding: 10px 12px; }
.pc-name  { font-size: 10pt; font-weight: 700; color: #111827; margin-bottom: 2px; }
.pc-co    { font-size: 7.5pt; color: #6b7280; margin-bottom: 5px; }
.pc-line  { font-size: 7.5pt; color: #6b7280; line-height: 1.65; }
.pc-gst   {
    display: inline-block; margin-top: 6px;
    background: #f3f4f6; border: 1px solid #d1d5db;
    color: #374151; font-size: 7pt;
    font-family: 'DejaVu Sans Mono', monospace; padding: 2px 6px;
}

/* ── SECTION LABEL ──────────────────────────────────────────────── */
.sl {
    font-size: 6pt; font-weight: 700; color: #374151;
    text-transform: uppercase; letter-spacing: 1.2px;
    border-bottom: 1px solid #e5e7eb;
    padding-bottom: 5px; margin-bottom: 10px;
}

/* ── ITEMS TABLE ────────────────────────────────────────────────── */
.it             { width: 100%; border-collapse: collapse; page-break-inside: auto; }
.it thead       { display: table-header-group; }
.it thead tr    { background: #1a1a2e; }
.it thead th {
    padding: 8px 10px;
    font-size: 6.5pt; font-weight: 700; color: #9ca3af;
    text-transform: uppercase; letter-spacing: 0.7px;
    text-align: left; border: none;
}
.it thead th.r  { text-align: right; }
.it thead th.c  { text-align: center; }
.it tbody tr    { page-break-inside: avoid; }
.it tbody tr.o td { background: #fff; }
.it tbody tr.e td { background: #f9fafb; }
.it tbody tr.l td { border-bottom: 2px solid #1a1a2e; }
.it td {
    padding: 9px 10px; font-size: 8.5pt; color: #111827;
    border-bottom: 1px solid #f3f4f6; vertical-align: top;
}
.it td.c { text-align: center; font-family: 'DejaVu Sans Mono', monospace; color: #374151; }
.it td.r { text-align: right;  font-family: 'DejaVu Sans Mono', monospace; color: #374151; }
.it td.a { text-align: right;  font-family: 'DejaVu Sans Mono', monospace; font-size: 9pt; font-weight: 700; color: #111827; }
.sno  { font-size: 7pt; color: #d1d5db; font-family: 'DejaVu Sans Mono', monospace; font-weight: 700; }
.inm  { font-size: 9pt; font-weight: 700; color: #111827; }
.idc  { font-size: 7pt; color: #9ca3af; margin-top: 2px; line-height: 1.4; }

/* ── TOTALS TABLE ───────────────────────────────────────────────── */
.tt { width: 100%; border-collapse: collapse; border: 1px solid #e5e7eb; }
.tt td {
    padding: 8px 12px; font-size: 8.5pt; color: #374151;
    border-bottom: 1px solid #f3f4f6; vertical-align: middle;
}
.tt tr:last-child td { border-bottom: none; }
.tt td.v { text-align: right; font-family: 'DejaVu Sans Mono', monospace; font-weight: 600; color: #111827; }

.ts td          { background: #fff; }
.td td          { background: #fff9f9; }
.td .dl         { color: #dc2626; }
.td td.v        { color: #dc2626; }
.tx td          { background: #f6fff6; }
.tx .tl         { color: #15803d; }
.tx td.v        { color: #15803d; }
.tx .gs         { font-size: 6.5pt; color: #4ade80; margin-top: 2px; }
.tn td          { background: #fafafa; color: #d1d5db; }
.tn td.v        { color: #d1d5db; }
.tg td {
    padding: 11px 12px; background: #1a1a2e;
    font-size: 10.5pt; font-weight: 700; color: #f3f4f6; border-bottom: none;
}
.tg td.v { font-size: 13pt; color: #f3f4f6; font-family: 'DejaVu Sans Mono', monospace; }

/* ── WORDS BOX ──────────────────────────────────────────────────── */
.wb {
    background: #f9fafb; border: 1px solid #e5e7eb;
    border-left: 2px solid #1a1a2e; padding: 8px 12px;
}
.wl { display: block; font-size: 5.5pt; font-weight: 700; color: #9ca3af; text-transform: uppercase; letter-spacing: 0.9px; margin-bottom: 3px; }
.wv { font-size: 7.5pt; color: #374151; line-height: 1.6; }

/* ── LEAD REF ───────────────────────────────────────────────────── */
.rb { background: #f9fafb; border: 1px solid #e5e7eb; padding: 7px 12px; margin-top: 7px; }
.rl { display: block; font-size: 5.5pt; font-weight: 700; color: #9ca3af; text-transform: uppercase; letter-spacing: 0.9px; margin-bottom: 3px; }
.rv { font-size: 7.5pt; color: #374151; }

/* ── NOTES / TERMS ──────────────────────────────────────────────── */
.nt       { border: 1px solid #e5e7eb; background: #f9fafb; page-break-inside: avoid; }
.nt-bar   { height: 2px; background: #1a1a2e; }
.nt-head  { background: #f3f4f6; border-bottom: 1px solid #e5e7eb; padding: 4px 12px; font-size: 5.5pt; font-weight: 700; color: #6b7280; text-transform: uppercase; letter-spacing: 1.2px; }
.nt-body  { padding: 9px 12px; font-size: 8pt; color: #4b5563; line-height: 1.65; white-space: pre-wrap; }

/* ── SIGNATURE ──────────────────────────────────────────────────── */
.sig-ln { height: 40px; border-bottom: 1px solid #d1d5db; }
.sig-lb { text-align: center; font-size: 7pt; color: #9ca3af; padding-top: 5px; }
.sig-co { text-align: center; font-size: 8pt; font-weight: 700; color: #111827; margin-top: 2px; }

.sp6  { height: 6px; }
.sp10 { height: 10px; }
.sp16 { height: 16px; }
.sp24 { height: 24px; }

</style>
</head>
<body>

@php
$qCfg     = config('quotation');
$cfgSt    = $qCfg['statuses'];
$st       = $cfgSt[$quotation->status] ?? $cfgSt['draft'];
$items    = is_array($quotation->items)
              ? $quotation->items
              : json_decode($quotation->items, true) ?? [];
$itemCount = count($items);

$statusBg = match($quotation->status) {
    'accepted' => '#059669',
    'rejected' => '#dc2626',
    'sent'     => '#d97706',
    default    => '#374151',
};

$isExpired = $quotation->valid_until
    && \Carbon\Carbon::parse($quotation->valid_until)->isPast()
    && !in_array($quotation->status, ['accepted', 'rejected']);

$co      = $tenant->name    ?? config('app.name', 'Company');
$coAddr  = implode(', ', array_filter([
    $tenant->address ?? null,
    $tenant->city    ?? null,
    $tenant->state   ?? null,
    $tenant->pincode ?? null,
]));
$coPhone = $tenant->phone   ?? null;
$coEmail = $tenant->email   ?? null;
$coGST   = $tenant->gst     ?? null;
$coWeb   = $tenant->website ?? null;

function toIndianWords(float $amount): string
{
    $o = ['','One','Two','Three','Four','Five','Six','Seven','Eight','Nine',
          'Ten','Eleven','Twelve','Thirteen','Fourteen','Fifteen','Sixteen',
          'Seventeen','Eighteen','Nineteen'];
    $t = ['','','Twenty','Thirty','Forty','Fifty','Sixty','Seventy','Eighty','Ninety'];
    $f = function(int $n) use (&$f, $o, $t): string {
        if ($n === 0)      return '';
        if ($n < 20)       return $o[$n];
        if ($n < 100)      return $t[(int)($n/10)] . ($n%10 ? ' '.$o[$n%10] : '');
        if ($n < 1000)     return $o[(int)($n/100)] . ' Hundred' . ($n%100 ? ' '.$f($n%100) : '');
        if ($n < 100000)   return $f((int)($n/1000)) . ' Thousand' . ($n%1000 ? ' '.$f($n%1000) : '');
        if ($n < 10000000) return $f((int)($n/100000)) . ' Lakh' . ($n%100000 ? ' '.$f($n%100000) : '');
        return $f((int)($n/10000000)) . ' Crore' . ($n%10000000 ? ' '.$f($n%10000000) : '');
    };
    $r = (int) floor($amount);
    $p = (int) round(($amount - $r) * 100);
    $s = trim($f($r)) . ' Rupees';
    if ($p > 0) $s .= ' and ' . trim($f($p)) . ' Paise';
    return $s . ' Only';
}

$subtotal = (float)($quotation->subtotal    ?? 0);
$discount = (float)($quotation->discount    ?? 0);
$taxPct   = (float)($quotation->tax_percent ?? 0);
$taxAmt   = (float)($quotation->tax_amount  ?? 0);
$total    = (float)($quotation->total       ?? 0);
$halfTax  = $taxAmt / 2;
$words    = toIndianWords($total);
@endphp

{{-- Watermark --}}
@if($quotation->status === 'rejected')
    <div class="wm" style="color:#ef4444">Rejected</div>
@elseif($quotation->status === 'accepted')
    <div class="wm" style="color:#10b981">Accepted</div>
@elseif($isExpired)
    <div class="wm" style="color:#f59e0b">Expired</div>
@endif

{{-- ═══════════════════════════════════
     FIXED HEADER  (total 80px)
     - .hd-top  = 54px
     - .hd-ribbon = 26px
═══════════════════════════════════ --}}
<div id="hd">
    <div class="hd-top">
        <table width="100%" style="border-collapse:collapse;height:54px">
            <tr>
                <td style="width:55%;vertical-align:bottom;padding-bottom:10px">
                    <div class="hd-co-name">{{ $co }}</div>
                    <div class="hd-co-meta">
                        @if($coAddr){{ $coAddr }}<br>@endif
                        @if($coPhone)T: {{ $coPhone }}@if($coEmail) &nbsp;&middot;&nbsp; {{ $coEmail }}@endif<br>@elseif($coEmail){{ $coEmail }}<br>@endif
                        @if($coGST)GSTIN: {{ $coGST }}@if($coWeb) &nbsp;&middot;&nbsp; {{ $coWeb }}@endif@elseif($coWeb){{ $coWeb }}@endif
                    </div>
                </td>
                <td style="width:45%;vertical-align:bottom;padding-bottom:10px">
                    <div class="hd-doc-word">Quotation</div>
                    <div class="hd-doc-num">{{ $quotation->number }}</div>
                </td>
            </tr>
        </table>
    </div>
    <div class="hd-ribbon" style="background:{{ $statusBg }}">
        {{ strtoupper($st['label']) }}
        @if($isExpired) &nbsp;&bull;&nbsp; EXPIRED @endif
    </div>
</div>

{{-- ═══════════════════════════════════
     FIXED FOOTER  (28px)

     Page numbers need isPhpEnabled=true.
     In config/dompdf.php:
       'isPhpEnabled' => true,
     Or in your controller before generate:
       $dompdf->set_option('isPhpEnabled', true);
═══════════════════════════════════ --}}
<div id="ft">
    <div class="ft-inner">
        <table width="100%" style="border-collapse:collapse">
            <tr>
                <td class="ft-l" style="width:40%">{{ $qCfg['pdf']['footer_text'] ?? 'Thank you for your business.' }}</td>
                <td class="ft-c" style="width:20%">
                    <script type="text/php">
                        if (isset($pdf)) {
                            $canvas = $pdf->getCanvas();
                            $font   = $fontMetrics->getFont('DejaVu Sans', 'normal');
                            $text   = 'Page ' . $canvas->get_page_number() . ' of ' . $canvas->get_page_count();
                            $canvas->text(229, 9, $text, $font, 7, [0.42, 0.49, 0.56]);
                        }
                    </script>
                </td>
                <td class="ft-r" style="width:40%">{{ $quotation->number }} &nbsp;&bull;&nbsp; {{ now()->format('d M Y') }}</td>
            </tr>
        </table>
    </div>
</div>

{{-- ═══════════════════════════════════
     BODY
═══════════════════════════════════ --}}
<div id="body">

    {{-- Meta band --}}
    <div class="meta">
        <table width="100%" style="border-collapse:collapse">
            <tr>
                <td class="mc" style="width:21%">
                    <div class="ml">Issue Date</div>
                    <div class="mv">{{ $quotation->date ? \Carbon\Carbon::parse($quotation->date)->format('d M Y') : '&mdash;' }}</div>
                </td>
                <td class="md">&nbsp;</td>
                <td class="mc" style="width:24%">
                    <div class="ml">Valid Until</div>
                    <div class="mv"@if($isExpired) style="color:#dc2626"@endif>
                        {{ $quotation->valid_until ? \Carbon\Carbon::parse($quotation->valid_until)->format('d M Y') : '&mdash;' }}
                        @if($isExpired)<span class="badge-exp">Expired</span>@endif
                    </div>
                </td>
                <td class="md">&nbsp;</td>
                <td class="mc" style="width:30%">
                    <div class="ml">Prepared By</div>
                    <div class="mv">{{ $quotation->createdBy?->name ?? auth()->user()->name }}</div>
                </td>
                <td class="md">&nbsp;</td>
                <td class="mc" style="width:25%">
                    <div class="mtl">Total Amount</div>
                    <div class="mtv">&#8377;{{ number_format($total, 2) }}</div>
                </td>
            </tr>
        </table>
    </div>

    {{-- Party cards --}}
    <table width="100%" style="border-collapse:collapse;margin-bottom:16px">
        <tr>
            <td style="width:48%;vertical-align:top">
                <div class="pc">
                    <div class="pc-bar"></div>
                    <div class="pc-head">Bill To</div>
                    <div class="pc-body">
                        @if($quotation->contact)
                            <div class="pc-name">{{ $quotation->contact->name }}</div>
                            @if($quotation->contact->company)
                                <div class="pc-co">{{ $quotation->contact->company }}</div>
                            @endif
                            <div class="pc-line">
                                @if($quotation->contact->address){{ $quotation->contact->address }}<br>@endif
                                @php $loc = collect([$quotation->contact->city ?? null, $quotation->contact->state ?? null])->filter()->join(', '); @endphp
                                @if($loc){{ $loc }}<br>@endif
                                @if($quotation->contact->phone)T: {{ $quotation->contact->phone }}<br>@endif
                                @if($quotation->contact->email){{ $quotation->contact->email }}@endif
                            </div>
                            @if($quotation->contact->gst_number)
                                <div><span class="pc-gst">GSTIN: {{ $quotation->contact->gst_number }}</span></div>
                            @endif
                        @else
                            <div class="pc-line" style="color:#d1d5db;font-style:italic">No contact linked</div>
                        @endif
                    </div>
                </div>
            </td>
            <td style="width:4%"></td>
            <td style="width:48%;vertical-align:top">
                <div class="pc">
                    <div class="pc-bar"></div>
                    <div class="pc-head">From</div>
                    <div class="pc-body">
                        <div class="pc-name">{{ $co }}</div>
                        <div class="pc-line">
                            @if($coAddr){{ $coAddr }}<br>@endif
                            @if($coPhone)T: {{ $coPhone }}<br>@endif
                            @if($coEmail){{ $coEmail }}<br>@endif
                            @if($coWeb){{ $coWeb }}@endif
                        </div>
                        @if($coGST)
                            <div><span class="pc-gst">GSTIN: {{ $coGST }}</span></div>
                        @endif
                    </div>
                </div>
            </td>
        </tr>
    </table>

    {{-- Items --}}
    <div class="sl">Items &amp; Services</div>
    <table class="it" width="100%">
        <thead>
            <tr>
                <th style="width:5%">#</th>
                <th style="width:27%">Item / Service</th>
                <th style="width:25%">Description</th>
                <th class="c" style="width:9%">Qty</th>
                <th class="r" style="width:16%">Rate (&#8377;)</th>
                <th class="r" style="width:18%">Amount (&#8377;)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($items as $idx => $item)
                @php
                    $rc = ($idx % 2 === 0) ? 'o' : 'e';
                    $lc = ($idx === $itemCount - 1) ? ' l' : '';
                @endphp
                <tr class="{{ $rc }}{{ $lc }}">
                    <td><span class="sno">{{ str_pad($idx + 1, 2, '0', STR_PAD_LEFT) }}</span></td>
                    <td><div class="inm">{{ $item['name'] ?? '&mdash;' }}</div></td>
                    <td>
                        @if(!empty($item['description']))
                            <div class="idc">{{ $item['description'] }}</div>
                        @else
                            <div class="idc" style="color:#e5e7eb">&mdash;</div>
                        @endif
                    </td>
                    <td class="c">{{ number_format((float)($item['quantity'] ?? 0), 2) }}</td>
                    <td class="r">{{ number_format((float)($item['rate']     ?? 0), 2) }}</td>
                    <td class="a">{{ number_format((float)($item['amount']   ?? 0), 2) }}</td>
                </tr>
            @empty
                <tr class="o">
                    <td colspan="6" style="text-align:center;padding:24px;color:#d1d5db;font-style:italic;background:#f9fafb">
                        No items added to this quotation.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="sp10"></div>

    {{-- Totals + words block --}}
    <table width="100%" style="border-collapse:collapse;page-break-inside:avoid">
        <tr>
            {{-- Left: amount in words + lead ref --}}
            <td style="width:50%;vertical-align:top;padding-right:14px">
                <div class="wb">
                    <span class="wl">Amount in Words</span>
                    <span class="wv">{{ $words }}</span>
                </div>
                @if($quotation->lead)
                    <div class="rb">
                        <span class="rl">Lead Reference</span>
                        <span class="rv">
                            {{ $quotation->lead->name }}
                            @if($quotation->lead->phone) &nbsp;&bull;&nbsp; {{ $quotation->lead->phone }} @endif
                        </span>
                    </div>
                @endif
                <div class="sp10"></div>
                <div style="font-size:6pt;color:#d1d5db">Generated {{ now()->format('d M Y, h:i A') }}</div>
            </td>
            {{-- Right: totals --}}
            <td style="width:50%;vertical-align:top;padding-left:14px">
                <table class="tt" width="100%">
                    <tr class="ts">
                        <td>Subtotal</td>
                        <td class="v">&#8377;{{ number_format($subtotal, 2) }}</td>
                    </tr>
                    @if($discount > 0)
                        <tr class="td">
                            <td><span class="dl">Discount</span></td>
                            <td class="v">&#8722;&#8377;{{ number_format($discount, 2) }}</td>
                        </tr>
                    @endif
                    @if($taxPct > 0)
                        <tr class="tx">
                            <td>
                                <span class="tl">GST {{ $taxPct }}%</span>
                                <div class="gs">
                                    CGST {{ number_format($taxPct/2,1) }}% (&#8377;{{ number_format($halfTax,2) }})
                                    &nbsp;+&nbsp;
                                    SGST {{ number_format($taxPct/2,1) }}% (&#8377;{{ number_format($halfTax,2) }})
                                </div>
                            </td>
                            <td class="v">+&#8377;{{ number_format($taxAmt, 2) }}</td>
                        </tr>
                    @else
                        <tr class="tn">
                            <td>Tax (0%)</td>
                            <td class="v">&#8377;0.00</td>
                        </tr>
                    @endif
                    <tr class="tg">
                        <td>Total</td>
                        <td class="v">&#8377;{{ number_format($total, 2) }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- Notes & Terms --}}
    @if($quotation->notes || $quotation->terms)
        <div class="sp16"></div>
        <div class="sl">Notes &amp; Terms</div>
        <table width="100%" style="border-collapse:collapse">
            <tr>
                @if($quotation->notes)
                    <td style="width:{{ $quotation->terms ? '49%' : '100%' }};vertical-align:top{{ $quotation->terms ? ';padding-right:8px' : '' }}">
                        <div class="nt">
                            <div class="nt-bar"></div>
                            <div class="nt-head">Notes</div>
                            <div class="nt-body">{{ $quotation->notes }}</div>
                        </div>
                    </td>
                    @if($quotation->terms)<td style="width:2%"></td>@endif
                @endif
                @if($quotation->terms)
                    <td style="width:{{ $quotation->notes ? '49%' : '100%' }};vertical-align:top{{ $quotation->notes ? ';padding-left:8px' : '' }}">
                        <div class="nt">
                            <div class="nt-bar"></div>
                            <div class="nt-head">Terms &amp; Conditions</div>
                            <div class="nt-body">{{ $quotation->terms }}</div>
                        </div>
                    </td>
                @endif
            </tr>
        </table>
    @endif

    {{-- Signature --}}
    <div class="sp24"></div>
    <table width="100%" style="border-collapse:collapse">
        <tr>
            <td style="width:36%;vertical-align:bottom;padding-right:12px">
                <div class="sig-ln"></div>
                <div class="sig-lb">Customer Signature &amp; Stamp</div>
            </td>
            <td style="width:28%"></td>
            <td style="width:36%;vertical-align:bottom;padding-left:12px">
                <div class="sig-ln"></div>
                <div class="sig-lb">
                    Authorised Signatory
                    <div class="sig-co">{{ $co }}</div>
                </div>
            </td>
        </tr>
    </table>

</div>{{-- /#body --}}

</body>
</html>
