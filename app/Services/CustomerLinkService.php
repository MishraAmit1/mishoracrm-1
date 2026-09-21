<?php

namespace App\Services;

use App\Models\Contact;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\LoyaltyTransaction;
use Illuminate\Support\Str;

/**
 * Links a platform-level Customer to their per-tenant Contact rows.
 *
 * A "thin" Contact (no real history — see isThinContact()) auto-links and is
 * immediately verified: low risk of a staff phone-number typo mattering. A
 * Contact with real history auto-links but stays UNVERIFIED — phone_verified
 * stays false — so the customer has to confirm "Is this you?" in the wallet
 * before the card is shown. This is the mitigation for Gap 2 (phone-as-
 * identity: a staff typo could otherwise hand someone else's points to a
 * stranger). See docs/customer-portal-loyalty.txt §2 + §12.
 *
 * Every Contact/Invoice/LoyaltyTransaction query here is explicit
 * withoutGlobalScopes() — this service runs cross-tenant, with no
 * authenticated tenant User in context, so BelongsToTenant's global scope
 * would silently return nothing (not an error) if relied on.
 */
class CustomerLinkService
{
    // Link-state columns are system-owned, and these writes often happen with no
    // tenant user in context — saveQuietly() keeps them out of the tenant audit
    // log (AuditLog::record would otherwise store a tenant-less, user-less row).
    // Link every not-yet-linked Contact across all tenants that matches this
    // Customer's phone (indexed, via phone_normalized) or email. Called on
    // every OTP login. Returns ['linked' => thin count, 'pending' => history count].
    public function linkByPhone(Customer $customer): array
    {
        $email = $customer->linkableEmail();

        $contacts = Contact::withoutGlobalScopes()
            ->whereNull('customer_id')
            ->where(function ($q) use ($customer, $email) {
                $q->where('phone_normalized', $customer->phone);
                if ($email) {
                    $q->orWhereRaw('LOWER(email) = ?', [$email]);
                }
            })
            ->get();

        $linked  = 0;
        $pending = 0;

        foreach ($contacts as $contact) {
            if ($this->isThinContact($contact)) {
                $contact->forceFill(['customer_id' => $customer->id, 'phone_verified' => true])->saveQuietly();
                $linked++;
            } else {
                $contact->forceFill(['customer_id' => $customer->id, 'phone_verified' => false])->saveQuietly();
                $pending++;
            }
        }

        return ['linked' => $linked, 'pending' => $pending];
    }

    // Called when a Contact is created/updated with a phone/email that
    // matches an EXISTING Customer. Never creates a Customer — a Customer
    // only ever comes into being via its own OTP login.
    public function attachContactToCustomer(Contact $contact): void
    {
        if ($contact->customer_id) {
            return;
        }

        $phone = $contact->phone_normalized;
        $email = $contact->email ? Str::lower($contact->email) : null;

        if ((!$phone || strlen($phone) < 10) && !$email) {
            return;
        }

        $customer = Customer::where(function ($q) use ($phone, $email) {
            if ($phone && strlen($phone) >= 10) {
                $q->where('phone', $phone);
            }
            if ($email) {
                // Only a verified email counts as proof of who owns the address.
                $q->orWhere(fn ($e) => $e->whereNotNull('email_verified_at')->whereRaw('LOWER(email) = ?', [$email]));
            }
        })->first();

        if (!$customer) {
            return;
        }

        if ($this->isThinContact($contact)) {
            $contact->forceFill(['customer_id' => $customer->id, 'phone_verified' => true])->saveQuietly();
        } else {
            $contact->forceFill(['customer_id' => $customer->id, 'phone_verified' => false])->saveQuietly();
        }
    }

    // Wallet "Is this you? [Yes] / [Not me]" response for a pending contact.
    public function confirmContact(Customer $customer, Contact $contact, bool $isMe): void
    {
        if ((int) $contact->customer_id !== (int) $customer->id) {
            return;
        }

        if ($isMe) {
            $contact->forceFill(['phone_verified' => true, 'link_flagged_at' => null])->saveQuietly();
            return;
        }

        $contact->forceFill([
            'customer_id'     => null,
            'phone_verified'  => false,
            'link_flagged_at' => now(),
        ])->saveQuietly();

        app(NotificationService::class)->sendToTenant(
            $contact->tenant_id,
            'loyalty.wrong_number',
            ['contact_id' => $contact->id, 'contact_name' => $contact->name],
            null,
            null,
            $contact
        );
    }

    // Low-risk to auto-link+verify without confirmation: no redemption
    // history, no paid invoice, and only a small welcome-bonus-sized balance.
    private function isThinContact(Contact $contact): bool
    {
        if ((int) $contact->loyalty_lifetime_points > 100) {
            return false;
        }

        if ((int) ($contact->stamps_lifetime ?? 0) > 0) {
            return false;
        }

        $hasRedemption = LoyaltyTransaction::withoutGlobalScopes()
            ->where('contact_id', $contact->id)
            ->where('type', LoyaltyTransaction::TYPE_REDEEM)
            ->exists();

        if ($hasRedemption) {
            return false;
        }

        $hasPaidInvoice = Invoice::withoutGlobalScopes()
            ->where('contact_id', $contact->id)
            ->where('status', 'paid')
            ->exists();

        return !$hasPaidInvoice;
    }
}
