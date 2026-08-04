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
        /* Vertical spacing is deliberately tight throughout (margins in
           the 4-14px range) so a typical invoice — a handful of items,
           full bank details, terms — renders on a single page instead
           of spilling a lone signature block onto an otherwise-empty
           page 2. Longer invoices still paginate correctly (items-table
           thead repeats, rows/boxes never split) — see ITEMS TABLE below. */
        /* NOT a universal `*` reset: dompdf silently drops @page's
           margin-top when a `* { margin: 0 }` rule is present anywhere
           in the stylesheet (a dompdf cascade quirk, confirmed by testing
           — margin-bottom is unaffected, only margin-top breaks). Reset
           margin explicitly per-tag instead so the fixed repeating header
           below actually gets its reserved top margin. */
        body, div, table, thead, tbody, tr, td, th, span, em, strong, img {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        /* dompdf only resolves font-weight:"normal" or "bold" when matching
           a font family — anything else (e.g. 600) silently fails to find
           a registered variant and falls back to a font with no ₹ glyph.
           So every emphasised amount/label below is either default weight
           or explicit `bold`, never a numeric weight. */

        @page {
            /* dompdf reliably supports only ONE position:fixed element
               repeating across pages — two (a fixed header + fixed
               footer) causes one of them to silently stop repeating.
               So the footer+signature stack (see BOTTOM-FIXED) is the
               one fixed element; the full header only renders on page 1
               (standard for invoicing tools — Zoho/QuickBooks/Xero all
               do this), and continuation pages get a slim running strip
               folded into the items-table's thead instead, which uses
               dompdf's separate, reliable table-header-repeat mechanism. */
            margin-top: 0;
            margin-right: 0;
            margin-bottom: 118px;
            margin-left: 0;
        }

        body {
            font-family: "{{ $fontFamily }}", "DejaVu Sans", sans-serif;
            font-size: 11.5px;
            color: #1e293b;
            background: #ffffff;
            line-height: 1.5;
        }

        /* ─── HEADER ───
             Renders once at the top of page 1 (standard across invoicing
             tools — Zoho/QuickBooks/Xero don't repeat the full branded
             header either). Continuation pages get a slim running strip
             instead — see the items-table thead's extra row below. The
             accent stripe is folded into this element's own border-bottom
             rather than a separate div. */
        .header-bar {
            background: {{ $primaryColor }};
            border-bottom: 3px solid {{ $accentColor }};
            width: 100%;
            padding: 16px 32px;
        }
        .header-inner { width: 100%; }
        .header-left  { vertical-align: top; width: 62%; text-align: {{ $logoPosition === 'left' ? 'left' : ($logoPosition === 'right' ? 'right' : 'center') }}; }
        .header-right { vertical-align: top; width: 38%; text-align: right; }

        .company-logo   { max-height: 42px; max-width: 160px; margin-bottom: 7px; }
        .company-name   { font-size: 18px; font-weight: bold; color: #ffffff; letter-spacing: 0.3px; }
        .company-tagline{ font-size: 9px; color: #b8c4d9; margin-top: 3px; }
        .company-contact{ font-size: 9px; color: #cbd5e1; margin-top: 6px; line-height: 1.7; }

        .invoice-heading { font-size: 17px; font-weight: bold; color: #ffffff; letter-spacing: 1px; text-transform: uppercase; white-space: nowrap; }
        .invoice-sub      { font-size: 9px; color: #b8c4d9; margin-top: 4px; letter-spacing: 0.3px; }

        /* ─── META BAND ─── */
        .meta-band { background: #f1f5f9; border-bottom: 1px solid #e2e8f0; padding: 9px 32px; }
        .meta-inner { width: 100%; }
        .meta-cell { padding-right: 20px; vertical-align: top; width: 25%; }
        .meta-cell.last { padding-right: 0; text-align: right; }
        .meta-label { font-size: 8.5px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; color: #94a3b8; }
        .meta-value { font-size: 12.5px; font-weight: bold; color: #1e293b; margin-top: 4px; }
        .meta-value.overdue { color: #dc2626; }

        .status-badge {
            display: inline-block;
            padding: 3px 11px;
            border-radius: 9px;
            font-size: 10px;
            font-weight: bold;
            letter-spacing: 0.5px;
        }
        .status-badge.status-paid    { background: #dcfce7; color: #16a34a; }
        .status-badge.status-sent    { background: #dbeafe; color: #2563eb; }
        .status-badge.status-draft   { background: #f1f5f9; color: #64748b; border: 1px solid #cbd5e1; }
        .status-badge.status-partial { background: #fef3c7; color: #d97706; }
        .status-badge.status-overdue { background: #fee2e2; color: #dc2626; }

        /* ─── BODY ─── */
        .body-content { padding: 13px 32px 4px 32px; }

        /* Unified small "sub-header" label used inside every box below
           (Billed By/To, Bank Details, Amount in Words, Notes, Terms,
           Payment Received) — one consistent look everywhere instead of
           several slightly different label styles. */
        .block-label {
            font-size: 8.5px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #94a3b8;
            margin-bottom: 7px;
        }

        /* Major section divider — used once per real section break. */
        .section-heading {
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1.3px;
            color: {{ $primaryColor }};
            border-bottom: 2px solid {{ $primaryColor }};
            padding-bottom: 5px;
            margin-top: 16px;
            margin-bottom: 9px;
        }
        .section-heading.first { margin-top: 0; }

        /* ─── BILL TO / FROM ─── */
        .party-table { width: 100%; }
        .party-cell { width: 48.5%; vertical-align: top; }
        .party-gap { width: 3%; }
        .party-box {
            border: 1px solid #e2e8f0;
            border-top: 3px solid {{ $primaryColor }};
            padding: 10px 14px;
            page-break-inside: avoid;
        }
        .party-box.buyer { border-top-color: {{ $accentColor }}; }
        .party-name    { font-size: 13.5px; font-weight: bold; color: #0f172a; margin-bottom: 3px; }
        .party-company { font-size: 11.5px; font-weight: bold; color: #334155; margin-bottom: 3px; }
        .party-detail  { font-size: 10.5px; color: #64748b; line-height: 1.55; }
        .party-gst {
            margin-top: 6px;
            padding-top: 6px;
            border-top: 1px dashed #e2e8f0;
            font-size: 10.5px;
            color: #475569;
        }
        .party-gst span { font-weight: bold; color: #1e293b; }

        /* ─── ITEMS TABLE ───
             thead repeats automatically on every page dompdf breaks the
             table across; tbody rows get page-break-inside:avoid so a
             single item row is never split top/bottom across a page.
             Pinned to DejaVu Sans regardless of $fontFamily: dompdf
             doesn't fall back per-glyph, so a Base-14 font (Helvetica/
             Times/Courier) would silently drop the ₹ (U+20B9) glyph. */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            font-family: "DejaVu Sans", sans-serif;
        }
        .items-table thead { display: table-header-group; }
        .items-table tbody { display: table-row-group; }
        .items-table thead tr { background: {{ $primaryColor }}; }
        .items-table thead th {
            padding: 6px 8px;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            color: #ffffff;
            text-align: left;
            white-space: nowrap;
        }
        .items-table thead th.r { text-align: right; }
        .items-table thead th.c { text-align: center; }

        /* Repeats on every page via the same thead mechanism as the
           column headers above — the only reliable way to show running
           context (company + invoice #) on continuation pages, since
           dompdf can't repeat a second fixed element alongside the
           bottom-fixed signature/footer stack (see @page comment). */
        .items-table thead tr.running-strip-row { background: {{ $accentColor }}; }
        .running-strip { padding: 3px 8px; font-size: 8px; font-weight: normal; text-transform: none; letter-spacing: 0.2px; color: #ffffff; text-align: left; }

        .items-table tbody tr { border-bottom: 1px solid #f1f5f9; page-break-inside: avoid; }
        .items-table tbody tr:nth-child(even) { background: #f8fafc; }
        .items-table tbody tr:last-child { border-bottom: 2px solid #e2e8f0; }

        .items-table tbody td { padding: 5.5px 8px; font-size: 10.5px; color: #334155; vertical-align: top; }
        .items-table tbody td.r { text-align: right; }
        .items-table tbody td.c { text-align: center; }

        .item-sr        { color: #94a3b8; font-size: 10.5px; }
        .item-desc-main { font-size: 11px; font-weight: bold; color: #0f172a; }
        .item-desc-sub  { font-size: 9.5px; color: #94a3b8; margin-top: 2px; }
        .item-hsn       { font-size: 9.5px; color: #64748b; margin-top: 2px; }
        .item-total     { font-weight: bold; color: #0f172a; }

        /* ─── BANK DETAILS + TOTALS ─── */
        .bottom-section { width: 100%; margin-top: 12px; }
        .bank-cell   { width: 52%; vertical-align: top; padding-right: 18px; }
        .totals-cell { width: 48%; vertical-align: top; }

        .bank-box {
            border: 1px solid #e2e8f0;
            border-left: 3px solid {{ $primaryColor }};
            padding: 10px 14px;
            background: #f8fafc;
            page-break-inside: avoid;
        }
        .bank-row { font-size: 10.5px; color: #475569; padding: 1px 0; }
        .bank-row span { font-weight: bold; color: #1e293b; min-width: 95px; display: inline-block; }

        .amount-words {
            background: #f1f5f9;
            border: 1px solid #e2e8f0;
            border-left: 3px solid {{ $accentColor }};
            padding: 7px 14px;
            margin-top: 8px;
            page-break-inside: avoid;
        }
        .amount-words-text { font-size: 11px; font-weight: bold; color: #1e293b; font-style: italic; line-height: 1.4; }

        .quotation-ref { margin-top: 12px; font-size: 9.5px; color: #94a3b8; }
        .quotation-ref strong { color: {{ $primaryColor }}; }

        .totals-table { width: 100%; border-collapse: collapse; font-family: "DejaVu Sans", sans-serif; }
        .totals-table td { padding: 5px 12px; font-size: 11px; }
        .totals-table tr { border-bottom: 1px solid #f1f5f9; }
        .t-label     { color: #64748b; }
        .t-value     { text-align: right; font-weight: bold; color: #1e293b; }
        .t-discount  { text-align: right; font-weight: bold; color: #dc2626; }
        .t-sub-label { color: #64748b; font-size: 10px; }
        .t-sub-value { text-align: right; font-weight: bold; color: #64748b; font-size: 10px; }

        .total-final-row td { background: {{ $primaryColor }}; color: #ffffff; font-size: 13px; font-weight: bold; padding: 10px 12px; }
        .total-final-row .t-value { color: #ffffff; }

        .balance-row td { background: #fef2f2; color: #991b1b; font-weight: bold; padding: 8px 12px; font-size: 12px; }
        .balance-row .t-value { color: #991b1b; }

        .paid-row td { background: #f0fdf4; color: #15803d; font-weight: bold; padding: 8px 12px; font-size: 11px; }
        .paid-row .t-value { color: #15803d; }

        /* ─── PAYMENT RECEIVED ─── */
        .payment-received {
            background: #f0fdf4;
            border: 1px solid #86efac;
            border-left: 3px solid #16a34a;
            padding: 9px 14px;
            margin-top: 12px;
            page-break-inside: avoid;
            font-family: "DejaVu Sans", sans-serif;
        }
        .payment-received-title { font-size: 9px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; color: #15803d; margin-bottom: 5px; }
        .payment-received-row   { font-size: 10.5px; color: #166534; line-height: 1.7; }

        /* ─── NOTES / TERMS / FOOTER NOTE ─── */
        .notes-terms { width: 100%; margin-top: 10px; }
        .notes-box-cell { width: 48.5%; vertical-align: top; }
        .terms-box-cell { width: 48.5%; vertical-align: top; }
        .notes-gap { width: 3%; }
        .block-body {
            font-size: 10.5px;
            color: #64748b;
            line-height: 1.5;
            padding: 7px 12px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            page-break-inside: avoid;
        }
        .tenant-note {
            margin-top: 12px;
            white-space: pre-line;
        }

        /* ─── BOTTOM-FIXED: signature + footer stack ───
             `top` (not `bottom`) is deliberate: dompdf's positioner for a
             fixed block-level element only ever reads `top`/`left` — a
             `bottom` offset is silently ignored, which made this element
             render once instead of repeating on every page (confirmed by
             reading dompdf's own Positioner\Absolute::position()). A4
             page height at the configured 96dpi is 841.89pt / 0.75 =
             1122.52px; anchoring 118px (this block's own height) up from
             that bottom edge keeps it flush on every page regardless of
             content length. Signature and footer are ordinary flow
             children *inside* this one fixed box, so they always stack
             the same way — this is what pins the signature to a fixed
             spot at the bottom instead of floating wherever the
             preceding content happens to end. */
        .bottom-fixed { position: fixed; top: 1004.52px; left: 0; right: 0; height: 118px; }

        .signature-section { width: 100%; height: 80px; padding: 10px 32px 0 32px; border-top: 1px solid #e2e8f0; }
        .sig-table { width: 100%; }
        .sig-left  { width: 55%; vertical-align: top; }
        .sig-right { width: 45%; vertical-align: top; text-align: right; }
        .sig-box {
            border: 1px solid #e2e8f0;
            padding: 5px 16px;
            display: inline-block;
            text-align: center;
            min-width: 180px;
        }
        .declaration { font-size: 9px; color: #94a3b8; line-height: 1.4; }
        .sig-space { height: 16px; border-bottom: 1px solid #cbd5e1; margin: 3px 0; }
        .sig-name { font-size: 11px; font-weight: bold; color: #1e293b; }
        .sig-designation { font-size: 9.5px; color: #94a3b8; }

        /* ─── RUNNING FOOTER ─── */
        .footer-bar { height: 34px; background: {{ $primaryColor }}; padding: 8px 32px; }
        .footer-inner { width: 100%; }
        .footer-left  { width: 62%; vertical-align: middle; }
        .footer-right { width: 38%; vertical-align: middle; text-align: right; }
        .footer-text  { font-size: 8.5px; color: #b8c4d9; line-height: 1.6; }
        .footer-pagenum:after { content: "Page " counter(page) " of " counter(pages); }
    </style>
</head>
<body>

    {{-- ════════════════════════════════════════════
         HEADER
    ════════════════════════════════════════════ --}}
    <div class="header-bar">
        <table class="header-inner">
            <tr>
                <td class="header-left">
                    @if($tenant->logo)
                        <img src="{{ public_path('storage/' . $tenant->logo) }}" alt="{{ $tenant->name }}" class="company-logo"><br>
                    @endif
                    <div class="company-name">{{ $tenant->name }}</div>
                    @if(isset($tenant->settings['tagline']))
                        <div class="company-tagline">{{ $tenant->settings['tagline'] }}</div>
                    @endif
                    <div class="company-contact">
                        @if($tenant->email) {{ $tenant->email }}<br>@endif
                        @if($tenant->phone) {{ $tenant->phone }}<br>@endif
                        @if(isset($tenant->settings['address'])) {{ $tenant->settings['address'] }}<br>@endif
                        @if(isset($tenant->settings['gstin'])) GSTIN: {{ $tenant->settings['gstin'] }}@endif
                    </div>
                </td>
                <td class="header-right">
                    <div class="invoice-heading">Tax Invoice</div>
                    <div class="invoice-sub">Original for Recipient</div>
                </td>
            </tr>
        </table>
    </div>

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
                    <div class="meta-value {{ $invoice->isOverdue() ? 'overdue' : '' }}">{{ $invoice->due_date->format('d M Y') }}</div>
                </td>
                <td class="meta-cell last">
                    <div class="meta-label">Status</div>
                    <div class="meta-value" style="margin-top:5px;">
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
                        <div class="block-label">Billed By (Seller)</div>
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
                            <div class="party-gst">GSTIN: <span>{{ $tenant->settings['gstin'] }}</span></div>
                        @endif
                        @if(isset($tenant->settings['pan']))
                            <div class="party-detail" style="margin-top:2px;">PAN: {{ $tenant->settings['pan'] }}</div>
                        @endif
                    </div>
                </td>
                <td class="party-gap"></td>
                <td class="party-cell">
                    <div class="party-box buyer">
                        <div class="block-label">Billed To (Buyer)</div>
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
                            <div class="party-gst">GSTIN: <span>{{ $invoice->contact->gst_number }}</span></div>
                        @endif
                    </div>
                </td>
            </tr>
        </table>

        {{-- ── ITEMS TABLE ── --}}
        <div class="section-heading">Particulars of Supply</div>
        <table class="items-table">
            <thead>
                <tr class="running-strip-row">
                    <th colspan="8" class="running-strip">{{ $tenant->name }} &nbsp;—&nbsp; Invoice #{{ $invoice->number }}</th>
                </tr>
                <tr>
                    <th style="width:3%;">#</th>
                    <th style="width:34%;">Description</th>
                    <th class="c" style="width:9%;">HSN/SAC</th>
                    <th class="c" style="width:7%;">Qty</th>
                    <th class="r" style="width:14%;">Unit Price (₹)</th>
                    <th class="c" style="width:7%;">GST %</th>
                    <th class="r" style="width:12%;">GST Amt (₹)</th>
                    <th class="r" style="width:14%;">Total (₹)</th>
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
                        </td>
                        <td class="c" style="font-size:9.5px; color:#94a3b8;">{{ $item['hsn'] ?? '—' }}</td>
                        <td class="c">{{ $qty }}</td>
                        <td class="r">{{ number_format($rate, 2) }}</td>
                        <td class="c">{{ number_format($taxPct, 0) }}%</td>
                        <td class="r">{{ number_format($lineGst, 2) }}</td>
                        <td class="r item-total">{{ number_format($lineTotal, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        {{-- ── BANK DETAILS  +  TOTALS ── --}}
        <table class="bottom-section">
            <tr>
                <td class="bank-cell">

                    @php $hasBankDetails = $showBankDetails && (isset($tenant->settings['bank_name']) || isset($tenant->settings['account_number'])); @endphp
                    @if($hasBankDetails)
                    <div class="bank-box">
                        <div class="block-label">Payment / Bank Details</div>
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

                    <div class="amount-words" style="{{ $hasBankDetails ? '' : 'margin-top:0;' }}">
                        <div class="block-label">Total Amount (in words)</div>
                        <div class="amount-words-text">{{ \App\Helpers\NumberToWords::convert($invoice->total) }} Only</div>
                    </div>

                    @if($invoice->quotation)
                        <div class="quotation-ref">Against Quotation: <strong>{{ $invoice->quotation->number }}</strong></div>
                    @endif

                </td>

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
                            <td class="t-sub-label">Taxable Amount</td>
                            <td class="t-sub-value">₹ {{ number_format($taxableAmount, 2) }}</td>
                        </tr>
                        @endif

                        @if($showTaxSummary)
                            @php
                                $cgst = round($invoice->tax_amount / 2, 2);
                                $sgst = round($invoice->tax_amount / 2, 2);
                            @endphp
                            <tr>
                                <td class="t-sub-label">CGST ({{ number_format($invoice->tax_percent / 2, 1) }}%)</td>
                                <td class="t-sub-value">₹ {{ number_format($cgst, 2) }}</td>
                            </tr>
                            <tr>
                                <td class="t-sub-label">SGST ({{ number_format($invoice->tax_percent / 2, 1) }}%)</td>
                                <td class="t-sub-value">₹ {{ number_format($sgst, 2) }}</td>
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

                        <tr class="total-final-row">
                            <td>Grand Total</td>
                            <td class="t-value">₹ {{ number_format($invoice->total, 2) }}</td>
                        </tr>

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
                    <div class="block-label">Notes</div>
                    <div class="block-body">{{ $invoice->notes }}</div>
                </td>
                @endif
                @if($invoice->notes && $invoice->terms)
                <td class="notes-gap"></td>
                @endif
                @if($invoice->terms)
                <td class="terms-box-cell">
                    <div class="block-label">Terms &amp; Conditions</div>
                    <div class="block-body">{{ $invoice->terms }}</div>
                </td>
                @endif
            </tr>
        </table>
        @endif

        {{-- ── TENANT'S CUSTOM FOOTER NOTE ── --}}
        @if($footerNote)
        <div class="block-body tenant-note">{{ $footerNote }}</div>
        @endif

    </div>{{-- /body-content --}}

    {{-- ════════════════════════════════════════════
         BOTTOM-FIXED: signature + running footer.
         Fixed as one block so the signature always sits in the same
         spot at the bottom of every page (not wherever content happens
         to end), with the branded footer bar right below it.
    ════════════════════════════════════════════ --}}
    <div class="bottom-fixed">
        <div class="signature-section">
            <table class="sig-table">
                <tr>
                    <td class="sig-left">
                        <div class="declaration">
                            We declare that this invoice shows the actual price of the goods/services and that all
                            particulars are true and correct. <em>Subject to jurisdiction of local courts only.</em>
                            E &amp; O.E. — computer generated invoice, no signature required.
                        </div>
                    </td>
                    <td class="sig-right">
                        <div class="sig-box">
                            <div class="block-label" style="margin-bottom:0;">For {{ $tenant->name }}</div>
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
                        <div class="footer-text">
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
