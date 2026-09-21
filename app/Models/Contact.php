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
        'birthday',
        'anniversary',
    ];

    // loyalty_* + referral_code + *_greeted_on + phone_normalized columns are
    // deliberately NOT fillable — LoyaltyService / model boot own them.
    protected $casts = [
        'loyalty_points'          => 'integer',
        'loyalty_lifetime_points' => 'integer',
        'loyalty_updated_at'      => 'datetime',
        'birthday'                => 'date',
        'anniversary'             => 'date',
        'birthday_greeted_on'     => 'date',
        'anniversary_greeted_on'  => 'date',
        'phone_verified'          => 'boolean',
        'link_flagged_at'         => 'datetime',
        'stamp_count'             => 'integer',
        'stamps_lifetime'         => 'integer',
        'stamp_rewards_earned'    => 'integer',
        'stamp_updated_at'        => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (Contact $contact) {
            if (empty($contact->referral_code)) {
                $contact->referral_code = static::generateReferralCode($contact->tenant_id);
            }
        });

        static::saving(function (Contact $contact) {
            if ($contact->isDirty('phone')) {
                $digits = preg_replace('/\D/', '', (string) $contact->phone);
                $contact->phone_normalized = $digits === '' ? null : substr($digits, -10);

                // Correcting the number is how a tenant resolves a "not me" flag.
                if ($contact->exists && $contact->link_flagged_at && !$contact->isDirty('link_flagged_at')) {
                    $contact->link_flagged_at = null;
                }
            }
        });
    }

    // Short, unambiguous code (no 0/O/1/I) unique within the tenant.
    public static function generateReferralCode(?int $tenantId): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        do {
            $code = '';
            for ($i = 0; $i < 6; $i++) {
                $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }
            $exists = static::withoutGlobalScopes()
                ->where('tenant_id', $tenantId)
                ->where('referral_code', $code)
                ->exists();
        } while ($exists);

        return $code;
    }

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

    public function subscriptions(): HasMany
    {
        return $this->hasMany(ServiceSubscription::class)->latest();
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

    public function loyaltyTransactions(): HasMany
    {
        return $this->hasMany(LoyaltyTransaction::class)->latest()->latest('id');
    }

    public function loyaltyCampaignRecipients(): HasMany
    {
        return $this->hasMany(LoyaltyCampaignRecipient::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function referredBy(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'referred_by_contact_id');
    }

    public function referrals(): HasMany
    {
        return $this->hasMany(Contact::class, 'referred_by_contact_id');
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

    // Display label for the customer's current loyalty tier, or null when the
    // module is unused / they've never earned. Colours live in config/crm.php.
    public function loyaltyTierLabel(): ?string
    {
        if (!$this->loyalty_tier) {
            return null;
        }

        return config("crm.loyalty.tiers.{$this->loyalty_tier}.label", ucfirst($this->loyalty_tier));
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