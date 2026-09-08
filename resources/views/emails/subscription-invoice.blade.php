@php
    $seller  = $inv['seller'];
    $accent  = $inv['accent_color'] ?? '#4f46e5';
    $primary = $inv['primary_color'] ?? '#1e293b';
    $cur     = $inv['currency'] ?? '₹';
@endphp
<div style="max-width:560px;margin:0 auto;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Arial,sans-serif;color:#1e293b">

  <div style="background:{{ $primary }};padding:26px 28px;border-radius:10px 10px 0 0">
    <div style="color:#fff;font-size:19px;font-weight:700;letter-spacing:.2px">{{ $seller['name'] }}</div>
    <div style="color:rgba(255,255,255,.72);font-size:12.5px;margin-top:3px">Payment receipt &amp; tax invoice</div>
  </div>

  <div style="background:#fff;padding:28px;border:1px solid #e2e8f0;border-top:none;border-radius:0 0 10px 10px">

    <p style="margin:0 0 4px;font-size:15px">Hi {{ $inv['buyer']['name'] }},</p>
    <p style="margin:0 0 20px;font-size:14px;line-height:1.65;color:#475569">
      Thank you for your subscription. Your payment has been received and your
      <strong>{{ $inv['line']['title'] }}</strong> is active. Your tax invoice is attached as a PDF.
    </p>

    <table style="width:100%;border-collapse:collapse;font-size:13.5px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px">
      <tr>
        <td style="padding:12px 16px;color:#64748b">Invoice No.</td>
        <td style="padding:12px 16px;text-align:right;font-weight:700">{{ $inv['invoice_number'] }}</td>
      </tr>
      <tr>
        <td style="padding:12px 16px;color:#64748b;border-top:1px solid #e2e8f0">Invoice Date</td>
        <td style="padding:12px 16px;text-align:right;font-weight:700;border-top:1px solid #e2e8f0">{{ $inv['issued_at']->format('d M Y') }}</td>
      </tr>
      <tr>
        <td style="padding:12px 16px;color:#64748b;border-top:1px solid #e2e8f0">Plan</td>
        <td style="padding:12px 16px;text-align:right;font-weight:700;border-top:1px solid #e2e8f0">{{ $inv['line']['title'] }} ({{ ucfirst($inv['billing_cycle']) }})</td>
      </tr>
      @if($inv['period_end'])
      <tr>
        <td style="padding:12px 16px;color:#64748b;border-top:1px solid #e2e8f0">Valid Until</td>
        <td style="padding:12px 16px;text-align:right;font-weight:700;border-top:1px solid #e2e8f0">{{ $inv['period_end']->format('d M Y') }}</td>
      </tr>
      @endif
      <tr>
        <td style="padding:14px 16px;color:{{ $primary }};border-top:2px solid #e2e8f0;font-weight:700">Amount Paid</td>
        <td style="padding:14px 16px;text-align:right;border-top:2px solid #e2e8f0;font-weight:800;font-size:16px;color:{{ $primary }}">{{ $cur }}{{ number_format($inv['total_amount'], 2) }}</td>
      </tr>
    </table>

    @if($inv['payment']['reference'])
    <p style="margin:14px 0 0;font-size:12.5px;color:#64748b">
      Payment reference: <span style="font-family:'SF Mono',Menlo,monospace;color:#334155">{{ $inv['payment']['reference'] }}</span>
    </p>
    @endif

    <p style="margin:22px 0 0;font-size:13px;line-height:1.6;color:#94a3b8;border-top:1px solid #f1f5f9;padding-top:16px">
      This is a system-generated invoice from {{ $seller['name'] }}@if($seller['gstin']), GSTIN {{ $seller['gstin'] }}@endif.
      @if($seller['email']) For any billing question, reply to this email or write to {{ $seller['email'] }}.@endif
    </p>

  </div>
</div>
