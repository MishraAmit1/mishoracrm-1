<?php

namespace App\Services;

use Razorpay\Api\Api;
use Razorpay\Api\Errors\SignatureVerificationError;

class RazorpayService
{
    protected Api $api;

    public function __construct()
    {
        if (!class_exists(Api::class)) {
            throw new \RuntimeException(
                'Razorpay SDK not found. Run: composer install --no-dev --optimize-autoloader'
            );
        }

        $keyId     = config('services.razorpay.key_id');
        $keySecret = config('services.razorpay.key_secret');

        if (empty($keyId) || empty($keySecret)) {
            throw new \RuntimeException(
                'Razorpay credentials missing. Set RAZORPAY_KEY_ID and RAZORPAY_KEY_SECRET in .env'
            );
        }

        $this->api = new Api($keyId, $keySecret);
    }

    /**
     * Create a Razorpay Order for subscription payment.
     * Amount is in INR (will be converted to paise internally).
     */
    public function createOrder(int $amountInRupees, string $currency = 'INR', array $notes = []): array
    {
        $order = $this->api->order->create([
            'amount'   => $amountInRupees * 100, // paise
            'currency' => $currency,
            'notes'    => $notes,
        ]);

        return $order->toArray();
    }

    /**
     * Verify Razorpay payment signature after checkout.
     */
    public function verifyPaymentSignature(string $orderId, string $paymentId, string $signature): bool
    {
        try {
            $this->api->utility->verifyPaymentSignature([
                'razorpay_order_id'   => $orderId,
                'razorpay_payment_id' => $paymentId,
                'razorpay_signature'  => $signature,
            ]);
            return true;
        } catch (SignatureVerificationError $e) {
            return false;
        }
    }

    /**
     * Verify Razorpay webhook signature.
     */
    public function verifyWebhookSignature(string $payload, string $signature): bool
    {
        try {
            $this->api->utility->verifyWebhookSignature(
                $payload,
                $signature,
                config('services.razorpay.webhook_secret')
            );
            return true;
        } catch (SignatureVerificationError $e) {
            return false;
        }
    }

    /**
     * Fetch payment details from Razorpay.
     */
    public function fetchPayment(string $paymentId): array
    {
        return $this->api->payment->fetch($paymentId)->toArray();
    }

    public function getKeyId(): string
    {
        return config('services.razorpay.key_id');
    }
}
