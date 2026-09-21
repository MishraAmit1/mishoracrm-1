<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Collection;

/**
 * Platform-level customer identity — one global, passwordless login for a
 * phone number, shared across every tenant the person is a customer of.
 * Deliberately NOT tenant-scoped (no BelongsToTenant): a Customer's whole
 * point is to see across tenants, so every Contact query here is explicit
 * withoutGlobalScopes() + tenant_id, never relying on ambient scoping.
 * See docs/customer-portal-loyalty.txt §1.
 */
class Customer extends Authenticatable
{
    use SoftDeletes;

    protected $fillable = [
        'phone',
        'name',
        'email',
        'pin_hash',
        'pin_attempts',
        'pin_locked_until',
        'last_login_at',
        'blocked_at',
    ];

    protected $hidden = [
        'pin_hash',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'pin_attempts'     => 'integer',
        'pin_locked_until' => 'datetime',
        'last_login_at'    => 'datetime',
        'blocked_at'       => 'datetime',
    ];

    // ── Relationships ─────────────────────────────────────────────

    // Contact::$customer_id points here across every tenant. The global
    // tenant scope on Contact would otherwise silently return nothing for
    // a Customer-guard request (no authed tenant User) — bypass it here.
    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class)->withoutGlobalScopes();
    }

    // ── Helpers ──────────────────────────────────────────────────

    public function isBlocked(): bool
    {
        return (bool) $this->blocked_at;
    }

    public function isPinLocked(): bool
    {
        return $this->pin_locked_until && $this->pin_locked_until->isFuture();
    }

    // Only a proven email may be used as a Contact-linking key (see the
    // email_verified_at migration). Lowercased, or null when not usable.
    public function linkableEmail(): ?string
    {
        return $this->email && $this->email_verified_at ? mb_strtolower($this->email) : null;
    }

    // Cards eligible to show in the wallet: verified link + both modules ON
    // (loyalty + customer_portal) + something worth showing. See §0A.1/§1.
    public function walletContacts(): Collection
    {
        return $this->contacts()
            ->where('phone_verified', true)
            ->with('tenant')
            ->get()
            ->filter(function (Contact $contact) {
                $tenant = $contact->tenant;

                if (!$tenant || !$tenant->hasModuleEnabled('loyalty') || !$tenant->hasModuleEnabled('customer_portal')) {
                    return false;
                }

                return (int) $contact->loyalty_lifetime_points > 0
                    || (int) ($contact->stamp_count ?? 0) > 0
                    || $contact->loyaltyCampaignRecipients()->withoutGlobalScopes()->exists();
            })
            ->values();
    }

    // Linked-but-unconfirmed Contacts waiting on an "Is this you?" answer.
    public function pendingContacts(): Collection
    {
        return $this->contacts()
            ->whereNotNull('customer_id')
            ->where('phone_verified', false)
            ->with('tenant')
            ->get()
            ->filter(fn (Contact $contact) => $contact->tenant
                && $contact->tenant->hasModuleEnabled('loyalty')
                && $contact->tenant->hasModuleEnabled('customer_portal'))
            ->values();
    }
}
