<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<title>Tax Invoice — {{ $inv['invoice_number'] }}</title>
@php
    $primary = $inv['primary_color'] ?? '#33475B';
    $accent  = $inv['accent_color']  ?? '#FF7A59';
    $cur     = $inv['currency'] ?? '₹';
    $seller  = $inv['seller'];
    $buyer   = $inv['buyer'];

    $sellerAddr = collect([
        $seller['address'],
        collect([$seller['city'], $seller['state'], $seller['pincode']])->filter()->join(', '),
    ])->filter()->join("\n");

    $buyerAddr = collect([
        $buyer['address'],
        collect([$buyer['city'], $buyer['state'], $buyer['pincode']])->filter()->join(', '),
    ])->filter()->join("\n");
@endphp
<style>
    /* Per-tag reset — NOT a universal `* { margin:0 }`: dompdf drops the
       @page margin when a `*` reset is present anywhere in the sheet. */
    body, div, table, thead, tbody, tr, td, th, span, p, h1, h2, img {
        margin: 0; padding: 0; box-sizing: border-box;
    }

    @page { margin: 0; }

    body {
        font-family: "DejaVu Sans", sans-serif;
        font-size: 11px;
        color: #1e293b;
        line-height: 1.5;
    }

    /* dompdf only resolves font-weight "normal"/"bold" — numeric weights
       silently fall back to a font with no ₹ glyph. Use `bold` only. */

    /* ─── HEADER ─── */
    .header {
        background: {{ $primary }};
        padding: 22px 34px 20px;
        border-bottom: 3px solid {{ $accent }};
    }
    .header-table { width: 100%; }
    .header-left  { vertical-align: top; }
    .header-right { vertical-align: top; text-align: right; width: 175px; }

    .brand-logo  { max-height: 34px; max-width: 150px; margin-bottom: 8px; }
    .brand-name  { font-size: 17px; font-weight: bold; color: #ffffff; letter-spacing: .2px; }
    .brand-meta  { font-size: 8.5px; color: #cbd5e1; margin-top: 6px; line-height: 1.55; white-space: pre-line; }

    .doc-title   { font-size: 15px; font-weight: bold; color: #ffffff; letter-spacing: 2px; text-transform: uppercase; }
    .doc-sub     { font-size: 8px; color: #cbd5e1; margin-top: 4px; letter-spacing: .4px; }

    /* ─── META BAND ─── */
    .meta-band { background: #f1f5f9; border-bottom: 1px solid #e2e8f0; padding: 10px 34px; }
    .meta-table { width: 100%; }
    .meta-table td { width: 33.33%; vertical-align: top; padding-right: 16px; }
    .meta-table td.last { padding-right: 0; text-align: right; }
    .meta-label { font-size: 7.5px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; color: #94a3b8; }
    .meta-value { font-size: 11.5px; font-weight: bold; color: #1e293b; margin-top: 3px; }

    /* ─── BODY ─── */
    .body { padding: 18px 34px 0; }

    .parties { width: 100%; margin-bottom: 4px; }
    .parties td { width: 50%; vertical-align: top; }
    .parties td.right { padding-left: 22px; }

    .party-label {
        font-size: 7.5px; font-weight: bold; text-transform: uppercase;
        letter-spacing: 1px; color: #94a3b8; margin-bottom: 5px;
    }
    .party-card {
        border: 1px solid #e2e8f0;
        border-top: 3px solid {{ $primary }};
        padding: 10px 13px;
        min-height: 92px;
    }
    .party-card.buyer { border-top-color: {{ $accent }}; }
    .party-name   { font-size: 12.5px; font-weight: bold; color: #0f172a; margin-bottom: 3px; }
    .party-line   { font-size: 9.5px; color: #64748b; line-height: 1.55; white-space: pre-line; }
    .party-tax    { margin-top: 6px; padding-top: 5px; border-top: 1px dashed #e2e8f0; font-size: 9.5px; color: #475569; }
    .party-tax b  { color: #1e293b; }

    /* ─── ITEMS ─── */
    .section-head {
        font-size: 8.5px; font-weight: bold; text-transform: uppercase; letter-spacing: 1.2px;
        color: {{ $primary }}; border-bottom: 2px solid {{ $primary }};
        padding-bottom: 4px; margin: 16px 0 8px;
    }
    .items { width: 100%; border-collapse: collapse; }
    .items thead th {
        background: {{ $primary }}; color: #ffffff;
        font-size: 8px; font-weight: bold; text-transform: uppercase; letter-spacing: .3px;
        padding: 7px 8px; text-align: left;
    }
    .items thead th.r { text-align: right; }
    .items thead th.c { text-align: center; }
    .items tbody td { padding: 9px 8px; font-size: 10px; color: #334155; vertical-align: top; border-bottom: 1px solid #e2e8f0; }
    .items tbody td.r { text-align: right; }
    .items tbody td.c { text-align: center; }
    .item-title { font-size: 10.5px; font-weight: bold; color: #0f172a; }
    .item-desc  { font-size: 8.5px; color: #94a3b8; margin-top: 2px; }

    /* ─── TOTALS ─── */
    .bottom { width: 100%; margin-top: 14px; }
    .bottom td { vertical-align: top; }
    .bottom .words-cell  { width: 52%; padding-right: 20px; }
    .bottom .totals-cell { width: 48%; }

    .words-box {
        background: #f8fafc; border: 1px solid #e2e8f0; border-left: 3px solid {{ $accent }};
        padding: 9px 13px;
    }
    .words-label { font-size: 7.5px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; color: #94a3b8; margin-bottom: 4px; }
    .words-text  { font-size: 10.5px; font-weight: bold; color: #1e293b; line-height: 1.45; }

    .totals { width: 100%; border-collapse: collapse; }
    .totals td { padding: 5px 12px; font-size: 10.5px; }
    .totals tr { border-bottom: 1px solid #f1f5f9; }
    .t-label { color: #64748b; }
    .t-value { text-align: right; font-weight: bold; color: #1e293b; }
    .t-label.sub { font-size: 9.5px; }
    .t-value.sub { font-size: 9.5px; color: #64748b; }
    .t-value.discount { color: #dc2626; }
    .grand td { background: {{ $primary }}; color: #ffffff; font-size: 12.5px; font-weight: bold; padding: 10px 12px; }
    .grand .t-value { color: #ffffff; }

    /* ─── PAYMENT ─── */
    .paid-box {
        background: #f0fdf4; border: 1px solid #86efac; border-left: 3px solid #16a34a;
        padding: 9px 14px; margin-top: 14px;
    }
    .paid-title { font-size: 8px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; color: #15803d; margin-bottom: 3px; }
    .paid-row   { font-size: 9.5px; color: #166534; line-height: 1.6; }
    .paid-row b { color: #14532d; }

    /* ─── TERMS + SIGN ─── */
    .foot-table { width: 100%; margin-top: 16px; }
    .foot-table td { vertical-align: top; }
    .terms-cell { width: 60%; padding-right: 22px; }
    .sign-cell  { width: 40%; text-align: right; }

    .terms-label { font-size: 7.5px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; color: #94a3b8; margin-bottom: 4px; }
    .terms-text  { font-size: 8.5px; color: #64748b; line-height: 1.6; white-space: pre-line; }

    .sign-box { border: 1px solid #e2e8f0; padding: 10px 16px; display: inline-block; text-align: center; min-width: 170px; }
    .sign-for  { font-size: 8px; font-weight: bold; text-transform: uppercase; letter-spacing: .5px; color: #94a3b8; }
    .sign-gap  { height: 30px; }
    .sign-role { font-size: 9.5px; font-weight: bold; color: #1e293b; }

    .declaration { font-size: 8px; color: #94a3b8; line-height: 1.5; margin-top: 14px; }

    /* ─── FOOTER ─── */
    /* dompdf's positioner for a fixed element only reads `top`/`left`
       (a `bottom` offset is ignored). A4 height 841.89pt / 0.75 = 1122.52px
       at dompdf's 96dpi; anchor this 30px-tall bar flush to that edge. */
    .doc-footer {
        position: fixed; top: 1092.52px; left: 0; right: 0; height: 30px;
        background: {{ $primary }}; padding: 8px 34px;
    }
    .doc-footer-table { width: 100%; }
    .doc-footer-table td { font-size: 7.5px; color: #cbd5e1; }
    .doc-footer-table td.r { text-align: right; }
</style>
</head>
<body>

    {{-- ═══ HEADER ═══ --}}
    <div class="header">
        <table class="header-table">
            <tr>
                <td class="header-left">
                    @if($seller['logo'])
                        <img src="{{ $seller['logo'] }}" alt="{{ $seller['name'] }}" class="brand-logo"><br>
                    @endif
                    <span class="brand-name">{{ $seller['name'] }}</span>
                    @php
                        $sellerHeadMeta = collect([
                            $sellerAddr ?: null,
                            collect([
                                $seller['gstin'] ? 'GSTIN: ' . $seller['gstin'] : null,
                                $seller['pan'] ? 'PAN: ' . $seller['pan'] : null,
                            ])->filter()->join('   ·   ') ?: null,
                            collect([$seller['email'], $seller['phone'], $seller['website']])->filter()->join('   ·   ') ?: null,
                        ])->filter()->join("\n");
                    @endphp
                    @if($sellerHeadMeta)
                        <div class="brand-meta">{{ $sellerHeadMeta }}</div>
                    @endif
                </td>
                <td class="header-right">
                    <div class="doc-title">Tax Invoice</div>
                    <div class="doc-sub">Original for Recipient</div>
                </td>
            </tr>
        </table>
    </div>

    {{-- ═══ META BAND ═══ --}}
    <div class="meta-band">
        <table class="meta-table">
            <tr>
                <td>
                    <div class="meta-label">Invoice No.</div>
                    <div class="meta-value">{{ $inv['invoice_number'] }}</div>
                </td>
                <td>
                    <div class="meta-label">Invoice Date</div>
                    <div class="meta-value">{{ $inv['issued_at']->format('d M Y') }}</div>
                </td>
                <td class="last">
                    <div class="meta-label">Place of Supply</div>
                    <div class="meta-value">{{ $inv['place_of_supply'] }}</div>
                </td>
            </tr>
        </table>
    </div>

    {{-- ═══ BODY ═══ --}}
    <div class="body">

        {{-- Parties --}}
        <table class="parties">
            <tr>
                <td>
                    <div class="party-label">Billed By</div>
                    <div class="party-card">
                        <div class="party-name">{{ $seller['name'] }}</div>
                        @if($sellerAddr)<div class="party-line">{{ $sellerAddr }}</div>@endif
                        @if($seller['gstin'] || $seller['pan'])
                            <div class="party-tax">
                                @if($seller['gstin'])GSTIN: <b>{{ $seller['gstin'] }}</b>@endif
                                @if($seller['gstin'] && $seller['pan']) &nbsp;·&nbsp; @endif
                                @if($seller['pan'])PAN: <b>{{ $seller['pan'] }}</b>@endif
                            </div>
                        @endif
                    </div>
                </td>
                <td class="right">
                    <div class="party-label">Billed To</div>
                    <div class="party-card buyer">
                        <div class="party-name">{{ $buyer['name'] }}</div>
                        @php
                            $buyerContact = collect([$buyer['email'], $buyer['phone']])->filter()->join('  ·  ');
                        @endphp
                        @if($buyerAddr)<div class="party-line">{{ $buyerAddr }}</div>@endif
                        @if($buyerContact)<div class="party-line">{{ $buyerContact }}</div>@endif
                        <div class="party-tax">
                            GSTIN: <b>{{ $buyer['gstin'] ?: 'Unregistered' }}</b>
                        </div>
                    </div>
                </td>
            </tr>
        </table>

        {{-- Items --}}
        <div class="section-head">Particulars</div>
        <table class="items">
            <thead>
                <tr>
                    <th style="width:4%">#</th>
                    <th style="width:48%">Description</th>
                    <th class="c" style="width:12%">SAC</th>
                    <th class="c" style="width:7%">Qty</th>
                    <th class="r" style="width:14%">Rate ({{ $cur }})</th>
                    <th class="r" style="width:15%">Amount ({{ $cur }})</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="c">1</td>
                    <td>
                        <div class="item-title">{{ $inv['line']['title'] }}</div>
                        <div class="item-desc">{{ $inv['line']['description'] }}</div>
                    </td>
                    <td class="c">{{ $inv['line']['sac'] }}</td>
                    <td class="c">{{ $inv['line']['qty'] }}</td>
                    <td class="r">{{ number_format($inv['original_amount'], 2) }}</td>
                    <td class="r">{{ number_format($inv['original_amount'], 2) }}</td>
                </tr>
            </tbody>
        </table>

        {{-- Words + Totals --}}
        <table class="bottom">
            <tr>
                <td class="words-cell">
                    <div class="words-box">
                        <div class="words-label">Total Amount (in words)</div>
                        <div class="words-text">{{ $inv['amount_in_words'] }}</div>
                    </div>
                    @if($inv['coupon_code'])
                        <div style="font-size:8.5px;color:#94a3b8;margin-top:9px">Coupon applied: <b style="color:{{ $accent }}">{{ $inv['coupon_code'] }}</b></div>
                    @endif
                </td>
                <td class="totals-cell">
                    <table class="totals">
                        <tr>
                            <td class="t-label">Subtotal</td>
                            <td class="t-value">{{ $cur }} {{ number_format($inv['original_amount'], 2) }}</td>
                        </tr>
                        @if($inv['discount_amount'] > 0)
                        <tr>
                            <td class="t-label">Discount (–)</td>
                            <td class="t-value discount">– {{ $cur }} {{ number_format($inv['discount_amount'], 2) }}</td>
                        </tr>
                        @endif
                        <tr>
                            <td class="t-label sub">Taxable Value</td>
                            <td class="t-value sub">{{ $cur }} {{ number_format($inv['taxable_amount'], 2) }}</td>
                        </tr>

                        @if($inv['gst_amount'] > 0)
                            @if($inv['is_inter_state'])
                                <tr>
                                    <td class="t-label sub">IGST @ {{ rtrim(rtrim(number_format($inv['gst_percentage'], 2), '0'), '.') }}%</td>
                                    <td class="t-value sub">{{ $cur }} {{ number_format($inv['igst_amount'], 2) }}</td>
                                </tr>
                            @else
                                <tr>
                                    <td class="t-label sub">CGST @ {{ rtrim(rtrim(number_format($inv['gst_percentage'] / 2, 2), '0'), '.') }}%</td>
                                    <td class="t-value sub">{{ $cur }} {{ number_format($inv['cgst_amount'], 2) }}</td>
                                </tr>
                                <tr>
                                    <td class="t-label sub">SGST @ {{ rtrim(rtrim(number_format($inv['gst_percentage'] / 2, 2), '0'), '.') }}%</td>
                                    <td class="t-value sub">{{ $cur }} {{ number_format($inv['sgst_amount'], 2) }}</td>
                                </tr>
                            @endif
                            <tr>
                                <td class="t-label">Total GST</td>
                                <td class="t-value">{{ $cur }} {{ number_format($inv['gst_amount'], 2) }}</td>
                            </tr>
                        @endif

                        <tr class="grand">
                            <td>Grand Total</td>
                            <td class="t-value">{{ $cur }} {{ number_format($inv['total_amount'], 2) }}</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        {{-- Payment received --}}
        <div class="paid-box">
            <div class="paid-title">✓ Payment Received</div>
            <div class="paid-row">
                Date: <b>{{ $inv['payment']['paid_at']->format('d M Y') }}</b>
                &nbsp;·&nbsp; Mode: <b>{{ $inv['payment']['method'] }}</b>
                @if($inv['payment']['reference'])
                    &nbsp;·&nbsp; Ref: <b>{{ $inv['payment']['reference'] }}</b>
                @endif
            </div>
        </div>

        {{-- Terms + Signature --}}
        <table class="foot-table">
            <tr>
                <td class="terms-cell">
                    <div class="terms-label">Terms &amp; Conditions</div>
                    <div class="terms-text">{{ $inv['terms'] }}</div>
                    @if($inv['footer_note'])
                        <div class="terms-text" style="margin-top:8px">{{ $inv['footer_note'] }}</div>
                    @endif
                </td>
                <td class="sign-cell">
                    <div class="sign-box">
                        <div class="sign-for">For {{ $seller['name'] }}</div>
                        <div class="sign-gap"></div>
                        <div class="sign-role">Authorised Signatory</div>
                    </div>
                </td>
            </tr>
        </table>

        <div class="declaration">
            This is a computer-generated invoice and is valid without a signature or company seal.
            @if($inv['gst_amount'] <= 0)
                GST has not been charged on this invoice.
            @endif
            E &amp; O.E.
        </div>

    </div>

    {{-- ═══ FOOTER ═══ --}}
    <div class="doc-footer">
        <table class="doc-footer-table">
            <tr>
                <td>{{ $seller['name'] }}@if($seller['gstin']) &nbsp;·&nbsp; GSTIN {{ $seller['gstin'] }}@endif</td>
                <td class="r">Invoice {{ $inv['invoice_number'] }} &nbsp;·&nbsp; Generated {{ now()->format('d M Y') }}</td>
            </tr>
        </table>
    </div>

</body>
</html>
