<?php

namespace App\Models;

use App\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class Appointment extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'contact_id',
        'service_id',
        'created_by',
        'starts_at',
        'ends_at',
        'status',
        'source',
        'public_token',
        'notes',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at'   => 'datetime',
    ];

    public static function statuses(): array
    {
        return [
            'booked'    => 'Booked',
            'confirmed' => 'Confirmed',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
            'no_show'   => 'No-show',
        ];
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    // ── Scopes ────────────────────────────────────────────────────

    // Counts toward slot capacity / clutters the calendar less once
    // cancelled or no-show — everything else is "active".
    public function scopeActive($query)
    {
        return $query->whereNotIn('status', ['cancelled', 'no_show']);
    }

    public function scopeUpcoming($query)
    {
        return $query->active()->where('starts_at', '>=', now());
    }

    public function scopeToday($query)
    {
        return $query->whereDate('starts_at', now()->toDateString());
    }

    public function scopeCancelled($query)
    {
        return $query->whereIn('status', ['cancelled', 'no_show']);
    }

    // ── Public link (mirrors Quotation::ensurePublicToken/publicUrl) ───

    public function ensurePublicToken(): string
    {
        if (!$this->public_token) {
            $this->update(['public_token' => Str::random(48)]);
        }

        return $this->public_token;
    }

    public function publicUrl(): string
    {
        return route('public.booking.appointment', $this->ensurePublicToken());
    }

    // ── Slot availability ────────────────────────────────────────────
    // Generates bookable start times for one calendar date, honoring the
    // tenant's configured business hours / slot length / advance window,
    // and excludes any slot already at capacity.
    public static function availableSlots(Tenant $tenant, Carbon $date, int $durationMinutes): array
    {
        $settings = $tenant->bookingSettings();

        if (!$settings['enabled']) {
            return [];
        }

        if ($date->startOfDay()->gt(now()->addDays($settings['advance_booking_days'])->endOfDay())) {
            return [];
        }

        $weekday = strtolower($date->format('D')); // mon, tue, ...
        $hours   = $settings['hours'][$weekday] ?? ['closed' => true];

        if (!empty($hours['closed'])) {
            return [];
        }

        $slotMinutes = max(5, (int) $settings['slot_duration_minutes']);
        $capacity    = max(1, (int) $settings['capacity_per_slot']);

        $open  = Carbon::parse($date->toDateString() . ' ' . $hours['open']);
        $close = Carbon::parse($date->toDateString() . ' ' . $hours['close']);

        $slots = [];
        $cursor = $open->copy();

        while ($cursor->copy()->addMinutes($durationMinutes)->lte($close)) {
            $slotStart = $cursor->copy();
            $slotEnd   = $cursor->copy()->addMinutes($durationMinutes);

            $isPast = $slotStart->lt(now());

            if (!$isPast) {
                $booked = static::where('tenant_id', $tenant->id)
                    ->active()
                    ->where('starts_at', '<', $slotEnd)
                    ->where('ends_at', '>', $slotStart)
                    ->count();

                if ($booked < $capacity) {
                    $slots[] = $slotStart->format('H:i');
                }
            }

            $cursor->addMinutes($slotMinutes);
        }

        return $slots;
    }
}
