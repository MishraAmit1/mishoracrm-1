@php
    $cgst = round($invoice->tax_amount / 2, 2);
    $sgst = round($invoice->tax_amount / 2, 2);
@endphp
<table class="totals-table">
    <tr>
        <td class="t-label">Subtotal</td>
        <td class="t-value">₹ {{ number_format($invoice->subtotal, 2) }}</td>
    </tr>
    @if($invoice->discount > 0)
    <tr>
        <td class="t-label">Discount (–)</td>
        <td class="t-value" style="color:#dc2626;">– ₹ {{ number_format($invoice->discount, 2) }}</td>
    </tr>
    @endif
    <tr>
        <td class="t-label">CGST ({{ number_format($invoice->tax_percent / 2, 1) }}%)</td>
        <td class="t-value">₹ {{ number_format($cgst, 2) }}</td>
    </tr>
    <tr>
        <td class="t-label">SGST ({{ number_format($invoice->tax_percent / 2, 1) }}%)</td>
        <td class="t-value">₹ {{ number_format($sgst, 2) }}</td>
    </tr>
    <tr class="total-final-row">
        <td>Grand Total</td>
        <td class="t-value" style="color:#fff;">₹ {{ number_format($invoice->total, 2) }}</td>
    </tr>
    @if($invoice->paid_amount > 0)
    <tr>
        <td class="t-label">Amount Received</td>
        <td class="t-value" style="color:#15803d;">₹ {{ number_format($invoice->paid_amount, 2) }}</td>
    </tr>
    <tr>
        <td class="t-label">Balance Due</td>
        <td class="t-value" style="color:#991b1b;">₹ {{ number_format($invoice->due_amount, 2) }}</td>
    </tr>
    @endif
</table>
