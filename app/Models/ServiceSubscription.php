<?php

namespace App\Models;

use App\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceSubscription extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'contact_id',
        'service_id',
        'invoice_id',
        'starts_at',
        'expires_at',
        'duration_value',
        'duration_unit',
        'total_quantity',
        'used_quantity',
        'status',
        'auto_renew',
        'expiry_notified_at',
        'notes',
    ];

    protected $casts = [
        'starts_at'          => 'date',
        'expires_at'         => 'date',
        'duration_value'     => 'integer',
        'total_quantity'     => 'integer',
        'used_quantity'      => 'integer',
        'auto_renew'         => 'boolean',
        'expiry_notified_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    // ── Scopes ────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    // Active AND not yet lapsed (or no expiry set at all).
    public function scopeCurrentlyValid($query)
    {
        return $query->active()
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhereDate('expires_at', '>=', now()->toDateString()));
    }

    // Active AND lapsed — computed from the date, no stored "expired" status.
    public function scopeExpired($query)
    {
        return $query->active()
            ->whereNotNull('expires_at')
            ->whereDate('expires_at', '<', now()->toDateString());
    }

    public function scopeExpiringSoon($query, int $days = 7)
    {
        return $query->active()
            ->whereNotNull('expires_at')
            ->whereDate('expires_at', '>=', now()->toDateString())
            ->whereDate('expires_at', '<=', now()->addDays($days)->toDateString());
    }

    // Active, marked auto-renew, and reached (or passed) its expiry date —
    // what AutoRenewServiceSubscriptions processes each run.
    public function scopeDueForAutoRenewal($query)
    {
        return $query->active()
            ->where('auto_renew', true)
            ->whereNotNull('expires_at')
            ->whereDate('expires_at', '<=', now()->toDateString());
    }

    // ── Helpers ───────────────────────────────────────────────────

    public function daysUntilExpiry(): ?int
    {
        if (!$this->expires_at) {
            return null;
        }

        return (int) now()->startOfDay()->diffInDays($this->expires_at->startOfDay(), false);
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->lt(now()->startOfDay());
    }

    // ── Usage tracking (quantity-limited services, e.g. "10 sessions") ──

    public function hasQuantityTracking(): bool
    {
        return $this->total_quantity !== null;
    }

    public function remainingQuantity(): ?int
    {
        return $this->hasQuantityTracking()
            ? max(0, $this->total_quantity - $this->used_quantity)
            : null;
    }

    public function isFullyUsed(): bool
    {
        return $this->hasQuantityTracking() && $this->used_quantity >= $this->total_quantity;
    }

    // Increments used_quantity by $count, capped at total_quantity so it
    // never overshoots even if clicked after the last unit was used.
    public function markUsed(int $count = 1): void
    {
        if (!$this->hasQuantityTracking()) {
            return;
        }

        $this->update(['used_quantity' => min($this->total_quantity, $this->used_quantity + $count)]);
    }

    // Computes an expiry date from a start date + duration_value/duration_unit.
    // Falls back to the Service's billing_cycle when no explicit duration is set.
    public static function computeExpiry(\DateTimeInterface $start, ?int $durationValue, ?string $durationUnit, ?string $billingCycle = null): ?\Carbon\Carbon
    {
        $start = \Carbon\Carbon::parse($start);

        if ($durationValue && $durationUnit) {
            return $durationUnit === 'days'
                ? $start->copy()->addDays($durationValue)
                : $start->copy()->addMonths($durationValue);
        }

        return match ($billingCycle) {
            'monthly'   => $start->copy()->addMonth(),
            'quarterly' => $start->copy()->addMonths(3),
            'yearly'    => $start->copy()->addYear(),
            default     => null,
        };
    }
}
