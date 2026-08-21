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
        'status',
        'expiry_notified_at',
        'notes',
    ];

    protected $casts = [
        'starts_at'          => 'date',
        'expires_at'         => 'date',
        'duration_value'     => 'integer',
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
