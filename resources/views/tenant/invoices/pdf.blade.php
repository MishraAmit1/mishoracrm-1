<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Tax Invoice – {{ $invoice->number }}</title>
    @php
        $primaryColor    = $pdfSettings->primary_color ?? '#33475B';
        $accentColor     = $pdfSettings->accent_color  ?? '#FF7A59';
        $logoPosition    = $pdfSettings->logo_position ?? 'left';
        $footerNote      = $pdfSettings->footer_note ?? null;
        $showBankDetails = $pdfSettings->show_bank_details ?? true;
        $showTaxSummary  = $pdfSettings->show_tax_summary ?? true;
        $fontFamily      = $pdfSettings->font_family ?? 'DejaVu Sans';

        $isPaid    = $invoice->isPaid() && $invoice->paid_at;
        $isOverdue = $invoice->isOverdue();
        $isDraft   = $invoice->status === 'draft';

        $watermarkText  = $isPaid ? 'PAID' : ($isOverdue ? 'OVERDUE' : ($isDraft ? 'DRAFT' : null));
        $watermarkColor = $isPaid ? '#16a34a' : ($isOverdue ? '#dc2626' : '#94a3b8');
    @endphp
    <style>
        /* Vertical spacing is deliberately tight throughout (margins in
           the 4-14px range) so a typical invoice — a handful of items,
           full bank details, terms — renders on a single page instead
           of spilling onto a page 2. Longer invoices still paginate
           correctly (items-table thead repeats, rows/boxes never split)
           — see ITEMS TABLE below. Everything below the items table
           (bank details, totals, signature) flows normally rather than
           being pinned to a page's bottom edge — an earlier version
           forced the signature against the bottom of whichever page it
           landed on, which for a short invoice produced an almost-empty
           trailing page. Letting it sit right after the content it
           follows is both simpler and how real invoicing tools
           (Zoho/QuickBooks/Xero) do it. */

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

        /* ─── PAGE MARGINS ───
             The full branded header (logo/name/meta band) now repeats on
             every page via a `position:fixed` block — see .page-header-
             fixed below. Key dompdf quirk this depends on, confirmed by
             rendering an isolated test case and reading back the exact
             text/fill coordinates from the produced PDF: a fixed
             element's `top` is NOT measured from the physical page edge —
             it's measured from the top of the page's *margin box*, i.e.
             offset by whatever @page margin-top is. So a fixed element
             meant to sit flush at the true physical top must use
             `top: -{margin-top value}` to cancel that offset back out.
             margin-top here (214px) is the header block's own measured
             rendered height (198.5px, header-bar + meta-band) plus a
             small buffer for breathing room and for tenant data that
             wraps a touch taller than the measurement case tested.
             margin-bottom (46px) reserves room for the fixed footer the
             same way — see FOOTER below for its own top calculation,
             which must account for this same offset rule. */
        @page {
            margin-top: 214px;
            margin-right: 0;
            margin-bottom: 46px;
            margin-left: 0;
        }

        body {
            font-family: "{{ $fontFamily }}", "DejaVu Sans", sans-serif;
            font-size: 11.5px;
            color: #1e293b;
            background: #ffffff;
            line-height: 1.5;
        }

        /* ─── STATUS WATERMARK ───
             Fixed so it repeats, faint, on every page — the same effect
             every mainstream invoicing tool uses for Paid/Overdue/Draft.
             z-index:-1 keeps it behind normal content; verified in
             isolation that dompdf composites a negative-z-index fixed
             element beneath opaque page content correctly (it only shows
             through the page's white/light backgrounds, exactly as
             intended — it is not meant to show through solid bars). */
        .watermark {
            position: fixed;
            top: 230px;
            left: 0;
            width: 100%;
            text-align: center;
            z-index: -1;
        }
        .watermark span {
            display: inline-block;
            transform: rotate(-27deg);
            font-size: 76px;
            font-weight: bold;
            letter-spacing: 6px;
            text-transform: uppercase;
            color: {{ $watermarkColor }};
            opacity: 0.10;
            border: 5px solid {{ $watermarkColor }};
            padding: 10px 34px;
            border-radius: 10px;
        }

        /* ─── HEADER (+ META BAND) — repeats on every page ───
             Wrapped together in .page-header-fixed, `position:fixed`, so
             the full branded header and the invoice-number/date/status
             strip both appear identically on every page — not just page
             1. See the @page comment above for the margin-offset math
             this depends on.

             "TAX INVOICE" is positioned with `position:absolute; left:`
             — deliberately `left`, not `right`. Read dompdf's own
             Positioner/Absolute.php: for block-level elements it ONLY
             evaluates `$style->left`/`$style->top` — `right` and
             `bottom` are never read at all for this element type (a
             confirmed dompdf limitation, not a CSS mistake). A `right`-
             based version and, before that, a `<table>` shrink-to-content
             column (via `width:1%` and separately a fixed px width) were
             all tried and each independently verified — by decompressing
             an actual rendered PDF's content stream and reading the raw
             Tm/Td text-positioning operators — to start drawing "TAX
             INVOICE" at the same x≈515pt on a 595.28pt-wide page, 20+pt
             past the right edge, regardless of the column/right value
             used. Since "TAX INVOICE" / "Original for Recipient" are
             fixed, unchanging strings (not tenant data), their required
             `left` was calculated directly from dompdf's own
             get_text_width() (102.9pt at this font/spacing, page width
             595.28pt, 30pt right margin → left ≈ 462pt ≈ 605px) and
             re-verified the same way (decoded content stream: text now
             starts at the intended position, comfortably inside the
             page). If this text, its font-size, or its letter-spacing
             ever changes, this value must be recalculated the same way
             — don't assume `right`/percentage/table tricks will work. */
        .page-header-fixed {
            position: fixed;
            top: -214px; /* cancels @page margin-top — see comment above */
            left: 0;
            right: 0;
        }
        .header-bar {
            position: relative;
            background: {{ $primaryColor }};
            border-bottom: 3px solid {{ $accentColor }};
            width: 100%;
            padding: 20px 32px;
        }
        .brand-block {
            /* Reserves room on the right so long company names wrap
               instead of running under the absolutely-positioned title. */
            padding-right: 190px;
            text-align: {{ $logoPosition === 'left' ? 'left' : ($logoPosition === 'right' ? 'right' : 'center') }};
        }
        .title-block {
            position: absolute;
            top: 20px;
            left: 605px;
            text-align: right;
            padding-left: 20px;
            border-left: 1px solid rgba(255,255,255,0.3);
        }

        .company-logo { max-height: 38px; max-width: 150px; vertical-align: middle; margin-right: 12px; }
        .company-name { font-size: 20px; font-weight: bold; color: #ffffff; letter-spacing: 0.2px; vertical-align: middle; }
        .company-tagline { font-size: 9px; color: #b8c4d9; margin-top: 4px; letter-spacing: 0.2px; }
        .company-contact { font-size: 9.5px; color: #cbd5e1; margin-top: 10px; }

        .invoice-heading { font-size: 16px; font-weight: bold; color: #ffffff; letter-spacing: 2px; text-transform: uppercase; }
        .invoice-sub { font-size: 8.5px; color: #b8c4d9; margin-top: 5px; letter-spacing: 0.3px; }

        /* ─── META BAND ─── */
        .meta-band { background: #f1f5f9; border-bottom: 1px solid #e2e8f0; padding: 8px 32px; }
        .meta-inner { width: 100%; }
        .meta-cell { padding-right: 20px; vertical-align: top; width: 25%; border-right: 1px solid #e2e8f0; }
        .meta-cell.last { padding-right: 0; padding-left: 20px; text-align: right; border-right: none; }
        .meta-label { font-size: 8.5px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; color: #94a3b8; }
        .meta-value { font-size: 13px; font-weight: bold; color: #1e293b; margin-top: 4px; }
        .meta-value.overdue { color: #dc2626; }

        .status-badge {
            display: inline-block;
            padding: 3px 12px;
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
        .body-content { padding: 6px 32px 4px 32px; }

        /* Unified small "sub-header" label used inside every box below
           (Billed To, Bank Details, Amount in Words, Notes, Terms,
           Payment Received) — one consistent look everywhere instead of
           several slightly different label styles. */
        .block-label {
            font-size: 8.5px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: {{ $primaryColor }};
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
            margin-top: 15px;
            margin-bottom: 9px;
        }
        .section-heading.first { margin-top: 0; }

        /* ─── BILL TO ─── */
        .party-box {
            border: 1px solid #e2e8f0;
            border-top: 3px solid {{ $primaryColor }};
            border-radius: 6px;
            padding: 10px 15px;
            page-break-inside: avoid;
        }
        .party-box-single { width: 58%; }
        .party-box.buyer { border-top-color: {{ $accentColor }}; }
        .party-name    { font-size: 14px; font-weight: bold; color: #0f172a; margin-bottom: 3px; }
        .party-company { font-size: 11.5px; font-weight: bold; color: #334155; margin-bottom: 3px; }
        .party-detail  { font-size: 10.5px; color: #64748b; line-height: 1.6; }
        .party-gst {
            margin-top: 7px;
            padding-top: 7px;
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
            border: 1px solid #e2e8f0;
        }
        .items-table thead { display: table-header-group; }
        .items-table tbody { display: table-row-group; }
        .items-table thead tr { background: {{ $primaryColor }}; }
        .items-table thead th {
            padding: 6px 9px;
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

        .items-table tbody tr { border-bottom: 1px solid #f1f5f9; page-break-inside: avoid; }
        .items-table tbody tr:nth-child(even) { background: #f8fafc; }
        .items-table tbody tr:last-child { border-bottom: none; }

        .items-table tbody td { padding: 6px 9px; font-size: 10.5px; color: #334155; vertical-align: top; }
        .items-table tbody td.r { text-align: right; }
        .items-table tbody td.c { text-align: center; }

        .item-sr        { color: #94a3b8; font-size: 10.5px; }
        .item-desc-main { font-size: 11px; font-weight: bold; color: #0f172a; }
        .item-desc-sub  { font-size: 9.5px; color: #94a3b8; margin-top: 2px; }
        .item-hsn       { font-size: 9.5px; color: #64748b; margin-top: 2px; }
        .item-total     { font-weight: bold; color: #0f172a; }

        /* ─── AMOUNT IN WORDS / QUOTATION REF ─── */
        .bottom-section { width: 100%; margin-top: 10px; }
        .bank-cell   { width: 52%; vertical-align: top; padding-right: 18px; }
        .totals-cell { width: 48%; vertical-align: top; }

        .amount-words {
            background: #f1f5f9;
            border: 1px solid #e2e8f0;
            border-left: 3px solid {{ $accentColor }};
            border-radius: 6px;
            padding: 7px 14px;
            page-break-inside: avoid;
        }
        .amount-words-text { font-size: 11px; font-weight: bold; color: #1e293b; font-style: italic; line-height: 1.4; }

        .quotation-ref { margin-top: 12px; font-size: 9.5px; color: #94a3b8; }
        .quotation-ref strong { color: {{ $primaryColor }}; }

        /* ─── TOTALS CARD ─── */
        .totals-card {
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 4px;
            page-break-inside: avoid;
        }
        .totals-table { width: 100%; border-collapse: collapse; font-family: "DejaVu Sans", sans-serif; }
        .totals-table td { padding: 5px 10px; font-size: 11px; }
        .totals-table tr { border-bottom: 1px solid #f1f5f9; }
        .t-label     { color: #64748b; }
        .t-value     { text-align: right; font-weight: bold; color: #1e293b; }
        .t-discount  { text-align: right; font-weight: bold; color: #dc2626; }
        .t-sub-label { color: #64748b; font-size: 10px; }
        .t-sub-value { text-align: right; font-weight: bold; color: #64748b; font-size: 10px; }

        /* No border-radius on these full-bleed row backgrounds: each <td>
           would round independently and the straight edge of the row
           above/below it then pokes past that rounding — a visible
           notch at the seam (confirmed by rendering). Square corners
           here, radius only on the .totals-card wrapper, look clean. */
        .total-final-row td { background: {{ $primaryColor }}; color: #ffffff; font-size: 13.5px; font-weight: bold; padding: 10px 12px; }
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
            border-radius: 6px;
            padding: 7px 14px;
            margin-top: 10px;
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
            color: #334155;
            line-height: 1.6;
            padding: 8px 13px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            page-break-inside: avoid;
        }
        .tenant-note {
            margin-top: 14px;
            white-space: pre-line;
        }

        /* ─── BANK DETAILS + SIGNATURE ───
             Ordinary flow content — it renders wherever the preceding
             content ends, not forced to a page's bottom edge (see the
             top-of-file note on why). page-break-inside:avoid keeps each
             box, and the signature row itself, from splitting across a
             page boundary; if the whole row doesn't fit in the space
             left on a page, dompdf pushes it to the next one — normal,
             expected behaviour for a document this shape. */
        .signature-section { width: 100%; margin-top: 8px; padding-top: 7px; border-top: 1px solid #e2e8f0; }
        .sig-table { width: 100%; }
        .sig-left  { width: 55%; vertical-align: bottom; }
        .sig-right { width: 45%; vertical-align: bottom; text-align: right; }

        .bank-box {
            border: 1px solid #e2e8f0;
            border-left: 3px solid {{ $primaryColor }};
            border-radius: 6px;
            padding: 11px 15px;
            margin-bottom: 12px;
            background: #f8fafc;
            page-break-inside: avoid;
        }
        .bank-row { font-size: 10.5px; color: #475569; padding: 1.5px 0; }
        .bank-row span { font-weight: bold; color: #1e293b; min-width: 95px; display: inline-block; }

        .sig-box {
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 7px 18px;
            display: inline-block;
            text-align: center;
            min-width: 190px;
            page-break-inside: avoid;
        }
        .declaration { font-size: 9.5px; color: #94a3b8; line-height: 1.5; page-break-inside: avoid; }
        .sig-space { height: 24px; border-bottom: 1px solid #cbd5e1; margin: 3px 0; }
        .sig-name { font-size: 11px; font-weight: bold; color: #1e293b; }
        .sig-designation { font-size: 9.5px; color: #94a3b8; }

        /* ─── RUNNING FOOTER ───
             The one other fixed/repeating element (besides the header).
             `top` (not `bottom`) is deliberate: dompdf's positioner for
             a fixed block-level element only ever reads `top`/`left` —
             a `bottom` offset is silently ignored and renders the
             element once instead of on every page (confirmed by reading
             dompdf's own Positioner\Absolute::position()).

             An earlier version of this bar tried to fit the tenant's
             full name + email + phone + GSTIN on one line inside a
             fixed 34px-tall box — for longer tenant data that line
             wrapped to two, and since the box's height was fixed the
             second line had nowhere to go but past the page edge,
             getting visibly clipped. Two fixes: the content is now
             short, fixed-shape data only (invoice number, generated
             date, page count — no free-length tenant fields, since the
             repeating header above already shows the full company
             identity on every page), and the box is tall enough for a
             comfortable single line with room to spare regardless.

             Top offset accounts for the same margin-box-relative
             quirk documented above the @page rule: a fixed element's
             `top` is offset by @page's margin-top. Physically the bar's
             own top edge must sit at (page height − bar height) so its
             bottom edge lands flush with the true page bottom edge —
             A4 page height at 96dpi is 841.89pt / 0.75 = 1122.52px, bar
             height 42px, so physical top = 1080.52px; subtracting the
             214px margin-top offset gives the 866.52px used here. Both
             values were re-verified the same way as the header block:
             rendering an isolated test case and reading the exact fill
             rectangle coordinates back out of the produced PDF. */
        .footer-bar { position: fixed; top: 866.52px; left: 0; right: 0; height: 42px; background: {{ $primaryColor }}; border-top: 2px solid {{ $accentColor }}; padding: 9px 32px; }
        .footer-inner { width: 100%; }
        .footer-left  { width: 60%; vertical-align: middle; }
        .footer-right { width: 40%; vertical-align: middle; text-align: right; }
        .footer-text  { font-size: 9px; color: #b8c4d9; line-height: 1.4; white-space: nowrap; }
        .footer-text strong { color: #ffffff; }
        /* "Page N of M" is NOT rendered here: dompdf has no built-in CSS
           counter(pages) (confirmed by reading its source — "page" is a
           real tracked counter, "pages" is not special-cased anywhere
           and silently evaluates to 0). It's drawn as a canvas overlay
           instead, right-aligned into this same footer-right cell's
           space — see InvoiceController::withPageNumbers(). */
    </style>
</head>
<body>

    @if($watermarkText)
    <div class="watermark"><span>{{ $watermarkText }}</span></div>
    @endif

    {{-- ════════════════════════════════════════════
         HEADER + META BAND — fixed, repeats on every page.
    ════════════════════════════════════════════ --}}
    <div class="page-header-fixed">
        <div class="header-bar">
            <div class="title-block">
                <div class="invoice-heading">Tax Invoice</div>
                <div class="invoice-sub">Original for Recipient</div>
            </div>
            <div class="brand-block">
                @if($tenant->logo)
                    <img src="{{ public_path('storage/' . $tenant->logo) }}" alt="{{ $tenant->name }}" class="company-logo">
                @endif
                <span class="company-name">{{ $tenant->name }}</span>
                @if(isset($tenant->settings['tagline']))
                    <div class="company-tagline">{{ $tenant->settings['tagline'] }}</div>
                @endif
                @php
                    $headerContactLine = collect([
                        $tenant->email,
                        $tenant->phone,
                        $tenant->settings['address'] ?? null,
                        isset($tenant->settings['gstin']) ? 'GSTIN: ' . $tenant->settings['gstin'] : null,
                        isset($tenant->settings['pan']) ? 'PAN: ' . $tenant->settings['pan'] : null,
                    ])->filter()->join('  ·  ');
                @endphp
                @if($headerContactLine)
                    <div class="company-contact">{{ $headerContactLine }}</div>
                @endif
            </div>
        </div>

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
    </div>{{-- /page-header-fixed --}}

    {{-- ════════════════════════════════════════════
         BODY
    ════════════════════════════════════════════ --}}
    <div class="body-content">

        {{-- ── BILL TO ──
             No separate "Billed By (Seller)" box: the seller's full
             details (including GSTIN/PAN) now live in the header
             instead, so they aren't shown twice on the page. --}}
        <div class="party-box buyer party-box-single">
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

        {{-- ── ITEMS TABLE ── --}}
        <div class="section-heading">Particulars of Supply</div>
        <table class="items-table">
            <thead>
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

        {{-- ── AMOUNT IN WORDS  +  TOTALS ── --}}
        <table class="bottom-section">
            <tr>
                <td class="bank-cell">

                    @php $hasBankDetails = $showBankDetails && (isset($tenant->settings['bank_name']) || isset($tenant->settings['account_number'])); @endphp

                    <div class="amount-words">
                        <div class="block-label">Total Amount (in words)</div>
                        <div class="amount-words-text">{{ \App\Helpers\NumberToWords::convert($invoice->total) }} Only</div>
                    </div>

                    @if($invoice->quotation)
                        <div class="quotation-ref">Against Quotation: <strong>{{ $invoice->quotation->number }}</strong></div>
                    @endif

                </td>

                <td class="totals-cell">
                    <div class="totals-card">
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

                        @if($invoice->placeOfSupplyName())
                        <tr>
                            <td class="t-sub-label">Place of Supply</td>
                            <td class="t-sub-value">{{ $invoice->placeOfSupplyName() }}</td>
                        </tr>
                        @endif
                        @if($showTaxSummary)
                            @foreach($invoice->gstLines() as $line)
                            <tr>
                                <td class="{{ $loop->last ? 't-label' : 't-sub-label' }}">{{ $line['label'] }}</td>
                                <td class="{{ $loop->last ? 't-value' : 't-sub-value' }}">₹ {{ number_format($line['amount'], 2) }}</td>
                            </tr>
                            @endforeach
                            <tr>
                                <td class="t-label">Total GST</td>
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
                    </div>
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

        {{-- ── BANK DETAILS + SIGNATURE ──
             Flows right after whatever precedes it (see the CSS comment
             above .signature-section) instead of being pinned to the
             page's bottom edge. --}}
        <div class="signature-section">
            <table class="sig-table">
                <tr>
                    <td class="sig-left">
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

    </div>{{-- /body-content --}}

    {{-- ════════════════════════════════════════════
         RUNNING FOOTER — fixed, repeats on every page.
    ════════════════════════════════════════════ --}}
    <div class="footer-bar">
        <table class="footer-inner">
            <tr>
                <td class="footer-left">
                    <div class="footer-text"><strong>Invoice #{{ $invoice->number }}</strong> &nbsp;·&nbsp; Generated {{ now()->format('d M Y') }}</div>
                </td>
                <td class="footer-right"></td>
            </tr>
        </table>
    </div>

</body>
</html>
