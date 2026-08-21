<?php

namespace App\Models;

use App\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TimeEntry extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'contact_id',
        'task_id',
        'service_id',
        'invoice_id',
        'started_at',
        'ended_at',
        'duration_minutes',
        'hourly_rate',
        'is_billable',
        'is_invoiced',
        'notes',
    ];

    protected $casts = [
        'started_at'        => 'datetime',
        'ended_at'          => 'datetime',
        'duration_minutes'  => 'integer',
        'hourly_rate'       => 'decimal:2',
        'is_billable'       => 'boolean',
        'is_invoiced'       => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
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

    public function scopeRunning($query)
    {
        return $query->whereNull('ended_at');
    }

    public function scopeBillableUninvoiced($query)
    {
        return $query->where('is_billable', true)->where('is_invoiced', false)->whereNotNull('ended_at');
    }

    // ── Helpers ───────────────────────────────────────────────────

    public function isRunning(): bool
    {
        return $this->ended_at === null;
    }

    public function durationHours(): float
    {
        if ($this->duration_minutes !== null) {
            return round($this->duration_minutes / 60, 2);
        }

        if ($this->isRunning()) {
            return round($this->started_at->diffInMinutes(now()) / 60, 2);
        }

        return 0.0;
    }

    // Stops a running entry, computing duration_minutes from the elapsed
    // time, then keeps the linked Task's actual_hours in sync.
    public function stop(): void
    {
        if (!$this->isRunning()) {
            return;
        }

        $endedAt = now();

        $this->update([
            'ended_at'          => $endedAt,
            'duration_minutes'  => max(1, (int) round($this->started_at->diffInMinutes($endedAt))),
        ]);

        $this->task?->recalculateActualHours();
    }
}
