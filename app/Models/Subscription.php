<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Coupon;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subscription extends Model
{
    protected $fillable = [
        'tenant_id',
        'plan_id',
        'coupon_id',
        'original_amount',
        'discount_amount',
        'gst_percentage',
        'gst_amount',
        'total_amount',
        'razorpay_subscription_id',
        'razorpay_order_id',
        'razorpay_payment_id',
        'razorpay_signature',
        'status',
        'billing_cycle',
        'trial_ends_at',
        'started_at',
        'ends_at',
        'cancelled_at',
        'renewal_reminder_sent_at',
        'invoice_number',
        'invoice_issued_at',
        'invoice_delivery',
    ];

    protected $casts = [
        'trial_ends_at'            => 'datetime',
        'started_at'               => 'datetime',
        'ends_at'                  => 'datetime',
        'cancelled_at'             => 'datetime',
        'renewal_reminder_sent_at' => 'datetime',
        'invoice_issued_at'        => 'datetime',
        'invoice_delivery'         => 'array',
    ];

    // ── Relationships ─────────────────────────────────────────────

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    // ── Scopes ────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeTrial($query)
    {
        return $query->where('status', 'trial');
    }

    public function scopeExpired($query)
    {
        return $query->where('status', 'expired');
    }

    public function scopeExpiringSoon($query, int $days = 7)
    {
        return $query->where('status', 'active')
                     ->whereBetween('ends_at', [now(), now()->addDays($days)]);
    }

    // ── Helpers ───────────────────────────────────────────────────

    // Free plan (₹0) — never billed, so never locked out. `ends_at` on a
    // free subscription is informational only.
    public function isFree(): bool
    {
        return $this->plan && (float) $this->plan->monthly_price === 0.0;
    }

    public function isActive(): bool
    {
        if ($this->status !== 'active') return false;

        return $this->isFree() || $this->ends_at === null || $this->ends_at->isFuture();
    }

    public function isTrial(): bool
    {
        return $this->status === 'trial' && $this->trial_ends_at?->isFuture();
    }

    // Statuses that represent an unpaid checkout attempt, never an entitlement.
    public const UNPAID_STATUSES = ['pending_payment', 'past_due'];

    // Allow-list: only a live active / trial / paid-cancelled term grants
    // access. Any other status (expired, pending_payment, past_due, unknown)
    // is locked out — fail closed.
    public function isExpired(): bool
    {
        // Free plan tenants are never locked out.
        if ($this->isFree()) return false;

        switch ($this->status) {
            case 'active':
                return $this->ends_at !== null && $this->ends_at->isPast();

            case 'trial':
                $deadline = $this->trial_ends_at ?? $this->ends_at;
                return !$deadline || $deadline->isPast()
                    || ($this->ends_at && $this->ends_at->isPast());

            // Cancelled still has access until the paid term actually runs out.
            case 'cancelled':
                return !$this->ends_at || $this->ends_at->isPast();

            default:
                return true;
        }
    }

    public function daysLeft(): int
    {
        if (!$this->ends_at) return 0;
        return max(0, (int) now()->diffInDays($this->ends_at, false));
    }

    public function trialDaysLeft(): int
    {
        if (!$this->trial_ends_at) return 0;
        return max(0, (int) now()->diffInDays($this->trial_ends_at, false));
    }

    public function getMrrAttribute(): float
    {
        if (!$this->plan) return 0;

        return $this->billing_cycle === 'yearly'
            ? round($this->plan->yearly_price / 12, 2)
            : (float) $this->plan->monthly_price;
    }

    // ── Tax invoice ───────────────────────────────────────────────

    // A paid subscription term the tenant can pull a tax invoice for.
    // Free plans, trials and not-yet-paid checkouts never qualify. Any
    // activated paid term does — even a legacy row with no stored amount,
    // since the amount is reconstructed from the plan price at render time.
    public function isInvoiceable(): bool
    {
        if ($this->isFree() || $this->isTrial() || $this->status === 'pending_payment') {
            return false;
        }

        return \in_array($this->status, ['active', 'cancelled', 'expired', 'past_due'], true)
            || $this->razorpay_payment_id !== null
            || (float) $this->total_amount > 0
            || (float) $this->original_amount > 0;
    }

    public function hasInvoice(): bool
    {
        return $this->invoice_number !== null;
    }
}