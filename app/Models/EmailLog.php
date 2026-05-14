<?php

namespace App\Models;

use App\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailLog extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'template_id',
        'lead_id',
        'contact_id',
        'sent_by',
        'to_email',
        'to_name',
        'subject',
        'body',
        'status',
        'error_message',
        'is_bulk',
        'bulk_id',
        'sent_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'is_bulk' => 'boolean',
    ];

    // ── Relationships ─────────────────────────────────────────────

    public function template(): BelongsTo
    {
        return $this->belongsTo(EmailTemplate::class);
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function sentBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    // ── Scopes ────────────────────────────────────────────────────

    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    public function scopeThisMonth($query)
    {
        return $query->whereMonth('created_at', now()->month)
                     ->whereYear('created_at', now()->year);
    }

    public function scopeBulk($query)
    {
        return $query->where('is_bulk', true);
    }

    public function scopeSingle($query)
    {
        return $query->where('is_bulk', false);
    }

    // ── Helpers ───────────────────────────────────────────────────

    public function isSent(): bool
    {
        return $this->status === 'sent';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }
}