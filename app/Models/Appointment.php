<?php

namespace App\Models;

use App\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
        'assigned_to',
        'starts_at',
        'ends_at',
        'status',
        'source',
        'public_token',
        'notes',
        'reminder_sent_at',
        'service_address',
        'work_started_at',
        'work_started_lat',
        'work_started_lng',
        'work_completed_at',
        'work_completed_lat',
        'work_completed_lng',
        'materials_used',
        'customer_signature',
        'customer_signed_name',
        'customer_signed_at',
        'invoice_id',
    ];

    protected $casts = [
        'starts_at'            => 'datetime',
        'ends_at'              => 'datetime',
        'reminder_sent_at'     => 'datetime',
        'work_started_at'      => 'datetime',
        'work_started_lat'     => 'decimal:7',
        'work_started_lng'     => 'decimal:7',
        'work_completed_at'    => 'datetime',
        'work_completed_lat'   => 'decimal:7',
        'work_completed_lng'   => 'decimal:7',
        'materials_used'       => 'array',
        'customer_signed_at'   => 'datetime',
    ];

    // pending sign-off is not a separate DB status — a completed job
    // without a customer_signed_at is simply "awaiting sign-off" in the UI.
    public static function statuses(): array
    {
        return [
            'booked'      => 'Booked',
            'confirmed'   => 'Confirmed',
            'in_progress' => 'In Progress',
            'completed'   => 'Completed',
            'cancelled'   => 'Cancelled',
            'no_show'     => 'No-show',
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

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(AppointmentAttachment::class)->latest();
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    // ── Job workflow helpers ─────────────────────────────────────────

    public function isInProgress(): bool
    {
        return $this->status === 'in_progress';
    }

    public function canStartWork(): bool
    {
        return in_array($this->status, ['booked', 'confirmed'], true);
    }

    public function canCompleteWork(): bool
    {
        return $this->status === 'in_progress';
    }

    public function isAwaitingSignoff(): bool
    {
        return $this->status === 'completed' && $this->customer_signed_at === null;
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
