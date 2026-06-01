<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Services\RazorpayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SubscriptionWebhookController extends Controller
{
    public function __construct(protected RazorpayService $razorpay) {}

    public function handle(Request $request)
    {
        $payload   = $request->getContent();
        $signature = $request->header('X-Razorpay-Signature', '');

        if (!$this->razorpay->verifyWebhookSignature($payload, $signature)) {
            Log::warning('Razorpay webhook: invalid signature');
            return response()->json(['status' => 'invalid signature'], 400);
        }

        $event = $request->input('event');
        $data  = $request->input('payload.payment.entity', []);

        Log::info("Razorpay webhook received: {$event}", ['order_id' => $data['order_id'] ?? null]);

        match ($event) {
            'payment.captured'  => $this->onPaymentCaptured($data),
            'payment.failed'    => $this->onPaymentFailed($data),
            'subscription.activated' => $this->onSubscriptionActivated($request->input('payload.subscription.entity', [])),
            'subscription.cancelled' => $this->onSubscriptionCancelled($request->input('payload.subscription.entity', [])),
            default             => null,
        };

        return response()->json(['status' => 'ok']);
    }

    private function onPaymentCaptured(array $payment): void
    {
        $orderId = $payment['order_id'] ?? null;
        if (!$orderId) return;

        Subscription::where('razorpay_order_id', $orderId)
            ->whereIn('status', ['pending_payment', 'past_due'])
            ->update([
                'status'              => 'active',
                'razorpay_payment_id' => $payment['id'] ?? null,
                'started_at'          => now(),
            ]);
    }

    private function onPaymentFailed(array $payment): void
    {
        $orderId = $payment['order_id'] ?? null;
        if (!$orderId) return;

        Subscription::where('razorpay_order_id', $orderId)
            ->where('status', 'pending_payment')
            ->update(['status' => 'past_due']);
    }

    private function onSubscriptionActivated(array $sub): void
    {
        $razorpaySubId = $sub['id'] ?? null;
        if (!$razorpaySubId) return;

        Subscription::where('razorpay_subscription_id', $razorpaySubId)
            ->update(['status' => 'active']);
    }

    private function onSubscriptionCancelled(array $sub): void
    {
        $razorpaySubId = $sub['id'] ?? null;
        if (!$razorpaySubId) return;

        Subscription::where('razorpay_subscription_id', $razorpaySubId)
            ->update(['status' => 'cancelled', 'cancelled_at' => now()]);
    }
}
