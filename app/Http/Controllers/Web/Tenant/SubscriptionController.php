<?php

namespace App\Http\Controllers\Web\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\Plan;
use App\Models\PlatformSetting;
use App\Models\Subscription;
use App\Services\RazorpayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SubscriptionController extends Controller
{
    public function __construct(protected RazorpayService $razorpay) {}

    // ── Plans listing ─────────────────────────────────────────────
    public function plans(): View
    {
        $plans      = Plan::where('is_active', true)->orderBy('sort_order')->get();
        $currentSub = Auth::user()->tenant->subscription;
        $monthlyBillingEnabled = PlatformSetting::get('monthly_billing_enabled', '0') === '1';

        return view('tenant.subscription.plans', compact('plans', 'currentSub', 'monthlyBillingEnabled'));
    }

    // ── Current subscription ──────────────────────────────────────
    public function current(): View|RedirectResponse
    {
        $subscription = Auth::user()->tenant->subscription;

        if (!$subscription) {
            return redirect()->route('tenant.subscription.plans');
        }

        return view('tenant.subscription.current', compact('subscription'));
    }

    // ── Initiate checkout ─────────────────────────────────────────
    public function checkout(string $planSlug, string $cycle = 'monthly'): View|RedirectResponse
    {
        abort_if(!\in_array($cycle, ['monthly', 'yearly'], true), 404);

        $plan = Plan::where('slug', $planSlug)->where('is_active', true)->firstOrFail();

        if ($plan->monthly_price == 0) {
            return redirect()->route('tenant.subscription.plans')
                ->with('info', 'Free plan already active hai.');
        }

        $originalAmount = $cycle === 'yearly' ? (int) $plan->yearly_price : (int) $plan->monthly_price;
        $amount         = $cycle === 'yearly'
            ? (int) $plan->discountedYearlyPrice()
            : (int) $plan->discountedMonthlyPrice();

        $user   = Auth::user();
        $tenant = $user->tenant;

        try {
            $order = $this->razorpay->createOrder($amount, 'INR', [
                'tenant_id' => $tenant->id,
                'plan_slug' => $plan->slug,
                'cycle'     => $cycle,
            ]);
        } catch (\RuntimeException $e) {
            return redirect()->route('tenant.subscription.plans')
                ->with('error', 'Payment gateway is currently unavailable. Please contact support.');
        }

        Subscription::updateOrCreate(
            ['tenant_id' => $tenant->id, 'status' => 'pending_payment'],
            [
                'plan_id'           => $plan->id,
                'razorpay_order_id' => $order['id'],
                'billing_cycle'     => $cycle,
                'original_amount'   => $plan->hasDiscount() ? $originalAmount : null,
                'discount_amount'   => $plan->hasDiscount() ? ($originalAmount - $amount) : 0,
                'started_at'        => now(),
                'ends_at'           => $cycle === 'yearly' ? now()->addYear() : now()->addMonth(),
            ]
        );

        return view('tenant.subscription.checkout', [
            'plan'           => $plan,
            'cycle'          => $cycle,
            'amount'         => $amount,
            'originalAmount' => $originalAmount,
            'order'          => $order,
            'razorpayKey'    => $this->razorpay->getKeyId(),
            'tenant'         => $tenant,
            'user'           => $user,
        ]);
    }

    // ── Apply coupon (AJAX) ───────────────────────────────────────
    public function applyCoupon(Request $request): JsonResponse
    {
        $request->validate([
            'coupon_code' => 'required|string|max:50',
            'plan_slug'   => 'required|string',
            'cycle'       => 'required|in:monthly,yearly',
        ]);

        $tenant = Auth::user()->tenant;

        $subscription = Subscription::where('tenant_id', $tenant->id)
            ->where('status', 'pending_payment')
            ->latest()
            ->first();

        if (!$subscription) {
            return response()->json(['success' => false, 'message' => 'No pending checkout found. Please start again.'], 422);
        }

        $plan = $subscription->plan;

        // Base = plan-discounted price (what user actually owes before any coupon)
        $baseAmount = $subscription->billing_cycle === 'yearly'
            ? (int) $plan->discountedYearlyPrice()
            : (int) $plan->discountedMonthlyPrice();

        $userId = Auth::id();
        $coupon = Coupon::where('code', strtoupper(trim($request->coupon_code)))->first();

        if (!$coupon || !$coupon->isValid($userId)) {
            $message = match (true) {
                !$coupon                                                           => 'Invalid coupon code.',
                !$coupon->is_active                                                => 'This coupon is no longer active.',
                $coupon->expires_at && $coupon->expires_at->isPast()              => 'This coupon has expired.',
                $coupon->max_uses && $coupon->used_count >= $coupon->max_uses     => 'This coupon has reached its usage limit.',
                $coupon->applicable_to === 'specific_user'                        => 'This coupon is not valid for your account.',
                default                                                            => 'Invalid coupon code.',
            };

            return response()->json(['success' => false, 'message' => $message]);
        }

        $discountAmount = $coupon->calculateDiscount($baseAmount);
        $finalAmount    = max(0, $baseAmount - $discountAmount);

        try {
            $order = $this->razorpay->createOrder((int) $finalAmount, 'INR', [
                'tenant_id' => $tenant->id,
                'plan_slug' => $plan->slug,
                'cycle'     => $subscription->billing_cycle,
                'coupon'    => $coupon->code,
            ]);
        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => 'Payment gateway unavailable. Please contact support.'], 503);
        }

        $subscription->update([
            'razorpay_order_id' => $order['id'],
            'coupon_id'         => $coupon->id,
            'discount_amount'   => $discountAmount,
        ]);

        return response()->json([
            'success'         => true,
            'message'         => 'Coupon applied! You save ₹' . number_format($discountAmount),
            'coupon_name'     => $coupon->name,
            'discount_label'  => $coupon->discount_label,
            'original_amount' => $baseAmount,
            'discount_amount' => (int) $discountAmount,
            'final_amount'    => (int) $finalAmount,
            'order_id'        => $order['id'],
        ]);
    }

    // ── Verify payment after Razorpay callback ────────────────────
    public function verify(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'razorpay_order_id'   => 'required|string',
            'razorpay_payment_id' => 'required|string',
            'razorpay_signature'  => 'required|string',
        ]);

        if (!$this->razorpay->verifyPaymentSignature($data['razorpay_order_id'], $data['razorpay_payment_id'], $data['razorpay_signature'])) {
            return redirect()->route('tenant.subscription.plans')
                ->with('error', 'Payment verification failed. Please contact support.');
        }

        $tenant = Auth::user()->tenant;

        DB::transaction(function () use ($data, $tenant) {
            $tenant->subscriptions()
                ->whereIn('status', ['active', 'trial', 'pending_payment'])
                ->update(['status' => 'cancelled', 'cancelled_at' => now()]);

            $sub = Subscription::where('tenant_id', $tenant->id)
                ->where('razorpay_order_id', $data['razorpay_order_id'])
                ->first();

            if ($sub) {
                $sub->update([
                    'status'              => 'active',
                    'razorpay_payment_id' => $data['razorpay_payment_id'],
                    'razorpay_signature'  => $data['razorpay_signature'],
                    'started_at'          => now(),
                ]);

                if ($sub->coupon_id) {
                    Coupon::where('id', $sub->coupon_id)->increment('used_count');
                }
            }
        });

        return redirect()->route('tenant.subscription.success')
            ->with('payment_id', $data['razorpay_payment_id']);
    }

    // ── Success page ──────────────────────────────────────────────
    public function success(): View
    {
        $subscription = Auth::user()->tenant->subscription;
        return view('tenant.subscription.success', compact('subscription'));
    }

    // ── Cancel subscription ───────────────────────────────────────
    public function cancel(): RedirectResponse
    {
        $tenant       = Auth::user()->tenant;
        $subscription = $tenant->subscription;

        if (!$subscription || !$subscription->isActive()) {
            return back()->with('error', 'No active subscription to cancel.');
        }

        $subscription->update(['status' => 'cancelled', 'cancelled_at' => now()]);

        return redirect()->route('tenant.subscription.current')
            ->with('success', 'Subscription cancelled. Service available until ' . $subscription->ends_at->format('d M Y') . '.');
    }

    // ── Expired page ──────────────────────────────────────────────
    public function expired(): View
    {
        $plans = Plan::where('is_active', true)->where('monthly_price', '>', 0)->orderBy('sort_order')->get();

        return view('tenant.subscription.expired', compact('plans'));
    }
}
