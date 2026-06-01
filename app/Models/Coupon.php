<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Coupon extends Model
{
    protected $fillable = [
        'code',
        'name',
        'description',
        'type',
        'discount_value',
        'max_discount',
        'applicable_to',
        'tenant_id',
        'user_id',
        'max_uses',
        'used_count',
        'expires_at',
        'is_active',
    ];

    protected $casts = [
        'discount_value' => 'decimal:2',
        'max_discount'   => 'decimal:2',
        'is_active'      => 'boolean',
        'expires_at'     => 'datetime',
        'used_count'     => 'integer',
        'max_uses'       => 'integer',
    ];

    // ── Relationships ────────────────────────────────────────────

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'coupon_users')->withTimestamps();
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    // ── Scopes ───────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // ── Helpers ──────────────────────────────────────────────────

    public function isValid(?int $userId = null): bool
    {
        if (!$this->is_active) return false;
        if ($this->expires_at && $this->expires_at->isPast()) return false;
        if ($this->max_uses && $this->used_count >= $this->max_uses) return false;
        if ($this->applicable_to === 'specific_user') {
            if (!$userId) return false;
            return $this->users()->where('user_id', $userId)->exists();
        }

        return true;
    }

    public function calculateDiscount(float $amount): float
    {
        if ($this->type === 'percentage') {
            $discount = round($amount * $this->discount_value / 100, 2);
            if ($this->max_discount) {
                $discount = min($discount, (float) $this->max_discount);
            }
        } else {
            $discount = min((float) $this->discount_value, $amount);
        }

        return $discount;
    }

    public function getDiscountLabelAttribute(): string
    {
        return $this->type === 'percentage'
            ? $this->discount_value . '% off'
            : '₹' . number_format($this->discount_value) . ' off';
    }
}
