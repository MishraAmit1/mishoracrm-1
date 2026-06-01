@extends('layouts.app')
@section('title', 'Checkout — ' . $plan->name)

@push('styles')
<style>
.checkout-wrap {
    max-width: 520px;
    margin: 48px auto;
}
.checkout-card {
    background: var(--bg-card);
    border: 1px solid var(--border-subtle);
    border-radius: var(--r-lg);
    overflow: hidden;
}
.checkout-header {
    padding: 24px 28px;
    border-bottom: 1px solid var(--border-subtle);
}
.checkout-header h2 {
    font-size: 20px;
    font-weight: 700;
    color: var(--text-100);
    margin-bottom: 4px;
}
.checkout-header p { font-size: 13.5px; color: var(--text-400); }
.checkout-body { padding: 28px; }
.summary-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 0;
    border-bottom: 1px solid var(--border-subtle);
    font-size: 14px;
}
.summary-row:last-child { border-bottom: none; }
.summary-row .label { color: var(--text-400); }
.summary-row .value { color: var(--text-100); font-weight: 600; }
.summary-row.total .value { font-size: 20px; font-weight: 800; color: var(--accent); }
.pay-btn {
    display: block;
    width: 100%;
    padding: 14px;
    background: var(--accent);
    color: #fff;
    border: none;
    border-radius: var(--r-md);
    font-size: 15px;
    font-weight: 700;
    cursor: pointer;
    margin-top: 24px;
    transition: opacity 0.2s;
}
.pay-btn:hover { opacity: 0.9; }
.secure-note {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    margin-top: 14px;
    font-size: 12px;
    color: var(--text-400);
}
.back-link {
    display: flex;
    align-items: center;
    gap: 6px;
    color: var(--text-400);
    font-size: 13.5px;
    text-decoration: none;
    margin-bottom: 20px;
}
.back-link:hover { color: var(--text-100); }
.coupon-section {
    padding: 16px 0 0;
    border-top: 1px solid var(--border-subtle);
    margin-top: 8px;
}
.coupon-label {
    font-size: 13px;
    font-weight: 600;
    color: var(--text-300);
    margin-bottom: 8px;
}
.coupon-row {
    display: flex;
    gap: 8px;
}
.coupon-input {
    flex: 1;
    padding: 9px 12px;
    background: var(--bg-input);
    border: 1px solid var(--border-subtle);
    border-radius: var(--r-md);
    color: var(--text-100);
    font-size: 13.5px;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    outline: none;
}
.coupon-input:focus { border-color: var(--accent); }
.coupon-btn {
    padding: 9px 16px;
    background: var(--bg-card);
    border: 1px solid var(--border-subtle);
    border-radius: var(--r-md);
    font-size: 13px;
    font-weight: 600;
    color: var(--text-200);
    cursor: pointer;
    transition: background 0.15s;
    white-space: nowrap;
}
.coupon-btn:hover { background: var(--bg-hover); }
.coupon-btn:disabled { opacity: 0.5; cursor: default; }
.coupon-msg {
    font-size: 12.5px;
    margin-top: 7px;
    display: none;
}
.coupon-msg.success { color: #16a34a; }
.coupon-msg.error   { color: var(--red, #ef4444); }
.summary-row.discount .value { color: #16a34a; }
.summary-row.strikethrough .value {
    text-decoration: line-through;
    color: var(--text-400);
    font-weight: 400;
}
.plan-discount-badge {
    display: inline-block;
    background: #dcfce7;
    color: #16a34a;
    font-size: 11px;
    font-weight: 700;
    padding: 2px 7px;
    border-radius: 20px;
    margin-left: 6px;
    vertical-align: middle;
    letter-spacing: 0.03em;
}
</style>
@endpush

@section('content')
<div class="page-content">
<div class="checkout-wrap">

    <a href="{{ route('tenant.subscription.plans') }}" class="back-link">
        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
        Back to Plans
    </a>

    <div class="checkout-card">
        <div class="checkout-header">
            <h2>Complete Your Purchase</h2>
            <p>Secure payment powered by Razorpay</p>
        </div>
        <div class="checkout-body">
            <div class="summary-row">
                <span class="label">Plan</span>
                <span class="value">{{ $plan->name }}</span>
            </div>
            <div class="summary-row">
                <span class="label">Billing Cycle</span>
                <span class="value">{{ ucfirst($cycle) }}</span>
            </div>
            <div class="summary-row">
                <span class="label">Duration</span>
                <span class="value">{{ $cycle === 'yearly' ? '12 months' : '1 month' }}</span>
            </div>
            @if($originalAmount > $amount)
            <div class="summary-row strikethrough">
                <span class="label">Original Price</span>
                <span class="value">₹{{ number_format($originalAmount) }}</span>
            </div>
            <div class="summary-row discount">
                <span class="label">
                    Plan Discount
                    <span class="plan-discount-badge">{{ $plan->discount_percentage }}% OFF</span>
                </span>
                <span class="value">− ₹{{ number_format($originalAmount - $amount) }}</span>
            </div>
            @endif
            <div class="summary-row discount" id="row-discount" style="display:none">
                <span class="label">Discount (<span id="discount-label"></span>)</span>
                <span class="value">− ₹<span id="discount-amount">0</span></span>
            </div>
            <div class="summary-row total">
                <span class="label">Total Amount</span>
                <span class="value">₹<span id="total-display">{{ number_format($amount) }}</span></span>
            </div>

            {{-- Coupon section --}}
            <div class="coupon-section">
                <div class="coupon-label">Have a coupon code?</div>
                <div class="coupon-row">
                    <input type="text" id="coupon-input" class="coupon-input" placeholder="ENTER CODE" maxlength="50">
                    <button type="button" id="coupon-apply-btn" class="coupon-btn">Apply</button>
                </div>
                <div class="coupon-msg" id="coupon-msg"></div>
            </div>

            <button class="pay-btn" id="rzp-pay-btn">
                Pay ₹<span id="btn-amount">{{ number_format($amount) }}</span> with Razorpay
            </button>

            {{-- Hidden form to POST payment details to verify route --}}
            <form id="payment-form" action="{{ route('tenant.subscription.verify') }}" method="POST" style="display:none">
                @csrf
                <input type="hidden" name="razorpay_order_id"   id="razorpay_order_id">
                <input type="hidden" name="razorpay_payment_id" id="razorpay_payment_id">
                <input type="hidden" name="razorpay_signature"  id="razorpay_signature">
            </form>

            <div class="secure-note">
                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"/>
                </svg>
                256-bit SSL encrypted &amp; secured by Razorpay
            </div>
        </div>
    </div>
</div>
</div>
@endsection

@push('scripts')
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script>
let currentAmount  = {{ $amount }};
let currentOrderId = "{{ $order['id'] }}";

function buildRzpOptions() {
    return {
        key: "{{ $razorpayKey }}",
        amount: currentAmount * 100,
        currency: "INR",
        name: "{{ config('app.name') }}",
        description: "{{ $plan->name }} — {{ ucfirst($cycle) }} Plan",
        order_id: currentOrderId,
        prefill: {
            name:    "{{ $user->name }}",
            email:   "{{ $user->email }}",
            contact: "{{ $user->phone ?? '' }}"
        },
        notes: { plan: "{{ $plan->name }}", cycle: "{{ $cycle }}" },
        theme: { color: "#6366f1" },
        handler: function (response) {
            document.getElementById('razorpay_order_id').value   = response.razorpay_order_id;
            document.getElementById('razorpay_payment_id').value = response.razorpay_payment_id;
            document.getElementById('razorpay_signature').value  = response.razorpay_signature;
            document.getElementById('payment-form').submit();
        }
    };
}

document.getElementById('rzp-pay-btn').onclick = function (e) {
    e.preventDefault();
    const rzp = new Razorpay(buildRzpOptions());
    rzp.on('payment.failed', function (r) {
        alert('Payment failed: ' + r.error.description);
    });
    rzp.open();
};

// ── Coupon AJAX ──────────────────────────────────────────────────
document.getElementById('coupon-apply-btn').addEventListener('click', function () {
    const code    = document.getElementById('coupon-input').value.trim();
    const msgEl   = document.getElementById('coupon-msg');
    const btn     = this;

    if (!code) {
        showCouponMsg('Please enter a coupon code.', false);
        return;
    }

    btn.disabled   = true;
    btn.textContent = 'Applying…';
    msgEl.style.display = 'none';

    fetch("{{ route('tenant.subscription.apply-coupon') }}", {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                         || "{{ csrf_token() }}"
        },
        body: JSON.stringify({
            coupon_code: code,
            plan_slug:   "{{ $plan->slug }}",
            cycle:       "{{ $cycle }}"
        })
    })
    .then(r => r.json())
    .then(data => {
        btn.disabled    = false;
        btn.textContent = 'Apply';

        if (data.success) {
            currentAmount  = data.final_amount;
            currentOrderId = data.order_id;

            document.getElementById('discount-label').textContent  = data.discount_label;
            document.getElementById('discount-amount').textContent = Number(data.discount_amount).toLocaleString('en-IN');
            document.getElementById('total-display').textContent   = Number(data.final_amount).toLocaleString('en-IN');
            document.getElementById('btn-amount').textContent      = Number(data.final_amount).toLocaleString('en-IN');
            document.getElementById('row-discount').style.display  = '';

            showCouponMsg('✓ ' + data.message, true);
            document.getElementById('coupon-input').disabled       = true;
            btn.disabled = true;
            btn.textContent = 'Applied';
        } else {
            showCouponMsg(data.message, false);
        }
    })
    .catch(() => {
        btn.disabled    = false;
        btn.textContent = 'Apply';
        showCouponMsg('Something went wrong. Please try again.', false);
    });
});

function showCouponMsg(msg, success) {
    const el = document.getElementById('coupon-msg');
    el.textContent    = msg;
    el.className      = 'coupon-msg ' + (success ? 'success' : 'error');
    el.style.display  = 'block';
}

document.getElementById('coupon-input').addEventListener('keydown', function (e) {
    if (e.key === 'Enter') document.getElementById('coupon-apply-btn').click();
});
</script>
@endpush
