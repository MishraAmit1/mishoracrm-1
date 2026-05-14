<?php

namespace App\Models;

use App\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lead extends TenantModel
{
    use SoftDeletes, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'name',
        'phone',
        'email',
        'company',
        'designation',
        'city',
        'state',
        'source',
        'status',
        'priority',
        'lead_value',
        'assigned_to',
        'created_by',
        'notes',
        'lost_reason',
        'contacted_at',
        'converted_at',
        'expected_close_date',
    ];

    protected $casts = [
        'lead_value'           => 'decimal:2',
        'contacted_at'         => 'datetime',
        'converted_at'         => 'datetime',
        'expected_close_date'  => 'datetime',
    ];

    // ── Relationships ─────────────────────────────────────────────

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

    public function followups(): HasMany
    {
        return $this->hasMany(Followup::class)->latest();
    }

    public function tasks(): MorphMany
    {
        return $this->morphMany(Task::class, 'taskable')->latest();
    }

    public function reminders(): MorphMany
    {
        return $this->morphMany(Reminder::class, 'remindable')->latest();
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    // ── Scopes ────────────────────────────────────────────────────

    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeSource($query, string $source)
    {
        return $query->where('source', $source);
    }

    public function scopeAssignedTo($query, int $userId)
    {
        return $query->where('assigned_to', $userId);
    }

    public function scopePriority($query, string $priority)
    {
        return $query->where('priority', $priority);
    }

    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('phone', 'like', "%{$search}%")
              ->orWhere('email', 'like', "%{$search}%")
              ->orWhere('company', 'like', "%{$search}%");
        });
    }

    public function scopeThisMonth($query)
    {
        return $query->whereMonth('created_at', now()->month)
                     ->whereYear('created_at', now()->year);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    // ── Helpers ───────────────────────────────────────────────────

    public function isNew(): bool
    {
        return $this->status === 'new';
    }

    public function isConverted(): bool
    {
        return $this->status === 'converted';
    }

    public function isLost(): bool
    {
        return $this->status === 'lost';
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'new'         => 'blue',
            'contacted'   => 'amber',
            'qualified'   => 'purple',
            'proposal'    => 'indigo',
            'negotiation' => 'orange',
            'converted'   => 'green',
            'lost'        => 'red',
            default       => 'gray',
        };
    }

    public function getPriorityColorAttribute(): string
    {
        return match($this->priority) {
            'high'   => 'red',
            'medium' => 'amber',
            'low'    => 'green',
            default  => 'gray',
        };
    }

    public function getFormattedValueAttribute(): string
    {
        if (!$this->lead_value) return '—';
        return '₹' . number_format($this->lead_value, 0);
    }

    // ── Static helpers ────────────────────────────────────────────

    public static function sources(): array
    {
        return [
            'facebook'   => 'Facebook',
            'instagram'  => 'Instagram',
            'google'     => 'Google Ads',
            'website'    => 'Website',
            'whatsapp'   => 'WhatsApp',
            'referral'   => 'Referral',
            'cold_call'  => 'Cold Call',
            'email'      => 'Email',
            'walk_in'    => 'Walk-in',
            'other'      => 'Other',
        ];
    }

    public static function statuses(): array
    {
        return [
            'new'         => 'New',
            'contacted'   => 'Contacted',
            'qualified'   => 'Qualified',
            'proposal'    => 'Proposal',
            'negotiation' => 'Negotiation',
            'converted'   => 'Converted',
            'lost'        => 'Lost',
        ];
    }

    public static function priorities(): array
    {
        return [
            'low'    => 'Low',
            'medium' => 'Medium',
            'high'   => 'High',
        ];
    }
}