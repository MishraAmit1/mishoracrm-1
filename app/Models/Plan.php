<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends TenantModel
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'monthly_price',
        'yearly_price',
        'razorpay_monthly_plan_id',
        'razorpay_yearly_plan_id',
        'features',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'monthly_price' => 'decimal:2',
        'yearly_price'  => 'decimal:2',
        'features'      => 'array',
        'is_active'     => 'boolean',
    ];

    // ── Relationships ─────────────────────────────────────────────

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function activeSubscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class)->where('status', 'active');
    }

    public function trialSubscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class)->where('status', 'trial');
    }

    // ── Scopes ────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // ── Helpers ───────────────────────────────────────────────────

    public function getFeature(string $key, $default = null)
    {
        return $this->features[$key] ?? $default;
    }

    public function hasFeature(string $key): bool
    {
        return isset($this->features[$key]) && $this->features[$key] !== false;
    }

    public function isUnlimited(string $key): bool
    {
        return ($this->features[$key] ?? null) === -1;
    }

    public function getFormattedMonthlyPriceAttribute(): string
    {
        return $this->monthly_price == 0
            ? 'Free'
            : '₹' . number_format($this->monthly_price);
    }

    public function getFormattedYearlyPriceAttribute(): string
    {
        return $this->yearly_price == 0
            ? 'Free'
            : '₹' . number_format($this->yearly_price);
    }
}