<div class="signature-section">
    <div class="sig-box">
        <div style="font-size:10px;font-weight:bold;text-transform:uppercase;letter-spacing:1px;color:#94a3b8;">For {{ $tenant->name }}</div>
        <div class="sig-space"></div>
        <div style="font-size:12px;font-weight:bold;">Authorised Signatory</div>
        @if($invoice->createdBy)
            <div style="font-size:10px;color:#94a3b8;">{{ $invoice->createdBy->name }}</div>
        @endif
    </div>
</div>
