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
    ];

    protected $casts = [
        'trial_ends_at' => 'datetime',
        'started_at'    => 'datetime',
        'ends_at'       => 'datetime',
        'cancelled_at'  => 'datetime',
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

    public function isActive(): bool
    {
        return $this->status === 'active' && $this->ends_at?->isFuture();
    }

    public function isTrial(): bool
    {
        return $this->status === 'trial' && $this->trial_ends_at?->isFuture();
    }

    public function isExpired(): bool
    {
        if ($this->status === 'expired') return true;
        if ($this->status === 'cancelled') return true;

        // ends_at past ho gayi
        if ($this->ends_at && $this->ends_at->isPast()) return true;

        // Trial khatam ho gayi
        if ($this->status === 'trial' && $this->trial_ends_at?->isPast()) return true;

        return false;
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
}