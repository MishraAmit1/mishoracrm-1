<?php

namespace App\Models;

use App\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Deal extends Model
{
    use SoftDeletes, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'contact_id',
        'lead_id',
        'title',
        'value',
        'stage',
        'probability',
        'expected_close_date',
        'actual_close_date',
        'notes',
        'lost_reason',
        'assigned_to',
        'created_by',
    ];

    protected $casts = [
        'value'               => 'decimal:2',
        'probability'         => 'integer',
        'expected_close_date' => 'date',
        'actual_close_date'   => 'date',
    ];

    // ── Relationships ─────────────────────────────────────────────

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function tasks(): MorphMany
    {
        return $this->morphMany(Task::class, 'taskable')->latest();
    }

    public function followups(): HasMany
    {
        return $this->hasMany(Followup::class)->latest();
    }

    // ── Scopes ────────────────────────────────────────────────────

    public function scopeStage($query, string $stage)
    {
        return $query->where('stage', $stage);
    }

    public function scopeOpen($query)
    {
        return $query->whereNotIn('stage', ['won', 'lost']);
    }

    public function scopeWon($query)
    {
        return $query->where('stage', 'won');
    }

    public function scopeLost($query)
    {
        return $query->where('stage', 'lost');
    }

    public function scopeAssignedTo($query, int $userId)
    {
        return $query->where('assigned_to', $userId);
    }

    public function scopeSearch($query, string $search)
    {
        return $query->where('title', 'like', "%{$search}%");
    }

    public function scopeThisMonth($query)
    {
        return $query->whereMonth('created_at', now()->month)
                     ->whereYear('created_at', now()->year);
    }

    // ── Helpers ───────────────────────────────────────────────────

    public function isWon(): bool
    {
        return $this->stage === 'won';
    }

    public function isLost(): bool
    {
        return $this->stage === 'lost';
    }

    public function isOpen(): bool
    {
        return !in_array($this->stage, ['won', 'lost']);
    }

    public function getFormattedValueAttribute(): string
    {
        return '₹' . number_format($this->value, 0);
    }

    public function getStageColorAttribute(): string
    {
        return match($this->stage) {
            'new'         => 'blue',
            'proposal'    => 'amber',
            'negotiation' => 'purple',
            'won'         => 'green',
            'lost'        => 'red',
            default       => 'gray',
        };
    }

    // ── Static helpers ────────────────────────────────────────────

    public static function stages(): array
    {
        return [
            'new'         => 'New',
            'proposal'    => 'Proposal',
            'negotiation' => 'Negotiation',
            'won'         => 'Won',
            'lost'        => 'Lost',
        ];
    }
}