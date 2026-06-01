<?php

namespace App\Services\Integrations;

use App\Models\Lead;
use App\Models\TenantIntegration;
use Illuminate\Support\Facades\Log;

/**
 * JustDial Lead Integration
 *
 * Type: WEBHOOK (JustDial pushes leads via HTTP POST)
 *
 * Setup:
 *   1. Login to JustDial Vendor Panel → API Integration
 *   2. Provide webhook URL: /webhook/leads/{webhook_token}
 *   3. JustDial will POST lead data to this URL
 *
 * Optional: JustDial can send a secret key in headers for verification.
 * Store it as 'secret_key' in credentials.
 *
 * Payload format (form-encoded or JSON, both handled):
 *   leadid, type, category, name, mobile, email, city, cityname,
 *   area, company, branchname, comments, calltype, duration
 */
class JustDialLeadService
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

    public function processWebhook(array $payload): bool
    {
        $leadId = $payload['leadid'] ?? null;
        $phone  = $this->cleanPhone($payload['mobile'] ?? $payload['phone'] ?? null);
        $name   = $payload['name']  ?? 'JustDial Lead';
        $email  = $payload['email'] ?? null;

        // Dedup by JustDial lead ID
        if ($leadId) {
            $exists = Lead::where('tenant_id', $this->integration->tenant_id)
                ->where('notes', 'like', "%JDID:{$leadId}%")
                ->exists();
            if ($exists) return false;
        } elseif ($phone) {
            $exists = Lead::where('tenant_id', $this->integration->tenant_id)
                ->where('phone', $phone)
                ->where('source', 'justdial')
                ->where('created_at', '>=', now()->subDay())
                ->exists();
            if ($exists) return false;
        }

        $city     = $payload['cityname'] ?? $payload['city'] ?? null;
        $company  = $payload['company']  ?? $payload['branchname'] ?? null;
        $comment  = $payload['comments'] ?? null;
        $category = $payload['category'] ?? null;

        $notes = trim(implode("\n", array_filter([
            $leadId   ? "JDID:{$leadId}"            : null,
            $category ? "Category: {$category}"     : null,
            $comment  ? "Message: {$comment}"       : null,
        ])));

        Lead::create([
            'tenant_id'  => $this->integration->tenant_id,
            'name'       => $name,
            'phone'      => $phone,
            'email'      => $email,
            'company'    => $company,
            'city'       => $city,
            'source'     => 'justdial',
            'status'     => 'new',
            'priority'   => 'medium',
            'notes'      => $notes ?: null,
            'created_by' => null,
        ]);

        $this->integration->increment('leads_imported');
        $this->integration->update(['last_synced_at' => now()]);

        return true;
    }

    private function cleanPhone(?string $phone): ?string
    {
        if (!$phone) return null;
        $cleaned = preg_replace('/\D/', '', $phone);
        return strlen($cleaned) >= 10 ? $cleaned : $phone;
    }
}
