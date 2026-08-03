<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Tax Invoice – {{ $invoice->number }}</title>
    @php
        $primaryColor    = $pdfSettings->primary_color ?? '#1e3a5f';
        $accentColor     = $pdfSettings->accent_color  ?? '#3b82f6';
        $logoPosition    = $pdfSettings->logo_position ?? 'left';
        $footerNote      = $pdfSettings->footer_note ?? null;
        $showBankDetails = $pdfSettings->show_bank_details ?? true;
        $showTaxSummary  = $pdfSettings->show_tax_summary ?? true;
        $fontFamily      = $pdfSettings->font_family ?? 'DejaVu Sans';
    @endphp
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        /* ─── PAGE / MULTI-PAGE SETUP ───
             Bottom margin reserves space for the fixed running footer below,
             so a second (or later) page never overlaps it. */
        @page {
            margin: 0 0 65px 0;
        }

        body {
            font-family: "{{ $fontFamily }}", "DejaVu Sans", sans-serif;
            font-size: 12px;
            color: #1e293b;
            background: #ffffff;
            line-height: 1.5;
        }

        /* ─── PAGE WRAPPER ─── */
        .page {
            width: 100%;
            padding: 0;
        }

        /* ─── TOP HEADER BAR ─── */
        .header-bar {
            background: {{ $primaryColor }};
            width: 100%;
            padding: 22px 32px;
        }
        .header-inner {
            width: 100%;
        }
        .header-left { vertical-align: middle; width: 60%; }
        .header-right { vertical-align: middle; width: 40%; text-align: right; }

        .company-logo {
            max-height: 55px;
            max-width: 180px;
            margin-bottom: 8px;
        }
        .company-name {
            font-size: 20px;
            font-weight: bold;
            color: #ffffff;
            letter-spacing: 0.5px;
        }
        .company-tagline {
            font-size: 10px;
            color: #94a3b8;
            margin-top: 2px;
            letter-spacing: 0.5px;
        }
        .company-contact {
            font-size: 10px;
            color: #cbd5e1;
            margin-top: 6px;
            line-height: 1.8;
        }

        .invoice-heading {
            font-size: 28px;
            font-weight: bold;
            color: #ffffff;
            letter-spacing: 3px;
            text-transform: uppercase;
        }
        .invoice-sub {
            font-size: 11px;
            color: #94a3b8;
            margin-top: 4px;
            letter-spacing: 0.5px;
        }

        /* ─── ACCENT STRIPE ─── */
        .accent-stripe {
            width: 100%;
            height: 4px;
            background: {{ $accentColor }};
        }

        /* ─── INVOICE META BAND ─── */
        .meta-band {
            background: #f1f5f9;
            border-bottom: 1px solid #e2e8f0;
            padding: 14px 32px;
        }
        .meta-inner { width: 100%; }
        .meta-cell {
            padding: 0 20px 0 0;
            vertical-align: middle;
            width: 25%;
        }
        .meta-cell:last-child { padding-right: 0; }
        .meta-label {
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #94a3b8;
        }
        .meta-value {
            font-size: 13px;
            font-weight: bold;
            color: #1e293b;
            margin-top: 2px;
        }
        .meta-value.overdue { color: #dc2626; }

        .status-badge {
            display: inline-block;
            padding: 3px 11px;
            border-radius: 9px;
            font-size: 10.5px;
            font-weight: bold;
            letter-spacing: 0.5px;
        }
        .status-badge.status-paid    { background: #dcfce7; color: #16a34a; }
        .status-badge.status-sent    { background: #dbeafe; color: #2563eb; }
        .status-badge.status-draft   { background: #f1f5f9; color: #64748b; }
        .status-badge.status-partial { background: #fef3c7; color: #d97706; }
        .status-badge.status-overdue { background: #fee2e2; color: #dc2626; }

        /* ─── BODY CONTENT ─── */
        .body-content { padding: 24px 32px; }

        /* ─── BILL TO / FROM ─── */
        .party-table { width: 100%; margin-bottom: 24px; }
        .party-cell { width: 48%; vertical-align: top; }
        .party-box {
            border: 1px solid #e2e8f0;
            border-top: 3px solid {{ $primaryColor }};
            padding: 14px 16px;
            border-radius: 2px;
            page-break-inside: avoid;
        }
        .party-box.buyer { border-top-color: {{ $accentColor }}; }
        .party-label {
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: #94a3b8;
            margin-bottom: 8px;
        }
        .party-name {
            font-size: 14px;
            font-weight: bold;
            color: #0f172a;
            margin-bottom: 3px;
        }
        .party-company {
            font-size: 12px;
            font-weight: bold;
            color: #334155;
            margin-bottom: 3px;
        }
        .party-detail {
            font-size: 11px;
            color: #64748b;
            line-height: 1.8;
        }
        .party-gst {
            margin-top: 6px;
            padding-top: 6px;
            border-top: 1px dashed #e2e8f0;
            font-size: 11px;
            color: #475569;
        }
        .party-gst span { font-weight: bold; color: #1e293b; }

        /* ─── SECTION TITLE ─── */
        .section-title {
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: {{ $primaryColor }};
            border-bottom: 2px solid {{ $primaryColor }};
            padding-bottom: 5px;
            margin-bottom: 12px;
        }

        /* ─── ITEMS TABLE ───
             thead repeats automatically on every page dompdf breaks the table
             across; tbody rows get page-break-inside:avoid so a single item
             row is never split top/bottom across a page. */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 0;
            /* Pinned to DejaVu Sans regardless of $fontFamily above: dompdf
               doesn't fall back per-glyph, so a Base-14 font (Helvetica/
               Times/Courier) would silently drop the ₹ (U+20B9) glyph. */
            font-family: "DejaVu Sans", sans-serif;
        }
        .items-table thead { display: table-header-group; }
        .items-table tbody { display: table-row-group; }
        .items-table thead tr {
            background: {{ $primaryColor }};
        }
        .items-table thead th {
            padding: 10px 10px;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #ffffff;
            border: none;
            text-align: left;
        }
        .items-table thead th.r { text-align: right; }
        .items-table thead th.c { text-align: center; }

        .items-table tbody tr { border-bottom: 1px solid #f1f5f9; page-break-inside: avoid; }
        .items-table tbody tr:nth-child(even) { background: #f8fafc; }
        .items-table tbody tr:last-child { border-bottom: 2px solid #e2e8f0; }

        .items-table tbody td {
            padding: 9px 10px;
            font-size: 11px;
            color: #334155;
            vertical-align: top;
        }
        .items-table tbody td.r { text-align: right; }
        .items-table tbody td.c { text-align: center; }

        .item-sr {
            color: #94a3b8;
            font-size: 11px;
        }
        .item-desc-main { font-size: 12px; font-weight: bold; color: #0f172a; }
        .item-desc-sub  { font-size: 10px; color: #94a3b8; margin-top: 2px; }
        .item-hsn       { font-size: 10px; color: #64748b; margin-top: 2px; }

        /* ─── TOTALS + BANK ─── */
        .bottom-section { width: 100%; margin-top: 0; }
        .bank-cell   { width: 52%; vertical-align: top; padding-right: 16px; padding-top: 16px; }
        .totals-cell { width: 48%; vertical-align: top; padding-top: 4px; }

        .bank-box {
            border: 1px solid #e2e8f0;
            border-left: 4px solid {{ $primaryColor }};
            padding: 12px 14px;
            background: #f8fafc;
            page-break-inside: avoid;
        }
        .bank-title {
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: {{ $primaryColor }};
            margin-bottom: 8px;
        }
        .bank-row {
            font-size: 11px;
            color: #475569;
            padding: 2px 0;
        }
        .bank-row span { font-weight: bold; color: #1e293b; min-width: 100px; display: inline-block; }

        .totals-table { width: 100%; border-collapse: collapse; font-family: "DejaVu Sans", sans-serif; }
        .totals-table td { padding: 7px 12px; font-size: 12px; }
        .totals-table tr { border-bottom: 1px solid #f1f5f9; }
        .t-label { color: #64748b; }
        .t-value { text-align: right; font-weight: bold; color: #1e293b; }
        .t-discount { text-align: right; font-weight: bold; color: #dc2626; }
        .t-gst-label { color: #64748b; font-size: 11px; }

        .total-final-row td {
            background: {{ $primaryColor }};
            color: #ffffff;
            font-size: 14px;
            font-weight: bold;
            padding: 11px 12px;
        }
        .total-final-row .t-value { color: #ffffff; text-align: right; }

        .balance-row td {
            background: #fef2f2;
            color: #991b1b;
            font-weight: bold;
            padding: 8px 12px;
            font-size: 13px;
        }
        .balance-row .t-value { color: #991b1b; text-align: right; }

        .paid-row td {
            background: #f0fdf4;
            color: #15803d;
            font-weight: bold;
            padding: 8px 12px;
        }
        .paid-row .t-value { color: #15803d; text-align: right; }

        /* ─── AMOUNT IN WORDS ─── */
        .amount-words {
            background: #f1f5f9;
            border: 1px solid #e2e8f0;
            border-left: 4px solid {{ $accentColor }};
            padding: 10px 16px;
            margin-top: 16px;
            font-size: 11px;
            page-break-inside: avoid;
        }
        .amount-words-label {
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #94a3b8;
            margin-bottom: 3px;
        }
        .amount-words-text {
            font-size: 12px;
            font-weight: bold;
            color: #1e293b;
            font-style: italic;
        }

        /* ─── PAYMENT RECEIVED ─── */
        .payment-received {
            background: #f0fdf4;
            border: 1px solid #86efac;
            border-left: 4px solid #16a34a;
            padding: 12px 16px;
            margin-top: 16px;
            page-break-inside: avoid;
            font-family: "DejaVu Sans", sans-serif;
        }
        .payment-received-title {
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #15803d;
            margin-bottom: 4px;
        }
        .payment-received-row {
            font-size: 11px;
            color: #166534;
            line-height: 1.7;
        }

        /* ─── NOTES & TERMS ─── */
        .notes-terms { width: 100%; margin-top: 20px; }
        .notes-box-cell { width: 48%; vertical-align: top; padding-right: 12px; }
        .terms-box-cell { width: 48%; vertical-align: top; padding-left: 12px; }
        .nt-title {
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #94a3b8;
            margin-bottom: 5px;
        }
        .nt-body {
            font-size: 11px;
            color: #64748b;
            line-height: 1.7;
            padding: 10px 12px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            page-break-inside: avoid;
        }

        /* ─── SIGNATURE SECTION ─── */
        .signature-section {
            width: 100%;
            margin-top: 24px;
            border-top: 1px solid #e2e8f0;
            padding-top: 16px;
            page-break-inside: avoid;
        }
        .sig-table { width: 100%; }
        .sig-left  { width: 48%; vertical-align: bottom; }
        .sig-right { width: 48%; vertical-align: bottom; text-align: right; }
        .sig-box-right {
            border: 1px solid #e2e8f0;
            padding: 10px 14px;
            display: inline-block;
            text-align: center;
            min-width: 200px;
        }
        .sig-label {
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #94a3b8;
        }
        .sig-space {
            height: 48px;
            border-bottom: 1px solid #cbd5e1;
            margin: 8px 0;
        }
        .sig-name { font-size: 12px; font-weight: bold; color: #1e293b; }
        .sig-designation { font-size: 10px; color: #94a3b8; }

        .declaration {
            font-size: 10px;
            color: #94a3b8;
            line-height: 1.7;
            margin-top: 8px;
        }

        /* ─── FOOTER ───
             Fixed so it repeats identically on every page (including page 2+
             when the items table overflows), instead of only printing once
             after the last block of content. */
        .footer-bar {
            position: fixed;
            bottom: -65px;
            left: 0;
            right: 0;
            background: {{ $primaryColor }};
            padding: 12px 32px;
        }
        .footer-inner { width: 100%; }
        .footer-left  { width: 60%; vertical-align: middle; }
        .footer-right { width: 40%; vertical-align: middle; text-align: right; }
        .footer-text  { font-size: 10px; color: #94a3b8; line-height: 1.7; }
        .footer-page  { font-size: 10px; color: #94a3b8; }
        .footer-pagenum:after { content: "Page " counter(page) " of " counter(pages); }
    </style>
</head>
<body>
<div class="page">

    {{-- ════════════════════════════════════════════
         TOP HEADER
    ════════════════════════════════════════════ --}}
    <div class="header-bar">
        <table class="header-inner">
            <tr>
                <td class="header-left" style="text-align:{{ $logoPosition === 'left' ? 'left' : ($logoPosition === 'right' ? 'right' : 'center') }};">
                    @if($tenant->logo)
                        <img src="{{ public_path('storage/' . $tenant->logo) }}"
                             alt="{{ $tenant->name }}"
                             class="company-logo"><br>
                    @endif
                    <div class="company-name">{{ $tenant->name }}</div>
                    <div class="company-tagline">
                        @if(isset($tenant->settings['tagline'])) {{ $tenant->settings['tagline'] }} @endif
                    </div>
                    <div class="company-contact">
                        @if($tenant->email)    {{ $tenant->email }}<br>@endif
                        @if($tenant->phone)    {{ $tenant->phone }}<br>@endif
                        @if(isset($tenant->settings['address'])) {{ $tenant->settings['address'] }}<br>@endif
                        @if(isset($tenant->settings['gstin']))   GSTIN: {{ $tenant->settings['gstin'] }}@endif
                    </div>
                </td>
                <td class="header-right">
                    <div class="invoice-heading">TAX INVOICE</div>
                    <div class="invoice-sub">Original for Recipient</div>
                </td>
            </tr>
        </table>
    </div>

    {{-- ACCENT STRIPE --}}
    <div class="accent-stripe"></div>

    {{-- ════════════════════════════════════════════
         META BAND — Invoice # / Date / Due / Status
    ════════════════════════════════════════════ --}}
    <div class="meta-band">
        <table class="meta-inner">
            <tr>
                <td class="meta-cell">
                    <div class="meta-label">Invoice No.</div>
                    <div class="meta-value">{{ $invoice->number }}</div>
                </td>
                <td class="meta-cell">
                    <div class="meta-label">Invoice Date</div>
                    <div class="meta-value">{{ $invoice->date->format('d M Y') }}</div>
                </td>
                <td class="meta-cell">
                    <div class="meta-label">Due Date</div>
                    <div class="meta-value {{ $invoice->isOverdue() ? 'overdue' : '' }}">
                        {{ $invoice->due_date->format('d M Y') }}
                    </div>
                </td>
                <td class="meta-cell" style="text-align:right;">
                    <div class="meta-label">Status</div>
                    <div class="meta-value" style="margin-top:4px;">
                        <span class="status-badge status-{{ $invoice->status }}">{{ strtoupper($invoice->status) }}</span>
                        @if($invoice->isOverdue()) &nbsp;⚠ @endif
                    </div>
                </td>
            </tr>
        </table>
    </div>

    {{-- ════════════════════════════════════════════
         BODY
    ════════════════════════════════════════════ --}}
    <div class="body-content">

        {{-- ── BILL FROM / BILL TO ── --}}
        <table class="party-table">
            <tr>
                <td class="party-cell">
                    <div class="party-box">
                        <div class="party-label">Billed By (Seller)</div>
                        <div class="party-name">{{ $tenant->name }}</div>
                        @if(isset($tenant->settings['address']))
                            <div class="party-detail">{{ $tenant->settings['address'] }}</div>
                        @endif
                        @if($tenant->email || $tenant->phone)
                            <div class="party-detail">
                                @if($tenant->email) {{ $tenant->email }}<br>@endif
                                @if($tenant->phone) {{ $tenant->phone }}@endif
                            </div>
                        @endif
                        @if(isset($tenant->settings['gstin']))
                            <div class="party-gst">
                                GSTIN: <span>{{ $tenant->settings['gstin'] }}</span>
                            </div>
                        @endif
                        @if(isset($tenant->settings['pan']))
                            <div class="party-detail" style="font-size:10px; color:#64748b;">
                                PAN: {{ $tenant->settings['pan'] }}
                            </div>
                        @endif
                    </div>
                </td>
                <td style="width:4%;"></td>
                <td class="party-cell">
                    <div class="party-box buyer">
                        <div class="party-label">Billed To (Buyer)</div>
                        <div class="party-name">{{ $invoice->contact->name ?? '—' }}</div>
                        @if($invoice->contact?->company)
                            <div class="party-company">{{ $invoice->contact->company }}</div>
                        @endif
                        <div class="party-detail">
                            @if($invoice->contact?->email)   {{ $invoice->contact->email }}<br>@endif
                            @if($invoice->contact?->phone)   {{ $invoice->contact->phone }}<br>@endif
                            @if($invoice->contact?->address) {{ $invoice->contact->address }}<br>@endif
                            @php
                                $cityState = collect([
                                    $invoice->contact->city    ?? null,
                                    $invoice->contact->state   ?? null,
                                    $invoice->contact->pincode ?? null,
                                ])->filter()->join(', ');
                            @endphp
                            @if($cityState) {{ $cityState }} @endif
                        </div>
                        @if($invoice->contact?->gst_number)
                            <div class="party-gst">
                                GSTIN: <span>{{ $invoice->contact->gst_number }}</span>
                            </div>
                        @endif
                    </div>
                </td>
            </tr>
        </table>

        {{-- ── ITEMS TABLE ── --}}
        <div class="section-title">Particulars of Supply</div>
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width:4%;">#</th>
                    <th style="width:38%;">Description of Goods / Services</th>
                    <th class="c" style="width:8%;">HSN/SAC</th>
                    <th class="c" style="width:8%;">Qty</th>
                    <th class="r" style="width:14%;">Unit Price (₹)</th>
                    <th class="c" style="width:8%;">GST %</th>
                    <th class="r" style="width:10%;">GST Amt (₹)</th>
                    <th class="r" style="width:10%;">Total (₹)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoice->items as $i => $item)
                    @php
                        $qty      = floatval($item['quantity'] ?? 0);
                        $rate     = floatval($item['rate']     ?? 0);
                        $taxPct   = floatval($item['tax_percent'] ?? $invoice->tax_percent ?? 18);
                        $lineBase = $qty * $rate;
                        $lineGst  = round($lineBase * $taxPct / 100, 2);
                        $lineTotal= round($lineBase + $lineGst, 2);
                    @endphp
                    <tr>
                        <td class="item-sr c">{{ $i + 1 }}</td>
                        <td>
                            <div class="item-desc-main">{{ $item['description'] ?? '' }}</div>
                            @if(!empty($item['notes']))
                                <div class="item-desc-sub">{{ $item['notes'] }}</div>
                            @endif
                            @if(!empty($item['hsn']))
                                <div class="item-hsn">HSN: {{ $item['hsn'] }}</div>
                            @endif
                        </td>
                        <td class="c" style="font-size:10px; color:#94a3b8;">
                            {{ $item['hsn'] ?? '—' }}
                        </td>
                        <td class="c">{{ $qty }}</td>
                        <td class="r">{{ number_format($rate, 2) }}</td>
                        <td class="c">{{ number_format($taxPct, 0) }}%</td>
                        <td class="r">{{ number_format($lineGst, 2) }}</td>
                        <td class="r" style="font-weight:bold; color:#0f172a;">
                            {{ number_format($lineTotal, 2) }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        {{-- ── BOTTOM: BANK DETAILS  +  TOTALS ── --}}
        <table class="bottom-section">
            <tr>
                {{-- BANK DETAILS --}}
                <td class="bank-cell">

                    @php $hasBankDetails = $showBankDetails && (isset($tenant->settings['bank_name']) || isset($tenant->settings['account_number'])); @endphp
                    @if($hasBankDetails)
                    <div class="bank-box">
                        <div class="bank-title">Payment / Bank Details</div>
                        @if(isset($tenant->settings['bank_name']))
                            <div class="bank-row"><span>Bank Name</span> {{ $tenant->settings['bank_name'] }}</div>
                        @endif
                        @if(isset($tenant->settings['account_name']))
                            <div class="bank-row"><span>Account Name</span> {{ $tenant->settings['account_name'] }}</div>
                        @endif
                        @if(isset($tenant->settings['account_number']))
                            <div class="bank-row"><span>Account No.</span> {{ $tenant->settings['account_number'] }}</div>
                        @endif
                        @if(isset($tenant->settings['ifsc']))
                            <div class="bank-row"><span>IFSC Code</span> {{ $tenant->settings['ifsc'] }}</div>
                        @endif
                        @if(isset($tenant->settings['upi']))
                            <div class="bank-row"><span>UPI</span> {{ $tenant->settings['upi'] }}</div>
                        @endif
                    </div>
                    @endif

                    {{-- AMOUNT IN WORDS --}}
                    <div class="amount-words" style="margin-top: {{ $hasBankDetails ? '12px' : '0px' }};">
                        <div class="amount-words-label">Total Amount (in words)</div>
                        <div class="amount-words-text">
                            {{ \App\Helpers\NumberToWords::convert($invoice->total) }} Only
                        </div>
                    </div>

                    {{-- QUOTATION REF --}}
                    @if($invoice->quotation)
                        <div style="margin-top:10px; font-size:10px; color:#94a3b8;">
                            Against Quotation: <strong style="color:{{ $primaryColor }};">{{ $invoice->quotation->number }}</strong>
                        </div>
                    @endif

                </td>

                {{-- TOTALS --}}
                <td class="totals-cell">
                    <table class="totals-table">
                        <tr>
                            <td class="t-label">Subtotal</td>
                            <td class="t-value">₹ {{ number_format($invoice->subtotal, 2) }}</td>
                        </tr>
                        @if($invoice->discount > 0)
                        <tr>
                            <td class="t-label">Discount (–)</td>
                            <td class="t-discount">– ₹ {{ number_format($invoice->discount, 2) }}</td>
                        </tr>
                        @php $taxableAmount = $invoice->subtotal - $invoice->discount; @endphp
                        <tr>
                            <td class="t-gst-label">Taxable Amount</td>
                            <td class="t-value" style="font-size:11px; color:#64748b;">₹ {{ number_format($taxableAmount, 2) }}</td>
                        </tr>
                        @else
                            @php $taxableAmount = $invoice->subtotal; @endphp
                        @endif

                        {{-- GST Split --}}
                        @if($showTaxSummary)
                            @php
                                $cgst = round($invoice->tax_amount / 2, 2);
                                $sgst = round($invoice->tax_amount / 2, 2);
                            @endphp
                            <tr>
                                <td class="t-gst-label">CGST ({{ number_format($invoice->tax_percent / 2, 1) }}%)</td>
                                <td class="t-value" style="font-size:11px; color:#64748b;">₹ {{ number_format($cgst, 2) }}</td>
                            </tr>
                            <tr>
                                <td class="t-gst-label">SGST ({{ number_format($invoice->tax_percent / 2, 1) }}%)</td>
                                <td class="t-value" style="font-size:11px; color:#64748b;">₹ {{ number_format($sgst, 2) }}</td>
                            </tr>
                            <tr>
                                <td class="t-label">Total GST ({{ number_format($invoice->tax_percent, 0) }}%)</td>
                                <td class="t-value">₹ {{ number_format($invoice->tax_amount, 2) }}</td>
                            </tr>
                        @else
                            <tr>
                                <td class="t-label">GST ({{ number_format($invoice->tax_percent, 0) }}%)</td>
                                <td class="t-value">₹ {{ number_format($invoice->tax_amount, 2) }}</td>
                            </tr>
                        @endif

                        {{-- GRAND TOTAL --}}
                        <tr class="total-final-row">
                            <td>Grand Total</td>
                            <td class="t-value">₹ {{ number_format($invoice->total, 2) }}</td>
                        </tr>

                        {{-- PAID --}}
                        @if($invoice->paid_amount > 0)
                        <tr class="paid-row">
                            <td>Amount Received</td>
                            <td class="t-value">₹ {{ number_format($invoice->paid_amount, 2) }}</td>
                        </tr>
                        <tr class="balance-row">
                            <td>Balance Due</td>
                            <td class="t-value">₹ {{ number_format($invoice->due_amount, 2) }}</td>
                        </tr>
                        @endif
                    </table>
                </td>
            </tr>
        </table>

        {{-- ── PAYMENT RECEIVED BOX (if paid) ── --}}
        @if($invoice->isPaid() && $invoice->paid_at)
        <div class="payment-received">
            <div class="payment-received-title">✓ Payment Received</div>
            <div class="payment-received-row">
                Date: <strong>{{ $invoice->paid_at->format('d M Y') }}</strong>
                &nbsp;&nbsp;|&nbsp;&nbsp;
                Amount: <strong>₹ {{ number_format($invoice->paid_amount, 2) }}</strong>
                @if($invoice->razorpay_payment_id)
                    &nbsp;&nbsp;|&nbsp;&nbsp;
                    Ref No.: <strong>{{ $invoice->razorpay_payment_id }}</strong>
                @endif
            </div>
        </div>
        @endif

        {{-- ── NOTES & TERMS ── --}}
        @if($invoice->notes || $invoice->terms)
        <table class="notes-terms">
            <tr>
                @if($invoice->notes)
                <td class="notes-box-cell">
                    <div class="nt-title">Notes</div>
                    <div class="nt-body">{{ $invoice->notes }}</div>
                </td>
                @endif
                @if($invoice->terms)
                <td class="terms-box-cell" style="{{ !$invoice->notes ? 'padding-left:0;' : '' }}">
                    <div class="nt-title">Terms &amp; Conditions</div>
                    <div class="nt-body">{{ $invoice->terms }}</div>
                </td>
                @endif
            </tr>
        </table>
        @endif

        {{-- ── TENANT'S CUSTOM FOOTER NOTE ── --}}
        @if($footerNote)
        <div class="nt-body" style="margin-top:16px; white-space:pre-line;">{{ $footerNote }}</div>
        @endif

        {{-- ── SIGNATURE SECTION ── --}}
        <div class="signature-section">
            <table class="sig-table">
                <tr>
                    <td class="sig-left">
                        <div class="declaration">
                            We declare that this invoice shows the actual price of the goods/services<br>
                            described and that all particulars are true and correct.<br>
                            <em>Subject to jurisdiction of local courts only.</em>
                        </div>
                        <div style="font-size:10px; color:#94a3b8; margin-top:8px;">
                            E &amp; O.E. &nbsp;&nbsp;|&nbsp;&nbsp;
                            Computer generated invoice — no signature required.
                        </div>
                    </td>
                    <td class="sig-right">
                        <div style="border:1px solid #e2e8f0; padding:12px 20px; text-align:center; display:inline-block; min-width:200px;">
                            <div class="sig-label">For {{ $tenant->name }}</div>
                            <div class="sig-space"></div>
                            <div class="sig-name">Authorised Signatory</div>
                            @if($invoice->createdBy)
                                <div class="sig-designation">{{ $invoice->createdBy->name }}</div>
                            @endif
                        </div>
                    </td>
                </tr>
            </table>
        </div>

    </div>{{-- /body-content --}}

    {{-- ════════════════════════════════════════════
         FOOTER BAR
    ════════════════════════════════════════════ --}}
    <div class="footer-bar">
        <table class="footer-inner">
            <tr>
                <td class="footer-left">
                    <div class="footer-text">
                        {{ $tenant->name }}
                        @if($tenant->email) &nbsp;|&nbsp; {{ $tenant->email }} @endif
                        @if($tenant->phone) &nbsp;|&nbsp; {{ $tenant->phone }} @endif
                        @if(isset($tenant->settings['gstin'])) &nbsp;|&nbsp; GSTIN: {{ $tenant->settings['gstin'] }} @endif
                    </div>
                </td>
                <td class="footer-right">
                    <div class="footer-text" style="color:#475569;">
                        Invoice #{{ $invoice->number }} &nbsp;|&nbsp; Generated {{ now()->format('d M Y') }}
                        &nbsp;|&nbsp; <span class="footer-pagenum"></span>
                    </div>
                </td>
            </tr>
        </table>
    </div>

</div>
</body>
</html>
