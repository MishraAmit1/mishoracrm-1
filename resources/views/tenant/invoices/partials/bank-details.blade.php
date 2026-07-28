@if(isset($tenant->settings['bank_name']) || isset($tenant->settings['account_number']))
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
