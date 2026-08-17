<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<title>Purchase Order {{ $purchaseOrder->number }}</title>
<style>

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

#hd { position: fixed; top: 0; left: 0; right: 0; height: 80px; }
#ft { position: fixed; bottom: 0; left: 0; right: 0; height: 28px; }
#body { padding: 0 32px; }

.hd-top { background: #1a1a2e; padding: 10px 32px 0 32px; height: 54px; }
.hd-co-name { font-size: 12pt; font-weight: 700; color: #f8f8f8; letter-spacing: -0.2px; }
.hd-co-meta { font-size: 6.5pt; color: #6b7280; margin-top: 3px; line-height: 1.5; }
.hd-doc-word { font-size: 17pt; font-weight: 700; color: #fff; text-align: right; text-transform: uppercase; letter-spacing: 2px; line-height: 1; }
.hd-doc-num { font-size: 6.5pt; color: #9ca3af; text-align: right; font-family: 'DejaVu Sans Mono', monospace; margin-top: 4px; }
.hd-ribbon { background: #374151; padding: 5px 32px; font-size: 6pt; font-weight: 700; color: #fff; letter-spacing: 1.8px; text-transform: uppercase; }

.ft-inner { background: #1a1a2e; border-top: 1px solid #374151; padding: 6px 32px; height: 28px; }
.ft-l { font-size: 6pt; color: #4b5563; vertical-align: middle; }
.ft-c { font-size: 6pt; color: #6b7280; text-align: center; vertical-align: middle; }
.ft-r { font-size: 6pt; color: #4b5563; text-align: right; font-family: 'DejaVu Sans Mono', monospace; vertical-align: middle; }

.meta { border: 1px solid #e5e7eb; border-top: 2px solid #1a1a2e; background: #fafafa; padding: 10px 0; margin-bottom: 14px; }
.mc { padding: 0 14px; vertical-align: middle; }
.md { width: 1px; padding: 0; background: #e5e7eb; }
.ml { font-size: 5.5pt; font-weight: 700; color: #9ca3af; text-transform: uppercase; letter-spacing: 0.9px; margin-bottom: 3px; }
.mv { font-size: 8.5pt; font-weight: 700; color: #111827; font-family: 'DejaVu Sans Mono', monospace; }
.mtl { font-size: 5.5pt; font-weight: 700; color: #9ca3af; text-transform: uppercase; letter-spacing: 0.9px; text-align: right; margin-bottom: 3px; }
.mtv { font-size: 13pt; font-weight: 700; color: #111827; text-align: right; font-family: 'DejaVu Sans Mono', monospace; }

.pc { border: 1px solid #e5e7eb; background: #fff; }
.pc-bar { height: 2px; background: #1a1a2e; }
.pc-head { background: #f3f4f6; border-bottom: 1px solid #e5e7eb; padding: 4px 12px; font-size: 5.5pt; font-weight: 700; color: #6b7280; text-transform: uppercase; letter-spacing: 1.2px; }
.pc-body { padding: 10px 12px; }
.pc-name { font-size: 10pt; font-weight: 700; color: #111827; margin-bottom: 2px; }
.pc-co { font-size: 7.5pt; color: #6b7280; margin-bottom: 5px; }
.pc-line { font-size: 7.5pt; color: #6b7280; line-height: 1.65; }
.pc-gst { display: inline-block; margin-top: 6px; background: #f3f4f6; border: 1px solid #d1d5db; color: #374151; font-size: 7pt; font-family: 'DejaVu Sans Mono', monospace; padding: 2px 6px; }

.sl { font-size: 6pt; font-weight: 700; color: #374151; text-transform: uppercase; letter-spacing: 1.2px; border-bottom: 1px solid #e5e7eb; padding-bottom: 5px; margin-bottom: 10px; }

.it { width: 100%; border-collapse: collapse; page-break-inside: auto; }
.it thead { display: table-header-group; }
.it thead tr { background: #1a1a2e; }
.it thead th { padding: 8px 10px; font-size: 6.5pt; font-weight: 700; color: #9ca3af; text-transform: uppercase; letter-spacing: 0.7px; text-align: left; border: none; }
.it thead th.r { text-align: right; }
.it thead th.c { text-align: center; }
.it tbody tr { page-break-inside: avoid; }
.it tbody tr.o td { background: #fff; }
.it tbody tr.e td { background: #f9fafb; }
.it tbody tr.l td { border-bottom: 2px solid #1a1a2e; }
.it td { padding: 9px 10px; font-size: 8.5pt; color: #111827; border-bottom: 1px solid #f3f4f6; vertical-align: top; }
.it td.c { text-align: center; font-family: 'DejaVu Sans Mono', monospace; color: #374151; }
.it td.r { text-align: right; font-family: 'DejaVu Sans Mono', monospace; color: #374151; }
.it td.a { text-align: right; font-family: 'DejaVu Sans Mono', monospace; font-size: 9pt; font-weight: 700; color: #111827; }
.sno { font-size: 7pt; color: #d1d5db; font-family: 'DejaVu Sans Mono', monospace; font-weight: 700; }
.inm { font-size: 9pt; font-weight: 700; color: #111827; }
.idc { font-size: 7pt; color: #9ca3af; margin-top: 2px; line-height: 1.4; }

.tt { width: 100%; border-collapse: collapse; border: 1px solid #e5e7eb; }
.tt td { padding: 8px 12px; font-size: 8.5pt; color: #374151; border-bottom: 1px solid #f3f4f6; vertical-align: middle; }
.tt tr:last-child td { border-bottom: none; }
.tt td.v { text-align: right; font-family: 'DejaVu Sans Mono', monospace; font-weight: 600; color: #111827; }
.ts td { background: #fff; }
.td td { background: #fff9f9; }
.td .dl { color: #dc2626; }
.td td.v { color: #dc2626; }
.tx td { background: #f6fff6; }
.tx .tl { color: #15803d; }
.tx td.v { color: #15803d; }
.tn td { background: #fafafa; color: #d1d5db; }
.tn td.v { color: #d1d5db; }
.tg td { padding: 11px 12px; background: #1a1a2e; font-size: 10.5pt; font-weight: 700; color: #f3f4f6; border-bottom: none; }
.tg td.v { font-size: 13pt; color: #f3f4f6; font-family: 'DejaVu Sans Mono', monospace; }

.nt { border: 1px solid #e5e7eb; background: #f9fafb; page-break-inside: avoid; }
.nt-bar { height: 2px; background: #1a1a2e; }
.nt-head { background: #f3f4f6; border-bottom: 1px solid #e5e7eb; padding: 4px 12px; font-size: 5.5pt; font-weight: 700; color: #6b7280; text-transform: uppercase; letter-spacing: 1.2px; }
.nt-body { padding: 9px 12px; font-size: 8pt; color: #4b5563; line-height: 1.65; white-space: pre-wrap; }

.sp10 { height: 10px; }
.sp16 { height: 16px; }

</style>
</head>
<body>

@php
$items = is_array($purchaseOrder->items) ? $purchaseOrder->items : (json_decode($purchaseOrder->items, true) ?? []);
$itemCount = count($items);

$statusBg = match($purchaseOrder->status) {
    'received'           => '#059669',
    'partially_received'  => '#2563eb',
    'cancelled'           => '#dc2626',
    'sent'                => '#d97706',
    default               => '#374151',
};

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

$subtotal = (float)($purchaseOrder->subtotal    ?? 0);
$discount = (float)($purchaseOrder->discount    ?? 0);
$taxPct   = (float)($purchaseOrder->tax_percent ?? 0);
$taxAmt   = (float)($purchaseOrder->tax_amount  ?? 0);
$total    = (float)($purchaseOrder->total       ?? 0);
$symHtml  = '&#8377;';
@endphp

<div id="hd">
    <div class="hd-top">
        <table width="100%" style="border-collapse:collapse;height:54px">
            <tr>
                <td style="width:55%;vertical-align:bottom;padding-bottom:10px">
                    <div class="hd-co-name">{{ $co }}</div>
                    <div class="hd-co-meta">
                        @if($coAddr){{ $coAddr }}<br>@endif
                        @if($coPhone)T: {{ $coPhone }}@if($coEmail) &nbsp;&middot;&nbsp; {{ $coEmail }}@endif<br>@elseif($coEmail){{ $coEmail }}<br>@endif
                        @if($coGST)GSTIN: {{ $coGST }}@endif
                    </div>
                </td>
                <td style="width:45%;vertical-align:bottom;padding-bottom:10px">
                    <div class="hd-doc-word">Purchase Order</div>
                    <div class="hd-doc-num">{{ $purchaseOrder->number }}</div>
                </td>
            </tr>
        </table>
    </div>
    <div class="hd-ribbon" style="background:{{ $statusBg }}">
        {{ strtoupper(str_replace('_',' ',$purchaseOrder->status)) }}
    </div>
</div>

<div id="ft">
    <div class="ft-inner">
        <table width="100%" style="border-collapse:collapse">
            <tr>
                <td class="ft-l" style="width:40%">Generated automatically by {{ $co }}.</td>
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
                <td class="ft-r" style="width:40%">{{ $purchaseOrder->number }} &nbsp;&bull;&nbsp; {{ now()->format('d M Y') }}</td>
            </tr>
        </table>
    </div>
</div>

<div id="body">

    <div class="meta">
        <table width="100%" style="border-collapse:collapse">
            <tr>
                <td class="mc" style="width:23%">
                    <div class="ml">Issue Date</div>
                    <div class="mv">{{ $purchaseOrder->date ? \Carbon\Carbon::parse($purchaseOrder->date)->format('d M Y') : '&mdash;' }}</div>
                </td>
                <td class="md">&nbsp;</td>
                <td class="mc" style="width:26%">
                    <div class="ml">Expected Delivery</div>
                    <div class="mv">{{ $purchaseOrder->expected_delivery_date ? \Carbon\Carbon::parse($purchaseOrder->expected_delivery_date)->format('d M Y') : '&mdash;' }}</div>
                </td>
                <td class="md">&nbsp;</td>
                <td class="mc" style="width:26%">
                    <div class="ml">Prepared By</div>
                    <div class="mv">{{ $purchaseOrder->createdBy?->name ?? auth()->user()->name }}</div>
                </td>
                <td class="md">&nbsp;</td>
                <td class="mc" style="width:25%">
                    <div class="mtl">Total Amount</div>
                    <div class="mtv">{!! $symHtml !!}{{ number_format($total, 2) }}</div>
                </td>
            </tr>
        </table>
    </div>

    <table width="100%" style="border-collapse:collapse;margin-bottom:16px">
        <tr>
            <td style="width:48%;vertical-align:top">
                <div class="pc">
                    <div class="pc-bar"></div>
                    <div class="pc-head">Vendor</div>
                    <div class="pc-body">
                        @if($purchaseOrder->vendor)
                            <div class="pc-name">{{ $purchaseOrder->vendor->name }}</div>
                            @if($purchaseOrder->vendor->company)
                                <div class="pc-co">{{ $purchaseOrder->vendor->company }}</div>
                            @endif
                            <div class="pc-line">
                                @if($purchaseOrder->vendor->address){{ $purchaseOrder->vendor->address }}<br>@endif
                                @php $loc = collect([$purchaseOrder->vendor->city ?? null, $purchaseOrder->vendor->state ?? null])->filter()->join(', '); @endphp
                                @if($loc){{ $loc }}<br>@endif
                                @if($purchaseOrder->vendor->phone)T: {{ $purchaseOrder->vendor->phone }}<br>@endif
                                @if($purchaseOrder->vendor->email){{ $purchaseOrder->vendor->email }}@endif
                            </div>
                            @if($purchaseOrder->vendor->gst_number)
                                <div><span class="pc-gst">GSTIN: {{ $purchaseOrder->vendor->gst_number }}</span></div>
                            @endif
                        @else
                            <div class="pc-line" style="color:#d1d5db;font-style:italic">No vendor linked</div>
                        @endif
                    </div>
                </div>
            </td>
            <td style="width:4%"></td>
            <td style="width:48%;vertical-align:top">
                <div class="pc">
                    <div class="pc-bar"></div>
                    <div class="pc-head">Bill From / Ship To</div>
                    <div class="pc-body">
                        <div class="pc-name">{{ $co }}</div>
                        <div class="pc-line">
                            @if($coAddr){{ $coAddr }}<br>@endif
                            @if($coPhone)T: {{ $coPhone }}<br>@endif
                            @if($coEmail){{ $coEmail }}@endif
                        </div>
                        @if($coGST)
                            <div><span class="pc-gst">GSTIN: {{ $coGST }}</span></div>
                        @endif
                    </div>
                </div>
            </td>
        </tr>
    </table>

    <div class="sl">Items</div>
    <table class="it" width="100%">
        <thead>
            <tr>
                <th style="width:5%">#</th>
                <th style="width:26%">Item</th>
                <th style="width:22%">Description</th>
                <th class="c" style="width:8%">Qty</th>
                <th class="r" style="width:14%">Rate ({!! $symHtml !!})</th>
                <th class="c" style="width:9%">GST %</th>
                <th class="r" style="width:16%">Amount ({!! $symHtml !!})</th>
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
                    <td class="c">{{ number_format((float)($item['tax_percent'] ?? $taxPct), 1) }}%</td>
                    <td class="a">{{ number_format((float)($item['amount']   ?? 0), 2) }}</td>
                </tr>
            @empty
                <tr class="o">
                    <td colspan="7" style="text-align:center;padding:24px;color:#d1d5db;font-style:italic;background:#f9fafb">
                        No items added to this purchase order.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="sp10"></div>

    <table width="100%" style="border-collapse:collapse;page-break-inside:avoid">
        <tr>
            <td style="width:50%;vertical-align:top;padding-right:14px">
                <div style="font-size:6pt;color:#d1d5db">Generated {{ now()->format('d M Y, h:i A') }}</div>
            </td>
            <td style="width:50%;vertical-align:top;padding-left:14px">
                <table class="tt" width="100%">
                    <tr class="ts">
                        <td>Subtotal</td>
                        <td class="v">{!! $symHtml !!}{{ number_format($subtotal, 2) }}</td>
                    </tr>
                    @if($discount > 0)
                        <tr class="td">
                            <td><span class="dl">Discount</span></td>
                            <td class="v">&#8722;{!! $symHtml !!}{{ number_format($discount, 2) }}</td>
                        </tr>
                    @endif
                    @if($taxPct > 0)
                        <tr class="tx">
                            <td><span class="tl">Tax {{ $taxPct }}%</span></td>
                            <td class="v">+{!! $symHtml !!}{{ number_format($taxAmt, 2) }}</td>
                        </tr>
                    @else
                        <tr class="tn">
                            <td>Tax (0%)</td>
                            <td class="v">{!! $symHtml !!}0.00</td>
                        </tr>
                    @endif
                    <tr class="tg">
                        <td>Total</td>
                        <td class="v">{!! $symHtml !!}{{ number_format($total, 2) }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    @if($purchaseOrder->notes || $purchaseOrder->terms)
        <div class="sp16"></div>
        <div class="sl">Notes &amp; Terms</div>
        <table width="100%" style="border-collapse:collapse">
            <tr>
                @if($purchaseOrder->notes)
                    <td style="width:{{ $purchaseOrder->terms ? '49%' : '100%' }};vertical-align:top{{ $purchaseOrder->terms ? ';padding-right:8px' : '' }}">
                        <div class="nt">
                            <div class="nt-bar"></div>
                            <div class="nt-head">Notes</div>
                            <div class="nt-body">{{ $purchaseOrder->notes }}</div>
                        </div>
                    </td>
                    @if($purchaseOrder->terms)<td style="width:2%"></td>@endif
                @endif
                @if($purchaseOrder->terms)
                    <td style="width:{{ $purchaseOrder->notes ? '49%' : '100%' }};vertical-align:top{{ $purchaseOrder->notes ? ';padding-left:8px' : '' }}">
                        <div class="nt">
                            <div class="nt-bar"></div>
                            <div class="nt-head">Terms &amp; Conditions</div>
                            <div class="nt-body">{{ $purchaseOrder->terms }}</div>
                        </div>
                    </td>
                @endif
            </tr>
        </table>
    @endif

</div>{{-- /#body --}}

</body>
</html>
