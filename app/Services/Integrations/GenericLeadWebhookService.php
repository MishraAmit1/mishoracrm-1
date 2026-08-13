<?php

namespace App\Services\Integrations;

use App\Models\Lead;
use App\Models\TenantIntegration;
use App\Services\DuplicateMatcher;

/**
 * Generic Lead Webhook Integration (TradeIndia, Sulekha, etc.)
 *
 * These platforms don't have a dedicated API/SDK — they POST a simple
 * form-like payload to our webhook URL. This service handles the shared
 * field-mapping + verification + dedup logic for all of them.
 *
 * Optional: platform can send a secret key in headers/query for
 * verification. Store it as 'secret_key' in credentials.
 */
class GenericLeadWebhookService
{
    public function __construct(private TenantIntegration $integration) {}

    public static function forIntegration(TenantIntegration $integration): self
    {
        return new self($integration);
    }

    // ── Verify Request ────────────────────────────────────────────

    public function verifyRequest(string $providedKey): bool
    {
        $secretKey = $this->integration->getCredential('secret_key');
        if (empty($secretKey)) return true; // no key configured = allow all

        return hash_equals($secretKey, $providedKey);
    }

    // ── Process Incoming Webhook Payload ─────────────────────────

    public function processWebhook(array $data): bool
    {
        $name  = $data['name']    ?? $data['full_name']    ?? $data['contact_name'] ?? 'Lead';
        $phone = $data['phone']   ?? $data['mobile']       ?? $data['contact']      ?? null;
        $email = $data['email']   ?? $data['email_id']     ?? null;
        $city  = $data['city']    ?? $data['location']     ?? null;
        $msg   = $data['message'] ?? $data['requirements'] ?? $data['query']         ?? null;

        if (!$name && !$phone && !$email) {
            return false;
        }

        if (DuplicateMatcher::findExistingLead($this->integration->tenant_id, $phone, $email)) {
            return false;
        }

        Lead::create([
            'tenant_id'  => $this->integration->tenant_id,
            'name'       => $name,
            'phone'      => $phone,
            'email'      => $email,
            'city'       => $city,
            'source'     => $this->integration->platform,
            'status'     => 'new',
            'priority'   => 'medium',
            'notes'      => $msg,
            'created_by' => null,
        ]);

        $this->integration->increment('leads_imported');
        $this->integration->update(['last_synced_at' => now()]);

        return true;
    }
}
