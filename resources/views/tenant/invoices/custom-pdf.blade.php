<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Tax Invoice – {{ $invoice->number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        @page { margin: 0 0 65px 0; }

        body {
            font-family: "{{ $fontFamily }}", "DejaVu Sans", sans-serif;
            font-size: 12px;
            color: #1e293b;
            line-height: 1.6;
            padding: 28px 32px 0 32px;
        }

        p { margin: 6px 0; }
        h1, h2, h3 { color: {{ $primaryColor }}; margin: 10px 0; }
        img { max-width: 100%; }

        /* ── Items table ──
             Forced to DejaVu Sans regardless of the tenant's chosen body font:
             dompdf does not fall back per-glyph, so a Base-14 font like
             Helvetica/Times/Courier silently drops the ₹ (U+20B9) glyph it
             doesn't contain. DejaVu Sans has it in both weights. */
        .items-table { width: 100%; border-collapse: collapse; margin: 12px 0; font-family: "DejaVu Sans", sans-serif; }
        .items-table thead { display: table-header-group; }
        .items-table tbody { display: table-row-group; }
        .items-table thead tr { background: {{ $primaryColor }}; }
        .items-table thead th {
            padding: 10px; font-size: 10px; font-weight: bold;
            text-transform: uppercase; letter-spacing: .5px; color: #fff; text-align: left;
        }
        .items-table thead th.r { text-align: right; }
        .items-table thead th.c { text-align: center; }
        .items-table tbody tr { border-bottom: 1px solid #f1f5f9; page-break-inside: avoid; }
        .items-table tbody tr:nth-child(even) { background: #f8fafc; }
        .items-table tbody td { padding: 9px 10px; font-size: 11px; vertical-align: top; }
        .items-table tbody td.r { text-align: right; }
        .items-table tbody td.c { text-align: center; }

        /* ── Totals ── (forced to DejaVu Sans — see items-table comment above) */
        .totals-table { width: 100%; max-width: 320px; border-collapse: collapse; margin: 12px 0; float: right; font-family: "DejaVu Sans", sans-serif; }
        .totals-table td { padding: 7px 12px; font-size: 12px; }
        .totals-table .t-label { color: #64748b; }
        .totals-table .t-value { text-align: right; font-weight: 600; }
        .totals-table .total-final-row td { background: {{ $accentColor }}; font-size: 14px; font-weight: bold; }

        /* ── Bank details ── */
        .bank-box {
            border: 1px solid #e2e8f0; border-left: 4px solid {{ $primaryColor }};
            padding: 12px 14px; background: #f8fafc; page-break-inside: avoid; margin: 12px 0;
        }
        .bank-title { font-size: 10px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; color: {{ $primaryColor }}; margin-bottom: 8px; }
        .bank-row { font-size: 11px; color: #475569; padding: 2px 0; }
        .bank-row span { font-weight: bold; color: #1e293b; min-width: 100px; display: inline-block; }

        /* ── Signature ── */
        .signature-section { width: 100%; margin-top: 24px; border-top: 1px solid #e2e8f0; padding-top: 16px; page-break-inside: avoid; }
        .sig-box { border: 1px solid #e2e8f0; padding: 12px 20px; display: inline-block; text-align: center; min-width: 200px; float: right; }
        .sig-space { height: 48px; border-bottom: 1px solid #cbd5e1; margin: 8px 0; }
    </style>
</head>
<body>
{!! $renderedHtml !!}
</body>
</html>
