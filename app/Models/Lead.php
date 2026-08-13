<?php

namespace App\Models;

use App\BelongsToTenant;
use App\Events\LeadConverted;
use App\HasAuditLog;
use App\HasCustomFields;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lead extends TenantModel
{
    use SoftDeletes, BelongsToTenant, HasCustomFields, HasAuditLog, HasFactory;

    public static string $customFieldModule = 'lead';

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
        'score',
        'assigned_to',
        'created_by',
        'notes',
        'lost_reason',
        'contacted_at',
        'qualified_at',
        'sla_notified_at',
        'converted_at',
        'expected_close_date',
    ];

    protected $casts = [
        'lead_value'           => 'decimal:2',
        'score'                => 'integer',
        'contacted_at'         => 'datetime',
        'qualified_at'         => 'datetime',
        'sla_notified_at'      => 'datetime',
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

    public function deal(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Deal::class);
    }

    public function callLogs(): HasMany
    {
        return $this->hasMany(LeadCallLog::class)->latest();
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

    public function emailLogs(): HasMany
    {
        return $this->hasMany(EmailLog::class)->latest();
    }

    public function whatsappLogs(): HasMany
    {
        return $this->hasMany(WhatsappLog::class)->latest();
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

    public function scopeConverted($query)
    {
        return $query->where('status', 'converted');
    }
    public function scopeActive($query)
    {
        return $query->whereNotIn('status', ['converted', 'lost']);
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

    // ── Status transition timestamps ──────────────────────────────
    // Single source of truth for which *_at column to stamp when a
    // lead's status changes — used by every status-update call site
    // (web + API) so they can't drift from one another again.
    public static function statusTimestamps(string $newStatus, ?string $oldStatus): array
    {
        $timestamps = [];

        if ($newStatus === 'contacted' && $oldStatus !== 'contacted') {
            $timestamps['contacted_at'] = now();
        }

        if ($newStatus === 'qualified' && $oldStatus !== 'qualified') {
            $timestamps['qualified_at'] = now();
        }

        if ($newStatus === 'converted' && $oldStatus !== 'converted') {
            $timestamps['converted_at'] = now();
        }

        return $timestamps;
    }

    // ── Convert to Contact ───────────────────────────────────────
    // Reused by LeadController::convert() and by QuotationController
    // when an accepted, lead-only quotation is converted to an invoice.
    public function convertToContact(): Contact
    {
        if ($this->isConverted()) {
            $existing = Contact::where('lead_id', $this->id)
                ->where('tenant_id', $this->tenant_id)
                ->first();

            if ($existing) {
                return $existing;
            }
        }

        $contact = Contact::create([
            'tenant_id'   => $this->tenant_id,
            'lead_id'     => $this->id,
            'name'        => $this->name,
            'phone'       => $this->phone,
            'email'       => $this->email,
            'company'     => $this->company ?? null,
            'designation' => $this->designation ?? null,
            'source'      => $this->source,
            'city'        => $this->city ?? null,
            'state'       => $this->state ?? null,
            'address'     => $this->address ?? null,
            'assigned_to' => $this->assigned_to,
            'created_by'  => auth()->id(),
        ]);

        Deal::create([
            'tenant_id'            => $this->tenant_id,
            'contact_id'           => $contact->id,
            'lead_id'              => $this->id,
            'title'                => $this->name . ' — Deal',
            'value'                => $this->lead_value ?? 0,
            'stage'                => 'new',
            'probability'          => 10,
            'expected_close_date'  => now()->addDays(30)->toDateString(),
            'notes'                => $this->notes,
            'assigned_to'          => $this->assigned_to,
            'created_by'           => auth()->id(),
        ]);

        $this->update([
            'status'       => 'converted',
            'converted_at' => now(),
        ]);

        event(new LeadConverted($this, $contact));

        return $contact;
    }

    public function getScoreLabelAttribute(): string
    {
        return match (true) {
            $this->score >= 70 => 'Hot',
            $this->score >= 40 => 'Warm',
            default             => 'Cold',
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'new'         => 'blue',
            'contacted'   => 'amber',
            'qualified'   => 'purple',
            'converted'   => 'green',
            'lost'        => 'red',
            default       => 'gray',
        };
    }

    public function getPriorityColorAttribute(): string
    {
        return match ($this->priority) {
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
            'facebook'   => 'Facebook / Meta Ads',
            'instagram'  => 'Instagram',
            'google'     => 'Google Ads',
            'website'    => 'Website',
            'whatsapp'   => 'WhatsApp',
            'indiamart'  => 'IndiaMART',
            'justdial'   => 'JustDial',
            'tradeindia' => 'TradeIndia',
            'sulekha'    => 'Sulekha',
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
            'new'       => ['label' => 'New',       'color' => 'accent', 'bg' => 'accent-dim', 'dot' => '#378ADD'],
            'contacted' => ['label' => 'Contacted',  'color' => 'amber',  'bg' => 'amber-dim',  'dot' => '#EF9F27'],
            'qualified' => ['label' => 'Qualified',  'color' => 'purple', 'bg' => 'purple-dim', 'dot' => '#534AB7'],
            'converted' => ['label' => 'Converted',  'color' => 'green',  'bg' => 'green-dim',  'dot' => '#1D9E75'],
            'lost'      => ['label' => 'Lost',       'color' => 'red',    'bg' => 'red-dim',    'dot' => '#E05252'],
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
