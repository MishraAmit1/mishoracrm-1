<?php

namespace App\Models;

use App\BelongsToTenant;
use App\HasAuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Contact extends Model
{
    use SoftDeletes, BelongsToTenant, HasAuditLog;

    protected $fillable = [
        'tenant_id',
        'lead_id',
        'name',
        'phone',
        'email',
        'company',
        'designation',
        'address',
        'city',
        'state',
        'pincode',
        'gst_number',
        'notes',
    ];

    // ── Relationships ─────────────────────────────────────────────

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function deals(): HasMany
    {
        return $this->hasMany(Deal::class)->latest();
    }

    public function followups(): HasMany
    {
        return $this->hasMany(Followup::class)->latest();
    }

    public function tasks(): MorphMany
    {
        return $this->morphMany(Task::class, 'taskable')->latest();
    }

    public function quotations(): HasMany
    {
        return $this->hasMany(Quotation::class)->latest();
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class)->latest();
    }

    public function emailLogs(): HasMany
    {
        return $this->hasMany(EmailLog::class)->latest();
    }

    public function whatsappLogs(): HasMany
    {
        return $this->hasMany(WhatsappLog::class)->latest();
    }

    public function employees(): HasMany
    {
        return $this->hasMany(ContactEmployee::class)->orderByDesc('is_primary')->orderBy('id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(ContactAttachment::class);
    }

    // ── Scopes ────────────────────────────────────────────────────

    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name',    'like', "%{$search}%")
              ->orWhere('phone', 'like', "%{$search}%")
              ->orWhere('email', 'like', "%{$search}%")
              ->orWhere('company', 'like', "%{$search}%");
        });
    }

    // ── Helpers ───────────────────────────────────────────────────

    // Primary recipient for outgoing emails: the primary employee's first
    // email if one is set, otherwise falls back to the contact's own email.
    public function primaryEmail(): ?string
    {
        $primaryEmployee = $this->employees->firstWhere('is_primary', true);
        $primaryEmails   = $primaryEmployee ? array_values(array_filter($primaryEmployee->emails ?? [])) : [];

        return $primaryEmails[0] ?? $this->email;
    }

    // Every other known email (contact's own + all employees' emails) minus
    // whichever one is being used as the primary — for CC'ing on outgoing emails.
    public function ccEmails(): array
    {
        $primary = $this->primaryEmail();
        $pool    = [];

        if ($this->email && $this->email !== $primary) {
            $pool[] = ['email' => $this->email, 'name' => $this->name];
        }

        foreach ($this->employees as $employee) {
            foreach ($employee->emails ?? [] as $email) {
                if (!$email || $email === $primary) continue;
                $pool[] = ['email' => $email, 'name' => $employee->name];
            }
        }

        $seen   = [];
        $result = [];
        foreach ($pool as $entry) {
            $key = strtolower($entry['email']);
            if (isset($seen[$key])) continue;
            $seen[$key] = true;
            $result[]   = $entry;
        }

        return $result;
    }

    public function getFullAddressAttribute(): string
    {
        return collect([
            $this->address,
            $this->city,
            $this->state,
            $this->pincode,
        ])->filter()->join(', ');
    }
}