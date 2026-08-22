<?php

namespace App\Models;

use App\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Ticket extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'ticket_number',
        'contact_id',
        'service_id',
        'assigned_to',
        'created_by',
        'subject',
        'description',
        'status',
        'priority',
        'source',
        'public_token',
        'resolved_at',
        'sla_notified_at',
    ];

    protected $casts = [
        'resolved_at'      => 'datetime',
        'sla_notified_at'  => 'datetime',
    ];

    // First-response SLA window per priority — how long a ticket can sit
    // with no staff reply before it's flagged as breached.
    public static function slaHours(): array
    {
        return [
            'urgent' => 1,
            'high'   => 4,
            'medium' => 24,
            'low'    => 48,
        ];
    }

    public static function statuses(): array
    {
        return [
            'open'        => 'Open',
            'in_progress' => 'In Progress',
            'resolved'    => 'Resolved',
            'closed'      => 'Closed',
        ];
    }

    public static function priorities(): array
    {
        return [
            'low'    => 'Low',
            'medium' => 'Medium',
            'high'   => 'High',
            'urgent' => 'Urgent',
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

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function replies(): HasMany
    {
        return $this->hasMany(TicketReply::class)->oldest();
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(TicketAttachment::class)->latest();
    }

    // ── Scopes ────────────────────────────────────────────────────

    public function scopeOpenTickets($query)
    {
        return $query->whereIn('status', ['open', 'in_progress']);
    }

    public function scopeMine($query, int $userId)
    {
        return $query->where('assigned_to', $userId);
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
        return route('public.support.ticket', $this->ensurePublicToken());
    }

    // Human-friendly reference number, e.g. TKT-20260822-0001 — the
    // public_token stays the actual access-control key; this is what the
    // customer quotes over phone/email and types into the tracking lookup.
    // Mirrors Invoice::generateNumber() exactly.
    public static function generateNumber(?int $tenantId = null): string
    {
        $tenantId = $tenantId ?? auth()->user()->tenant_id;

        $lastId = static::withoutGlobalScopes()
            ->where('tenant_id', $tenantId)
            ->max('id') ?? 0;

        $num = str_pad($lastId + 1, 4, '0', STR_PAD_LEFT);
        return 'TKT-' . now()->format('Ymd') . '-' . $num;
    }
}
