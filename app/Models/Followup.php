<?php

namespace App\Models;

use App\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Followup extends TenantModel
{
    use SoftDeletes, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'lead_id',
        'contact_id',
        'deal_id',
        'assigned_to',
        'created_by',
        'type',
        'scheduled_at',
        'done_at',
        'notes',
        'outcome',
        'status',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'done_at'      => 'datetime',
    ];

    // ── Relationships ─────────────────────────────────────────────

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function deal(): BelongsTo
    {
        return $this->belongsTo(Deal::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
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

    public function scopeScheduled($query)
    {
        return $query->where('status', 'scheduled');
    }

    public function scopeToday($query)
    {
        return $query->whereDate('scheduled_at', today());
    }

    public function scopeUpcoming($query)
    {
        return $query->where('status', 'scheduled')
                     ->where('scheduled_at', '>=', now())
                     ->orderBy('scheduled_at');
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', 'scheduled')
                     ->where('scheduled_at', '<', now());
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('assigned_to', $userId);
    }

    // ── Helpers ───────────────────────────────────────────────────

    public function isScheduled(): bool
    {
        return $this->status === 'scheduled';
    }

    public function isDone(): bool
    {
        return $this->status === 'done';
    }

    public function isMissed(): bool
    {
        return $this->status === 'missed';
    }

    public function isOverdue(): bool
    {
        return $this->status === 'scheduled' && $this->scheduled_at->isPast();
    }

    // ── Static helpers ────────────────────────────────────────────

    public static function types(): array
    {
        return [
            'call'      => 'Phone Call',
            'email'     => 'Email',
            'whatsapp'  => 'WhatsApp',
            'meeting'   => 'Meeting',
            'other'     => 'Other',
        ];
    }

    public static function statuses(): array
    {
        return [
            'scheduled'   => 'Scheduled',
            'done'        => 'Done',
            'missed'      => 'Missed',
            'rescheduled' => 'Rescheduled',
        ];
    }
}